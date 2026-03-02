# Scripts Hotspot On-Login / On-Logout
# Cette méthode utilise les hooks natifs du hotspot MikroTik
#
# AVANTAGE: Notification instantanée (pas besoin d'attendre le scheduler)
#
# INSTALLATION:
# 1. Créer les scripts ci-dessous
# 2. Configurer le profil hotspot pour les utiliser

# =============================================
# CONFIGURATION - MODIFIER CETTE LIGNE
# =============================================
:global radiusServerUrl "http://VOTRE_IP_SERVEUR/web/api.php"

# =============================================
# Script On-Login (quand un utilisateur se connecte)
# =============================================
# Nom du script: hotspot-on-login
#
# Variables disponibles automatiquement:
# $user - username
# $address - IP address
# $mac-address - MAC address
# $session-id - Session ID

/system script add name="hotspot-on-login" policy=ftp,reboot,read,write,policy,test,password,sniff,romon,sensitive source={
:global radiusServerUrl
:local url ($radiusServerUrl . "?route=/sessions/login")
:local json "{\"user\":\"$user\",\"address\":\"$address\",\"mac-address\":\"$\"mac-address\"\",\"session-id\":\"$\"session-id\"\"}"
:do {
    /tool fetch url=$url mode=http http-method=post http-data=$json http-header-field="Content-Type:application/json" output=none
} on-error={
    :log warning ("Hotspot login notify failed for " . $user)
}
}

# =============================================
# Script On-Logout (quand un utilisateur se déconnecte)
# =============================================
# Nom du script: hotspot-on-logout

/system script add name="hotspot-on-logout" policy=ftp,reboot,read,write,policy,test,password,sniff,romon,sensitive source={
:global radiusServerUrl
:local url ($radiusServerUrl . "?route=/sessions/logout")
:local json "{\"user\":\"$user\",\"session-id\":\"$\"session-id\"\"}"
:do {
    /tool fetch url=$url mode=http http-method=post http-data=$json http-header-field="Content-Type:application/json" output=none
} on-error={
    :log warning ("Hotspot logout notify failed for " . $user)
}
}

# =============================================
# Script de synchronisation des sessions (optionnel)
# =============================================
# Ce script envoie la liste des utilisateurs actifs au NAS
# pour fermer les sessions orphelines dans la base RADIUS
# Exécuté toutes les 60 secondes via scheduler

/system script add name="hotspot-sync-sessions" policy=ftp,reboot,read,write,policy,test,password,sniff,romon,sensitive source={
:global radiusServerUrl
:local url ($radiusServerUrl . "?route=/sessions/sync")

# Construire la liste JSON des utilisateurs actifs
:local json "{\"sessions\":["
:local first true
:foreach i in=[/ip hotspot active find] do={
    :local user [/ip hotspot active get $i user]
    :local address [/ip hotspot active get $i address]
    :local mac [/ip hotspot active get $i mac-address]
    :local uptime [/ip hotspot active get $i uptime]
    :local bytesIn [/ip hotspot active get $i bytes-in]
    :local bytesOut [/ip hotspot active get $i bytes-out]

    :if ($first = false) do={ :set json ($json . ",") }
    :set json ($json . "{\"user\":\"" . $user . "\",\"address\":\"" . $address . "\",\"mac-address\":\"" . $mac . "\",\"uptime\":\"" . $uptime . "\",\"bytes-in\":" . $bytesIn . ",\"bytes-out\":" . $bytesOut . "}")
    :set first false
}
:set json ($json . "]}")

:do {
    /tool fetch url=$url mode=http http-method=post http-data=$json http-header-field="Content-Type:application/json" output=none
} on-error={
    :log warning "Hotspot sync failed"
}
}

# Scheduler pour sync toutes les 60 secondes
/system scheduler add name="hotspot-sync" interval=1m on-event="/system script run hotspot-sync-sessions" policy=ftp,reboot,read,write,policy,test,password,sniff,romon,sensitive

# =============================================
# Configurer le profil hotspot
# =============================================
# Remplacez "default" par le nom de votre profil

/ip hotspot profile
set [find name="default"] on-login="hotspot-on-login" on-logout="hotspot-on-logout"
