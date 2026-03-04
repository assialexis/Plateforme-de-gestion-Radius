<?php
/**
 * MikroTik Command Sender
 *
 * Envoie des commandes RouterOS aux routeurs MikroTik via la queue
 * de commandes en base de données. Le routeur poll fetch_cmd.php
 * pour récupérer et exécuter les commandes.
 *
 * Architecture (Pull-Based Remote Control) :
 * 1. Cette classe insère des commandes dans la table router_commands
 * 2. Le routeur MikroTik poll fetch_cmd.php toutes les 10 secondes
 * 3. fetch_cmd.php retourne la prochaine commande en attente
 * 4. Le routeur exécute et confirme via ?done=ID
 *
 * @package NAS
 */

class MikroTikCommandSender
{
    private ?PDO $pdo;

    /**
     * Constructeur
     *
     * @param PDO|null $pdo Connexion PDO (null = résolution automatique via RadiusDatabase)
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    /**
     * Résoudre la connexion PDO si non fournie
     */
    private function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $config = require __DIR__ . '/../../config/config.php';
            $this->pdo = new PDO(
                "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}",
                $config['database']['username'],
                $config['database']['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
                );
        }
        return $this->pdo;
    }

    /**
     * Envoyer une commande à un routeur MikroTik
     *
     * @param string $routerId L'identité du routeur (nas.router_id)
     * @param string $command Les commandes RouterOS à exécuter
     * @param string|null $description Description lisible (optionnel)
     * @param int $priority Priorité (1=urgent, 50=normal, 99=bas)
     * @param string $commandType Type de commande pour le suivi
     * @param int $expiresIn Expiration en secondes (défaut: 1h)
     * @return int|false ID de la commande créée ou false en cas d'erreur
     */
    public function send(
        string $routerId,
        string $command,
        ?string $description = null,
        int $priority = 50,
        string $commandType = 'raw',
        int $expiresIn = 600
        ): int|false
    {
        $routerId = preg_replace('/[^a-zA-Z0-9_-]/', '', $routerId);
        if (empty($routerId) || empty($command)) {
            return false;
        }

        try {
            $pdo = $this->getPdo();

            // Résoudre nas_id et admin_id
            $stmt = $pdo->prepare("SELECT id, admin_id FROM nas WHERE router_id = ? LIMIT 1");
            $stmt->execute([$routerId]);
            $nas = $stmt->fetch();

            $nasId = $nas['id'] ?? null;
            $adminId = $nas['admin_id'] ?? null;

            $stmt = $pdo->prepare("
                INSERT INTO router_commands
                    (router_id, nas_id, admin_id, command_type, command_content,
                     command_description, priority, status, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL ? SECOND))
            ");

            $stmt->execute([
                $routerId, $nasId, $adminId, $commandType, $command,
                $description, $priority, $expiresIn
            ]);

            return (int)$pdo->lastInsertId();
        }
        catch (PDOException $e) {
            error_log("MikroTikCommandSender::send() error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer plusieurs commandes en une seule fois
     *
     * @param string $routerId L'identité du routeur
     * @param array $commands Tableau de commandes
     * @return bool True si toutes les commandes ont été créées
     */
    public function sendBatch(string $routerId, array $commands): bool
    {
        $allSuccess = true;
        foreach ($commands as $index => $command) {
            if (!$this->send($routerId, $command, null, $index + 1)) {
                $allSuccess = false;
            }
        }
        return $allSuccess;
    }

    /**
     * Obtenir les commandes en attente pour un routeur
     *
     * @param string $routerId L'identité du routeur
     * @param string|null $status Filtrer par statut (null = tous)
     * @return array Liste des commandes
     */
    public function getPendingCommands(string $routerId, ?string $status = 'pending'): array
    {
        $routerId = preg_replace('/[^a-zA-Z0-9_-]/', '', $routerId);
        try {
            $pdo = $this->getPdo();
            $sql = "SELECT id, command_type, command_description, priority, status,
                           created_at, sent_at, executed_at, retry_count
                    FROM router_commands WHERE router_id = ?";
            $params = [$routerId];

            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY priority ASC, created_at ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }
        catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Obtenir l'historique des commandes pour un routeur ou un admin
     *
     * @param string|null $routerId Filtrer par routeur
     * @param int|null $adminId Filtrer par admin
     * @param int $limit Nombre max de résultats
     * @param int $offset Offset pour pagination
     * @param string|null $status Filtrer par statut
     * @return array
     */
    public function getCommandHistory(
        ?string $routerId = null,
        ?int $adminId = null,
        int $limit = 50,
        int $offset = 0,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
        ): array
    {
        try {
            $pdo = $this->getPdo();
            $sql = "SELECT rc.*, n.shortname as router_name
                    FROM router_commands rc
                    LEFT JOIN nas n ON rc.nas_id = n.id
                    WHERE 1=1";
            $params = [];

            if ($routerId) {
                $sql .= " AND rc.router_id = ?";
                $params[] = $routerId;
            }
            if ($adminId) {
                $sql .= " AND rc.admin_id = ?";
                $params[] = $adminId;
            }
            if ($status) {
                $sql .= " AND rc.status = ?";
                $params[] = $status;
            }
            if ($dateFrom) {
                $sql .= " AND rc.created_at >= ?";
                $params[] = $dateFrom . ' 00:00:00';
            }
            if ($dateTo) {
                $sql .= " AND rc.created_at <= ?";
                $params[] = $dateTo . ' 23:59:59';
            }

            $sql .= " ORDER BY rc.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }
        catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Obtenir une commande par son ID
     */
    public function getCommandById(int $id): ?array
    {
        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM router_commands WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        }
        catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Obtenir des statistiques sur les commandes
     */
    public function getCommandStats(?string $routerId = null, ?int $adminId = null): array
    {
        try {
            $pdo = $this->getPdo();
            $sql = "SELECT
                        COUNT(*) as total,
                        SUM(status = 'pending') as pending,
                        SUM(status = 'sent') as sent,
                        SUM(status = 'executed') as executed,
                        SUM(status = 'failed') as failed,
                        SUM(status = 'expired') as expired,
                        SUM(status = 'cancelled') as cancelled,
                        AVG(CASE WHEN executed_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, sent_at, executed_at) END) as avg_execution_time
                    FROM router_commands WHERE 1=1";
            $params = [];

            if ($routerId) {
                $sql .= " AND router_id = ?";
                $params[] = $routerId;
            }
            if ($adminId) {
                $sql .= " AND admin_id = ?";
                $params[] = $adminId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        }
        catch (PDOException $e) {
            return ['total' => 0, 'pending' => 0, 'sent' => 0, 'executed' => 0, 'failed' => 0];
        }
    }

    /**
     * Annuler une commande en attente
     *
     * @param int $commandId ID de la commande
     * @param string|null $routerId Vérification de sécurité
     * @return bool
     */
    public function cancelCommand(int $commandId, ?string $routerId = null): bool
    {
        try {
            $pdo = $this->getPdo();
            $sql = "UPDATE router_commands SET status = 'cancelled' WHERE id = ? AND status IN ('pending', 'sent')";
            $params = [$commandId];

            if ($routerId) {
                $sql .= " AND router_id = ?";
                $params[] = $routerId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        }
        catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Relancer une commande échouée
     */
    public function retryCommand(int $commandId): bool
    {
        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare(
                "UPDATE router_commands SET status = 'pending', sent_at = NULL, retry_count = 0,
                 error_message = NULL, expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)
                 WHERE id = ? AND status IN ('failed', 'expired')"
            );
            $stmt->execute([$commandId]);
            return $stmt->rowCount() > 0;
        }
        catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Annuler toutes les commandes en attente pour un routeur
     */
    public function cancelAllCommands(string $routerId): int
    {
        $routerId = preg_replace('/[^a-zA-Z0-9_-]/', '', $routerId);
        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare(
                "UPDATE router_commands SET status = 'cancelled'
                 WHERE router_id = ? AND status IN ('pending', 'sent')"
            );
            $stmt->execute([$routerId]);
            return $stmt->rowCount();
        }
        catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Supprimer une commande par son ID
     */
    public function deleteCommand(int $commandId): bool
    {
        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("DELETE FROM router_commands WHERE id = ?");
            $stmt->execute([$commandId]);
            return $stmt->rowCount() > 0;
        }
        catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Supprimer plusieurs commandes par leurs IDs
     */
    public function deleteCommands(array $ids): int
    {
        if (empty($ids))
            return 0;
        try {
            $pdo = $this->getPdo();
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM router_commands WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            return $stmt->rowCount();
        }
        catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Vider tout l'historique des commandes d'un admin
     */
    public function clearHistory(?int $adminId = null): int
    {
        try {
            $pdo = $this->getPdo();
            if ($adminId) {
                $stmt = $pdo->prepare("DELETE FROM router_commands WHERE admin_id = ?");
                $stmt->execute([$adminId]);
            }
            else {
                $stmt = $pdo->query("DELETE FROM router_commands");
            }
            return $stmt->rowCount();
        }
        catch (PDOException $e) {
            return 0;
        }
    }

    // =====================================================
    // COMMANDES PRÉ-DÉFINIES
    // =====================================================

    /**
     * Déconnecter un utilisateur Hotspot
     */
    public function disconnectHotspotUser(string $routerId, string $username, ?string $nasUrl = null): int|false
    {
        $username = addslashes($username);

        $notifyCmd = '';
        if ($nasUrl) {
            $notifyCmd = <<<RSC

# Notifier le serveur NAS de la deconnexion
:do {
    /tool fetch url=("{$nasUrl}?route=/sessions/logout") mode=http http-method=post http-data=("{\"user\":\"{$username}\"}") http-header-field="Content-Type:application/json" output=none
    :log info "NAS: Notification logout envoyee pour {$username}"
} on-error={
    :log warning "NAS: Echec notification logout pour {$username}"
}
RSC;
        }

        $command = <<<RSC
:log info "NAS: Deconnexion hotspot {$username}"
:local activeId [/ip hotspot active find user="{$username}"]
:if (\$activeId != "") do={
    /ip hotspot active remove \$activeId
    :log info "NAS: Hotspot {$username} deconnecte avec succes"{$notifyCmd}
} else={
    :log info "NAS: Hotspot {$username} n'est pas connecte"
}
RSC;

        return $this->send($routerId, $command, "Déconnexion hotspot {$username}", 10, 'disconnect_hotspot');
    }

    /**
     * Déconnecter un utilisateur PPPoE
     */
    public function disconnectPPPoEUser(string $routerId, string $username): int|false
    {
        $username = addslashes($username);
        $command = <<<RSC
:log info "NAS: Deconnexion utilisateur {$username}"
:local found false
:foreach activeId in=[/ppp active find name="{$username}"] do={
    :set found true
    /ppp active remove \$activeId
    :log info "NAS: Session PPPoE {$username} deconnectee"
}
:if (\$found = false) do={
    :log info "NAS: Utilisateur {$username} n'est pas connecte"
}
RSC;

        return $this->send($routerId, $command, "Déconnexion PPPoE {$username}", 10, 'disconnect_pppoe');
    }

    /**
     * Changer le débit d'un utilisateur PPPoE (FUP)
     */
    public function setUserRateLimit(string $routerId, string $username, string $rateLimit): int|false
    {
        $username = addslashes($username);
        $rateLimit = addslashes($rateLimit);
        $command = <<<RSC
:log info "NAS: Modification debit {$username} -> {$rateLimit}"
:do {
    # Modifier le ppp secret pour les futures connexions
    /ppp secret set [find name="{$username}"] rate-limit="{$rateLimit}"
    :log info "NAS: PPP secret {$username} modifie"

    # Modifier la simple queue active si elle existe (effet immediat)
    :local queueId [/queue simple find name=("<pppoe-{$username}>")]
    :if (\$queueId != "") do={
        /queue simple set \$queueId max-limit={$rateLimit}
        :log info "NAS: Queue {$username} modifiee en temps reel"
    } else={
        :log info "NAS: Pas de queue active pour {$username}"
    }
} on-error={
    :log error "NAS: Erreur modification debit {$username}"
}
RSC;

        return $this->send($routerId, $command, "Rate limit {$username} -> {$rateLimit}", 20, 'set_rate_limit');
    }

    /**
     * Déclencher le FUP : déconnecter le client du MikroTik
     */
    public function setActiveQueueSpeed(string $routerId, string $username, int $downloadSpeed, int $uploadSpeed): int|false
    {
        $username = addslashes($username);
        $rateLimit = $this->formatSpeed($uploadSpeed) . '/' . $this->formatSpeed($downloadSpeed);
        $command = <<<RSC
:log info "NAS-FUP: Declenchement FUP {$username} ({$rateLimit})"
:foreach i in=[/ppp active find name="{$username}"] do={ /ppp active remove \$i }
RSC;

        return $this->send($routerId, $command, "FUP declenchement {$username} ({$rateLimit})", 10, 'fup_trigger');
    }

    /**
     * Réinitialiser le FUP : déconnecter le client pour qu'il se reconnecte avec le débit normal
     */
    public function removeFupQueue(string $routerId, string $username): int|false
    {
        $username = addslashes($username);
        $command = <<<RSC
:log info "NAS-FUP: Reset FUP {$username}"
:foreach i in=[/ppp active find name="{$username}"] do={ /ppp active remove \$i }
RSC;

        return $this->send($routerId, $command, "FUP reset {$username}", 10, 'fup_reset');
    }

    /**
     * Formater une vitesse en bps vers le format MikroTik
     */
    private function formatSpeed(int $bps): string
    {
        if ($bps >= 1000000) {
            return round($bps / 1000000) . 'M';
        }
        elseif ($bps >= 1000) {
            return round($bps / 1000) . 'k';
        }
        return $bps . '';
    }

    /**
     * Activer ou désactiver un utilisateur PPPoE
     */
    public function setUserDisabled(string $routerId, string $username, bool $disabled): int|false
    {
        $username = addslashes($username);
        $state = $disabled ? 'yes' : 'no';
        $action = $disabled ? 'desactive' : 'active';

        $command = <<<RSC
:log info "NAS: {$action} utilisateur {$username}"
:do {
    /ppp secret set [find name="{$username}"] disabled={$state}
    :log info "NAS: Utilisateur {$username} {$action} avec succes"
} on-error={
    :log error "NAS: Erreur {$action} {$username}"
}
RSC;

        return $this->send($routerId, $command, ucfirst($action) . " {$username}", 15, 'toggle_user');
    }

    /**
     * Créer un utilisateur PPPoE sur le routeur
     */
    public function createPPPoEUser(string $routerId, array $user): int|false
    {
        $name = addslashes($user['name'] ?? '');
        $password = addslashes($user['password'] ?? '');
        $profile = addslashes($user['profile'] ?? 'default');
        $comment = addslashes($user['comment'] ?? 'Cree par NAS');
        $remoteAddress = $user['remote_address'] ?? '';
        $rateLimit = $user['rate_limit'] ?? '';

        if (empty($name) || empty($password)) {
            return false;
        }

        $options = "name=\"{$name}\" password=\"{$password}\" profile=\"{$profile}\" service=pppoe comment=\"{$comment}\"";

        if (!empty($remoteAddress)) {
            $options .= " remote-address=\"{$remoteAddress}\"";
        }
        if (!empty($rateLimit)) {
            $options .= " rate-limit=\"{$rateLimit}\"";
        }

        $command = <<<RSC
:log info "NAS: Creation utilisateur PPPoE {$name}"
:do {
    /ppp secret add {$options}
    :log info "NAS: Utilisateur {$name} cree avec succes"
} on-error={
    :log error "NAS: Erreur creation utilisateur {$name}"
}
RSC;

        return $this->send($routerId, $command, "Création PPPoE {$name}", 30, 'create_pppoe');
    }

    /**
     * Supprimer un utilisateur PPPoE du routeur
     */
    public function deletePPPoEUser(string $routerId, string $username): int|false
    {
        $username = addslashes($username);

        $command = <<<RSC
:log info "NAS: Suppression utilisateur PPPoE {$username}"
# D'abord deconnecter si actif
:local activeId [/ppp active find name="{$username}"]
:if (\$activeId != "") do={
    /ppp active remove \$activeId
    :log info "NAS: Session {$username} fermee"
}
# Ensuite supprimer le secret
:do {
    /ppp secret remove [find name="{$username}"]
    :log info "NAS: Utilisateur {$username} supprime avec succes"
} on-error={
    :log error "NAS: Erreur suppression utilisateur {$username}"
}
RSC;

        return $this->send($routerId, $command, "Suppression PPPoE {$username}", 25, 'delete_pppoe');
    }

    /**
     * Envoyer un message de log au routeur (pour test)
     */
    public function sendLogMessage(string $routerId, string $message): int|false
    {
        $message = addslashes($message);
        $command = ":log info \"NAS: {$message}\"";

        return $this->send($routerId, $command, "Log: {$message}", 99, 'log');
    }

    /**
     * Exécuter une commande RouterOS personnalisée
     */
    public function executeRaw(string $routerId, string $command, ?string $description = null): int|false
    {
        if ($description) {
            $safeDesc = addslashes($description);
            $command = ":log info \"NAS: {$safeDesc}\"\n" . $command;
        }

        return $this->send($routerId, $command, $description);
    }
}