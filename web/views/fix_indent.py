import sys

with open('hotspot-templates.php', 'r') as f:
    lines = f.readlines()

new_block = """<script>
    function hotspotTemplatesPage() {
        return {
            templates: [],
            zones: [],
            profiles: [],
            mediaItems: [],
            loading: true,
            saving: false,
            showCreateModal: false,
            showEditModal: false,
            showPreviewModal: false,
            activeTab: 'general',
            previewHtml: '',
            livePreviewHtml: '',
            previewDebounce: null,
            currentTemplate: null,
            form: {
                name: '',
                template_code: '',
                logo_url: '',
                logo_position: 'center',
                background_type: 'gradient',
                background_color: '#1e3a5f',
                background_gradient_start: '#1e3a5f',
                background_gradient_end: '#0d1b2a',
                primary_color: '#3b82f6',
                secondary_color: '#10b981',
                text_color: '#ffffff',
                card_bg_color: '#ffffff',
                card_text_color: '#1f2937',
                title_text: __('template.default_title'),
                subtitle_text: '',
                login_button_text: __('template.default_login_button'),
                username_placeholder: __('template.default_username'),
                password_placeholder: __('template.default_password'),
                footer_text: '',
                show_logo: true,
                show_password_field: true,
                show_remember_me: false,
                show_footer: true,
                show_chat_support: false,
                chat_support_type: 'whatsapp',
                chat_whatsapp_phone: '',
                chat_welcome_message: __('template.default_welcome'),
                html_content: '',
                css_content: '',
                js_content: '',
                config: {
                    contact_number: '',
                    default_auth_method: 'voucher',
                    zone_id: '',
                    selected_profiles: [],
                    logo_url: '',
                    slider_images: [],
                    services: []
                }
            },
"""

lines[866:924] = [new_block]

with open('hotspot-templates.php', 'w') as f:
    f.writelines(lines)
