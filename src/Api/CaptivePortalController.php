<?php

class CaptivePortalController
{
    private $db;
    private $auth;
    private $templatesDir;

    public function __construct($db, $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->templatesDir = realpath(__DIR__ . '/../../Portail Captif');
    }

    /**
     * List available captive portal templates
     */
    public function listTemplates()
    {
        $this->auth->requireRole('admin');

        if (!$this->templatesDir || !is_dir($this->templatesDir)) {
            jsonResponse(['templates' => []]);
        }

        $templates = [];
        $dirs = array_filter(glob($this->templatesDir . '/*'), 'is_dir');

        foreach ($dirs as $dir) {
            $folderName = basename($dir);
            // On ne prend que Template 18 et Template 19 pour le moment comme demandé
            if (in_array($folderName, ['Template 18', 'Template 19'])) {
                $config = [];
                $configFile = $dir . '/config.json';
                if (file_exists($configFile)) {
                    $config = json_decode(file_get_contents($configFile), true) ?: [];
                }

                $templates[] = [
                    'id' => str_replace(' ', '_', $folderName),
                    'name' => $folderName,
                    'path' => $dir,
                    'config' => $config
                ];
            }
        }

        jsonResponse(['templates' => $templates]);
    }

    /**
     * Get a specific template details for the editor
     */
    public function getTemplate($params)
    {
        $this->auth->requireRole('admin');

        $id = is_array($params) ? ($params['id'] ?? '') : (string)$params;
        $folderName = str_replace('_', ' ', $id);
        $templateDir = $this->templatesDir . '/' . $folderName;

        if (!$this->templatesDir || !is_dir($templateDir) || !in_array($folderName, ['Template 18', 'Template 19'])) {
            jsonError('Template not found', 404);
        }

        // Parse actual current values from HTML
        $config = [
            'page_name' => 'Mon Hotspot',
            'phone' => '',
            'email' => '',
            'audio_enabled' => false,
            'slide_enabled' => false,
            'slider_images' => [],
            'logo_url' => '',
            'recover_ticket' => false,
            'recover_ticket_url' => '',
            'live_chat' => false,
            'chat_support_type' => 'live_chat',
            'chat_whatsapp_phone' => '',
            'buy_button' => true,
            'buy_ticket' => false,
            'buy_ticket_url' => '',
            'otp_enabled' => false,
            'registration_enabled' => false,
            'connection_mode' => 'voucher',
            'selected_zone' => null,
            'selected_profiles' => [],
            'services' => [],
            'custom_css' => '',
            'custom_js' => '',
            'custom_html' => '',
        ];

        // Charger la config JSON persistée si elle existe
        $configFile = $templateDir . '/config.json';
        if (file_exists($configFile)) {
            $savedConfig = json_decode(file_get_contents($configFile), true);
            if (is_array($savedConfig)) {
                // Fusionner toutes les clés sauvegardées
                foreach ($savedConfig as $key => $value) {
                    if (array_key_exists($key, $config)) {
                        $config[$key] = $value;
                    }
                }
            }
        }

        $loginPath = $templateDir . '/login.html';
        if (file_exists($loginPath)) {
            $content = file_get_contents($loginPath);

            // Extract Page Name (from title)
            if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
                $config['page_name'] = html_entity_decode($matches[1]);
            }

            // Extract Connection Mode
            if (preg_match('/switchMode\(\'(.*?)\'\);/i', $content, $matches)) {
                // S'assurer qu'on ne prend pas la définition de la fonction, mais l'appel d'initialisation
                if (preg_match('/switchMode\(\'(voucher|member)\'\);/i', $content, $matches)) {
                    // Check if it's inside the DOMContentLoaded block
                    preg_match_all('/switchMode\(\'(voucher|member)\'\);/i', $content, $allMatches);
                    // The last one is usually the default init one in the script block
                    if (!empty($allMatches[1])) {
                        $config['connection_mode'] = end($allMatches[1]);
                    }
                }
            }

            // Extract Audio state - vérifie si le script audio-player.js est présent
            if (strpos($content, 'js/audio-player.js') !== false) {
                $config['audio_enabled'] = true;
            } else {
                $config['audio_enabled'] = false;
            }

            // Extract Slide state
            if (strpos($content, 'style="display: none;" class="slider-container"') !== false ||
            strpos($content, 'style="display:none" class="slider-container"') !== false) {
                $config['slide_enabled'] = false;
            }
            else if (strpos($content, 'class="slider-container"') !== false) {
                $config['slide_enabled'] = true;
            }

            // Extract Recover Ticket state
            if (strpos($content, 'style="display: none;" class="ticket-section"') !== false ||
            strpos($content, 'style="display:none" class="ticket-section"') !== false) {
                $config['recover_ticket'] = false;
            }
            else if (strpos($content, 'class="ticket-section"') !== false) {
                $config['recover_ticket'] = true;
            }

            // Extract Buy Ticket state
            if (strpos($content, '<!-- BUY_TICKET_START -->') !== false) {
                $config['buy_ticket'] = true;
            }

            // Extract OTP state
            if (strpos($content, '<!-- OTP_SCRIPT_START -->') !== false) {
                $config['otp_enabled'] = true;
            }

            // Extract Registration state
            if (strpos($content, '<!-- REGISTRATION_SCRIPT_START -->') !== false) {
                $config['registration_enabled'] = true;
            }

            // Extract custom CSS
            if (preg_match('/<!-- CUSTOM_CSS_START -->\s*<style>(.*?)<\/style>\s*<!-- CUSTOM_CSS_END -->/is', $content, $matches)) {
                $config['custom_css'] = trim($matches[1]);
            }

            // Extract custom JS
            if (preg_match('/<!-- CUSTOM_JS_START -->\s*<script>(.*?)<\/script>\s*<!-- CUSTOM_JS_END -->/is', $content, $matches)) {
                $config['custom_js'] = trim($matches[1]);
            }
        }

