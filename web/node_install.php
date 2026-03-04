<?php
/**
 * Endpoint public pour servir le script d'installation RADIUS Node
 *
 * Usage (depuis le VPS distant):
 *   curl -sSL https://plateforme.com/node_install.php?code=RS-XXXX&token=SYNC_TOKEN | sudo bash
 *
 * Authentification via code serveur + sync_token dans l'URL.
 */

ini_set('session.use_cookies', '0');
ini_set('display_errors', 0);
error_reporting(E_ALL);

$code = $_GET['code'] ?? '';
$token = $_GET['token'] ?? '';

if (empty($code) || empty($token)) {
    header('HTTP/1.1 400 Bad Request');
    echo "# Erreur: paramètres 'code' et 'token' requis\n";
    echo "# Usage: curl -sSL 'https://votre-plateforme.com/node_install.php?code=RS-XXXX&token=TOKEN' | sudo bash\n";
    exit(1);
}

// Charger la config et la BDD
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';

try {
    $db = new RadiusDatabase($config['database']);
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo "# Erreur: impossible de se connecter à la base de données\n";
    exit(1);
}

// Vérifier le serveur par code
$server = $db->getRadiusServerByCode($code);
if (!$server) {
    header('HTTP/1.1 404 Not Found');
    echo "# Erreur: serveur '{$code}' non trouvé\n";
    exit(1);
}

// Vérifier le sync_token
if (!hash_equals($server['sync_token'], $token)) {
    header('HTTP/1.1 403 Forbidden');
    echo "# Erreur: token invalide\n";
    exit(1);
}

// Déterminer l'URL de la plateforme
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$platformUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/' && $basePath !== '\\') {
    $platformUrl .= $basePath;
}
// Retirer /web si présent (l'URL publique ne doit pas inclure /web)
$platformUrl = rtrim($platformUrl, '/');

$serverCode = $server['code'];
$syncToken = $server['sync_token'];
$platformToken = $server['platform_token'];
$syncInterval = $server['sync_interval'] ?? 60;
$serverName = $server['name'];

// Servir le script bash
header('Content-Type: text/plain; charset=utf-8');

echo <<<BASH
#!/bin/bash
# ===========================================
# Installation RADIUS Node - {$serverName}
# Code: {$serverCode}
# ===========================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

info()    { echo -e "\${CYAN}[INFO]\${NC} \$1"; }
success() { echo -e "\${GREEN}[OK]\${NC} \$1"; }
error()   { echo -e "\${RED}[ERREUR]\${NC} \$1"; exit 1; }

echo ""
echo -e "\${BOLD}╔══════════════════════════════════════════════╗\${NC}"
echo -e "\${BOLD}║     RADIUS Node - Installation automatique   ║\${NC}"
echo -e "\${BOLD}╚══════════════════════════════════════════════╝\${NC}"
echo ""
echo -e "  Serveur: \${CYAN}{$serverName}\${NC}"
echo -e "  Code:    \${CYAN}{$serverCode}\${NC}"
echo ""

# Vérifications
if [ "\$EUID" -ne 0 ]; then
    error "Ce script doit être exécuté en root (sudo)"
fi

# Variables
INSTALL_DIR="/opt/radius-node"
PLATFORM_URL="{$platformUrl}"
SERVER_CODE="{$serverCode}"
SYNC_TOKEN="{$syncToken}"
PLATFORM_TOKEN="{$platformToken}"
SYNC_INTERVAL={$syncInterval}

# Vérifier l'URL de la plateforme (localhost ne fonctionne pas pour un VPS distant)
if echo "\$PLATFORM_URL" | grep -qE "(localhost|127\\.0\\.0\\.1)"; then
    echo ""
    echo -e "\${RED}ATTENTION: L'URL de la plateforme est: \$PLATFORM_URL\${NC}"
    echo "Cela ne fonctionnera pas pour un VPS distant."
    echo ""
    if [ -t 0 ] || [ -e /dev/tty ]; then
        read -p "Entrez l'URL publique de la plateforme: " NEW_URL < /dev/tty
        if [ -n "\$NEW_URL" ]; then
            PLATFORM_URL="\$NEW_URL"
        else
            error "URL requise pour continuer"
        fi
    else
        error "L'URL de la plateforme ne peut pas être localhost"
    fi
