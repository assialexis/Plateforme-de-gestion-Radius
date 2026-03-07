<?php
/**
 * Controller API PPPoE Users & Profiles
 */

require_once __DIR__ . '/../MikroTik/CommandSender.php';

class PPPoEController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private MikroTikCommandSender $commandSender;
    private ?NodePushService $pushService;

    public function __construct(RadiusDatabase $db, AuthService $auth, ?NodePushService $pushService = null)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->commandSender = new MikroTikCommandSender($db->getPdo());
        $this->pushService = $pushService;
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * Vérifier qu'une entité appartient à l'admin courant
     */
    private function verifyOwnership(?array $entity, string $entityName = 'Resource'): void
    {
        $adminId = $this->getAdminId();
        if ($adminId !== null && $entity !== null && isset($entity['admin_id']) && (int)$entity['admin_id'] !== $adminId) {
            jsonError($entityName . ' not found', 404);
            exit;
        }
    }

    // ==========================================
    // PPPoE Users
    // ==========================================

    /**
     * GET /api/pppoe/users
     */
    public function listUsers(): void
    {
        $filters = [
            'status' => get('status'),
            'search' => get('search'),
            'profile_id' => get('profile_id'),
            'zone_id' => get('zone_id'),
        ];

        $page = max(1, (int)(get('page') ?: 1));
        $perPage = min(100, max(10, (int)(get('per_page') ?: 20)));

        $result = $this->db->getAllPPPoEUsers($filters, $page, $perPage, $this->getAdminId());
        jsonSuccess($result);
    }

    /**
     * GET /api/pppoe/users/{id}
     */
    public function showUser(array $params): void
    {
        $id = (int)$params['id'];
        $user = $this->db->getPPPoEUserById($id);

        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        // Ajouter les sessions récentes
        $user['recent_sessions'] = $this->db->getPPPoEUserSessions($id, 10);
        $user['active_sessions'] = $this->db->countActivePPPoESessions($id);

        jsonSuccess($user);
    }

    /**
     * POST /api/pppoe/users
     */
    public function createUser(): void
    {
        $data = getJsonBody();

        if (empty($data['username'])) {
            jsonError(__('api.pppoe_username_required'), 400);
        }

        if (empty($data['password'])) {
            jsonError(__('api.password_required'), 400);
        }

        if (empty($data['profile_id'])) {
            jsonError(__('api.pppoe_profile_required'), 400);
        }

        // Vérifier si le username existe déjà (PPPoE ou voucher)
        if ($this->db->getPPPoEUserByUsername($data['username'])) {
            jsonError(__('api.pppoe_username_exists'), 400);
        }

        if ($this->db->getVoucherByUsername($data['username'])) {
            jsonError(__('api.pppoe_username_exists_voucher'), 400);
        }

        // Vérifier que le profil existe
        $profile = $this->db->getPPPoEProfileById((int)$data['profile_id']);
        if (!$profile) {
            jsonError(__('api.profile_not_found'), 400);
        }

        // Ajouter le vendeur actuel si connecté
        if (empty($data['sold_by']) && isset($_SESSION['user_id'])) {
            $data['sold_by'] = $_SESSION['user_id'];
        }

        // Multi-tenant: associate with current admin
        $data['admin_id'] = $this->getAdminId();

        // Compte désactivé jusqu'au paiement de la facture initiale
        $data['status'] = 'disabled';

        try {
            $id = $this->db->createPPPoEUser($data);

            // Si mode pool avec une IP assignée, allouer l'IP
            if (($data['ip_mode'] ?? '') === 'pool' && !empty($data['pool_ip'])) {
                $this->allocateIPForPPPoE($data['pool_ip'], $id, $data['username']);
            }

            // Si mode statique avec une IP, l'allouer si elle fait partie d'un pool
            if (($data['ip_mode'] ?? '') === 'static' && !empty($data['static_ip'])) {
                $this->allocateStaticIP($data['static_ip'], $id, $data['username']);
            }

            // Créer automatiquement une facture pour le nouvel abonnement
            $this->createInitialInvoice($id, $profile);

            $user = $this->db->getPPPoEUserById($id);
            jsonSuccess($user, __('api.pppoe_user_created'));
        } catch (Exception $e) {
            jsonError(__('api.pppoe_user_create_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Allouer une IP du pool à un utilisateur PPPoE
     */
    private function allocateIPForPPPoE(string $ipAddress, int $userId, string $username): bool
    {
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("
            UPDATE ip_pool_allocations
            SET status = 'allocated',
                user_type = 'pppoe',
                user_id = ?,
                username = ?,
                allocated_at = NOW()
            WHERE ip_address = ? AND status = 'available'
        ");
        $stmt->execute([$userId, $username, $ipAddress]);

        if ($stmt->rowCount() > 0) {
            // Mettre à jour le compteur du pool
            $stmt = $pdo->prepare("
                UPDATE ip_pools p
                JOIN ip_pool_allocations a ON a.pool_id = p.id
                SET p.used_ips = p.used_ips + 1
                WHERE a.ip_address = ?
            ");
            $stmt->execute([$ipAddress]);
            return true;
        }

        return false;
    }

    /**
     * Libérer l'IP d'un utilisateur PPPoE
     */
    private function releaseIPForPPPoE(int $userId): bool
    {
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("
            SELECT pool_id FROM ip_pool_allocations
            WHERE user_type = 'pppoe' AND user_id = ? AND status = 'allocated'
        ");
        $stmt->execute([$userId]);
        $allocation = $stmt->fetch();

        if (!$allocation) {
            return false;
        }

        $stmt = $pdo->prepare("
            UPDATE ip_pool_allocations
            SET status = 'available',
                user_id = NULL,
                username = NULL,
                allocated_at = NULL
            WHERE user_type = 'pppoe' AND user_id = ? AND status = 'allocated'
        ");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() > 0) {
            // Décrémenter le compteur du pool
            $stmt = $pdo->prepare("UPDATE ip_pools SET used_ips = GREATEST(0, used_ips - 1) WHERE id = ?");
            $stmt->execute([$allocation['pool_id']]);
            return true;
        }

        return false;
    }

    /**
     * Allouer une IP statique (si elle fait partie d'un pool)
     */
    private function allocateStaticIP(string $ipAddress, int $userId, string $username): bool
    {
        $pdo = $this->db->getPdo();

        // Vérifier si l'IP fait partie d'un pool
        $stmt = $pdo->prepare("
            SELECT id, pool_id, status FROM ip_pool_allocations
            WHERE ip_address = ?
        ");
        $stmt->execute([$ipAddress]);
        $allocation = $stmt->fetch();

        if (!$allocation) {
            // L'IP n'est pas dans un pool, pas besoin de l'allouer
            return true;
        }

        // Si l'IP est disponible ou réservée pour ce user, l'allouer
        if ($allocation['status'] === 'available' ||
            ($allocation['status'] === 'reserved' && $allocation['user_id'] == $userId)) {

            $wasReserved = ($allocation['status'] === 'reserved');

            $stmt = $pdo->prepare("
                UPDATE ip_pool_allocations
                SET status = 'allocated',
                    user_type = 'static',
                    user_id = ?,
                    username = ?,
                    allocated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId, $username, $allocation['id']]);

            if ($stmt->rowCount() > 0 && !$wasReserved) {
                // Mettre à jour le compteur du pool (seulement si n'était pas déjà réservée)
                $stmt = $pdo->prepare("UPDATE ip_pools SET used_ips = used_ips + 1 WHERE id = ?");
                $stmt->execute([$allocation['pool_id']]);
            }
            return true;
        }

        return false;
    }

    /**
     * Libérer une IP statique (si elle fait partie d'un pool)
     */
    private function releaseStaticIP(string $ipAddress): bool
    {
        $pdo = $this->db->getPdo();

        // Vérifier si l'IP fait partie d'un pool et est allouée
        $stmt = $pdo->prepare("
            SELECT id, pool_id, status FROM ip_pool_allocations
            WHERE ip_address = ? AND user_type = 'static' AND status = 'allocated'
        ");
        $stmt->execute([$ipAddress]);
        $allocation = $stmt->fetch();

        if (!$allocation) {
            return true; // Pas dans un pool ou pas allouée
        }

        $stmt = $pdo->prepare("
            UPDATE ip_pool_allocations
            SET status = 'available',
                user_id = NULL,
                username = NULL,
                allocated_at = NULL
            WHERE id = ?
        ");
        $stmt->execute([$allocation['id']]);

        if ($stmt->rowCount() > 0) {
            // Décrémenter le compteur du pool
            $stmt = $pdo->prepare("UPDATE ip_pools SET used_ips = GREATEST(0, used_ips - 1) WHERE id = ?");
            $stmt->execute([$allocation['pool_id']]);
            return true;
        }

        return false;
    }

    /**
     * PUT /api/pppoe/users/{id}
     */
    public function updateUser(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $user = $this->db->getPPPoEUserById($id);
        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        // Si le username change, vérifier qu'il n'existe pas déjà
        if (!empty($data['username']) && $data['username'] !== $user['username']) {
            if ($this->db->getPPPoEUserByUsername($data['username'])) {
                jsonError(__('api.pppoe_username_exists'), 400);
            }
            if ($this->db->getVoucherByUsername($data['username'])) {
                jsonError(__('api.pppoe_username_exists_voucher'), 400);
            }
        }

        try {
            // Gérer les changements d'IP
            $oldIpMode = $user['ip_mode'] ?? 'router';
            $newIpMode = $data['ip_mode'] ?? $oldIpMode;
            $oldPoolIp = $user['pool_ip'] ?? null;
            $newPoolIp = $data['pool_ip'] ?? null;
            $oldStaticIp = $user['static_ip'] ?? null;
            $newStaticIp = $data['static_ip'] ?? null;
            $username = $data['username'] ?? $user['username'];

            // Si on quitte le mode pool ou change d'IP pool, libérer l'ancienne IP pool
            if ($oldIpMode === 'pool' && $oldPoolIp) {
                if ($newIpMode !== 'pool' || $newPoolIp !== $oldPoolIp) {
                    $this->releaseIPForPPPoE($id);
                }
            }

            // Si on quitte le mode static ou change d'IP statique, libérer l'ancienne IP statique
            if ($oldIpMode === 'static' && $oldStaticIp) {
                if ($newIpMode !== 'static' || $newStaticIp !== $oldStaticIp) {
                    $this->releaseStaticIP($oldStaticIp);
                }
            }

            // Si on passe en mode pool avec une nouvelle IP, l'allouer
            if ($newIpMode === 'pool' && $newPoolIp && $newPoolIp !== $oldPoolIp) {
                $this->allocateIPForPPPoE($newPoolIp, $id, $username);
            }

            // Si on passe en mode static avec une nouvelle IP, l'allouer
            if ($newIpMode === 'static' && $newStaticIp && $newStaticIp !== $oldStaticIp) {
                $this->allocateStaticIP($newStaticIp, $id, $username);
            }

            $this->db->updatePPPoEUser($id, $data);
            $updatedUser = $this->db->getPPPoEUserById($id);
            jsonSuccess($updatedUser, __('api.pppoe_user_updated'));
        } catch (Exception $e) {
            jsonError(__('api.pppoe_user_update_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/pppoe/users/{id}
     */
    public function deleteUser(array $params): void
    {
        $id = (int)$params['id'];

        $user = $this->db->getPPPoEUserById($id);
        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        // Vérifier s'il y a des sessions actives
        if ($this->db->countActivePPPoESessions($id) > 0) {
            jsonError(__('api.pppoe_user_has_active_sessions'), 400);
        }

        try {
            // Libérer l'IP du pool si assignée
            if (($user['ip_mode'] ?? '') === 'pool' && !empty($user['pool_ip'])) {
                $this->releaseIPForPPPoE($id);
            }

            // Libérer l'IP statique si assignée
            if (($user['ip_mode'] ?? '') === 'static' && !empty($user['static_ip'])) {
                $this->releaseStaticIP($user['static_ip']);
            }

            $this->db->deletePPPoEUser($id);
            jsonSuccess(null, __('api.pppoe_user_deleted'));
        } catch (Exception $e) {
            jsonError(__('api.pppoe_user_delete_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/pppoe/users/{id}/renew
     */
    public function renewUser(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $user = $this->db->getPPPoEUserById($id);
        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        $days = isset($data['days']) ? (int)$data['days'] : null;
        $paymentMethod = $data['payment_method'] ?? null;

        try {
            $invoice = null;
            $payment = null;

            // Si méthode de paiement fournie, créer facture + paiement
            if ($paymentMethod) {
                $adminId = $this->getAdminId();
                $profile = $this->db->getPPPoEProfileById($user['profile_id']);

                // Créer la facture
                $invoiceId = $this->db->generateInvoiceForUser($user, [
                    'admin_id' => $adminId
                ]);
                $invoice = $this->db->getInvoiceById($invoiceId);

                // Enregistrer le paiement total
                $paymentId = $this->db->createPayment([
                    'invoice_id' => $invoiceId,
                    'pppoe_user_id' => $user['id'],
                    'amount' => $invoice['total_amount'],
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'received_by' => $_SESSION['user_id'] ?? null,
                    'admin_id' => $adminId
                ]);
                $payment = $this->db->getPaymentById($paymentId);
                $invoice = $this->db->getInvoiceById($invoiceId);

                // Notification de paiement
                try {
                    require_once __DIR__ . '/../Utils/pppoe-payment-helpers.php';
                    sendPaymentNotification($this->db->getPdo(), $invoiceId, $adminId);
                } catch (\Throwable $e) {
                    error_log('Payment notification failed: ' . $e->getMessage());
                }
            }

            // Renouveler l'abonnement
            $this->db->renewPPPoEUser($id, $days);
            $updatedUser = $this->db->getPPPoEUserById($id);

            $result = $updatedUser;
            if ($invoice) {
                $result['invoice'] = $invoice;
                $result['payment'] = $payment;
            }

            jsonSuccess($result, __('api.pppoe_subscription_renewed'));
        } catch (Exception $e) {
            jsonError(__('api.pppoe_renew_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/pppoe/users/{id}/suspend
     */
    public function suspendUser(array $params): void
    {
        $id = (int)$params['id'];

        $user = $this->db->getPPPoEUserById($id);
        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        try {
            // Mettre à jour le statut dans la base
            $this->db->updatePPPoEUserStatus($id, 'suspended');

            // Déconnecter l'utilisateur du routeur MikroTik
            $disconnectSent = $this->disconnectPPPoEUserFromRouter($user);

            // Fermer la session dans la base RADIUS
            $this->closePPPoESession($id);

            $message = __('api.pppoe_account_suspended');
            if ($disconnectSent) {
                $message .= ' - ' . __('api.pppoe_disconnect_sent');
            }

            jsonSuccess(['disconnect_sent' => $disconnectSent], $message);
        } catch (Exception $e) {
            jsonError(__('api.pppoe_suspend_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Déconnecte un utilisateur PPPoE du routeur MikroTik
     * @return bool True si au moins une commande de déconnexion a été envoyée
     */
    private function disconnectPPPoEUserFromRouter(array $user): bool
    {
        $pdo = $this->db->getPdo();
        $sent = false;

        // 1. D'abord essayer via la zone de l'utilisateur
        $zoneId = $user['zone_id'] ?? null;
        if ($zoneId) {
            $stmt = $pdo->prepare("
                SELECT router_id FROM nas
                WHERE zone_id = ? AND router_id IS NOT NULL AND router_id != ''
            ");
            $stmt->execute([$zoneId]);
            while ($nas = $stmt->fetch()) {
                if ($this->commandSender->disconnectPPPoEUser($nas['router_id'], $user['username'])) {
                    $sent = true;
                }
            }
        }

        // 2. Si pas trouvé par zone, chercher via la session active
        if (!$sent) {
            $stmt = $pdo->prepare("
                SELECT ps.nas_ip, n.router_id
                FROM pppoe_sessions ps
                LEFT JOIN nas n ON ps.nas_ip = n.nasname
                WHERE ps.pppoe_user_id = ? AND ps.stop_time IS NULL AND n.router_id IS NOT NULL
                LIMIT 1
            ");
            $stmt->execute([$user['id']]);
            $session = $stmt->fetch();

            if ($session && $session['router_id']) {
                $sent = $this->commandSender->disconnectPPPoEUser($session['router_id'], $user['username']);
            }
        }

        // 3. En dernier recours, envoyer à tous les NAS avec router_id
        if (!$sent) {
            $stmt = $pdo->query("SELECT router_id FROM nas WHERE router_id IS NOT NULL AND router_id != ''");
            while ($nas = $stmt->fetch()) {
                if ($this->commandSender->disconnectPPPoEUser($nas['router_id'], $user['username'])) {
                    $sent = true;
                }
            }
        }

        return $sent;
    }

    /**
     * Supprime le FUP d'un utilisateur sur le(s) routeur(s) MikroTik
     */
    private function removeFupQueueFromRouter(array $user): void
    {
        $pdo = $this->db->getPdo();
        $username = $user['username'] ?? '';
        if (empty($username)) return;

        $zoneId = $user['zone_id'] ?? null;
        if ($zoneId) {
            $stmt = $pdo->prepare("
                SELECT router_id FROM nas
                WHERE zone_id = ? AND router_id IS NOT NULL AND router_id != ''
            ");
            $stmt->execute([$zoneId]);
            while ($nas = $stmt->fetch()) {
                $this->commandSender->removeFupQueue($nas['router_id'], $username);
            }
        } else {
            $stmt = $pdo->query("SELECT router_id FROM nas WHERE router_id IS NOT NULL AND router_id != ''");
            while ($nas = $stmt->fetch()) {
                $this->commandSender->removeFupQueue($nas['router_id'], $username);
            }
        }
    }

    /**
     * Ferme la session PPPoE dans la base de données
     */
    private function closePPPoESession(int $userId): void
    {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("
            UPDATE pppoe_sessions
            SET stop_time = NOW(), terminate_cause = 'Admin-Reset'
            WHERE pppoe_user_id = ? AND stop_time IS NULL
        ");
        $stmt->execute([$userId]);
    }

    /**
     * POST /api/pppoe/users/{id}/activate
     */
    public function activateUser(array $params): void
    {
        $id = (int)$params['id'];

        $user = $this->db->getPPPoEUserById($id);
        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        // Vérifier si l'abonnement n'est pas expiré
        if ($user['valid_until'] && strtotime($user['valid_until']) < time()) {
            jsonError(__('api.pppoe_activate_expired'), 400);
        }

        try {
            $this->db->updatePPPoEUserStatus($id, 'active');
            jsonSuccess(null, __('api.pppoe_user_activated'));
        } catch (Exception $e) {
            jsonError(__('api.pppoe_activate_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pppoe/users/{id}/sessions
     */
    public function userSessions(array $params): void
    {
        $id = (int)$params['id'];
        $limit = min(100, max(10, (int)(get('limit') ?: 50)));

        $user = $this->db->getPPPoEUserById($id);
        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        $sessions = $this->db->getPPPoEUserSessions($id, $limit);
        jsonSuccess([
            'user' => $user,
            'sessions' => $sessions
        ]);
    }

    /**
     * POST /pppoe/users/{id}/reset-traffic
     */
    public function resetTraffic(array $params): void
    {
        $id = (int)$params['id'];

        $user = $this->db->getPPPoEUserById($id);
        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        $this->db->resetPPPoEUserTraffic($id);
        jsonSuccess(['message' => __('api.pppoe_traffic_reset_success')]);
    }

    /**
     * GET /pppoe/users/{id}/traffic-stats
     */
    public function userTrafficStats(array $params): void
    {
        $id = (int)$params['id'];
        $days = min(365, max(1, (int)(get('days') ?: 30)));

        $user = $this->db->getPPPoEUserById($id);
        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        $stats = $this->db->getPPPoEUserDailyTraffic($id, $days);
        jsonSuccess($stats);
    }

    // ==========================================
    // PPPoE Profiles
    // ==========================================

    /**
     * GET /api/pppoe/profiles
     */
    public function listProfiles(): void
    {
        $filters = [
            'zone_id' => get('zone_id'),
            'is_active' => get('is_active'),
        ];

        $profiles = $this->db->getAllPPPoEProfiles($filters, $this->getAdminId());
        jsonSuccess($profiles);
    }

    /**
     * GET /api/pppoe/profiles/{id}
     */
    public function showProfile(array $params): void
    {
        $id = (int)$params['id'];
        $profile = $this->db->getPPPoEProfileById($id);

        if (!$profile) {
            jsonError(__('api.pppoe_profile_not_found'), 404);
        }
        $this->verifyOwnership($profile, 'PPPoE profile');

        jsonSuccess($profile);
    }

    /**
     * POST /api/pppoe/profiles
     */
    public function createProfile(): void
    {
        $data = getJsonBody();

        if (empty($data['name'])) {
            jsonError(__('api.pppoe_profile_name_required'), 400);
        }

        // Multi-tenant: associate with current admin
        $data['admin_id'] = $this->getAdminId();

        try {
            $id = $this->db->createPPPoEProfile($data);
            $profile = $this->db->getPPPoEProfileById($id);
            jsonSuccess($profile, __('api.pppoe_profile_created'));
        } catch (Exception $e) {
            jsonError(__('api.pppoe_profile_create_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/pppoe/profiles/{id}
     */
    public function updateProfile(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $profile = $this->db->getPPPoEProfileById($id);
        if (!$profile) {
            jsonError(__('api.pppoe_profile_not_found'), 404);
        }
        $this->verifyOwnership($profile, 'PPPoE profile');

        if (empty($data['name'])) {
            jsonError(__('api.pppoe_profile_name_required'), 400);
        }

        try {
            $this->db->updatePPPoEProfile($id, $data);
            $updatedProfile = $this->db->getPPPoEProfileById($id);
            jsonSuccess($updatedProfile, __('api.pppoe_profile_updated'));
        } catch (Exception $e) {
            jsonError(__('api.pppoe_profile_update_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/pppoe/profiles/{id}
     */
    public function deleteProfile(array $params): void
    {
        $id = (int)$params['id'];

        $profile = $this->db->getPPPoEProfileById($id);
        if (!$profile) {
            jsonError(__('api.pppoe_profile_not_found'), 404);
        }
        $this->verifyOwnership($profile, 'PPPoE profile');

        try {
            $this->db->deletePPPoEProfile($id);
            jsonSuccess(null, __('api.pppoe_profile_deleted'));
        } catch (Exception $e) {
            jsonError(__('api.pppoe_profile_delete_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    // ==========================================
    // PPPoE Statistics
    // ==========================================

    /**
     * GET /api/pppoe/stats
     */
    public function stats(): void
    {
        $stats = $this->db->getPPPoEStats($this->getAdminId());
        jsonSuccess($stats);
    }

    /**
     * POST /api/pppoe/users/batch
     * Créer plusieurs utilisateurs PPPoE en une fois
     */
    public function createBatch(): void
    {
        $data = getJsonBody();

        if (empty($data['profile_id'])) {
            jsonError(__('api.pppoe_profile_required'), 400);
        }

        $count = (int)($data['count'] ?? 1);
        if ($count < 1 || $count > 100) {
            jsonError(__('api.pppoe_batch_count_invalid'), 400);
        }

        $profile = $this->db->getPPPoEProfileById((int)$data['profile_id']);
        if (!$profile) {
            jsonError(__('api.profile_not_found'), 400);
        }

        $prefix = strtoupper($data['prefix'] ?? 'PPP');
        $passwordLength = (int)($data['password_length'] ?? 8);
        $createdUsers = [];
        $attempts = 0;
        $maxAttempts = $count * 3;

        while (count($createdUsers) < $count && $attempts < $maxAttempts) {
            $attempts++;

            // Générer username unique
            $username = $prefix . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

            // Vérifier unicité
            if ($this->db->getPPPoEUserByUsername($username) || $this->db->getVoucherByUsername($username)) {
                continue;
            }

            // Générer mot de passe
            $password = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, $passwordLength);

            try {
                $userData = [
                    'username' => $username,
                    'password' => $password,
                    'profile_id' => $data['profile_id'],
                    'zone_id' => $data['zone_id'] ?? null,
                    'customer_name' => $data['customer_name'] ?? null,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'sale_amount' => $data['sale_amount'] ?? $profile['price'],
                    'sold_by' => $_SESSION['user_id'] ?? null,
                    'admin_id' => $this->getAdminId(),
                ];

                $id = $this->db->createPPPoEUser($userData);
                $createdUsers[] = [
                    'id' => $id,
                    'username' => $username,
                    'password' => $password,
                    'profile_name' => $profile['name']
                ];
            } catch (Exception $e) {
                // Ignorer les erreurs individuelles
                continue;
            }
        }

        if (empty($createdUsers)) {
            jsonError(__('api.pppoe_batch_create_failed'), 500);
        }

        jsonSuccess([
            'created' => count($createdUsers),
            'users' => $createdUsers
        ], count($createdUsers) . ' ' . __('api.pppoe_users_created'));
    }

    // ==========================================
    // Sessions Management
    // ==========================================

    /**
     * GET /api/pppoe/sessions
     * Liste toutes les sessions PPPoE avec filtres
     */
    public function listSessions(): void
    {
        $page = max(1, (int)(get('page') ?: 1));
        $perPage = min(100, max(10, (int)(get('per_page') ?: 20)));
        $status = get('status'); // 'active', 'closed', 'all'
        $search = get('search');
        $userId = get('user_id');
        $adminId = $this->getAdminId();

        $pdo = $this->db->getPdo();

        // Construire la requête
        $where = [];
        $params = [];

        // Multi-tenant: filtrer par admin_id
        if ($adminId !== null) {
            $where[] = "u.admin_id = ?";
            $params[] = $adminId;
        }

        if ($status === 'active') {
            $where[] = "s.stop_time IS NULL";
        } elseif ($status === 'closed') {
            $where[] = "s.stop_time IS NOT NULL";
        }

        if ($userId) {
            $where[] = "s.pppoe_user_id = ?";
            $params[] = (int)$userId;
        }

        if ($search) {
            $where[] = "(u.username LIKE ? OR u.customer_name LIKE ? OR s.client_ip LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Compter le total
        $countSql = "SELECT COUNT(*) FROM pppoe_sessions s
                     JOIN pppoe_users u ON s.pppoe_user_id = u.id
                     $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Récupérer les sessions
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT s.*,
                       u.username, u.customer_name, u.profile_id,
                       p.name as profile_name,
                       TIMESTAMPDIFF(SECOND, s.start_time, COALESCE(s.stop_time, NOW())) as duration_seconds
                FROM pppoe_sessions s
                JOIN pppoe_users u ON s.pppoe_user_id = u.id
                LEFT JOIN pppoe_profiles p ON u.profile_id = p.id
                $whereClause
                ORDER BY s.start_time DESC
                LIMIT ? OFFSET ?";

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll();

        // Formater les données
        foreach ($sessions as &$session) {
            $session['is_active'] = $session['stop_time'] === null;
            $session['duration_formatted'] = $this->formatDuration($session['duration_seconds']);
            $session['data_in_formatted'] = $this->formatBytes($session['input_octets'] ?? 0);
            $session['data_out_formatted'] = $this->formatBytes($session['output_octets'] ?? 0);
            $session['data_total_formatted'] = $this->formatBytes(($session['input_octets'] ?? 0) + ($session['output_octets'] ?? 0));
        }

        jsonSuccess([
            'data' => $sessions,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ]);
    }

    /**
     * GET /api/pppoe/sessions/active
     * Liste des sessions actives uniquement
     */
    public function activeSessions(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $sql = "SELECT s.*,
                       u.username, u.customer_name, u.profile_id,
                       p.name as profile_name,
                       TIMESTAMPDIFF(SECOND, s.start_time, NOW()) as duration_seconds
                FROM pppoe_sessions s
                JOIN pppoe_users u ON s.pppoe_user_id = u.id
                LEFT JOIN pppoe_profiles p ON u.profile_id = p.id
                WHERE s.stop_time IS NULL";

        $params = [];
        if ($adminId !== null) {
            $sql .= " AND u.admin_id = ?";
            $params[] = $adminId;
        }

        $sql .= " ORDER BY s.start_time DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll();

        // Formater les données
        foreach ($sessions as &$session) {
            $session['is_active'] = true;
            $session['duration_formatted'] = $this->formatDuration($session['duration_seconds']);
            $session['data_in_formatted'] = $this->formatBytes($session['input_octets'] ?? 0);
            $session['data_out_formatted'] = $this->formatBytes($session['output_octets'] ?? 0);
            $session['data_total_formatted'] = $this->formatBytes(($session['input_octets'] ?? 0) + ($session['output_octets'] ?? 0));
        }

        jsonSuccess([
            'data' => $sessions,
            'total' => count($sessions)
        ]);
    }

    /**
     * GET /api/pppoe/sessions/stats
     * Statistiques des sessions
     */
    public function sessionStats(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        // Multi-tenant: joindre avec pppoe_users pour filtrer par admin
        $adminJoin = "";
        $adminWhere = "";
        $adminParams = [];
        if ($adminId !== null) {
            $adminJoin = " JOIN pppoe_users u ON s.pppoe_user_id = u.id";
            $adminWhere = " AND u.admin_id = ?";
            $adminParams = [$adminId];
        }

        // Sessions actives
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pppoe_sessions s{$adminJoin} WHERE s.stop_time IS NULL{$adminWhere}");
        $stmt->execute($adminParams);
        $activeSessions = (int)$stmt->fetchColumn();

        // Sessions orphelines potentielles (pas de mise à jour depuis 10 min)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM pppoe_sessions s{$adminJoin}
            WHERE s.stop_time IS NULL{$adminWhere}
            AND (
                (s.last_update IS NOT NULL AND s.last_update < DATE_SUB(NOW(), INTERVAL 10 MINUTE))
                OR (s.last_update IS NULL AND s.start_time < DATE_SUB(NOW(), INTERVAL 10 MINUTE))
            )
        ");
        $stmt->execute($adminParams);
        $orphanedSessions = (int)$stmt->fetchColumn();

        // Sessions aujourd'hui
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM pppoe_sessions s{$adminJoin}
            WHERE DATE(s.start_time) = CURDATE(){$adminWhere}
        ");
        $stmt->execute($adminParams);
        $todaySessions = (int)$stmt->fetchColumn();

        // Trafic total aujourd'hui
        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(s.input_octets), 0) as total_in,
                COALESCE(SUM(s.output_octets), 0) as total_out
            FROM pppoe_sessions s{$adminJoin}
            WHERE DATE(s.start_time) = CURDATE(){$adminWhere}
        ");
        $stmt->execute($adminParams);
        $traffic = $stmt->fetch();

        jsonSuccess([
            'active_sessions' => $activeSessions,
            'orphaned_sessions' => $orphanedSessions,
            'today_sessions' => $todaySessions,
            'today_traffic_in' => $this->formatBytes($traffic['total_in']),
            'today_traffic_out' => $this->formatBytes($traffic['total_out']),
            'today_traffic_total' => $this->formatBytes($traffic['total_in'] + $traffic['total_out'])
        ]);
    }

    /**
     * DELETE /api/pppoe/sessions/{id}
     * Terminer manuellement une session (déconnexion MikroTik + marquée comme terminée dans la DB)
     */
    public function terminateSession(array $params): void
    {
        $sessionId = (int)$params['id'];
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        // Vérifier que la session existe et est active, avec les infos utilisateur (scoped par admin)
        $sql = "
            SELECT s.*, u.id as user_id, u.username, u.zone_id
            FROM pppoe_sessions s
            JOIN pppoe_users u ON s.pppoe_user_id = u.id
            WHERE s.id = ?";
        $params = [$sessionId];
        if ($adminId !== null) {
            $sql .= " AND u.admin_id = ?";
            $params[] = $adminId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $session = $stmt->fetch();

        if (!$session) {
            jsonError(__('api.pppoe_session_not_found'), 404);
        }

        if ($session['stop_time'] !== null) {
            jsonError(__('api.pppoe_session_already_terminated'), 400);
        }

        // Utiliser la même méthode que suspendUser pour déconnecter
        $user = [
            'id' => $session['user_id'],
            'username' => $session['username'],
            'zone_id' => $session['zone_id']
        ];
        $disconnectSent = $this->disconnectPPPoEUserFromRouter($user);

        // Marquer la session comme terminée
        $stmt = $pdo->prepare("
            UPDATE pppoe_sessions
            SET stop_time = NOW(), terminate_cause = 'Admin-Reset'
            WHERE id = ?
        ");
        $stmt->execute([$sessionId]);

        $message = __('api.pppoe_session_terminated');
        if ($disconnectSent) {
            $message .= ' - ' . __('api.pppoe_disconnect_sent');
        }

        jsonSuccess(['disconnect_sent' => $disconnectSent], $message);
    }

    /**
     * POST /api/pppoe/sessions/cleanup
     * Nettoyer les sessions orphelines
     */
    public function cleanupSessions(): void
    {
        $pdo = $this->db->getPdo();
        $data = getJsonBody();
        $adminId = $this->getAdminId();

        // Durée en minutes (par défaut 10)
        $minutes = (int)($data['minutes'] ?? 10);
        if ($minutes < 5) {
            $minutes = 5;
        }

        // Nettoyer les sessions orphelines (scoped par admin)
        $sql = "
            UPDATE pppoe_sessions s
            JOIN pppoe_users u ON s.pppoe_user_id = u.id
            SET s.stop_time = NOW(), s.terminate_cause = 'Orphaned-Session'
            WHERE s.stop_time IS NULL
            AND (
                (s.last_update IS NOT NULL AND s.last_update < DATE_SUB(NOW(), INTERVAL ? MINUTE))
                OR (s.last_update IS NULL AND s.start_time < DATE_SUB(NOW(), INTERVAL ? MINUTE))
            )";
        $execParams = [$minutes, $minutes];
        if ($adminId !== null) {
            $sql .= " AND u.admin_id = ?";
            $execParams[] = $adminId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($execParams);
        $cleaned = $stmt->rowCount();

        jsonSuccess([
            'cleaned' => $cleaned
        ], $cleaned . ' ' . __('api.pppoe_sessions_cleaned'));
    }

    /**
     * POST /api/pppoe/sessions/terminate-all
     * Terminer toutes les sessions actives
     */
    public function terminateAllSessions(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        // Scoped par admin: ne terminer que les sessions des utilisateurs de cet admin
        $sql = "
            UPDATE pppoe_sessions s
            JOIN pppoe_users u ON s.pppoe_user_id = u.id
            SET s.stop_time = NOW(), s.terminate_cause = 'Admin-Reset-All'
            WHERE s.stop_time IS NULL";
        $execParams = [];
        if ($adminId !== null) {
            $sql .= " AND u.admin_id = ?";
            $execParams[] = $adminId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($execParams);
        $terminated = $stmt->rowCount();

        jsonSuccess([
            'terminated' => $terminated
        ], $terminated . ' ' . __('api.pppoe_sessions_terminated'));
    }

    /**
     * POST /api/pppoe/users/{id}/disconnect
     * Déconnecter un utilisateur PPPoE (déconnexion MikroTik + terminer ses sessions actives)
     */
    public function disconnectUser(array $params): void
    {
        $userId = (int)$params['id'];
        $pdo = $this->db->getPdo();

        // Vérifier que l'utilisateur existe
        $user = $this->db->getPPPoEUserById($userId);
        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        // Envoyer la commande de déconnexion au MikroTik
        $disconnectSent = $this->disconnectPPPoEUserFromRouter($user);

        // Terminer toutes les sessions actives de cet utilisateur
        $stmt = $pdo->prepare("
            UPDATE pppoe_sessions
            SET stop_time = NOW(), terminate_cause = 'Admin-Disconnect'
            WHERE pppoe_user_id = ? AND stop_time IS NULL
        ");
        $stmt->execute([$userId]);
        $terminated = $stmt->rowCount();

        $message = __('api.pppoe_user_disconnected') . " '{$user['username']}'";
        if ($terminated > 0) {
            $message .= " ($terminated session(s))";
        }
        if ($disconnectSent) {
            $message .= ' - ' . __('api.pppoe_disconnect_sent');
        }

        jsonSuccess([
            'terminated' => $terminated,
            'disconnect_sent' => $disconnectSent
        ], $message);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Créer une facture initiale pour un nouveau client PPPoE
     */
    private function createInitialInvoice(int $userId, array $profile): void
    {
        try {
            $pdo = $this->db->getPdo();
            $user = $this->db->getPPPoEUserById($userId);

            if (!$user || !$profile) {
                return;
            }

            // Générer le numéro de facture
            $stmt = $pdo->query("SELECT COUNT(*) + 1 as num FROM pppoe_invoices WHERE YEAR(created_at) = YEAR(NOW())");
            $row = $stmt->fetch();
            $invoiceNumber = 'FAC-' . date('Y') . '-' . str_pad($row['num'], 5, '0', STR_PAD_LEFT);

            // Calculer les dates
            $validityDays = $profile['validity_days'] ?? 30;
            $periodStart = date('Y-m-d');
            $periodEnd = date('Y-m-d', strtotime('+' . $validityDays . ' days'));
            $dueDate = date('Y-m-d', strtotime('+7 days')); // Échéance dans 7 jours

            // Récupérer le prix du profil
            $price = $profile['price'] ?? 0;

            // Récupérer l'admin_id courant
            $adminId = $this->auth->getAdminId();

            // Créer la facture
            $stmt = $pdo->prepare("
                INSERT INTO pppoe_invoices (
                    pppoe_user_id, invoice_number, period_start, period_end, due_date,
                    amount, tax_rate, tax_amount, total_amount,
                    paid_amount, status, description, admin_id, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, 0, 0, ?,
                    0, 'pending', ?, ?, NOW()
                )
            ");
            $stmt->execute([
                $userId,
                $invoiceNumber,
                $periodStart,
                $periodEnd,
                $dueDate,
                $price,
                $price,
                'Abonnement initial - ' . $profile['name'],
                $adminId
            ]);
            $invoiceId = $pdo->lastInsertId();

            // Ajouter la ligne de facture
            $stmt = $pdo->prepare("
                INSERT INTO pppoe_invoice_items (
                    invoice_id, description, quantity, unit_price, total_price, item_type
                ) VALUES (?, ?, 1, ?, ?, 'subscription')
            ");
            $stmt->execute([
                $invoiceId,
                'Abonnement ' . $profile['name'] . ' (' . $validityDays . ' jours)',
                $price,
                $price
            ]);
        } catch (Exception $e) {
            // Log l'erreur mais ne pas bloquer la création du client
            error_log('Erreur création facture initiale PPPoE: ' . $e->getMessage());
        }
    }

    /**
     * Formater une durée en secondes
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $m = floor($seconds / 60);
            $s = $seconds % 60;
            return "{$m}m {$s}s";
        } elseif ($seconds < 86400) {
            $h = floor($seconds / 3600);
            $m = floor(($seconds % 3600) / 60);
            return "{$h}h {$m}m";
        } else {
            $d = floor($seconds / 86400);
            $h = floor(($seconds % 86400) / 3600);
            return "{$d}j {$h}h";
        }
    }

    /**
     * Formater des bytes
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // ==========================================
    // FUP (Fair Usage Policy) Endpoints
    // ==========================================

    /**
     * GET /api/pppoe/users/{id}/fup
     * Obtenir le statut FUP d'un utilisateur
     */
    public function getUserFupStatus(array $params): void
    {
        $id = (int)$params['id'];

        $user = $this->db->getPPPoEUserById($id);
        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        $status = $this->db->getPPPoEUserFupStatus($id);

        if (!$status) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }

        // Ajouter les champs formatés pour l'affichage
        $status['normal_speed'] = $this->formatSpeed($status['normal_download_speed'] ?? 0) . ' / ' .
                                  $this->formatSpeed($status['normal_upload_speed'] ?? 0);
        $status['fup_speed'] = $this->formatSpeed($status['fup_download_speed'] ?? 0) . ' / ' .
                               $this->formatSpeed($status['fup_upload_speed'] ?? 0);

        // Vitesse effective (FUP ou normale selon l'état)
        if ($status['fup_triggered'] && !$status['fup_override']) {
            $status['effective_speed'] = $status['fup_speed'];
        } else {
            $status['effective_speed'] = $status['normal_speed'];
        }

        // Calculer la date du prochain reset
        $status['fup_next_reset'] = $this->calculateNextFupReset($status);

        jsonSuccess($status);
    }

    /**
     * GET /api/pppoe/users/{id}/fup/node
     * Récupérer le statut FUP en temps réel depuis le nœud RADIUS
     */
    public function getUserFupNodeStatus(array $params): void
    {
        $id = (int)$params['id'];

        $user = $this->db->getPPPoEUserById($id);
        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        if (!$this->pushService) {
            jsonError('NodePushService non disponible', 500);
        }

        $result = $this->pushService->queryNodeFupStatus($id, $user['zone_id'] ?? null);

        if ($result['error']) {
            jsonError($result['error'], 503);
        }

        $nodeData = $result['data'];
        if (!$nodeData) {
            jsonError('Aucune donnée retournée par le nœud', 503);
        }

        // Formater pour l'affichage
        $nodeData['source'] = 'node';
        $nodeData['normal_speed'] = $this->formatSpeed((int)($nodeData['normal_download_speed'] ?? 0)) . ' / ' .
                                    $this->formatSpeed((int)($nodeData['normal_upload_speed'] ?? 0));
        $nodeData['fup_speed'] = $this->formatSpeed((int)($nodeData['fup_download_speed'] ?? 0)) . ' / ' .
                                 $this->formatSpeed((int)($nodeData['fup_upload_speed'] ?? 0));

        if ($nodeData['fup_triggered'] && !$nodeData['fup_override']) {
            $nodeData['effective_speed'] = $nodeData['fup_speed'];
        } else {
            $nodeData['effective_speed'] = $nodeData['normal_speed'];
        }

        jsonSuccess($nodeData);
    }

    /**
     * Formater une vitesse en bps vers Mbps/Kbps
     */
    private function formatSpeed(int $bps): string
    {
        if ($bps >= 1000000) {
            return round($bps / 1000000, 1) . ' Mbps';
        } elseif ($bps >= 1000) {
            return round($bps / 1000, 0) . ' Kbps';
        }
        return $bps . ' bps';
    }

    /**
     * Calculer la date du prochain reset FUP
     */
    private function calculateNextFupReset(array $status): ?string
    {
        $resetType = $status['fup_reset_type'] ?? 'monthly';
        $resetDay = $status['fup_reset_day'] ?? 1;

        if ($resetType === 'monthly') {
            $now = new \DateTime();
            $currentDay = (int)$now->format('d');

            if ($currentDay >= $resetDay) {
                // Prochain mois
                $now->modify('first day of next month');
            }
            $now->setDate((int)$now->format('Y'), (int)$now->format('m'), min($resetDay, (int)$now->format('t')));
            return $now->format('Y-m-d');
        }

        return null;
    }

    /**
     * POST /api/pppoe/users/{id}/fup/reset
     * Réinitialiser le FUP d'un utilisateur
     */
    public function resetUserFup(array $params): void
    {
        $id = (int)$params['id'];
        $user = $this->db->getPPPoEUserById($id);

        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        try {
            $this->db->resetFup($id, 'admin');

            // Push instantané du reset FUP vers le nœud (sans attendre le pull sync 60s)
            $status = $this->db->getPPPoEUserFupStatus($id);
            if ($this->pushService && $status) {
                try {
                    $this->pushService->notifyFupReset($user, $status);
                } catch (\Throwable $e) {
                    // Non-bloquant : le pull sync rattrapera
                    error_log("FUP reset push failed: " . $e->getMessage());
                }
            }

            // Déconnecter l'utilisateur sur le routeur
            $this->disconnectPPPoEUserFromRouter($user);

            jsonSuccess($status, __('api.pppoe_fup_reset_success'));
        } catch (\Throwable $e) {
            jsonError(__('api.pppoe_fup_reset_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/pppoe/users/{id}/fup/toggle-override
     * Activer/désactiver l'override FUP
     */
    public function toggleUserFupOverride(array $params): void
    {
        $id = (int)$params['id'];
        $user = $this->db->getPPPoEUserById($id);

        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        try {
            $this->db->toggleFupOverride($id);
            $status = $this->db->getPPPoEUserFupStatus($id);
            jsonSuccess($status, __('api.pppoe_fup_override_toggled'));
        } catch (Exception $e) {
            jsonError(__('api.pppoe_fup_toggle_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pppoe/fup/triggered
     * Obtenir la liste des utilisateurs avec FUP déclenché
     */
    public function getFupTriggeredUsers(): void
    {
        try {
            $users = $this->db->getFupTriggeredUsers($this->getAdminId());
            jsonSuccess($users);
        } catch (Exception $e) {
            jsonError(__('api.pppoe_fup_triggered_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/pppoe/users/{id}/fup/trigger
     * Déclencher manuellement le FUP pour un utilisateur
     */
    public function triggerUserFup(array $params): void
    {
        $id = (int)$params['id'];
        $user = $this->db->getPPPoEUserById($id);

        if (!$user) {
            jsonError(__('api.pppoe_user_not_found'), 404);
        }
        $this->verifyOwnership($user, 'PPPoE user');

        try {
            $status = $this->db->getPPPoEUserFupStatus($id);

            if (!$status || !$status['fup_enabled']) {
                jsonError(__('api.pppoe_fup_not_enabled'), 400);
            }

            if ($status['fup_triggered']) {
                jsonError(__('api.pppoe_fup_already_triggered'), 400);
            }

            $result = $this->db->triggerFup($id, $status);

            if ($result) {
                $newStatus = $this->db->getPPPoEUserFupStatus($id);
                jsonSuccess($newStatus, __('api.pppoe_fup_triggered_success'));
            } else {
                jsonError(__('api.pppoe_fup_trigger_failed'), 500);
            }
        } catch (Exception $e) {
            jsonError(__('api.pppoe_fup_trigger_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pppoe/fup/warnings
     * Obtenir les utilisateurs proches du quota FUP
     */
    public function getFupWarnings(): void
    {
        $threshold = (int)(get('threshold') ?: 80);
        $threshold = max(50, min(99, $threshold));

        try {
            $users = $this->db->getFupWarningUsers($threshold, $this->getAdminId());
            jsonSuccess($users);
        } catch (Exception $e) {
            jsonError(__('api.pppoe_fup_warnings_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pppoe/users/{id}/fup/logs
     * Obtenir l'historique FUP d'un utilisateur
     */
    public function getUserFupLogs(array $params): void
    {
        $id = (int)$params['id'];
        $limit = (int)(get('limit') ?: 50);

        try {
            $logs = $this->db->getFupLogs($id, $limit);
            jsonSuccess($logs);
        } catch (Exception $e) {
            jsonError(__('api.pppoe_fup_logs_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/pppoe/fup/reset-monthly
     * Réinitialiser le FUP mensuel (normalement appelé par cron)
     */
    public function resetMonthlyFup(): void
    {
        try {
            $count = $this->db->resetMonthlyFup();
            jsonSuccess(['reset_count' => $count], __('api.pppoe_fup_monthly_reset') . ": {$count}");
        } catch (Exception $e) {
            jsonError(__('api.pppoe_fup_monthly_reset_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pppoe/pending-disconnects
     * Retourne la liste des utilisateurs à déconnecter (pour le script MikroTik)
     */
    public function getPendingDisconnects(): void
    {
        $users = $this->db->getPendingDisconnects();

        // Format simple pour le MikroTik
        $usernames = array_map(fn($u) => $u['username'], $users);

        jsonSuccess([
            'users' => $usernames,
            'count' => count($usernames)
        ]);
    }

    /**
     * POST /api/pppoe/confirm-disconnect/{username}
     * Confirme qu'un utilisateur a été déconnecté par le MikroTik
     */
    public function confirmDisconnect(array $params): void
    {
        $username = $params['username'] ?? '';

        if (empty($username)) {
            jsonError(__('api.pppoe_username_required'), 400);
        }

        $result = $this->db->confirmDisconnect($username);

        if ($result) {
            jsonSuccess(['username' => $username], __('api.pppoe_disconnect_confirmed'));
        } else {
            jsonError(__('api.pppoe_user_not_pending_disconnect'), 404);
        }
    }
}
