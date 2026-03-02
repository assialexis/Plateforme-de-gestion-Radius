# Script MikroTik pour synchroniser les sessions Hotspot avec le serveur
# Ce script fait deux choses:
# 1. Envoie les sessions actives au serveur
# 2. Récupère et exécute les demandes de déconnexion
#
# INSTALLATION:
# 1. Modifier l'URL du serveur ci-dessous
# 2. Copier dans System > Scripts avec le nom "sync-sessions"
# 3. Créer un scheduler: /system scheduler add name="sync-sessions" interval=1m on-event="sync-sessions"
#
# CONFIGURATION - MODIFIER CETTE LIGNE:
:local serverBase "http://VOTRE_IP_SERVEUR/web/api.php"

# =============================================
# PARTIE 1: Envoyer les sessions actives
# =============================================
:local syncUrl ($serverBase . "?route=/sessions/sync")
:local json "{\"sessions\":["
:local first true

:foreach i in=[/ip hotspot active find] do={
    :if (!$first) do={ :set json ($json . ",") }
    :set first false

    :local u [/ip hotspot active get $i user]
    :local a [/ip hotspot active get $i address]
    :local m [/ip hotspot active get $i mac-address]
    :local t [/ip hotspot active get $i uptime]
    :local s [/ip hotspot active get $i session-id]
    :local bi [/ip hotspot active get $i bytes-in]
    :local bo [/ip hotspot active get $i bytes-out]

    :set json ($json . "{\"user\":\"" . $u . "\",\"address\":\"" . $a . "\",\"mac-address\":\"" . $m . "\",\"uptime\":\"" . $t . "\",\"session-id\":\"" . $s . "\",\"bytes-in\":" . $bi . ",\"bytes-out\":" . $bo . "}")
}

:set json ($json . "]}")

# Envoyer les sessions
:do {
    /tool fetch url=$syncUrl mode=http http-method=post http-data=$json http-header-field="Content-Type:application/json" output=none
} on-error={
    :log warning "Sync sessions: Failed to send sessions to server"
}

# =============================================
# PARTIE 2: Récupérer les demandes de déconnexion
# =============================================
:local disconnectUrl ($serverBase . "?route=/sessions/pending-disconnects")
:local confirmUrl ($serverBase . "?route=/sessions/confirm-disconnect")

:do {
    # Récupérer les demandes
    :local response [/tool fetch url=$disconnectUrl mode=http output=user as-value]
    :local data ($response->"data")

    # Parser le JSON pour trouver les usernames à déconnecter
    # Format attendu: {"success":true,"data":{"disconnects":[{"username":"user1"},...]}}

    # Chercher chaque username dans la réponse et déconnecter
    :local pos 0
    :while ([:find $data "\"username\":\"" $pos] != nil) do={
        :local start ([:find $data "\"username\":\"" $pos] + 12)
        :local end [:find $data "\"" $start]
        :local username [:pick $data $start $end]
        :set pos ($end + 1)

        :if ([:len $username] > 0) do={
            :log info ("Sync sessions: Disconnecting user " . $username)

            # Déconnecter l'utilisateur
            :do {
                /ip hotspot active remove [find user=$username]

                # Confirmer la déconnexion
                :local confirmData ("{\"username\":\"" . $username . "\"}")
                /tool fetch url=$confirmUrl mode=http http-method=post http-data=$confirmData http-header-field="Content-Type:application/json" output=none

                :log info ("Sync sessions: User " . $username . " disconnected and confirmed")
            } on-error={
                :log warning ("Sync sessions: Could not disconnect user " . $username)
            }
        }
    }
} on-error={
    :log warning "Sync sessions: Failed to fetch disconnect requests"
}
