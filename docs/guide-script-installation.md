# Guide Pratique : Script d'Installation RADIUS Node

## Qu'est-ce que ce fichier ?

Quand vous ajoutez un serveur RADIUS sur la plateforme (page **Serveurs RADIUS**), un bouton vous permet de télécharger un fichier nommé :

```
install-radius-node-RS-XXXXXXXX.sh
```

C'est un **script d'installation automatique** (bash) qui configure entièrement un VPS distant pour qu'il devienne un nœud RADIUS. Le script contient déjà les tokens de sécurité et le code du serveur, prêts à l'emploi.

---

## Étapes concrètes

### Étape 1 : Préparer le VPS

Achetez un VPS Ubuntu 22.04 (ou Debian 12) chez un fournisseur (OVH, Hetzner, DigitalOcean, Contabo, etc.).

**Configuration minimale recommandée :**
- 1 vCPU, 1 Go RAM, 20 Go SSD
- Ubuntu 22.04 LTS ou Debian 12
- Accès root (SSH)

**Ports à ouvrir dans le pare-feu :**

| Port | Protocole | Usage |
|------|-----------|-------|
| 22 | TCP | SSH (administration) |
| 443 | TCP | HTTPS (webhook push temps réel) |
| 1812 | UDP | RADIUS Authentication |
| 1813 | UDP | RADIUS Accounting |

### Étape 2 : Rendre la plateforme accessible

**IMPORTANT** : Votre plateforme centrale doit être accessible depuis Internet (pas `localhost`).

Le script va tenter de contacter la plateforme pour télécharger les fichiers du nœud RADIUS. Si votre plateforme tourne actuellement sur `localhost`, vous avez deux options :

1. **Héberger la plateforme sur un serveur public** (recommandé pour la production)
2. **Utiliser un nom de domaine** pointant vers votre serveur plateforme

Quand le script détecte que l'URL est `localhost`, il vous demandera automatiquement de saisir l'URL publique correcte.

### Étape 3 : Transférer le script sur le VPS

Depuis votre ordinateur, envoyez le fichier vers le VPS via SCP :

```bash
scp install-radius-node-RS-XXXXXXXX.sh root@IP_DU_VPS:/root/
```

Remplacez :
- `RS-XXXXXXXX` par le code de votre serveur (ex: `RS-E12AFD9D`)
- `IP_DU_VPS` par l'adresse IP de votre VPS

**Alternative** : Vous pouvez aussi vous connecter au VPS en SSH et copier-coller le contenu du script.

### Étape 4 : Se connecter au VPS

```bash
ssh root@IP_DU_VPS
```

### Étape 5 : Rendre le script exécutable

```bash
chmod +x /root/install-radius-node-RS-XXXXXXXX.sh
```

### Étape 6 : Lancer l'installation

```bash
sudo /root/install-radius-node-RS-XXXXXXXX.sh
```

Le script va :
1. Vérifier que vous êtes root
2. Vous demander l'URL de la plateforme (si localhost détecté)
3. Installer les dépendances (PHP, MySQL, Nginx)
4. Créer la structure `/opt/radius-node/`
5. Télécharger les fichiers du nœud depuis la plateforme
6. Générer la configuration locale avec un mot de passe DB aléatoire
7. Créer la base de données MySQL locale
8. Configurer le service systemd (démarrage automatique)
9. Configurer le cron de synchronisation (toutes les minutes)
10. Configurer Nginx pour le webhook HTTPS
11. Démarrer tous les services
12. Lancer une première synchronisation

### Étape 7 : Vérifier l'installation

Après l'exécution, vérifiez que tout fonctionne :

```bash
# Vérifier le service RADIUS
systemctl status radius-node

# Vérifier les logs
tail -f /opt/radius-node/logs/radius.log

# Vérifier la sync
tail -f /opt/radius-node/logs/sync.log

# Vérifier Nginx
systemctl status nginx
```

### Étape 8 : Configurer le SSL (production)

Le script installe un certificat SSL auto-signé. Pour la production, installez un vrai certificat :

```bash
# Installer Certbot
apt-get install -y certbot python3-certbot-nginx

# Obtenir un certificat Let's Encrypt
certbot --nginx -d votre-domaine-vps.com
```

Puis modifiez `/etc/nginx/sites-available/radius-node` pour utiliser le vrai certificat.

### Étape 9 : Vérifier sur la plateforme

Retournez sur la plateforme web, page **Serveurs RADIUS**. Après quelques secondes, le serveur devrait passer en statut **online** (le nœud envoie un heartbeat toutes les minutes).

---

## Ce que fait le script en détail

```
/opt/radius-node/               ← Dossier d'installation
├── config/
│   └── config.php              ← Configuration (tokens, DB, ports)
├── src/                        ← Code source RADIUS
├── database/
│   └── node_schema.sql         ← Schéma DB locale
├── logs/
│   ├── radius.log              ← Logs du serveur RADIUS
│   └── sync.log                ← Logs de synchronisation
├── radius_server.php           ← Serveur RADIUS (service systemd)
├── sync_client.php             ← Client de sync (cron toutes les minutes)
└── webhook.php                 ← Endpoint pour les push temps réel
```

**Services créés :**
- `radius-node.service` — Serveur RADIUS (ports 1812/1813 UDP)
- Cron toutes les minutes — Synchronisation avec la plateforme centrale
- Nginx reverse proxy — Webhook HTTPS (port 443)

**Base de données locale :**
- `radius_node` — Contient les copies locales des zones, NAS, vouchers, profils

---

## Dépannage

### Le script échoue au téléchargement du package

```
ERREUR: Impossible de télécharger le package
```

**Cause** : La plateforme n'est pas accessible depuis le VPS.

**Solutions** :
- Vérifiez que l'URL de la plateforme est correcte et publique (pas `localhost`)
- Vérifiez que le pare-feu de la plateforme autorise les connexions entrantes
- Testez manuellement : `curl -v URL_PLATEFORME/web/node_sync.php`

### Le service ne démarre pas

```bash
systemctl status radius-node
journalctl -u radius-node -f
```

**Causes possibles** :
- PHP non installé correctement → `php -v`
- Port 1812/1813 déjà utilisé → `ss -ulnp | grep 181`
- Erreur de configuration → Vérifier `/opt/radius-node/config/config.php`

### Le serveur reste "offline" sur la plateforme

**Causes possibles** :
- Le cron ne tourne pas → `crontab -l` pour vérifier
- URL de la plateforme incorrecte dans le config
- Token de sync invalide → Régénérez-le depuis la plateforme et mettez à jour le config
- Pare-feu bloquant les connexions sortantes du VPS

### Erreur "INVALID_TOKEN"

Le token dans le config du nœud ne correspond plus à celui de la plateforme. Régénérez le token depuis la plateforme et mettez à jour `/opt/radius-node/config/config.php`.

---

## Commandes utiles après installation

```bash
# Redémarrer le serveur RADIUS
systemctl restart radius-node

# Voir les logs en direct
tail -f /opt/radius-node/logs/radius.log

# Forcer une synchronisation
php /opt/radius-node/sync_client.php

# Voir la configuration
cat /opt/radius-node/config/config.php

# Vérifier la base de données locale
mysql -u radius_node -p radius_node -e "SHOW TABLES;"

# Vérifier les sessions actives
mysql -u radius_node -p radius_node -e "SELECT * FROM sessions WHERE is_active=1;"
```
