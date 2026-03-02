# RADIUS Manager

Système de gestion de hotspot WiFi avec serveur RADIUS intégré en PHP.

## Fonctionnalités

- **Serveur RADIUS PHP** : Authentification (port 1812) et Accounting (port 1813)
- **Interface Web** : Dashboard moderne avec Tailwind CSS
- **Gestion des Vouchers** : Création, génération en masse, import CSV
- **Profils** : Templates de vouchers prédéfinis
- **Sessions** : Suivi en temps réel, déconnexion à distance
- **NAS** : Gestion des équipements MikroTik/routeurs
- **Logs** : Journal d'authentification avec export CSV
- **API REST** : Intégration avec d'autres systèmes

## Prérequis

- PHP 8.0+
- MySQL/MariaDB 5.7+
- Extensions PHP : `pdo_mysql`, `sockets`
- Droits root pour les ports RADIUS (< 1024)

## Installation

### 1. Cloner/Copier les fichiers

```bash
cd /var/www/html
git clone <repo> radius-server
# ou copier manuellement
```

### 2. Exécuter l'installation

```bash
cd radius-server
php install.php
```

L'installation interactive vous guidera pour :
- Configurer la connexion MySQL
- Créer la base de données
- Initialiser les tables

### 3. Configuration Apache/Nginx

