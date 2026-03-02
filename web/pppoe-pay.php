<?php
/**
 * Page publique de paiement pour les clients PPPoE
 * Permet de payer les factures impayées ou d'étendre l'abonnement
 */

// Configuration
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Charger les dépendances
require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';
require_once __DIR__ . '/../src/Payment/PaymentService.php';

// Charger la configuration
$config = require __DIR__ . '/../config/config.php';

// Initialiser la base de données
try {
    $db = new RadiusDatabase($config['database']);
} catch (Exception $e) {
    die('Database connection failed');
}

$cancelled = isset($_GET['cancelled']);
$adminId = isset($_GET['admin']) ? (int)$_GET['admin'] : null;
$currency = $config['currency'] ?? 'XAF';
$appName = 'RADIUS Manager';

// Si un username est fourni, déduire l'admin depuis l'utilisateur PPPoE
if ($adminId === null && !empty($_GET['username'])) {
    try {
        $stmt = $db->getPdo()->prepare("SELECT admin_id FROM pppoe_users WHERE username = ? AND admin_id IS NOT NULL LIMIT 1");
        $stmt->execute([$_GET['username']]);
        $row = $stmt->fetch();
        if ($row) {
            $adminId = (int)$row['admin_id'];
        }
    } catch (Exception $e) {}
}

// Si toujours pas d'admin, utiliser le premier admin
if ($adminId === null) {
    try {
        $stmt = $db->getPdo()->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            $adminId = (int)$row['id'];
        }
    } catch (Exception $e) {}
}

// Charger le nom de l'app depuis les settings (scopé par admin)
try {
    if ($adminId !== null) {
        $stmt = $db->getPdo()->prepare("SELECT setting_value FROM settings WHERE setting_key = 'app_name' AND admin_id = ?");
        $stmt->execute([$adminId]);
    } else {
        $stmt = $db->getPdo()->query("SELECT setting_value FROM settings WHERE setting_key = 'app_name'");
    }
    $row = $stmt->fetch();
    if ($row) {
        $appName = $row['setting_value'];
    }
} catch (Exception $e) {}

// Charger les passerelles actives (scopées par admin)
$gateways = $db->getActivePaymentGateways($adminId);

// Charger les passerelles plateforme actives pour cet admin
$platformGateways = [];
try {
    require_once __DIR__ . '/../src/Payment/PlatformPaymentService.php';
    $platformPaymentService = new PlatformPaymentService($db->getPdo(), $config);
    if ($adminId) {
        $platformGateways = $platformPaymentService->getActiveForAdmin($adminId);
    }
} catch (Exception $e) {
    // Silently fail — platform gateways optional
}

// Fonctions de formatage locales
function pppoeFormatPrice($amount, $currency) {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}

function pppoeFormatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

function pppoeFormatDays($days) {
    if (!$days) return '-';
    if ($days == 1) return '1 jour';
    return $days . ' jours';
}