fi

# ========================
# 1. Installation des dépendances
# ========================
info "1/8 - Installation des dépendances..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq

# Détecter la version PHP disponible
PHP_VERSION=""
if apt-cache show php8.3 &>/dev/null; then
    PHP_VERSION="8.3"
elif apt-cache show php8.2 &>/dev/null; then
    PHP_VERSION="8.2"
elif apt-cache show php8.1 &>/dev/null; then
    PHP_VERSION="8.1"
else
    apt-get install -y -qq software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt-get update -qq
    PHP_VERSION="8.2"
fi

apt-get install -y -qq \\
    php\${PHP_VERSION}-cli php\${PHP_VERSION}-mysql php\${PHP_VERSION}-curl \\
    php\${PHP_VERSION}-mbstring php\${PHP_VERSION}-fpm \\
    mariadb-server nginx curl
success "Dépendances installées (PHP \${PHP_VERSION})"

# ========================
# 2. Créer le répertoire
# ========================
info "2/8 - Création du répertoire..."
mkdir -p \$INSTALL_DIR/{src,database,logs,config}
success "Répertoire créé: \$INSTALL_DIR"

# ========================
# 3. Télécharger le package
# ========================
info "3/8 - Téléchargement du package RADIUS Node..."
curl -s -H "X-Node-Token: \$SYNC_TOKEN" \\
    "\$PLATFORM_URL/node_sync.php?action=download&server=\$SERVER_CODE" \\
    -o /tmp/radius-node.tar.gz

if [ -f /tmp/radius-node.tar.gz ] && file /tmp/radius-node.tar.gz | grep -q "gzip"; then
    tar -xzf /tmp/radius-node.tar.gz -C \$INSTALL_DIR
    rm /tmp/radius-node.tar.gz
    success "Package téléchargé et extrait"
else
    echo -e "\${RED}  Le téléchargement du package a échoué.\${NC}"
    echo "  Vous devrez copier manuellement les fichiers du nœud RADIUS."
    echo "  Voir: https://github.com/assialexis/Plateforme-de-gestion-Radius"
fi

# ========================
# 4. Configuration
# ========================
info "4/8 - Configuration..."
DB_PASSWORD=\$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 16)

cat > \$INSTALL_DIR/config/config.php << PHPEOF
<?php
return [
    'platform' => [
        'url'            => '\$PLATFORM_URL',
        'server_code'    => '\$SERVER_CODE',
        'sync_token'     => '\$SYNC_TOKEN',
        'platform_token' => '\$PLATFORM_TOKEN',
        'sync_interval'  => \$SYNC_INTERVAL,
    ],
    'database' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'dbname'   => 'radius_node',
        'username' => 'radius_node',
        'password' => '\$DB_PASSWORD',
        'charset'  => 'utf8mb4',
    ],
    'radius' => [
        'auth_port' => 1812,
        'acct_port' => 1813,
        'listen_ip' => '0.0.0.0',
    ],
    'options' => [
        'debug'    => false,
        'log_file' => '\$INSTALL_DIR/logs/radius.log',
    ],
];
PHPEOF
chmod 600 \$INSTALL_DIR/config/config.php
success "Configuration créée"

# ========================
# 5. Base de données
# ========================
info "5/8 - Configuration de la base de données..."
systemctl start mariadb 2>/dev/null || true
systemctl enable mariadb 2>/dev/null || true

mysql -e "CREATE DATABASE IF NOT EXISTS radius_node CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'radius_node'@'localhost' IDENTIFIED BY '\$DB_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON radius_node.* TO 'radius_node'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

if [ -f \$INSTALL_DIR/database/node_schema.sql ]; then
    mysql radius_node < \$INSTALL_DIR/database/node_schema.sql
    success "Schéma importé"
else
    echo "  Schema SQL non trouvé - sera initialisé à la première sync"
fi

