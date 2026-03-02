<?php
/**
 * Documentation contextuelle par page
 * Chaque entrée correspond à un code de page (paramètre ?page=...)
 *
 * Structure :
 *   'title'       => Titre affiché dans le panneau d'aide
 *   'description' => Description générale de la page
 *   'features'    => Liste des fonctionnalités principales
 *   'tips'        => Astuce ou conseil (optionnel)
 *   'youtube_url' => URL de la vidéo tutoriel (vide = pas de bouton)
 */
return [

    'dashboard' => [
        'title' => 'Tableau de bord',
        'description' => 'Vue d\'ensemble de votre système. Le dashboard affiche en temps réel l\'état de vos services, le nombre de sessions actives, les statistiques de connexion et les graphiques de trafic.',
        'features' => [
            'Sessions actives en temps réel (Hotspot & PPPoE)',
            'Statistiques de connexion et de trafic',
            'Graphiques d\'utilisation de la bande passante',
            'État des routeurs NAS connectés',
            'Résumé des revenus et transactions récentes',
        ],
        'tips' => 'Le dashboard se rafraîchit automatiquement. Cliquez sur les cartes de statistiques pour accéder aux détails.',
        'youtube_url' => '',
    ],

    'vouchers' => [
        'title' => 'Vouchers & Tickets',
        'description' => 'Gestion des vouchers (tickets WiFi) pour le Hotspot. Créez, imprimez et gérez des lots de vouchers avec différents profils de connexion.',
        'features' => [
            'Génération de vouchers par lot',
            'Attribution de profils de connexion (durée, débit, quota)',
            'Impression de tickets personnalisés',
            'Suivi de l\'utilisation des vouchers',
            'Filtrage par statut (actif, utilisé, expiré)',
            'Export des vouchers',
        ],
        'tips' => 'Utilisez la génération par lot pour créer rapidement un grand nombre de vouchers avec le même profil.',
        'youtube_url' => '',
    ],

    'profiles' => [
        'title' => 'Profils de connexion',
        'description' => 'Configuration des profils qui définissent les paramètres de connexion des utilisateurs : durée, débit, quota de données et tarification.',
        'features' => [
            'Création de profils avec durée limitée ou illimitée',
            'Configuration du débit (upload/download)',
            'Définition de quotas de données',
            'Tarification par profil',
            'Partage de connexion simultanée',
            'Attribution aux vouchers et utilisateurs',
        ],
        'tips' => 'Créez des profils variés pour répondre à différents besoins : pass horaire, journalier, hebdomadaire, mensuel.',
        'youtube_url' => '',
    ],

    'sessions' => [
        'title' => 'Sessions actives',
        'description' => 'Visualisation et gestion des sessions de connexion actives sur le Hotspot. Surveillez qui est connecté en temps réel.',
        'features' => [
            'Liste des sessions actives en temps réel',
            'Détails de chaque session (IP, MAC, durée, trafic)',
            'Déconnexion manuelle d\'un utilisateur',
            'Filtrage par NAS/routeur',
            'Historique des sessions passées',
        ],
        'tips' => 'Vous pouvez déconnecter un utilisateur en cliquant sur le bouton de déconnexion à côté de sa session.',
        'youtube_url' => '',
    ],

    'pppoe' => [
        'title' => 'PPPoE - Plans & Abonnés',
        'description' => 'Gestion des connexions PPPoE pour les abonnements Internet. Créez des plans de service et gérez vos abonnés PPPoE.',
        'features' => [
            'Création de plans PPPoE (débit, prix, durée)',
            'Gestion des abonnés PPPoE',
            'Attribution de plans aux abonnés',
            'Suivi des expirations d\'abonnement',
            'Configuration des paramètres de connexion',
        ],
        'tips' => 'Les plans PPPoE permettent de gérer des abonnements récurrents avec facturation automatique.',
        'youtube_url' => '',
    ],

    'pppoe-user' => [
        'title' => 'Abonnés PPPoE',
        'description' => 'Gestion détaillée des comptes abonnés PPPoE. Ajoutez, modifiez et suivez les abonnements de vos clients.',
        'features' => [
            'Ajout et modification des abonnés',
            'Attribution et changement de plan',
            'Suivi de l\'expiration des abonnements',
            'Historique de connexion par abonné',
            'Activation/désactivation de comptes',
        ],
        'tips' => 'Vous pouvez renouveler un abonnement expiré directement depuis la fiche de l\'abonné.',
        'youtube_url' => '',
    ],

    'pppoe-transactions' => [
        'title' => 'Transactions PPPoE',
        'description' => 'Historique de toutes les transactions liées aux abonnements PPPoE : paiements, renouvellements et changements de plan.',
        'features' => [
            'Historique complet des transactions PPPoE',
            'Détails de chaque paiement',
            'Filtrage par date, abonné ou plan',
            'Suivi des renouvellements',
            'Export des transactions',
        ],
        'youtube_url' => '',
    ],

    'network' => [
        'title' => 'Réseau',
        'description' => 'Configuration et surveillance du réseau. Gérez les paramètres réseau de vos routeurs MikroTik.',
        'features' => [
            'Vue d\'ensemble du réseau',
            'Configuration des interfaces réseau',
            'Gestion des pools d\'adresses IP',
            'Configuration DHCP',
            'Surveillance de la connectivité',
        ],
        'tips' => 'Assurez-vous que vos pools d\'adresses IP sont correctement dimensionnés pour le nombre d\'utilisateurs attendus.',
        'youtube_url' => '',
    ],

    'billing' => [
        'title' => 'Facturation',
        'description' => 'Gestion de la facturation et des paiements. Configurez les méthodes de paiement et suivez les revenus.',
        'features' => [
            'Configuration des méthodes de paiement',
            'Suivi des revenus par période',
            'Historique des factures',
            'Rapports financiers',
            'Intégration des passerelles de paiement',
        ],
        'youtube_url' => '',
    ],

    'zones' => [
        'title' => 'Zones',
        'description' => 'Organisation géographique de vos points d\'accès. Regroupez vos routeurs par zone pour une gestion simplifiée.',
        'features' => [
            'Création de zones géographiques',
            'Attribution de routeurs aux zones',
            'Statistiques par zone',
            'Gestion des vendeurs par zone',
        ],
        'tips' => 'Les zones permettent de segmenter vos points d\'accès et d\'attribuer des vendeurs spécifiques à chaque zone.',
        'youtube_url' => '',
    ],

    'nas' => [
        'title' => 'Routeurs NAS',
        'description' => 'Gestion des routeurs MikroTik (NAS). Ajoutez, configurez et surveillez vos routeurs connectés à la plateforme.',
        'features' => [
            'Ajout et configuration de routeurs MikroTik',
            'Surveillance de l\'état de connexion',
            'Test de connectivité',
            'Configuration des paramètres API',
            'Attribution aux zones',
        ],
        'tips' => 'Vérifiez que l\'API MikroTik est activée sur votre routeur et que le port API (8728) est accessible.',
        'youtube_url' => '',
    ],

    'nas-map' => [
        'title' => 'Carte des NAS',
        'description' => 'Visualisation géographique de vos routeurs sur une carte interactive. Localisez rapidement vos équipements.',
        'features' => [
            'Carte interactive avec positionnement des routeurs',
            'État de connexion en temps réel sur la carte',
            'Informations au survol (nom, IP, statut)',
        ],
        'youtube_url' => '',
    ],

    'bandwidth' => [
        'title' => 'Bande passante',
        'description' => 'Surveillance et gestion de la bande passante. Visualisez l\'utilisation du débit en temps réel.',
        'features' => [
            'Graphiques de bande passante en temps réel',
            'Utilisation par interface',
            'Historique de consommation',
            'Alertes de saturation',
        ],
        'youtube_url' => '',
    ],

    'monitoring' => [
        'title' => 'Supervision',
        'description' => 'Surveillance globale de l\'infrastructure. Vérifiez l\'état de vos routeurs, services et connexions.',
        'features' => [
            'État de santé des routeurs',
            'Alertes en cas de panne',
            'Graphiques de performance',
            'Historique de disponibilité',
            'Surveillance des ressources (CPU, RAM)',
        ],
        'tips' => 'Consultez régulièrement le monitoring pour anticiper les problèmes de capacité.',
        'youtube_url' => '',
    ],

    'users' => [
        'title' => 'Utilisateurs',
        'description' => 'Gestion des comptes utilisateurs de la plateforme (administrateurs, vendeurs, gérants). Configurez les accès et permissions.',
        'features' => [
            'Création de comptes utilisateurs',
            'Attribution de rôles (admin, vendeur, gérant)',
            'Gestion des permissions',
            'Activation/désactivation de comptes',
            'Historique d\'activité',
        ],
        'tips' => 'Créez des comptes vendeurs pour vos points de vente et limitez leurs accès aux fonctionnalités nécessaires.',
        'youtube_url' => '',
    ],

    'transactions' => [
        'title' => 'Transactions',
        'description' => 'Historique de toutes les transactions Hotspot : ventes de vouchers, paiements en ligne et transactions manuelles.',
        'features' => [
            'Historique complet des transactions',
            'Filtrage par date, type, vendeur',
            'Détails de chaque transaction',
            'Résumé des revenus',
            'Export des données',
        ],
        'youtube_url' => '',
    ],

    'logs' => [
        'title' => 'Journaux',
        'description' => 'Journaux d\'activité du système. Consultez l\'historique des actions effectuées par les utilisateurs et le système.',
        'features' => [
            'Journal des connexions/déconnexions',
            'Actions des administrateurs',
            'Erreurs système',
            'Filtrage par type et date',
        ],
        'youtube_url' => '',
    ],

    'payments' => [
        'title' => 'Paiements',
        'description' => 'Configuration et gestion des passerelles de paiement. Activez les paiements en ligne pour vos services.',
        'features' => [
            'Configuration des passerelles (FedaPay, CinetPay, etc.)',
            'Suivi des paiements en ligne',
            'Historique des transactions de paiement',
            'Test des passerelles',
        ],
        'tips' => 'Testez votre passerelle de paiement en mode sandbox avant de passer en production.',
        'youtube_url' => '',
    ],

    'library' => [
        'title' => 'Bibliothèque',
        'description' => 'Bibliothèque de ressources et fichiers. Gérez les médias et documents utilisés dans vos portails captifs.',
        'features' => [
            'Upload de fichiers (images, logos)',
            'Gestion des médias pour portails captifs',
            'Organisation des ressources',
        ],
        'youtube_url' => '',
    ],

    'voucher-templates' => [
        'title' => 'Modèles de tickets',
        'description' => 'Personnalisation des modèles d\'impression de vouchers. Créez des designs uniques pour vos tickets WiFi.',
        'features' => [
            'Création de modèles de tickets personnalisés',
            'Éditeur visuel de mise en page',
            'Variables dynamiques (code, durée, prix, etc.)',
            'Aperçu avant impression',
            'Plusieurs modèles par défaut',
        ],
        'tips' => 'Utilisez les variables comme {code}, {profile}, {price} pour insérer automatiquement les informations du voucher.',
        'youtube_url' => '',
    ],

    'hotspot-templates' => [
        'title' => 'Templates Hotspot',
        'description' => 'Gestion des templates de page de connexion MikroTik. Personnalisez l\'apparence de la page de login Hotspot.',
        'features' => [
            'Templates de page de connexion prédéfinis',
            'Personnalisation des couleurs et logos',
            'Déploiement sur les routeurs MikroTik',
            'Aperçu du template',
        ],
        'youtube_url' => '',
    ],

    'captive-portal' => [
        'title' => 'Portail captif',
        'description' => 'Configuration du portail captif avancé. Créez des pages de connexion modernes avec achat de voucher intégré.',
        'features' => [
            'Page de connexion personnalisable',
            'Achat de voucher en ligne intégré',
            'Multi-langues',
            'Intégration des passerelles de paiement',
            'Personnalisation du design',
        ],
        'tips' => 'Le portail captif permet à vos clients d\'acheter et utiliser des vouchers directement depuis la page de connexion WiFi.',
        'youtube_url' => '',
    ],

    'captive-portal-editor' => [
        'title' => 'Éditeur de portail captif',
        'description' => 'Éditeur visuel pour personnaliser votre portail captif. Modifiez les textes, couleurs, images et la mise en page.',
        'features' => [
            'Éditeur visuel drag & drop',
            'Personnalisation des couleurs et polices',
            'Upload de logo et images',
            'Aperçu en temps réel',
            'Sauvegarde des modifications',
        ],
        'youtube_url' => '',
    ],

    'settings' => [
        'title' => 'Paramètres',
        'description' => 'Configuration générale de la plateforme. Personnalisez le nom, la langue, le fuseau horaire et autres paramètres.',
        'features' => [
            'Nom et branding de la plateforme',
            'Langue et fuseau horaire',
            'Configuration email (SMTP)',
            'Paramètres de sécurité',
            'Préférences d\'affichage',
        ],
        'youtube_url' => '',
    ],

    'sales' => [
        'title' => 'Ventes',
        'description' => 'Suivi des ventes et performances commerciales. Analysez les ventes par vendeur, zone et période.',
        'features' => [
            'Tableau de bord des ventes',
            'Performances par vendeur',
            'Ventes par zone géographique',
            'Rapports par période',
            'Objectifs de vente',
        ],
        'youtube_url' => '',
    ],

    'loyalty' => [
        'title' => 'Programme de fidélité',
        'description' => 'Gestion du programme de fidélité client. Récompensez vos clients réguliers avec des avantages et réductions.',
        'features' => [
            'Configuration des règles de fidélité',
            'Accumulation de points',
            'Récompenses et avantages',
            'Suivi des points par client',
        ],
        'youtube_url' => '',
    ],

    'modules' => [
        'title' => 'Modules',
        'description' => 'Gestion des modules et extensions de la plateforme. Activez ou désactivez les fonctionnalités supplémentaires.',
        'features' => [
            'Liste des modules disponibles',
            'Activation/désactivation de modules',
            'Souscription avec choix de durée',
            'Suivi des abonnements actifs',
            'Coût en crédits CRT',
        ],
        'tips' => 'Vous pouvez souscrire à un module pour plusieurs mois en une seule fois pour bénéficier d\'une utilisation continue.',
        'youtube_url' => '',
    ],

    'chat' => [
        'title' => 'Chat',
        'description' => 'Messagerie interne de la plateforme. Communiquez avec vos équipes et gérez les conversations.',
        'features' => [
            'Messagerie en temps réel',
            'Conversations individuelles et de groupe',
            'Notifications de nouveaux messages',
        ],
        'youtube_url' => '',
    ],

    'telegram' => [
        'title' => 'Telegram',
        'description' => 'Intégration de Telegram pour les notifications et l\'envoi de messages automatiques à vos clients.',
        'features' => [
            'Configuration du bot Telegram',
            'Envoi de notifications automatiques',
            'Messages de bienvenue et d\'expiration',
            'Notifications de paiement',
        ],
        'tips' => 'Créez un bot Telegram via @BotFather pour obtenir le token nécessaire à la configuration.',
        'youtube_url' => '',
    ],

    'whatsapp' => [
        'title' => 'WhatsApp',
        'description' => 'Intégration de WhatsApp pour la communication avec vos clients. Envoyez des notifications et messages automatiques.',
        'features' => [
            'Configuration de l\'API WhatsApp',
            'Envoi de messages automatiques',
            'Notifications de vouchers et abonnements',
            'Templates de messages',
        ],
        'youtube_url' => '',
    ],

    'sms' => [
        'title' => 'SMS',
        'description' => 'Gestion de l\'envoi de SMS. Configurez les passerelles SMS pour envoyer des notifications à vos clients.',
        'features' => [
            'Configuration des passerelles SMS',
            'Envoi de SMS individuels ou en masse',
            'Templates de messages SMS',
            'Historique d\'envoi',
            'Suivi des crédits CSMS',
        ],
        'tips' => 'Utilisez le système CSMS (crédits SMS plateforme) pour un envoi simplifié sans configuration externe.',
        'youtube_url' => '',
    ],

    'otp' => [
        'title' => 'OTP - Vérification',
        'description' => 'Configuration de la vérification par code OTP (One-Time Password). Sécurisez les connexions avec une vérification par SMS.',
        'features' => [
            'Activation de la vérification OTP',
            'Configuration du fournisseur SMS',
            'Personnalisation du message OTP',
            'Durée de validité du code',
        ],
        'youtube_url' => '',
    ],

    'marketing' => [
        'title' => 'Marketing',
        'description' => 'Outils marketing pour promouvoir vos services. Créez des campagnes et gérez la communication client.',
        'features' => [
            'Création de campagnes marketing',
            'Envoi de messages promotionnels',
            'Ciblage par zone ou profil client',
            'Suivi des campagnes',
        ],
        'youtube_url' => '',
    ],

    'subscription' => [
        'title' => 'Abonnement',
        'description' => 'Gestion de l\'abonnement à la plateforme. Consultez votre plan actuel, vos crédits et gérez votre renouvellement.',
        'features' => [
            'Détails du plan d\'abonnement actuel',
            'Solde de crédits CRT',
            'Historique des paiements d\'abonnement',
            'Renouvellement d\'abonnement',
        ],
        'youtube_url' => '',
    ],

    'topology' => [
        'title' => 'Topologie réseau',
        'description' => 'Visualisation de la topologie de votre réseau. Affichez les connexions entre vos routeurs et équipements.',
        'features' => [
            'Schéma visuel du réseau',
            'Connexions entre équipements',
            'État des liens en temps réel',
        ],
        'youtube_url' => '',
    ],

    // --- Pages SuperAdmin ---

    'superadmin-admins' => [
        'title' => 'Gestion des administrateurs',
        'description' => 'Gestion des comptes administrateurs de la plateforme. Créez, modifiez et surveillez les comptes admin.',
        'features' => [
            'Liste de tous les administrateurs',
            'Création de nouveaux comptes admin',
            'Attribution des permissions',
            'Suivi de l\'activité des admins',
            'Gestion des crédits par admin',
        ],
        'youtube_url' => '',
    ],

    'superadmin-permissions' => [
        'title' => 'Permissions',
        'description' => 'Configuration des permissions et rôles. Définissez les accès pour chaque rôle utilisateur.',
        'features' => [
            'Définition des rôles (admin, vendeur, gérant)',
            'Permissions par page',
            'Permissions par fonctionnalité',
            'Personnalisation des accès',
        ],
        'youtube_url' => '',
    ],

    'superadmin-settings' => [
        'title' => 'Paramètres SuperAdmin',
        'description' => 'Configuration globale de la plateforme. Paramètres système, tarification et configurations avancées.',
        'features' => [
            'Paramètres système globaux',
            'Configuration des tarifs (CRT, modules)',
            'Passerelles de paiement',
            'Configuration SMS (CSMS)',
            'Paramètres de sécurité avancés',
        ],
        'youtube_url' => '',
    ],

    'superadmin-module-pricing' => [
        'title' => 'Tarification des modules',
        'description' => 'Configuration des prix des modules. Définissez le coût en crédits CRT pour chaque module de la plateforme.',
        'features' => [
            'Prix par module en CRT',
            'Type de facturation (mensuel ou unique)',
            'Activation/désactivation de modules',
            'Description des modules',
        ],
        'youtube_url' => '',
    ],

    'superadmin-transactions' => [
        'title' => 'Transactions SuperAdmin',
        'description' => 'Historique de toutes les transactions de la plateforme. Vue globale des paiements et mouvements de crédits.',
        'features' => [
            'Transactions de tous les administrateurs',
            'Rechargements de crédits CRT',
            'Souscriptions aux modules',
            'Filtrage et export des données',
        ],
        'youtube_url' => '',
    ],

];
