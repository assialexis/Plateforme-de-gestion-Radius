<?php
/**
 * Controller API Templates (Voucher & Hotspot)
 */

class TemplateController
{
    private RadiusDatabase $db;
    private AuthService $auth;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    // ==========================================
    // Voucher Templates
    // ==========================================

    /**
     * GET /api/templates/vouchers
     * Liste tous les templates de vouchers
     */
    public function indexVouchers(): void
    {
        $adminId = $this->getAdminId();
        $templates = $this->db->getAllVoucherTemplates($adminId);
        jsonSuccess($templates);
    }

    /**
     * GET /api/templates/vouchers/{id}
     * Obtenir un template par ID
     */
    public function showVoucher(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $template = $this->db->getVoucherTemplateById($id, $adminId);

        if (!$template) {
            jsonError(__('api.template_not_found'), 404);
        }

        jsonSuccess($template);
    }

    /**
     * POST /api/templates/vouchers
     * Créer un nouveau template
     */
    public function storeVoucher(): void
    {
        $data = getJsonBody();
        $adminId = $this->getAdminId();

        if (empty($data['name'])) {
            jsonError(__('api.template_name_required'), 400);
        }

        $data['admin_id'] = $adminId;
        $id = $this->db->createVoucherTemplate($data);
        $template = $this->db->getVoucherTemplateById($id, $adminId);

        jsonSuccess($template, __('api.template_created'));
    }

    /**
     * PUT /api/templates/vouchers/{id}
     * Mettre à jour un template
     */
    public function updateVoucher(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();
        $adminId = $this->getAdminId();

        $template = $this->db->getVoucherTemplateById($id, $adminId);
        if (!$template) {
            jsonError(__('api.template_not_found'), 404);
        }

        if (empty($data['name'])) {
            jsonError(__('api.template_name_required'), 400);
        }

        $this->db->updateVoucherTemplate($id, $data);
        $template = $this->db->getVoucherTemplateById($id, $adminId);

        jsonSuccess($template, __('api.template_updated'));
    }

    /**
     * DELETE /api/templates/vouchers/{id}
     * Supprimer un template
     */
    public function destroyVoucher(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $template = $this->db->getVoucherTemplateById($id, $adminId);

        if (!$template) {
            jsonError(__('api.template_not_found'), 404);
        }

        // Ne pas supprimer le template par défaut
        if ($template['is_default']) {
            jsonError(__('api.template_default_cannot_delete'), 400);
        }

        $this->db->deleteVoucherTemplate($id);
        jsonSuccess(null, __('api.template_deleted'));
    }

    /**
     * POST /api/templates/vouchers/{id}/default
     * Définir comme template par défaut
     */
    public function setDefaultVoucher(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $template = $this->db->getVoucherTemplateById($id, $adminId);

        if (!$template) {
            jsonError(__('api.template_not_found'), 404);
        }

        $this->db->setDefaultVoucherTemplate($id, $adminId);
        $template = $this->db->getVoucherTemplateById($id, $adminId);

        jsonSuccess($template, __('api.template_default_set'));
    }

    /**
     * GET /api/templates/vouchers/default
     * Obtenir le template par défaut
     */
    public function getDefaultVoucher(): void
    {
        $adminId = $this->getAdminId();
        $template = $this->db->getDefaultVoucherTemplate($adminId);

        if (!$template) {
            jsonError(__('api.template_default_not_found'), 404);
        }

        jsonSuccess($template);
    }

    /**
     * POST /api/templates/vouchers/{id}/preview
     * Générer un aperçu du template avec des données de test
     */
    public function previewVoucher(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $template = $this->db->getVoucherTemplateById($id, $adminId);

        if (!$template) {
            jsonError(__('api.template_not_found'), 404);
        }

        // Générer des vouchers de test pour l'aperçu
        $testVouchers = [];
        $count = $template['columns_count'] * $template['rows_count'];

        for ($i = 1; $i <= $count; $i++) {
            $testVouchers[] = [
                'code' => 'TEST' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'password' => 'PASS' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'profile' => '1 Heure',
                'validity' => '1h',
                'speed' => '2 Mbps / 1 Mbps',
                'price' => '100 XAF'
            ];
        }

        jsonSuccess([
            'template' => $template,
            'vouchers' => $testVouchers
        ]);
    }

    // ==========================================
    // Hotspot Templates
    // ==========================================

