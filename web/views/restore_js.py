with open('hotspot-templates.php', 'r') as f:
    text = f.read()

prefix = text.split('<script>')[0].rstrip()

js_content = """
<script>
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
            previewMode: 'mobile',
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

            get filteredProfiles() {
                if (!this.form.config.zone_id) return [];
                return this.profiles.filter(p => parseInt(p.zone_id) === parseInt(this.form.config.zone_id));
            },

            async init() {
                await Promise.all([
                    this.loadTemplates(),
                    this.loadZones(),
                    this.loadProfiles(),
                    this.loadMedia()
                ]);
            },

            async loadZones() {
                try {
                    const res = await API.get('/zones');
                    this.zones = res.data || [];
                } catch (e) { }
            },

            async loadProfiles() {
                try {
                    const res = await API.get('/profiles');
                    this.profiles = res.data || [];
                } catch (e) { }
            },

            async loadMedia() {
                try {
                    const res = await API.get('/library');
                    this.mediaItems = res.data || [];
                } catch (e) { }
            },

            triggerLivePreview() {
                if (!this.showCreateModal && !this.showEditModal) return;
                clearTimeout(this.previewDebounce);
                this.previewDebounce = setTimeout(() => {
                    this.fetchLivePreview();
                }, 800);
            },

            async fetchLivePreview() {
                try {
                    this.livePreviewHtml = ''; // Show spinner
                    const response = await API.post('/templates/hotspot/preview-live', this.form);
                    this.livePreviewHtml = response.data.html;
                } catch (error) {
                    console.error("Erreur de preview live", error);
                }
            },

            async loadTemplates() {
                this.loading = true;
                try {
                    const response = await API.get('/templates/hotspot');
                    this.templates = response.data || [];
                } catch (error) {
                    Toast.error(__('template.msg_load_error'));
                    this.templates = [];
                } finally {
                    this.loading = false;
                }
            },

            getPreviewStyle(template) {
                if (template.background_type === 'color') {
                    return `background-color: ${template.background_color};`;
                }
                return `background: linear-gradient(135deg, ${template.background_gradient_start} 0%, ${template.background_gradient_end} 100%);`;
            },

            resetForm() {
                this.form = {
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
                };
                this.activeTab = 'general';
            },

            editTemplate(template) {
                this.currentTemplate = template;
                this.form = {
                    name: template.name || '',
                    template_code: template.template_code || '',
                    logo_url: template.logo_url || '',
                    logo_position: template.logo_position || 'center',
                    background_type: template.background_type || 'gradient',
                    background_color: template.background_color || '#1e3a5f',
                    background_gradient_start: template.background_gradient_start || '#1e3a5f',
                    background_gradient_end: template.background_gradient_end || '#0d1b2a',
                    primary_color: template.primary_color || '#3b82f6',
                    secondary_color: template.secondary_color || '#10b981',
                    text_color: template.text_color || '#ffffff',
                    card_bg_color: template.card_bg_color || '#ffffff',
                    card_text_color: template.card_text_color || '#1f2937',
                    title_text: template.title_text || '',
                    subtitle_text: template.subtitle_text || '',
                    login_button_text: template.login_button_text || __('template.default_login_button'),
                    username_placeholder: template.username_placeholder || __('template.default_username'),
                    password_placeholder: template.password_placeholder || __('template.default_password'),
                    footer_text: template.footer_text || '',
                    show_logo: !!template.show_logo,
                    show_password_field: !!template.show_password_field,
                    show_remember_me: !!template.show_remember_me,
                    show_footer: !!template.show_footer,
                    show_chat_support: !!template.show_chat_support,
                    chat_support_type: template.chat_support_type || 'whatsapp',
                    chat_whatsapp_phone: template.chat_whatsapp_phone || '',
                    chat_welcome_message: template.chat_welcome_message || __('template.default_welcome'),
                    html_content: template.html_content || '',
                    css_content: template.css_content || '',
                    js_content: template.js_content || '',
                    config: template.config ? (typeof template.config === 'string' ? JSON.parse(template.config) : template.config) : {
                        contact_number: '',
                        default_auth_method: 'voucher',
                        zone_id: '',
                        selected_profiles: [],
                        logo_url: '',
                        slider_images: [],
                        services: []
                    }
                };
                
                // S'assurer que les tableaux existent bien
                if (!this.form.config.selected_profiles) this.form.config.selected_profiles = [];
                if (!this.form.config.slider_images) this.form.config.slider_images = [];
                if (!this.form.config.services) this.form.config.services = [];

                this.activeTab = 'general';
                this.showEditModal = true;
                this.fetchLivePreview();
            },

            async saveTemplate() {
                if (!this.form.name || !this.form.template_code) {
                    Toast.error(__('template.msg_name_code_required'));
                    return;
                }

                this.saving = true;
                try {
                    if (this.showEditModal && this.currentTemplate) {
                        await API.put(`/templates/hotspot/${this.currentTemplate.id}`, this.form);
                        Toast.success(__('template.msg_updated'));
                    } else {
                        await API.post('/templates/hotspot', this.form);
                        Toast.success(__('template.msg_created'));
                    }
                    await this.loadTemplates();
                    this.closeModal();
                } catch (error) {
                    Toast.error(error.message || __('template.msg_save_error'));
                } finally {
                    this.saving = false;
                }
            },

            async deleteTemplate(template) {
                if (!confirm(__('template.msg_confirm_delete').replace(':name', template.name))) return;

                try {
                    await API.delete(`/templates/hotspot/${template.id}`);
                    Toast.success(__('template.msg_deleted'));
                    await this.loadTemplates();
                } catch (error) {
                    Toast.error(error.message || __('template.msg_delete_error'));
                }
            },

            async setDefault(template) {
                try {
                    await API.post(`/templates/hotspot/${template.id}/default`);
                    Toast.success(__('template.msg_set_default'));
                    await this.loadTemplates();
                } catch (error) {
                    Toast.error(error.message || __('common.error'));
                }
            },

            async duplicateTemplate(template) {
                try {
                    await API.post(`/templates/hotspot/${template.id}/duplicate`);
                    Toast.success(__('template.msg_duplicated'));
                    await this.loadTemplates();
                } catch (error) {
                    Toast.error(error.message || __('template.msg_duplicate_error'));
                }
            },

            async previewTemplate(template) {
                try {
                    const response = await API.post(`/templates/hotspot/${template.id}/generate`);
                    this.previewHtml = response.data.html;
                    this.currentTemplate = template;
                    this.showPreviewModal = true;
                } catch (error) {
                    Toast.error(__('template.msg_preview_error'));
                }
            },

            async downloadTemplate(template) {
                try {
                    const response = await API.post(`/templates/hotspot/${template.id}/generate`);
                    const blob = new Blob([response.data.html], { type: 'text/html' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'login.html';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    Toast.success(__('template.msg_downloaded'));
                } catch (error) {
                    Toast.error(__('template.msg_download_error'));
                }
            },

            downloadPreviewHtml() {
                if (!this.previewHtml) return;
                const blob = new Blob([this.previewHtml], { type: 'text/html' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'login.html';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            },

            closeModal() {
                this.showCreateModal = false;
                this.showEditModal = false;
                this.currentTemplate = null;
                this.livePreviewHtml = '';
                clearTimeout(this.previewDebounce);
                this.resetForm();
            }
        }
    }
</script>"""

with open('hotspot-templates.php', 'w') as f:
    f.write(prefix + "\n" + js_content + "\n")

