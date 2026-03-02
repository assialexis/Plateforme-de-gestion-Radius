
    function vouchersPage() {
        return {
            vouchers: [],
            profiles: [],
            zones: [],
            availableNotes: [],
            search: '',
            statusFilter: '',
            typeFilter: '',
            zoneFilter: '',
            notesFilter: '',
            currentPage: 1,
            perPage: 20,
            totalItems: 0,
            totalPages: 1,
            showCreateModal: false,
            showGenerateModal: false,
            showDetailsModal: false,
            showResultsModal: false,
            selectedVoucher: null,
            generatedVouchers: [],
            createType: 'voucher',
            selectedIds: [],
            zoneProfiles: [],
            loadingZoneProfiles: false,
            newVoucher: {
                username: '',
                password: '',
                profile_id: '',
                zone_id: '',
                customer_name: '',
                customer_phone: '',
                notes: '',
                vendeur_id: ''
            },
            generateForm: {
                type: 'voucher',
                count: 10,
                prefix: 'V',
                password_length: 6,
                password_type: 'alphanumeric',
                profile_id: '',
                zone_id: '',
                notes: '',
                vendeur_id: ''
            },
            defaultTemplate: null,
            vendeurs: [],

            async init() {
                await Promise.all([
                    this.loadProfiles(),
                    this.loadZones(),
                    this.loadNotes(),
                    this.loadDefaultTemplate(),
                    this.loadVendeurs()
                ]);
                await this.loadVouchers();
            },

            async loadDefaultTemplate() {
                try {
                    const response = await API.get('/templates/vouchers/default');
                    if (response.success) {
                        this.defaultTemplate = response.data;
                    }
                } catch (e) {
                    // Pas de template par défaut, on utilisera les valeurs par défaut
                }
            },

            async loadVendeurs() {
                try {
                    const response = await API.get('/users?role=vendeur');
                    this.vendeurs = response.data || [];
                } catch (e) {
                    console.error('Error loading vendeurs', e);
                }
            },

            async loadProfiles() {
                try {
                    const response = await API.get('/profiles?active=1');
                    this.profiles = response.data;
                } catch (error) {
                    showToast(__('api.error_loading'), 'error');
                }
            },

            async loadZones() {
                try {
                    const response = await API.get('/zones');
                    this.zones = response.data.filter(z => z.is_active);
                } catch (error) {
                    console.error('Error loading zones:', error);
                }
            },

            async loadNotes() {
                try {
                    const response = await API.get('/vouchers/notes');
                    this.availableNotes = response.data || [];
                } catch (error) {
                    console.error('Error loading notes:', error);
                }
            },

            async loadVouchers() {
                try {
                    let url = `/vouchers?page=${this.currentPage}&per_page=${this.perPage}`;
                    if (this.search) url += `&search=${encodeURIComponent(this.search)}`;
                    if (this.statusFilter) url += `&status=${this.statusFilter}`;
                    if (this.typeFilter) url += `&type=${this.typeFilter}`;
                    if (this.zoneFilter) url += `&zone=${this.zoneFilter}`;
                    if (this.notesFilter) url += `&notes=${encodeURIComponent(this.notesFilter)}`;

                    const response = await API.get(url);
                    this.vouchers = response.data.data.map(v => ({ ...v, show_password: false }));
                    this.totalItems = response.data.total;
                    this.totalPages = response.data.total_pages;
                    // Réinitialiser la sélection lors du rechargement
                    this.selectedIds = [];
                } catch (error) {
                    showToast(__('api.error_loading'), 'error');
                }
            },

            // Sélection
            toggleSelect(id) {
                const index = this.selectedIds.indexOf(id);
                if (index === -1) {
                    this.selectedIds.push(id);
                } else {
                    this.selectedIds.splice(index, 1);
                }
            },

            toggleSelectAll(checked) {
                if (checked) {
                    this.selectedIds = this.vouchers.map(v => v.id);
                } else {
                    this.selectedIds = [];
                }
            },

            printSelected(printType = 'normal') {
                const selected = this.vouchers.filter(v => this.selectedIds.includes(v.id));
                if (selected.length === 0) return;

                const items = selected.map(v => {
                    const profile = this.profiles.find(p => p.id == v.profile_id);
                    return {
                        code: v.username,
                        password: v.has_password ? v.plain_password : null,
                        profileName: profile?.name || '',
                        time: profile?.time_limit ? formatTime(profile.time_limit) : '',
                        speed: profile?.download_speed ? formatSpeed(profile.download_speed) : '',
                        price: profile?.price ? Number(profile.price).toLocaleString() + ' XAF' : ''
                    };
                });

                this.openPrintWindow(items, printType);
            },

            printSingle(voucher, printType = 'normal') {
                const profile = this.getProfileInfo(voucher.profile_id);
                const items = [{
                    code: voucher.username,
                    password: voucher.has_password ? voucher.plain_password : null,
                    profileName: profile.name || (voucher.profile_name || ''),
                    time: voucher.time_limit ? formatTime(voucher.time_limit) : '',
                    speed: voucher.download_speed ? formatSpeed(voucher.download_speed) : '',
                    price: voucher.price ? Number(voucher.price).toLocaleString() + ' XAF' : ''
                }];
                this.openPrintWindow(items, printType);
            },

            async deleteSelected() {
                if (this.selectedIds.length === 0) return;

                if (!confirmAction(__('confirm.delete_message'))) return;

                try {
                    // Supprimer un par un
                    let deleted = 0;
                    let errors = 0;

                    for (const id of this.selectedIds) {
                        try {
                            await API.delete(`/vouchers/${id}`);
                            deleted++;
                        } catch (e) {
                            errors++;
                        }
                    }

                    if (deleted > 0) {
                        showToast(__('voucher.msg_deleted'));
                    }
                    if (errors > 0) {
                        showToast(__('api.error_deleting'), 'error');
                    }

                    this.selectedIds = [];
                    this.loadVouchers();
                } catch (error) {
                    showToast(__('api.error_deleting'), 'error');
                }
            },

            async createVoucher() {
                if (!this.newVoucher.profile_id) {
                    showToast(__('voucher.select_profile'), 'error');
                    return;
                }

                try {
                    const data = {
                        username: this.createType === 'ticket' ? this.newVoucher.username : this.newVoucher.username.toUpperCase(),
                        password: this.createType === 'ticket' ? this.newVoucher.password : null,
                        profile_id: this.newVoucher.profile_id,
                        customer_name: this.newVoucher.customer_name || null,
                        customer_phone: this.newVoucher.customer_phone || null,
                        notes: this.newVoucher.notes || null,
                        vendeur_id: this.newVoucher.vendeur_id || null
                    };

                    await API.post('/vouchers', data);
                    showToast(__('voucher.msg_created'));
                    this.showCreateModal = false;
                    this.resetNewVoucher();
                    this.loadVouchers();
                    this.loadNotes();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async generateVouchers() {
                // Validation des champs obligatoires
                if (!this.generateForm.profile_id) {
                    showToast(__('voucher.select_profile'), 'error');
                    return;
                }
                if (!this.generateForm.zone_id) {
                    showToast(__('common.all_zones'), 'error');
                    return;
                }

                try {
                    const data = {
                        type: this.generateForm.type,
                        count: parseInt(this.generateForm.count),
                        prefix: this.generateForm.type === 'ticket' ? this.generateForm.prefix : this.generateForm.prefix.toUpperCase(),
                        password_length: this.generateForm.type === 'ticket' ? parseInt(this.generateForm.password_length) : null,
                        password_type: this.generateForm.type === 'ticket' ? this.generateForm.password_type : null,
                        profile_id: this.generateForm.profile_id,
                        zone_id: this.generateForm.zone_id,
                        notes: this.generateForm.notes || null,
                        vendeur_id: this.generateForm.vendeur_id || null
                    };

                    const response = await API.post('/vouchers/generate', data);
                    this.generatedVouchers = response.data.vouchers;
                    const typeLabel = this.generateForm.type === 'ticket' ? 'tickets' : 'vouchers';
                    showToast(__('voucher.msg_generate_success'));
                    this.showGenerateModal = false;
                    this.showResultsModal = true;
                    this.loadVouchers();
                    this.loadNotes();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async viewVoucher(voucher) {
                try {
                    const response = await API.get(`/vouchers/${voucher.id}`);
                    this.selectedVoucher = response.data;
                    this.showDetailsModal = true;
                } catch (error) {
                    showToast(__('api.error_loading'), 'error');
                }
            },

            async resetVoucher(voucher) {
                if (!confirmAction(__('confirm.delete_message'))) return;

                try {
                    await API.post(`/vouchers/${voucher.id}/reset`);
                    showToast(__('notify.success'));
                    this.loadVouchers();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async toggleVoucher(voucher) {
                const action = voucher.status === 'disabled' ? 'enable' : 'disable';
                try {
                    await API.post(`/vouchers/${voucher.id}/${action}`);
                    showToast(action === 'enable' ? __('voucher.msg_enabled') : __('voucher.msg_disabled'));
                    this.loadVouchers();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async deleteVoucher(voucher) {
                if (!confirmAction(__('voucher.msg_delete_confirm'))) return;

                try {
                    await API.delete(`/vouchers/${voucher.id}`);
                    showToast(__('voucher.msg_deleted'));
                    this.loadVouchers();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            resetNewVoucher() {
                this.newVoucher = {
                    username: '',
                    password: '',
                    profile_id: '',
                    zone_id: '',
                    customer_name: '',
                    customer_phone: '',
                    notes: '',
                    vendeur_id: ''
                };
                this.createType = 'voucher';
            },

            getSelectedProfilePrice(profileId) {
                if (!profileId) return 0;
                const profile = this.profiles.find(p => p.id == profileId);
                return profile ? profile.price : 0;
            },

            getSelectedProfileName(profileId) {
                if (!profileId) return '';
                const profile = this.profiles.find(p => p.id == profileId);
                return profile ? profile.name : '';
            },

            getSelectedZoneProfilePrice(profileId) {
                if (!profileId) return 0;
                const profile = this.zoneProfiles.find(p => p.id == profileId);
                return profile ? profile.price : 0;
            },

            getSelectedZoneProfileName(profileId) {
                if (!profileId) return '';
                const profile = this.zoneProfiles.find(p => p.id == profileId);
                return profile ? profile.name : '';
            },

            async loadZoneProfiles() {
                // Réinitialiser le profil sélectionné quand la zone change
                this.generateForm.profile_id = '';
                this.zoneProfiles = [];

                if (!this.generateForm.zone_id) {
                    return;
                }

                this.loadingZoneProfiles = true;
                try {
                    const response = await API.get(`/zones/${this.generateForm.zone_id}/profiles`);
                    // Filtrer les profils actifs
                    this.zoneProfiles = (response.data || []).filter(p => p.is_active);
                } catch (error) {
                    console.error('Erreur chargement profils zone:', error);
                    showToast(__('voucher.msg_load_profiles_error'), 'error');
                } finally {
                    this.loadingZoneProfiles = false;
                }
            },

            generateRandomPassword(length = 8) {
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                let password = '';
                for (let i = 0; i < length; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return password;
            },

            copyToClipboard(text) {
                navigator.clipboard.writeText(text);
                showToast(__('common.copied'));
            },

            getProfileInfo(profileId) {
                const p = this.profiles.find(pr => pr.id == profileId);
                if (!p) return {};
                return {
                    name: p.name || '',
                    price: p.price ? Number(p.price).toLocaleString() + ' XAF' : '',
                    time: p.time_limit ? formatTime(p.time_limit) : 'Illimité',
                    speed: p.download_speed ? formatSpeed(p.download_speed) : ''
                };
            },

            printVouchers(printType = 'normal') {
                const profile = this.getProfileInfo(this.generateForm.profile_id);
                const items = this.generatedVouchers.map(v => ({
                    code: v.username,
                    password: v.plain_password || null,
                    profileName: profile.name || '',
                    time: profile.time || '',
                    speed: profile.speed || '',
                    price: profile.price || ''
                }));

                this.openPrintWindow(items, printType);
            },

            // Génère la page d'impression en utilisant le template par défaut
            openPrintWindow(items, printType = 'normal') {
                if (!items.length) return;

                const t = this.defaultTemplate || {};
                const primaryColor = t.primary_color || '#1a1a2e';
                const borderColor = t.border_color || '#e2e8f0';
                const bgColor = t.background_color || '#ffffff';
                const textColor = t.text_color || '#0f172a';
                let cols = t.columns_count || 4;
                const headerText = t.header_text || '';
                const footerText = t.footer_text || '';
                const showPassword = t.show_password !== undefined ? !!parseInt(t.show_password) : true;
                const showValidity = t.show_validity !== undefined ? !!parseInt(t.show_validity) : true;
                const showSpeed = t.show_speed !== undefined ? !!parseInt(t.show_speed) : false;
                const showPrice = t.show_price !== undefined ? !!parseInt(t.show_price) : true;
                const showLogo = t.show_logo !== undefined ? !!parseInt(t.show_logo) : true;
                let showQr = t.show_qr_code !== undefined ? !!parseInt(t.show_qr_code) : false;
                const showHeader = showLogo || headerText;
                let paperSize = t.paper_size || 'A4';
                let orientation = t.orientation || 'portrait';
                if (printType === 'mini') {
                    cols = 1;
                    paperSize = '58mm auto';
                } else if (printType === 'qr') {
                    showQr = true;
                }

                const ticketsHtml = items.map((v, i) => {
                    let html = '<div class="ticket">';

                    // Header
                    if (showHeader) {
                        html += `<div class="t-header">${headerText || v.profileName || 'WiFi Hotspot'}</div>`;
                    }

                    // Body (with optional QR)
                    html += `<div class="t-body-container">`;
                    html += `<div class="${showQr ? 't-body-qr' : 't-info'}">`;
                    if (showQr) {
                        html += `<div class="t-qr" id="qr-${i}"></div><div class="t-info">`;
                    }
                    html += `<div class="t-row"><span class="t-label">PIN / CODE</span><span class="t-value">${v.code}</span></div>`;
                    if (showPassword && v.password) {
                        html += `<div class="t-row"><span class="t-label">PASSWORD</span><span class="t-value">${v.password}</span></div>`;
                    }
                    if (showQr) {
                        html += `</div>`;
                    }
                    html += '</div></div>';

                    // Footer info
                    const infos = [];
                    if (showValidity && v.time) infos.push(`<span>${v.time}</span>`);
                    if (showSpeed && v.speed) infos.push(`<span>${v.speed}</span>`);
                    if (showPrice && v.price) infos.push(`<span class="t-price">${v.price}</span>`);
                    if (infos.length) {
                        html += `<div class="t-footer">${infos.join('')}</div>`;
                    }

                    // Footer text
                    if (footerText) {
                        html += `<div class="t-note">${footerText}</div>`;
                    }

                    html += '</div>';
                    return html;
                }).join('');

                const qrData = showQr ? JSON.stringify(items.map(v => v.code)) : '[]';
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`<!DOCTYPE html><html><head><title>Impression</title>
            ${showQr ? '<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"><\/script>' : ''}
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
                @page { size: ${paperSize} ${orientation}; margin: 8mm; }
                * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
                body { background: #f8fafc; }
                .grid { display: grid; grid-template-columns: repeat(${cols}, 1fr); gap: 12px; padding: 10px; }
                .ticket {
                    border: 2px solid ${borderColor};
                    border-radius: 12px;
                    overflow: hidden;
                    page-break-inside: avoid;
                    background: ${bgColor};
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                }
                .t-header {
                    background: ${primaryColor};
                    color: #fff;
                    text-align: center;
                    font-weight: 700;
                    font-size: 10pt;
                    padding: 8px 10px;
                    letter-spacing: 1px;
                    text-transform: uppercase;
                }
                .t-body-container {
                    display: flex;
                    flex: 1;
                    padding: 12px;
                    align-items: center;
                    justify-content: center;
                }
                .t-body-qr { display: flex; align-items: center; gap: 12px; width: 100%; }
                .t-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 8px; width: 100%; }
                .t-qr { flex-shrink: 0; background: #fff; padding: 2px; border-radius: 6px; border: 1px solid #e2e8f0; }
                .t-qr img, .t-qr svg { width: 50px; height: 50px; display: block; }
                .t-row { display: flex; flex-direction: column; text-align: center; }
                .t-label { font-size: 6.5pt; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 3px; }
                .t-value {
                    font-family: 'Consolas', 'Courier New', monospace;
                    font-weight: 700;
                    font-size: 11pt;
                    color: ${textColor};
                    letter-spacing: 1.5px;
                    background: #f1f5f9;
                    padding: 4px 6px;
                    border-radius: 6px;
                    border: 1px solid #e2e8f0;
                }
                .t-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 8px 12px;
                    background: #f8fafc;
                    font-size: 8pt;
                    color: #475569;
                    border-top: 2px dashed #e2e8f0;
                    font-weight: 600;
                }
                .t-price { font-weight: 800; color: ${primaryColor}; font-size: 9pt; }
                .t-note { text-align: center; font-size: 6.5pt; color: #94a3b8; padding: 4px 12px 8px; background: #f8fafc; font-weight: 500; }
                @media print {
                    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: none; }
                }
            </style></head><body>
            <div class="grid">${ticketsHtml}</div>
            <script>
                var codes = ${qrData};
                function generateQRCodes() {
                    if (typeof qrcode === 'undefined' || !codes.length) { window.print(); return; }
                    codes.forEach(function(code, i) {
                        var el = document.getElementById('qr-' + i);
                        if (!el) return;
                        var qr = qrcode(0, 'M');
                        qr.addData(code);
                        qr.make();
                        el.innerHTML = qr.createSvgTag(3, 0);
                    });
                    setTimeout(function(){ window.print(); }, 200);
                }
                if (${showQr}) {
                    var checkLib = setInterval(function(){
                        if (typeof qrcode !== 'undefined') { clearInterval(checkLib); generateQRCodes(); }
                    }, 50);
                    setTimeout(function(){ clearInterval(checkLib); window.print(); }, 3000);
                } else {
                    window.onload = function(){ setTimeout(function(){ window.print(); }, 200); };
                }
            <\/script>
            </body></html>`);
                printWindow.document.close();
            },

            exportCSV() {
                const hasPassword = this.generatedVouchers[0]?.plain_password;
                let csv = hasPassword ? 'Username,Password\n' : 'Code\n';

                this.generatedVouchers.forEach(v => {
                    if (hasPassword) {
                        csv += `${v.username},${v.plain_password}\n`;
                    } else {
                        csv += `${v.username}\n`;
                    }
                });

                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = hasPassword ? 'tickets.csv' : 'vouchers.csv';
                a.click();
                window.URL.revokeObjectURL(url);
            },

            getStatusClass(status) {
                const classes = {
                    'unused': 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300',
                    'active': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'expired': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    'disabled': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                };
                return classes[status] || classes['unused'];
            },

            getStatusLabel(status) {
                const labels = {
                    'unused': 'Non utilisé',
                    'active': 'Actif',
                    'expired': 'Expiré',
                    'disabled': 'Désactivé'
                };
                return labels[status] || status;
            },

            getPercent(used, limit) {
                if (!limit) return 0;
                return Math.min(100, Math.round((used / limit) * 100));
            },

            getProgressColor(percent) {
                if (percent >= 90) return 'bg-red-500';
                if (percent >= 70) return 'bg-yellow-500';
                return 'bg-green-500';
            },

            formatBytes(bytes) { return formatBytes(bytes); },
            formatTime(seconds) { return formatTime(seconds); },
            formatSpeed(bps) { return formatSpeed(bps); },

            isExpired(validUntil) {
                if (!validUntil) return false;
                return new Date(validUntil) < new Date();
            },

            getRemainingTime(validUntil) {
                if (!validUntil) return '-';
                const now = new Date();
                const expires = new Date(validUntil);
                const diffMs = expires - now;

                if (diffMs <= 0) return 'Expiré';

                const diffSeconds = Math.floor(diffMs / 1000);
                return formatTime(diffSeconds);
            }
        };
    }