# ========================
# 6. Service systemd
# ========================
info "6/8 - Configuration du service RADIUS..."
cat > /etc/systemd/system/radius-node.service << EOF
[Unit]
Description=RADIUS Node Server ({$serverCode})
After=network.target mariadb.service

[Service]
Type=simple
ExecStart=/usr/bin/php \$INSTALL_DIR/radius_server.php
Restart=always
RestartSec=5
User=root
StandardOutput=append:\$INSTALL_DIR/logs/radius.log
StandardError=append:\$INSTALL_DIR/logs/radius-error.log

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable radius-node
success "Service systemd configuré"

# ========================
# 7. Cron de sync
# ========================
info "7/8 - Configuration de la synchronisation..."
(crontab -l 2>/dev/null | grep -v "sync_client.php"; echo "* * * * * /usr/bin/php \$INSTALL_DIR/sync_client.php >> \$INSTALL_DIR/logs/sync.log 2>&1") | crontab -
success "Cron sync configuré (toutes les minutes)"

# ========================
# 8. Nginx webhook
# ========================
info "8/8 - Configuration Nginx (webhook)..."
PHP_FPM_SOCK="/var/run/php/php\${PHP_VERSION}-fpm.sock"

cat > /etc/nginx/sites-available/radius-node << EOF
server {
    listen 443 ssl;
    server_name _;

    ssl_certificate /etc/ssl/certs/ssl-cert-snakeoil.pem;
    ssl_certificate_key /etc/ssl/private/ssl-cert-snakeoil.key;

    root \$INSTALL_DIR;

    location = /webhook.php {
        fastcgi_pass unix:\$PHP_FPM_SOCK;
        fastcgi_param SCRIPT_FILENAME \$INSTALL_DIR/webhook.php;
        include fastcgi_params;
    }

    location / {
        return 403;
    }
}
EOF

ln -sf /etc/nginx/sites-available/radius-node /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true
nginx -t 2>/dev/null && systemctl restart nginx
success "Nginx configuré"

# ========================
# Pare-feu
# ========================
if command -v ufw &>/dev/null; then
    info "Configuration du pare-feu..."
    ufw allow 22/tcp >/dev/null 2>&1
    ufw allow 1812/udp >/dev/null 2>&1
    ufw allow 1813/udp >/dev/null 2>&1
    ufw allow 443/tcp >/dev/null 2>&1
    success "Ports ouverts: 22/tcp, 1812/udp, 1813/udp, 443/tcp"
fi

# ========================
# Démarrage
# ========================
info "Démarrage du serveur RADIUS..."
if [ -f \$INSTALL_DIR/radius_server.php ]; then
    systemctl start radius-node
    success "Serveur RADIUS démarré"
else
    echo "  radius_server.php non trouvé - le service démarrera après la sync"
fi

# Première sync
if [ -f \$INSTALL_DIR/sync_client.php ]; then
    info "Première synchronisation..."
    php \$INSTALL_DIR/sync_client.php 2>&1 || true
fi

# ========================
# Résumé
# ========================
echo ""
echo -e "\${BOLD}╔══════════════════════════════════════════════╗\${NC}"
echo -e "\${BOLD}║      Installation terminée avec succès !      ║\${NC}"
echo -e "\${BOLD}╚══════════════════════════════════════════════╝\${NC}"
echo ""
echo -e "  \${BOLD}Serveur:\${NC}     {$serverName} ({$serverCode})"
echo -e "  \${BOLD}Répertoire:\${NC}  \$INSTALL_DIR"
echo -e "  \${BOLD}RADIUS:\${NC}      Ports 1812/1813 UDP"
echo -e "  \${BOLD}Webhook:\${NC}     Port 443 HTTPS"
echo -e "  \${BOLD}Plateforme:\${NC}  \$PLATFORM_URL"
echo ""
echo -e "  \${BOLD}Commandes utiles:\${NC}"
echo -e "    systemctl status radius-node   - État du serveur"
echo -e "    tail -f \$INSTALL_DIR/logs/sync.log    - Logs sync"
echo -e "    tail -f \$INSTALL_DIR/logs/radius.log  - Logs RADIUS"
echo ""
BASH;
