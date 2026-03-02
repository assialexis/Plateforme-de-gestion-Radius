<?php
/**
 * Controller API Crédits SMS (CSMS)
 * Conversion CRT → CSMS, solde, historique
 */

class SmsCreditController
{
    private RadiusDatabase $db;
    private AuthService $auth;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    private function getAdminId(): int
    {
        $this->auth->requireAuth();
        $user = $this->auth->getUser();
        if ($user->isSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Le SuperAdmin n\'utilise pas les crédits SMS']);
            exit;
        }
        $adminId = $this->auth->getAdminId();
        if (!$adminId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => __('auth.unauthorized') ?? 'Non autorisé']);
            exit;
        }
        return $adminId;
    }

    /**
     * GET /sms-credits/balance
     */
    public function getBalance(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT sms_credit_balance FROM users WHERE id = ?");
        $stmt->execute([$adminId]);
        $balance = (float)$stmt->fetchColumn();

        // Get conversion settings
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM global_settings WHERE setting_key IN ('sms_credit_cost_fcfa', 'credit_exchange_rate', 'sms_credit_enabled', 'credit_currency')");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $costPerSms = (float)($settings['sms_credit_cost_fcfa'] ?? 25);
        $exchangeRate = (float)($settings['credit_exchange_rate'] ?? 100);
        // 1 CRT = exchangeRate FCFA → 1 CRT buys (exchangeRate / costPerSms) CSMS
        $csmsPerCrt = $costPerSms > 0 ? $exchangeRate / $costPerSms : 0;

        echo json_encode([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'cost_per_sms_fcfa' => $costPerSms,
                'csms_per_crt' => $csmsPerCrt,
                'exchange_rate' => $exchangeRate,
                'currency' => $settings['credit_currency'] ?? 'XOF',
                'enabled' => ($settings['sms_credit_enabled'] ?? '1') === '1',
            ]
        ]);
    }

    /**
     * POST /sms-credits/convert
     * Body: { crt_amount: number }
     */
    public function convertCredits(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();
        $data = getJsonBody();

        $crtAmount = (float)($data['crt_amount'] ?? 0);
        if ($crtAmount <= 0) {
            jsonError(__('sms_credits.invalid_amount') ?? 'Montant CRT invalide', 400);
            return;
        }

        // Check CSMS system enabled
        $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'sms_credit_enabled'");
        $stmt->execute();
        if ($stmt->fetchColumn() !== '1') {
            jsonError(__('sms_credits.system_disabled') ?? 'Le système CSMS est désactivé', 403);
            return;
        }

        // Get conversion rate
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM global_settings WHERE setting_key IN ('sms_credit_cost_fcfa', 'credit_exchange_rate')");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        $costPerSms = (float)($settings['sms_credit_cost_fcfa'] ?? 25);
        $exchangeRate = (float)($settings['credit_exchange_rate'] ?? 100);

        if ($costPerSms <= 0) {
            jsonError('Configuration CSMS invalide', 500);
            return;
        }

        $csmsGained = (int)floor($crtAmount * $exchangeRate / $costPerSms);

        if ($csmsGained < 1) {
            jsonError(__('sms_credits.min_one_csms') ?? 'Le montant CRT est insuffisant pour obtenir au moins 1 CSMS', 400);
            return;
        }

        $pdo->beginTransaction();
        try {
            // Lock user row to prevent race conditions
            $stmt = $pdo->prepare("SELECT credit_balance, sms_credit_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$adminId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $crtBalance = (float)$user['credit_balance'];
            $csmsBalance = (float)$user['sms_credit_balance'];

            if ($crtBalance < $crtAmount) {
                $pdo->rollBack();
                jsonError((__('sms_credits.insufficient_crt') ?? 'Solde CRT insuffisant') . '. ' . (__('sms_credits.current_balance') ?? 'Solde actuel') . ': ' . $crtBalance, 400);
                return;
            }

            $newCrtBalance = $crtBalance - $crtAmount;
            $newCsmsBalance = $csmsBalance + $csmsGained;

            // Update both balances
            $stmt = $pdo->prepare("UPDATE users SET credit_balance = ?, sms_credit_balance = ? WHERE id = ?");
            $stmt->execute([$newCrtBalance, $newCsmsBalance, $adminId]);

            // Log CRT deduction in credit_transactions
            $stmt = $pdo->prepare(
                "INSERT INTO credit_transactions (admin_id, type, amount, balance_after, reference_type, description)
                 VALUES (?, 'adjustment', ?, ?, 'sms_credit_conversion', ?)"
            );
            $stmt->execute([
                $adminId,
                -$crtAmount,
                $newCrtBalance,
                "Conversion de {$crtAmount} CRT en {$csmsGained} CSMS"
            ]);

            // Log CSMS addition in sms_credit_transactions
            $stmt = $pdo->prepare(
                "INSERT INTO sms_credit_transactions (admin_id, type, amount, balance_after, reference_type, description)
                 VALUES (?, 'conversion', ?, ?, 'credit_conversion', ?)"
            );
            $stmt->execute([
                $adminId,
                $csmsGained,
                $newCsmsBalance,
                "Conversion de {$crtAmount} CRT en {$csmsGained} CSMS"
            ]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => "{$csmsGained} CSMS " . (__('sms_credits.added_to_balance') ?? 'ajoutés à votre solde'),
                'data' => [
                    'crt_deducted' => $crtAmount,
                    'csms_gained' => $csmsGained,
                    'new_crt_balance' => $newCrtBalance,
                    'new_csms_balance' => $newCsmsBalance,
                ]
            ]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            jsonError('Erreur lors de la conversion: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /sms-credits/transactions
     */
    public function getTransactions(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(10, (int)($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;
        $type = $_GET['type'] ?? null;

        $where = "WHERE admin_id = ?";
        $params = [$adminId];

        if ($type && in_array($type, ['conversion', 'sms_sent', 'adjustment', 'refund'])) {
            $where .= " AND type = ?";
            $params[] = $type;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_credit_transactions $where");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT id, type, amount, balance_after, reference_type, reference_id, description, created_at
             FROM sms_credit_transactions $where
             ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $params[] = $perPage;
        $params[] = $offset;
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
            ]
        ]);
    }
}
