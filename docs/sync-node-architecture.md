# Synchronisation Central ↔ Noeud RADIUS

## Vue d'ensemble

L'architecture de sync repose sur 3 mécanismes complémentaires :

| Mécanisme | Direction | Déclencheur | Délai |
|-----------|-----------|-------------|-------|
| **Push webhook** | Central → Noeud | Action admin (reset FUP, modif user...) | Instantané |
| **Pull sync** | Central → Noeud | Cron toutes les 60s (hash-based) | 0-60s |
| **Push sync** | Noeud → Central | Cron toutes les 60s | 0-60s |

```
┌──────────────────┐                    ┌──────────────────┐
│  CENTRAL          │                    │  NOEUD RADIUS    │
│  (plateforme web) │                    │  (VPS distant)   │
│                   │                    │                  │
│  api.php          │── Push webhook ──▶│  webhook.php     │
│  node_sync.php    │◀── Pull sync ────│  sync_client.php │
│  node_sync.php    │◀── Push sync ────│  sync_client.php │
│                   │                    │                  │
│  NodePushService  │── Query GET ─────▶│  webhook.php     │
│                   │◀── JSON response ─│                  │
└──────────────────┘                    └──────────────────┘
```

---

## 1. Push Webhook (Central → Noeud) — Instantané

Le central envoie un POST au noeud à chaque action admin.

### Endpoint noeud

```
POST http://<node_ip>/webhook.php
Headers:
  Content-Type: application/json
  X-Platform-Token: <platform_token>
  X-Event: <event_name>

Body:
{
  "event": "pppoe_user.fup_reset",
  "data": { ... },
  "timestamp": 1709827200,
  "server_code": "RS-XXXXXXXX"
}
```

### Events supportés

| Event | Description | Handler |
|-------|-------------|---------|
| `voucher.created/updated` | Création/modification voucher | `upsertVoucher()` |
| `voucher.deleted` | Suppression voucher | DELETE |
| `profile.created/updated` | Modification profil | `upsertProfile()` |
| `nas.created/updated` | Modification NAS | `upsertNas()` |
| `zone.created/updated` | Modification zone | `upsertZone()` |
| `pppoe_user.created/updated` | Modification utilisateur PPPoE | `upsertPPPoEUser()` |
| `pppoe_user.deleted` | Suppression utilisateur PPPoE | DELETE |
| **`pppoe_user.fup_reset`** | Reset FUP instantané | `handleFupReset()` |

### Authentification

Le noeud vérifie le token dans `X-Platform-Token` contre sa config locale :

```php
$token = $_SERVER['HTTP_X_PLATFORM_TOKEN'] ?? '';
if (!hash_equals($config['platform']['platform_token'], $token)) {
    http_response_code(403);
    exit;
}
```

### Côté central : NodePushService

```php
// Envoyer un événement à tous les noeuds d'une zone
$pushService->pushToZoneNodes($zoneId, 'pppoe_user.updated', $userData);

// Envoyer à tous les noeuds actifs
$pushService->pushToAllNodes('profile.updated', $profileData);

// Reset FUP spécifique
$pushService->notifyFupReset($user, $fupStatus);
```

### Construction de l'URL

```php
// NodePushService::buildNodeUrl()
// Logique : port 443 = HTTPS, sinon HTTP
// Champs utilisés : host, webhook_port, webhook_path
//
// Exemples :
//   host=89.167.78.7, port=80   → http://89.167.78.7/webhook.php
//   host=node.example.com, port=443 → https://node.example.com/webhook.php
```

---

## 2. Query GET (Central → Noeud) — Lecture temps réel

Le central peut interroger le noeud pour récupérer des données en temps réel.

### Exemple : FUP Status (bouton "Sync noeud")

```
GET http://<node_ip>/webhook.php?action=fup_status&user_id=123
Headers:
  X-Platform-Token: <platform_token>
```

**Réponse :**
```json
{
  "status": "ok",
  "source": "node",
  "data": {
    "id": 123,
    "username": "client_pppoe",
    "fup_data_used": 1500000000,
    "fup_data_offset": 0,
    "fup_triggered": 0,
    "fup_triggered_at": null,
    "fup_last_reset": "2026-03-07 14:32:00",
    "fup_override": 0,
    "fup_quota": 10737418240,
    "fup_download_speed": 256000,
    "fup_upload_speed": 128000,
    "normal_download_speed": 2048000,
    "normal_upload_speed": 1024000,
    "fup_enabled": 1
  }
}
```

### Flux complet UI → Noeud

