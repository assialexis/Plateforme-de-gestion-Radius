<?php
/**
 * Controller API pour les rapports de ventes
 */

class SalesController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private PDO $pdo;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->pdo = $db->getPdo();
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * GET /sales - Liste des ventes avec filtres
     */
    public function index(): void
    {
        $filters = [
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'seller_id' => $_GET['seller_id'] ?? null,
            'gerant_id' => $_GET['gerant_id'] ?? null,
            'zone_id' => $_GET['zone_id'] ?? null,
            'nas_id' => $_GET['nas_id'] ?? null,
            'profile_id' => $_GET['profile_id'] ?? null,
            'payment_method' => $_GET['payment_method'] ?? null,
            'seller_role' => $_GET['seller_role'] ?? null,
        ];

        $limit = min((int)($_GET['limit'] ?? 50), 500);
        $offset = (int)($_GET['offset'] ?? 0);

        $sql = "
            SELECT
                v.id,
                v.username as ticket_code,
                v.sold_by,
                v.sold_at,
                v.sold_on_nas_id,
                v.payment_method,
                v.sale_amount,
                v.commission_vendeur,
                v.commission_gerant,
                v.commission_admin,
                v.commission_paid,
                v.data_used,
                v.time_used,
                v.status,
                u_seller.username as seller_username,
                u_seller.full_name as seller_name,
                u_seller.role as seller_role,
                u_seller.parent_id as seller_parent_id,
                u_parent.username as parent_username,
                u_parent.full_name as parent_name,
                u_parent.role as parent_role,
                n.shortname as nas_name,
                n.router_id,
                z.id as zone_id,
                z.name as zone_name,
                p.id as profile_id,
                p.name as profile_name,
                p.price as profile_price
            FROM vouchers v
            LEFT JOIN users u_seller ON v.sold_by = u_seller.id
            LEFT JOIN users u_parent ON u_seller.parent_id = u_parent.id
            LEFT JOIN nas n ON v.sold_on_nas_id = n.id
            LEFT JOIN zones z ON n.zone_id = z.id
            LEFT JOIN profiles p ON v.profile_id = p.id
            WHERE v.sold_at IS NOT NULL
        ";
        $params = [];

        // Multi-tenant filtering
        $adminId = $this->getAdminId();
        if ($adminId !== null) {
            $sql .= " AND v.admin_id = ?";
            $params[] = $adminId;
        }

        // Filtres
        if ($filters['date_from']) {
            $sql .= " AND DATE(v.sold_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if ($filters['date_to']) {
            $sql .= " AND DATE(v.sold_at) <= ?";
            $params[] = $filters['date_to'];
        }
        if ($filters['gerant_id']) {
            $sql .= " AND (v.gerant_id = ? OR v.sold_by IN (SELECT id FROM users WHERE parent_id = ?))";
            $params[] = $filters['gerant_id'];
            $params[] = $filters['gerant_id'];
        }
        if ($filters['seller_id']) {
            $sql .= " AND (v.sold_by = ? OR v.gerant_id = ? OR v.sold_by IN (SELECT id FROM users WHERE parent_id = ?))";
            $params[] = $filters['seller_id'];
            $params[] = $filters['seller_id'];
            $params[] = $filters['seller_id'];
        }
        if ($filters['zone_id']) {
            $sql .= " AND z.id = ?";
            $params[] = $filters['zone_id'];
        }
        if ($filters['nas_id']) {
            $sql .= " AND v.sold_on_nas_id = ?";
            $params[] = $filters['nas_id'];
        }
        if ($filters['profile_id']) {
            $sql .= " AND v.profile_id = ?";
            $params[] = $filters['profile_id'];
        }
        if ($filters['payment_method']) {
            $sql .= " AND v.payment_method = ?";
            $params[] = $filters['payment_method'];
        }
        if ($filters['seller_role']) {
            $sql .= " AND u_seller.role = ?";
            $params[] = $filters['seller_role'];
        }

        // LIMIT et OFFSET doivent être injectés directement (valeurs entières castées)
        $sql .= " ORDER BY v.sold_at DESC LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compter le total
        $countSql = "SELECT COUNT(*) FROM vouchers v
                     LEFT JOIN users u_seller ON v.sold_by = u_seller.id
                     LEFT JOIN nas n ON v.sold_on_nas_id = n.id
                     LEFT JOIN zones z ON n.zone_id = z.id
                     WHERE v.sold_at IS NOT NULL";
        $countParams = [];

        if ($adminId !== null) {
            $countSql .= " AND v.admin_id = ?";
            $countParams[] = $adminId;
        }

        if ($filters['date_from']) {
            $countSql .= " AND DATE(v.sold_at) >= ?";
            $countParams[] = $filters['date_from'];
        }
        if ($filters['date_to']) {
            $countSql .= " AND DATE(v.sold_at) <= ?";
            $countParams[] = $filters['date_to'];
        }
        if ($filters['seller_id']) {
            $countSql .= " AND (v.sold_by = ? OR v.gerant_id = ? OR v.sold_by IN (SELECT id FROM users WHERE parent_id = ?))";
            $countParams[] = $filters['seller_id'];
            $countParams[] = $filters['seller_id'];
            $countParams[] = $filters['seller_id'];
        }
        if ($filters['zone_id']) {
            $countSql .= " AND z.id = ?";
            $countParams[] = $filters['zone_id'];
        }
        if ($filters['nas_id']) {
            $countSql .= " AND v.sold_on_nas_id = ?";
            $countParams[] = $filters['nas_id'];
        }
        if ($filters['profile_id']) {
            $countSql .= " AND v.profile_id = ?";
            $countParams[] = $filters['profile_id'];
        }
        if ($filters['payment_method']) {
            $countSql .= " AND v.payment_method = ?";
            $countParams[] = $filters['payment_method'];
        }
        if ($filters['seller_role']) {
            $countSql .= " AND u_seller.role = ?";
            $countParams[] = $filters['seller_role'];
        }

        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($countParams);
        $total = $stmt->fetchColumn();

        jsonSuccess([
            'sales' => $sales,
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * GET /sales/stats - Statistiques globales des ventes (vouchers + paiements en ligne)
     */
    public function stats(): void
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $adminId = $this->getAdminId();
        $adminFilter = $adminId !== null ? " AND admin_id = ?" : "";
        $baseParams = [$dateFrom, $dateTo];
        if ($adminId !== null) {
            $baseParams[] = $adminId;
        }
        // Params pour payment_transactions (utilise paid_at)
        $ptBaseParams = [$dateFrom, $dateTo];
        if ($adminId !== null) {
            $ptBaseParams[] = $adminId;
        }

        // --- Stats globales vouchers ---
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) as total_sales,
                COALESCE(SUM(sale_amount), 0) as total_revenue,
                COALESCE(SUM(commission_vendeur), 0) as total_commission_vendeur,
                COALESCE(SUM(commission_gerant), 0) as total_commission_gerant,
                COALESCE(SUM(commission_admin), 0) as total_commission_admin
            FROM vouchers
            WHERE sold_at IS NOT NULL
            AND DATE(sold_at) BETWEEN ? AND ?
            $adminFilter
        ");
        $stmt->execute($baseParams);
        $global = $stmt->fetch(PDO::FETCH_ASSOC);

        // --- Stats globales payment_transactions (completed, voucher_purchase only) ---
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) as total_sales,
                COALESCE(SUM(amount), 0) as total_revenue
            FROM payment_transactions
            WHERE status = 'completed'
            AND transaction_type = 'voucher_purchase'
            AND DATE(paid_at) BETWEEN ? AND ?
            $adminFilter
        ");
        $stmt->execute($ptBaseParams);
        $ptGlobal = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fusionner les stats globales
        $global['total_sales'] = (int)$global['total_sales'] + (int)$ptGlobal['total_sales'];
        $global['total_revenue'] = (float)$global['total_revenue'] + (float)$ptGlobal['total_revenue'];

        // --- Stats par méthode de paiement (vouchers) ---
        $stmt = $this->pdo->prepare("
            SELECT
                payment_method,
                COUNT(*) as count,
                COALESCE(SUM(sale_amount), 0) as total
            FROM vouchers
            WHERE sold_at IS NOT NULL
            AND DATE(sold_at) BETWEEN ? AND ?
            $adminFilter
            GROUP BY payment_method
        ");
        $stmt->execute($baseParams);
        $byPaymentMethod = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- Stats par passerelle (payment_transactions completed, voucher_purchase only) ---
        $stmt = $this->pdo->prepare("
            SELECT
                gateway_code as payment_method,
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total
            FROM payment_transactions
            WHERE status = 'completed'
            AND transaction_type = 'voucher_purchase'
            AND DATE(paid_at) BETWEEN ? AND ?
            $adminFilter
            GROUP BY gateway_code
        ");
        $stmt->execute($ptBaseParams);
        $ptByGateway = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fusionner les méthodes de paiement
        $byPaymentMethod = array_merge($byPaymentMethod, $ptByGateway);

        // --- Stats par jour (vouchers + payment_transactions) ---
        $stmt = $this->pdo->prepare("
            SELECT date, SUM(count) as count, SUM(total) as total FROM (
                SELECT DATE(sold_at) as date, COUNT(*) as count, COALESCE(SUM(sale_amount), 0) as total
                FROM vouchers
                WHERE sold_at IS NOT NULL AND DATE(sold_at) BETWEEN ? AND ? $adminFilter
                GROUP BY DATE(sold_at)
                UNION ALL
                SELECT DATE(paid_at) as date, COUNT(*) as count, COALESCE(SUM(amount), 0) as total
                FROM payment_transactions
                WHERE status = 'completed' AND transaction_type = 'voucher_purchase' AND DATE(paid_at) BETWEEN ? AND ? $adminFilter
                GROUP BY DATE(paid_at)
            ) combined
            GROUP BY date
            ORDER BY date DESC
            LIMIT 30
        ");
        $dayParams = array_merge($baseParams, $ptBaseParams);
        $stmt->execute($dayParams);
        $byDay = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- Stats par mois (vouchers + payment_transactions) ---
        $stmt = $this->pdo->prepare("
            SELECT month, SUM(count) as count, SUM(total) as total FROM (
                SELECT DATE_FORMAT(sold_at, '%Y-%m') as month, COUNT(*) as count, COALESCE(SUM(sale_amount), 0) as total
                FROM vouchers
                WHERE sold_at IS NOT NULL AND DATE(sold_at) BETWEEN ? AND ? $adminFilter
                GROUP BY DATE_FORMAT(sold_at, '%Y-%m')
                UNION ALL
                SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, COUNT(*) as count, COALESCE(SUM(amount), 0) as total
                FROM payment_transactions
                WHERE status = 'completed' AND transaction_type = 'voucher_purchase' AND DATE(paid_at) BETWEEN ? AND ? $adminFilter
                GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
            ) combined
            GROUP BY month
            ORDER BY month DESC
            LIMIT 12
        ");
        $monthParams = array_merge($baseParams, $ptBaseParams);
        $stmt->execute($monthParams);
        $byMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- Résumé global (Aujourd'hui, Ce mois-ci, Cette année) ---
        $paramSummary = [];
        if ($adminId !== null) {
            $paramSummary[] = $adminId;
        }
        $paramSummary2 = $paramSummary; // same for payment_transactions

        $stmt = $this->pdo->prepare("
            SELECT
                SUM(CASE WHEN DATE(sold_at) = CURDATE() THEN 1 ELSE 0 END) as tickets_today,
                COALESCE(SUM(CASE WHEN DATE(sold_at) = CURDATE() THEN sale_amount ELSE 0 END), 0) as revenue_today,
                SUM(CASE WHEN YEAR(sold_at) = YEAR(CURDATE()) AND MONTH(sold_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as tickets_month,
                COALESCE(SUM(CASE WHEN YEAR(sold_at) = YEAR(CURDATE()) AND MONTH(sold_at) = MONTH(CURDATE()) THEN sale_amount ELSE 0 END), 0) as revenue_month,
                SUM(CASE WHEN YEAR(sold_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as tickets_year,
                COALESCE(SUM(CASE WHEN YEAR(sold_at) = YEAR(CURDATE()) THEN sale_amount ELSE 0 END), 0) as revenue_year
            FROM vouchers
            WHERE sold_at IS NOT NULL
            $adminFilter
        ");
        $stmt->execute($paramSummary);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Ajouter les paiements en ligne (achats de tickets uniquement) au résumé
        $stmt = $this->pdo->prepare("
            SELECT
                SUM(CASE WHEN DATE(paid_at) = CURDATE() THEN 1 ELSE 0 END) as tickets_today,
                COALESCE(SUM(CASE WHEN DATE(paid_at) = CURDATE() THEN amount ELSE 0 END), 0) as revenue_today,
                SUM(CASE WHEN YEAR(paid_at) = YEAR(CURDATE()) AND MONTH(paid_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as tickets_month,
                COALESCE(SUM(CASE WHEN YEAR(paid_at) = YEAR(CURDATE()) AND MONTH(paid_at) = MONTH(CURDATE()) THEN amount ELSE 0 END), 0) as revenue_month,
                SUM(CASE WHEN YEAR(paid_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as tickets_year,
                COALESCE(SUM(CASE WHEN YEAR(paid_at) = YEAR(CURDATE()) THEN amount ELSE 0 END), 0) as revenue_year
            FROM payment_transactions
            WHERE status = 'completed'
            AND transaction_type = 'voucher_purchase'
            $adminFilter
        ");
        $stmt->execute($paramSummary2);
        $ptSummary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fusionner le résumé
        foreach (['tickets_today', 'revenue_today', 'tickets_month', 'revenue_month', 'tickets_year', 'revenue_year'] as $key) {
            $summary[$key] = (float)($summary[$key] ?? 0) + (float)($ptSummary[$key] ?? 0);
        }

        jsonSuccess([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'global' => $global,
            'by_payment_method' => $byPaymentMethod,
            'by_day' => array_reverse($byDay),
            'by_month' => array_reverse($byMonth),
            'summary' => $summary
        ]);
    }

    /**
     * GET /sales/by-seller - Ventes par vendeur/gérant
     */
    public function bySeller(): void
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $role = $_GET['role'] ?? null;
        $adminId = $this->getAdminId();

        $adminFilter = $adminId !== null ? " AND v.admin_id = ?" : "";

        $sql = "
            SELECT
                u.id as user_id,
                u.username,
                u.full_name,
                u.role,
                u.parent_id,
                up.username as parent_username,
                up.full_name as parent_name,
                COUNT(v.id) as sales_count,
                COALESCE(SUM(v.sale_amount), 0) as total_sales,
                COALESCE(SUM(v.commission_vendeur), 0) as commission_earned,
                COALESCE(SUM(CASE WHEN v.commission_paid = 0 THEN v.commission_vendeur ELSE 0 END), 0) as commission_pending
            FROM users u
            LEFT JOIN vouchers v ON v.sold_by = u.id
                AND v.sold_at IS NOT NULL
                AND DATE(v.sold_at) BETWEEN ? AND ?
                $adminFilter
            LEFT JOIN users up ON u.parent_id = up.id
            WHERE u.role = 'vendeur'
        ";
        $params = [$dateFrom, $dateTo];
        if ($adminId !== null) {
            $params[] = $adminId; // Pour left join vouchers
            $sql .= " AND (u.id = ? OR u.parent_id = ? OR u.parent_id IN (SELECT id FROM users WHERE parent_id = ?))";
            $params[] = $adminId;
            $params[] = $adminId;
            $params[] = $adminId;
        }

        if ($role) {
            $sql .= " AND u.role = ?";
            $params[] = $role;
        }

        $sql .= " GROUP BY u.id ORDER BY total_sales DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'sellers' => $sellers
        ]);
    }

    /**
     * GET /sales/by-gerant - Ventes par gérant
     */
    public function byGerant(): void
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $adminId = $this->getAdminId();

        $adminFilter = $adminId !== null ? " AND v.admin_id = ?" : "";

        $sql = "
            SELECT
                u.id as user_id,
                u.username,
                u.full_name,
                u.role,
                u.parent_id,
                up.username as parent_username,
                up.full_name as parent_name,
                COUNT(v.id) as sales_count,
                COALESCE(SUM(v.sale_amount), 0) as total_sales,
                COALESCE(SUM(v.commission_gerant), 0) as commission_earned,
                COALESCE(SUM(CASE WHEN v.commission_paid = 0 THEN v.commission_gerant ELSE 0 END), 0) as commission_pending
            FROM users u
            LEFT JOIN vouchers v ON (v.gerant_id = u.id OR (v.gerant_id IS NULL AND v.sold_by IN (SELECT id FROM users WHERE parent_id = u.id)))
                AND v.sold_at IS NOT NULL
                AND DATE(v.sold_at) BETWEEN ? AND ?
                $adminFilter
            LEFT JOIN users up ON u.parent_id = up.id
            WHERE u.role = 'gerant'
        ";
        $params = [$dateFrom, $dateTo];
        if ($adminId !== null) {
            $params[] = $adminId; // Pour left join vouchers
            $sql .= " AND (u.id = ? OR u.parent_id = ? OR u.parent_id IN (SELECT id FROM users WHERE parent_id = ?))";
            $params[] = $adminId;
            $params[] = $adminId;
            $params[] = $adminId;
        }

        $sql .= " GROUP BY u.id ORDER BY total_sales DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $gerants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'gerants' => $gerants
        ]);
    }

    /**
     * GET /sales/by-zone - Ventes par zone
     */
    public function byZone(): void
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $adminId = $this->getAdminId();
        $adminFilter = $adminId !== null ? " AND v.admin_id = ?" : "";
        $zoneAdminFilter = $adminId !== null ? " AND z.admin_id = ?" : "";

        $sql = "
            SELECT
                z.id as zone_id,
                z.name as zone_name,
                z.code as zone_code,
                COUNT(v.id) as sales_count,
                COALESCE(SUM(v.sale_amount), 0) as total_sales,
                COUNT(DISTINCT v.sold_by) as sellers_count,
                COUNT(DISTINCT v.sold_on_nas_id) as nas_count
            FROM zones z
            LEFT JOIN nas n ON n.zone_id = z.id
            LEFT JOIN vouchers v ON v.sold_on_nas_id = n.id
                AND v.sold_at IS NOT NULL
                AND DATE(v.sold_at) BETWEEN ? AND ?
                $adminFilter
            WHERE z.is_active = 1
            $zoneAdminFilter
            GROUP BY z.id
            ORDER BY total_sales DESC
        ";
        $params = [$dateFrom, $dateTo];
        if ($adminId !== null) {
            $params[] = $adminId;
            $params[] = $adminId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'zones' => $zones
        ]);
    }

    /**
     * GET /sales/by-nas - Ventes par routeur/NAS
     */
    public function byNas(): void
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $zoneId = $_GET['zone_id'] ?? null;
        $paymentMethod = $_GET['payment_method'] ?? null;
        $adminId = $this->getAdminId();

        $sql = "
            SELECT
                n.id as nas_id,
                n.shortname as nas_name,
                n.router_id,
                n.nasname as nas_ip,
                z.id as zone_id,
                z.name as zone_name,
                COUNT(v.id) as sales_count,
                COALESCE(SUM(v.sale_amount), 0) as total_sales,
                COUNT(DISTINCT v.sold_by) as sellers_count,
                SUM(CASE WHEN v.payment_method = 'cash' THEN 1 ELSE 0 END) as cash_count,
                SUM(CASE WHEN v.payment_method = 'mobile_money' THEN 1 ELSE 0 END) as mobile_money_count,
                SUM(CASE WHEN v.payment_method = 'online' THEN 1 ELSE 0 END) as online_count,
                SUM(CASE WHEN v.payment_method = 'free' THEN 1 ELSE 0 END) as free_count,
                COALESCE(SUM(CASE WHEN v.payment_method = 'cash' THEN v.sale_amount ELSE 0 END), 0) as cash_amount,
                COALESCE(SUM(CASE WHEN v.payment_method = 'mobile_money' THEN v.sale_amount ELSE 0 END), 0) as mobile_money_amount,
                COALESCE(SUM(CASE WHEN v.payment_method = 'online' THEN v.sale_amount ELSE 0 END), 0) as online_amount
            FROM nas n
            LEFT JOIN zones z ON n.zone_id = z.id
            LEFT JOIN vouchers v ON v.sold_on_nas_id = n.id
                AND v.sold_at IS NOT NULL
                AND DATE(v.sold_at) BETWEEN ? AND ?
        ";
        $params = [$dateFrom, $dateTo];

        // Ajouter condition sur payment_method dans le LEFT JOIN si spécifié
        if ($paymentMethod) {
            $sql = str_replace(
                "AND DATE(v.sold_at) BETWEEN ? AND ?",
                "AND DATE(v.sold_at) BETWEEN ? AND ? AND v.payment_method = ?",
                $sql
            );
            $params[] = $paymentMethod;
        }

        $sql .= " WHERE 1=1";

        if ($adminId !== null) {
            $sql .= " AND n.admin_id = ?";
            $params[] = $adminId;
        }

        if ($zoneId) {
            $sql .= " AND n.zone_id = ?";
            $params[] = $zoneId;
        }

        $sql .= " GROUP BY n.id ORDER BY total_sales DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $nasList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'payment_method' => $paymentMethod,
            'nas' => $nasList
        ]);
    }

    /**
     * GET /sales/by-profile - Ventes par profil
     */
    public function byProfile(): void
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $adminId = $this->getAdminId();
        $adminFilter = $adminId !== null ? " AND v.admin_id = ?" : "";

        $profileAdminFilter = $adminId !== null ? " AND p.admin_id = ?" : "";

        $sql = "
            SELECT
                p.id as profile_id,
                p.name as profile_name,
                p.price as profile_price,
                COUNT(v.id) as sales_count,
                COALESCE(SUM(v.sale_amount), 0) as total_sales,
                COALESCE(SUM(v.commission_vendeur + v.commission_gerant + v.commission_admin), 0) as total_commissions,
                SUM(CASE WHEN v.payment_method = 'cash' THEN 1 ELSE 0 END) as cash_count,
                COALESCE(SUM(CASE WHEN v.payment_method = 'cash' THEN v.sale_amount ELSE 0 END), 0) as cash_amount,
                SUM(CASE WHEN v.payment_method = 'mobile_money' THEN 1 ELSE 0 END) as mobile_money_count,
                COALESCE(SUM(CASE WHEN v.payment_method = 'mobile_money' THEN v.sale_amount ELSE 0 END), 0) as mobile_money_amount,
                SUM(CASE WHEN v.payment_method = 'online' THEN 1 ELSE 0 END) as online_count,
                COALESCE(SUM(CASE WHEN v.payment_method = 'online' THEN v.sale_amount ELSE 0 END), 0) as online_amount
            FROM profiles p
            LEFT JOIN vouchers v ON v.profile_id = p.id
                AND v.sold_at IS NOT NULL
                AND DATE(v.sold_at) BETWEEN ? AND ?
                $adminFilter
            WHERE p.is_active = 1
            $profileAdminFilter
            GROUP BY p.id
            ORDER BY sales_count DESC
        ";
        $params = [$dateFrom, $dateTo];
        if ($adminId !== null) {
            $params[] = $adminId; // for $adminFilter (vouchers)
            $params[] = $adminId; // for $profileAdminFilter (profiles)
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'profiles' => $profiles
        ]);
    }

    /**
     * GET /sales/commissions - Commissions à payer
     */
    public function commissions(): void
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $adminId = $this->getAdminId();
        $adminFilter = $adminId !== null ? " AND v.admin_id = ?" : "";
        $userAdminFilter = $adminId !== null ? " AND (u.id = ? OR u.parent_id = ? OR u.parent_id IN (SELECT id FROM users WHERE parent_id = ?))" : "";

        // Commissions par utilisateur
        $sql = "
            SELECT
                u.id as user_id,
                u.username,
                u.full_name,
                u.role,
                COUNT(v.id) as sales_count,
                COALESCE(SUM(v.sale_amount), 0) as total_sales,
                CASE
                    WHEN u.role = 'vendeur' THEN COALESCE(SUM(v.commission_vendeur), 0)
                    WHEN u.role = 'gerant' THEN COALESCE(SUM(v.commission_gerant), 0)
                    WHEN u.role = 'admin' THEN COALESCE(SUM(v.commission_admin), 0)
                    ELSE 0
                END as total_commission,
                CASE
                    WHEN u.role = 'vendeur' THEN COALESCE(SUM(CASE WHEN v.commission_paid = 0 THEN v.commission_vendeur ELSE 0 END), 0)
                    WHEN u.role = 'gerant' THEN COALESCE(SUM(CASE WHEN v.commission_paid = 0 THEN v.commission_gerant ELSE 0 END), 0)
                    WHEN u.role = 'admin' THEN COALESCE(SUM(CASE WHEN v.commission_paid = 0 THEN v.commission_admin ELSE 0 END), 0)
                    ELSE 0
                END as pending_commission
            FROM users u
            JOIN vouchers v ON (
                (u.role = 'vendeur' AND v.sold_by = u.id) OR
                (u.role = 'gerant' AND v.sold_by IN (SELECT id FROM users WHERE parent_id = u.id)) OR
                (u.role = 'admin' AND v.sold_by IN (SELECT id FROM users WHERE parent_id IN (SELECT id FROM users WHERE parent_id = u.id)))
            )
            WHERE v.sold_at IS NOT NULL
            AND DATE(v.sold_at) BETWEEN ? AND ?
            $adminFilter
            $userAdminFilter
            AND u.role IN ('vendeur', 'gerant', 'admin')
            GROUP BY u.id
            HAVING total_commission > 0
            ORDER BY pending_commission DESC
        ";
        $params = [$dateFrom, $dateTo];
        if ($adminId !== null) {
            $params[] = $adminId; // for $adminFilter (vouchers)
            $params[] = $adminId; // for $userAdminFilter u.id
            $params[] = $adminId; // for $userAdminFilter u.parent_id
            $params[] = $adminId; // for $userAdminFilter parent parent
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Taux de commission
        $ratesSql = "SELECT * FROM commission_rates WHERE is_active = 1";
        $ratesParams = [];
        if ($adminId !== null) {
            $ratesSql .= " AND admin_id = ?";
            $ratesParams[] = $adminId;
        }
        $ratesSql .= " ORDER BY role";
        $stmt = $this->pdo->prepare($ratesSql);
        $stmt->execute($ratesParams);
        $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'commissions' => $commissions,
            'rates' => $rates
        ]);
    }

    /**
     * GET /sales/commission-rates - Taux de commission
     */
    public function getCommissionRates(): void
    {
        $adminId = $this->getAdminId();

        $sql = "
            SELECT cr.*, z.name as zone_name, p.name as profile_name
            FROM commission_rates cr
            LEFT JOIN zones z ON cr.zone_id = z.id
            LEFT JOIN profiles p ON cr.profile_id = p.id
            WHERE 1=1
        ";
        $params = [];
        if ($adminId !== null) {
            $sql .= " AND cr.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY cr.role, cr.zone_id, cr.profile_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess($rates);
    }

    /**
     * PUT /sales/commission-rates/{id} - Modifier un taux
     */
    public function updateCommissionRate(array $params): void
    {
        $id = $params['id'] ?? null;
        $data = getJsonBody();
        $adminId = $this->getAdminId();

        if (!$id) {
            jsonError(__('api.id_required'), 400);
        }

        $sql = "
            UPDATE commission_rates
            SET rate_type = ?, rate_value = ?, is_active = ?
            WHERE id = ?
        ";
        $updateParams = [
            $data['rate_type'] ?? 'percentage',
            $data['rate_value'] ?? 0,
            $data['is_active'] ?? 1,
            $id
        ];

        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $updateParams[] = $adminId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($updateParams);

        jsonSuccess(null, __('api.commission_rate_updated'));
    }

    /**
     * POST /sales/mark-paid - Marquer des commissions comme payées
     */
    public function markCommissionsPaid(): void
    {
        $data = getJsonBody();
        $userId = $data['user_id'] ?? null;
        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? null;
        $adminId = $this->getAdminId();

        if (!$userId || !$dateFrom || !$dateTo) {
            jsonError(__('api.missing_parameters'), 400);
        }

        // Récupérer les infos utilisateur (avec vérification admin_id)
        $userSql = "SELECT role FROM users WHERE id = ?";
        $userParams = [$userId];
        if ($adminId !== null) {
            $userSql .= " AND admin_id = ?";
            $userParams[] = $adminId;
        }
        $stmt = $this->pdo->prepare($userSql);
        $stmt->execute($userParams);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            jsonError(__('api.user_not_found'), 404);
        }

        $adminVoucherFilter = $adminId !== null ? " AND admin_id = ?" : "";

        // Marquer les commissions comme payées selon le rôle
        if ($user['role'] === 'vendeur') {
            $stmt = $this->pdo->prepare("
                UPDATE vouchers
                SET commission_paid = 1
                WHERE sold_by = ?
                AND sold_at IS NOT NULL
                AND DATE(sold_at) BETWEEN ? AND ?
                AND commission_paid = 0
                $adminVoucherFilter
            ");
        }
        elseif ($user['role'] === 'gerant') {
            $stmt = $this->pdo->prepare("
                UPDATE vouchers
                SET commission_paid = 1
                WHERE sold_by IN (SELECT id FROM users WHERE parent_id = ?)
                AND sold_at IS NOT NULL
                AND DATE(sold_at) BETWEEN ? AND ?
                AND commission_paid = 0
                $adminVoucherFilter
            ");
        }
        else {
            jsonError(__('api.role_not_supported'), 400);
        }

        $execParams = [$userId, $dateFrom, $dateTo];
        if ($adminId !== null) {
            $execParams[] = $adminId;
        }
        $stmt->execute($execParams);
        $count = $stmt->rowCount();

        jsonSuccess(['marked_count' => $count], __('api.commissions_marked_paid'));
    }

    /**
     * GET /sales/seller/{id} - Détails d'un vendeur
     */
    public function sellerDetails(array $params): void
    {
        $sellerId = $params['id'] ?? null;
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $adminId = $this->getAdminId();
        $adminFilter = $adminId !== null ? " AND admin_id = ?" : "";
        $adminFilterV = $adminId !== null ? " AND v.admin_id = ?" : "";

        if (!$sellerId) {
            jsonError(__('api.seller_id_required'), 400);
        }

        // Info vendeur (avec vérification admin_id)
        $userSql = "
            SELECT u.*, up.username as parent_username, up.full_name as parent_name
            FROM users u
            LEFT JOIN users up ON u.parent_id = up.id
            WHERE u.id = ?
        ";
        $userParams = [$sellerId];
        if ($adminId !== null) {
            $userSql .= " AND (u.id = ? OR u.parent_id = ? OR u.parent_id IN (SELECT id FROM users WHERE parent_id = ?))";
            $userParams[] = $adminId;
            $userParams[] = $adminId;
            $userParams[] = $adminId;
        }
        $stmt = $this->pdo->prepare($userSql);
        $stmt->execute($userParams);
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$seller) {
            jsonError(__('api.seller_not_found'), 404);
        }

        $isGerant = $seller['role'] === 'gerant';
        $sellerCondition = $isGerant ? "(v.gerant_id = ? OR (v.gerant_id IS NULL AND v.sold_by IN (SELECT id FROM users WHERE parent_id = ?)))" : "v.sold_by = ?";
        $commField = $isGerant ? "commission_gerant" : "commission_vendeur";
        $sellerParamsArray = $isGerant ? [$sellerId, $sellerId] : [$sellerId];

        // Stats du vendeur
        $statsSql = "
            SELECT
                COUNT(*) as total_sales,
                COALESCE(SUM(v.sale_amount), 0) as total_revenue,
                COALESCE(SUM(v.$commField), 0) as total_commission,
                COALESCE(SUM(CASE WHEN v.commission_paid = 0 THEN v.$commField ELSE 0 END), 0) as pending_commission
            FROM vouchers v
            WHERE $sellerCondition
            AND v.sold_at IS NOT NULL
            AND DATE(v.sold_at) BETWEEN ? AND ?
            $adminFilterV
        ";
        $statsParams = array_merge($sellerParamsArray, [$dateFrom, $dateTo]);
        if ($adminId !== null) {
            $statsParams[] = $adminId;
        }
        $stmt = $this->pdo->prepare($statsSql);
        $stmt->execute($statsParams);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Ventes récentes
        $recentSql = "
            SELECT
                v.id,
                v.username as ticket_code,
                v.sold_at,
                v.payment_method,
                v.sale_amount,
                v.$commField as commission_vendeur,
                p.name as profile_name,
                n.shortname as nas_name
            FROM vouchers v
            LEFT JOIN profiles p ON v.profile_id = p.id
            LEFT JOIN nas n ON v.sold_on_nas_id = n.id
            WHERE $sellerCondition
            AND v.sold_at IS NOT NULL
            AND DATE(v.sold_at) BETWEEN ? AND ?
            $adminFilterV
            ORDER BY v.sold_at DESC
            LIMIT 50
        ";
        $recentParams = array_merge($sellerParamsArray, [$dateFrom, $dateTo]);
        if ($adminId !== null) {
            $recentParams[] = $adminId;
        }
        $stmt = $this->pdo->prepare($recentSql);
        $stmt->execute($recentParams);
        $recentSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ventes par jour
        $byDaySql = "
            SELECT
                DATE(v.sold_at) as date,
                COUNT(*) as count,
                COALESCE(SUM(v.sale_amount), 0) as total
            FROM vouchers v
            WHERE $sellerCondition
            AND v.sold_at IS NOT NULL
            AND DATE(v.sold_at) BETWEEN ? AND ?
            $adminFilterV
            GROUP BY DATE(v.sold_at)
            ORDER BY date DESC
        ";
        $byDayParams = array_merge($sellerParamsArray, [$dateFrom, $dateTo]);
        if ($adminId !== null) {
            $byDayParams[] = $adminId;
        }
        $stmt = $this->pdo->prepare($byDaySql);
        $stmt->execute($byDayParams);
        $byDay = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Résumé global (Jour, Mois, Année) - sans tenir compte des filtres de date
        $summarySql = "
            SELECT
                SUM(CASE WHEN DATE(v.sold_at) = CURDATE() THEN 1 ELSE 0 END) as tickets_today,
                COALESCE(SUM(CASE WHEN DATE(v.sold_at) = CURDATE() THEN v.sale_amount ELSE 0 END), 0) as revenue_today,
                
                SUM(CASE WHEN YEAR(v.sold_at) = YEAR(CURDATE()) AND MONTH(v.sold_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as tickets_month,
                COALESCE(SUM(CASE WHEN YEAR(v.sold_at) = YEAR(CURDATE()) AND MONTH(v.sold_at) = MONTH(CURDATE()) THEN v.sale_amount ELSE 0 END), 0) as revenue_month,
                
                SUM(CASE WHEN YEAR(v.sold_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as tickets_year,
                COALESCE(SUM(CASE WHEN YEAR(v.sold_at) = YEAR(CURDATE()) THEN v.sale_amount ELSE 0 END), 0) as revenue_year
            FROM vouchers v
            WHERE $sellerCondition
            AND v.sold_at IS NOT NULL
            $adminFilterV
        ";
        $summaryParams = $sellerParamsArray;
        if ($adminId !== null) {
            $summaryParams[] = $adminId;
        }
        $stmt = $this->pdo->prepare($summarySql);
        $stmt->execute($summaryParams);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        $gerantSellers = [];
        if ($isGerant) {
            $gerantSellersSql = "
                SELECT
                    u.id,
                    u.username,
                    u.full_name,
                    SUM(CASE WHEN DATE(v.sold_at) = CURDATE() THEN 1 ELSE 0 END) as tickets_today,
                    COALESCE(SUM(CASE WHEN DATE(v.sold_at) = CURDATE() THEN v.sale_amount ELSE 0 END), 0) as revenue_today,
                    SUM(CASE WHEN YEAR(v.sold_at) = YEAR(CURDATE()) AND MONTH(v.sold_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as tickets_month,
                    COALESCE(SUM(CASE WHEN YEAR(v.sold_at) = YEAR(CURDATE()) AND MONTH(v.sold_at) = MONTH(CURDATE()) THEN v.sale_amount ELSE 0 END), 0) as revenue_month,
                    SUM(CASE WHEN YEAR(v.sold_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as tickets_year,
                    COALESCE(SUM(CASE WHEN YEAR(v.sold_at) = YEAR(CURDATE()) THEN v.sale_amount ELSE 0 END), 0) as revenue_year
                FROM vouchers v
                JOIN users u ON v.sold_by = u.id
                WHERE $sellerCondition
                AND v.sold_at IS NOT NULL
                $adminFilterV
                GROUP BY u.id
                ORDER BY revenue_month DESC
            ";
            $gerantSellersParams = $sellerParamsArray;
            if ($adminId !== null) {
                $gerantSellersParams[] = $adminId;
            }
            $stmt = $this->pdo->prepare($gerantSellersSql);
            $stmt->execute($gerantSellersParams);
            $gerantSellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        jsonSuccess([
            'seller' => $seller,
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'stats' => $stats,
            'recent_sales' => $recentSales,
            'by_day' => array_reverse($byDay),
            'summary' => $summary,
            'gerant_sellers' => $gerantSellers
        ]);
    }

    /**
     * DELETE /sales/{id} - Supprimer une vente (reset sold_at du voucher)
     */
    public function delete(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $adminId = $this->getAdminId();

        if (!$id) {
            jsonError(__('sales.delete_invalid_id'), 400);
        }

        // Vérifier que la vente existe et appartient à l'admin
        $sql = "SELECT id, sold_at FROM vouchers WHERE id = ?";
        $p = [$id];
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $p[] = $adminId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($p);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$voucher) {
            jsonError(__('sales.delete_not_found'), 404);
        }

        // Reset les champs de vente du voucher
        $stmt = $this->pdo->prepare("
            UPDATE vouchers SET
                sold_at = NULL,
                sold_by = NULL,
                sold_on_nas_id = NULL,
                payment_method = NULL,
                sale_amount = NULL,
                commission_vendeur = NULL,
                commission_gerant = NULL,
                commission_admin = NULL,
                commission_paid = 0,
                gerant_id = NULL
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        jsonSuccess(null, __('sales.delete_success'));
    }

    /**
     * DELETE /sales/batch - Supprimer plusieurs ventes
     */
    public function deleteBatch(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        $adminId = $this->getAdminId();

        if (empty($ids) || !is_array($ids)) {
            jsonError(__('sales.delete_no_selection'), 400);
        }

        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE vouchers SET
                    sold_at = NULL, sold_by = NULL, sold_on_nas_id = NULL,
                    payment_method = NULL, sale_amount = NULL,
                    commission_vendeur = NULL, commission_gerant = NULL,
                    commission_admin = NULL, commission_paid = 0, gerant_id = NULL
                WHERE id IN ($placeholders)";
        $p = $ids;

        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $p[] = $adminId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($p);

        jsonSuccess(['deleted' => $stmt->rowCount()], __('sales.delete_batch_success'));
    }
}