    /**
     * GET /api/templates/hotspot
     * Liste tous les templates hotspot
     */
    public function indexHotspot(): void
    {
        $adminId = $this->getAdminId();
        $templates = $this->db->getAllHotspotTemplates($adminId);
        jsonSuccess($templates);
    }

    /**
     * GET /api/templates/hotspot/{id}
     * Obtenir un template hotspot par ID
     */
    public function showHotspot(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $template = $this->db->getHotspotTemplateById($id, $adminId);

        if (!$template) {
            jsonError(__('api.template_not_found'), 404);
        }

        jsonSuccess($template);
    }

    /**
     * POST /api/templates/hotspot
     * Créer un nouveau template hotspot
     */
    public function storeHotspot(): void
    {
        $data = getJsonBody();
        $adminId = $this->getAdminId();

        if (empty($data['name'])) {
            jsonError(__('api.template_name_required'), 400);
        }

        if (isset($data['config']) && is_array($data['config'])) {
            $data['config'] = json_encode($data['config']);
        }
        $data['admin_id'] = $adminId;

        $id = $this->db->createHotspotTemplate($data);
        $template = $this->db->getHotspotTemplateById($id, $adminId);

        jsonSuccess($template, __('api.template_created'));
    }

    /**
     * PUT /api/templates/hotspot/{id}
     * Mettre à jour un template hotspot
     */
    public function updateHotspot(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();
        $adminId = $this->getAdminId();

        $template = $this->db->getHotspotTemplateById($id, $adminId);
        if (!$template) {
            jsonError(__('api.template_not_found'), 404);
        }

        if (empty($data['name'])) {
            jsonError(__('api.template_name_required'), 400);
        }

        if (isset($data['config']) && is_array($data['config'])) {
            $data['config'] = json_encode($data['config']);
        }

        $this->db->updateHotspotTemplate($id, $data);
        $template = $this->db->getHotspotTemplateById($id, $adminId);

        jsonSuccess($template, __('api.template_updated'));
    }

    /**
     * DELETE /api/templates/hotspot/{id}
     * Supprimer un template hotspot
     */
    public function destroyHotspot(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $template = $this->db->getHotspotTemplateById($id, $adminId);

        if (!$template) {
            jsonError(__('api.template_not_found'), 404);
        }

        // Ne pas supprimer le template par défaut
        if ($template['is_default']) {
            jsonError(__('api.template_default_cannot_delete'), 400);
        }

        $this->db->deleteHotspotTemplate($id);
        jsonSuccess(null, __('api.template_deleted'));
    }

    /**
     * POST /api/templates/hotspot/{id}/default
     * Définir comme template par défaut
     */
    public function setDefaultHotspot(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $template = $this->db->getHotspotTemplateById($id, $adminId);

        if (!$template) {
            jsonError(__('api.template_not_found'), 404);
        }

        $this->db->setDefaultHotspotTemplate($id, $adminId);
        $template = $this->db->getHotspotTemplateById($id, $adminId);

        jsonSuccess($template, __('api.template_default_set'));
    }

    /**
     * GET /api/templates/hotspot/default
     * Obtenir le template hotspot par défaut
     */
    public function getDefaultHotspot(): void
    {
        $adminId = $this->getAdminId();
        $template = $this->db->getDefaultHotspotTemplate($adminId);

        if (!$template) {
            jsonError(__('api.template_default_not_found'), 404);
        }

        jsonSuccess($template);
    }

    /**
     * POST /api/templates/hotspot/{id}/duplicate
     * Dupliquer un template hotspot
     */
    public function duplicateHotspot(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $template = $this->db->getHotspotTemplateById($id, $adminId);

        if (!$template) {
            jsonError(__('api.template_not_found'), 404);
        }

        // Préparer les données pour la copie
        $copyData = $template;
        unset($copyData['id']);
        unset($copyData['created_at']);
        unset($copyData['updated_at']);
        $copyData['name'] = $template['name'] . ' (copie)';
        $copyData['template_code'] = $template['template_code'] . '_copy_' . time();
        $copyData['is_default'] = 0;
        $copyData['admin_id'] = $adminId;

        $newId = $this->db->createHotspotTemplate($copyData);
        $newTemplate = $this->db->getHotspotTemplateById($newId, $adminId);

        jsonSuccess($newTemplate, __('api.template_duplicated'));
    }