```
[Bouton "Sync noeud"]
     │
     ▼
[JS: loadFupNodeStatus()]
     │  fetch('api.php?route=/pppoe/users/{id}/fup/node')
     ▼
[API: PPPoEController::getUserFupNodeStatus()]
     │  verifyOwnership() → multi-tenant
     ▼
[NodePushService::queryNodeFupStatus()]
     │  GET http://node/webhook.php?action=fup_status&user_id=123
     │  Timeout: 5s connexion, 3s connect
     ▼
[webhook.php: GET handler]
     │  SELECT depuis DB locale du noeud
     ▼
[JSON response → affichage dans le panneau UI]
```

### Comment ajouter une nouvelle query GET

1. **Sur le noeud** (`webhook.php`) — ajouter un handler GET :

```php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'mon_action') {
        // Récupérer les données locales
        $stmt = $pdo->prepare("SELECT ... FROM ma_table WHERE id = ?");
        $stmt->execute([$_GET['id'] ?? 0]);
        $data = $stmt->fetch();

        echo json_encode(['status' => 'ok', 'source' => 'node', 'data' => $data]);
        exit;
    }
}
```

2. **Sur le central** (`NodePushService.php`) — ajouter une méthode query :

```php
public function queryNodeMaAction(int $id, ?int $zoneId): array
{
    $server = $this->findServerForZone($zoneId);
    if (!$server) return ['data' => null, 'error' => 'Aucun serveur configuré'];

    $url = $this->buildNodeUrl($server) . '?action=mon_action&id=' . $id;
    // ... curl GET avec X-Platform-Token ...
    return ['data' => $responseData, 'error' => null];
}
```

3. **Sur le central** (`PPPoEController.php` ou autre) — ajouter un endpoint API :

```php
public function getMonActionNodeStatus(array $params): void
{
    $id = (int)$params['id'];
    $result = $this->pushService->queryNodeMaAction($id, $zoneId);
    if ($result['error']) jsonError($result['error'], 503);
    jsonSuccess($result['data']);
}
```

4. **Route** (`api.php`) :

```php
$router->get('/mon-module/{id}/node-status', fn($p) => $controller->getMonActionNodeStatus($p));
```

5. **UI** (`views/ma-page.php`) :

```javascript
async loadNodeStatus() {
    this.loading = true;
    try {
        const res = await fetch(`api.php?route=/mon-module/${this.id}/node-status`);
        const json = await res.json();
        if (json.success) this.nodeData = json.data;
    } finally {
        this.loading = false;
    }
}
```

---

## 3. Sync Périodique (Cron — toutes les 60s)

Le `sync_client.php` tourne sur le noeud via cron et effectue 3 phases :

### Phase 1 : Heartbeat

```
GET {platformUrl}/node_sync.php?action=heartbeat&server={code}
Headers: X-Node-Token: {sync_token}

Réponse: { "config_hash": "abc123..." }
```

Compare le hash local avec le hash du central. Si différent → Pull.

### Phase 2 : Pull (si config modifiée)

```
GET {platformUrl}/node_sync.php?action=pull&server={code}&hash={old}
Headers: X-Node-Token: {sync_token}
```

Données synchronisées :
- `zones` — Remplacement complet
- `nas` — Remplacement complet
- `profiles` — Remplacement complet
- `pppoe_profiles` — Remplacement complet (inclut les paramètres FUP)
- `vouchers` — Fusion (INSERT/UPDATE, supprime les absents)
- `pppoe_users` — Fusion + propagation reset FUP

**Propagation FUP dans le Pull :**

```php
// Pour chaque utilisateur PPPoE reçu du central :
$centralLastReset = $user['fup_last_reset'];

if ($centralLastReset > $nodeLastReset) {
    // Nouveau reset détecté !
    $localTotal = SUM(sessions du noeud);

    UPDATE pppoe_users SET
        fup_data_used = 0,
        fup_data_offset = $localTotal,   // ← Total LOCAL, pas celui du central
        fup_triggered = 0,
        fup_last_reset = $centralLastReset
    WHERE id = ?;

    // Déconnecter l'utilisateur du routeur MikroTik
    // pour forcer une re-auth avec le débit normal
}
```

> **Important** : Le `fup_data_offset` utilise toujours le total des sessions **locales** du noeud, jamais celui du central. Sinon la consommation FUP serait mal calculée.

### Phase 3 : Push (données locales → central)

```
POST {platformUrl}/node_sync.php?action=push&server={code}
Headers: X-Node-Token: {sync_token}

Body:
{
  "sessions": [...],           // Sessions voucher non sync
  "auth_logs": [...],          // Logs d'authentification
  "voucher_updates": [...],    // Compteurs voucher
  "pppoe_sessions": [...],     // Sessions PPPoE
  "pppoe_user_updates": [      // Compteurs FUP
    {
      "id": 123,
      "data_used": 5000000000,
      "fup_data_used": 150000000,
      "fup_data_offset": 4850000000,
      "fup_triggered": 0,
      "fup_last_reset": "2026-03-07 14:32:00",
      "last_nas_ip": "10.0.0.1",
      "last_acct_session_id": "abc123"
    }
  ]
}
```

