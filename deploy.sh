#!/bin/bash
# ============================================================
# RADIUS Manager - Script de déploiement pour Ubuntu 22.04/24.04
# ============================================================
#
# Usage:
#   1. Copier ce fichier sur le VPS (via scp, nano, etc.)
#   2. Exécuter avec token GitHub (repo privé):
#      GITHUB_TOKEN=ghp_xxxxx sudo bash deploy.sh
#
#   Ou sans token (repo public):
#      sudo bash deploy.sh
#
# Ce script installe et configure automatiquement :
#   - Apache 2.4 + PHP 8.1+ + MySQL/MariaDB
#   - RADIUS Manager (clone depuis GitHub)
#   - Base de données + migrations
#   - VirtualHost + permissions
#   - Cron jobs
#   - (Optionnel) SSL Let's Encrypt
# ============================================================

set -e

# ========================
# Couleurs
# ========================
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# ========================
# Fonctions utilitaires
# ========================
info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }
error()   { echo -e "${RED}[ERREUR]${NC} $1"; exit 1; }
step()    { echo -e "\n${CYAN}${BOLD}==> $1${NC}"; }

# Fonction read compatible curl|bash (lit depuis /dev/tty)
ask() {
    local prompt="$1" var="$2" default="$3" silent="$4"
    if [ -t 0 ]; then
        # Mode normal (exécution directe)
        if [ "$silent" = "s" ]; then
            read -sp "$prompt" "$var"
        else
            read -p "$prompt" "$var"
        fi
    elif [ -e /dev/tty ]; then
        # Mode pipe (curl|bash) - lire depuis le terminal
        if [ "$silent" = "s" ]; then
            read -sp "$prompt" "$var" < /dev/tty
        else
            read -p "$prompt" "$var" < /dev/tty
        fi
    else
        # Pas de terminal disponible - utiliser la valeur par défaut
        eval "$var=''"
    fi
    # Appliquer la valeur par défaut si vide
    if [ -z "${!var}" ] && [ -n "$default" ]; then
        eval "$var='$default'"
    fi
}

# ========================
# Vérifications
# ========================
if [ "$EUID" -ne 0 ]; then
    error "Ce script doit être exécuté en tant que root (sudo bash deploy.sh)"
fi

if ! grep -qi "ubuntu" /etc/os-release 2>/dev/null; then
    warn "Ce script est conçu pour Ubuntu 22.04/24.04. Continuez à vos risques."
fi

# ========================
# Variables
# ========================
INSTALL_DIR="/var/www/nas"
REPO_URL="https://github.com/assialexis/Plateforme-de-gestion-Radius.git"
DB_NAME="radius_db"
DB_USER="radius_user"
VHOST_FILE="/etc/apache2/sites-available/nas.conf"
CRON_FILE="/etc/cron.d/radius-manager"

# Support token GitHub pour repo privé (passer en argument ou variable d'environnement)
# Usage: GITHUB_TOKEN=ghp_xxx sudo bash deploy.sh
if [ -n "${GITHUB_TOKEN}" ]; then
    REPO_URL="https://${GITHUB_TOKEN}@github.com/assialexis/Plateforme-de-gestion-Radius.git"
fi

echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║      RADIUS Manager - Installation v1.0.0       ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════╝${NC}"
echo ""

# ========================
# Collecte des informations
# ========================
step "Configuration de l'installation"

# Mot de passe DB
DB_PASS=$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 16)
echo -e "  Mot de passe MySQL pour '${DB_USER}': ${CYAN}${DB_PASS}${NC} (auto-généré)"

# Mot de passe SuperAdmin
ask "  Mot de passe SuperAdmin (défaut: admin123): " ADMIN_PASS "admin123" "s"
echo ""

# Domaine
ask "  Nom de domaine (laisser vide pour accès par IP): " DOMAIN "_"

# SSL
INSTALL_SSL="n"
if [ "$DOMAIN" != "_" ]; then
    ask "  Installer SSL Let's Encrypt ? (y/N): " INSTALL_SSL "n"
