import re

file_path = '/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/vouchers.php'
with open(file_path, 'r') as f:
    text = f.read()

# 1. Remove "PDF" button from bulk actions
pdf_bulk = """                    <button @click="printSelected('pdf')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors" title="Exporter en PDF">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </button>"""
text = text.replace(pdf_bulk + '\n', '')
text = text.replace(pdf_bulk, '')

# 2. Remove "PDF" button from single row actions
pdf_row = """                                    <button @click="printSingle(voucher, 'pdf')" title="PDF" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </button>"""
text = text.replace('\n' + pdf_row, '')
text = text.replace(pdf_row, '')

# 3. Remove from Modal results
pdf_modal = """
                        <button @click="printVouchers('pdf')"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]" title="Exporter en PDF">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            PDF
                        </button>"""
text = text.replace(pdf_modal, '')

# 4. Revert executePrint function
new_print_js = """                function executePrint() {
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
                        window.print();
                    }
                }

                function generateQRCodes() {
                    if (typeof qrcode === 'undefined' || !codes.length) { executePrint(); return; }
                    codes.forEach(function(code, i) {
                        var el = document.getElementById('qr-' + i);
                        if (!el) return;
                        var qr = qrcode(0, 'M');
                        qr.addData(code);
                        qr.make();
                        el.innerHTML = qr.createSvgTag(3, 0);
                    });
                    setTimeout(function(){ executePrint(); }, 200);
                }

                if (${showQr}) {
                    var checkLib = setInterval(function(){
                        if (typeof qrcode !== 'undefined') { clearInterval(checkLib); generateQRCodes(); }
                    }, 50);
                    setTimeout(function(){ clearInterval(checkLib); executePrint(); }, 3000);
                } else {
                    window.onload = function(){ setTimeout(function(){ executePrint(); }, 200); };
                }"""

old_print_js = """                function generateQRCodes() {
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
                }"""
text = text.replace(new_print_js, old_print_js)

html2pdf_script = """
            ${printType === 'pdf' ? '<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"><\/script>' : ''}"""
text = text.replace(html2pdf_script, '')

with open(file_path, 'w') as f:
    f.write(text)

print("Reverted PDF patches.")