### Protection contre l'écrasement du reset FUP

Le central ne met à jour les champs FUP que si le `fup_last_reset` du noeud est >= celui du central :

```sql
UPDATE pppoe_users SET
    data_used = GREATEST(data_used, ?),        -- Toujours prendre le max
    fup_data_used = CASE
        WHEN nodeLastReset >= centralLastReset THEN nodeValue
        ELSE fup_data_used                     -- Garder la valeur du central
    END,
    ...
WHERE id = ?
```

Cela empêche un push du noeud (avec d'anciennes données) d'écraser un reset récent sur le central.

---

## 4. Calcul de la consommation FUP

### Formule

```
fup_data_used = SUM(pppoe_sessions.input_octets + output_octets) - fup_data_offset
```

### Champs clés dans `pppoe_users`

| Champ | Description |
|-------|-------------|
| `data_used` | Total cumulé depuis la création du compte (monotone) |
| `fup_data_used` | Consommation depuis le dernier reset FUP |
| `fup_data_offset` | Total des sessions au moment du dernier reset |
| `fup_triggered` | 1 si le quota FUP est dépassé |
| `fup_triggered_at` | Date/heure du déclenchement |
| `fup_last_reset` | Date/heure du dernier reset (sert de version pour la sync) |
| `fup_override` | 1 = ignorer le FUP même si déclenché |

### Cycle de vie

```
[Compte créé]
  fup_data_offset = 0
  fup_data_used = 0

[Utilisation normale]
  Accounting MikroTik → updatePPPoESession()
  fup_data_used = SUM(sessions) - fup_data_offset

[Quota dépassé]
  fup_triggered = 1, fup_triggered_at = NOW()
  Vitesse réduite → fup_download_speed / fup_upload_speed

[Admin reset FUP]
  fup_data_offset = SUM(sessions)   ← "nouveau zéro"
  fup_data_used = 0
  fup_triggered = 0
  fup_last_reset = NOW()

[Reset mensuel automatique]
  Même logique, déclenché par le profil (fup_reset_type = 'monthly')
```

---

## 5. Sécurité

### Double protection du webhook

1. **Token** : Header `X-Platform-Token` vérifié avec `hash_equals()`
2. **IP Whitelist** : Nginx sur le noeud n'autorise que l'IP du central

```nginx
server {
    listen 80;
    root /opt/radius-node;

    allow <IP_DU_CENTRAL>;
    deny all;

    location = /webhook.php {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location / { return 404; }
}
```

### Tokens

| Token | Usage | Header | Direction |
|-------|-------|--------|-----------|
| `sync_token` | Sync périodique | `X-Node-Token` | Noeud → Central |
| `platform_token` | Webhook + query | `X-Platform-Token` | Central → Noeud |

---

## 6. Résilience

| Scénario | Mécanisme de rattrapage |
|----------|------------------------|
| Webhook échoue (noeud down) | Pull sync rattrape en 60s max |
| Push sync échoue (central down) | Données gardées localement, re-push au prochain cycle |
| Pull sync échoue (réseau) | Heartbeat re-tentera au prochain cycle |
| Données FUP incohérentes après reset | Protection `fup_last_reset` empêche l'écrasement |
| Noeud pas encore configuré | `checkFupResetWithCentral()` fait un appel temps réel au central pendant l'auth RADIUS |

---

## 7. Ajouter un nouveau type de sync

Pour synchroniser une nouvelle entité (ex: `hotspot_plans`) :

### A. Push webhook (instant)

1. **Central** — `NodePushService.php` : ajouter méthode `notifyHotspotPlanChange()`
2. **Noeud** — `webhook.php` : ajouter case `hotspot_plan.updated` dans le switch

### B. Pull sync (périodique)

1. **Central** — `RadiusDatabase::getRadiusServerSyncData()` : ajouter la requête
2. **Noeud** — `sync_client.php` dans `applyPullData()` : ajouter le traitement

### C. Push sync (noeud → central)

1. **Noeud** — `sync_client.php` dans `collectPushData()` : collecter les données
2. **Central** — `RadiusDatabase::importNodeSyncData()` : importer les données

### D. Query GET (lecture temps réel)

1. **Noeud** — `webhook.php` : ajouter handler GET `?action=hotspot_status`
2. **Central** — `NodePushService.php` : ajouter `queryNodeHotspotStatus()`
3. **Central** — Controller + route API + bouton UI
