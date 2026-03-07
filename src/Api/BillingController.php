<?php
/**
 * Controller API Facturation PPPoE
 */

class BillingController
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
    // Factures
    // ==========================================

    /**
     * GET /api/billing/invoices
     */
    public function listInvoices(): void
    {
        $filters = [
            'status' => get('status'),
            'user_id' => get('user_id'),
            'search' => get('search'),
            'date_from' => get('date_from'),
            'date_to' => get('date_to'),
        ];

        // Convertir le filtre période en date_from/date_to
        $period = get('period');
        if ($period && empty($filters['date_from'])) {
            $now = new DateTime();
            switch ($period) {
                case 'today':
                    $filters['date_from'] = $now->format('Y-m-d');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
                case 'week':
                    $filters['date_from'] = $now->modify('monday this week')->format('Y-m-d');
                    $filters['date_to'] = date('Y-m-d');
                    break;
                case 'month':
                    $filters['date_from'] = $now->format('Y-m-01');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
                case 'year':
                    $filters['date_from'] = $now->format('Y-01-01');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
            }
        }

        $page = max(1, (int)(get('page') ?: 1));
        $perPage = min(100, max(10, (int)(get('per_page') ?: 20)));

        $result = $this->db->getAllInvoices($filters, $page, $perPage, $this->getAdminId());

        // Reformater pour le frontend
        jsonSuccess([
            'invoices' => $result['data'],
            'pagination' => [
                'current_page' => $result['page'],
                'total_pages' => $result['total_pages'],
                'total' => $result['total'],
                'per_page' => $result['per_page']
            ]
        ]);
    }

    /**
     * GET /api/billing/invoices/{id}
     */
    public function showInvoice(array $params): void
    {
        $id = (int)$params['id'];
        $invoice = $this->db->getInvoiceById($id);

        if (!$invoice) {
            jsonError(__('api.billing_invoice_not_found'), 404);
        }

        // Ajouter les lignes et paiements
        $invoice['items'] = $this->db->getInvoiceItems($id);
        $invoice['payments'] = $this->db->getInvoicePayments($id);

        jsonSuccess($invoice);
    }

    /**
     * POST /api/billing/invoices
     */
    public function createInvoice(): void
    {
        $data = getJsonBody();

        if (empty($data['pppoe_user_id'])) {
            jsonError(__('api.billing_client_required'), 400);
        }

        // Vérifier que le client existe
        $user = $this->db->getPPPoEUserById((int)$data['pppoe_user_id']);
        if (!$user) {
            jsonError(__('api.billing_client_not_found'), 404);
        }

        // Multi-tenant: associate with current admin
        $data['admin_id'] = $this->getAdminId();

        try {
            $invoiceId = $this->db->createInvoice($data);
            $invoice = $this->db->getInvoiceById($invoiceId);

            // WhatsApp: notification de facture créée
            try {
                require_once __DIR__ . '/../Services/WhatsAppNotifier.php';
                $notifier = new \WhatsAppNotifier($this->db->getPdo());
                $notifier->triggerEvent('invoice_created', (int)$data['pppoe_user_id']);
            } catch (\Throwable $e) {
                error_log('WhatsApp invoice_created notification failed: ' . $e->getMessage());
            }

            jsonSuccess($invoice, __('api.billing_invoice_created'));
        } catch (Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * PUT /api/billing/invoices/{id}
     */
    public function updateInvoice(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $invoice = $this->db->getInvoiceById($id);
        if (!$invoice) {
            jsonError(__('api.billing_invoice_not_found'), 404);
        }

        // Ne pas modifier les factures payées
        if ($invoice['status'] === 'paid') {
            jsonError(__('api.billing_cannot_edit_paid'), 400);
        }

        try {
            $this->db->updateInvoice($id, $data);
            $invoice = $this->db->getInvoiceById($id);
            jsonSuccess($invoice, __('api.billing_invoice_updated'));
        } catch (Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * DELETE /api/billing/invoices/{id}
     */
    public function deleteInvoice(array $params): void
    {
        $id = (int)$params['id'];

        $invoice = $this->db->getInvoiceById($id);
        if (!$invoice) {
            jsonError(__('api.billing_invoice_not_found'), 404);
        }

        // Ne pas supprimer les factures payées
        if ($invoice['status'] === 'paid' || $invoice['paid_amount'] > 0) {
            jsonError(__('api.billing_cannot_delete_with_payments'), 400);
        }

        $this->db->deleteInvoice($id);
        jsonSuccess(null, __('api.billing_invoice_deleted'));
    }

    /**
     * POST /api/billing/invoices/{id}/cancel
     */
    public function cancelInvoice(array $params): void
    {
        $id = (int)$params['id'];

        $invoice = $this->db->getInvoiceById($id);
        if (!$invoice) {
            jsonError(__('api.billing_invoice_not_found'), 404);
        }

        $this->db->updateInvoice($id, ['status' => 'cancelled']);
        jsonSuccess(null, __('api.billing_invoice_cancelled'));
    }

    /**
     * POST /api/billing/invoices/generate
     * Générer une facture pour un client
     */
    public function generateInvoice(): void
    {
        $data = getJsonBody();

        if (empty($data['pppoe_user_id'])) {
            jsonError(__('api.billing_client_required'), 400);
        }

        $user = $this->db->getPPPoEUserById((int)$data['pppoe_user_id']);
        if (!$user) {
            jsonError(__('api.billing_client_not_found'), 404);
        }

        try {
            $invoiceId = $this->db->generateInvoiceForUser($user, $data);
            $invoice = $this->db->getInvoiceById($invoiceId);
            $invoice['items'] = $this->db->getInvoiceItems($invoiceId);
            jsonSuccess($invoice, __('api.billing_invoice_generated'));
        } catch (Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/billing/invoices/generate-batch
     * Générer des factures pour plusieurs clients
     */
    public function generateBatchInvoices(): void
    {
        $data = getJsonBody();
        $userIds = $data['user_ids'] ?? [];
        $options = $data['options'] ?? [];

        if (empty($userIds)) {
            jsonError(__('api.billing_no_clients_selected'), 400);
        }

        $created = 0;
        $errors = [];

        foreach ($userIds as $userId) {
            try {
                $user = $this->db->getPPPoEUserById((int)$userId);
                if ($user) {
                    $this->db->generateInvoiceForUser($user, $options);
                    $created++;
                }
            } catch (Exception $e) {
                $errors[] = "Client #{$userId}: " . $e->getMessage();
            }
        }

        jsonSuccess([
            'created' => $created,
            'errors' => $errors
        ], $created . ' ' . __('api.billing_invoices_generated'));
    }

    // ==========================================
    // Paiements
    // ==========================================

    /**
     * GET /api/billing/payments
     */
    public function listPayments(): void
    {
        $filters = [
            'user_id' => get('user_id'),
            'invoice_id' => get('invoice_id'),
            'method' => get('method'),
            'date_from' => get('date_from'),
            'date_to' => get('date_to'),
        ];

        $page = max(1, (int)(get('page') ?: 1));
        $perPage = min(100, max(10, (int)(get('per_page') ?: 20)));

        $result = $this->db->getAllPayments($filters, $page, $perPage, $this->getAdminId());

        // Reformater pour le frontend
        jsonSuccess([
            'payments' => $result['data'],
            'pagination' => [
                'current_page' => $result['page'],
                'total_pages' => $result['total_pages'],
                'total' => $result['total'],
                'per_page' => $result['per_page']
            ]
        ]);
    }

    /**
     * POST /api/billing/payments
     */
    public function createPayment(): void
    {
        $data = getJsonBody();

        if (empty($data['invoice_id'])) {
            jsonError(__('api.billing_invoice_required'), 400);
        }

        if (empty($data['amount']) || $data['amount'] <= 0) {
            jsonError(__('api.billing_invalid_amount'), 400);
        }

        if (empty($data['payment_method'])) {
            jsonError(__('api.billing_payment_method_required'), 400);
        }

        $invoice = $this->db->getInvoiceById((int)$data['invoice_id']);
        if (!$invoice) {
            jsonError(__('api.billing_invoice_not_found'), 404);
        }

        if ($invoice['status'] === 'cancelled') {
            jsonError(__('api.billing_cannot_pay_cancelled'), 400);
        }

        $remaining = $invoice['total_amount'] - $invoice['paid_amount'];
        if ($data['amount'] > $remaining) {
            jsonError(__('api.billing_amount_exceeds_remaining') . " ({$remaining})", 400);
        }

        try {
            $data['pppoe_user_id'] = $invoice['pppoe_user_id'];
            $data['received_by'] = $_SESSION['user_id'] ?? null;
            $data['admin_id'] = $this->getAdminId();

            $paymentId = $this->db->createPayment($data);
            $payment = $this->db->getPaymentById($paymentId);

            // Notification si facture entièrement payée
            $updatedInvoice = $this->db->getInvoiceById((int)$data['invoice_id']);
            if ($updatedInvoice && $updatedInvoice['status'] === 'paid') {
                try {
                    require_once __DIR__ . '/../Utils/pppoe-payment-helpers.php';
                    sendPaymentNotification($this->db->getPdo(), (int)$data['invoice_id'], $this->getAdminId());
                } catch (\Throwable $e) {
                    error_log('Payment notification failed: ' . $e->getMessage());
                }
            }

            jsonSuccess($payment, __('api.billing_payment_recorded'));
        } catch (Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * DELETE /api/billing/payments/{id}
     */
    public function deletePayment(array $params): void
    {
        $id = (int)$params['id'];

        $payment = $this->db->getPaymentById($id);
        if (!$payment) {
            jsonError(__('api.billing_payment_not_found'), 404);
        }

        $this->db->deletePayment($id);
        jsonSuccess(null, __('api.billing_payment_deleted'));
    }

    /**
     * POST /api/billing/invoices/{id}/pay
     * Payer une facture directement
     */
    public function payInvoice(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $invoice = $this->db->getInvoiceById($id);
        if (!$invoice) {
            jsonError(__('api.billing_invoice_not_found'), 404);
            return;
        }

        if ($invoice['status'] === 'cancelled') {
            jsonError(__('api.billing_cannot_pay_cancelled'), 400);
            return;
        }

        if ($invoice['status'] === 'paid') {
            jsonError(__('api.billing_invoice_already_paid'), 400);
            return;
        }

        $amount = (float)($data['amount'] ?? 0);
        if ($amount <= 0) {
            jsonError(__('api.billing_invalid_amount'), 400);
            return;
        }

        $remaining = $invoice['total_amount'] - $invoice['paid_amount'];
        if ($amount > $remaining) {
            $amount = $remaining; // Plafonner au montant restant
        }

        try {
            $paymentData = [
                'invoice_id' => $id,
                'pppoe_user_id' => $invoice['pppoe_user_id'],
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $_SESSION['user_id'] ?? null
            ];

            $paymentId = $this->db->createPayment($paymentData);
            $payment = $this->db->getPaymentById($paymentId);

            // Recharger la facture mise à jour
            $invoice = $this->db->getInvoiceById($id);

            // Notification si facture entièrement payée
            if ($invoice['status'] === 'paid') {
                try {
                    require_once __DIR__ . '/../Utils/pppoe-payment-helpers.php';
                    sendPaymentNotification($this->db->getPdo(), $id, $this->getAdminId());
                } catch (\Throwable $e) {
                    error_log('Payment notification failed: ' . $e->getMessage());
                }
            }

            jsonSuccess([
                'payment' => $payment,
                'invoice' => $invoice
            ], __('api.billing_payment_recorded'));
        } catch (Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    // ==========================================
    // Statistiques
    // ==========================================

    /**
     * GET /api/billing/stats
     */
    public function stats(): void
    {
        $stats = $this->db->getBillingStats($this->getAdminId());
        jsonSuccess($stats);
    }

    /**
     * GET /api/billing/user/{id}/summary
     */
    public function getUserBillingSummary(array $params): void
    {
        $userId = (int)$params['id'];

        $user = $this->db->getPPPoEUserById($userId);
        if (!$user) {
            jsonError(__('api.billing_client_not_found'), 404);
        }

        $summary = $this->db->getUserBillingSummary($userId);
        jsonSuccess($summary);
    }

    // ==========================================
    // Paramètres
    // ==========================================

    /**
     * GET /api/billing/settings
     */
    public function getSettings(): void
    {
        $settings = $this->db->getBillingSettings();
        jsonSuccess($settings);
    }

    /**
     * PUT /api/billing/settings
     */
    public function updateSettings(): void
    {
        $data = getJsonBody();

        if (empty($data)) {
            jsonError(__('api.no_data_received'), 400);
            return;
        }

        foreach ($data as $key => $value) {
            // Convertir en string (peut être null, int, etc.)
            $stringValue = $value === null ? '' : (string)$value;
            $this->db->updateBillingSetting($key, $stringValue);
        }

        $settings = $this->db->getBillingSettings();
        jsonSuccess($settings, __('api.billing_settings_updated'));
    }

    // ==========================================
    // Notification Logs
    // ==========================================

    /**
     * GET /api/billing/notification-logs
     */
    public function getNotificationLogs(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = "WHERE 1=1";
        $params = [];

        $adminId = $this->getAdminId();
        if ($adminId) {
            $where .= " AND n.admin_id = ?";
            $params[] = $adminId;
        }

        if (!empty($_GET['channel'])) {
            $where .= " AND n.channel = ?";
            $params[] = $_GET['channel'];
        }

        if (!empty($_GET['status'])) {
            $where .= " AND n.status = ?";
            $params[] = $_GET['status'];
        }

        if (!empty($_GET['date_from'])) {
            $where .= " AND DATE(n.created_at) >= ?";
            $params[] = $_GET['date_from'];
        }

        if (!empty($_GET['date_to'])) {
            $where .= " AND DATE(n.created_at) <= ?";
            $params[] = $_GET['date_to'];
        }

        // Total count
        $countStmt = $this->db->getPdo()->prepare("SELECT COUNT(*) FROM payment_notification_log n $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch logs
        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $this->db->getPdo()->prepare("
            SELECT n.*, i.invoice_number
            FROM payment_notification_log n
            LEFT JOIN pppoe_invoices i ON n.invoice_id = i.id
            $where
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        jsonSuccess([
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => max(1, ceil($total / $perPage)),
                'total' => $total,
                'per_page' => $perPage
            ]
        ]);
    }

    // ==========================================
    // Export
    // ==========================================

    /**
     * GET /api/billing/invoices/{id}/pdf
     */
    public function generateInvoicePdf(array $params): void
    {
        $id = (int)$params['id'];

        $invoice = $this->db->getInvoiceById($id);
        if (!$invoice) {
            jsonError(__('api.billing_invoice_not_found'), 404);
        }

        $invoice['items'] = $this->db->getInvoiceItems($id);
        $invoice['payments'] = $this->db->getInvoicePayments($id);
        $settings = $this->db->getBillingSettings();

        $html = $this->buildInvoiceHtml($invoice, $settings);

        // Générer le PDF avec Dompdf
        $fontDir = dirname(__DIR__, 2) . '/vendor/dompdf/dompdf/lib/fonts';
        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => true,
            'fontDir' => $fontDir,
            'fontCache' => $fontDir,
            'defaultFont' => 'Helvetica',
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Facture_' . $invoice['invoice_number'] . '.pdf"');
        echo $dompdf->output();
        exit;
    }

    /**
     * GET /api/billing/invoices/{id}/html
     */
    public function generateInvoiceHtml(array $params): void
    {
        $id = (int)$params['id'];

        $invoice = $this->db->getInvoiceById($id);
        if (!$invoice) {
            jsonError(__('api.billing_invoice_not_found'), 404);
        }

        $invoice['items'] = $this->db->getInvoiceItems($id);
        $invoice['payments'] = $this->db->getInvoicePayments($id);
        $settings = $this->db->getBillingSettings();

        // Générer le HTML de la facture
        $html = $this->buildInvoiceHtml($invoice, $settings);

        jsonSuccess(['html' => $html]);
    }

    /**
     * POST /api/billing/invoices/{id}/send
     */
    public function sendInvoice(array $params): void
    {
        $id = (int)$params['id'];

        $invoice = $this->db->getInvoiceById($id);
        if (!$invoice) {
            jsonError(__('api.billing_invoice_not_found'), 404);
        }

        // Marquer comme envoyée si c'est un brouillon
        if ($invoice['status'] === 'draft') {
            $this->db->updateInvoice($id, ['status' => 'pending']);
        }

        // TODO: Implémenter l'envoi par email/SMS

        jsonSuccess(null, __('api.billing_invoice_sent'));
    }

    /**
     * POST /api/billing/invoices/{id}/send-whatsapp
     */
    public function sendInvoiceWhatsApp(array $params): void
    {
        $id = (int)$params['id'];

        $invoice = $this->db->getInvoiceById($id);
        if (!$invoice) {
            jsonError(__('api.billing_invoice_not_found'), 404);
        }

        // Vérifier que WhatsApp est activé
        $pdo = $this->db->getPdo();
        $stmt = $pdo->query("SELECT is_enabled FROM whatsapp_config LIMIT 1");
        $configRow = $stmt->fetch();

        if (!$configRow || !$configRow['is_enabled']) {
            jsonError(__('api.billing_whatsapp_not_enabled'), 400);
        }

        $body = getJsonBody();
        $phone = $body['phone'] ?? null;
        $sendPdf = !empty($body['send_pdf']);

        require_once __DIR__ . '/../Services/WhatsAppNotifier.php';
        $notifier = new WhatsAppNotifier($pdo);

        // Envoyer le message texte
        $result = $notifier->sendInvoiceNotification($id, $phone);

        if (!$result['success']) {
            jsonError($result['error'] ?? __('api.billing_whatsapp_send_error'), 500);
        }

        $response = ['message_id' => $result['message_id'] ?? null];

        // Envoyer le PDF si demandé
        if ($sendPdf) {
            $targetPhone = $phone;
            if (!$targetPhone) {
                $invoiceData = $notifier->prepareInvoiceData($id);
                $targetPhone = $invoiceData['_phone'] ?? null;
            }

            if ($targetPhone) {
                $pdfResult = $notifier->sendInvoicePdf($id, $targetPhone);
                $response['pdf_sent'] = $pdfResult['success'];
                if (!$pdfResult['success']) {
                    $response['pdf_error'] = $pdfResult['error'] ?? 'Erreur PDF';
                } else {
                    $response['pdf_message_id'] = $pdfResult['message_id'] ?? null;
                }
            }
        }

        jsonSuccess($response, $sendPdf ? __('api.billing_invoice_pdf_sent_whatsapp') : __('api.billing_invoice_sent_whatsapp'));
    }

    /**
     * GET /api/billing/payments/{id}
     */
    public function showPayment(array $params): void
    {
        $id = (int)$params['id'];
        $payment = $this->db->getPaymentById($id);

        if (!$payment) {
            jsonError(__('api.billing_payment_not_found'), 404);
        }

        jsonSuccess($payment);
    }

    private function buildInvoiceHtml(array $invoice, array $settings): string
    {
        $statusLabels = [
            'draft' => 'Brouillon',
            'pending' => 'En attente',
            'paid' => 'Payée',
            'partial' => 'Partiel',
            'overdue' => 'En retard',
            'cancelled' => 'Annulée'
        ];

        $statusColors = [
            'draft' => '#6b7280',
            'pending' => '#f59e0b',
            'paid' => '#10b981',
            'partial' => '#3b82f6',
            'overdue' => '#ef4444',
            'cancelled' => '#6b7280'
        ];

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Facture ' . htmlspecialchars($invoice['invoice_number']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .company { font-size: 14px; }
        .company h1 { margin: 0; font-size: 24px; color: #2563eb; }
        .invoice-info { text-align: right; }
        .invoice-number { font-size: 20px; font-weight: bold; color: #2563eb; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 4px; color: white; font-weight: bold; background: ' . ($statusColors[$invoice['status']] ?? '#6b7280') . '; }
        .client-box { background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .client-box h3 { margin: 0 0 10px 0; color: #374151; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2563eb; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .totals { width: 300px; margin-left: auto; }
        .totals td { padding: 8px; }
        .totals .total-row { font-weight: bold; font-size: 14px; background: #f3f4f6; }
        .footer { margin-top: 40px; text-align: center; color: #6b7280; font-size: 11px; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">
            <h1>' . htmlspecialchars($settings['company_name'] ?? 'Mon Entreprise') . '</h1>
            <p>' . nl2br(htmlspecialchars($settings['company_address'] ?? '')) . '</p>
            <p>Tél: ' . htmlspecialchars($settings['company_phone'] ?? '') . '</p>
            <p>Email: ' . htmlspecialchars($settings['company_email'] ?? '') . '</p>
        </div>
        <div class="invoice-info">
            <div class="invoice-number">FACTURE</div>
            <div style="font-size: 18px; margin: 5px 0;">' . htmlspecialchars($invoice['invoice_number']) . '</div>
            <div class="status">' . ($statusLabels[$invoice['status']] ?? $invoice['status']) . '</div>
            <p style="margin-top: 15px;">
                <strong>Date:</strong> ' . date('d/m/Y', strtotime($invoice['created_at'])) . '<br>
                <strong>Échéance:</strong> ' . date('d/m/Y', strtotime($invoice['due_date'])) . '
            </p>
        </div>
    </div>

    <div class="client-box">
        <h3>Facturé à:</h3>
        <strong>' . htmlspecialchars($invoice['customer_name'] ?? $invoice['username']) . '</strong><br>
        ' . htmlspecialchars($invoice['customer_phone'] ?? '') . '<br>
        ' . htmlspecialchars($invoice['customer_email'] ?? '') . '<br>
        ' . nl2br(htmlspecialchars($invoice['customer_address'] ?? '')) . '
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="width: 80px; text-align: center;">Qté</th>
                <th style="width: 100px; text-align: right;">Prix unit.</th>
                <th style="width: 100px; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($invoice['items'] as $item) {
            $html .= '
            <tr>
                <td>' . htmlspecialchars($item['description']) . '</td>
                <td style="text-align: center;">' . number_format($item['quantity'], 0) . '</td>
                <td style="text-align: right;">' . number_format($item['unit_price'], 0, ',', ' ') . ' FCFA</td>
                <td style="text-align: right;">' . number_format($item['total_price'], 0, ',', ' ') . ' FCFA</td>
            </tr>';
        }

        $html .= '
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Sous-total:</td>
            <td style="text-align: right;">' . number_format($invoice['amount'], 0, ',', ' ') . ' FCFA</td>
        </tr>';

        if ($invoice['tax_amount'] > 0) {
            $html .= '
        <tr>
            <td>Taxe (' . $invoice['tax_rate'] . '%):</td>
            <td style="text-align: right;">' . number_format($invoice['tax_amount'], 0, ',', ' ') . ' FCFA</td>
        </tr>';
        }

        $html .= '
        <tr class="total-row">
            <td>Total:</td>
            <td style="text-align: right;">' . number_format($invoice['total_amount'], 0, ',', ' ') . ' FCFA</td>
        </tr>';

        if ($invoice['paid_amount'] > 0) {
            $html .= '
        <tr>
            <td>Payé:</td>
            <td style="text-align: right; color: #10b981;">-' . number_format($invoice['paid_amount'], 0, ',', ' ') . ' FCFA</td>
        </tr>
        <tr class="total-row">
            <td>Reste à payer:</td>
            <td style="text-align: right; color: #ef4444;">' . number_format($invoice['total_amount'] - $invoice['paid_amount'], 0, ',', ' ') . ' FCFA</td>
        </tr>';
        }

        $html .= '
    </table>

    <div class="footer">
        <p>' . htmlspecialchars($settings['invoice_footer'] ?? '') . '</p>
    </div>
</body>
</html>';

        return $html;
    }
}
