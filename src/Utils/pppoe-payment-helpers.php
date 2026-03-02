<?php
/**
 * Helper functions for PPPoE payment processing
 * These functions are used by both the callback handler and the admin API
 */

/**
 * Logger pour les callbacks PPPoE
 */
function logPPPoEPayment($type, $message, $data = []) {
    $logFile = __DIR__ . '/../../logs/pppoe-payment-callbacks.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logEntry = date('Y-m-d H:i:s') . " [$type] $message " . json_encode($data) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Solder une facture
 */
function settleInvoice(PDO $pdo, int $invoiceId, float $amount, string $reference): void
{
    // Récupérer la facture pour avoir l'ID utilisateur
    $stmt = $pdo->prepare("SELECT * FROM pppoe_invoices WHERE id = ?");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        throw new Exception('Invoice not found: ' . $invoiceId);
    }

    // Enregistrer le paiement
    $stmt = $pdo->prepare("
        INSERT INTO pppoe_payments (
            invoice_id, pppoe_user_id, amount, payment_method, payment_reference, payment_date, admin_id
        ) VALUES (?, ?, ?, 'online', ?, NOW(), ?)
    ");
    $stmt->execute([$invoiceId, $invoice['pppoe_user_id'], $amount, $reference, $invoice['admin_id'] ?? null]);

    // Calculer si la facture sera totalement payée
    $newPaidAmount = $invoice['paid_amount'] + $amount;
    $isFullyPaid = $newPaidAmount >= $invoice['total_amount'];

    // Mettre à jour la facture
    $stmt = $pdo->prepare("
        UPDATE pppoe_invoices
        SET paid_amount = paid_amount + ?,
            status = CASE
                WHEN paid_amount + ? >= total_amount THEN 'paid'
                ELSE 'partial'
            END
        WHERE id = ?
    ");
    $stmt->execute([$amount, $amount, $invoiceId]);

    // Si la facture est entièrement payée, activer le compte PPPoE s'il est désactivé
    if ($isFullyPaid) {
        // Vérifier si le user est disabled et récupérer validity_days
        $stmt = $pdo->prepare("
            SELECT pu.id, pu.status, pp.validity_days
            FROM pppoe_users pu
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            WHERE pu.id = ? AND pu.status = 'disabled'
        ");
        $stmt->execute([$invoice['pppoe_user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            $validityDays = $user['validity_days'] ?? 30;
            $newValidUntil = date('Y-m-d H:i:s', strtotime("+{$validityDays} days"));
            $stmt = $pdo->prepare("
                UPDATE pppoe_users SET status = 'active', valid_from = NOW(), valid_until = ? WHERE id = ?
            ");
            $stmt->execute([$newValidUntil, $user['id']]);
        }

        logPPPoEPayment('INFO', 'User activated after invoice payment', [
            'user_id' => $invoice['pppoe_user_id'],
            'invoice_id' => $invoiceId
        ]);

        // Appliquer le changement de plan si c'est une facture de type plan_change
        $metaStmt = $pdo->prepare("SELECT metadata FROM pppoe_invoices WHERE id = ?");
        $metaStmt->execute([$invoiceId]);
        $metaJson = $metaStmt->fetchColumn();
        if ($metaJson) {
            $meta = json_decode($metaJson, true);
            if (($meta['type'] ?? '') === 'plan_change' && !empty($meta['new_profile_id'])) {
                $profileStmt = $pdo->prepare("SELECT validity_days FROM pppoe_profiles WHERE id = ?");
                $profileStmt->execute([$meta['new_profile_id']]);
                $profileData = $profileStmt->fetch();
                if ($profileData) {
                    $validDays = $profileData['validity_days'] ?? 30;
                    $newValid = date('Y-m-d H:i:s', strtotime("+{$validDays} days"));
                    $pdo->prepare("UPDATE pppoe_users SET profile_id = ?, valid_from = NOW(), valid_until = ? WHERE id = ?")
                        ->execute([$meta['new_profile_id'], $newValid, $invoice['pppoe_user_id']]);
                    logPPPoEPayment('INFO', 'Plan changed after payment', [
                        'user_id' => $invoice['pppoe_user_id'],
                        'new_profile_id' => $meta['new_profile_id']
                    ]);
                }
            }
        }

        // Notification de paiement
        try {
            $adminStmt = $pdo->prepare("SELECT admin_id FROM pppoe_users WHERE id = ?");
            $adminStmt->execute([$invoice['pppoe_user_id']]);
            $adminId = (int)$adminStmt->fetchColumn();
            sendPaymentNotification($pdo, $invoiceId, $adminId);
        } catch (\Throwable $e) {
            logPPPoEPayment('ERROR', 'Payment notification failed', ['error' => $e->getMessage()]);
        }
    }
}

/**
 * Prolonger un abonnement
 */
function extendSubscription(RadiusDatabase $db, PDO $pdo, int $userId, array $profile, float $amount, string $reference): void
{
    $days = $profile['validity_days'] ?? 30;

    // Prolonger la validité
    $stmt = $pdo->prepare("
        UPDATE pppoe_users
        SET valid_until = DATE_ADD(COALESCE(valid_until, NOW()), INTERVAL ? DAY)
        WHERE id = ?
    ");
    $stmt->execute([$days, $userId]);

    // Créer une facture soldée pour la prolongation
    createPaidInvoice($pdo, $userId, $profile, $amount, $reference, 'Prolongation abonnement');
}

/**
 * Renouveler un abonnement (réactiver un compte expiré)
 */
function renewSubscription(RadiusDatabase $db, PDO $pdo, int $userId, array $profile, float $amount, string $reference): void
{
    $days = $profile['validity_days'] ?? 30;

    // Renouveler (commencer à partir d'aujourd'hui)
    $stmt = $pdo->prepare("
        UPDATE pppoe_users
        SET valid_until = DATE_ADD(NOW(), INTERVAL ? DAY),
            status = 'active'
        WHERE id = ?
    ");
    $stmt->execute([$days, $userId]);

    // Créer une facture soldée pour le renouvellement
    createPaidInvoice($pdo, $userId, $profile, $amount, $reference, 'Renouvellement abonnement');
}

/**
 * Créer une facture soldée
 */
function createPaidInvoice(PDO $pdo, int $userId, array $profile, float $amount, string $reference, string $description): void
{
    // Récupérer admin_id de l'utilisateur
    $adminStmt = $pdo->prepare("SELECT admin_id FROM pppoe_users WHERE id = ?");
    $adminStmt->execute([$userId]);
    $adminId = $adminStmt->fetchColumn() ?: null;

    // Générer le numéro de facture
    $stmt = $pdo->query("SELECT COUNT(*) + 1 as num FROM pppoe_invoices WHERE YEAR(created_at) = YEAR(NOW())");
    $row = $stmt->fetch();
    $invoiceNumber = 'FAC-' . date('Y') . '-' . str_pad($row['num'], 5, '0', STR_PAD_LEFT);

    // Calculer les dates
    $validityDays = $profile['validity_days'] ?? 30;
    $periodStart = date('Y-m-d');
    $periodEnd = date('Y-m-d', strtotime('+' . $validityDays . ' days'));

    // Créer la facture
    $stmt = $pdo->prepare("
        INSERT INTO pppoe_invoices (
            pppoe_user_id, invoice_number, period_start, period_end, due_date,
            amount, tax_rate, tax_amount, total_amount,
            paid_amount, status, paid_date, payment_method, payment_reference, description, created_at, admin_id
        ) VALUES (
            ?, ?, ?, ?, NOW(),
            ?, 0, 0, ?,
            ?, 'paid', NOW(), 'online', ?, ?, NOW(), ?
        )
    ");
    $stmt->execute([
        $userId,
        $invoiceNumber,
        $periodStart,
        $periodEnd,
        $amount,
        $amount,
        $amount,
        $reference,
        $description . ' - ' . $profile['name'],
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
        $description . ' - ' . $profile['name'] . ' (' . $validityDays . ' jours)',
        $amount,
        $amount
    ]);

    // Enregistrer le paiement
    $stmt = $pdo->prepare("
        INSERT INTO pppoe_payments (
            invoice_id, pppoe_user_id, amount, payment_method, payment_reference, payment_date, admin_id
        ) VALUES (?, ?, ?, 'online', ?, NOW(), ?)
    ");
    $stmt->execute([$invoiceId, $userId, $amount, $reference, $adminId]);

    // Notification de paiement
    try {
        $adminStmt = $pdo->prepare("SELECT admin_id FROM pppoe_users WHERE id = ?");
        $adminStmt->execute([$userId]);
        $adminId = (int)$adminStmt->fetchColumn();
        sendPaymentNotification($pdo, (int)$invoiceId, $adminId);
    } catch (\Throwable $e) {
        logPPPoEPayment('ERROR', 'Payment notification failed', ['error' => $e->getMessage()]);
    }
}

/**
 * Traiter un paiement PPPoE réussi
 */
function processSuccessfulPPPoEPayment(RadiusDatabase $db, PDO $pdo, array $transaction): void
{
    $paymentType = $transaction['payment_type'];
    $userId = $transaction['pppoe_user_id'];
    $invoiceId = $transaction['invoice_id'];
    $amount = $transaction['amount'];

    // Récupérer l'utilisateur PPPoE
    $user = $db->getPPPoEUserById($userId);
    if (!$user) {
        throw new Exception('User not found: ' . $userId);
    }

    // Récupérer le profil
    $profile = $db->getPPPoEProfileById($user['profile_id']);

    switch ($paymentType) {
        case 'invoice':
            // Payer la facture
            settleInvoice($pdo, $invoiceId, $amount, $transaction['transaction_id']);
            logPPPoEPayment('INFO', 'Invoice settled', ['invoice_id' => $invoiceId, 'amount' => $amount]);
            break;

        case 'extension':
            // Prolonger l'abonnement
            extendSubscription($db, $pdo, $userId, $profile, $amount, $transaction['transaction_id']);
            logPPPoEPayment('INFO', 'Subscription extended', ['user_id' => $userId]);
            break;

        case 'renewal':
            // Renouveler l'abonnement
            renewSubscription($db, $pdo, $userId, $profile, $amount, $transaction['transaction_id']);
            logPPPoEPayment('INFO', 'Subscription renewed', ['user_id' => $userId]);
            break;
    }
}

/**
 * Envoyer une notification de paiement au client (WhatsApp ou SMS)
 */
function sendPaymentNotification(PDO $pdo, int $invoiceId, int $adminId): void
{
    // Vérifier si les notifications sont activées
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM billing_settings WHERE setting_key IN ('payment_notif_enabled', 'payment_notif_channel', 'payment_notif_template', 'payment_notif_template_whatsapp', 'payment_notif_template_sms')");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    if (empty($settings['payment_notif_enabled']) || $settings['payment_notif_enabled'] !== '1') {
        return;
    }

    $channel = $settings['payment_notif_channel'] ?? 'whatsapp';
    // Template spécifique au canal, avec fallback vers l'ancien template unique
    $template = $settings['payment_notif_template_' . $channel]
        ?? $settings['payment_notif_template']
        ?? '';
    if (empty($template)) {
        return;
    }

    // Charger les données facture+client via WhatsAppNotifier (réutilise prepareInvoiceData)
    require_once __DIR__ . '/../Services/WhatsAppNotifier.php';
    $whatsapp = new WhatsAppNotifier($pdo);
    $data = $whatsapp->prepareInvoiceData($invoiceId);

    if (empty($data)) {
        logPPPoEPayment('WARNING', 'Payment notification: no data for invoice', ['invoice_id' => $invoiceId]);
        return;
    }

    $phone = $data['_phone'] ?? null;
    if (!$phone) {
        logPPPoEPayment('WARNING', 'Payment notification: no phone for invoice', ['invoice_id' => $invoiceId]);
        return;
    }

    // Substituer les variables dans le template
    $message = $whatsapp->processTemplate($template, $data);

    $sendResult = ['success' => false, 'error' => 'Unknown channel'];

    if ($channel === 'whatsapp') {
        if (!$whatsapp->isConfigured()) {
            logPPPoEPayment('WARNING', 'Payment notification: WhatsApp not configured');
            return;
        }
        $formattedPhone = $whatsapp->formatPhone($phone);
        $sendResult = $whatsapp->sendMessage($formattedPhone, $message);
    } elseif ($channel === 'sms') {
        require_once __DIR__ . '/../Services/SmsService.php';
        $smsService = new SmsService($pdo);
        // Trouver la première passerelle SMS active de l'admin
        $gwStmt = $pdo->prepare("SELECT id FROM sms_gateways WHERE admin_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1");
        $gwStmt->execute([$adminId]);
        $smsGatewayId = $gwStmt->fetchColumn();

        if ($smsGatewayId) {
            $sendResult = $smsService->sendSms((int)$smsGatewayId, $phone, $message);
        } else {
            $sendResult = ['success' => false, 'error' => 'Aucune passerelle SMS active'];
        }
    }

    // Log dans la base de données
    $status = $sendResult['success'] ? 'sent' : 'failed';
    $errorMsg = $sendResult['success'] ? null : ($sendResult['error'] ?? 'Erreur inconnue');
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO payment_notification_log
            (admin_id, invoice_id, pppoe_user_id, customer_name, phone, channel, message, status, error_message)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $adminId, $invoiceId, $data['_user_id'] ?? 0,
            $data['customer_name'] ?? '', $phone, $channel,
            $message, $status, $errorMsg
        ]);
    } catch (\Throwable $e) {
        // Ignore log errors
    }

    logPPPoEPayment($status === 'sent' ? 'INFO' : 'ERROR', 'Payment notification ' . ($status === 'sent' ? 'sent' : 'failed'), [
        'invoice_id' => $invoiceId,
        'channel' => $channel,
        'phone' => $phone,
        'error' => $errorMsg
    ]);
}
