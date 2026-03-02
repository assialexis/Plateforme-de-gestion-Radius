import re

file_path = '/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/vouchers.php'
with open(file_path, 'r') as f:
    text = f.read()

# 1. Modify the bulk actions (Lines 18-25 roughly)
old_bulk_print = """                    <button @click="printSelected()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <?= __('common.print')?>
                    </button>"""

new_bulk_print = """                    <button @click="printSelected('normal')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors" title="A4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>
                    <button @click="printSelected('mini')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors" title="Mini Imprimante">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h5M8 14h8M8 18h8" />
                        </svg>
                    </button>
                    <button @click="printSelected('qr')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors" title="QR Code">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                        </svg>
                    </button>"""
text = text.replace(old_bulk_print, new_bulk_print)

# 2. Add individual actions for single row (Line 361)
old_row_actions = """                                    <button @click="viewVoucher(voucher)\""""
new_row_actions = """                                    <button @click="printSingle(voucher, 'mini')" title="Mini Imprimante" class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h5M8 14h8M8 18h8" />
                                        </svg>
                                    </button>
                                    <button @click="printSingle(voucher, 'qr')" title="QR Code" class="text-gray-400 hover:text-purple-600 dark:hover:text-purple-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                                        </svg>
                                    </button>
                                    <button @click="viewVoucher(voucher)\""""
text = text.replace(old_row_actions, new_row_actions)

# 3. Modify printVouchers block in modal results
old_modal_print = """                        <button @click="printVouchers()"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <?= __('common.print')?>
                        </button>"""
new_modal_print = """                        <button @click="printVouchers('normal')"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]" title="A4">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            A4
                        </button>
                        <button @click="printVouchers('mini')"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]" title="Mini">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h5M8 14h8M8 18h8" />
                            </svg>
                            Mini
                        </button>
                        <button @click="printVouchers('qr')"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]" title="QR Code">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                            </svg>
                            QR
                        </button>"""
text = text.replace(old_modal_print, new_modal_print)

# 4. Modify printSelected() and printVouchers() and openPrintWindow() in JS
old_print_selected = """            printSelected() {"""
new_print_selected = """            printSelected(printType = 'normal') {"""
text = text.replace(old_print_selected, new_print_selected)

old_print_selected_call = """                this.openPrintWindow(items);"""
new_print_selected_call = """                this.openPrintWindow(items, printType);"""
text = text.replace(old_print_selected_call, new_print_selected_call)

old_print_vouchers = """            printVouchers() {"""
new_print_vouchers = """            printVouchers(printType = 'normal') {"""
text = text.replace(old_print_vouchers, new_print_vouchers)

old_print_vouchers_call = """                this.openPrintWindow(items);"""
new_print_vouchers_call = """                this.openPrintWindow(items, printType);"""
text = text.replace(old_print_vouchers_call, new_print_vouchers_call)

old_open_print = """            openPrintWindow(items) {"""
new_open_print = """            openPrintWindow(items, printType = 'normal') {"""
text = text.replace(old_open_print, new_open_print)

old_open_print_vars = """                const showHeader = showLogo || headerText;
                const paperSize = t.paper_size || 'A4';
                const orientation = t.orientation || 'portrait';"""
new_open_print_vars = """                const showHeader = showLogo || headerText;
                let paperSize = t.paper_size || 'A4';
                let orientation = t.orientation || 'portrait';
                
                if (printType === 'mini') {
                    cols = 1;
                    paperSize = '58mm auto';
                } else if (printType === 'qr') {
                    showQr = true;
                }"""
text = text.replace(old_open_print_vars, new_open_print_vars)

# Append printSingle method after printSelected
old_print_single = """            async deleteSelected() {"""
new_print_single = """            printSingle(voucher, printType = 'normal') {
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

            async deleteSelected() {"""
text = text.replace(old_print_single, new_print_single)


with open(file_path, 'w') as f:
    f.write(text)

print("Patch applied")

