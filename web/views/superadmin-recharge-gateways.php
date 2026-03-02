<?php $pageTitle = __('superadmin.recharge_gateways') ?? 'Passerelles de recharge';
$currentPage = 'superadmin-recharge-gateways'; ?>

<div x-data="rechargeGatewaysPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('superadmin.recharge_gateways_desc') ?? 'Passerelles que les administrateurs pourront utiliser pour recharger leurs crédits' ?>
            </p>
        </div>
    </div>

    <div x-show="loading" class="text-center py-12 text-gray-500">
        <?= __('common.loading') ?? 'Chargement...' ?>
    </div>

    <div x-show="!loading">
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('superadmin.recharge_gateways') ?? 'Passerelles de recharge' ?>
                </h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-[#21262d]/50">
                <template x-for="gw in rechargeGateways" :key="gw.gateway_code">
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-xs font-bold"
                                    :class="{
                                        'bg-blue-600': gw.gateway_code === 'fedapay',
                                        'bg-orange-500': gw.gateway_code === 'cinetpay',
                                        'bg-green-600': gw.gateway_code === 'ligdicash',
                                        'bg-purple-600': gw.gateway_code === 'cryptomus',
                                        'bg-emerald-600': gw.gateway_code === 'paygate_global',
                                        'bg-cyan-600': gw.gateway_code === 'feexpay',
                                        'bg-indigo-600': gw.gateway_code === 'kkiapay',
                                        'bg-teal-600': gw.gateway_code === 'paydunya',
                                        'bg-rose-600': gw.gateway_code === 'yengapay'
                                    }"
                                    x-text="gw.name.substring(0, 2).toUpperCase()"></div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 text-sm" x-text="gw.name"></h4>
                                    <p class="text-xs text-gray-500" x-text="gw.description"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <select x-model="gw.is_sandbox" class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300">
                                    <option value="1">Sandbox</option>
                                    <option value="0">Production</option>
                                </select>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" :checked="gw.is_active == 1"
                                        @change="gw.is_active = gw.is_active == 1 ? 0 : 1">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-600"></div>
                                </label>
                            </div>
                        </div>
                        <!-- Config fields -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <template x-for="(val, key) in gw.config" :key="key">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1" x-text="key.replace(/_/g, ' ')"></label>
                                    <div class="relative">
                                        <input type="text" :value="val" @input="gw.config[key] = $event.target.value"
                                            :placeholder="key"
                                            class="w-full px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 font-mono pr-8">
                                        <button x-show="key.includes('secret') || key.includes('password') || key.includes('private') || key.includes('token') || key === 'api_key'" type="button"
                                            onclick="var inp=this.parentElement.querySelector('input'); var svgEye=this.children[0]; var svgOff=this.children[1]; if(inp.type==='text'){inp.type='password';svgEye.style.display='none';svgOff.style.display='block';}else{inp.type='text';svgEye.style.display='block';svgOff.style.display='none';}"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            title="Masquer / Afficher">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg class="w-3.5 h-3.5" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="flex justify-end">
                            <button @click="saveGateway(gw)" :disabled="gw.saving"
                                class="px-4 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                                <span x-show="!gw.saving"><?= __('common.save') ?? 'Enregistrer' ?></span>
                                <span x-show="gw.saving"><?= __('common.loading') ?? '...' ?></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="rechargeGateways.length === 0" class="p-5 text-center text-sm text-gray-500">
                <?= __('superadmin.no_gateways') ?? 'Aucune passerelle configurée' ?>
            </div>
        </div>
    </div>
</div>

<script>
function rechargeGatewaysPage() {
    return {
        rechargeGateways: [],
        loading: true,

        async init() {
            await this.loadRechargeGateways();
            this.loading = false;
        },

        async loadRechargeGateways() {
            try {
                const res = await fetch('api.php?route=/superadmin/recharge-gateways', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.rechargeGateways = data.data.gateways.map(gw => ({ ...gw, saving: false }));
                }
            } catch (e) { console.error(e); }
        },

        async saveGateway(gw) {
            gw.saving = true;
            try {
                const res = await fetch(`api.php?route=/superadmin/recharge-gateways/${gw.gateway_code}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        is_active: gw.is_active == 1,
                        is_sandbox: gw.is_sandbox == 1 || gw.is_sandbox === '1',
                        config: gw.config
                    })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) { showToast('Erreur', 'error'); }
            gw.saving = false;
        }
    };
}
</script>