fi

echo ""
info "Récapitulatif:"
echo "  - Répertoire: ${INSTALL_DIR}"
echo "  - Base de données: ${DB_NAME}"
echo "  - Utilisateur DB: ${DB_USER}"
echo "  - Domaine: $([ "$DOMAIN" = "_" ] && echo "Accès par IP" || echo "$DOMAIN")"
echo "  - SSL: $([ "$INSTALL_SSL" = "y" ] && echo "Oui" || echo "Non")"
echo ""
ask "Confirmer l'installation ? (Y/n): " CONFIRM "Y"
if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
    echo "Installation annulée."
    exit 0
fi

# ========================
# 1. Mise à jour système
# ========================
step "1/10 - Mise à jour du système"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq
success "Système mis à jour"

# ========================
# 2. Installation des paquets
# ========================
step "2/10 - Installation d'Apache, PHP, MySQL"

# Vérifier quelle version de PHP est disponible
PHP_VERSION=""
if apt-cache show php8.3 &>/dev/null; then
    PHP_VERSION="8.3"
elif apt-cache show php8.2 &>/dev/null; then
    PHP_VERSION="8.2"
elif apt-cache show php8.1 &>/dev/null; then
    PHP_VERSION="8.1"
else
    # Ajouter le PPA pour PHP 8.1+
    apt-get install -y -qq software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt-get update -qq
    PHP_VERSION="8.1"
fi

info "Version PHP: ${PHP_VERSION}"

apt-get install -y -qq \
    apache2 \
    php${PHP_VERSION} \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-dom \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-intl \
    libapache2-mod-php${PHP_VERSION} \
    mariadb-server \
    git \
    unzip \
    curl

success "Paquets installés (PHP ${PHP_VERSION}, Apache, MariaDB)"

# ========================
# 3. Installer Composer
# ========================
step "3/10 - Installation de Composer"
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    success "Composer installé"
else
    success "Composer déjà installé"
fi

# ========================
# 4. Cloner le projet
# ========================
step "4/10 - Clonage du projet"
if [ -d "${INSTALL_DIR}" ]; then
    warn "Le répertoire ${INSTALL_DIR} existe déjà"
    ask "  Supprimer et réinstaller ? (y/N): " OVERWRITE "n"
    if [[ "$OVERWRITE" =~ ^[Yy]$ ]]; then
        rm -rf "${INSTALL_DIR}"
    else
        error "Installation annulée. Supprimez ${INSTALL_DIR} manuellement ou choisissez un autre emplacement."
    fi
fi

git clone "${REPO_URL}" "${INSTALL_DIR}"
success "Projet cloné dans ${INSTALL_DIR}"

# ========================
# 5. Configurer la base de données
# ========================
step "5/10 - Configuration de la base de données"

# Démarrer et sécuriser MariaDB
systemctl start mariadb
systemctl enable mariadb

# Créer la base et l'utilisateur
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Importer le schéma
mysql "${DB_NAME}" < "${INSTALL_DIR}/database/schema.sql"
success "Base de données '${DB_NAME}' créée et schéma importé"

# Mettre à jour le mot de passe superadmin
ADMIN_HASH=$(php -r "echo password_hash('${ADMIN_PASS}', PASSWORD_BCRYPT);")
mysql "${DB_NAME}" -e "UPDATE admins SET password='${ADMIN_HASH}' WHERE username='admin';"
info "Compte admin par défaut: admin / ${ADMIN_PASS}"

# ========================
# 6. Configuration de l'application
# ========================
step "6/10 - Configuration de l'application"

cd "${INSTALL_DIR}"

# Créer config.php depuis l'exemple
cp config/config.example.php config/config.php

# Injecter les credentials de la base de données
sed -i "s/'host' => '127.0.0.1'/'host' => '127.0.0.1'/" config/config.php
sed -i "s/'dbname' => 'radius_db'/'dbname' => '${DB_NAME}'/" config/config.php
sed -i "s/'username' => 'root'/'username' => '${DB_USER}'/" config/config.php
sed -i "s/'password' => ''/'password' => '${DB_PASS}'/" config/config.php

