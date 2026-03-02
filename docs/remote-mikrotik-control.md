# Contrôle MikroTik à Distance

## Table des matières

1. [Introduction](#introduction)
2. [Architecture double canal](#architecture-double-canal)
3. [Installation sur le routeur](#installation-sur-le-routeur)
4. [Guide administrateur](#guide-administrateur)
5. [Stratégie de déconnexion](#stratégie-de-déconnexion)
6. [Résolution du NAS](#résolution-du-nas)
7. [API Reference](#api-reference)
8. [Cycle de vie des commandes](#cycle-de-vie-des-commandes)
9. [Walled Garden automatique](#walled-garden-automatique)
10. [Synchronisation des sessions](#synchronisation-des-sessions)
11. [Sécurité](#sécurité)
12. [Maintenance & Cron](#maintenance--cron)
13. [Dépannage](#dépannage)
14. [Référence technique](#référence-technique)

---

## Introduction

Le système de contrôle à distance permet de gérer les routeurs MikroTik via **deux canaux complémentaires** utilisés simultanément :

| Canal | Méthode | Latence | Prérequis |
|-------|---------|---------|-----------|
| **API directe** | Connexion TCP au port API du routeur (8728) | Instantanée (~100ms) | IP accessible + identifiants API configurés |
| **Pull-Based (polling)** | Le routeur interroge le serveur toutes les 10s | 0-10 secondes | Script setup installé sur le routeur |

### Pourquoi deux canaux ?

- **L'API directe** est instantanée mais nécessite que le serveur puisse joindre le routeur (même réseau ou VPN)
- **Le pull-based** fonctionne partout (derrière NAT, 4G, firewall) mais a un délai de 0-10s
- Les deux sont **toujours déclenchés ensemble** : l'API directe pour la rapidité, le polling comme filet de sécurité

### Avantages

- **Aucun VPN requis** : le canal pull-based fonctionne derrière un NAT, un pare-feu ou un réseau mobile (4G/5G)
- **Aucune IP publique nécessaire** : le routeur initie la connexion sortante
- **Double garantie** : si l'API directe échoue, le polling prend le relais automatiquement
- **Suivi complet** : chaque commande a un cycle de vie traçable (pending → sent → executed)
- **Retry automatique** : les commandes échouées sont relancées automatiquement
- **Multi-tenant** : chaque administrateur ne voit que ses propres routeurs et commandes
- **Walled Garden dynamique** : les domaines des passerelles de paiement actives sont automatiquement autorisés

---

## Architecture double canal

```
┌─────────────────────┐                    ┌─────────────────────────────┐
│   MikroTik Router   │                    │        Serveur NAS          │
│                     │                    │                             │
│                     │   Canal 1 : API    │                             │
│  ┌───────────────┐  │   directe (TCP)    │  ┌───────────────────────┐  │
│  │  API Service  │←─┼───────────────────←│  │  SessionController    │  │
│  │  port 8728    │──┼───────────────────→│  │  PPPoEController      │  │
│  └───────────────┘  │  Instantané        │  │  tryDirectApiDisconnect│ │
│                     │                    │  └───────────────────────┘  │
│                     │                    │             │               │
│                     │                    │     EN PARALLÈLE            │
│                     │                    │             │               │
│  ┌───────────────┐  │   Canal 2 :        │  ┌───────────────────────┐  │
│  │   nas-cmd     │──┼──── Pull-Based ──→ │  │  fetch_cmd.php        │  │
│  │  (scheduler)  │  │   HTTP GET (10s)   │  │                       │  │
│  │               │←─┼────────────────────│  │  router_commands      │  │
│  │  Exécute cmd  │  │  Commande .rsc     │  │  (table MySQL)        │  │
│  │               │──┼──────────────────→ │  │                       │  │
│  │  Confirme     │  │  ?done=ID          │  └───────────────────────┘  │
│  └───────────────┘  │                    │                             │
│                     │                    │  ┌───────────────────────┐  │
│  ┌───────────────┐  │  HTTP POST (5min)  │  │  RouterSyncController │  │
│  │   nas-sync    │──┼──────────────────→ │  │  - Compare sessions   │  │
│  │  (scheduler)  │  │  Sessions actives  │  │  - Détecte expirés    │  │
│  └───────────────┘  │                    │  └───────────────────────┘  │
└─────────────────────┘                    └─────────────────────────────┘
```

### Flux de déconnexion d'un client (exemple)

```
Admin clique "Déconnecter" sur la page Sessions
                │
                ▼
    SessionController::disconnect()
                │
        ┌───────┴───────┐
        │               │
        ▼               ▼
   Canal 1 : API    Canal 2 : Polling
   MikroTik directe (filet de sécurité)
        │               │
        ▼               ▼
   tryDirectApi     commandSender
   Disconnect()     ->disconnectHotspotUser()
        │               │
   Instantané       INSERT INTO
   (si accessible)  router_commands
        │               │
        │               ▼
        │          Le routeur récupère
        │          la commande au
        │          prochain poll (≤10s)
        │               │
        ▼               ▼
   Déconnexion      Déconnexion
   immédiate        différée
        │               │
        └───────┬───────┘
                ▼
   Marquage session en BDD
   (stop_time = NOW())
```

### Quel canal est utilisé ?

| Situation | API directe | Pull-based | Résultat |
|-----------|:-----------:|:----------:|----------|
| Même réseau local, API configurée | OK | OK | Déconnexion instantanée + confirmation polling |
| Même réseau, API non configurée | -- | OK | Déconnexion en ≤10s via polling |
| Routeur derrière NAT/4G | -- | OK | Déconnexion en ≤10s via polling |
| Routeur hors ligne | -- | -- | Commande en attente, exécutée au retour |
| API accessible, script non installé | OK | -- | Déconnexion instantanée, pas de suivi polling |

---

## Installation sur le routeur

### Prérequis

- RouterOS v7 ou supérieur
- Accès au terminal MikroTik (Winbox ou SSH)
- Connexion internet active sur le routeur

### Configuration API directe (optionnel mais recommandé)

Pour activer le canal API directe (déconnexion instantanée) :

1. Sur le routeur MikroTik, activez le service API :
   ```
   /ip service enable api
   ```

2. Dans l'interface NAS, éditez le routeur et remplissez :
   - **Adresse API** : IP ou hostname du routeur (ex: `192.168.1.65`)
   - **Port API** : `8728` (défaut)
   - **Utilisateur API** : un utilisateur MikroTik avec droits suffisants
   - **Mot de passe API** : le mot de passe correspondant

> L'API directe fonctionne uniquement si le serveur NAS peut joindre le routeur via TCP. Pour les routeurs distants (derrière NAT/4G), seul le canal pull-based sera utilisé.

### Installation du script de polling

#### Étape 1 : Générer le script setup

1. Connectez-vous à l'interface d'administration NAS
2. Allez dans la page **NAS / Routeurs**
3. Cliquez sur le bouton **`</>`** (Setup) du routeur concerné
4. Une modale s'ouvre avec le script personnalisé

#### Étape 2 : Installer le script

1. Cliquez **"Copier"** pour copier le script dans le presse-papiers
2. Ouvrez le **Terminal** dans Winbox (ou connectez-vous en SSH)
3. **Collez** le script complet dans le terminal
4. Attendez la fin de l'installation (message `=== INSTALLATION COMPLETE ===`)

#### Étape 3 : Vérifier le fonctionnement

Après l'installation, vérifiez dans l'interface NAS :
- Le badge sur la carte du routeur doit passer au **vert** (en ligne)
- Le texte "En ligne" apparaît sous la carte

Côté MikroTik, vérifiez :
```
/system script print where name~"nas-"
/system scheduler print where name~"nas-"
/log print where message~"NAS:"
```

### Contenu du script setup

Le script généré dynamiquement inclut :

| Élément | Description |
|---------|-------------|
| Nettoyage | Suppression des anciens scripts/schedulers `nas-*` |
| Walled Garden | Domaines des passerelles de paiement actives + serveur NAS |
| Script `nas-cmd` | Polling des commandes toutes les 10 secondes |
| Script `nas-sync` | Synchronisation des sessions toutes les 5 minutes |
| Schedulers | Exécution automatique des deux scripts |

### Réinstallation

Si vous avez besoin de réinstaller le script (changement de serveur, nouveau token, etc.) :
1. Régénérez le token via le bouton **"Régénérer le token"** dans la modale Setup
2. Recopiez et recollez le nouveau script sur le routeur

---

## Guide administrateur

### Page NAS / Routeurs

Chaque carte de routeur affiche :

- **Badge de statut** (coin de l'icône routeur) :
  - Vert : routeur en ligne (vu il y a < 30 secondes)
  - Gris : routeur hors ligne ou script non installé

- **Texte de statut** (bas de la carte) :
  - "En ligne" : le routeur communique activement
  - "Vu il y a Xmin" : dernière communication
  - "Jamais connecté" : script non installé

- **Bouton Setup `</>`** : ouvre la modale pour générer/copier le script d'installation

### Modale Setup

La modale affiche :
- **Statut** : online/offline, dernière connexion, présence du token
- **Instructions** : étapes d'installation
- **Script** : le script RouterOS complet à copier
- **Actions** :
  - **Copier** : copie le script dans le presse-papiers
  - **Télécharger .rsc** : télécharge le script en fichier
  - **Régénérer le token** : crée un nouveau token d'authentification (nécessite réinstallation)

### Page Commandes Routeur

Accessible via le menu latéral **Réseau > Commandes Routeur**, cette page affiche :

#### Cartes de statistiques
- **Total** : nombre total de commandes
- **En attente** : commandes non encore envoyées
- **Envoyées** : commandes envoyées, en attente de confirmation
- **Exécutées** : commandes terminées avec succès
- **Échouées** : commandes ayant échoué après tous les retries
- **Expirées** : commandes n'ayant jamais été récupérées

#### Filtres
- **Par statut** : tous, en attente, envoyées, exécutées, échouées, expirées, annulées
- **Par routeur** : filtrer les commandes d'un routeur spécifique

#### Tableau des commandes
| Colonne | Description |
|---------|-------------|
| ID | Identifiant unique de la commande |
| Routeur | Nom du routeur cible |
| Type | Type de commande (disconnect, create, rate_limit, raw, etc.) |
| Description | Description courte de la commande |
| Statut | Badge coloré indiquant l'état |
| Créée | Date de création |
| Exécutée | Date d'exécution (si applicable) |
| Actions | Voir le détail, annuler, relancer |

#### Actions disponibles
- **Voir** (icône oeil) : affiche le contenu complet de la commande RouterOS
- **Annuler** (icône croix) : annule une commande en attente ou envoyée
- **Relancer** (icône refresh) : relance une commande échouée ou expirée

---

## Stratégie de déconnexion

### Déconnexion Hotspot (Sessions)

Quand un administrateur clique **"Déconnecter"** sur la page Sessions :

```
SessionController::disconnect($id)
│
├─ 1. Trouver le NAS associé (findNasForSession)
│
├─ 2. Canal API directe : tryDirectApiDisconnect()
│     ├─ Vérifie que mikrotik_api_username/password sont configurés
│     ├─ Tente la connexion sur mikrotik_host (priorité 1)
│     ├─ Fallback sur nas_ip de la session (priorité 2)
│     └─ Exécute hotspotDisconnectByUser() via RouterOS API
│
├─ 3. Canal Pull-Based : commandSender->disconnectHotspotUser()
│     └─ INSERT INTO router_commands (type=disconnect_hotspot, priority=10)
│
└─ 4. Marquage BDD : UPDATE sessions SET stop_time=NOW()
```

**Les deux canaux sont TOUJOURS déclenchés** (sauf si l'un n'est pas disponible). L'API directe déconnecte instantanément, le polling sert de filet de sécurité.

### Déconnexion en masse (Disconnect All)

Même stratégie mais optimisée : une seule connexion API par NAS pour toutes les sessions de ce NAS.

```
SessionController::disconnectAll()
│
├─ Grouper les sessions par nas_ip
│
└─ Pour chaque NAS :
     ├─ Ouvrir UNE connexion API (connectToRouterApi)
     ├─ Pour chaque session :
     │    ├─ API directe : hotspotDisconnectByUser()
     │    ├─ Pull-Based : commandSender->disconnectHotspotUser()
     │    └─ Marquage BDD
     └─ Fermer la connexion API
```

### Déconnexion PPPoE

Même principe dual pour les utilisateurs PPPoE :
- **API directe** : déconnexion de la session active + modification du secret PPP
- **Pull-Based** : commande `disconnect_pppoe` dans la queue

### Actions déclenchant des commandes MikroTik

| Action utilisateur | Type commande | Canal API | Canal Polling |
|-------------------|---------------|:---------:|:------------:|
| Déconnecter session hotspot | `disconnect_hotspot` | Oui | Oui |
| Déconnecter toutes les sessions | `disconnect_hotspot` | Oui | Oui |
| Déconnecter session PPPoE | `disconnect_pppoe` | Oui | Oui |
| Suspendre utilisateur PPPoE | `toggle_user` | -- | Oui |
| Modifier débit PPPoE (FUP) | `set_rate_limit` / `set_fup` | -- | Oui |
| Créer utilisateur PPPoE | `create_pppoe` | -- | Oui |
| Supprimer utilisateur PPPoE | `delete_pppoe` | -- | Oui |
| Voucher expiré (sync auto) | `disconnect_hotspot` | -- | Oui |

---

## Résolution du NAS

### Comment le système identifie le bon routeur

Quand une action est déclenchée depuis une session, le système doit identifier quel routeur NAS est concerné. La méthode `findNasForSession()` utilise une résolution en 3 niveaux :

```
Session (nas_ip = 192.168.1.65)
         │
    ┌────┴────────────────────────────────┐
    │  Priorité 1 : Match exact nasname   │
    │  nasname === "192.168.1.65" ?        │──→ Match trouvé
    │  (adresse RADIUS du NAS)            │
    └────┬────────────────────────────────┘
         │ Non trouvé
    ┌────┴────────────────────────────────┐
    │  Priorité 2 : Match mikrotik_host   │
    │  mikrotik_host === "192.168.1.65" ? │──→ Match trouvé
    │  (adresse API configurée)           │
    └────┬────────────────────────────────┘
         │ Non trouvé
    ┌────┴────────────────────────────────┐
    │  Priorité 3 : Wildcard 0.0.0.0/0   │
    │  Préfère le NAS avec last_seen      │──→ Fallback
    │  (routeur qui poll activement)      │
    └─────────────────────────────────────┘
```

### Pourquoi c'est important

Si plusieurs NAS sont configurés en wildcard (`nasname = 0.0.0.0/0`), le système choisit en priorité celui qui :
1. A un `mikrotik_host` correspondant à l'IP de la session
2. Sinon, celui qui a un `last_seen` récent (routeur actif avec polling)

Cela évite d'envoyer des commandes au mauvais routeur.

### Configuration recommandée

| Champ | Usage | Exemple |
|-------|-------|---------|
| `nasname` | Adresse RADIUS (peut être wildcard) | `0.0.0.0/0` ou `192.168.1.65` |
| `mikrotik_host` | Adresse pour l'API directe | `192.168.1.65` |
| `router_id` | Identifiant unique pour le polling | `NAS-8D87B889-60D2` |

**Recommandation** : toujours renseigner `mikrotik_host` même si le NAS est en wildcard, pour que la résolution fonctionne correctement.

---

## API Reference

### Router Setup

#### Générer le script setup
```
GET /api/router-setup/{routerId}
```
Retourne le script `.rsc` personnalisé pour le routeur.

**Réponse :**
```json
{
  "success": true,
  "data": {
    "script": "# NAS — INSTALLATION SCRIPT...",
    "router_id": "NAS-XXXXXXXX-XXXX",
    "polling_token": "a1b2c3d4..."
  }
}
```

#### Régénérer le token
```
POST /api/router-setup/{routerId}/generate-token
```
Génère un nouveau token d'authentification. Le script setup doit être réinstallé après cette opération.

#### Statut d'un routeur
```
GET /api/router-setup/{routerId}/status
```
**Réponse :**
```json
{
  "success": true,
  "data": {
    "online": true,
    "last_seen": "2026-03-02 10:30:15",
    "last_seen_ago": 8,
    "has_token": true,
    "polling_interval": 10,
    "setup_installed_at": "2026-03-01 14:00:00"
  }
}
```

#### Statuts de tous les routeurs
```
GET /api/router-setup/statuses
```
Retourne les statuts de tous les routeurs de l'administrateur connecté.

### Sessions (déconnexion)

#### Déconnecter une session
```
DELETE /api/sessions/{id}
```
Déconnecte le client via les deux canaux (API directe + polling) et marque la session terminée.

**Réponse :**
```json
{
  "success": true,
  "message": "Utilisateur déconnecté",
  "data": {
    "method": "API MikroTik + commande polling",
    "api_success": true
  }
}
```

Le champ `method` indique quel(s) canal(aux) ont été utilisés :
- `"API MikroTik + commande polling"` : les deux canaux ont fonctionné
- `"commande polling"` : seul le polling est disponible (API non configurée ou inaccessible)
- `"API MikroTik"` : seule l'API a fonctionné (pas de router_id pour le polling)
- `"marquage DB"` : aucun canal disponible, seule la BDD est mise à jour

#### Déconnecter toutes les sessions
```
POST /api/sessions/disconnect-all
```

### Router Commands

#### Lister les commandes
```
GET /api/router-commands?router_id={id}&status={status}&limit={n}&offset={n}
```

**Paramètres (query string) :**
| Paramètre | Type | Description |
|-----------|------|-------------|
| `router_id` | string | Filtrer par routeur (optionnel) |
| `status` | string | Filtrer par statut : `pending`, `sent`, `executed`, `failed`, `expired`, `cancelled` |
| `limit` | int | Nombre de résultats (max 200, défaut 50) |
| `offset` | int | Offset pour pagination |

**Réponse :**
```json
{
  "success": true,
  "data": {
    "commands": [
      {
        "id": 1,
        "router_id": "NAS-9D0CB27F-1F1",
        "router_name": "HAP AX 2",
        "command_type": "disconnect_hotspot",
        "command_description": "Déconnexion hotspot USER123",
        "priority": 10,
        "status": "executed",
        "created_at": "2026-03-02 10:00:00",
        "sent_at": "2026-03-02 10:00:08",
        "executed_at": "2026-03-02 10:00:10",
        "retry_count": 0,
        "error_message": null
      }
    ],
    "stats": {
      "total": 156,
      "pending": 2,
      "sent": 0,
      "executed": 148,
      "failed": 3,
      "expired": 3,
      "cancelled": 0,
      "avg_execution_time": 2.4
    },
    "pagination": { "limit": 50, "offset": 0 }
  }
}
```

#### Créer une commande
```
POST /api/router-commands
Content-Type: application/json
```

**Commande brute :**
```json
{
  "router_id": "NAS-9D0CB27F-1F1",
  "command": "/system identity print",
  "description": "Vérifier l'identité du routeur",
  "priority": 50,
  "command_type": "raw"
}
```

**Commande prédéfinie :**
```json
{
  "router_id": "NAS-9D0CB27F-1F1",
  "command_type": "disconnect_hotspot",
  "command": "",
  "params": {
    "username": "VOUCHER123"
  }
}
```

**Types prédéfinis disponibles :**
| Type | Paramètres | Description |
|------|-----------|-------------|
| `disconnect_hotspot` | `username` | Déconnecter un utilisateur hotspot |
| `disconnect_pppoe` | `username` | Déconnecter un utilisateur PPPoE |
| `create_pppoe` | `name`, `password`, `profile` | Créer un utilisateur PPPoE |
| `delete_pppoe` | `username` | Supprimer un utilisateur PPPoE |
| `set_rate_limit` | `username`, `rate_limit` | Modifier le débit d'un utilisateur |
| `toggle_user` | `username`, `disabled` | Activer/désactiver un utilisateur |
| `log` | `message` | Envoyer un message au log du routeur |

#### Annuler une commande
```
POST /api/router-commands/{id}/cancel
```
Annule une commande en statut `pending` ou `sent`.

#### Relancer une commande
```
POST /api/router-commands/{id}/retry
```
Relance une commande en statut `failed` ou `expired`.

#### Statistiques
```
GET /api/router-commands/stats?router_id={id}
```

### Router Sync

#### Synchroniser les sessions
```
POST /api/router-sync/sync
Content-Type: application/json
X-NAS-Token: {polling_token}
```

**Corps de la requête (envoyé par le routeur) :**
```json
{
  "router_id": "NAS-9D0CB27F-1F1",
  "hotspot": [
    { "u": "VOUCHER001", "ip": "10.0.0.5", "mac": "AA:BB:CC:DD:EE:FF" }
  ],
  "pppoe": [
    { "u": "client_jean", "ip": "10.1.0.10", "svc": "pppoe" }
  ],
  "system": {
    "cpu": 15,
    "free_mem": 52428800,
    "total_mem": 67108864,
    "uptime": "3d02:15:30",
    "version": "7.14.3"
  }
}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "synced": true,
    "hotspot_count": 1,
    "pppoe_count": 1,
    "commands": [
      {
        "type": "disconnect_hotspot",
        "username": "VOUCHER_EXPIRED",
        "reason": "voucher_expired"
      }
    ]
  }
}
```

### Endpoint de polling (fetch_cmd.php)

Cet endpoint est appelé directement par le routeur MikroTik. Il ne passe pas par le routeur API.

```
GET /web/fetch_cmd.php?router={router_id}
```

**Headers optionnels :**
- `X-NAS-Token: {polling_token}` -- authentification

**Réponses :**

| Cas | Réponse | Code HTTP |
|-----|---------|-----------|
| Pas de commande | `# NOP` | 200 |
| Commande disponible | `# CMD:{id}\n{contenu}` | 200 |
| Router ID manquant | `# ERROR:MISSING_ROUTER_ID` | 400 |
| Router inconnu | `# ERROR:UNKNOWN_ROUTER` | 404 |
| Token invalide | `# ERROR:INVALID_TOKEN` | 403 |
| BDD indisponible | `# ERROR:DB_UNAVAILABLE` | 503 |

**Confirmation d'exécution :**
```
GET /web/fetch_cmd.php?router={router_id}&done={cmdId}
-> # OK
```

**Signalement d'erreur :**
```
GET /web/fetch_cmd.php?router={router_id}&fail={cmdId}&error={message}
-> # OK
```

---

## Cycle de vie des commandes

```
                    ┌──────────┐
                    │ PENDING  │ <- Commande créée
                    └────┬─────┘
                         │
                    Le routeur poll
                    fetch_cmd.php
                         │
                    ┌────▼─────┐
                    │   SENT   │ <- Envoyée au routeur
                    └────┬─────┘
                         │
              ┌──────────┼──────────┐
              │          │          │
         Confirmation  Erreur    Timeout
          (?done=)    (?fail=)   (>5 min)
              │          │          │
        ┌─────▼────┐ ┌──▼────┐ ┌──▼──────┐
        │ EXECUTED  │ │PENDING│ │ PENDING  │
        │          │ │(retry)│ │ (reset)  │
        └──────────┘ └──┬────┘ └──────────┘
                        │
                   retry_count
                   >= max_retries ?
                        │
                   ┌────▼─────┐
                   │  FAILED  │
                   └──────────┘

    ┌──────────┐     ┌───────────┐
    │ CANCELLED│     │  EXPIRED  │
    │(manuel)  │     │(expires_at│
    └──────────┘     │ dépassé)  │
                     └───────────┘
```

### Statuts

| Statut | Couleur | Description |
|--------|---------|-------------|
| `pending` | Jaune | Commande créée, en attente d'être récupérée par le routeur |
| `sent` | Bleu | Commande envoyée au routeur, en attente de confirmation |
| `executed` | Vert | Commande exécutée avec succès (confirmée par le routeur) |
| `failed` | Rouge | Commande échouée après le nombre max de retries |
| `expired` | Gris | Commande non récupérée avant son expiration |
| `cancelled` | Gris | Commande annulée manuellement par l'administrateur |

### Priorités

| Valeur | Usage | Exemples |
|--------|-------|----------|
| 1-10 | Urgent | Déconnexion d'utilisateurs |
| 11-20 | Haute | Modification de débit, FUP |
| 21-30 | Normale-haute | Création/suppression d'utilisateurs |
| 50 | Normale (défaut) | Commandes génériques |
| 99 | Basse | Messages de log, tests |

### Mécanisme de retry

Quand le routeur signale une erreur (`?fail=cmdId`) :
1. `retry_count` est incrémenté
2. Si `retry_count < max_retries` (défaut: 3), la commande repasse en `pending`
3. Si `retry_count >= max_retries`, la commande passe en `failed`

Les commandes `sent` non confirmées après **5 minutes** sont automatiquement remises en `pending`.

---

## Walled Garden automatique

Le script setup inclut automatiquement les règles de Walled Garden basées sur les passerelles de paiement actives.

### Passerelles supportées

| Passerelle | Domaines autorisés |
|------------|--------------------|
| FedaPay | `*.fedapay.com`, `api.fedapay.com`, `cdn.fedapay.com` |
| CinetPay | `*.cinetpay.com`, `api-checkout.cinetpay.com`, `cdn.cinetpay.com` |
| FeexPay | `*.feexpay.me`, `api.feexpay.me` |
| KkiaPay | `*.kkiapay.me`, `api.kkiapay.me`, `cdn.kkiapay.me` |
| FlexPay | `*.flexpay.cd`, `backend.flexpay.cd` |
| MoneyFusion | `*.moneyfusion.net`, `pay.moneyfusion.net` |
| PayDunya | `*.paydunya.com`, `app.paydunya.com` |
| PayGate | `*.paygateglobal.com` |
| Cryptomus | `*.cryptomus.com`, `api.cryptomus.com` |
| YengaPay | `*.yengapay.com` |
| LigdiCash | `*.ligdicash.com`, `app.ligdicash.com` |
| Moneroo | `*.moneroo.io`, `api.moneroo.io` |
| Stripe | `*.stripe.com`, `js.stripe.com` |
| PayPal | `*.paypal.com`, `*.paypalobjects.com` |

### Personnalisation

Les domaines sont stockés dans la table `gateway_walled_garden`. Pour ajouter un domaine :

```sql
INSERT INTO gateway_walled_garden (gateway_code, domain, port, description)
VALUES ('ma_passerelle', '*.exemple.com', '80,443', 'Ma passerelle custom');
```

Le serveur NAS lui-même est **toujours** inclus automatiquement dans le Walled Garden.

### Mise à jour

Quand les passerelles de paiement changent (activation/désactivation), régénérez le script setup et réinstallez-le sur le routeur pour mettre à jour les règles de Walled Garden.

---

## Synchronisation des sessions

Le script `nas-sync` s'exécute toutes les **5 minutes** et envoie au serveur :

### Données envoyées

- **Sessions hotspot actives** : username, IP, MAC de chaque utilisateur connecté
- **Sessions PPPoE actives** : username, IP, service
- **Informations système** : charge CPU, mémoire libre/totale, uptime, version RouterOS

### Actions automatiques du serveur

Le serveur compare les sessions actives avec sa base de données et peut :

1. **Détecter les vouchers expirés** encore connectés -> retourne une commande de déconnexion
2. **Détecter les utilisateurs PPPoE suspendus/expirés** -> retourne une commande de déconnexion
3. **Mettre à jour les compteurs de session** dans la base de données
4. **Stocker les métriques système** pour le monitoring

---

## Sécurité

### Authentification par token (polling)

Chaque routeur possède un `polling_token` unique (64 caractères hexadécimaux). Ce token est :

- Généré lors de la première ouverture de la modale Setup
- Envoyé par le routeur dans le header HTTP `X-NAS-Token`
- Vérifié par `fetch_cmd.php` à chaque requête
- Régénérable depuis l'interface admin (nécessite réinstallation du script)

### Authentification API directe

L'API MikroTik utilise un couple utilisateur/mot de passe stocké dans la table `nas` :
- `mikrotik_api_username` : utilisateur MikroTik
- `mikrotik_api_password` : mot de passe (stocké en clair, accès restreint)
- La connexion est tentée sur `mikrotik_host` puis en fallback sur `nas_ip`

### Validation du router_id

- Le `router_id` est vérifié contre la table `nas` à chaque appel
- Les caractères sont sanitizés (alphanumériques, tirets et underscores uniquement)
- Un router_id inconnu retourne une erreur 404

### Protection des commandes

- Les commandes sont isolées par `admin_id` (multi-tenant)
- La taille maximum d'une commande est de **64 Ko**
- Les commandes expirent automatiquement après **1 heure** (configurable)
- Les paramètres injectés dans les commandes RouterOS sont échappés via `addslashes()`

### Recommandations

- Utilisez **HTTPS** en production pour chiffrer les communications
- Régénérez le token si vous soupçonnez une compromission
- Créez un utilisateur MikroTik dédié pour l'API avec des droits minimaux
- Surveillez les tentatives de connexion avec des router_id inconnus dans les logs

---

## Maintenance & Cron

### Script de nettoyage

Le script `cron/router_commands_cleanup.php` effectue 3 opérations :

1. **Expire** les commandes `pending` dont `expires_at` est dépassé
2. **Reset** les commandes `sent` bloquées depuis plus de 5 minutes
3. **Supprime** les commandes terminées de plus de 30 jours

### Configuration du cron

Ajoutez la ligne suivante au crontab du serveur :

```cron
*/5 * * * * php /chemin/vers/nas/cron/router_commands_cleanup.php >> /var/log/nas-cleanup.log 2>&1
```

### Supervision

Surveillez ces indicateurs :

| Indicateur | Valeur normale | Action si anormal |
|------------|---------------|-------------------|
| Commandes `pending` | 0-5 | Vérifier que le routeur poll correctement |
| Commandes `sent` > 5min | 0 | Le routeur ne confirme pas -> vérifier la connexion |
| Commandes `failed` | < 5% du total | Vérifier les logs MikroTik |
| `last_seen` > 1 min | Non | Routeur hors ligne -> vérifier la connexion internet |

---

## Dépannage

### Le routeur ne passe pas "En ligne"

1. **Vérifiez que le script est installé** :
   ```
   /system script print where name~"nas-"
   /system scheduler print where name~"nas-"
   ```

2. **Vérifiez les logs** :
   ```
   /log print where message~"NAS:"
   ```

3. **Testez manuellement le fetch** :
   ```
   /tool fetch url="http://votre-serveur/nas/web/fetch_cmd.php?router=NAS-XXXXXXXX-XXXX" mode=http output=user
   ```

4. **Vérifiez la résolution DNS** du serveur NAS depuis le routeur :
   ```
   :resolve votre-serveur.com
   ```

### La déconnexion ne fonctionne pas

1. **Vérifiez quel routeur est associé** : La session peut être associée au mauvais NAS
   - Assurez-vous que `mikrotik_host` est renseigné dans la configuration du NAS
   - Si plusieurs NAS sont en wildcard (`0.0.0.0/0`), le système préfère celui avec `last_seen` récent

2. **Vérifiez l'API directe** :
   - Les champs `mikrotik_api_username` et `mikrotik_api_password` sont-ils remplis ?
   - Le port API (8728) est-il ouvert sur le routeur ?
   - Testez : `/ip service print where name=api`

3. **Vérifiez le polling** :
   - Le routeur poll-t-il ? (badge vert sur la page NAS)
   - La commande apparaît-elle dans la page Commandes Routeur ?
   - Statut `pending` = le routeur ne poll pas
   - Statut `executed` = la commande a été exécutée

4. **Consultez la page Commandes Routeur** pour voir le statut de la commande envoyée

### Les commandes restent en "Envoyée"

- Le routeur a reçu la commande mais ne confirme pas
- Vérifiez les logs MikroTik pour des erreurs d'import (`/log print where message~"NAS:"`)
- La commande sera automatiquement remise en `pending` après 5 minutes

### Les commandes restent en "En attente"

- Le routeur ne poll pas le serveur
- Vérifiez que le scheduler `nas-cmd` est actif : `/system scheduler print where name="nas-cmd"`
- Vérifiez que le routeur a accès au serveur NAS (firewall, DNS, NAT)

### Erreur "Invalid token"

- Le token du routeur ne correspond pas à celui en base de données
- Régénérez le token dans l'interface admin et réinstallez le script

### Le Walled Garden ne fonctionne pas

- Vérifiez les règles : `/ip hotspot walled-garden print where comment~"NAS-"`
- Régénérez et réinstallez le script setup pour mettre à jour les règles
- Vérifiez que les passerelles de paiement sont bien activées dans l'interface admin

### Note sur les fuseaux horaires

Le statut online/offline est calculé via `TIMESTAMPDIFF` directement en SQL pour éviter les décalages entre le fuseau horaire de PHP et celui de MySQL. Si le badge ne passe jamais au vert alors que le routeur poll, vérifiez que MySQL et PHP utilisent le même fuseau horaire ou que la version du code utilise bien `TIMESTAMPDIFF`.

---

## Référence technique

### Base de données

#### Table `router_commands`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT (PK) | Identifiant auto-incrémenté |
| `router_id` | VARCHAR(64) | ID du routeur (format `NAS-XXXXXXXX-XXXX`) |
| `nas_id` | INT (FK) | Référence vers la table `nas` |
| `admin_id` | INT | Isolation multi-tenant |
| `command_type` | VARCHAR(50) | Type : `raw`, `disconnect_hotspot`, `disconnect_pppoe`, `create_pppoe`, `delete_pppoe`, `set_rate_limit`, `set_fup`, `remove_fup`, `toggle_user`, `log` |
| `command_content` | TEXT | Script RouterOS à exécuter |
| `command_description` | VARCHAR(255) | Description lisible |
| `priority` | INT | 1 (urgent) à 99 (bas), défaut: 50 |
| `status` | ENUM | `pending`, `sent`, `executed`, `failed`, `expired`, `cancelled` |
| `created_at` | TIMESTAMP | Date de création |
| `sent_at` | DATETIME | Date d'envoi au routeur |
| `executed_at` | DATETIME | Date de confirmation |
| `expires_at` | DATETIME | Date d'expiration |
| `error_message` | VARCHAR(500) | Message d'erreur (si échec) |
| `retry_count` | INT | Nombre de tentatives |
| `max_retries` | INT | Nombre max de retries (défaut: 3) |
| `created_by` | INT | Utilisateur ayant créé la commande |

#### Table `gateway_walled_garden`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT (PK) | Identifiant |
| `gateway_code` | VARCHAR(50) | Code passerelle (`fedapay`, `cinetpay`, etc.) |
| `domain` | VARCHAR(255) | Pattern de domaine (`*.fedapay.com`) |
| `port` | VARCHAR(20) | Ports autorisés (défaut: `80,443`) |
| `description` | VARCHAR(255) | Description |

#### Colonnes clés de `nas`

| Colonne | Type | Description |
|---------|------|-------------|
| `router_id` | VARCHAR(64) | Identifiant unique pour le polling |
| `nasname` | VARCHAR(128) | Adresse RADIUS (`0.0.0.0/0` = wildcard) |
| `mikrotik_host` | VARCHAR(255) | Adresse du routeur pour l'API directe |
| `mikrotik_api_port` | INT | Port API MikroTik (défaut: 8728) |
| `mikrotik_api_username` | VARCHAR(100) | Utilisateur API MikroTik |
| `mikrotik_api_password` | VARCHAR(255) | Mot de passe API MikroTik |
| `last_seen` | DATETIME | Dernière communication polling |
| `polling_token` | VARCHAR(64) | Token d'authentification polling |
| `polling_interval` | INT | Intervalle de polling en secondes (défaut: 10) |
| `setup_installed_at` | DATETIME | Date d'installation du script |

### Fichiers du système

| Fichier | Rôle |
|---------|------|
| `web/fetch_cmd.php` | Endpoint de polling (appelé par le routeur) |
| `src/Mikrotik/CommandSender.php` | Classe d'envoi de commandes (queue BDD) |
| `src/Mikrotik/SetupScriptGenerator.php` | Générateur de scripts setup dynamiques |
| `src/Mikrotik/RouterOS.php` | Client API MikroTik (connexion directe TCP) |
| `src/Api/RouterSetupController.php` | API : setup, token, statut |
| `src/Api/RouterCommandController.php` | API : CRUD commandes |
| `src/Api/RouterSyncController.php` | API : synchronisation sessions |
| `src/Api/SessionController.php` | Déconnexion hotspot (dual canal) |
| `src/Api/PPPoEController.php` | Déconnexion PPPoE (dual canal) |
| `web/views/nas.php` | UI : badge online/offline, modale setup |
| `web/views/router-commands.php` | UI : page historique commandes |
| `cron/router_commands_cleanup.php` | Nettoyage périodique |
| `database/migrations/047_router_commands.sql` | Migration BDD |