    /**
     * POST /api/templates/hotspot/{id}/generate
     * Générer le code HTML MikroTik pour le template
     */
    public function generateHotspotHtml(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $template = $this->db->getHotspotTemplateById($id, $adminId);

        if (!$template) {
            jsonError(__('api.template_not_found'), 404);
        }

        $html = $this->generateMikroTikHtml($template);

        jsonSuccess([
            'html' => $html,
            'filename' => 'login.html'
        ]);
    }

    /**
     * POST /api/templates/hotspot/preview-live
     * Générer l'aperçu HTML sans sauvegarder (à la volée)
     */
    public function previewLiveHotspotHtml(): void
    {
        $data = getJsonBody();
        file_put_contents('/Applications/XAMPP/xamppfiles/htdocs/nas/preview_data.log', json_encode($data));
        if (isset($data['config']) && is_array($data['config'])) {
            $data['config'] = json_encode($data['config']);
        }

        $data['is_preview'] = true;
        $html = $this->generateMikroTikHtml($data);
        jsonSuccess(['html' => $html]);
    }

    /**
     * Générer le HTML MikroTik pour un template hotspot basé sur le modèle reférentiel
     */
    private function generateMikroTikHtml(array $template): string
    {
        $basePath = dirname(__DIR__, 2) . '/Hotspottemplate/login.html';
        $html = @file_get_contents($basePath);

        if (!$html) {
            return "<!-- Erreur : Le modèle de base login.html est introuvable à " . htmlspecialchars($basePath) . " -->";
        }

        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/nas/Hotspottemplate/";
        $baseWebUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/nas/web/";
        $html = str_replace('<head>', "<head>\n    <base href=\"$baseUrl\">", $html);

        $config = json_decode($template['config'] ?? '{}', true) ?: [];

        // 1. Définir les variables JS globales pour le comportement
        $jsConfig = [
            'theme' => 'lite',
            'contactPhone' => $config['contact_number'] ?? '',
            'sliderEnabled' => !empty($config['slider_images']),
            'sliderInterval' => 4000,
            'defaultMode' => $config['default_auth_method'] ?? 'voucher',
            'loginvc' => $template['username_placeholder'] ?? 'Code Voucher',
            'loginup' => $template['subtitle_text'] ?? 'Identifiants',
            'setCase' => 'none'
        ];

        // 2. Extraire et insérer les tarifs depuis les profils sélectionnés
        $tarifs = [];
        if (!empty($config['selected_profiles'])) {
            $icons = ['clock', 'sun', 'calendar'];
            $idx = 0;
            $unit_map = ['m' => 'minutes', 'h' => 'heures', 'd' => 'jours', 'months' => 'mois'];

            foreach ($config['selected_profiles'] as $index => $profileId) {
                // Fetch the actual profile data from database
                $profile = $this->db->getProfileById((int)$profileId);

                if ($profile) {
                    $v_unit = $profile['validity_unit'] ?? 'd';
                    $unit_str = $unit_map[$v_unit] ?? $v_unit;
                    $validiteStr = ($profile['validity'] ?? 1) . ' ' . $unit_str;

                    $adminParam = !empty($profile['admin_id']) ? '&admin=' . $profile['admin_id'] : (!empty($template['admin_id']) ? '&admin=' . $template['admin_id'] : '');
                    $paymentLink = $baseWebUrl . 'pay.php?profile=' . $profileId . $adminParam;

                    $tarifs[] = [
                        'id' => (int)$profileId,
                        'duree' => htmlspecialchars($profile['name'] ?? 'Profil N°' . $profileId),
                        'prix' => (int)($profile['price'] ?? 0),
                        'devise' => 'FCFA',
                        'validite' => $validiteStr,
                        'icon' => $icons[$idx % count($icons)],
                        'populaire' => ($idx === 1),
                        'lienPaiement' => $paymentLink
                    ];
                }
                else {
                    $adminParam = !empty($template['admin_id']) ? '&admin=' . $template['admin_id'] : '';
                    $paymentLink = $baseWebUrl . 'pay.php?profile=' . $profileId . $adminParam;

                    $tarifs[] = [
                        'id' => (int)$profileId,
                        'duree' => 'Profil N°' . $profileId,
                        'prix' => 0,
                        'devise' => 'FCFA',
                        'validite' => '24 heures',
                        'icon' => $icons[$idx % count($icons)],
                        'populaire' => ($idx === 1),
                        'lienPaiement' => $paymentLink
                    ];
                }
                $idx++;
            }
        }

        $tarifsDataJSON = [
            'tarifs' => $tarifs,
            'paiement' => [
                'contact' => $config['contact_number'] ?? '',
                'infoModal' => 'Veuillez appeler le support pour payer.'
            ]
        ];

        // 3. Remplacer CSS/Logos
        if (!empty($config['logo_url'])) {
            $logoSrc = trim($config['logo_url']);
            if (strpos($logoSrc, 'http') !== 0) {
                if (strpos($logoSrc, 'uploads/') === 0) {
                    $logoSrc = $baseWebUrl . $logoSrc;
                }
                else {
                    $logoSrc = $baseWebUrl . 'uploads/media/' . $logoSrc;
                }
            }

            $logoHtml = '<img src="' . htmlspecialchars($logoSrc) . '" alt="Logo" style="max-height: 48px; width: auto; object-fit: contain;">';
            $html = preg_replace('/<div class="logo-icon">.*?<\/div>/s', '<div class="logo-icon">' . "\n                    " . $logoHtml . "\n                " . '</div>', $html);
        }

        // 4. Remplacer Slider Images
        if (!empty($config['slider_images']) && is_array($config['slider_images'])) {
            $slidesHtml = '<div class="slides">';
            $dotsHtml = '<div class="slider-dots">';
            foreach ($config['slider_images'] as $index => $imageUrl) {
                $activeClass = $index === 0 ? ' active' : '';

                $imgSrc = trim($imageUrl);
                if (strpos($imgSrc, 'http') !== 0) {
                    if (strpos($imgSrc, 'uploads/') === 0) {
                        $imgSrc = $baseWebUrl . $imgSrc;
                    }
                    else {
                        $imgSrc = $baseWebUrl . 'uploads/media/' . $imgSrc;
                    }
                }

                $slidesHtml .= "\n" . '                    <div class="slide' . $activeClass . '"><img src="' . htmlspecialchars($imgSrc) . '" alt="Slide ' . ($index + 1) . '"></div>';
                $dotsHtml .= "\n" . '                    <span class="dot' . $activeClass . '" onclick="goToSlide(' . $index . ')"></span>';
            }
            $slidesHtml .= "\n" . '                </div>';
            $dotsHtml .= "\n" . '                </div>';

            $html = preg_replace('/<div class="slides">.*?<\/div>\s*<div class="slider-dots">.*?<\/div>/s', $slidesHtml . "\n                " . $dotsHtml, $html);
        }

        $scriptInjectionConfig = "<script>\n" .
            "var config = " . json_encode($jsConfig, JSON_UNESCAPED_UNICODE) . ";\n";

        if (!empty($template['is_preview'])) {
            $scriptInjectionConfig .= "try { localStorage.removeItem('loginMode'); } catch(e) {}\n";
        }

        $scriptInjectionConfig .= "</script>";

        // Remplacer l'appel à conf.js par notre configuration calculée (évite l'écrasement)
        $html = str_replace('<script src="conf.js"></script>', $scriptInjectionConfig, $html);

        $tarifsReplacement = "tarifsData = " . json_encode($tarifsDataJSON, JSON_UNESCAPED_UNICODE) . ";\n" .
            "renderTarifs(tarifsData.tarifs);\n" .
            "var c = document.getElementById('contact-number');\n" .
            "if(c) { c.textContent = tarifsData.paiement.contact; }";

        // Désactiver l'appel AJAX de `tarifs.json` pour utiliser l'injection directe JSON et forcer le rendu
        $html = preg_replace("/fetch\('tarifs\.json'\)[\s\S]*?\.catch\(function\(err\) \{[\s\S]*?\}\);/", $tarifsReplacement, $html);

        // 3. Remplacer Titre de la page
        $title = htmlspecialchars($template['title_text'] ?? 'WiFi Zone');
        $html = preg_replace('/<h1 id="page-title">.*?<\/h1>/', '<h1 id="page-title">' . $title . '</h1>', $html);

        // 4. Injecter les services
        if (!empty($config['services'])) {
            $servicesHtml = '<style>
            .services-grid-custom { display: grid; gap: 1.5rem; padding: 0 20px; grid-template-columns: 1fr; }
            @media (min-width: 768px) { .services-grid-custom { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); } }
            </style>';
            $servicesHtml .= '<div class="services-section" style="margin-top: 2rem; width: 100%;"><h2 class="section-title">Nos Services</h2><div class="services-grid-custom">';
            foreach ($config['services'] as $service) {
                $servicesHtml .= '<div class="service-card glass-card" style="padding:1.5rem;text-align:center;">';
                if (!empty($service['icon'])) {
                    $servicesHtml .= '<div class="service-icon" style="margin-bottom:1rem;color:#3b82f6;">' . $service['icon'] . '</div>';
                }
                $servicesHtml .= '<h3 style="margin-bottom:0.5rem;font-size:1.1rem;">' . htmlspecialchars($service['title'] ?? '') . '</h3>';
                if (!empty($service['description'])) {
                    $servicesHtml .= '<p style="font-size:0.9rem;opacity:0.8;">' . htmlspecialchars($service['description']) . '</p>';
                }
                $servicesHtml .= '</div>';
            }
            $servicesHtml .= '</div></div>';

            $html = str_replace('</main>', "</main>\n" . $servicesHtml, $html);
        }