# Installer les dépendances Composer
composer install --no-dev --optimize-autoloader --quiet 2>/dev/null || composer install --no-dev --quiet

success "Application configurée"

# ========================
# 7. Permissions
# ========================
step "7/10 - Configuration des permissions"

# Créer les répertoires nécessaires
mkdir -p "${INSTALL_DIR}/logs"
mkdir -p "${INSTALL_DIR}/storage/backups"
mkdir -p "${INSTALL_DIR}/storage/tmp"
mkdir -p "${INSTALL_DIR}/web/uploads"

# Permissions
chown -R www-data:www-data "${INSTALL_DIR}"
chmod -R 755 "${INSTALL_DIR}"
chmod -R 775 "${INSTALL_DIR}/logs"
chmod -R 775 "${INSTALL_DIR}/storage"
chmod -R 775 "${INSTALL_DIR}/web/uploads"
chmod 640 "${INSTALL_DIR}/config/config.php"
chown www-data:www-data "${INSTALL_DIR}/config/config.php"

success "Permissions configurées"

# ========================
# 8. Configuration Apache
# ========================
step "8/10 - Configuration d'Apache"

# Activer les modules nécessaires
a2enmod rewrite headers deflate expires php${PHP_VERSION} 2>/dev/null

# Désactiver le site par défaut
a2dissite 000-default.conf 2>/dev/null || true

# Créer le VirtualHost
if [ "$DOMAIN" = "_" ]; then
    SERVER_NAME_LINE="ServerName _"
    SERVER_ALIAS_LINE=""
else
    SERVER_NAME_LINE="ServerName ${DOMAIN}"
    SERVER_ALIAS_LINE="ServerAlias www.${DOMAIN}"
fi

cat > "${VHOST_FILE}" << VHOST
<VirtualHost *:80>
    ${SERVER_NAME_LINE}
    ${SERVER_ALIAS_LINE}
    DocumentRoot ${INSTALL_DIR}/web

    <Directory ${INSTALL_DIR}/web>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    # Bloquer l'accès aux fichiers sensibles
    <DirectoryMatch "^${INSTALL_DIR}/(config|src|database|storage|vendor|logs)">
        Require all denied
    </DirectoryMatch>

    # Logs
    ErrorLog \${APACHE_LOG_DIR}/nas-error.log
    CustomLog \${APACHE_LOG_DIR}/nas-access.log combined

    # PHP settings
    php_value upload_max_filesize 50M
    php_value post_max_size 50M
    php_value max_execution_time 300
    php_value memory_limit 256M
</VirtualHost>
VHOST

a2ensite nas.conf 2>/dev/null

# Configurer PHP
PHP_INI=$(php -i 2>/dev/null | grep "Loaded Configuration File" | awk '{print $NF}')
if [ -n "$PHP_INI" ] && [ -f "$PHP_INI" ]; then
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' "$PHP_INI"
    sed -i 's/post_max_size = .*/post_max_size = 50M/' "$PHP_INI"
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
    sed -i 's/memory_limit = .*/memory_limit = 256M/' "$PHP_INI"
fi

# Redémarrer Apache
systemctl restart apache2
systemctl enable apache2

success "Apache configuré et redémarré"

# ========================
# 9. Cron jobs
# ========================
step "9/10 - Configuration des tâches planifiées"

cat > "${CRON_FILE}" << CRON
# RADIUS Manager - Tâches planifiées
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# Nettoyage commandes routeur - toutes les 30 minutes
*/30 * * * * www-data php ${INSTALL_DIR}/cron/router_commands_cleanup.php >> /var/log/nas-cron.log 2>&1

# Rappels PPPoE - tous les jours à 8h
0 8 * * * www-data php ${INSTALL_DIR}/cron/pppoe_reminders.php >> /var/log/nas-cron.log 2>&1

# Notifications Telegram - toutes les heures
0 * * * * www-data php ${INSTALL_DIR}/cron/telegram_notifications.php >> /var/log/nas-cron.log 2>&1

