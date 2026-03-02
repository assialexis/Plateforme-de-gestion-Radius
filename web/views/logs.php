<?php $pageTitle = 'Logs d\'authentification';
$currentPage = 'logs'; ?>

<div x-data="logsPage()" x-init="init()">
    <!-- Filtres -->
    <div
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <?= __('log.voucher')?>
                </label>
                <input type="text" x-model="filters.username" @input.debounce.300ms="loadLogs()"
                    placeholder="<?= __('common.search_placeholder')?>"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <?= __('log.action')?>
                </label>
                <select x-model="filters.action" @change="loadLogs()"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                    <option value="">
                        <?= __('log.all')?>
                    </option>
                    <option value="accept">
                        <?= __('log.accepted')?>
                    </option>
                    <option value="reject">
                        <?= __('log.rejected')?>
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <?= __('log.start_date')?>
                </label>
                <input type="date" x-model="filters.date_from" @change="loadLogs()"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <?= __('log.end_date')?>
                </label>
                <input type="date" x-model="filters.date_to" @change="loadLogs()"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
            </div>
            <div class="flex-1"></div>
            <div class="relative" x-data="{ openExport: false }">
                <button @click="openExport = !openExport" @click.away="openExport = false" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-[#30363d] bg-white dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?= __('sales.export') ?? 'Exporter' ?>
                    <svg class="w-4 h-4 ml-2" :class="{'rotate-180': openExport}" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transition: transform 0.2s;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                
                <div x-show="openExport" x-transition.opacity x-cloak
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-[#161b22] rounded-xl shadow-lg border border-gray-200 dark:border-[#30363d] py-1 z-50">
                    <button @click="exportData('csv'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export CSV
                    </button>
                    <button @click="exportData('excel'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </button>
                    <button @click="exportData('json'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        Export JSON
                    </button>
                    <button @click="exportData('pdf'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Export PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-[#161b22] rounded-lg p-4 border border-gray-200/60 dark:border-[#30363d]">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <?= __('log.stats_total')?>
            </p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="totalItems"></p>
        </div>
        <div class="bg-white dark:bg-[#161b22] rounded-lg p-4 border border-gray-200/60 dark:border-[#30363d]">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <?= __('log.stats_accepted')?>
            </p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400"
                x-text="logs.filter(l => l.action === 'accept').length"></p>
        </div>
        <div class="bg-white dark:bg-[#161b22] rounded-lg p-4 border border-gray-200/60 dark:border-[#30363d]">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <?= __('log.stats_rejected')?>
            </p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"
                x-text="logs.filter(l => l.action === 'reject').length"></p>
        </div>
        <div class="bg-white dark:bg-[#161b22] rounded-lg p-4 border border-gray-200/60 dark:border-[#30363d]">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <?= __('log.stats_page')?>
            </p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white"><span x-text="currentPage"></span>/<span
                    x-text="totalPages"></span></p>
        </div>
    </div>

    <!-- Table des logs -->
    <div
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <?= __('log.table_date')?>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <?= __('log.table_voucher')?>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <?= __('log.table_action')?>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <?= __('log.table_reason')?>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <?= __('log.table_nas')?>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Client (MAC & IP)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-for="log in logs" :key="log.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"
                                x-text="new Date(log.created_at).toLocaleString('fr-FR')"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono font-semibold text-gray-900 dark:text-white"
                                    x-text="log.username"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="log.action === 'accept' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                                    x-text="log.action === 'accept' ? '<?= __('log.accepted')?>' : '<?= __('log.rejected')?>'"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate"
                                x-text="log.reason || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white"
                                        x-text="log.nas_display_name || log.nas_name || 'NAS Inconnu'"></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="'ID: ' + (log.nas_router_id || log.nas_name || '-') + ' | IP: ' + log.nas_ip"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-mono font-medium text-gray-700 dark:text-gray-300"
                                        x-text="log.client_mac || '-'"></span>
                                    <span class="text-xs font-mono text-blue-600 dark:text-blue-400"
                                        x-text="log.client_ip || '-'"></span>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="logs.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
            <?= __('log.empty')?>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <?= __('log.showing')?> <span x-text="((currentPage - 1) * perPage) + 1"></span>
                <?= __('log.to')?>
                <span x-text="Math.min(currentPage * perPage, totalItems)"></span>
                <?= __('log.of')?>
                <span x-text="totalItems"></span>
            </p>
            <div class="flex gap-2">
                <button @click="currentPage--; loadLogs()" :disabled="currentPage <= 1"
                    class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#30363d]">
                    <?= __('common.previous')?>
                </button>
                <button @click="currentPage++; loadLogs()" :disabled="currentPage >= totalPages"
                    class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#30363d]">
                    <?= __('common.next')?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function logsPage() {
        return {
            logs: [],
            filters: {
                username: '',
                action: '',
                date_from: '',
                date_to: ''
            },
            currentPage: 1,
            perPage: 50,
            totalItems: 0,
            totalPages: 1,

            async init() {
                await this.loadLogs();
            },

            async loadLogs() {
                try {
                    let url = `/logs?page=${this.currentPage}&per_page=${this.perPage}`;
                    if (this.filters.username) url += `&username=${encodeURIComponent(this.filters.username)}`;
                    if (this.filters.action) url += `&action=${this.filters.action}`;
                    if (this.filters.date_from) url += `&date_from=${this.filters.date_from}`;
                    if (this.filters.date_to) url += `&date_to=${this.filters.date_to}`;

                    const response = await API.get(url);
                    this.logs = response.data.data;
                    this.totalItems = response.data.total;
                    this.totalPages = response.data.total_pages;
                } catch (error) {
                    showToast('<?= __('log.msg_loading_error')?>', 'error');
                }
            },

            async exportData(format) {
                try {
                    showToast('Préparation de l\'export', 'info');
                    
                    let url = `/logs?page=1&per_page=10000`;
                    if (this.filters.username) url += `&username=${encodeURIComponent(this.filters.username)}`;
                    if (this.filters.action) url += `&action=${this.filters.action}`;
                    if (this.filters.date_from) url += `&date_from=${this.filters.date_from}`;
                    if (this.filters.date_to) url += `&date_to=${this.filters.date_to}`;

                    const response = await API.get(url);
                    const data = response.data.data;

                    if (!data || data.length === 0) {
                        showToast(__('log.empty') || 'Aucune donnée à exporter', 'warning');
                        return;
                    }

                    const headers = [
                        __('log.table_date') || 'Date', 
                        __('log.table_voucher') || 'Utilisateur', 
                        __('log.table_action') || 'Action', 
                        __('log.table_reason') || 'Raison',
                        __('log.table_nas') || 'NAS', 
                        'Client (MAC/IP)'
                    ];

                    const rows = data.map(log => {
                        return {
                            date: log.created_at || '',
                            username: log.username || '',
                            action: log.action === 'accept' ? 'Accepté' : 'Rejeté',
                            reason: log.reason || '-',
                            nas: `${log.nas_display_name || log.nas_name || '-'} (${log.nas_ip})`,
                            client: `${log.client_mac || '-'} / ${log.client_ip || '-'}`
                        };
                    });

                    // JSON Export
                    if (format === 'json') {
                        const blob = new Blob([JSON.stringify(rows, null, 2)], {type: 'application/json'});
                        this.downloadFile(blob, 'logs_authentification.json');
                        return;
                    }

                    // CSV Export
                    if (format === 'csv') {
                        const csvContent = [
                            headers.join(','),
                            ...rows.map(row => 
                                Object.values(row).map(v => 
                                    `"${String(v).replace(/"/g, '""')}"`
                                ).join(',')
                            )
                        ].join('\n');
                        const blob = new Blob(['\uFEFF' + csvContent], {type: 'text/csv;charset=utf-8;'});
                        this.downloadFile(blob, 'logs_authentification.csv');
                        return;
                    }

                    // Excel Export
                    if (format === 'excel') {
                        await this.loadXLSX();
                        const ws = XLSX.utils.json_to_sheet(rows);
                        XLSX.utils.sheet_add_aoa(ws, [headers], { origin: "A1" });
                        const wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, "Logs");
                        XLSX.writeFile(wb, 'logs_authentification.xlsx');
                        return;
                    }

                    // PDF Export
                    if (format === 'pdf') {
                        await this.loadJSPDF();
                        const doc = new window.jspdf.jsPDF('landscape');
                        doc.text('Logs d\'authentification', 14, 15);
                        
                        const pdfRows = rows.map(r => Object.values(r).map(v => typeof v === 'string' && v.includes('GMT') ? new Date(v).toLocaleString('fr-FR') : v));
                        doc.autoTable({
                            head: [headers],
                            body: pdfRows,
                            startY: 20,
                            styles: { fontSize: 8 },
                            headStyles: { fillColor: [41, 128, 185] }
                        });
                        doc.save('logs_authentification.pdf');
                        return;
                    }

                } catch (error) {
                    console.error('Erreur export:', error);
                    showToast('Erreur lors de l\'export', 'error');
                }
            },
            
            downloadFile(blob, filename) {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            },
            
            async loadXLSX() {
                if (typeof XLSX !== 'undefined') return;
                return new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            },
            
            async loadJSPDF() {
                if (window.jspdf && window.jspdf.jsPDF && typeof window.jspdf.jsPDF.prototype.autoTable === 'function') return;
                
                if (!window.jspdf) {
                    await new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                }
                
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }
        }
    }
</script>