        // 5. Options d'affichage (Masquer des sections)
        $cssInjections = [];
        if (isset($template['show_logo']) && empty($template['show_logo'])) {
            $cssInjections[] = '.logo-icon, .logo img { display: none !important; }';
        }
        if (isset($template['show_password_field']) && empty($template['show_password_field'])) {
            $cssInjections[] = '#password-group, #tab-membre { display: none !important; }';
        }
        if (isset($template['show_footer']) && empty($template['show_footer'])) {
            $cssInjections[] = '.footer { display: none !important; }';
        }
        if (!empty($cssInjections)) {
            $customCss = "<style>\n" . implode("\n", $cssInjections) . "\n</style>";
            $html = str_replace('</head>', $customCss . "\n</head>", $html);
        }

        // 6. Option : Pied de page (Texte)
        if (!empty($template['show_footer']) && !empty($template['footer_text'])) {
            $footerText = htmlspecialchars($template['footer_text']);
            $html = preg_replace('/<footer class="footer">.*?<\/footer>/s', '<footer class="footer" style="text-align:center; padding:20px; color:var(--text-muted); font-size:12px;">' . $footerText . '</footer>', $html);
        }

        // 7. Option : Afficher "Se souvenir de moi"
        if (!empty($template['show_remember_me'])) {
            $rememberCheckbox = '<div class="input-group" style="margin-top:10px; display:flex; align-items:center; gap:8px;">' . "\n" .
                '    <input type="checkbox" name="remember" id="remember" value="yes" style="width: auto; padding:0; background:transparent; border:none; box-shadow:none;">' . "\n" .
                '    <label for="remember" style="font-size:14px; color:var(--text-secondary); cursor:pointer;">Se souvenir de moi</label>' . "\n" .
                '    <input type="hidden" name="domain" value="">' . "\n" .
                '</div>';
            $html = preg_replace('/<button type="submit" class="btn-submit">/', $rememberCheckbox . "\n                    " . '<button type="submit" class="btn-submit">', $html);
        }