# Notifications WhatsApp - toutes les 2 heures
0 */2 * * * www-data php ${INSTALL_DIR}/cron/whatsapp_notifications.php >> /var/log/nas-cron.log 2>&1
CRON

chmod 644 "${CRON_FILE}"

# Créer le fichier log avec les bons droits pour www-data
touch /var/log/nas-cron.log
chown www-data:www-data /var/log/nas-cron.log

success "Cron jobs configurés"

# ========================
# 10. SSL (optionnel)
# ========================
if [[ "$INSTALL_SSL" =~ ^[Yy]$ ]] && [ "$DOMAIN" != "_" ]; then
    step "10/10 - Installation SSL Let's Encrypt"
    apt-get install -y -qq certbot python3-certbot-apache
    certbot --apache -d "${DOMAIN}" --non-interactive --agree-tos --register-unsafely-without-email || warn "SSL échoué - vous pourrez le configurer plus tard avec: certbot --apache -d ${DOMAIN}"
    success "SSL installé"
else
    step "10/10 - SSL ignoré"
    info "Pour installer SSL plus tard: sudo certbot --apache -d votre-domaine.com"
fi

# ========================
# Exécuter les migrations
# ========================
step "Exécution des migrations"
cd "${INSTALL_DIR}"
php update.php migrate 2>&1 || warn "Certaines migrations ont échoué - vérifiez les logs"
success "Migrations exécutées"

# ========================
# Résumé
# ========================
SERVER_IP=$(hostname -I | awk '{print $1}')
ACCESS_URL=$([ "$DOMAIN" != "_" ] && echo "http://${DOMAIN}" || echo "http://${SERVER_IP}")

echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║       Installation terminée avec succès !        ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${BOLD}URL:${NC}              ${CYAN}${ACCESS_URL}${NC}"
echo -e "  ${BOLD}Utilisateur:${NC}      ${CYAN}admin${NC}"
echo -e "  ${BOLD}Mot de passe:${NC}     ${CYAN}${ADMIN_PASS}${NC}"
echo ""
echo -e "  ${BOLD}Base de données:${NC}  ${DB_NAME}"
echo -e "  ${BOLD}User DB:${NC}          ${DB_USER}"
echo -e "  ${BOLD}Pass DB:${NC}          ${DB_PASS}"
echo -e "  ${BOLD}Répertoire:${NC}       ${INSTALL_DIR}"
echo ""
echo -e "  ${YELLOW}IMPORTANT: Changez le mot de passe admin après la première connexion !${NC}"
echo -e "  ${YELLOW}IMPORTANT: Sauvegardez les credentials MySQL ci-dessus !${NC}"
echo ""
echo -e "  ${BOLD}Commandes utiles:${NC}"
echo -e "    php ${INSTALL_DIR}/update.php status    - Statut du système"
echo -e "    php ${INSTALL_DIR}/update.php backup    - Créer un backup"
echo -e "    php ${INSTALL_DIR}/update.php migrate   - Exécuter les migrations"
echo ""
echo -e "  ${BOLD}Logs:${NC}"
echo -e "    tail -f /var/log/apache2/nas-error.log"
echo -e "    tail -f ${INSTALL_DIR}/logs/app.log"
echo ""

# Sauvegarder les credentials dans un fichier
CRED_FILE="${INSTALL_DIR}/.install_credentials"
cat > "${CRED_FILE}" << CREDS
# RADIUS Manager - Credentials d'installation
# Date: $(date)
# SUPPRIMEZ CE FICHIER APRÈS AVOIR NOTÉ LES INFORMATIONS

URL: ${ACCESS_URL}
Admin: admin / ${ADMIN_PASS}
Database: ${DB_NAME}
DB User: ${DB_USER}
DB Pass: ${DB_PASS}
CREDS
chmod 600 "${CRED_FILE}"
chown root:root "${CRED_FILE}"
warn "Credentials sauvegardés dans ${CRED_FILE} (supprimez-le après lecture)"
