# Scripts MikroTik pour la synchronisation

## Architecture

Comme le MikroTik n'a pas d'IP publique et n'est pas joignable depuis le serveur,
l'architecture utilise un modèle **push** :

```
MikroTik (LAN) ──push sessions──> Serveur (Cloud)
MikroTik (LAN) <──pull disconnects── Serveur (Cloud)
```

Le MikroTik :
1. Envoie les sessions actives au serveur toutes les minutes
2. Récupère les demandes de déconnexion en attente
3. Exécute les déconnexions et confirme au serveur

## Installation

### 1. Modifier l'URL du serveur

Ouvrez le fichier `sync-sessions.rsc` et modifiez cette ligne :
```routeros
:local serverBase "http://VOTRE_IP_SERVEUR/web/api.php"
```

Remplacez `VOTRE_IP_SERVEUR` par l'adresse de votre serveur (ex: `192.168.1.100` ou `monserveur.com`).

### 2. Ajouter le script dans MikroTik

**Option A - Via Winbox/WebFig :**
1. Aller dans System > Scripts
2. Cliquer sur "Add New"
3. Name: `sync-sessions`
4. Source: Coller le contenu du fichier `sync-sessions.rsc`
5. Cliquer OK

**Option B - Via Terminal :**
```routeros
/system script add name="sync-sessions" source={
    # Coller le contenu ici
}
```

### 3. Créer le scheduler

```routeros
/system scheduler add name="sync-sessions" interval=1m on-event="sync-sessions" start-time=startup
```

### 4. Tester

Pour tester manuellement :
```routeros
/system script run sync-sessions
```

Voir les logs :
```routeros
/log print where topics~"script"
```

## Version simplifiée (une ligne)

Si vous préférez une installation rapide, exécutez ces commandes dans le terminal MikroTik :

```routeros
# Remplacez l'URL
:global syncUrl "http://192.168.1.100/web/api.php"

/system script add name="sync-sessions" source={
:local serverBase "http://192.168.1.100/web/api.php"
:local syncUrl ($serverBase."?route=/sessions/sync")
:local json "{\"sessions\":["
:local first true
:foreach i in=[/ip hotspot active find] do={
:if (!$first) do={:set json ($json.",")}
:set first false
:local u [/ip hotspot active get $i user]
:local a [/ip hotspot active get $i address]
:local m [/ip hotspot active get $i mac-address]
:local t [/ip hotspot active get $i uptime]
:local s [/ip hotspot active get $i session-id]
:local bi [/ip hotspot active get $i bytes-in]
:local bo [/ip hotspot active get $i bytes-out]
:set json ($json."{\"user\":\"".$u."\",\"address\":\"".$a."\",\"mac-address\":\"".$m."\",\"uptime\":\"".$t."\",\"session-id\":\"".$s."\",\"bytes-in\":".$bi.",\"bytes-out\":".$bo."}")
}
:set json ($json."]}")
:do {/tool fetch url=$syncUrl mode=http http-method=post http-data=$json http-header-field="Content-Type:application/json" output=none} on-error={}
:local dUrl ($serverBase."?route=/sessions/pending-disconnects")
:local cUrl ($serverBase."?route=/sessions/confirm-disconnect")
:do {
:local r [/tool fetch url=$dUrl mode=http output=user as-value]
:local d ($r->"data")
:local p 0
:while ([:find $d "\"username\":\"" $p]!=nil) do={
:local st ([:find $d "\"username\":\"" $p]+12)
:local en [:find $d "\"" $st]
:local un [:pick $d $st $en]
:set p ($en+1)
:if ([:len $un]>0) do={
:do {
/ip hotspot active remove [find user=$un]
/tool fetch url=$cUrl mode=http http-method=post http-data=("{\"username\":\"".$un."\"}") http-header-field="Content-Type:application/json" output=none
} on-error={}
}
}
} on-error={}
}

/system scheduler add name="sync-sessions" interval=1m on-event="sync-sessions" start-time=startup
```

## Fonctionnement

### Synchronisation des sessions

Le script collecte ces informations pour chaque session active :
- `user` : Nom d'utilisateur (voucher/ticket)
- `address` : Adresse IP du client
- `mac-address` : Adresse MAC du client
- `uptime` : Durée de connexion
- `session-id` : ID unique de session
- `bytes-in` : Données téléchargées
- `bytes-out` : Données uploadées

### Déconnexion depuis le serveur

Quand vous cliquez sur "Déconnecter" dans l'interface web :
1. Le serveur enregistre une demande de déconnexion
2. Le MikroTik récupère cette demande lors du prochain sync (max 1 minute)
3. Le MikroTik déconnecte l'utilisateur
4. Le MikroTik confirme la déconnexion au serveur

### Endpoints API utilisés

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/sessions/sync` | POST | Recevoir les sessions du MikroTik |
| `/sessions/pending-disconnects` | GET | Liste des utilisateurs à déconnecter |
| `/sessions/confirm-disconnect` | POST | Confirmer une déconnexion |

## Dépannage

### Le script ne s'exécute pas
Vérifiez que le scheduler est actif :
```routeros
/system scheduler print
```

### Erreur de connexion au serveur
Vérifiez que le MikroTik peut atteindre le serveur :
```routeros
/ping 192.168.1.100 count=3
```

### Les sessions ne s'affichent pas
Vérifiez les logs du script :
```routeros
/log print where topics~"script"
```

Testez l'URL manuellement :
```routeros
/tool fetch url="http://VOTRE_SERVEUR/web/api.php?route=/sessions/sync" mode=http http-method=post http-data="{\"sessions\":[]}" output=user
```
