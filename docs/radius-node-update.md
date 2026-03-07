# Mise à jour du Noeud RADIUS depuis GitHub

## Table des matières

1. [Méthodes de mise à jour](#1-méthodes-de-mise-à-jour)
2. [Méthode 1 : Git Pull (recommandée)](#2-méthode-1--git-pull-recommandée)
3. [Méthode 2 : Download package via API](#3-méthode-2--download-package-via-api)
4. [Redémarrage du serveur RADIUS](#4-redémarrage-du-serveur-radius)
5. [Automatisation](#5-automatisation)

---

## 1. Méthodes de mise à jour

Le code du noeud RADIUS (`/opt/radius-node/`) peut être mis à jour de deux façons :

| Méthode | Avantage | Inconvénient |
|---------|----------|--------------|
| **Git Pull** | Simple, rapide, versionnée | Nécessite git sur le noeud |
| **Download API** | Pas besoin de git | Manuel, pas de versioning |

> **Important** : La config locale (`config/config.php`) n'est **jamais écrasée** par les mises à jour car elle est dans `.gitignore`.

---

## 2. Méthode 1 : Git Pull (recommandée)

### Installation initiale (une seule fois)

```bash
# Installer git
apt update && apt install -y git

# Sauvegarder la config existante
cp /opt/radius-node/config/config.php /tmp/config-backup.php

# Cloner le dépôt (sparse checkout : uniquement radius-node/)
cd /opt
rm -rf radius-node
git clone --no-checkout https://github.com/assialexis/Plateforme-de-gestion-Radius.git radius-node
cd radius-node
git sparse-checkout init --cone
git sparse-checkout set radius-node
git checkout main

# Déplacer les fichiers du sous-dossier vers la racine
mv radius-node/* radius-node/.* . 2>/dev/null
rmdir radius-node

# Restaurer la config
cp /tmp/config-backup.php config/config.php
```

#### Alternative : clone simple (si le sparse checkout pose problème)

```bash
cd /opt
rm -rf radius-node
git clone https://github.com/assialexis/Plateforme-de-gestion-Radius.git radius-node-repo
ln -sf /opt/radius-node-repo/radius-node /opt/radius-node

# Restaurer la config
cp /tmp/config-backup.php /opt/radius-node/config/config.php
```

### Mise à jour courante

```bash
cd /opt/radius-node && git pull origin main
```

Puis [redémarrer le serveur RADIUS](#4-redémarrage-du-serveur-radius).

---

## 3. Méthode 2 : Download package via API

Le serveur central expose un endpoint pour télécharger le package complet du noeud.

```bash
# Variables (depuis /opt/radius-node/config/config.php)
TOKEN="votre_sync_token"
URL="https://radius.mikroot.com"
SERVER="RS-XXXXXXXX"

# Télécharger et extraire
curl -k -H "X-Node-Token: $TOKEN" \
  "$URL/node_sync.php?action=download&server=$SERVER" \
  -o /tmp/radius-node.tar.gz

# Extraire (la config n'est pas incluse dans le package)
cd /opt/radius-node && tar xzf /tmp/radius-node.tar.gz

# Nettoyage
rm /tmp/radius-node.tar.gz
```

Puis [redémarrer le serveur RADIUS](#4-redémarrage-du-serveur-radius).

### Commande complète (copier-coller)

```bash
# Lire la config automatiquement
eval $(php -r "\$c=require'/opt/radius-node/config/config.php'; echo \"TOKEN={\$c['platform']['sync_token']}\nURL={\$c['platform']['url']}\nSERVER={\$c['platform']['server_code']}\";")

curl -k -H "X-Node-Token: $TOKEN" "$URL/node_sync.php?action=download&server=$SERVER" -o /tmp/radius-node.tar.gz && cd /opt/radius-node && tar xzf /tmp/radius-node.tar.gz && rm /tmp/radius-node.tar.gz && echo "OK - Mis à jour!"
```

---

## 4. Redémarrage du serveur RADIUS

Après chaque mise à jour du code, il faut redémarrer le serveur RADIUS :

```bash
# Arrêter le processus en cours
pkill -f radius_server.php

# Attendre l'arrêt
sleep 2

# Relancer
cd /opt/radius-node && nohup php radius_server.php >> /var/log/radius-node.log 2>&1 &

# Vérifier qu'il tourne
sleep 1 && ps aux | grep radius_server
```

### Avec systemd (recommandé en production)

Créer le service `/etc/systemd/system/radius-node.service` :

```ini
[Unit]
Description=RADIUS Node Server
After=network.target mysql.service

[Service]
Type=simple
User=root
WorkingDirectory=/opt/radius-node
ExecStart=/usr/bin/php radius_server.php
Restart=always
RestartSec=5
StandardOutput=append:/var/log/radius-node.log
StandardError=append:/var/log/radius-node.log

[Install]
WantedBy=multi-user.target
```

```bash
# Activer le service
systemctl daemon-reload
systemctl enable radius-node
systemctl start radius-node

# Après une mise à jour :
systemctl restart radius-node

# Vérifier le statut :
systemctl status radius-node
```

---

## 5. Automatisation

### Script de mise à jour (`/opt/radius-node/update.sh`)

```bash
#!/bin/bash
# Mise à jour automatique du noeud RADIUS depuis GitHub
set -e

INSTALL_DIR="/opt/radius-node"
LOG="/var/log/radius-node-update.log"

echo "[$(date)] Début mise à jour..." >> $LOG

cd $INSTALL_DIR

# Pull les changements
git pull origin main >> $LOG 2>&1

# Redémarrer le serveur RADIUS
if systemctl is-active --quiet radius-node; then
    systemctl restart radius-node
    echo "[$(date)] Service redémarré via systemd" >> $LOG
else
    pkill -f radius_server.php 2>/dev/null || true
    sleep 2
    nohup php radius_server.php >> /var/log/radius-node.log 2>&1 &
    echo "[$(date)] Processus redémarré manuellement" >> $LOG
fi

echo "[$(date)] Mise à jour terminée" >> $LOG
```

```bash
chmod +x /opt/radius-node/update.sh
```

### Cron : mise à jour automatique toutes les heures

```bash
# Ajouter au crontab
crontab -e
```

```
# Mise à jour du noeud RADIUS (toutes les heures)
0 * * * * /opt/radius-node/update.sh
```

### Webhook GitHub (mise à jour instantanée au push)

Si vous souhaitez une mise à jour instantanée à chaque push sur GitHub, configurez un webhook :

1. Sur GitHub : Settings → Webhooks → Add webhook
   - URL : `https://<IP_NOEUD>:9443/webhook-update`
   - Content type : `application/json`
   - Secret : un token aléatoire
   - Events : `push`

2. Créer un mini-serveur webhook sur le noeud (`/opt/radius-node/webhook-update.php`) :

```php
<?php
$secret = 'VOTRE_SECRET_WEBHOOK';
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!hash_equals('sha256=' . hash_hmac('sha256', $payload, $secret), $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}

$data = json_decode($payload, true);
if (($data['ref'] ?? '') === 'refs/heads/main') {
    exec('/opt/radius-node/update.sh > /dev/null 2>&1 &');
    echo json_encode(['status' => 'updating']);
}
```

---

## Résumé des commandes utiles

| Action | Commande |
|--------|----------|
| Mettre à jour | `cd /opt/radius-node && git pull origin main` |
| Redémarrer | `systemctl restart radius-node` |
| Voir les logs | `tail -f /var/log/radius-node.log` |
| Statut du service | `systemctl status radius-node` |
| Voir la version | `cd /opt/radius-node && git log --oneline -5` |
| Revenir en arrière | `cd /opt/radius-node && git checkout HEAD~1` |
