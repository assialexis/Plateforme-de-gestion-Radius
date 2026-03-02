import re

file_path = '/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/vouchers.php'
with open(file_path, 'r') as f:
    text = f.read()

# 1. Add "PDF" button to bulk actions
old_bulk_print = """                    <button @click="printSelected('qr')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors" title="QR Code">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                        </svg>
                    </button>"""

new_bulk_print = """                    <button @click="printSelected('qr')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors" title="QR Code">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                        </svg>
                    </button>
                    <button @click="printSelected('pdf')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors" title="Exporter en PDF">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </button>"""
text = text.replace(old_bulk_print, new_bulk_print)

# 2. Add "PDF" button to single row actions
old_row_actions = """                                    <button @click="printSingle(voucher, 'qr')" title="QR Code" class="text-gray-400 hover:text-purple-600 dark:hover:text-purple-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                                        </svg>
                                    </button>"""
new_row_actions = """                                    <button @click="printSingle(voucher, 'qr')" title="QR Code" class="text-gray-400 hover:text-purple-600 dark:hover:text-purple-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                                        </svg>
                                    </button>
                                    <button @click="printSingle(voucher, 'pdf')" title="PDF" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </button>"""
text = text.replace(old_row_actions, new_row_actions)

# 3. Add to Modal results
old_modal_print = """                        <button @click="printVouchers('qr')"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]" title="QR Code">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                            </svg>
                            QR
                        </button>"""
new_modal_print = """                        <button @click="printVouchers('qr')"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]" title="QR Code">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                            </svg>
                            QR
                        </button>
                        <button @click="printVouchers('pdf')"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]" title="Exporter en PDF">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            PDF
                        </button>"""
text = text.replace(old_modal_print, new_modal_print)

# 4. Modify openPrintWindow to handle 'pdf'
old_window = """                if (printType === 'mini') {
                    cols = 1;
                    paperSize = '58mm auto';
                } else if (printType === 'qr') {
                    showQr = true;
                }"""
new_window = """                if (printType === 'mini') {
                    cols = 1;
                    paperSize = '58mm auto';
                } else if (printType === 'qr') {
                    showQr = true;
                }"""
text = text.replace(old_window, new_window)

old_html_print = """                    window.onload = function(){ setTimeout(function(){ window.print(); }, 200); };
                }
            <\/script>"""
new_html_print = """                    window.onload = function(){ 
                        if ('${printType}' === 'pdf') {
                            var el = document.body;
                            var opt = {
                                margin:       [8, 8, 8, 8],
                                filename:     'vouchers.pdf',
                                image:        { type: 'jpeg', quality: 0.98 },
                                html2canvas:  { scale: 2, useCORS: true },
                                jsPDF:        { unit: 'mm', format: '${paperSize}' === 'A4' ? 'a4' : [58, 200], orientation: '${orientation}' }
                            };
                            html2pdf().set(opt).from(el).save().then(function(){
                                window.close();
                            });
                        } else {
                            setTimeout(function(){ window.print(); }, 200); 
                        }
                    };
                }
            <\/script>"""
text = text.replace(old_html_print, new_html_print)

old_doc_write = """            ${showQr ? '<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"><\/script>' : ''}"""
new_doc_write = """            ${showQr ? '<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"><\/script>' : ''}
            ${printType === 'pdf' ? '<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"><\/script>' : ''}"""
text = text.replace(old_doc_write, new_doc_write)

old_setInterval = """                    var checkLib = setInterval(function(){
                        if (typeof qrcode !== 'undefined') { clearInterval(checkLib); generateQRCodes(); }
                    }, 50);
                    setTimeout(function(){ clearInterval(checkLib); window.print(); }, 3000);"""
new_setInterval = """                    var checkLib = setInterval(function(){
                        if (typeof qrcode !== 'undefined') { clearInterval(checkLib); generateQRCodes(); }
                    }, 50);
                    setTimeout(function(){ 
                        clearInterval(checkLib); 
                        if ('${printType}' === 'pdf') { window.onload(); } else { window.print(); }
                    }, 3000);"""

text = text.replace(old_setInterval, new_setInterval)

old_code_gen = """                        el.innerHTML = qr.createSvgTag(3, 0);
                    });
                    setTimeout(function(){ window.print(); }, 200);
                }"""
new_code_gen = """                        el.innerHTML = qr.createSvgTag(3, 0);
                    });
                    setTimeout(function(){ 
                        if ('${printType}' === 'pdf') { window.onload(); } else { window.print(); }
                    }, 200);
                }"""
text = text.replace(old_code_gen, new_code_gen)

with open(file_path, 'w') as f:
    f.write(text)

print("Patch applied")