**Apache (.htaccess fourni)** :
```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html/radius-server/web
    <Directory /var/www/html/radius-server/web>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx** :
```nginx
server {
    listen 80;
    root /var/www/html/radius-server/web;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. Démarrer le serveur RADIUS

```bash
# En premier plan (pour debug)
sudo php radius_server.php

# En arrière-plan (production)
sudo php radius_server.php daemon
```

### 5. Créer un service systemd (Linux)

```bash
sudo nano /etc/systemd/system/radius-manager.service
```

```ini
[Unit]
Description=RADIUS Manager Server
After=network.target mysql.service

[Service]
Type=simple
User=root
WorkingDirectory=/var/www/html/radius-server
ExecStart=/usr/bin/php radius_server.php
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable radius-manager
sudo systemctl start radius-manager
```

## Configuration MikroTik

### 1. Configurer le serveur RADIUS

```
/radius add service=hotspot address=<IP_SERVEUR> secret=testing123
```

### 2. Configurer le Hotspot

```
/ip hotspot profile set default use-radius=yes
```

### 3. Activer le Accounting

```
/ip hotspot profile set default accounting=yes
```

### 4. Installer le système de commandes à distance

Le NAS peut envoyer des commandes aux routeurs MikroTik via un système de polling.

**Installation sur le routeur :**

Copiez et exécutez le script suivant sur votre routeur MikroTik :

```
/tool fetch url="http://NAS_IP/web/mikrotik-setup.rsc" mode=http
/import file-name="mikrotik-setup.rsc"
```

Ou manuellement :

```
# Supprimer ancien script/scheduler si existant
/system script remove [find name="nas-cmd"]
/system scheduler remove [find name="nas-cmd"]

# Créer le script de polling
/system script add name="nas-cmd" source={
    :local routerId [/system identity get name]
    /tool fetch url=("http://NAS_IP/web/fetch_cmd.php?router=".$routerId) mode=http output=file dst-path="nas-cmd.rsc"
    /import file-name="nas-cmd.rsc"
    /file remove "nas-cmd.rsc"
}

# Créer le scheduler (poll toutes les 10 secondes)
:delay 1
/system scheduler add name="nas-cmd" interval="10s" on-event="/system script run nas-cmd"
```

**Remplacez `NAS_IP` par l'adresse IP de votre serveur NAS.**

---

## Système de Commandes MikroTik (Command Polling)

### Présentation

Le système de commandes permet au NAS d'envoyer des commandes RouterOS aux routeurs MikroTik sans connexion API directe. Les routeurs récupèrent périodiquement les commandes en attente via HTTP.

### Architecture

```
┌─────────────────┐         ┌─────────────────┐         ┌─────────────────┐
│                 │         │                 │         │                 │
│   NAS Server    │◄────────│   fetch_cmd.php │◄────────│    MikroTik     │
│                 │         │                 │  HTTP   │    Router       │
│  Crée fichiers  │         │  Retourne .rsc  │  Poll   │                 │
│  .rsc           │         │  Supprime après │  10s    │  Exécute .rsc   │
│                 │         │                 │         │                 │
└─────────────────┘         └─────────────────┘         └─────────────────┘
```

### Fonctionnement

1. **Le NAS crée une commande** : Un fichier `.rsc` est créé dans `/web/mikrotik-commands/{router_id}/`
2. **Le routeur poll** : Toutes les 10 secondes, le routeur appelle `fetch_cmd.php?router={identity}`
3. **fetch_cmd.php répond** : Retourne le contenu des fichiers `.rsc` et les supprime
4. **Le routeur exécute** : Le routeur importe et exécute les commandes RouterOS

### Structure des fichiers

```
web/
├── fetch_cmd.php              # API de récupération des commandes
├── mikrotik-setup.rsc         # Script d'installation pour MikroTik
└── mikrotik-commands/         # Dossier des commandes en attente
    ├── ROUTER-1/              # Dossier par router_id (system identity)
    │   ├── cmd-001.rsc
    │   └── cmd-002.rsc
    └── ROUTER-2/
        └── disconnect-user.rsc
```

### Créer une commande depuis PHP

```php
/**
 * Envoyer une commande à un routeur MikroTik
 *
 * @param string $routerId L'identité du routeur (system identity)
 * @param string $command  Les commandes RouterOS à exécuter
 * @param string $filename Nom du fichier (optionnel)
 */
function sendMikroTikCommand(string $routerId, string $command, ?string $filename = null): bool
{
    // Sanitize router ID
    $routerId = preg_replace('/[^a-zA-Z0-9_-]/', '', $routerId);

    // Dossier des commandes
    $commandsDir = __DIR__ . '/web/mikrotik-commands/' . $routerId;

    // Créer le dossier si nécessaire
    if (!is_dir($commandsDir)) {
        mkdir($commandsDir, 0777, true);
    }

    // Générer un nom de fichier unique
    if (!$filename) {
        $filename = 'cmd-' . date('YmdHis') . '-' . uniqid() . '.rsc';
    }

    // Écrire la commande
    return file_put_contents($commandsDir . '/' . $filename, $command) !== false;
}

// Exemple d'utilisation
sendMikroTikCommand('NAS-ROUTER-01', '
:log info "Commande depuis NAS"
/ip firewall filter add chain=input action=drop comment="Test NAS"
');
```

### Exemples de commandes

#### Déconnecter un utilisateur PPPoE

```php
$username = 'client001';
$command = <<<RSC
:log info "NAS: Deconnexion utilisateur {$username}"
:local activeId [/ppp active find name="{$username}"]
:if (\$activeId != "") do={
    /ppp active remove \$activeId
    :log info "NAS: Utilisateur {$username} deconnecte"
} else={
    :log info "NAS: Utilisateur {$username} non connecte"
}
RSC;

sendMikroTikCommand($routerId, $command);
```

#### Changer le débit d'un utilisateur (FUP)

```php
$username = 'client001';
$newRate = '2M/2M';  // Débit réduit après FUP
$command = <<<RSC
:log info "NAS: Application FUP pour {$username}"
/ppp secret set [find name="{$username}"] rate-limit="{$newRate}"
:log info "NAS: Debit {$username} limite a {$newRate}"
RSC;

sendMikroTikCommand($routerId, $command);
```

#### Créer un utilisateur PPPoE

```php
$user = [
    'name' => 'nouveau_client',
    'password' => 'MotDePasse123',
    'profile' => 'pppoe-10M',
    'comment' => 'Client #12345'
];

$command = <<<RSC
:log info "NAS: Creation utilisateur {$user['name']}"
/ppp secret add name="{$user['name']}" password="{$user['password']}" profile="{$user['profile']}" service=pppoe comment="{$user['comment']}"
:log info "NAS: Utilisateur {$user['name']} cree"
RSC;

sendMikroTikCommand($routerId, $command);
```

#### Activer/Désactiver un utilisateur

```php
// Désactiver
$command = <<<RSC
/ppp secret set [find name="{$username}"] disabled=yes
:log info "NAS: {$username} desactive"
RSC;

// Activer
$command = <<<RSC
/ppp secret set [find name="{$username}"] disabled=no
:log info "NAS: {$username} active"
RSC;

sendMikroTikCommand($routerId, $command);
```

#### Ajouter une règle firewall

```php
$ip = '192.168.1.100';
$command = <<<RSC
:log info "NAS: Blocage IP {$ip}"
/ip firewall filter add chain=forward src-address={$ip} action=drop comment="Bloque par NAS"
RSC;

sendMikroTikCommand($routerId, $command);
```

### Sécurité

1. **Validation du router_id** : Le router_id est sanitizé (caractères alphanumériques uniquement)
2. **Fichiers temporaires** : Les commandes sont supprimées immédiatement après récupération
3. **Pas d'accès direct** : Le routeur initie la connexion (pas de port ouvert sur MikroTik)
4. **HTTPS recommandé** : En production, utilisez HTTPS pour chiffrer les commandes

### Dépannage

#### Les commandes ne s'exécutent pas

1. Vérifier que le scheduler est actif sur le routeur :
   ```
   /system scheduler print
   ```

2. Vérifier les logs du routeur :
   ```
   /log print where message~"NAS"
   ```

3. Tester manuellement le fetch :
   ```
   /tool fetch url="http://NAS_IP/web/fetch_cmd.php?router=VOTRE_ROUTER_ID" mode=http
   ```

#### Erreur "no such item" au démarrage

Le script doit être créé avant le scheduler. Réinstallez avec :
```
/system scheduler remove [find name="nas-cmd"]
/system script remove [find name="nas-cmd"]
/tool fetch url="http://NAS_IP/web/mikrotik-setup.rsc" mode=http
/import file-name="mikrotik-setup.rsc"
```

#### Vérifier les commandes en attente

```bash
# Sur le serveur NAS
ls -la web/mikrotik-commands/ROUTER_ID/
```

```php
// Via PHP
$routerId = 'NAS-ROUTER-01';
$files = glob("web/mikrotik-commands/{$routerId}/*.rsc");
print_r($files);
```

## Connexion par défaut

- **URL** : `http://votre-serveur/web/`
- **Utilisateur** : `admin`
- **Mot de passe** : `admin123`

⚠️ **Changez le mot de passe par défaut immédiatement !**

## API REST

### Endpoints principaux

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/dashboard/stats` | Statistiques dashboard |
| GET | `/api/vouchers` | Liste des vouchers |
| POST | `/api/vouchers` | Créer un voucher |
| POST | `/api/vouchers/generate` | Générer des vouchers |
| GET | `/api/vouchers/{id}` | Détails voucher |
| DELETE | `/api/vouchers/{id}` | Supprimer voucher |
| POST | `/api/vouchers/{id}/reset` | Réinitialiser voucher |
| GET | `/api/nas` | Liste des NAS |
| GET | `/api/sessions/active` | Sessions actives |
| GET | `/api/profiles` | Liste des profils |
| GET | `/api/logs` | Logs d'authentification |

### Exemple d'utilisation

```bash
# Générer 10 vouchers
curl -X POST http://localhost/api.php/vouchers/generate \
  -H "Content-Type: application/json" \
  -d '{"count": 10, "prefix": "WIFI", "profile_id": 1}'
```

## Structure du projet

```
radius-server/
├── config/
│   └── config.php          # Configuration
├── database/
│   └── schema.sql          # Schéma MySQL
├── src/
│   ├── Radius/
│   │   ├── RadiusPacket.php    # Encodage/décodage RADIUS
│   │   ├── RadiusDatabase.php  # Accès base de données
│   │   └── RadiusServer.php    # Serveur RADIUS
│   ├── Api/
│   │   ├── Router.php          # Routeur API
│   │   └── *Controller.php     # Contrôleurs
│   └── Utils/
│       └── helpers.php         # Fonctions utilitaires
├── web/
│   ├── index.php           # Point d'entrée web
│   ├── api.php             # Point d'entrée API
│   ├── login.php           # Authentification
│   └── views/              # Templates HTML
├── logs/                   # Logs (créé automatiquement)
├── radius_server.php       # Démon RADIUS
├── install.php             # Script d'installation
└── README.md
```

## Sécurité

- Authentification admin avec sessions PHP
- Protection CSRF sur les formulaires
- Prepared statements SQL
- Rate limiting API
- Verrouillage compte après 5 tentatives
- Mots de passe hashés avec bcrypt

## Dépannage

### Le serveur RADIUS ne démarre pas

1. Vérifier que le port 1812/1813 n'est pas utilisé :
   ```bash
   sudo netstat -tulpn | grep 1812
   ```

2. Exécuter avec les droits root :
   ```bash
   sudo php radius_server.php
   ```

### Erreur de connexion MySQL

1. Vérifier les identifiants dans `config/config.php`
2. Tester la connexion :
   ```bash
   mysql -h localhost -u root -p radius_db
   ```

### Voucher rejeté

1. Vérifier le statut du voucher dans l'interface
2. Consulter les logs : `tail -f logs/radius.log`
3. Vérifier le secret RADIUS dans la config NAS

## Licence

MIT License

## Support

- Documentation : `/docs`
- Issues : GitHub Issues
