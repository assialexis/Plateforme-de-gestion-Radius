<?php
/**
 * Contrôleur du portail client PPPoE
 * Auth séparée via client_sessions (pas AuthService)
 */
class ClientPortalController
{
    private RadiusDatabase $db;
    private PDO $pdo;
    private array $config;

    public function __construct(RadiusDatabase $db, PDO $pdo, array $config = [])
    {
        $this->db = $db;
        $this->pdo = $pdo;
        $this->config = $config;
    }

    // ==========================================
    // Authentification client
    // ==========================================

    /**
     * POST /client/login
     */
    public function login(): void
    {
        $data = getJsonBody();
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : null;

        if (empty($username) || empty($password)) {
            jsonError('Identifiants requis', 400);
            return;
        }

        if (!$adminId) {
            jsonError('Admin non spécifié', 400);
            return;
        }

        // Chercher le user PPPoE
        $stmt = $this->pdo->prepare("
            SELECT pu.*, pp.name as profile_name, pp.price as profile_price
            FROM pppoe_users pu
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            WHERE pu.username = ? AND pu.admin_id = ?
        ");
        $stmt->execute([$username, $adminId]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonError('Identifiants incorrects', 401);
            return;
        }

        // Vérifier le mot de passe (stocké en clair pour RADIUS)
        if ($user['password'] !== $password) {
            jsonError('Identifiants incorrects', 401);
            return;
        }

        // Vérifier le statut
        if ($user['status'] === 'suspended') {
            jsonError('Votre compte est suspendu. Contactez votre fournisseur.', 403);
            return;
        }

        // Supprimer les anciennes sessions de ce user
        $stmt = $this->pdo->prepare("DELETE FROM client_sessions WHERE pppoe_user_id = ?");
        $stmt->execute([$user['id']]);

        // Créer une nouvelle session (24h)
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);

        $stmt = $this->pdo->prepare("
            INSERT INTO client_sessions (id, pppoe_user_id, admin_id, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $sessionId,
            $user['id'],
            $adminId,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $expiresAt
        ]);

        jsonSuccess([
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'customer_name' => $user['customer_name'],
                'status' => $user['status'],
                'profile_name' => $user['profile_name']
            ],
            'session_id' => $sessionId
        ], 'Connexion réussie');
    }

    /**
     * POST /client/logout
     */
    public function logout(): void
    {
        $sessionId = $this->getSessionIdFromHeader();
        if ($sessionId) {
            $stmt = $this->pdo->prepare("DELETE FROM client_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
        }
        jsonSuccess(null, 'Déconnexion réussie');
    }

    // ==========================================
    // Endpoints protégés
    // ==========================================

    /**
     * GET /client/account
     */
    public function getAccount(): void
    {
        $client = $this->authenticateClient();
        $this->requirePermission($client['admin_id'], 'client_view_account');

        $stmt = $this->pdo->prepare("
            SELECT pu.id, pu.username, pu.customer_name, pu.customer_phone, pu.customer_email,
                   pu.customer_address, pu.status, pu.valid_from, pu.valid_until, pu.created_at,
                   pu.data_used, pu.time_used, pu.profile_id,
                   pp.name as profile_name, pp.download_speed, pp.upload_speed,
                   pp.price as profile_price, pp.validity_days, pp.data_limit
            FROM pppoe_users pu
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            WHERE pu.id = ?
        ");
        $stmt->execute([$client['pppoe_user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonError('Compte non trouvé', 404);
            return;
        }

        // Calculer jours restants
        $daysRemaining = null;
        if ($user['valid_until']) {
            $daysRemaining = max(0, (int)((strtotime($user['valid_until']) - time()) / 86400));
        }

        // Permissions actives
        $permissions = $this->getClientPermissions($client['admin_id']);

        jsonSuccess([
            'user' => $user,
            'days_remaining' => $daysRemaining,
            'permissions' => $permissions
        ]);
    }

    /**
     * GET /client/invoices
     */
    public function getInvoices(): void
    {
        $client = $this->authenticateClient();
        $this->requirePermission($client['admin_id'], 'client_view_invoices');

        $stmt = $this->pdo->prepare("
            SELECT id, invoice_number, period_start, period_end, amount, tax_amount,
                   total_amount, paid_amount, status, due_date, paid_date, payment_method, created_at
            FROM pppoe_invoices
            WHERE pppoe_user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$client['pppoe_user_id']]);
        $invoices = $stmt->fetchAll();

        jsonSuccess(['invoices' => $invoices]);
    }

    /**
     * GET /client/transactions
     */
    public function getTransactions(): void
    {
        $client = $this->authenticateClient();
        $this->requirePermission($client['admin_id'], 'client_view_transactions');

        // Paiements manuels (admin)
        $stmt = $this->pdo->prepare("
            SELECT pp.id, pp.amount, pp.payment_method, pp.payment_reference,
                   pp.payment_date, pp.mobile_money_provider,
                   pi.invoice_number, 'payment' as source
            FROM pppoe_payments pp
            LEFT JOIN pppoe_invoices pi ON pp.invoice_id = pi.id
            WHERE pp.pppoe_user_id = ?
            ORDER BY pp.payment_date DESC
        ");
        $stmt->execute([$client['pppoe_user_id']]);
        $payments = $stmt->fetchAll();

        // Transactions en ligne
        $stmt = $this->pdo->prepare("
            SELECT pt.transaction_id, pt.amount, pt.gateway_code as payment_method,
                   pt.payment_type, pt.status, pt.created_at as payment_date,
                   pt.description, 'online' as source
            FROM pppoe_payment_transactions pt
            WHERE pt.pppoe_user_id = ? AND pt.status = 'completed'
            ORDER BY pt.created_at DESC
        ");
        $stmt->execute([$client['pppoe_user_id']]);
        $onlinePayments = $stmt->fetchAll();

        // Fusionner et trier par date
        $all = array_merge($payments, $onlinePayments);
        usort($all, fn($a, $b) => strtotime($b['payment_date']) - strtotime($a['payment_date']));

        jsonSuccess(['transactions' => $all]);
    }

    /**
     * GET /client/plans
     */
    public function getPlans(): void
    {
        $client = $this->authenticateClient();
        $this->requirePermission($client['admin_id'], 'client_change_plan');

        // Profil actuel
        $stmt = $this->pdo->prepare("SELECT profile_id FROM pppoe_users WHERE id = ?");
        $stmt->execute([$client['pppoe_user_id']]);
        $currentProfileId = (int)$stmt->fetchColumn();

        // Profils disponibles pour cet admin
        $stmt = $this->pdo->prepare("
            SELECT id, name, description, download_speed, upload_speed,
                   data_limit, validity_days, price, is_active
            FROM pppoe_profiles
            WHERE (admin_id = ? OR admin_id IS NULL) AND is_active = 1
            ORDER BY price ASC
        ");
        $stmt->execute([$client['admin_id']]);
        $profiles = $stmt->fetchAll();

        // Gateways de paiement (admin + plateforme)
        $safeGateways = $this->getAllAvailableGateways($client['admin_id']);

        jsonSuccess([
            'profiles' => $profiles,
            'current_profile_id' => $currentProfileId,
            'gateways' => $safeGateways
        ]);
    }

    /**
     * POST /client/change-plan
     */
    public function changePlan(): void
    {
        $client = $this->authenticateClient();
        $this->requirePermission($client['admin_id'], 'client_change_plan');

        $data = getJsonBody();
        $newProfileId = isset($data['profile_id']) ? (int)$data['profile_id'] : 0;

        if (!$newProfileId) {
            jsonError('Profil requis', 400);
            return;
        }

        // Vérifier le profil
        $newProfile = $this->db->getPPPoEProfileById($newProfileId);
        if (!$newProfile || !$newProfile['is_active']) {
            jsonError('Profil non disponible', 404);
            return;
        }

        // Vérifier que le profil appartient au même admin
        if ($newProfile['admin_id'] && $newProfile['admin_id'] != $client['admin_id']) {
            jsonError('Profil non autorisé', 403);
            return;
        }

        // Vérifier que ce n'est pas le même profil
        $stmt = $this->pdo->prepare("SELECT profile_id FROM pppoe_users WHERE id = ?");
        $stmt->execute([$client['pppoe_user_id']]);
        $currentProfileId = (int)$stmt->fetchColumn();

        if ($currentProfileId === $newProfileId) {
            jsonError('Vous êtes déjà sur cette offre', 400);
            return;
        }

        // Créer une facture pour le changement de plan
        $invoiceData = [
            'pppoe_user_id' => $client['pppoe_user_id'],
            'period_start' => date('Y-m-d'),
            'period_end' => date('Y-m-d', strtotime('+' . ($newProfile['validity_days'] ?? 30) . ' days')),
            'amount' => $newProfile['price'],
            'status' => 'pending',
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'admin_id' => $client['admin_id'],
            'description' => 'Changement offre: ' . $newProfile['name']
        ];

        $invoiceId = $this->db->createInvoice($invoiceData);

        // Stocker les metadata pour le changement de plan
        $metadata = json_encode([
            'type' => 'plan_change',
            'new_profile_id' => $newProfileId,
            'old_profile_id' => $currentProfileId
        ]);
        $this->pdo->prepare("UPDATE pppoe_invoices SET metadata = ? WHERE id = ?")->execute([$metadata, $invoiceId]);

        // Ajouter l'item de facture
        $stmt = $this->pdo->prepare("
            INSERT INTO pppoe_invoice_items (invoice_id, description, quantity, unit_price, total_price, item_type)
            VALUES (?, ?, 1, ?, ?, 'subscription')
        ");
        $stmt->execute([
            $invoiceId,
            'Changement offre: ' . $newProfile['name'],
            $newProfile['price'],
            $newProfile['price']
        ]);

        // Retourner la facture + gateways (admin + plateforme)
        $safeGateways = $this->getAllAvailableGateways($client['admin_id']);

        jsonSuccess([
            'invoice_id' => $invoiceId,
            'amount' => $newProfile['price'],
            'profile' => $newProfile,
            'gateways' => $safeGateways
        ], 'Facture créée. Procédez au paiement.');
    }

    /**
     * GET /client/traffic
     */
    public function getTrafficStats(): void
    {
        $client = $this->authenticateClient();
        $this->requirePermission($client['admin_id'], 'client_view_traffic');

        // Données de consommation
        $stmt = $this->pdo->prepare("
            SELECT data_used, time_used, last_login
            FROM pppoe_users WHERE id = ?
        ");
        $stmt->execute([$client['pppoe_user_id']]);
        $usage = $stmt->fetch();

        // Sessions récentes (20 dernières)
        $stmt = $this->pdo->prepare("
            SELECT acct_session_id as session_id, start_time, stop_time,
                   input_octets, output_octets,
                   session_time, terminate_cause
            FROM pppoe_sessions
            WHERE pppoe_user_id = ?
            ORDER BY start_time DESC
            LIMIT 20
        ");
        $stmt->execute([$client['pppoe_user_id']]);
        $sessions = $stmt->fetchAll();

        jsonSuccess([
            'data_used' => (int)($usage['data_used'] ?? 0),
            'time_used' => (int)($usage['time_used'] ?? 0),
            'last_login' => $usage['last_login'] ?? null,
            'sessions' => $sessions
        ]);
    }

    /**
     * POST /client/pay — Initier un paiement en ligne
     */
    public function initiatePayment(): void
    {
        $client = $this->authenticateClient();

        $data = getJsonBody();
        $invoiceId = isset($data['invoice_id']) ? (int)$data['invoice_id'] : null;
        $gatewayCode = $data['gateway_code'] ?? '';
        $paymentType = $data['payment_type'] ?? 'invoice';
        $customerPhone = $data['customer_phone'] ?? '';

        if (empty($gatewayCode)) {
            jsonError('Passerelle de paiement requise', 400);
            return;
        }

        // Récupérer le user PPPoE
        $stmt = $this->pdo->prepare("SELECT * FROM pppoe_users WHERE id = ?");
        $stmt->execute([$client['pppoe_user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonError('Compte non trouvé', 404);
            return;
        }

        $profile = $this->db->getPPPoEProfileById($user['profile_id']);
        $amount = 0;
        $description = '';

        if ($paymentType === 'invoice' && $invoiceId) {
            $stmt = $this->pdo->prepare("SELECT * FROM pppoe_invoices WHERE id = ? AND pppoe_user_id = ?");
            $stmt->execute([$invoiceId, $user['id']]);
            $invoice = $stmt->fetch();

            if (!$invoice) {
                jsonError('Facture non trouvée', 404);
                return;
            }
            if ($invoice['status'] === 'paid') {
                jsonError('Facture déjà payée', 400);
                return;
            }

            $amount = $invoice['total_amount'] - $invoice['paid_amount'];
            $description = 'Paiement facture ' . $invoice['invoice_number'];
        } elseif ($paymentType === 'extension') {
            $amount = $profile['price'] ?? 0;
            $description = 'Prolongation abonnement ' . ($profile['name'] ?? '');
        } elseif ($paymentType === 'renewal') {
            $amount = $profile['price'] ?? 0;
            $description = 'Renouvellement abonnement ' . ($profile['name'] ?? '');
        } else {
            jsonError('Type de paiement invalide', 400);
            return;
        }

        if ($amount <= 0) {
            jsonError('Montant invalide', 400);
            return;
        }

        // Vérifier la gateway (admin ou plateforme)
        $isPlatform = !empty($data['is_platform']);
        $gateway = null;

        if ($isPlatform) {
            $gateway = $this->findPlatformGateway($gatewayCode, $client['admin_id']);
        } else {
            $gateway = $this->db->getPaymentGatewayByCode($gatewayCode, $client['admin_id']);
            if (!$gateway || !$gateway['is_active']) {
                // Fallback: chercher dans les passerelles plateforme
                $gateway = $this->findPlatformGateway($gatewayCode, $client['admin_id']);
                if ($gateway) $isPlatform = true;
            }
        }

        if (!$gateway) {
            jsonError('Passerelle non disponible', 400);
            return;
        }

        // Créer la transaction
        $transactionId = 'PPPOE_' . strtoupper(bin2hex(random_bytes(12)));
        $stmt = $this->pdo->prepare("
            INSERT INTO pppoe_payment_transactions (
                transaction_id, pppoe_user_id, admin_id, gateway_code, payment_type,
                invoice_id, amount, currency, customer_phone, customer_email,
                description, status, is_platform, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
        ");
        $stmt->execute([
            $transactionId,
            $user['id'],
            $client['admin_id'],
            $gatewayCode,
            $paymentType,
            $invoiceId,
            $amount,
            $this->config['currency'] ?? 'XAF',
            $customerPhone,
            $user['customer_email'] ?? '',
            $description,
            $isPlatform ? 1 : 0
        ]);

        // Initier le paiement via la gateway
        try {
            $pppoeUsername = $user['username'] ?? 'Client';
            $phone = $customerPhone;
            $email = $user['customer_email'] ?? '';

            // Injecter l'opérateur choisi par le client dans la config
            $operator = $data['operator'] ?? '';
            if (!empty($operator)) {
                $config = is_array($gateway['config']) ? $gateway['config'] : (json_decode($gateway['config'], true) ?? []);
                $config['operator'] = $operator;
                $gateway['config'] = $config;
            }
            if ($isPlatform) {
                $redirectUrl = $this->callPlatformGatewayPayment($gateway, $transactionId, $amount, $phone, $email, $description, $pppoeUsername, $client['admin_id']);
            } else {
                $redirectUrl = $this->callGatewayPayment($gateway, $transactionId, $amount, $phone, $email, $description, $pppoeUsername, $client['admin_id']);
            }

            jsonSuccess([
                'transaction_id' => $transactionId,
                'redirect_url' => $redirectUrl
            ]);
        } catch (\Throwable $e) {
            $stmt = $this->pdo->prepare("UPDATE pppoe_payment_transactions SET status = 'failed', error_message = ? WHERE transaction_id = ?");
            $stmt->execute([$e->getMessage(), $transactionId]);
            jsonError('Erreur initiation paiement: ' . $e->getMessage(), 500);
        }
    }

    // ==========================================
    // Auth & permissions internes
    // ==========================================

    /**
     * Lire le session_id depuis le header X-Client-Session
     */
    private function getSessionIdFromHeader(): string
    {
        return $_SERVER['HTTP_X_CLIENT_SESSION'] ?? '';
    }

    /**
     * Authentifier le client via header X-Client-Session
     */
    private function authenticateClient(): array
    {
        $sessionId = $this->getSessionIdFromHeader();

        if (empty($sessionId)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non authentifié']);
            exit;
        }

        // Nettoyer les sessions expirées
        $this->pdo->exec("DELETE FROM client_sessions WHERE expires_at < NOW()");

        $stmt = $this->pdo->prepare("
            SELECT cs.pppoe_user_id, cs.admin_id, pu.status
            FROM client_sessions cs
            JOIN pppoe_users pu ON cs.pppoe_user_id = pu.id
            WHERE cs.id = ? AND cs.expires_at > NOW()
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if (!$session) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Session expirée']);
            exit;
        }

        if ($session['status'] === 'suspended') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Compte suspendu']);
            exit;
        }

        return [
            'pppoe_user_id' => (int)$session['pppoe_user_id'],
            'admin_id' => (int)$session['admin_id']
        ];
    }

    /**
     * Vérifier si le rôle client a une permission spécifique
     */
    private function clientHasPermission(int $adminId, string $permissionCode): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role = 'client'
            AND p.permission_code = ?
            AND (rp.admin_id = ? OR rp.admin_id IS NULL)
            ORDER BY rp.admin_id DESC
            LIMIT 1
        ");
        $stmt->execute([$permissionCode, $adminId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Exiger une permission ou erreur 403
     */
    private function requirePermission(int $adminId, string $permissionCode): void
    {
        if (!$this->clientHasPermission($adminId, $permissionCode)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permission refusée']);
            exit;
        }
    }

    /**
     * Obtenir toutes les permissions actives pour le rôle client
     */
    private function getClientPermissions(int $adminId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.permission_code
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role = 'client'
            AND p.category = 'client_portal'
            AND (rp.admin_id = ? OR rp.admin_id IS NULL)
        ");
        $stmt->execute([$adminId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Récupérer toutes les passerelles disponibles (admin + plateforme) pour un admin
     */
    /**
     * Gateways qui nécessitent obligatoirement un numéro de téléphone (paiement push USSD)
     */
    private const PHONE_REQUIRED_GATEWAYS = ['feexpay', 'paygate', 'paygate_global'];

    private function getAllAvailableGateways(int $adminId): array
    {
        // Gateways propres à l'admin
        $gateways = $this->db->getActivePaymentGateways($adminId);
        $result = array_map(fn($g) => [
            'code' => $g['gateway_code'],
            'name' => $g['name'],
            'logo_url' => $g['logo_url'] ?? null,
            'is_platform' => false,
            'requires_phone' => in_array($g['gateway_code'], self::PHONE_REQUIRED_GATEWAYS)
        ], $gateways);

        // Gateways plateforme activées pour cet admin
        $existingCodes = array_column($result, 'code');
        $platformGateways = $this->getPlatformGatewaysForAdmin($adminId);
        foreach ($platformGateways as $pg) {
            if (!in_array($pg['gateway_code'], $existingCodes)) {
                $result[] = [
                    'code' => $pg['gateway_code'],
                    'name' => $pg['name'],
                    'logo_url' => null,
                    'is_platform' => true,
                    'requires_phone' => in_array($pg['gateway_code'], self::PHONE_REQUIRED_GATEWAYS)
                ];
            }
        }

        return $result;
    }

    /**
     * Récupérer les passerelles plateforme actives pour un admin
     */
    private function getPlatformGatewaysForAdmin(int $adminId): array
    {
        // Vérifier si le système paygate est activé
        $stmt = $this->pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'paygate_enabled'");
        $stmt->execute();
        $enabled = $stmt->fetchColumn();
        if ($enabled !== '1') return [];

        $stmt = $this->pdo->prepare("
            SELECT pg.id, pg.gateway_code, pg.name, pg.description
            FROM platform_payment_gateways pg
            INNER JOIN admin_platform_gateways apg ON apg.platform_gateway_id = pg.id
            WHERE pg.is_active = 1 AND apg.is_active = 1 AND apg.admin_id = ?
            ORDER BY pg.display_order
        ");
        $stmt->execute([$adminId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouver une passerelle plateforme par code pour un admin
     */
    private function findPlatformGateway(string $gatewayCode, int $adminId): ?array
    {
        $platformGateways = $this->getPlatformGatewaysForAdmin($adminId);
        foreach ($platformGateways as $pg) {
            if ($pg['gateway_code'] === $gatewayCode) {
                // Charger la config depuis les recharge gateways (source unique)
                $configStmt = $this->pdo->prepare(
                    "SELECT config, is_sandbox FROM payment_gateways WHERE gateway_code = ? AND admin_id IS NULL"
                );
                $configStmt->execute([$gatewayCode]);
                $configRow = $configStmt->fetch(PDO::FETCH_ASSOC);

                $pg['config'] = $configRow ? (json_decode($configRow['config'] ?: '{}', true) ?: []) : [];
                $pg['is_sandbox'] = $configRow ? (bool)$configRow['is_sandbox'] : true;
                $pg['is_active'] = true;
                return $pg;
            }
        }
        return null;
    }

    /**
     * Appeler la gateway de paiement — délègue à PPPoEPayController
     * qui gère toutes les passerelles (fedapay, cinetpay, kkiapay, feexpay,
     * moneroo, paygate, paydunya, stripe, paypal, yengapay, etc.)
     */
    private function callGatewayPayment(array $gateway, string $transactionId, float $amount, string $phone, string $email, string $description, string $customerName, int $adminId = 0): string
    {
        require_once __DIR__ . '/PPPoEPayController.php';
        $payController = new PPPoEPayController($this->db, $this->config);
        return $payController->initiateGatewayPayment($gateway, $transactionId, $amount, $phone, $email, $description, $customerName);
    }

    /**
     * Appeler une passerelle plateforme pour le paiement
     */
    private function callPlatformGatewayPayment(array $gateway, string $transactionId, float $amount, string $phone, string $email, string $description, string $customerName, int $adminId = 0): string
    {
        return $this->callGatewayPayment($gateway, $transactionId, $amount, $phone, $email, $description, $customerName, $adminId);
    }
}
