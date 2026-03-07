#!/bin/bash
#
# Setup webhook server pour noeud RADIUS
# Usage: sudo ./setup-webhook.sh [IP_DU_CENTRAL]
#
# Installe Nginx + PHP-FPM pour servir webhook.php
# Securise avec IP whitelist + token (deja dans le code)
#

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Verifier root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Erreur: Lancer avec sudo${NC}"
    echo "Usage: sudo $0 [IP_DU_CENTRAL]"
    exit 1
fi

# IP du central (parametre ou demander)
CENTRAL_IP="${1:-}"
if [ -z "$CENTRAL_IP" ]; then
    echo -e "${YELLOW}Quelle est l'IP publique du serveur central ?${NC}"
    read -p "IP du central: " CENTRAL_IP
fi

if [ -z "$CENTRAL_IP" ]; then
    echo -e "${RED}Erreur: IP du central requise${NC}"
    exit 1
fi

# Detecter le repertoire du noeud
NODE_DIR="$(cd "$(dirname "$0")" && pwd)"
echo -e "${GREEN}Repertoire du noeud: ${NODE_DIR}${NC}"

# Verifier que webhook.php existe
if [ ! -f "${NODE_DIR}/webhook.php" ]; then
    echo -e "${RED}Erreur: webhook.php introuvable dans ${NODE_DIR}${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}=== Installation du serveur webhook ===${NC}"
echo -e "  Noeud:   ${NODE_DIR}"
echo -e "  Central: ${CENTRAL_IP}"
echo ""

# 1. Installer les paquets
echo -e "${YELLOW}[1/5] Installation Nginx + PHP-FPM...${NC}"
apt update -qq
apt install -y -qq nginx php-fpm php-mysql php-curl > /dev/null 2>&1
echo -e "${GREEN}  OK${NC}"

# 2. Detecter le socket PHP-FPM
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
FPM_SOCK="/run/php/php${PHP_VERSION}-fpm.sock"
if [ ! -S "$FPM_SOCK" ]; then
    # Chercher le socket
    FPM_SOCK=$(find /run/php/ -name "php*-fpm.sock" 2>/dev/null | head -1)
    if [ -z "$FPM_SOCK" ]; then
        # Demarrer PHP-FPM
        systemctl start "php${PHP_VERSION}-fpm" 2>/dev/null || true
        sleep 1
        FPM_SOCK="/run/php/php${PHP_VERSION}-fpm.sock"
    fi
fi
echo -e "${GREEN}  PHP-FPM socket: ${FPM_SOCK}${NC}"

# 3. Creer la config Nginx
echo -e "${YELLOW}[2/5] Configuration Nginx...${NC}"
cat > /etc/nginx/sites-available/radius-webhook << NGINX_EOF
server {
    listen 80;
    server_name _;
    root ${NODE_DIR};

    # Securite: uniquement l'IP du central
    allow ${CENTRAL_IP};
    deny all;

    # Uniquement webhook.php accessible
    location = /webhook.php {
        fastcgi_pass unix:${FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Bloquer tout le reste
    location / {
        return 404;
    }
}
NGINX_EOF

# Activer le site, supprimer les configs parasites
ln -sf /etc/nginx/sites-available/radius-webhook /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-enabled/radius-node 2>/dev/null
echo -e "${GREEN}  OK${NC}"

# 4. Permissions (www-data doit pouvoir lire les fichiers)
echo -e "${YELLOW}[3/6] Permissions...${NC}"
chmod 755 "${NODE_DIR}" "${NODE_DIR}/config" "${NODE_DIR}/src" 2>/dev/null || true
if [ -f "${NODE_DIR}/config/config.php" ]; then
    chmod 640 "${NODE_DIR}/config/config.php"
    chown root:www-data "${NODE_DIR}/config/config.php"
fi
echo -e "${GREEN}  OK${NC}"

# 5. Tester et demarrer Nginx
echo -e "${YELLOW}[4/6] Demarrage Nginx...${NC}"
systemctl start "php${PHP_VERSION}-fpm" 2>/dev/null || systemctl start php-fpm 2>/dev/null || true
nginx -t
systemctl enable nginx
systemctl start nginx 2>/dev/null || systemctl reload nginx
echo -e "${GREEN}  OK${NC}"

# 6. Firewall
echo -e "${YELLOW}[5/6] Firewall...${NC}"
if command -v ufw &> /dev/null; then
    ufw allow 80/tcp > /dev/null 2>&1 || true
    echo -e "${GREEN}  Port 80 ouvert (ufw)${NC}"
else
    echo -e "${YELLOW}  ufw non installe, verifier manuellement que le port 80 est ouvert${NC}"
fi

# 7. Test local
echo -e "${YELLOW}[6/6] Test local...${NC}"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/webhook.php 2>/dev/null || echo "000")
if [ "$RESPONSE" = "403" ] || [ "$RESPONSE" = "200" ]; then
    echo -e "${GREEN}  Webhook repond (HTTP ${RESPONSE})${NC}"
else
    echo -e "${RED}  Webhook ne repond pas (HTTP ${RESPONSE})${NC}"
    echo -e "${YELLOW}  Verifier: systemctl status nginx && systemctl status php${PHP_VERSION}-fpm${NC}"
fi

echo ""
echo -e "${GREEN}=== Installation terminee ===${NC}"
echo ""
echo -e "Prochaine etape sur le ${YELLOW}central${NC} :"
echo -e "  Page Serveurs RADIUS > modifier > webhook_port: ${YELLOW}80${NC}"
echo ""
echo -e "Test depuis le central :"
echo -e "  curl http://$(hostname -I | awk '{print $1}')/webhook.php?action=fup_status\\&user_id=1 -H 'X-Platform-Token: VOTRE_TOKEN'"
echo ""
