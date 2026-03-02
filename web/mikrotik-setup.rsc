# Supprimer ancien script/scheduler si existant
/system script remove [find name="nas-cmd"]
/system scheduler remove [find name="nas-cmd"]

# Creer le script de polling NAS avec systeme de queue
/system script add name="nas-cmd" policy=ftp,reboot,read,write,policy,test,password,sniff,romon,sensitive source={
    :local routerId [/system identity get name]
    :local nasUrl "http://nas.test/web/fetch_cmd.php"

    :do {
        # Recuperer la prochaine commande
        /tool fetch url=($nasUrl . "?router=" . $routerId) mode=http output=file dst-path="nas-cmd.rsc"
        :delay 500ms

        # Lire le contenu du fichier
        :local fileContent ""
        :do {
            :set fileContent [/file get [find name="nas-cmd.rsc"] contents]
        } on-error={}

        # Verifier si c'est une commande (commence par "# CMD:")
        :if ([:pick $fileContent 0 6] = "# CMD:") do={
            # Extraire l'ID de la commande (numero seulement, jusqu'au premier \r ou \n)
            :local lineEnd [:find $fileContent "\n"]
            :if ([:typeof $lineEnd] = "nil") do={ :set lineEnd [:len $fileContent] }
            :local cmdLine [:pick $fileContent 6 $lineEnd]

            # Nettoyer les caracteres \r eventuels
            :local cmdId ""
            :for i from=0 to=([:len $cmdLine] - 1) do={
                :local char [:pick $cmdLine $i ($i + 1)]
                :if ($char ~ "^[0-9]\$") do={
                    :set cmdId ($cmdId . $char)
                }
            }

            :log info ("NAS: Commande recue ID=" . $cmdId)

            # Importer et executer la commande
            :do {
                /import file-name="nas-cmd.rsc"
                :delay 1s

                # Confirmer l'execution au serveur
                :local confirmUrl ($nasUrl . "?router=" . $routerId . "&done=" . $cmdId)
                :log info ("NAS: Confirmation " . $confirmUrl)
                :do {
                    /tool fetch url=$confirmUrl mode=http output=none
                    :log info ("NAS: Commande " . $cmdId . " confirmee")
                } on-error={
                    :log warning ("NAS: Echec confirmation " . $cmdId)
                }
            } on-error={
                :log error ("NAS: Erreur execution " . $cmdId)
            }
        }

        # Supprimer le fichier local
        :do {
            /file remove "nas-cmd.rsc"
        } on-error={}

    } on-error={}
}

:delay 1
/system scheduler add name="nas-cmd" interval="10s" on-event="/system script run nas-cmd" start-time=startup policy=ftp,reboot,read,write,policy,test,password,sniff,romon,sensitive

:put "Installation terminee!"
:log info "NAS: Systeme de queue installe"
