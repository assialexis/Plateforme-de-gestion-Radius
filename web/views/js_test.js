
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
            get filteredProfiles() {                     if (!this.form.config.zone_id) return                         return this.profiles.filter(p => parseInt(p.zone_id) === parseInt(this.form.config.zo            
            },

            as                
                awa                    
                    t                    s(),
                                 Zones(),
                                dProfiles(),
                                    dMedia()
                ]);
                                                 es() {
                try {
                            t res = await API.get('/zones                            this             res.data || [];
                } catc                                              async loadProfiles() {
                                              const res = await A                files');
                        this.profiles = res.data || [];                     }                               },

            async loadMe                            try {
                                    await API.get('/            );
                        this.mediaIt                ta || [];
                } catch (e) { }
            },

                triggerLivePreview() {
                             .showCreateModal && !this.showEditModal) r                           clearTimeout(this.                nce);
                    this.previewDebounce = setTimeout(() =                                          LivePreview();
                }, 800);
                                async fetchLivePreview() {
                try {
                    t                    tml = ''; // Show spinner
                                 ponse = await API.                    /hotspot/preview-live', this.form);
                            .l            ewHtml = response.data.html;
                            (error) {
                          nsole.                     preview live", error);
                }
                                async loadTemplates() {
                              ng = true;
                                              const response = await AP                    s/hotspot');
                        this.templat                    ta || [];
                            (e                                    Toast.error(__('temp                d_error'));
                    this.templat                             } finally {
                    this.loading = fa                                           },

            getPreviewStyle(template) {
                if (template.background_type === 'color') {
                               `ba            -color: ${temp                und_color};`;
                    }
                            background: linear-                     ${template.ba                    t_start} 0%, ${template.b                    nt_end} 100%);`;
                                resetForm() {
                                                      name: '',
                             ate_code: '',
                    lo                                    logo_posit                                       backgroun                    t',
                                       r: '#1e3a5f',
                                nd_gradient_start: '#1e3a5f'                          background_gradient_end: '#0d1b2a',
                        primary_color:                                    secondary_color: '#10b981',
                                 r: '#ffffff',
                    card_bg_color: '#ffff                             card_text_color: '#1f2937',
                                      ('template.defaul                                   su                    
                    login_                    'template.default_login_b                                 userna                    __('template.default_usern                               password_placeholder                    efault_password'),
                          oter_text: '',
                    show_logo: true,
                        show_password_fi                                   sh                    false,
                             footer: tr                            show_chat_suppor                                  chat_support_type: 'wh                                 chat                        
                    ch                        __('template.d                                          h                                                          '                                   ontent: '',
                            ig:                               contact                
                        default_                 'voucher',
                            zone_id: '',
                            selected_profiles: [],
                                                                  slider_imag                                    services: []
                    }
                    };
                this.activeTab = 'general';
                                editTemplate(template) {
                this.currentTem                    ;
                this.form = {
                    name: template.name || '                           template_code: template.template_code || '',
                                        te.logo_url || '',
                    logo_position                    position || 'center',
                    background_typ                    ground_type || 'gradient',
                                       r: template.background_color || '#1e3a5f',
                             round_gradient_start: template.background_gradient_start                                       background_gradient_                    ckground_gradient_end || '#0d1b2a',
                             ry_color: template.primary_color || '#3b82f6',
                    secondary_color: te                    _color || '#10b981',
                    text_color: template.text_color || '#ffffff',
                        card_bg_color: template.card_bg_color || '#ffffff',
                    card_text_col                    d_text_color || '#1f2937',
                                  : template.title_text || '',
                        subtitle_text: template.subtitle_text || '',
                            n_button_text: template.login_button_text || __                    lt_login_button'),
                                      holder: template.username_placeholder || __('temp                    rname'),
                    password_placeholder: template.p                    der || __('template.default_password'),
                                     template.footer_text || '',
                    show_logo: !!template.show_logo,
                          ow_password_field: !!template.show_password                                show_remember_me: !!template.                    ,
                    show_footer: !!te                    er,
                    show_chat_support: !!template.show_chat_support,
                    chat_support_type: temp                        pe || 'whatsapp',
                          at_whatsapp_phone: template.chat                        ',
                                  me_message: template.ch                        | __('template                                                                   ate.html_cont                                       css                mplate.css_content || '',
                                  : template.js_content || '',
                    config: template.config ? (typeof                nfig === 'string' ? JSON.parse(template.config) : template.config) : {
                            contact_number: '',
                        default_auth                ucher',
                                     '',
                                      rofiles: [],
                            logo_url: '',
                                     ages: [],
                        services: []
                        }
                };

                // S'assurer q                    existent                                   this.form.config.sel                es) th                    elected_profiles = [];
                if (!this.f                        ages) this.form.config.slider_images = [];
                if (!this.form.c                        .form.config.services = [];

                                   = 'gener                         this.showEditModal = true;
                this.                                    },

            async saveTempl                                          form.name || !this.form.temp                                     To                ('template.msg_nam                    ));
                    return;
                }

                          aving = true                      try {
                             hi            itModal && this.currentTemplate) {
                              ait API.put(`/templates/hotspot/${this.currentTemplate.id}`, this.form);
                                 .succe                    msg_updated'));
                    } else {
                              ait API.post('/templates/hotspot', this.for                                Toast.success(__                sg_created'));
                        }
                    await this.loadTemplates();
                            .c            l();
                } catch (error) {
                        Toas                    ssage || __('template.msg_save_error'));
                } fi                                this.saving = false;
                                  },

            async delete                plate) {
                             irm(__('template.msg_confirm_delete').replace(':na                e.            return;

                try {
                    a                ete(`/                    t/${template.id}`);
                    Toast.success(__('templ                    ));
                    await this.loadTemplat                          } catch (error) {
                        Toast.error(er                    _('template.msg_delete_error'));
                }
            },
                 a            Default(template) {
                try {
                        await                     ates/hotspot/${template.id}/default`);
                    Toast.success(__('te                    efault'));
                    await th                    ();
                } catch (erro                             Toast.error(error.mes                common.error'));
                     
            },

            async duplicateTem                te                        try {
                    await API.pos                s/hots                    d}/duplicate`);
                    Toast.success(__('template.msg_duplicated')                           await this.loadTemplates();
                } catch (error) {                         Toast.error(error.message || __('t                    icate_error'));
                }
                                async p                    emplate) {
                                              const response = awa                    mplates/hot                    id}/generate`);
                                  ewHtml = response.data.htm                           this.currentTemplate = template;
                        this.showPreview                                  } catch (error) {
                                ro            mpla            review_error'));
                          },

            async downlo                 {
                try {
                    const response = awai                es/hotspot/${template.id}/generate`);
                 onst blob = new Blob([response.data.htm                ml' });
                      url = URL.createObjectURL(b                      const a = document.creat                                                             a.download =                               document.bo            );
                a.click();
                 ocument.body.removeChild(a);
                 RL.revokeObjectURL(url);
                   st.success(__('template.msg_d                          } catch (error) {                 Toast.error(__('template.msg_downlo                         }
                             re    ()          if (!this.previewHtml) return;
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