        // Toujours régénérer les URLs avec l'admin_id courant
        $baseUrl = $this->detectBaseUrl();
        $adminId = '';
        try {
            $user = $this->auth->getUser();
            if ($user) $adminId = $user->getId();
        } catch (\Exception $e) {}
        $config['buy_ticket_url'] = $baseUrl . '/pay.php?admin=' . $adminId;
        $config['recover_ticket_url'] = $baseUrl . '/retrieve-ticket.php?admin=' . $adminId;
        $config['admin_id'] = $adminId;

        $data = [
            'id' => $id,
            'name' => $folderName,
            'files' => [
                'login' => file_exists($templateDir . '/login.html'),
                'logout' => file_exists($templateDir . '/logout.html'),
                'status' => file_exists($templateDir . '/status.html'),
                'tarifs' => file_exists($templateDir . '/tarifs.html'),
                'services' => file_exists($templateDir . '/services.html'),
            ],
            'config' => $config
        ];

        jsonResponse($data);
    }

    /**
     * Save template configuration and modify HTML files
     */
    public function saveTemplate($params)
    {
        $this->auth->requireRole('admin');

        $id = is_array($params) ? ($params['id'] ?? '') : $params;
        $folderName = str_replace('_', ' ', $id);
        $templateDir = $this->templatesDir . '/' . $folderName;

        if (!$this->templatesDir || !is_dir($templateDir) || !in_array($folderName, ['Template 18', 'Template 19'])) {
            jsonError('Template not found', 404);
        }

        $data = getJsonBody();

        // Sauvegarder la config JSON complète pour persistance
        $configFile = $templateDir . '/config.json';
        $adminId = getAdminId() ?? 1;
        $configToSave = [
            'admin_id' => $adminId,
            'selected_zone' => $data['selected_zone'] ?? null,
            'selected_profiles' => $data['selected_profiles'] ?? [],
            'services' => $data['services'] ?? [],
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'slider_images' => $data['slider_images'] ?? [],
            'logo_url' => $data['logo_url'] ?? '',
            'recover_ticket_url' => $data['recover_ticket_url'] ?? '',
            'live_chat' => $data['live_chat'] ?? false,
            'chat_support_type' => $data['chat_support_type'] ?? 'live_chat',
            'chat_whatsapp_phone' => $data['chat_whatsapp_phone'] ?? '',
            'buy_button' => $data['buy_button'] ?? true,
            'buy_ticket' => $data['buy_ticket'] ?? false,
            'buy_ticket_url' => $data['buy_ticket_url'] ?? '',
            'otp_enabled' => $data['otp_enabled'] ?? false,
            'registration_enabled' => $data['registration_enabled'] ?? false,
            'connection_mode' => $data['connection_mode'] ?? 'voucher',
            'audio_enabled' => $data['audio_enabled'] ?? false,
            'slide_enabled' => $data['slide_enabled'] ?? false,
            'recover_ticket' => $data['recover_ticket'] ?? false,
            'custom_css' => $data['custom_css'] ?? '',
            'custom_js' => $data['custom_js'] ?? '',
            'custom_html' => $data['custom_html'] ?? '',
        ];
        file_put_contents($configFile, json_encode($configToSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $filesToUpdate = ['login.html', 'logout.html', 'status.html', 'tarifs.html', 'services.html'];

        $tariffsHtml = '';
        if (!empty($data['selected_profiles'])) {
            $zoneId = $data['selected_zone'] ?? null;
            $showBuyBtn = $data['buy_button'] ?? true;
            $tariffsHtml = $this->generateTariffsHtml($data['selected_profiles'], $zoneId, $showBuyBtn);
        }

        // Load services if configured
        $servicesHtmlCards = '';
        $servicesHtmlList = '';
        if (!empty($data['services'])) {
            $servicesHtmlCards = $this->generateServicesHtmlCards($data['services']);
            $servicesHtmlList = $this->generateServicesHtmlList($data['services']);
        }

        // Si custom_html est fourni, remplacer entièrement login.html
        $customHtml = trim($data['custom_html'] ?? '');

        foreach ($filesToUpdate as $filename) {
            $filePath = $templateDir . '/' . $filename;
            if (file_exists($filePath)) {

                // Si HTML complet personnalisé, remplacer login.html entièrement
                if ($filename === 'login.html' && $customHtml !== '') {
                    file_put_contents($filePath, $customHtml);
                    continue;
                }

                $content = file_get_contents($filePath);
                $content = $this->applyGeneralModifications($content, $data, $filename);

                if (in_array($filename, ['login.html', 'tarifs.html'])) {
                    $content = $this->injectTariffsHtml($content, $tariffsHtml, $filename);
                }

                if ($servicesHtmlCards !== '' && $filename === 'services.html') {
                    $content = $this->injectServicesHtmlCards($content, $servicesHtmlCards);
                }

                if ($servicesHtmlList !== '' && $filename === 'tarifs.html') {
                    $content = $this->injectServicesHtmlList($content, $servicesHtmlList);
                }

                // Injecter CSS/JS personnalisé sur toutes les pages
                $content = $this->injectCustomCode($content, $data);

                file_put_contents($filePath, $content);
            }
        }

        jsonResponse(['success' => true, 'message' => 'Configuration sauvegardée', 'data' => $data]);
    }

    /**
     * Détecte l'URL de base du serveur
     */
    private function detectBaseUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(dirname($scriptName), '/\\');
        if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        return $protocol . '://' . $host . $basePath;
    }

    /**
     * Génère le HTML des cartes de tarifs basées sur les profils
     */
    private function generateTariffsHtml($profileIds, $zoneId = null, $showBuyBtn = true)
    {
        $baseUrl = $this->detectBaseUrl();
        $adminId = '';
        try {
            $user = $this->auth->getUser();
            if ($user) $adminId = $user->getId();
        } catch (\Exception $e) {}

        $html = '';
        foreach ($profileIds as $id) {
            $stmt = $this->db->getPdo()->prepare("SELECT * FROM profiles WHERE id = ?");
            $stmt->execute([(int)$id]);
            $profile = $stmt->fetch();

            if ($profile) {
                $name = htmlspecialchars($profile['name']);
                $price = number_format($profile['price'], 0, '', ' ') . ' Fcfa';
                $link = $baseUrl . '/pay.php?profile=' . (int)$profile['id'] . '&admin=' . $adminId;

                $featuresHtml = "";
                if (!empty($profile['description'])) {
                    $lines = array_filter(array_map('trim', explode("\n", $profile['description'])));
                    foreach ($lines as $line) {
                        $featuresHtml .= "                            <li>" . htmlspecialchars(ltrim($line, '- ')) . "</li>\n";
                    }
                } else {
                    $featuresHtml = "                            <li>Connexion haute vitesse</li>\n                            <li>Achat via mobile money : Flooz ou Mix by Yas</li>\n                            <li>Accès illimité</li>\n";
                }

                $buyBtnStyle = $showBuyBtn ? '' : ' style="display: none;"';
                $html .= "                    <div class=\"tariff-card\">\n"
                    . "                        <div class=\"tariff-header\">\n"
                    . "                            <div class=\"tariff-name\">{$name}</div>\n"
                    . "                            <div class=\"tariff-price\">{$price}</div>\n"
                    . "                        </div>\n"
                    . "                        <ul class=\"tariff-features\">\n"
                    . $featuresHtml
                    . "                        </ul>\n"
                    . "                        <a href=\"{$link}\" target=\"_blank\" class=\"buy-btn\"{$buyBtnStyle}>Acheter</a>\n"
                    . "                    </div>\n";
            }
        }
        return $html;
    }

    /**
     * Injecte les cartes de tarifs directement dans le conteneur HTML
     */
    private function injectTariffsHtml($content, $tariffsHtml, $filename)
    {
        // Enlever l'injection JSON si elle existait précédemment
        $content = preg_replace('/<script id="tariffs-data".*?<\/script>/is', '', $content);
        $content = preg_replace('/<script id="tariffs-renderer".*?<\/script>/is', '', $content);

        $pattern = null;
        if ($filename === 'login.html') {
            // Capturer: <h2 class="tariffs-title">...</h2> ... </div> ... <p class="info bt">
            $pattern = '/(<div class="tariffs-container">\s*<h2 class="tariffs-title">.*?<\/h2>\s*)(.*?)(\s*<\/div>\s*<p class="info bt">)/is';
        } else if ($filename === 'tarifs.html') {
            $pattern = '/(<div class="tariffs-container">\s*)(.*?)(\s*<\/div>\s*(?:<div class="info-section">|<p class="info bt">))/is';
        }

        if ($pattern !== null && preg_match($pattern, $content)) {
            // Utiliser preg_replace_callback pour éviter les problèmes de backreferences dans $tariffsHtml
            $replacement = $tariffsHtml;
            $content = preg_replace_callback($pattern, function ($matches) use ($replacement) {
                return $matches[1] . "\n" . $replacement . "\n" . $matches[3];
            }, $content);
        }

        return $content;
    }

    /**
     * Génère le HTML des cartes de services pour services.html
     */
    private function generateServicesHtmlCards($services)
    {
        $html = '';
        $iconPath = 'M12,3L2,12H5V20H11V14H13V20H19V12H22L12,3M12,7.7C14.1,7.7 15.8,9.4 15.8,11.5C15.8,13.6 14.1,15.3 12,15.3C9.9,15.3 8.2,13.6 8.2,11.5C8.2,9.4 9.9,7.7 12,7.7Z';

        foreach ($services as $key => $service) {
            $title = htmlspecialchars($service['title'] ?? 'Service');
            $desc = htmlspecialchars($service['description'] ?? '');

            // Générer les features
            $featuresHtml = '';
            if (!empty($service['features']) && is_array($service['features'])) {
                $featuresHtml .= "                        <ul class=\"service-features\">\n";
                foreach ($service['features'] as $feature) {
                    $feature = trim($feature);
                    if ($feature !== '') {
                        $featuresHtml .= "                            <li>" . htmlspecialchars($feature) . "</li>\n";
                    }
                }
                $featuresHtml .= "                        </ul>\n";
            }

            $html .= "                    <div class=\"service-card\">\n"
                . "                        <div class=\"service-icon\">\n"
                . "                            <svg viewBox=\"0 0 24 24\">\n"
                . "                                <path d=\"{$iconPath}\"/>\n"
                . "                            </svg>\n"
                . "                        </div>\n"
                . "                        <h3 class=\"service-name\">{$title}</h3>\n"
                . "                        <p class=\"service-description\">{$desc}</p>\n"
                . $featuresHtml
                . "                        <button class=\"service-btn\" onclick=\"selectService('s_{$key}')\">Choisir ce service</button>\n"
                . "                    </div>\n";
        }
        return $html;
    }

    /**
     * Génère le HTML de la liste de services pour tarifs.html
     */
    private function generateServicesHtmlList($services)
    {
        $html = '';
        foreach ($services as $service) {
            $title = htmlspecialchars($service['title'] ?? 'Service');
            $html .= "<li>✅ {$title}</li>\n";
        }
        return $html;
    }

    /**
     * Injecte les cartes de services dans services.html
     */
    private function injectServicesHtmlCards($content, $servicesHtmlCards)
    {
        $pattern = '/(<div class="services-container">)(.*?)(<\/div>\s*<!-- Section contact -->)/is';
        if (preg_match($pattern, $content)) {
            $replacement = $servicesHtmlCards;
            $content = preg_replace_callback($pattern, function ($m) use ($replacement) {
                return $m[1] . "\n" . $replacement . "\n                    " . $m[3];
            }, $content);
        }
        return $content;
    }

    /**
     * Injecte la liste de services dans tarifs.html
     */
    private function injectServicesHtmlList($content, $servicesHtmlList)
    {
        $pattern = '/(<ul class="info-list">)(.*?)(<\/ul>)/is';
        if (preg_match($pattern, $content)) {
            $replacement = $servicesHtmlList;
            $content = preg_replace_callback($pattern, function ($m) use ($replacement) {
                return $m[1] . "\n                        " . $replacement . "                    " . $m[3];
            }, $content);
        }
        return $content;
    }

    /**
     * Injecte le CSS et JS personnalisé dans le HTML
     */
    private function injectCustomCode($content, $data)
    {
        $customCss = trim($data['custom_css'] ?? '');
        $customJs = trim($data['custom_js'] ?? '');

        // --- CSS personnalisé ---
        // Supprimer l'ancien bloc s'il existe
        $content = preg_replace('/\s*<!-- CUSTOM_CSS_START -->.*?<!-- CUSTOM_CSS_END -->/is', '', $content);

        if ($customCss !== '') {
            $cssBlock = "\n<!-- CUSTOM_CSS_START -->\n<style>{$customCss}</style>\n<!-- CUSTOM_CSS_END -->";
            // Injecter avant </head>
            $content = str_replace('</head>', $cssBlock . "\n</head>", $content);
        }

        // --- JS personnalisé ---
        // Supprimer l'ancien bloc s'il existe
        $content = preg_replace('/\s*<!-- CUSTOM_JS_START -->.*?<!-- CUSTOM_JS_END -->/is', '', $content);

        if ($customJs !== '') {
            $jsBlock = "\n<!-- CUSTOM_JS_START -->\n<script>{$customJs}</script>\n<!-- CUSTOM_JS_END -->";
            // Injecter avant </body>
            $content = str_replace('</body>', $jsBlock . "\n</body>", $content);
        }

        return $content;
    }

    /**
     * Applique les modifications générales au contenu HTML
     */
    private function applyGeneralModifications($content, $data, $filename)
    {
        // Remplacement du nom de la page (mnaSpot par défaut)
        if (!empty($data['page_name'])) {
            $pageName = htmlspecialchars($data['page_name']);
            $content = preg_replace('/<title>.*?<\/title>/i', '<title>' . $pageName . '</title>', $content);
            $content = preg_replace('/<h1 class="brand-name">.*?<\/h1>/i', '<h1 class="brand-name">' . $pageName . '</h1>', $content);
            $content = str_ireplace('mnaSpot', $pageName, $content);
        }

        // --- SPECIFIQUE LOGIN.HTML ---
        if ($filename === 'login.html') {

            // Mode de connexion par défaut
            if (isset($data['connection_mode'])) {
                $mode = in_array($data['connection_mode'], ['voucher', 'member']) ? $data['connection_mode'] : 'voucher';
                // Remplacer seulement l'appel dans DOMContentLoaded (le dernier)
                $content = preg_replace(
                    '/(DOMContentLoaded.*?switchMode\(\')(\w+)(\'\))/is',
                    '${1}' . $mode . '${3}',
                    $content
                );
            }

            // Options Audio - ajouter ou retirer complètement le player audio
            if (isset($data['audio_enabled'])) {
                if ($data['audio_enabled']) {
                    // Activer : s'assurer que les scripts audio sont présents
                    if (strpos($content, 'js/audio-player.js') === false) {
                        $audioScripts = "    <script>var audioAutoplay = true;</script>\n    <script src=\"js/audio-player.js\"></script>\n";
                        $content = str_replace('</body>', $audioScripts . '</body>', $content);
                    }
                } else {
                    // Désactiver : retirer les scripts audio
                    $content = preg_replace('/\s*<script>\s*var audioAutoplay\s*=\s*(true|false)\s*;?\s*<\/script>/i', '', $content);
                    $content = preg_replace('/\s*<script src="js\/audio-player\.js"><\/script>/i', '', $content);
                }
            }

            // Afficher ou masquer le Slide
            if (isset($data['slide_enabled'])) {
                $content = preg_replace('/style="display:\s*none;?"\s*class="slider-container"/', 'class="slider-container"', $content);
                if (!$data['slide_enabled']) {
                    $content = preg_replace('/class="slider-container"/', 'style="display: none;" class="slider-container"', $content);
                }
            }

            // Slider images - remplacer les slides
            if (!empty($data['slider_images']) && is_array($data['slider_images'])) {
                $slidesHtml = '';
                $dotsHtml = '';
                foreach ($data['slider_images'] as $i => $imageUrl) {
                    $url = htmlspecialchars($imageUrl);
                    $slidesHtml .= "                        <div class=\"slide slide-" . ($i+1) . "\" style=\"background-image: url('{$url}');\"></div>\n";
                    $active = ($i === 0) ? ' active' : '';
                    $dotsHtml .= "                        <div class=\"dot{$active}\" onclick=\"goToSlide({$i})\"></div>\n";
                }
                // Remplacer le contenu du slider
                $content = preg_replace_callback(
                    '/(<div class="slider" id="slider">)(.*?)(<\/div>\s*<div class="slider-dots")/is',
                    function($m) use ($slidesHtml) {
                        return $m[1] . "\n" . $slidesHtml . "                    " . $m[3];
                    },
                    $content
                );
                // Remplacer les dots
                $content = preg_replace_callback(
                    '/(<div class="slider-dots" id="sliderDots">)(.*?)(<\/div>\s*<\/div>\s*<!-- Mode de connexion|<\/div>\s*<\/div>\s*\n\s*<!-- Mode)/is',
                    function($m) use ($dotsHtml) {
                        return $m[1] . "\n" . $dotsHtml . "                    " . $m[3];
                    },
                    $content
                );
            }

            // Logo personnalisé
            $defaultLogoSvg = '<svg viewBox="0 0 24 24" class="wifi-icon"><path d="M12,3L2,12H5V20H11V14H13V20H19V12H22L12,3M12,7.7C14.1,7.7 15.8,9.4 15.8,11.5C15.8,13.6 14.1,15.3 12,15.3C9.9,15.3 8.2,13.6 8.2,11.5C8.2,9.4 9.9,7.7 12,7.7Z" fill="#fff"/></svg>';
            if (!empty($data['logo_url'])) {
                $logoUrl = htmlspecialchars($data['logo_url']);
                $logoImg = '<img src="' . $logoUrl . '" style="width:60px;height:60px;object-fit:contain;border-radius:12px;" alt="Logo">';
                $content = preg_replace_callback(
                    '/(<div class="wifi-logo">)(.*?)(<\/div>)/is',
                    function($m) use ($logoImg) {
                        return $m[1] . "\n                        " . $logoImg . "\n                    " . $m[3];
                    },
                    $content,
                    1
                );
            } else {
                // Restaurer le SVG par défaut si le logo a été retiré
                $content = preg_replace_callback(
                    '/(<div class="wifi-logo">)(.*?)(<\/div>)/is',
                    function($m) use ($defaultLogoSvg) {
                        return $m[1] . "\n                        " . $defaultLogoSvg . "\n                    " . $m[3];
                    },
                    $content,
                    1
                );
            }

            // Afficher ou masquer ticket de recuperation
            if (isset($data['recover_ticket'])) {
                $content = preg_replace('/style="display:\s*none;?"\s*class="ticket-section"/', 'class="ticket-section"', $content);
                if (!$data['recover_ticket']) {
                    $content = preg_replace('/class="ticket-section"/', 'style="display: none;" class="ticket-section"', $content);
                }
            }

            // URL de récupération de ticket
            if (!empty($data['recover_ticket_url'])) {
                $ticketUrl = htmlspecialchars($data['recover_ticket_url']);
                $content = preg_replace(
                    "/window\.open\('https?:\/\/[^']*',\s*'_blank'\);\s*\/\/ ticket/i",
                    "window.open('{$ticketUrl}', '_blank'); // ticket",
                    $content
                );
                // Aussi essayer le pattern sans commentaire
                $content = preg_replace_callback(
                    '/(function getTicket\(\)\s*\{[^}]*window\.open\(\')([^\']*)(\')/is',
                    function($m) use ($ticketUrl) {
                        return $m[1] . $ticketUrl . $m[3];
                    },
                    $content
                );
            }

            // Bouton Acheter un ticket - Injecter ou retirer
            $content = preg_replace('/<!-- BUY_TICKET_START -->.*?<!-- BUY_TICKET_END -->\s*/is', '', $content);
            if (!empty($data['buy_ticket']) && !empty($data['buy_ticket_url'])) {
                $buyTicketUrl = htmlspecialchars($data['buy_ticket_url']);
                $buyTicketHtml = "\n                <!-- BUY_TICKET_START -->\n"
                    . "                <div class=\"buy-ticket-section\">\n"
                    . "                    <a href=\"{$buyTicketUrl}\" target=\"_blank\" class=\"ticket-btn buy-ticket-btn\">\n"
                    . "                        <svg class=\"ticket-icon\" viewBox=\"0 0 24 24\">\n"
                    . "                            <path d=\"M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z\" fill=\"currentColor\"/>\n"
                    . "                        </svg>\n"
                    . "                        Acheter un ticket\n"
                    . "                    </a>\n"
                    . "                </div>\n"
                    . "                <!-- BUY_TICKET_END -->\n";
                // Injecter avant les tarifs
                if (strpos($content, '<!-- Cartes de tarifs -->') !== false) {
                    $content = str_replace('<!-- Cartes de tarifs -->', $buyTicketHtml . "\n\n                <!-- Cartes de tarifs -->", $content);
                } elseif (strpos($content, 'class="tariffs-container"') !== false) {
                    $content = preg_replace('/(\s*<div class="tariffs-container")/', $buyTicketHtml . "\n$1", $content, 1);
                }
            }

            // OTP Verification - Injecter ou retirer le script
            $content = preg_replace('/<!-- OTP_SCRIPT_START -->.*?<!-- OTP_SCRIPT_END -->\s*/is', '', $content);
            if (!empty($data['otp_enabled'])) {
                $otpSnippet = $this->generateOtpSnippet();
                if ($otpSnippet) {
                    $content = str_replace('</body>', $otpSnippet . '</body>', $content);
                }
            }

            // Registration (Inscription) - Injecter ou retirer le script
            $content = preg_replace('/<!-- REGISTRATION_SCRIPT_START -->.*?<!-- REGISTRATION_SCRIPT_END -->\s*/is', '', $content);
            if (!empty($data['registration_enabled'])) {
                $regSnippet = $this->generateRegistrationSnippet();
                if ($regSnippet) {
                    $content = str_replace('</body>', $regSnippet . '</body>', $content);
                }
            }

            // Chat Support - Injecter ou retirer le widget
            $content = preg_replace('/<!-- CHAT_WIDGET_START -->.*?<!-- CHAT_WIDGET_END -->\s*/is', '', $content);
            if (!empty($data['live_chat'])) {
                $chatType = $data['chat_support_type'] ?? 'live_chat';
                $chatHtml = "\n<!-- CHAT_WIDGET_START -->\n";
                if ($chatType === 'whatsapp' && !empty($data['chat_whatsapp_phone'])) {
                    $phone = htmlspecialchars($data['chat_whatsapp_phone']);
                    $chatHtml .= "<script>
(function(){
    var btn = document.createElement('a');
    btn.href = 'https://wa.me/{$phone}';
    btn.target = '_blank';
    btn.style.cssText = 'position:fixed;bottom:20px;right:20px;width:56px;height:56px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.2);z-index:9999;text-decoration:none;';
    btn.innerHTML = '<svg width=\"28\" height=\"28\" viewBox=\"0 0 24 24\" fill=\"white\"><path d=\"M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z\"/><path d=\"M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 01-4.243-1.216l-.252-.149-2.617.778.778-2.617-.149-.252A8 8 0 1112 20z\"/></svg>';
    document.body.appendChild(btn);
})();
</script>\n";
                } else {
                    // Live Chat widget - récupérer la clé du widget
                    $chatHtml .= "<script src=\"" . $this->detectBaseUrl() . "/chat-widget.js\" data-widget-key=\"auto\"></script>\n";
                }
                $chatHtml .= "<!-- CHAT_WIDGET_END -->\n";
                // Injecter avant </body>
                $content = str_replace('</body>', $chatHtml . '</body>', $content);
            }
        }

        return $content;
    }

    /**
     * Génère le snippet OTP à injecter dans login.html
     */
    private function generateOtpSnippet(): string
    {
        $baseUrl = $this->detectBaseUrl();
        $apiUrl = $baseUrl . '/api.php';
        $otpPageUrl = $baseUrl . '/public/otp-verify.html';

        $adminId = getAdminId() ?? 1;

        return <<<HTML

<!-- OTP_SCRIPT_START -->
<script>
(function() {
    var OTP_API = '{$apiUrl}';
    var OTP_PAGE = '{$otpPageUrl}';
    var ADMIN_ID = {$adminId};

    var origDoLogin = window.doLogin;
    window.doLogin = function() {
        var username = document.login.username.value;
        var password = document.login.password.value || username;
        var params = '?admin_id=' + ADMIN_ID
            + '&username=' + encodeURIComponent(username)
            + '&password=' + encodeURIComponent(password)
            + '&ip=' + encodeURIComponent('\$(ip)')
            + '&mac=' + encodeURIComponent('\$(mac)')
            + '&api_url=' + encodeURIComponent(OTP_API);
        window.location.href = OTP_PAGE + params;
        return false;
    };
})();
</script>
<!-- OTP_SCRIPT_END -->
HTML;
    }

    /**
     * Génère le snippet d'inscription (registration) à injecter dans login.html
     */
    private function generateRegistrationSnippet(): string
    {
        $baseUrl = $this->detectBaseUrl();
        $registrationPageUrl = $baseUrl . '/public/registration.html';

        $adminId = getAdminId() ?? 1;

        return <<<HTML

<!-- REGISTRATION_SCRIPT_START -->
<script>
(function() {
    var REG_PAGE = '{$registrationPageUrl}';
    var ADMIN_ID = {$adminId};

    var btn = document.createElement('a');
    btn.href = REG_PAGE + '?admin_id=' + ADMIN_ID + '&ip=\$(ip)&mac=\$(mac)';
    btn.style.cssText = 'display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:12px;text-decoration:none;font-weight:600;font-size:15px;box-shadow:0 4px 14px rgba(79,70,229,0.4);transition:transform 0.2s,box-shadow 0.2s;';
    btn.onmouseover = function() { this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 20px rgba(79,70,229,0.5)'; };
    btn.onmouseout = function() { this.style.transform=''; this.style.boxShadow='0 4px 14px rgba(79,70,229,0.4)'; };
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg> S\'inscrire';

    var container = document.createElement('div');
    container.style.cssText = 'text-align:center;margin-top:16px;';
    container.appendChild(btn);

    var form = document.querySelector('form') || document.querySelector('.login-form');
    if (form) {
        form.parentNode.insertBefore(container, form.nextSibling);
    } else {
        document.body.appendChild(container);
    }
})();
</script>
<!-- REGISTRATION_SCRIPT_END -->
HTML;
    }
}