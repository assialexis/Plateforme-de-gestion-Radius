<?php
/**
 * Controller API Gestion des Modules
 * Activation/désactivation et configuration des modules
 */

class ModuleController
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

    /**
     * S'assurer que la table modules existe et contient les modules par défaut
     */
    private function ensureModulesTable(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        // Créer la table si elle n'existe pas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS modules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                module_code VARCHAR(50) NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                icon VARCHAR(50) DEFAULT 'cube',
                is_active TINYINT(1) DEFAULT 0,
                config JSON,
                display_order INT DEFAULT 0,
                admin_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_modules_code_admin (module_code, admin_id),
                INDEX idx_modules_admin_id (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Lire les modules par défaut depuis global_settings
        $stmtDefaults = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'default_modules'");
        $stmtDefaults->execute();
        $defaultRow = $stmtDefaults->fetch(PDO::FETCH_ASSOC);
        $defaultActiveModules = $defaultRow ? (json_decode($defaultRow['setting_value'], true) ?? ['hotspot', 'captive-portal']) : ['hotspot', 'captive-portal'];

        // Liste complète des modules disponibles
        $allModules = [
            ['hotspot', 'Hotspot', 'Gestion des vouchers, profils et sessions WiFi hotspot', 'wifi'],
            ['loyalty', 'Programme de Fidélité', 'Récompensez vos clients fidèles avec des bonus automatiques', 'gift'],
            ['chat', 'Chat Support', 'Chat en temps réel avec les clients sur la page de paiement', 'chat-bubble-left-right'],
            ['sms', 'Notifications SMS', 'Envoi de SMS automatiques aux clients (vouchers, rappels)', 'device-phone-mobile'],
            ['analytics', 'Analytiques Avancées', 'Tableaux de bord et rapports détaillés', 'chart-bar'],
            ['pppoe', 'Gestion PPPoE', 'Gestion des abonnés PPPoE avec authentification RADIUS', 'signal'],
            ['whatsapp', 'WhatsApp', 'Notifications WhatsApp automatiques aux clients', 'chat-bubble-left'],
            ['telegram', 'Telegram', 'Notifications Telegram automatiques aux clients', 'paper-airplane'],
            ['captive-portal', 'Portail Captif', 'Personnalisation des pages de connexion du hotspot', 'wifi'],
        ];

        // Toujours ajouter les modules manquants (INSERT IGNORE) pour cet admin
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO modules (module_code, name, description, icon, is_active, display_order, admin_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        // Calculer le display_order à partir des modules existants
        $stmtOrder = $pdo->prepare("SELECT COALESCE(MAX(display_order), 0) as max_order FROM modules WHERE admin_id = ?");
        $stmtOrder->execute([$adminId]);
        $nextOrder = (int)$stmtOrder->fetch()['max_order'] + 1;

        $order = $nextOrder;
        foreach ($allModules as $module) {
            $isActive = in_array($module[0], $defaultActiveModules) ? 1 : 0;
            $stmt->execute([$module[0], $module[1], $module[2], $module[3], $isActive, $order, $adminId]);
            $order++;
        }
    }

    /**
     * GET /api/modules
     * Liste de tous les modules
     */
    public function listModules(): void
    {
        // S'assurer que la table existe avec les modules par défaut
        $this->ensureModulesTable();

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        if ($adminId !== null) {
            $stmt = $pdo->prepare("
                SELECT id, module_code, name, description, icon, is_active, config, display_order
                FROM modules
                WHERE admin_id = ?
                ORDER BY display_order, name
            ");
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $pdo->query("
                SELECT id, module_code, name, description, icon, is_active, config, display_order
                FROM modules
                ORDER BY display_order, name
            ");
        }
        $modules = $stmt->fetchAll();

        // Charger les prix des modules et config crédits
        $pricing = [];
        $creditSystemEnabled = false;
        $creditBalance = 0;
        try {
            $stmtP = $pdo->query("SELECT module_code, price_credits, billing_type, is_active FROM module_pricing");
            while ($row = $stmtP->fetch(PDO::FETCH_ASSOC)) {
                $pricing[$row['module_code']] = $row;
            }
            $stmtS = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'credit_system_enabled'");
            $stmtS->execute();
            $creditSystemEnabled = $stmtS->fetchColumn() === '1';

            if ($adminId !== null) {
                $stmtB = $pdo->prepare("SELECT credit_balance FROM users WHERE id = ?");
                $stmtB->execute([$adminId]);
                $creditBalance = (float)$stmtB->fetchColumn();
            }
        } catch (Exception $e) {
            // Tables pas encore créées
        }

        // Charger les abonnements pour cet admin
        $subscriptions = [];
        if ($adminId !== null) {
            try {
                $stmtSub = $pdo->prepare("SELECT module_code, activated_at, last_renewal_at, next_renewal_at, billing_type, auto_renew FROM admin_module_subscriptions WHERE admin_id = ?");
                $stmtSub->execute([$adminId]);
                while ($row = $stmtSub->fetch(\PDO::FETCH_ASSOC)) {
                    $subscriptions[$row['module_code']] = $row;
                }
            } catch (\Exception $e) {
                // Table pas encore créée
            }
        }

        // Décoder la config JSON et convertir is_active en booléen
        foreach ($modules as &$module) {
            $module['config'] = $module['config'] ? json_decode($module['config'], true) : [];
            $module['is_active'] = (bool)$module['is_active'];

            // Ajouter info pricing
            $code = $module['module_code'];
            if (isset($pricing[$code])) {
                $module['price_credits'] = (float)$pricing[$code]['price_credits'];
                $module['billing_type'] = $pricing[$code]['billing_type'];
                $module['pricing_active'] = (bool)$pricing[$code]['is_active'];
            } else {
                $module['price_credits'] = 0;
                $module['billing_type'] = 'one_time';
                $module['pricing_active'] = false;
            }

            // Ajouter info abonnement
            if (isset($subscriptions[$code])) {
                $sub = $subscriptions[$code];
                $module['activated_at'] = $sub['activated_at'];
                $module['next_renewal_at'] = $sub['next_renewal_at'];
                $module['last_renewal_at'] = $sub['last_renewal_at'];
                $module['auto_renew'] = (bool)($sub['auto_renew'] ?? false);
            } else {
                $module['activated_at'] = null;
                $module['next_renewal_at'] = null;
                $module['last_renewal_at'] = null;
                $module['auto_renew'] = false;
            }
        }

        jsonSuccess([
            'modules' => $modules,
            'credit_system_enabled' => $creditSystemEnabled,
            'credit_balance' => $creditBalance
        ]);
    }

    /**
     * GET /api/modules/:code
     * Obtenir un module par son code
     */
    public function getModule(array $params): void
    {
        $code = $params['code'] ?? '';
        if (empty($code)) {
            jsonError(__('api.module_code_required'), 400);
        }

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        if ($adminId !== null) {
            $stmt = $pdo->prepare("SELECT * FROM modules WHERE module_code = ? AND admin_id = ?");
            $stmt->execute([$code, $adminId]);
        }
        else {
            $stmt = $pdo->prepare("SELECT * FROM modules WHERE module_code = ?");
            $stmt->execute([$code]);
        }
        $module = $stmt->fetch();

        if (!$module) {
            jsonError(__('api.module_not_found'), 404);
        }

        $module['config'] = $module['config'] ? json_decode($module['config'], true) : [];
        jsonSuccess(['module' => $module]);
    }

    /**
     * GET /api/modules/:code/status
     * Vérifier si un module est actif
     */
    public function isModuleActive(array $params): void
    {
        $code = $params['code'] ?? '';
        if (empty($code)) {
            jsonError(__('api.module_code_required'), 400);
        }

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        if ($adminId !== null) {
            $stmt = $pdo->prepare("SELECT is_active FROM modules WHERE module_code = ? AND admin_id = ?");
            $stmt->execute([$code, $adminId]);
        }
        else {
            $stmt = $pdo->prepare("SELECT is_active FROM modules WHERE module_code = ?");
            $stmt->execute([$code]);
        }
        $result = $stmt->fetch();

        $isActive = $result ? (bool)$result['is_active'] : false;
        jsonSuccess(['is_active' => $isActive]);
    }

    /**
     * PUT /api/modules/:code/toggle
     * Activer/désactiver un module
     */
    public function toggleModule(array $params): void
    {
        $code = $params['code'] ?? '';
        if (empty($code)) {
            jsonError(__('api.module_code_required'), 400);
            return;
        }

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        // Vérifier si le module existe
        if ($adminId !== null) {
            $stmt = $pdo->prepare("SELECT * FROM modules WHERE module_code = ? AND admin_id = ?");
            $stmt->execute([$code, $adminId]);
        }
        else {
            $stmt = $pdo->prepare("SELECT * FROM modules WHERE module_code = ?");
            $stmt->execute([$code]);
        }
        $module = $stmt->fetch();

        if (!$module) {
            jsonError(__('api.module_not_found'), 404);
            return;
        }

        // Inverser le statut (PDO retourne is_active comme string "0" ou "1")
        $currentStatus = (int)$module['is_active'];
        $newStatus = $currentStatus ? 0 : 1;

        // Vérification des crédits lors de l'activation (seulement pour les admins, pas le superadmin)
        if ($newStatus === 1 && $adminId !== null) {
            try {
                $stmtPrice = $pdo->prepare("SELECT * FROM module_pricing WHERE module_code = ? AND is_active = 1");
                $stmtPrice->execute([$code]);
                $pricing = $stmtPrice->fetch(PDO::FETCH_ASSOC);

                // Vérifier si le système de crédits est actif
                $stmtSys = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'credit_system_enabled'");
                $stmtSys->execute();
                $creditSystemEnabled = $stmtSys->fetchColumn() === '1';

                if ($creditSystemEnabled && $pricing && (float)$pricing['price_credits'] > 0) {
                    // Lire le body JSON pour obtenir le nombre de mois
                    $body = json_decode(file_get_contents('php://input'), true) ?: [];
                    $months = max(1, (int)($body['months'] ?? 1));
                    if ($months > 24) $months = 24;

                    $unitPrice = (float)$pricing['price_credits'];
                    $billingType = $pricing['billing_type'];

                    // Pour les paiements uniques, toujours 1
                    if ($billingType === 'one_time') {
                        $months = 1;
                    }

                    $totalPrice = $unitPrice * $months;

                    // Vérifier le solde de l'admin
                    $stmtBal = $pdo->prepare("SELECT credit_balance FROM users WHERE id = ?");
                    $stmtBal->execute([$adminId]);
                    $balance = (float)$stmtBal->fetchColumn();

                    if ($balance < $totalPrice) {
                        jsonError((__('credits.insufficient_balance') ?? 'Solde de crédits insuffisant pour activer ce module') . '. ' . (__('credits.required') ?? 'Requis') . ': ' . $totalPrice . ' CRT', 402);
                        return;
                    }

                    // Déduire les crédits dans une transaction
                    $pdo->beginTransaction();
                    try {
                        $newBalance = $balance - $totalPrice;
                        $pdo->prepare("UPDATE users SET credit_balance = ? WHERE id = ?")
                            ->execute([$newBalance, $adminId]);

                        $description = 'Activation module: ' . ($module['name'] ?? $code);
                        if ($billingType === 'monthly' && $months > 1) {
                            $description .= " ({$months} mois)";
                        }

                        $pdo->prepare("
                            INSERT INTO credit_transactions (admin_id, type, amount, balance_after, reference_type, reference_id, description)
                            VALUES (?, 'module_activation', ?, ?, 'module', ?, ?)
                        ")->execute([
                            $adminId, -$totalPrice, $newBalance, $code, $description
                        ]);

                        // Enregistrer l'abonnement
                        $nextRenewal = $billingType === 'monthly'
                            ? date('Y-m-d H:i:s', strtotime("+{$months} month"))
                            : null;
                        $pdo->prepare("
                            INSERT INTO admin_module_subscriptions (admin_id, module_code, billing_type, activated_at, next_renewal_at, is_paid)
                            VALUES (?, ?, ?, NOW(), ?, 1)
                            ON DUPLICATE KEY UPDATE is_paid = 1, last_renewal_at = NOW(), next_renewal_at = VALUES(next_renewal_at)
                        ")->execute([$adminId, $code, $billingType, $nextRenewal]);

                        // Activer le module
                        $pdo->prepare("UPDATE modules SET is_active = 1 WHERE id = ?")
                            ->execute([$module['id']]);

                        $pdo->commit();

                        $message = (__('api.module_activated') ?? 'Module activé');
                        if ($billingType === 'monthly' && $months > 1) {
                            $message .= " ({$months} " . (__('credits.months') ?? 'mois') . ')';
                        }

                        jsonSuccess([
                            'message' => $message,
                            'module_code' => $code,
                            'is_active' => true,
                            'credits_deducted' => $totalPrice,
                            'months' => $months,
                            'new_balance' => $newBalance,
                            'next_renewal_at' => $nextRenewal,
                        ]);
                        return;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        jsonError(__('credits.deduction_error') ?? 'Erreur lors de la déduction des crédits', 500);
                        return;
                    }
                }
            } catch (Exception $e) {
                // Si les tables n'existent pas encore, continuer normalement
            }
        }

        $stmt = $pdo->prepare("UPDATE modules SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $module['id']]);

        jsonSuccess([
            'message' => $newStatus ? __('api.module_activated') : __('api.module_deactivated'),
            'module_code' => $code,
            'is_active' => (bool)$newStatus
        ]);
    }

    /**
     * POST /api/modules/:code/renew
     * Prolonger l'abonnement d'un module actif
     */
    public function renewModule(array $params): void
    {
        $code = $params['code'] ?? '';
        if (empty($code)) {
            jsonError(__('api.module_code_required') ?? 'Code module requis', 400);
            return;
        }

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        if ($adminId === null) {
            jsonError('Non autorisé', 403);
            return;
        }

        // Vérifier que le module existe et est actif
        $stmt = $pdo->prepare("SELECT * FROM modules WHERE module_code = ? AND admin_id = ?");
        $stmt->execute([$code, $adminId]);
        $module = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$module || !(int)$module['is_active']) {
            jsonError(__('module.not_active_for_renewal') ?? 'Le module doit être actif pour le prolonger', 400);
            return;
        }

        // Vérifier le pricing
        $stmtPrice = $pdo->prepare("SELECT * FROM module_pricing WHERE module_code = ? AND is_active = 1");
        $stmtPrice->execute([$code]);
        $pricing = $stmtPrice->fetch(\PDO::FETCH_ASSOC);

        if (!$pricing || (float)$pricing['price_credits'] <= 0) {
            jsonError(__('module.no_pricing') ?? 'Ce module n\'a pas de tarification', 400);
            return;
        }

        $billingType = $pricing['billing_type'];
        if ($billingType !== 'monthly') {
            jsonError(__('module.not_renewable') ?? 'Ce module ne peut pas être prolongé (paiement unique)', 400);
            return;
        }

        // Lire les paramètres
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $months = max(1, (int)($body['months'] ?? 1));
        if ($months > 24) $months = 24;

        $unitPrice = (float)$pricing['price_credits'];
        $totalPrice = $unitPrice * $months;

        // Vérifier le solde
        $stmtBal = $pdo->prepare("SELECT credit_balance FROM users WHERE id = ? FOR UPDATE");
        $pdo->beginTransaction();

        try {
            $stmtBal->execute([$adminId]);
            $balance = (float)$stmtBal->fetchColumn();

            if ($balance < $totalPrice) {
                $pdo->rollBack();
                jsonError(
                    (__('credits.insufficient_balance') ?? 'Solde de crédits insuffisant') .
                    '. ' . (__('credits.required') ?? 'Requis') . ': ' . $totalPrice . ' CRT',
                    402
                );
                return;
            }

            $newBalance = $balance - $totalPrice;

            // Déduire les crédits
            $pdo->prepare("UPDATE users SET credit_balance = ? WHERE id = ?")
                ->execute([$newBalance, $adminId]);

            // Écrire la transaction de crédit
            $description = 'Prolongation module: ' . ($module['name'] ?? $code) . " ({$months} mois)";
            $pdo->prepare("
                INSERT INTO credit_transactions
                    (admin_id, type, amount, balance_after, reference_type, reference_id, description)
                VALUES (?, 'module_renewal', ?, ?, 'module', ?, ?)
            ")->execute([$adminId, -$totalPrice, $newBalance, $code, $description]);

            // Calculer la nouvelle date d'expiration
            // Si next_renewal_at est dans le futur, on prolonge à partir de cette date
            // Sinon on prolonge à partir de maintenant
            $stmtSub = $pdo->prepare("SELECT next_renewal_at FROM admin_module_subscriptions WHERE admin_id = ? AND module_code = ?");
            $stmtSub->execute([$adminId, $code]);
            $currentExpiry = $stmtSub->fetchColumn();

            $baseDate = ($currentExpiry && strtotime($currentExpiry) > time())
                ? $currentExpiry
                : date('Y-m-d H:i:s');

            $newExpiry = date('Y-m-d H:i:s', strtotime("+{$months} month", strtotime($baseDate)));

            // Mettre à jour l'abonnement
            $pdo->prepare("
                UPDATE admin_module_subscriptions
                SET last_renewal_at = NOW(), next_renewal_at = ?
                WHERE admin_id = ? AND module_code = ?
            ")->execute([$newExpiry, $adminId, $code]);

            $pdo->commit();

            jsonSuccess([
                'message' => (__('module.renewed_success') ?? 'Abonnement prolongé') . " ({$months} " . (__('credits.months') ?? 'mois') . ')',
                'module_code' => $code,
                'credits_deducted' => $totalPrice,
                'months' => $months,
                'new_balance' => $newBalance,
                'next_renewal_at' => $newExpiry,
            ]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            jsonError(__('credits.deduction_error') ?? 'Erreur lors de la déduction des crédits', 500);
        }
    }

    /**
     * PUT /api/modules/:code/auto-renew
     * Toggle auto-renewal for a module subscription
     */
    public function toggleAutoRenew(array $params): void
    {
        $code = $params['code'] ?? '';
        if (empty($code)) {
            jsonError(__('api.module_code_required') ?? 'Code module requis', 400);
            return;
        }

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        if ($adminId === null) {
            jsonError('Non autorisé', 403);
            return;
        }

        // Check subscription exists
        $stmt = $pdo->prepare("SELECT * FROM admin_module_subscriptions WHERE admin_id = ? AND module_code = ?");
        $stmt->execute([$adminId, $code]);
        $sub = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$sub) {
            jsonError('Aucun abonnement trouvé pour ce module', 404);
            return;
        }

        if ($sub['billing_type'] !== 'monthly') {
            jsonError('Le renouvellement automatique n\'est disponible que pour les modules mensuels', 400);
            return;
        }

        $newVal = (int)$sub['auto_renew'] ? 0 : 1;
        $pdo->prepare("UPDATE admin_module_subscriptions SET auto_renew = ? WHERE admin_id = ? AND module_code = ?")
            ->execute([$newVal, $adminId, $code]);

        jsonSuccess([
            'auto_renew' => (bool)$newVal,
            'message' => $newVal
                ? (__('module.auto_renew_enabled') ?? 'Renouvellement automatique activé')
                : (__('module.auto_renew_disabled') ?? 'Renouvellement automatique désactivé'),
        ]);
    }

    /**
     * PUT /api/modules/:code
     * Mettre à jour la configuration d'un module
     */
    public function updateModule(array $params): void
    {
        $code = $params['code'] ?? '';
        if (empty($code)) {
            jsonError(__('api.module_code_required'), 400);
        }

        $data = getJsonBody();
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        // Vérifier si le module existe
        if ($adminId !== null) {
            $stmt = $pdo->prepare("SELECT * FROM modules WHERE module_code = ? AND admin_id = ?");
            $stmt->execute([$code, $adminId]);
        }
        else {
            $stmt = $pdo->prepare("SELECT * FROM modules WHERE module_code = ?");
            $stmt->execute([$code]);
        }
        $module = $stmt->fetch();

        if (!$module) {
            jsonError(__('api.module_not_found'), 404);
        }

        // Mettre à jour les champs modifiables
        $updates = [];
        $values = [];

        if (isset($data['is_active'])) {
            $updates[] = 'is_active = ?';
            $values[] = $data['is_active'] ? 1 : 0;
        }

        if (isset($data['config'])) {
            $updates[] = 'config = ?';
            $values[] = json_encode($data['config']);
        }

        if (isset($data['display_order'])) {
            $updates[] = 'display_order = ?';
            $values[] = (int)$data['display_order'];
        }

        if (empty($updates)) {
            jsonError(__('api.no_update_provided'), 400);
        }

        $values[] = $module['id'];
        $sql = "UPDATE modules SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        // Retourner le module mis à jour
        $stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
        $stmt->execute([$module['id']]);
        $updated = $stmt->fetch();
        $updated['config'] = $updated['config'] ? json_decode($updated['config'], true) : [];

        jsonSuccess([
            'message' => __('api.module_updated'),
            'module' => $updated
        ]);
    }

    /**
     * Méthode helper: vérifier si un module est actif (pour usage interne)
     */
    public static function checkModuleActive(RadiusDatabase $db, string $code, ?int $adminId = null): bool
    {
        $pdo = $db->getPdo();
        if ($adminId !== null) {
            $stmt = $pdo->prepare("SELECT is_active FROM modules WHERE module_code = ? AND admin_id = ?");
            $stmt->execute([$code, $adminId]);
        }
        else {
            $stmt = $pdo->prepare("SELECT is_active FROM modules WHERE module_code = ?");
            $stmt->execute([$code]);
        }
        $result = $stmt->fetch();
        return $result ? (bool)$result['is_active'] : false;
    }
}