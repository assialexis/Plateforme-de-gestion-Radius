# Guide de Déploiement - RADIUS Node (Multi-Serveur)

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Architecture](#2-architecture)
3. [Prérequis VPS](#3-prérequis-vps)
4. [Installation pas à pas](#4-installation-pas-à-pas)
5. [Configuration](#5-configuration)
6. [Synchronisation des données](#6-synchronisation-des-données)
7. [API de la plateforme centrale](#7-api-de-la-plateforme-centrale)
8. [Sécurité](#8-sécurité)
9. [Supervision et monitoring](#9-supervision-et-monitoring)
10. [Dépannage](#10-dépannage)
11. [Structure des fichiers](#11-structure-des-fichiers)

---

## 1. Vue d'ensemble

L'architecture Multi-Serveur RADIUS permet de distribuer l'authentification RADIUS sur plusieurs VPS géographiquement répartis, tout en gardant une **plateforme centrale unique** pour l'administration.

### Pourquoi ?

- **Scalabilité** : répartir la charge d'authentification sur plusieurs serveurs
- **Proximité réseau** : réduire la latence UDP entre les routeurs MikroTik et le serveur RADIUS
- **Isolation géographique** : chaque zone/région a son propre serveur RADIUS
- **Résilience** : si un nœud tombe, les autres continuent de fonctionner

### Principe

Chaque **nœud RADIUS** :
- Possède sa propre base de données MySQL locale (copie partielle)
- Authentifie les utilisateurs localement (latence minimale)
- Se synchronise avec la plateforme centrale (pull toutes les 60s + push temps réel)
- Continue de fonctionner même si la plateforme est temporairement inaccessible

---

## 2. Architecture

```
┌─────────────────────────────────────────────────────┐
│            PLATEFORME CENTRALE (Web)                │
│                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │  Web UI  │  │ REST API │  │  MySQL Central   │  │
│  │ Alpine.js│  │  api.php │  │  (radius_db)     │  │
│  └──────────┘  └─────┬────┘  └──────────────────┘  │
│                      │                              │
│         ┌────────────┼────────────┐                 │
│         │            │            │                 │
│  ┌──────▼──────┐  ┌──▼────────┐  │                 │
│  │ node_sync   │  │ NodePush  │  │                 │
│  │ .php (pull) │  │ Service   │  │                 │
│  └──────┬──────┘  └──┬────────┘  │                 │
└─────────┼────────────┼───────────┘                 │
          │            │                              │
          │ HTTPS      │ HTTPS (webhook)              │
          │            │                              │
  ┌───────┴──┐   ┌────┴─────┐   ┌──────────┐        │
  │  RADIUS  │   │  RADIUS  │   │  RADIUS  │        │
  │  Node 1  │   │  Node 2  │   │  Node 3  │        │
  │ (VPS FR) │   │ (VPS TG) │   │ (VPS BJ) │        │
  │          │   │          │   │          │        │
  │ Zone A,D │   │ Zone B,E │   │ Zone C,F │        │
  └────▲─────┘   └────▲─────┘   └────▲─────┘        │
       │              │              │               │
   MikroTik       MikroTik      MikroTik            │
   routers        routers       routers              │
```

### Flux de synchronisation

**Pull (périodique - toutes les 60s)** : Le nœud appelle la plateforme.

```
Nœud RADIUS                          Plateforme Centrale
     │                                       │
     │── GET /node_sync.php?action=heartbeat ──►│
     │◄── { config_hash: "abc123" }          │
     │                                       │
     │   (si hash différent du local)        │
     │── GET /node_sync.php?action=pull ─────►│
     │◄── { zones, nas, vouchers, profiles } │
     │                                       │
     │── POST /node_sync.php?action=push ────►│
     │   { sessions, auth_logs, counters }   │
     │◄── { status: "ok" }                  │
```

**Push (temps réel - webhook)** : La plateforme notifie le nœud immédiatement.

```
Admin crée un voucher     Plateforme          Nœud RADIUS
       │                      │                    │
       │── POST /api/vouchers ►│                    │
       │                      │── POST /webhook.php ►│
       │                      │   event: voucher.created
       │                      │   data: { voucher } │
       │                      │◄── { status: ok }  │
       │◄── Voucher créé     │                    │
```

---

## 3. Prérequis VPS

### Système d'exploitation

- **Ubuntu 22.04 LTS** ou **24.04 LTS** (recommandé)
- **Debian 12** (Bookworm)
- Minimum : 1 vCPU, 1 Go RAM, 10 Go SSD

### Logiciels requis

| Logiciel | Version | Rôle |
|---|---|---|
| **PHP CLI** | 8.1+ | Exécute le serveur RADIUS (processus permanent) |
| **PHP-FPM** | 8.1+ | Exécute le webhook (receveur push) |
| **Extensions PHP** | mysql, sockets, curl, json, mbstring | Connexion DB, réseau RADIUS, sync API |
| **MySQL / MariaDB** | 8.0+ / 10.6+ | Base de données locale du nœud |
| **Nginx** | 1.18+ | Reverse proxy HTTPS pour le webhook |
| **Certbot** | - | Certificat SSL Let's Encrypt (production) |
| **cron** | - | Planification de la sync périodique (inclus par défaut) |
| **curl** | - | Communication avec la plateforme (inclus par défaut) |

### Ports réseau à ouvrir

| Port | Protocole | Direction | Usage |
|---|---|---|---|
| **1812** | UDP | Entrant | RADIUS Authentication (routeurs → nœud) |
| **1813** | UDP | Entrant | RADIUS Accounting (routeurs → nœud) |
| **443** | TCP | Entrant | Webhook HTTPS (plateforme → nœud) |
| **443** | TCP | Sortant | Sync HTTPS (nœud → plateforme) |
| **3306** | TCP | Local uniquement | MySQL (accès local seulement) |

### Commandes d'installation des prérequis

```bash
# Ubuntu 22.04 / 24.04
sudo apt update
sudo apt install -y \
    php-cli php-fpm php-mysql php-sockets php-curl php-json php-mbstring \
    mariadb-server \
    nginx \
    certbot python3-certbot-nginx

# Vérifier PHP
php -v        # Doit afficher PHP 8.x
php -m        # Doit contenir : curl, json, mbstring, mysqlnd, pdo_mysql, sockets

# Vérifier MariaDB
sudo systemctl status mariadb

# Vérifier Nginx
sudo systemctl status nginx
```

---

## 4. Installation pas à pas

### Méthode 1 : Script automatique (recommandé)

1. Depuis la plateforme web, aller dans **Serveurs RADIUS**
2. Ajouter un nouveau serveur (nom, IP du VPS)
3. Cliquer sur le bouton **"Script d'installation"**
4. Copier le script sur le VPS et exécuter :

```bash
chmod +x install-radius-node-RS-XXXXXXXX.sh
sudo ./install-radius-node-RS-XXXXXXXX.sh
```

Le script automatise toutes les étapes ci-dessous.

### Méthode 2 : Installation manuelle

#### Étape 1 : Préparer la base de données locale

```bash
# Sécuriser MariaDB
sudo mysql_secure_installation

# Créer la DB et l'utilisateur
sudo mysql -e "CREATE DATABASE radius_node CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'radius_node'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_FORT';"
sudo mysql -e "GRANT ALL PRIVILEGES ON radius_node.* TO 'radius_node'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

#### Étape 2 : Déployer les fichiers

```bash
# Créer le répertoire
sudo mkdir -p /opt/radius-node/{src,config,database,logs}

# Copier les fichiers du package radius-node/
sudo cp -r radius-node/* /opt/radius-node/

# Permissions
sudo chmod -R 755 /opt/radius-node
sudo chmod 600 /opt/radius-node/config/config.php
sudo chmod 777 /opt/radius-node/logs
```

#### Étape 3 : Importer le schéma SQL

```bash
sudo mysql radius_node < /opt/radius-node/database/node_schema.sql
```

Cela crée les tables suivantes sur le nœud :

| Table | Direction | Description |
|---|---|---|
| `zones` | Central → Nœud | Zones assignées au nœud |
| `nas` | Central → Nœud | Routeurs MikroTik des zones |
| `profiles` | Central → Nœud | Profils de connexion |
| `vouchers` | Central ↔ Nœud | Vouchers (sync bidirectionnelle) |
| `pppoe_users` | Central → Nœud | Utilisateurs PPPoE |
| `sessions` | Nœud → Central | Sessions hotspot (push vers central) |
| `auth_logs` | Nœud → Central | Logs d'authentification (push vers central) |
| `pppoe_sessions` | Nœud → Central | Sessions PPPoE (push vers central) |
| `sync_meta` | Local | Métadonnées de synchronisation |

#### Étape 4 : Configurer

```bash
sudo cp /opt/radius-node/config/config.example.php /opt/radius-node/config/config.php
sudo nano /opt/radius-node/config/config.php
```

Voir section [Configuration](#5-configuration) pour les détails.

#### Étape 5 : Configurer le service systemd

```bash
sudo cat > /etc/systemd/system/radius-node.service << 'EOF'
[Unit]
Description=RADIUS Node Server
After=network.target mysql.service mariadb.service

[Service]
Type=simple
ExecStart=/usr/bin/php /opt/radius-node/radius_server.php
Restart=always
RestartSec=5
User=root
StandardOutput=append:/opt/radius-node/logs/radius.log
StandardError=append:/opt/radius-node/logs/radius-error.log

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable radius-node
sudo systemctl start radius-node
```

#### Étape 6 : Configurer le cron de sync

```bash
# Sync toutes les minutes
(crontab -l 2>/dev/null; echo "* * * * * /usr/bin/php /opt/radius-node/sync_client.php >> /opt/radius-node/logs/sync.log 2>&1") | crontab -
```

#### Étape 7 : Configurer Nginx (webhook HTTPS)

```bash
sudo cat > /etc/nginx/sites-available/radius-node << 'EOF'
server {
    listen 443 ssl;
    server_name votre-domaine.com;  # ou IP du VPS

    ssl_certificate /etc/letsencrypt/live/votre-domaine.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/votre-domaine.com/privkey.pem;

    # Sécurité
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /opt/radius-node;

    # Seul le webhook est accessible
    location = /webhook.php {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /opt/radius-node/webhook.php;
        include fastcgi_params;
    }

    # Bloquer tout le reste
    location / {
        return 403;
    }
}
EOF

sudo ln -sf /etc/nginx/sites-available/radius-node /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

Pour le certificat SSL :

```bash
# Avec un nom de domaine
sudo certbot --nginx -d votre-domaine.com

# Sans domaine (certificat auto-signé pour test)
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/radius-node.key \
    -out /etc/ssl/certs/radius-node.crt
```

#### Étape 8 : Premier test

```bash
# Lancer une sync manuelle
php /opt/radius-node/sync_client.php

# Vérifier le serveur RADIUS
sudo systemctl status radius-node

# Vérifier les logs
tail -f /opt/radius-node/logs/sync.log
tail -f /opt/radius-node/logs/radius.log
```

---

## 5. Configuration

### Fichier `/opt/radius-node/config/config.php`

```php
<?php
return [
    // ===== Connexion à la plateforme centrale =====
    'platform' => [
        'url'            => 'https://votre-plateforme.com',  // URL de la plateforme
        'server_code'    => 'RS-XXXXXXXX',                   // Code serveur (depuis l'UI)
        'sync_token'     => 'abc123...',                     // Token sync (nœud → plateforme)
        'platform_token' => 'def456...',                     // Token webhook (plateforme → nœud)
        'sync_interval'  => 60,                              // Sync toutes les 60 secondes
    ],

    // ===== Base de données locale =====
    'database' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'dbname'   => 'radius_node',
        'username' => 'radius_node',
        'password' => 'MOT_DE_PASSE_FORT',
        'charset'  => 'utf8mb4',
    ],

    // ===== Serveur RADIUS =====
    'radius' => [
        'auth_port' => 1812,     // Port authentification
        'acct_port' => 1813,     // Port accounting
        'listen_ip' => '0.0.0.0', // Écouter sur toutes les interfaces
    ],

    // ===== Options =====
    'options' => [
        'debug'                    => false,              // true pour debug (verbose logs)
        'log_file'                 => '/opt/radius-node/logs/radius.log',
        'default_session_timeout'  => 86400,              // 24h en secondes
        'default_idle_timeout'     => 300,                // 5 min en secondes
        'acct_interim_interval'    => 60,                 // Intervalle accounting en secondes
    ],
];
```

### Paramètres importants

| Paramètre | Description | Où le trouver |
|---|---|---|
| `server_code` | Identifiant unique du nœud (ex: `RS-A1B2C3D4`) | Plateforme → Serveurs RADIUS → colonne Code |
| `sync_token` | Token pour que le nœud s'authentifie auprès de la plateforme | Plateforme → Serveurs RADIUS → icône clé → "Token Sync" |
| `platform_token` | Token pour que la plateforme s'authentifie auprès du webhook | Plateforme → Serveurs RADIUS → icône clé → "Token Plateforme" |
| `url` | URL complète de la plateforme centrale | L'URL que vous utilisez pour accéder à la plateforme web |

---

## 6. Synchronisation des données

### Cycle de sync (toutes les 60 secondes)

Le fichier `sync_client.php` exécute ce cycle :

```
1. HEARTBEAT → Envoie un signe de vie à la plateforme
                Reçoit le config_hash actuel

2. PULL      → Compare le config_hash local vs distant
                Si différent : télécharge zones, NAS, profils, vouchers, PPPoE
                Si identique : skip (économie de bande passante)

3. PUSH      → Envoie les données locales non synchronisées :
                - Sessions (hotspot + PPPoE) avec flag synced=0
                - Logs d'authentification avec flag synced=0
                - Compteurs vouchers (temps utilisé, data utilisée)
```

### Push temps réel (webhook)

Quand un admin effectue une action sur la plateforme, le changement est poussé instantanément vers le(s) nœud(s) concerné(s) :

| Action sur la plateforme | Événement webhook | Nœuds notifiés |
|---|---|---|
| Créer un voucher | `voucher.created` | Nœud de la zone du voucher |
| Modifier un voucher | `voucher.updated` | Nœud de la zone du voucher |
| Supprimer un voucher | `voucher.deleted` | Nœud de la zone du voucher |
| Désactiver/Réactiver | `voucher.updated` | Nœud de la zone du voucher |
| Créer/Modifier un profil | `profile.created/updated` | Nœud de la zone du profil |
| Supprimer un profil | `profile.deleted` | Nœud de la zone du profil |
| Ajouter un NAS | `nas.created` | Nœud de la zone du NAS |
| Modifier un NAS | `nas.updated` | Nœud de la zone du NAS |
| Supprimer un NAS | `nas.deleted` | Nœud de la zone du NAS |
| Créer/Modifier une zone | `zone.created/updated` | Nœud assigné à la zone |
| Supprimer une zone | `zone.deleted` | Nœud assigné à la zone |
| Modifier un user PPPoE | `pppoe_user.updated` | Nœud de la zone de l'utilisateur |

### Résilience

- Si le **push échoue** (nœud temporairement inaccessible), le changement sera récupéré au **prochain pull** (dans les 60 secondes)
- Si la **plateforme est inaccessible**, le nœud continue d'authentifier avec ses **données locales**
- Les sessions et logs sont **stockés localement** avec un flag `synced=0` et poussés dès que la connexion est rétablie

---

## 7. API de la plateforme centrale

### Endpoints de sync (appelés par les nœuds)

Base URL : `https://votre-plateforme.com/node_sync.php`

| Endpoint | Méthode | Auth | Description |
|---|---|---|---|
| `?action=heartbeat&server=CODE` | GET | `X-Node-Token` | Heartbeat + récupère le config_hash |
| `?action=pull&server=CODE&hash=HASH` | GET | `X-Node-Token` | Pull config (gzip si supporté) |
| `?action=push&server=CODE` | POST | `X-Node-Token` | Push sessions/logs vers le central |

### Endpoints admin (gestion serveurs RADIUS)

Base URL : `https://votre-plateforme.com/api.php?route=`

| Endpoint | Méthode | Description |
|---|---|---|
| `/radius-servers` | GET | Lister tous les serveurs |
| `/radius-servers` | POST | Ajouter un serveur |
| `/radius-servers/statuses` | GET | État de tous les serveurs |
| `/radius-servers/generate-code` | GET | Générer un code unique |
| `/radius-servers/{id}` | GET | Détails d'un serveur + zones |
| `/radius-servers/{id}` | PUT | Modifier un serveur |
| `/radius-servers/{id}` | DELETE | Supprimer un serveur |
| `/radius-servers/{id}/status` | GET | État détaillé d'un serveur |
| `/radius-servers/{id}/zones` | GET | Zones assignées au serveur |
| `/radius-servers/{id}/regenerate-token` | POST | Régénérer un token (sync ou platform) |
| `/radius-servers/{id}/toggle` | POST | Activer/Désactiver le serveur |
| `/radius-servers/{id}/install-script` | GET | Télécharger le script d'installation |

### Endpoint webhook (sur le nœud)

URL : `https://ip-du-vps/webhook.php`

| Header | Valeur | Description |
|---|---|---|
| `X-Platform-Token` | Token plateforme | Authentification |
| `Content-Type` | `application/json` | Format du payload |

Payload :

```json
{
    "event": "voucher.created",
    "data": { ... },
    "timestamp": 1709472000,
    "server_code": "RS-A1B2C3D4"
}
```

---

## 8. Sécurité

### Tokens d'authentification

Le système utilise **2 tokens distincts** par serveur (128 caractères chacun) :

| Token | Direction | Usage |
|---|---|---|
| **sync_token** | Nœud → Plateforme | Le nœud s'authentifie pour pull/push via `X-Node-Token` |
| **platform_token** | Plateforme → Nœud | La plateforme s'authentifie pour les webhooks via `X-Platform-Token` |

Les tokens sont générés aléatoirement (`bin2hex(random_bytes(64))`) et peuvent être régénérés depuis l'UI.

### Bonnes pratiques

1. **HTTPS obligatoire** : Toutes les communications nœud ↔ plateforme sont chiffrées via HTTPS
2. **Certificats valides** : Utiliser Let's Encrypt en production (pas de certificats auto-signés)
3. **Mettre `ssl_verifypeer` à `true`** en production dans `sync_client.php` (ligne 139) et `NodePushService.php` (ligne 140)
4. **Firewall** : N'ouvrir que les ports nécessaires (1812/UDP, 1813/UDP, 443/TCP)
5. **MySQL local uniquement** : Ne pas exposer le port 3306 à l'extérieur
6. **Isolation des données** : Chaque nœud ne reçoit que les données de ses zones assignées
7. **Régénérer les tokens** régulièrement ou en cas de compromission suspectée

### Pare-feu (UFW)

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 1812/udp    # RADIUS Auth
sudo ufw allow 1813/udp    # RADIUS Accounting
sudo ufw allow 443/tcp     # Webhook HTTPS
sudo ufw enable
```

---

## 9. Supervision et monitoring

### Depuis la plateforme web

La page **Serveurs RADIUS** affiche pour chaque nœud :
- **Statut** : Online (heartbeat < 5 min), Offline, Setup
- **Dernière sync** : Date/heure du dernier pull réussi
- **Dernier heartbeat** : Date/heure du dernier signe de vie
- **Zones** : Nombre de zones assignées
- **NAS** : Nombre de routeurs MikroTik dans ses zones

### Logs sur le nœud

| Fichier | Contenu |
|---|---|
| `/opt/radius-node/logs/sync.log` | Journal de sync (pull/push, erreurs réseau) |
| `/opt/radius-node/logs/radius.log` | Journal du serveur RADIUS (auth accept/reject) |

### Vérifier l'état du serveur

```bash
# État du service RADIUS
sudo systemctl status radius-node

# Dernières lignes de sync
tail -20 /opt/radius-node/logs/sync.log

# Logs RADIUS en temps réel
tail -f /opt/radius-node/logs/radius.log

# Vérifier les ports UDP
sudo ss -ulnp | grep -E '1812|1813'

# Vérifier Nginx/webhook
sudo systemctl status nginx
curl -k https://localhost/webhook.php  # Doit retourner 403 (pas de token)

# Vérifier la DB locale
mysql -u radius_node -p radius_node -e "SELECT COUNT(*) FROM vouchers;"
mysql -u radius_node -p radius_node -e "SELECT * FROM sync_meta;"
```

### Tester la sync manuellement

```bash
php /opt/radius-node/sync_client.php
```

Sortie attendue :

```
[2026-03-03 14:30:00] === Sync cycle start ===
[2026-03-03 14:30:00] 1. Sending heartbeat...
[2026-03-03 14:30:00]    Heartbeat OK - Remote hash: a1b2c3d4e5
[2026-03-03 14:30:00] 2. Config unchanged, skipping pull
[2026-03-03 14:30:00] 3. Pushing local data...
[2026-03-03 14:30:00]    Push OK - Sessions: 5, Logs: 12, Updates: 2
[2026-03-03 14:30:00] === Sync cycle end ===
```

---

## 10. Dépannage

### Le nœud n'apparaît pas "Online" sur la plateforme

1. Vérifier que le cron tourne : `crontab -l`
2. Tester la sync manuellement : `php /opt/radius-node/sync_client.php`
3. Vérifier la connectivité : `curl -I https://votre-plateforme.com`
4. Vérifier le token dans `config.php`
5. Consulter les logs : `tail -20 /opt/radius-node/logs/sync.log`

### Erreur "INVALID_TOKEN" dans les logs de sync

- Le `sync_token` dans `config.php` ne correspond pas à celui de la plateforme
- Depuis la plateforme, aller dans Serveurs RADIUS → icône clé → copier le token sync

### Erreur "DB_UNAVAILABLE"

- MariaDB n'est pas démarré : `sudo systemctl start mariadb`
- Mot de passe incorrect dans `config.php`
- La base `radius_node` n'existe pas : `mysql -e "SHOW DATABASES;"`

### Le webhook ne reçoit pas les push

1. Vérifier que Nginx tourne : `sudo systemctl status nginx`
2. Vérifier le certificat SSL : `curl -v https://ip-du-vps/webhook.php`
3. Vérifier PHP-FPM : `sudo systemctl status php8.2-fpm`
4. Vérifier le `platform_token` dans `config.php` (doit correspondre à celui de la plateforme)
5. Vérifier les ports : `sudo ss -tlnp | grep 443`

### Les routeurs MikroTik ne s'authentifient pas

1. Vérifier que le serveur RADIUS tourne : `sudo systemctl status radius-node`
2. Vérifier les ports UDP : `sudo ss -ulnp | grep 1812`
3. Vérifier que les routeurs ont bien l'IP du VPS comme serveur RADIUS
4. Vérifier le secret RADIUS dans la config des routeurs
5. Consulter les logs : `tail -f /opt/radius-node/logs/radius.log`

### Sync pull retourne "no_change" mais les données manquent

- Forcer un pull complet en vidant le hash local :
  ```bash
  mysql -u radius_node -p radius_node -e "UPDATE sync_meta SET config_hash = '' WHERE id = 1;"
  php /opt/radius-node/sync_client.php
  ```

### Redémarrer tous les services

```bash
sudo systemctl restart radius-node
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart mariadb
```

---

## 11. Structure des fichiers

### Package RADIUS Node (`/opt/radius-node/`)

```
/opt/radius-node/
├── config/
│   ├── config.example.php    # Template de configuration
│   └── config.php            # Configuration active (à créer)
├── database/
│   └── node_schema.sql       # Schéma MySQL du nœud
├── src/
│   ├── RadiusServer.php      # Serveur RADIUS (auth + accounting UDP)
│   ├── RadiusPacket.php      # Encodage/décodage paquets RADIUS
│   └── RadiusDatabase.php    # Accès base de données
├── logs/
│   ├── radius.log            # Logs du serveur RADIUS
│   ├── radius-error.log      # Erreurs du serveur RADIUS
│   └── sync.log              # Logs de synchronisation
├── radius_server.php         # Point d'entrée du serveur RADIUS
├── sync_client.php           # Client de sync (cron toutes les 60s)
└── webhook.php               # Récepteur de push temps réel
```

### Fichiers côté plateforme centrale

| Fichier | Rôle |
|---|---|
| `database/migrations/048_radius_servers.sql` | Migration DB (table `radius_servers` + colonne `zones.radius_server_id`) |
| `src/Api/RadiusServerController.php` | CRUD API des serveurs RADIUS + script d'installation |
| `src/Services/NodePushService.php` | Envoi de webhooks push vers les nœuds |
| `web/node_sync.php` | Endpoint de sync pull (heartbeat, pull, push) |
| `web/views/radius-servers.php` | Interface web de gestion des serveurs RADIUS |

### Schéma de la base de données

**Table `radius_servers` (plateforme centrale)** :

| Colonne | Type | Description |
|---|---|---|
| `id` | INT PK | Identifiant |
| `name` | VARCHAR(100) | Nom du serveur (ex: "VPS France") |
| `code` | VARCHAR(50) UNIQUE | Code unique (ex: "RS-A1B2C3D4") |
| `host` | VARCHAR(255) | IP ou hostname du VPS |
| `webhook_port` | INT | Port HTTPS du webhook (défaut: 443) |
| `webhook_path` | VARCHAR(255) | Chemin du webhook (défaut: /webhook.php) |
| `sync_token` | VARCHAR(128) | Token auth nœud → plateforme |
| `platform_token` | VARCHAR(128) | Token auth plateforme → nœud |
| `status` | ENUM | online, offline, setup |
| `last_sync_at` | TIMESTAMP | Dernière sync réussie |
| `last_heartbeat_at` | TIMESTAMP | Dernier heartbeat reçu |
| `sync_interval` | INT | Intervalle en secondes (défaut: 60) |
| `is_active` | TINYINT | Actif ou non |

**Colonne ajoutée à `zones`** :

| Colonne | Type | Description |
|---|---|---|
| `radius_server_id` | INT FK NULL | Serveur RADIUS hébergeant cette zone |

---

## Annexe : Configurer un routeur MikroTik pour un nœud distant

Quand un nœud RADIUS est sur un VPS distant, les routeurs MikroTik doivent pointer vers l'IP du VPS (au lieu de l'IP locale) :

```routeros
/radius
add address=IP_DU_VPS secret=SECRET_RADIUS service=hotspot,ppp \
    authentication-port=1812 accounting-port=1813 timeout=3s
```

Assurez-vous que :
- Le **secret RADIUS** correspond à celui configuré dans le NAS sur la plateforme
- Le **NAS** est assigné à une **zone** liée au bon **serveur RADIUS**
- Les ports UDP 1812/1813 du VPS sont accessibles depuis le routeur