        // 8. Option : Chat Support
        if (!empty($template['show_chat_support'])) {
            $chatType = $template['chat_support_type'] ?? 'whatsapp';
            $chatHtml = '';

            if ($chatType === 'whatsapp' && !empty($template['chat_whatsapp_phone'])) {
                $phone = htmlspecialchars($template['chat_whatsapp_phone']);
                $msg = urlencode($template['chat_welcome_message'] ?? 'Bonjour');
                $chatHtml = '<a href="https://wa.me/' . $phone . '?text=' . $msg . '" target="_blank" style="position:fixed; bottom:20px; right:20px; z-index:9999; background:#25D366; color:white; width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(0,0,0,0.3); transition:all 0.3s ease;">' .
                    '<svg viewBox="0 0 24 24" width="34" height="34" fill="currentColor" style="color:#fff;"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.425-10.416c-4.281 0-7.762 3.481-7.762 7.762 0 1.369.355 2.705 1.033 3.882l-1.127 4.12 4.214-1.106c1.137.625 2.423.955 3.742.955 4.281 0 7.762-3.481 7.762-7.762s-3.481-7.762-7.762-7.762z"/></svg>' .
                    '</a>';
            }
            elseif ($chatType === 'live_chat') {
                $chatHtml = '<div style="position:fixed; bottom:20px; right:20px; z-index:9999; background:#3b82f6; color:white; width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(0,0,0,0.3);"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg></div>';
            }
            if (!empty($chatHtml)) {
                $html = str_replace('</body>', $chatHtml . "\n</body>", $html);
            }
        }

        return $html;
    }
}