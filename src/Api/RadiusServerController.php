<?php
/**
 * Controller API Serveurs RADIUS distribués
 */

class RadiusServerController
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
     * GET /api/radius-servers
     */
    public function index(): void
    {
        $adminId = $this->getAdminId();
        $servers = $this->db->getAllRadiusServers($adminId);
        jsonSuccess($servers);
    }

    /**
     * GET /api/radius-servers/{id}
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $server = $this->db->getRadiusServerById($id);

        if (!$server) {
            jsonError(__('api.radius_server_not_found'), 404);
        }

        // Ajouter les zones de ce serveur
        $server['zones'] = $this->db->getZonesByRadiusServer($id);

        jsonSuccess($server);
    }

    /**
     * POST /api/radius-servers
     */
    public function store(): void
    {
        $data = getJsonBody();

        if (empty($data['name'])) {
            jsonError(__('api.radius_server_name_required'), 400);
        }

        if (empty($data['host'])) {
            jsonError(__('api.radius_server_host_required'), 400);
        }

        $data['admin_id'] = $this->getAdminId();

        try {
            $id = $this->db->createRadiusServer($data);
            $server = $this->db->getRadiusServerById($id);
            jsonSuccess($server, __('api.radius_server_created'));
        } catch (Exception $e) {
            jsonError(__('api.radius_server_create_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/radius-servers/{id}
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $server = $this->db->getRadiusServerById($id);
        if (!$server) {
            jsonError(__('api.radius_server_not_found'), 404);
        }

        if (empty($data['name'])) {
            jsonError(__('api.radius_server_name_required'), 400);
        }

        if (empty($data['host'])) {
            jsonError(__('api.radius_server_host_required'), 400);
        }

        try {
            $this->db->updateRadiusServer($id, $data);
            $server = $this->db->getRadiusServerById($id);
            jsonSuccess($server, __('api.radius_server_updated'));
        } catch (Exception $e) {
            jsonError(__('api.radius_server_update_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/radius-servers/{id}
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];

        $server = $this->db->getRadiusServerById($id);
        if (!$server) {
            jsonError(__('api.radius_server_not_found'), 404);
        }

        try {
            $this->db->deleteRadiusServer($id);
            jsonSuccess(null, __('api.radius_server_deleted'));
        } catch (Exception $e) {
            jsonError(__('api.radius_server_delete_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/radius-servers/{id}/regenerate-token
     */
    public function regenerateToken(array $params): void
    {
        $id = (int)$params['id'];

        $server = $this->db->getRadiusServerById($id);
        if (!$server) {
            jsonError(__('api.radius_server_not_found'), 404);
        }

        $type = $_GET['type'] ?? 'sync';

        try {
            if ($type === 'platform') {
                $newToken = $this->db->regenerateRadiusServerPlatformToken($id);
            } else {
                $newToken = $this->db->regenerateRadiusServerSyncToken($id);
            }
            jsonSuccess(['token' => $newToken], __('api.radius_server_token_regenerated'));
        } catch (Exception $e) {
            jsonError(__('api.radius_server_token_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/radius-servers/{id}/status
     */
    public function status(array $params): void
    {
        $id = (int)$params['id'];

        $server = $this->db->getRadiusServerById($id);
        if (!$server) {
            jsonError(__('api.radius_server_not_found'), 404);
        }

        $statusData = [
            'id' => $server['id'],
            'name' => $server['name'],
            'code' => $server['code'],
            'host' => $server['host'],
            'status' => $server['status'],
            'last_sync_at' => $server['last_sync_at'],
            'last_heartbeat_at' => $server['last_heartbeat_at'],
            'zones_count' => $server['zones_count'],
            'nas_count' => $server['nas_count'],
        ];

        jsonSuccess($statusData);
    }

    /**
     * GET /api/radius-servers/statuses
     */
    public function statuses(): void
    {
        $adminId = $this->getAdminId();
        $statuses = $this->db->getRadiusServerStatuses($adminId);
        jsonSuccess($statuses);
    }

    /**
     * GET /api/radius-servers/{id}/zones
     */
    public function getZones(array $params): void
    {
        $id = (int)$params['id'];

        $server = $this->db->getRadiusServerById($id);
        if (!$server) {
            jsonError(__('api.radius_server_not_found'), 404);
        }

        $zones = $this->db->getZonesByRadiusServer($id);
        jsonSuccess($zones);
    }

    /**
     * GET /api/radius-servers/generate-code
     */
    public function generateCode(): void
    {
        try {
            $code = $this->db->generateRadiusServerCode();
            jsonSuccess(['code' => $code]);
        } catch (Exception $e) {
            jsonError(__('api.radius_server_generate_code_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/radius-servers/{id}/toggle
     */
    public function toggle(array $params): void
    {
        $id = (int)$params['id'];

        $server = $this->db->getRadiusServerById($id);
        if (!$server) {
            jsonError(__('api.radius_server_not_found'), 404);
        }

        try {
            $newStatus = $server['is_active'] ? 0 : 1;
            $this->db->updateRadiusServer($id, [
                'name' => $server['name'],
                'host' => $server['host'],
                'webhook_port' => $server['webhook_port'],
                'webhook_path' => $server['webhook_path'],
                'sync_interval' => $server['sync_interval'],
                'is_active' => $newStatus
            ]);
            $server = $this->db->getRadiusServerById($id);
            jsonSuccess($server, $server['is_active'] ? __('api.radius_server_activated') : __('api.radius_server_deactivated'));
        } catch (Exception $e) {
            jsonError(__('api.radius_server_toggle_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/radius-servers/active
     * Retourne tous les serveurs actifs (pour le select zone, accessible à tous les rôles)
     */
    public function active(): void
    {
        $servers = $this->db->getActiveRadiusServers();
        jsonSuccess($servers);
    }

    /**
     * POST /api/radius-servers/{id}/set-default
     * Définir un serveur comme serveur par défaut (superuser uniquement)
     */
    public function setDefault(array $params): void
    {
        if (!$this->auth->getCurrentUser()->isSuperAdmin()) {
            jsonError(__('api.forbidden'), 403);
            return;
        }

        $id = (int)$params['id'];
        $server = $this->db->getRadiusServerById($id);
        if (!$server) {
            jsonError(__('api.radius_server_not_found'), 404);
            return;
        }

        // Si déjà default, retirer le statut
        if (!empty($server['is_default'])) {
            $this->db->unsetDefaultRadiusServer($id);
            $server = $this->db->getRadiusServerById($id);
            jsonSuccess($server, __('radius_servers.default_removed'));
            return;
        }

        $this->db->setDefaultRadiusServer($id);
        $server = $this->db->getRadiusServerById($id);
        jsonSuccess($server, __('radius_servers.set_default_success'));
    }

    /**
     * GET /api/radius-servers/{id}/install-script
     * Génère le script d'installation pour un nœud RADIUS
     */
    public function installScript(array $params): void
    {
        $id = (int)$params['id'];

        $server = $this->db->getRadiusServerById($id);
        if (!$server) {
            jsonError(__('api.radius_server_not_found'), 404);
        }

        // Déterminer l'URL de la plateforme
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $platformUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        $basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
        if ($basePath !== '/') {
            $platformUrl .= $basePath;
        }

        $script = $this->generateInstallScript($server, $platformUrl);

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="install-radius-node-' . $server['code'] . '.sh"');
        echo $script;
        exit;
    }

    /**
     * Générer le script d'installation bash pour un nœud RADIUS
     */
    private function generateInstallScript(array $server, string $platformUrl): string
    {
        $syncToken = $server['sync_token'];
        $platformToken = $server['platform_token'];
        $serverCode = $server['code'];

        return <<<BASH
#!/bin/bash
# ===========================================
# Installation RADIUS Node - {$server['name']}
# Code: {$serverCode}
# Généré le: $(date)
# ===========================================

set -e

echo "=== Installation du nœud RADIUS ==="
echo "Serveur: {$server['name']} ({$serverCode})"
echo ""

# Vérifications
if [ "\$EUID" -ne 0 ]; then
    echo "ERREUR: Ce script doit être exécuté en root (sudo)"
    exit 1
fi

# Variables
INSTALL_DIR="/opt/radius-node"
PLATFORM_URL="{$platformUrl}"
SERVER_CODE="{$serverCode}"
SYNC_TOKEN="{$syncToken}"
PLATFORM_TOKEN="{$platformToken}"
SYNC_INTERVAL={$server['sync_interval']}

# Vérifier l'URL de la plateforme
if echo "\$PLATFORM_URL" | grep -qE "(localhost|127\\.0\\.0\\.1)"; then
    echo ""
    echo "ATTENTION: L'URL de la plateforme est configuree sur: \$PLATFORM_URL"
    echo "Cela ne fonctionnera pas pour un VPS distant."
    echo ""
    read -p "Entrez l'URL publique de la plateforme (ex: https://votre-domaine.com/nas): " NEW_URL
    if [ -n "\$NEW_URL" ]; then
        PLATFORM_URL="\$NEW_URL"
        echo "URL mise a jour: \$PLATFORM_URL"
    else
        echo "ERREUR: L'URL de la plateforme ne peut pas etre localhost pour un VPS distant."
        exit 1
    fi
fi

echo "1. Installation des dependances..."
apt-get update -qq
apt-get install -y -qq php8.2-cli php8.2-mysql php8.2-sockets php8.2-curl php8.2-fpm mysql-server nginx

echo "2. Création du répertoire d'installation..."
mkdir -p \$INSTALL_DIR/{src,database,logs,config}

echo "3. Téléchargement du package RADIUS Node..."
# Copier les fichiers depuis la plateforme
curl -s -H "X-Node-Token: \$SYNC_TOKEN" "\$PLATFORM_URL/node_sync.php?action=download&server=\$SERVER_CODE" -o /tmp/radius-node.tar.gz
if [ -f /tmp/radius-node.tar.gz ]; then
    tar -xzf /tmp/radius-node.tar.gz -C \$INSTALL_DIR
    rm /tmp/radius-node.tar.gz
else
    echo "ERREUR: Impossible de télécharger le package"
    exit 1
fi

echo "4. Configuration..."
DB_PASSWORD=\$(openssl rand -base64 32)
cat > \$INSTALL_DIR/config/config.php << PHPEOF
<?php
return [
    'platform' => [
        'url' => '\$PLATFORM_URL',
        'server_code' => '\$SERVER_CODE',
        'sync_token' => '\$SYNC_TOKEN',
        'platform_token' => '\$PLATFORM_TOKEN',
        'sync_interval' => \$SYNC_INTERVAL,
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'radius_node',
        'username' => 'radius_node',
        'password' => '\$DB_PASSWORD',
        'charset' => 'utf8mb4',
    ],
    'radius' => [
        'auth_port' => 1812,
        'acct_port' => 1813,
        'listen_ip' => '0.0.0.0',
    ],
    'options' => [
        'debug' => false,
        'log_file' => '\$INSTALL_DIR/logs/radius.log',
    ],
];
PHPEOF

echo "5. Configuration de la base de donnees locale..."
mysql -e "CREATE DATABASE IF NOT EXISTS radius_node CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'radius_node'@'localhost' IDENTIFIED BY '\$DB_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON radius_node.* TO 'radius_node'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "6. Initialisation du schéma..."
mysql radius_node < \$INSTALL_DIR/database/node_schema.sql

echo "7. Configuration du service systemd..."
cat > /etc/systemd/system/radius-node.service << EOF
[Unit]
Description=RADIUS Node Server
After=network.target mysql.service

[Service]
Type=simple
ExecStart=/usr/bin/php \$INSTALL_DIR/radius_server.php
Restart=always
RestartSec=5
User=root

[Install]
WantedBy=multi-user.target
EOF

echo "8. Configuration du cron de sync..."
(crontab -l 2>/dev/null; echo "* * * * * /usr/bin/php \$INSTALL_DIR/sync_client.php >> \$INSTALL_DIR/logs/sync.log 2>&1") | crontab -

echo "9. Configuration Nginx pour le webhook..."
cat > /etc/nginx/sites-available/radius-node << EOF
server {
    listen 443 ssl;
    server_name _;

    # SSL - remplacer par vos certificats
    ssl_certificate /etc/ssl/certs/ssl-cert-snakeoil.pem;
    ssl_certificate_key /etc/ssl/private/ssl-cert-snakeoil.key;

    root \$INSTALL_DIR;
    index webhook.php;

    location /webhook.php {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$INSTALL_DIR/webhook.php;
        include fastcgi_params;
    }
}
EOF
ln -sf /etc/nginx/sites-available/radius-node /etc/nginx/sites-enabled/

echo "10. Démarrage des services..."
systemctl daemon-reload
systemctl enable radius-node
systemctl start radius-node
systemctl restart nginx

echo ""
echo "=== Installation terminée ==="
echo "Serveur RADIUS: actif sur les ports 1812/1813"
echo "Webhook: actif sur le port 443"
echo "Sync: configuré toutes les minutes via cron"
echo ""
echo "Première sync en cours..."
php \$INSTALL_DIR/sync_client.php
echo "Terminé!"
BASH;
    }
}
