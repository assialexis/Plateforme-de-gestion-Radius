import sys

file_path = '/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/voucher-templates.php'

with open(file_path, 'r') as f:
    content = f.read()

start_marker = "                        .showEditModal = true;\n    },"
end_marker = "</script>"

start_idx = content.find(start_marker)
end_idx = content.find(end_marker, start_idx) + len(end_marker)

if start_idx == -1 or end_idx == -1:
    print("Markers not found!")
    sys.exit(1)

clean_code = """
            async saveTemplate() {
                this.saving = true;
                try {
                    const data = {
                        ...this.form,
                        show_logo: this.form.show_logo ? 1 : 0,
                        show_qr_code: this.form.show_qr_code ? 1 : 0,
                        show_password: this.form.show_password ? 1 : 0,
                        show_validity: this.form.show_validity ? 1 : 0,
                        show_speed: this.form.show_speed ? 1 : 0,
                        show_price: this.form.show_price ? 1 : 0,
                        is_default: this.form.is_default ? 1 : 0
                    };

                    if (this.showEditModal) {
                        await API.put(`/templates/vouchers/${this.editingId}`, data);
                        showToast(__('template.msg_updated'));
                    } else {
                        await API.post('/templates/vouchers', data);
                        showToast(__('template.msg_created'));
                    }
                    this.closeModal();
                    await this.loadTemplates();
                } catch (error) {
                    showToast(error.message || __('template.msg_save_error'), 'error');
                } finally {
                    this.saving = false;
                }
            },

            closeModal() {
                this.showCreateModal = false;
                this.showEditModal = false;
                this.editingId = null;
                this.resetForm();
            },

            async setDefault(template) {
                try {
                    await API.post(`/templates/vouchers/${template.id}/default`);
                    showToast(__('template.msg_set_default'));
                    await this.loadTemplates();
                } catch (error) {
                    showToast(error.message || __('common.error'), 'error');
                }
            },

            async deleteTemplate(template) {
                if (!confirmAction(__('template.msg_confirm_delete').replace(':name', template.name))) return;

                try {
                    await API.delete(`/templates/vouchers/${template.id}`);
                    showToast(__('template.msg_deleted'));
                    await this.loadTemplates();
                } catch (error) {
                    showToast(error.message || __('template.msg_delete_error'), 'error');
                }
            },

            async previewTemplate(template) {
                try {
                    const response = await API.post(`/templates/vouchers/${template.id}/preview`);
                    this.previewTemplate = response.data.template;
                    this.previewVouchers = response.data.vouchers;
                    this.showPreviewModal = true;
                } catch (error) {
                    showToast(__('template.msg_preview_error'), 'error');
                }
            },

            printPreview() {
                const t = this.previewTemplate;
                if (!t) return;

                const showQr = !!parseInt(t.show_qr_code);

                const ticketsHtml = this.previewVouchers.map((v, i) => {
                    let html = `<div class="ticket">`;
                    if (t.show_logo || t.header_text) {
                        html += `<div class="t-header">${t.header_text || 'WiFi Hotspot'}</div>`;
                    }
                    html += `<div class="t-body-container">`;
                    html += `<div class="${showQr ? 't-body-qr' : 't-info'}">`;
                    if (showQr) {
                        html += `<div class="t-qr" id="qr-${i}"></div><div class="t-info">`;
                    }
                    html += `<div class="t-row"><span class="t-label">PIN / CODE</span><span class="t-value">${v.code}</span></div>`;
                    if (t.show_password) {
                        html += `<div class="t-row"><span class="t-label">PASSWORD</span><span class="t-value">${v.password}</span></div>`;
                    }
                    if (showQr) {
                        html += `</div>`;
                    }
                    html += `</div></div>`;

                    const infos = [];
                    if (t.show_validity && v.validity) infos.push(`<span>${v.validity}</span>`);
                    if (t.show_speed && v.speed) infos.push(`<span>${v.speed}</span>`);
                    if (t.show_price && v.price) infos.push(`<span class="t-price">${v.price}</span>`);
                    if (infos.length) {
                        html += `<div class="t-footer">${infos.join('')}</div>`;
                    }
                    if (t.footer_text) {
                        html += `<div class="t-note">${t.footer_text}</div>`;
                    }
                    html += `</div>`;
                    return html;
                }).join('');

                const cols = t.columns_count || 4;
                const qrData = showQr ? JSON.stringify(this.previewVouchers.map(v => v.code)) : '[]';
                const paperSize = t.paper_size || 'A4';
                const orientation = t.orientation || 'portrait';
                
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`<!DOCTYPE html><html><head><title>Impression - Tickets</title>
            ${showQr ? '<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"><\/script>' : ''}
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
                @page { size: ${paperSize} ${orientation}; margin: 8mm; }
                * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
                body { background: #f8fafc; }
                .grid { display: grid; grid-template-columns: repeat(${cols}, 1fr); gap: 12px; padding: 10px; }
                .ticket {
                    border: 2px solid ${t.border_color || '#e2e8f0'};
                    border-radius: 12px;
                    overflow: hidden;
                    page-break-inside: avoid;
                    background: ${t.background_color || '#ffffff'};
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                }
                .t-header {
                    background: ${t.primary_color || '#1a1a2e'};
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
                    color: ${t.text_color || '#0f172a'};
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
                .t-price { font-weight: 800; color: ${t.primary_color || '#1a1a2e'}; font-size: 9pt; }
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
            }
        }
    }
</script>"""

new_content = content[:start_idx + len(start_marker)] + "\n" + clean_code + content[end_idx:]

with open(file_path, 'w') as f:
    f.write(new_content)

print("Successfully replaced.")
