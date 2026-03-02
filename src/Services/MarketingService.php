<?php

class MarketingService
{
    private PDO $pdo;
    private SmsService $smsService;
    private WhatsAppNotifier $whatsAppNotifier;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->smsService = new SmsService($pdo);
        $this->whatsAppNotifier = new WhatsAppNotifier($pdo);
    }

    /**
     * Get filtered client list from the specified source
     */
    public function getClients(int $adminId, string $source, array $filters, int $page = 1, int $perPage = 50): array
    {
        $params = [$adminId];
        $where = [];

        switch ($source) {
            case 'hotspot':
                $select = "SELECT DISTINCT pt.customer_phone AS phone, pt.customer_name AS name, pt.paid_at AS date, pt.amount, pt.gateway_code AS payment_method";
                $from = " FROM payment_transactions pt";
                $where[] = "pt.admin_id = ?";
                $where[] = "pt.status = 'completed'";
                $where[] = "pt.customer_phone IS NOT NULL AND pt.customer_phone != ''";

                if (!empty($filters['date_from'])) {
                    $where[] = "pt.paid_at >= ?";
                    $params[] = $filters['date_from'] . ' 00:00:00';
                }
                if (!empty($filters['date_to'])) {
                    $where[] = "pt.paid_at <= ?";
                    $params[] = $filters['date_to'] . ' 23:59:59';
                }
                if (!empty($filters['profile_id'])) {
                    $where[] = "pt.profile_id = ?";
                    $params[] = $filters['profile_id'];
                }
                if (!empty($filters['payment_method'])) {
                    $where[] = "pt.gateway_code = ?";
                    $params[] = $filters['payment_method'];
                }
                if (!empty($filters['amount_min'])) {
                    $where[] = "pt.amount >= ?";
                    $params[] = (float) $filters['amount_min'];
                }
                if (!empty($filters['amount_max'])) {
                    $where[] = "pt.amount <= ?";
                    $params[] = (float) $filters['amount_max'];
                }

                $orderBy = " ORDER BY pt.paid_at DESC";
                break;

            case 'otp':
                $select = "SELECT DISTINCT ov.phone, '' AS name, ov.verified_at AS date";
                $from = " FROM otp_verifications ov";
                $where[] = "ov.admin_id = ?";
                $where[] = "ov.status = 'verified'";
                $where[] = "ov.phone IS NOT NULL AND ov.phone != ''";

                if (!empty($filters['date_from'])) {
                    $where[] = "ov.verified_at >= ?";
                    $params[] = $filters['date_from'] . ' 00:00:00';
                }
                if (!empty($filters['date_to'])) {
                    $where[] = "ov.verified_at <= ?";
                    $params[] = $filters['date_to'] . ' 23:59:59';
                }

                $orderBy = " ORDER BY ov.verified_at DESC";
                break;

            case 'pppoe':
                $select = "SELECT pu.customer_phone AS phone, pu.whatsapp_phone, pu.customer_name AS name, pu.created_at AS date, pu.status, pu.valid_until";
                $from = " FROM pppoe_users pu";
                $where[] = "pu.admin_id = ?";
                $where[] = "pu.customer_phone IS NOT NULL AND pu.customer_phone != ''";

                if (!empty($filters['date_from'])) {
                    $where[] = "pu.created_at >= ?";
                    $params[] = $filters['date_from'] . ' 00:00:00';
                }
                if (!empty($filters['date_to'])) {
                    $where[] = "pu.created_at <= ?";
                    $params[] = $filters['date_to'] . ' 23:59:59';
                }
                if (!empty($filters['status'])) {
                    $where[] = "pu.status = ?";
                    $params[] = $filters['status'];
                }
                if (!empty($filters['profile_id'])) {
                    $where[] = "pu.profile_id = ?";
                    $params[] = $filters['profile_id'];
                }
                if (!empty($filters['expiry_from'])) {
                    $where[] = "pu.valid_until >= ?";
                    $params[] = $filters['expiry_from'] . ' 00:00:00';
                }
                if (!empty($filters['expiry_to'])) {
                    $where[] = "pu.valid_until <= ?";
                    $params[] = $filters['expiry_to'] . ' 23:59:59';
                }

                $orderBy = " ORDER BY pu.created_at DESC";
                break;

            default:
                return ['clients' => [], 'pagination' => ['page' => 1, 'per_page' => $perPage, 'total' => 0, 'pages' => 0]];
        }

        $whereClause = " WHERE " . implode(" AND ", $where);

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM (SELECT DISTINCT " . ($source === 'pppoe' ? 'pu.customer_phone' : ($source === 'hotspot' ? 'pt.customer_phone' : 'ov.phone')) . $from . $whereClause . ") AS sub";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch paginated
        $offset = ($page - 1) * $perPage;
        $sql = $select . $from . $whereClause . $orderBy . " LIMIT " . (int) $perPage . " OFFSET " . (int) $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'clients' => $clients,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ];
    }

    /**
     * Send a marketing campaign
     */
    public function sendCampaign(int $adminId, array $data): array
    {
        $channel = $data['channel'];
        $gatewayId = !empty($data['gateway_id']) ? (int) $data['gateway_id'] : null;
        $message = $data['message'];
        $source = $data['source'];
        $filters = $data['filters'] ?? [];
        $clientPhones = $data['client_phones'] ?? [];

        try {
        // Create campaign record
        $stmt = $this->pdo->prepare(
            "INSERT INTO marketing_campaigns (admin_id, channel, sms_gateway_id, client_source, filters, message_template, status, started_at)
             VALUES (?, ?, ?, ?, ?, ?, 'sending', NOW())"
        );
        $stmt->execute([
            $adminId,
            $channel,
            $gatewayId,
            $source,
            json_encode($filters),
            $message,
        ]);
        $campaignId = (int) $this->pdo->lastInsertId();

        // Get clients to send to
        if (!empty($clientPhones)) {
            // Fetch client data for the selected phones
            $clients = $this->getClientsByPhones($adminId, $source, $clientPhones, $filters);
        } else {
            // Get all matching clients
            $result = $this->getClients($adminId, $source, $filters, 1, 10000);
            $clients = $result['clients'];
        }

        $totalRecipients = count($clients);
        $sentCount = 0;
        $failedCount = 0;

        // Update total recipients
        $this->pdo->prepare("UPDATE marketing_campaigns SET total_recipients = ? WHERE id = ?")->execute([$totalRecipients, $campaignId]);

        // Send messages
        $insertMsg = $this->pdo->prepare(
            "INSERT INTO marketing_campaign_messages (campaign_id, phone, client_name, message, status, error_message, sent_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($clients as $client) {
            $phone = $client['phone'];
            // For PPPoE + WhatsApp, prefer whatsapp_phone
            if ($source === 'pppoe' && $channel === 'whatsapp' && !empty($client['whatsapp_phone'])) {
                $phone = $client['whatsapp_phone'];
            }

            $personalizedMessage = $this->processMessage($message, $client);

            // Send
            if ($channel === 'sms') {
                $result = $this->smsService->sendSms($gatewayId, $phone, $personalizedMessage, 'mkt_' . $campaignId);
            } else {
                $result = $this->whatsAppNotifier->sendMessage($phone, $personalizedMessage);
            }

            if ($result['success'] ?? false) {
                $sentCount++;
                $insertMsg->execute([$campaignId, $phone, $client['name'] ?? null, $personalizedMessage, 'sent', null, date('Y-m-d H:i:s')]);
            } else {
                $failedCount++;
                $errorMsg = $result['error'] ?? 'Unknown error';
                $insertMsg->execute([$campaignId, $phone, $client['name'] ?? null, $personalizedMessage, 'failed', $errorMsg, null]);
            }

            // Rate limiting — longer delay for WhatsApp to avoid bans
            $delayMs = (int) ($data['delay_ms'] ?? ($channel === 'whatsapp' ? 5000 : 200));
            usleep($delayMs * 1000);
        }

        // Update campaign
        $this->pdo->prepare(
            "UPDATE marketing_campaigns SET sent_count = ?, failed_count = ?, status = 'completed', completed_at = NOW() WHERE id = ?"
        )->execute([$sentCount, $failedCount, $campaignId]);

        return [
            'success' => true,
            'message' => "Campagne terminée: {$sentCount} envoyé(s), {$failedCount} échoué(s)",
            'data' => [
                'campaign_id' => $campaignId,
                'total' => $totalRecipients,
                'sent' => $sentCount,
                'failed' => $failedCount,
            ],
        ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get clients by specific phone numbers
     */
    private function getClientsByPhones(int $adminId, string $source, array $phones, array $filters): array
    {
        if (empty($phones)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($phones), '?'));
        $params = [$adminId];

        switch ($source) {
            case 'hotspot':
                $sql = "SELECT DISTINCT customer_phone AS phone, customer_name AS name, paid_at AS date
                        FROM payment_transactions
                        WHERE admin_id = ? AND status = 'completed' AND customer_phone IN ({$placeholders})";
                break;
            case 'otp':
                $sql = "SELECT DISTINCT phone, '' AS name, verified_at AS date
                        FROM otp_verifications
                        WHERE admin_id = ? AND status = 'verified' AND phone IN ({$placeholders})";
                break;
            case 'pppoe':
                $sql = "SELECT customer_phone AS phone, whatsapp_phone, customer_name AS name, created_at AS date, status
                        FROM pppoe_users
                        WHERE admin_id = ? AND customer_phone IN ({$placeholders})";
                break;
            default:
                return [];
        }

        $params = array_merge($params, $phones);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Replace template variables in message
     */
    private function processMessage(string $template, array $client): string
    {
        return str_replace(
            ['{{name}}', '{{phone}}'],
            [$client['name'] ?? '', $client['phone'] ?? ''],
            $template
        );
    }

    /**
     * Get campaign history
     */
    public function getCampaigns(int $adminId, int $page = 1, int $perPage = 20): array
    {
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM marketing_campaigns WHERE admin_id = ?");
        $countStmt->execute([$adminId]);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare(
            "SELECT * FROM marketing_campaigns WHERE admin_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->bindValue(1, $adminId, PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'campaigns' => $campaigns,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ];
    }

    /**
     * Get campaign details with messages
     */
    public function getCampaignDetails(int $adminId, int $campaignId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM marketing_campaigns WHERE id = ? AND admin_id = ?");
        $stmt->execute([$campaignId, $adminId]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$campaign) {
            return null;
        }

        $msgStmt = $this->pdo->prepare(
            "SELECT * FROM marketing_campaign_messages WHERE campaign_id = ? ORDER BY id"
        );
        $msgStmt->execute([$campaignId]);
        $campaign['messages'] = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

        return $campaign;
    }

    /**
     * Get profiles for filter dropdowns
     */
    public function getProfiles(int $adminId): array
    {
        $hotspot = [];
        $pppoe = [];

        try {
            $stmt = $this->pdo->prepare("SELECT id, name, price FROM profiles WHERE admin_id = ? ORDER BY name");
            $stmt->execute([$adminId]);
            $hotspot = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // profiles table might not exist
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id, name, price FROM pppoe_profiles WHERE admin_id = ? ORDER BY name");
            $stmt->execute([$adminId]);
            $pppoe = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // pppoe_profiles table might not exist
        }

        return [
            'hotspot' => $hotspot,
            'pppoe' => $pppoe,
        ];
    }

    /**
     * Get active SMS gateways
     */
    public function getGateways(int $adminId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, provider_code FROM sms_gateways WHERE admin_id = ? AND is_active = 1 ORDER BY name"
        );
        $stmt->execute([$adminId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
