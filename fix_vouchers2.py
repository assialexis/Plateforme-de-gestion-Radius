import sys

file_path = '/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/voucher-templates.php'
with open(file_path, 'r') as f:
    lines = f.readlines()

content_to_add = """            editingId: null,
            previewTemplate: null,
            previewVouchers: [],

            form: {
                name: '',
                description: '',
                template_type: 'simple',
                paper_size: 'A4',
                orientation: 'portrait',
                columns_count: 4,
                rows_count: 8,
                show_logo: true,
                show_qr_code: false,
                show_password: true,
                show_validity: true,
                show_speed: false,
                show_price: true,
                header_text: 'WiFi Hotspot',
                footer_text: 'Merci de votre visite!',
                background_color: '#ffffff',
                border_color: '#e5e7eb',
                primary_color: '#3b82f6',
                text_color: '#1f2937',
                is_default: false
            },

            async init() {
                await this.loadTemplates();
            },

            async loadTemplates() {
                this.loading = true;
                try {
                    const response = await API.get('/templates/vouchers');
                    this.templates = response.data;
                } catch (error) {
                    showToast(__('template.msg_load_error'), 'error');
                } finally {
                    this.loading = false;
                }
            },

"""

start_idx = -1
for i, line in enumerate(lines):
    if 'resetForm() {' in line:
        start_idx = i
        break

if start_idx != -1:
    with open(file_path, 'w') as f:
        f.writelines(lines[:start_idx])
        f.write(content_to_add)
        f.writelines(lines[start_idx:])
    print("Successfully fixed.")
else:
    print("Failed")