// Récupérer les paramètres de facturation
$billingSettings = [];
try {
    $stmt = $db->getPdo()->query("SELECT setting_key, setting_value FROM billing_settings");
    while ($row = $stmt->fetch()) {
        $billingSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Abonnement - <?= htmlspecialchars($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .gradient-bg { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen">

<div x-data="pppoePay()" x-init="init()" class="min-h-screen py-8 px-4">
    <div class="max-w-2xl mx-auto">

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full gradient-bg text-white mb-4">
                <i class="fas fa-wifi text-2xl"></i>
            </div>
            <?php
            $companyName = $billingSettings['company_name'] ?? $appName;
            $companyPhone = $billingSettings['company_phone'] ?? '';
            ?>
            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($companyName) ?></h1>
            <p class="text-gray-600 mt-2">Paiement Abonnement Internet</p>
            <?php if ($companyPhone): ?>
            <p class="text-sm text-gray-500 mt-1">
                <i class="fas fa-phone mr-1"></i>
                <a href="tel:<?= htmlspecialchars($companyPhone) ?>" class="hover:text-indigo-600"><?= htmlspecialchars($companyPhone) ?></a>
            </p>
            <?php endif; ?>
        </div>

        <?php if ($cancelled): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Paiement annulé. Vous pouvez réessayer quand vous voulez.
        </div>
        <?php endif; ?>

        <!-- Etape 1: Identification du client -->
        <div x-show="step === 'lookup'" x-cloak class="bg-white rounded-2xl shadow-lg p-6 card-hover">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                <span class="w-8 h-8 rounded-full gradient-bg text-white flex items-center justify-center text-sm mr-3">1</span>
                Identifiez-vous
            </h2>

            <form @submit.prevent="lookupUser">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-indigo-500"></i>
                        Nom d'utilisateur PPPoE
                    </label>
                    <input type="text" x-model="username" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="Entrez votre nom d'utilisateur">
                </div>

                <button type="submit" :disabled="loading"
                        class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-medium hover:from-indigo-700 hover:to-purple-700 transition-all disabled:opacity-50">
                    <span x-show="!loading">
                        <i class="fas fa-search mr-2"></i> Rechercher mon compte
                    </span>
                    <span x-show="loading">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Recherche...
                    </span>
                </button>
            </form>

            <div x-show="error" x-cloak class="mt-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span x-text="error"></span>
            </div>
        </div>

        <!-- Etape 2: Affichage du compte et options -->
        <div x-show="step === 'options'" x-cloak>

            <!-- Info client -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 card-hover">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-gray-800" x-text="user?.customer_name || user?.username"></h3>
                        <p class="text-sm text-gray-500">@<span x-text="user?.username"></span></p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium"
                          :class="{
                              'bg-green-100 text-green-800': user?.status === 'active',
                              'bg-amber-100 text-amber-800': user?.status === 'suspended',
                              'bg-red-100 text-red-800': user?.status === 'expired',
                              'bg-gray-100 text-gray-800': !['active','suspended','expired'].includes(user?.status)
                          }"
                          x-text="statusLabels[user?.status] || user?.status"></span>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Profil:</span>
                        <span class="font-medium ml-2" x-text="user?.profile_name"></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Valide jusqu'au:</span>
                        <span class="font-medium ml-2" x-text="formatDate(user?.valid_until)"></span>
                    </div>
                </div>

                <button @click="step = 'lookup'; user = null; error = null;"
                        class="mt-4 text-sm text-indigo-600 hover:text-indigo-800">
                    <i class="fas fa-arrow-left mr-1"></i> Changer de compte
                </button>
            </div>

            <!-- Factures impayées -->
            <div x-show="unpaidInvoices.length > 0" class="bg-white rounded-2xl shadow-lg p-6 mb-6 card-hover">
                <h3 class="font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-file-invoice-dollar mr-2 text-red-500"></i>
                    Factures en attente
                </h3>

                <div class="space-y-3">
                    <template x-for="invoice in unpaidInvoices" :key="invoice.id">
                        <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg cursor-pointer hover:bg-red-100 transition-colors"
                             @click="selectInvoice(invoice)">
                            <div>
                                <div class="font-medium text-gray-800" x-text="invoice.invoice_number"></div>
                                <div class="text-sm text-gray-500">
                                    Echéance: <span x-text="formatDate(invoice.due_date)"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-red-600" x-text="formatPrice(invoice.total_amount - invoice.paid_amount)"></div>
                                <div class="text-xs text-gray-500">à payer</div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Option de prolongation -->
            <div x-show="canExtend" class="bg-white rounded-2xl shadow-lg p-6 mb-6 card-hover">
                <h3 class="font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-calendar-plus mr-2 text-green-500"></i>
                    Prolonger votre abonnement
                </h3>

                <p class="text-sm text-gray-600 mb-4">
                    Votre abonnement est encore actif. Prolongez-le avant son expiration pour éviter toute interruption.
                </p>

                <div class="p-4 bg-green-50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-gray-800" x-text="user?.profile_name"></div>
                            <div class="text-sm text-gray-500">
                                + <span x-text="profile?.validity_days"></span> jours
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-green-600" x-text="formatPrice(profile?.price)"></div>
                        </div>
                    </div>
                    <button @click="selectExtension()"
                            class="mt-4 w-full py-2 px-4 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i> Prolonger
                    </button>
                </div>
            </div>

            <!-- Option de renouvellement (si expiré) -->
            <div x-show="user && user.status === 'expired'" class="bg-white rounded-2xl shadow-lg p-6 mb-6 card-hover">
                <h3 class="font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-redo mr-2 text-blue-500"></i>
                    Renouveler votre abonnement
                </h3>

                <p class="text-sm text-gray-600 mb-4">
                    Votre abonnement a expiré. Renouvelez-le pour continuer à utiliser le service.
                </p>

                <div class="p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-gray-800" x-text="user?.profile_name"></div>
                            <div class="text-sm text-gray-500">
                                <span x-text="profile?.validity_days"></span> jours
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-blue-600" x-text="formatPrice(profile?.price)"></div>
                        </div>
                    </div>
                    <button @click="selectRenewal()"
                            class="mt-4 w-full py-2 px-4 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        <i class="fas fa-redo mr-2"></i> Renouveler
                    </button>
                </div>
            </div>

            <!-- Message si pas d'action possible -->
            <div x-show="unpaidInvoices.length === 0 && !canExtend && user && user.status !== 'expired'"
                 class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <div class="text-center py-8">
                    <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check text-green-500 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2">Tout est à jour!</h3>
                    <p class="text-sm text-gray-600">
                        Vous n'avez pas de factures en attente et votre abonnement est actif.
                    </p>
                </div>
            </div>
        </div>

        <!-- Etape 3: Choix de la passerelle de paiement -->
        <div x-show="step === 'payment'" x-cloak class="bg-white rounded-2xl shadow-lg p-6 card-hover">
            <button @click="step = 'options'; selectedItem = null;"
                    class="mb-4 text-sm text-indigo-600 hover:text-indigo-800">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </button>

            <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                <span class="w-8 h-8 rounded-full gradient-bg text-white flex items-center justify-center text-sm mr-3">2</span>
                Choisir le mode de paiement
            </h2>

            <!-- Récapitulatif -->
            <div class="bg-indigo-50 rounded-lg p-4 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-sm text-gray-600" x-text="selectedItem?.type === 'invoice' ? 'Facture' : 'Abonnement'"></span>
                        <div class="font-medium text-gray-800" x-text="selectedItem?.label"></div>
                    </div>
                    <div class="text-xl font-bold text-indigo-600" x-text="formatPrice(selectedItem?.amount)"></div>
                </div>
            </div>

            <!-- Passerelles -->
            <div class="space-y-3">
                <?php foreach ($gateways as $gateway): ?>
                <button @click="initiatePayment('<?= $gateway['gateway_code'] ?>')"
                        :disabled="loading"
                        class="w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all disabled:opacity-50">
                    <div class="flex items-center">
                        <?php if ($gateway['logo']): ?>
                        <img src="<?= htmlspecialchars($gateway['logo']) ?>" alt="<?= htmlspecialchars($gateway['name']) ?>" class="h-8 mr-3">
                        <?php else: ?>
                        <i class="fas fa-credit-card text-2xl text-gray-400 mr-3"></i>
                        <?php endif; ?>
                        <div class="text-left">
                            <div class="font-medium text-gray-800"><?= htmlspecialchars($gateway['name']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($gateway['description'] ?? 'Paiement sécurisé') ?></div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </button>
                <?php endforeach; ?>

                <?php foreach ($platformGateways as $pgw): ?>
                <button @click="initiatePayment('<?= $pgw['gateway_code'] ?>', true)"
                        :disabled="loading"
                        class="w-full flex items-center justify-between p-4 border-2 border-indigo-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all disabled:opacity-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center mr-3">
                            <?php
                            $pgIcon = match($pgw['gateway_code']) {
                                'fedapay' => 'fa-credit-card',
                                'cinetpay' => 'fa-money-bill-wave',
                                'kkiapay' => 'fa-wallet',
                                'feexpay' => 'fa-exchange-alt',
                                'ligdicash' => 'fa-coins',
                                'paydunya' => 'fa-hand-holding-usd',
                                'paygate_global' => 'fa-globe',
                                'yengapay' => 'fa-money-check-alt',
                                default => 'fa-wallet'
                            };
                            ?>
                            <i class="fas <?= $pgIcon ?> text-xl text-indigo-600"></i>
                        </div>
                        <div class="text-left">
                            <div class="font-medium text-gray-800"><?= htmlspecialchars($pgw['name']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($pgw['description'] ?? 'Paiement sécurisé') ?></div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </button>
                <?php endforeach; ?>
            </div>

            <?php if (empty($gateways) && empty($platformGateways)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-exclamation-triangle text-4xl mb-4 text-amber-500"></i>
                <p>Aucun moyen de paiement disponible pour le moment.</p>
            </div>
            <?php endif; ?>

            <!-- Informations client pour le paiement -->
            <div class="mt-6 pt-6 border-t">
                <h4 class="font-medium text-gray-800 mb-4">Informations de contact</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Téléphone</label>
                        <input type="tel" x-model="customerPhone"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="Ex: 229 97000000">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Email (optionnel)</label>
                        <input type="email" x-model="customerEmail"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="email@exemple.com">
                    </div>
                </div>
            </div>

            <div x-show="error" x-cloak class="mt-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span x-text="error"></span>
            </div>
        </div>

        <!-- Loading overlay -->
        <div x-show="loading" x-cloak
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-indigo-600 mb-4"></i>
                <p class="text-gray-700">Traitement en cours...</p>
            </div>
        </div>

    </div>
</div>

<script>
function pppoePay() {
    return {
        step: 'lookup',
        username: '',
        user: null,
        profile: null,
        unpaidInvoices: [],
        canExtend: false,
        selectedItem: null,
        customerPhone: '',
        customerEmail: '',
        loading: false,
        error: null,
        currency: '<?= $currency ?>',
        adminId: <?= $adminId !== null ? $adminId : 'null' ?>,
        statusLabels: {
            'active': 'Actif',
            'suspended': 'Suspendu',
            'expired': 'Expiré',
            'pending': 'En attente'
        },

        init() {
            // Check URL params for username
            const params = new URLSearchParams(window.location.search);
            if (params.get('username')) {
                this.username = params.get('username');
                this.lookupUser();
            }
        },

        async lookupUser() {
            if (!this.username.trim()) {
                this.error = 'Veuillez entrer votre nom d\'utilisateur';
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                let lookupUrl = `api.php?route=/pppoe-pay/lookup&username=${encodeURIComponent(this.username)}`;
                if (this.adminId) lookupUrl += `&admin=${this.adminId}`;
                const response = await fetch(lookupUrl);
                const data = await response.json();

                if (data.success) {
                    this.user = data.data.user;
                    this.profile = data.data.profile;
                    this.unpaidInvoices = data.data.unpaid_invoices || [];
                    this.canExtend = data.data.can_extend;
                    this.customerPhone = this.user.customer_phone || '';
                    this.customerEmail = this.user.customer_email || '';
                    this.step = 'options';
                } else {
                    this.error = data.message || 'Utilisateur non trouvé';
                }
            } catch (e) {
                this.error = 'Erreur de connexion. Veuillez réessayer.';
            }

            this.loading = false;
        },

        selectInvoice(invoice) {
            this.selectedItem = {
                type: 'invoice',
                invoice_id: invoice.id,
                label: invoice.invoice_number,
                amount: parseFloat(invoice.total_amount) - parseFloat(invoice.paid_amount)
            };
            this.step = 'payment';
        },

        selectExtension() {
            this.selectedItem = {
                type: 'extension',
                label: 'Prolongation ' + this.user.profile_name,
                amount: parseFloat(this.profile.price)
            };
            this.step = 'payment';
        },

        selectRenewal() {
            this.selectedItem = {
                type: 'renewal',
                label: 'Renouvellement ' + this.user.profile_name,
                amount: parseFloat(this.profile.price)
            };
            this.step = 'payment';
        },

        async initiatePayment(gateway, isPlatform = false) {
            if (!this.customerPhone) {
                this.error = 'Veuillez entrer votre numéro de téléphone';
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const payload = {
                    username: this.username,
                    gateway_code: gateway,
                    payment_type: this.selectedItem.type,
                    invoice_id: this.selectedItem.invoice_id || null,
                    amount: this.selectedItem.amount,
                    customer_phone: this.customerPhone,
                    customer_email: this.customerEmail,
                    admin_id: this.adminId
                };

                if (isPlatform) {
                    payload.is_platform = true;
                }

                const response = await fetch('api.php?route=/pppoe-pay/initiate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success && data.data.redirect_url) {
                    window.location.href = data.data.redirect_url;
                } else {
                    this.error = data.message || 'Erreur lors de l\'initiation du paiement';
                    this.loading = false;
                }
            } catch (e) {
                this.error = 'Erreur de connexion. Veuillez réessayer.';
                this.loading = false;
            }
        },

        formatPrice(amount) {
            if (amount == null) return '-';
            return new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount) + ' ' + this.currency;
        },

        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('fr-FR');
        }
    };
}
</script>

</body>
</html>
