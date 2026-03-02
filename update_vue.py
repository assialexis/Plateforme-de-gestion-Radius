import sys

file_path = '/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/profiles.php'

with open(file_path, 'r') as f:
    lines = f.readlines()

new_content = """<script>
    function profilesPage() {
        return {
            profiles: [],
            zones: [],
            showModal: false,
            showDeleteModal: false,
            showLinkModal: false,
            editMode: false,
            editId: null,
            viewMode: localStorage.getItem('profilesViewMode') || 'grid',
            search: '',
            selectedZoneFilter: '',
            selectedProfiles: [],
            selectedProfileForLink: null,
            paymentLink: '',
            deleting: false,
            form: {
                name: '',
                description: '',
                validity_value: '',
                validity_unit: 'hours',
                time_limit_value: '',
                time_limit_unit: 'hours',
                data_limit_mb: '',
                download_speed_mbps: '',
                upload_speed_mbps: '',
                price: 0,
                simultaneous_use: 1,
                zone_id: '',
                is_active: true
            },

            get filteredProfiles() {
                let filtered = this.profiles;

                if (this.search) {
                    filtered = filtered.filter(profile =>
                        profile.name.toLowerCase().includes(this.search.toLowerCase()) ||
                        (profile.description && profile.description.toLowerCase().includes(this.search.toLowerCase()))
                    );
                }

                if (this.selectedZoneFilter) {
                    filtered = filtered.filter(profile => profile.zone_id == this.selectedZoneFilter);
                }

                return filtered;
            },

            async init() {
                await Promise.all([
                    this.loadProfiles(),
                    this.loadZones()
                ]);
                this.$watch('viewMode', value => localStorage.setItem('profilesViewMode', value));
            },

            async loadProfiles() {
                try {
                    const response = await API.get('/profiles');
                    this.profiles = response.data;
                } catch (error) {
                    Toast.error(__('profile.msg_load_error'));
                }
            },

            async loadZones() {
                try {
                    const response = await API.get('/zones');
                    this.zones = response.data.filter(z => z.is_active);
                } catch (error) {
                    console.error('Erreur chargement zones:', error);
                }
            },

            resetForm() {
                this.form = {
                    name: '',
                    description: '',
                    validity_value: '',
                    validity_unit: 'hours',
                    time_limit_value: '',
                    time_limit_unit: 'hours',
                    data_limit_mb: '',
                    download_speed_mbps: '',
                    upload_speed_mbps: '',
                    price: 0,
                    simultaneous_use: 1,
                    zone_id: '',
                    is_active: true
                };
                this.editId = null;
            },

            secondsToValueUnit(seconds) {
                if (!seconds) return { value: '', unit: 'hours' };
                if (seconds % 86400 === 0) return { value: seconds / 86400, unit: 'days' };
                if (seconds % 3600 === 0) return { value: seconds / 3600, unit: 'hours' };
                return { value: seconds / 60, unit: 'minutes' };
            },

            valueUnitToSeconds(value, unit) {
                if (!value) return null;
                const v = parseFloat(value);
                switch (unit) {
                    case 'days': return v * 86400;
                    case 'hours': return v * 3600;
                    case 'minutes': return v * 60;
                    default: return v * 3600;
                }
            },

            editProfile(profile) {
                this.editMode = true;
                this.editId = profile.id;

                const validity = this.secondsToValueUnit(profile.validity);
                const timeLimit = this.secondsToValueUnit(profile.time_limit);

                this.form = {
                    name: profile.name,
                    description: profile.description || '',
                    validity_value: validity.value,
                    validity_unit: profile.validity_unit || validity.unit,
                    time_limit_value: timeLimit.value,
                    time_limit_unit: timeLimit.unit,
                    data_limit_mb: profile.data_limit ? profile.data_limit / (1024 * 1024) : '',
                    download_speed_mbps: profile.download_speed ? profile.download_speed / 1000000 : '',
                    upload_speed_mbps: profile.upload_speed ? profile.upload_speed / 1000000 : '',
                    price: profile.price || 0,
                    simultaneous_use: profile.simultaneous_use || 1,
                    zone_id: profile.zone_id || '',
                    is_active: profile.is_active == 1
                };
                this.showModal = true;
            },

            async saveProfile() {
                try {
                    const data = {
                        name: this.form.name,
                        description: this.form.description || null,
                        validity: this.valueUnitToSeconds(this.form.validity_value, this.form.validity_unit),
                        validity_unit: this.form.validity_unit,
                        time_limit: this.valueUnitToSeconds(this.form.time_limit_value, this.form.time_limit_unit),
                        data_limit: this.form.data_limit_mb ? this.form.data_limit_mb * 1024 * 1024 : null,
                        download_speed: this.form.download_speed_mbps ? this.form.download_speed_mbps * 1000000 : null,
                        upload_speed: this.form.upload_speed_mbps ? this.form.upload_speed_mbps * 1000000 : null,
                        price: this.form.price || 0,
                        simultaneous_use: this.form.simultaneous_use || 1,
                        zone_id: this.form.zone_id || null,
                        is_active: this.form.is_active ? 1 : 0
                    };

                    if (this.editMode) {
                        await API.put(`/profiles/${this.editId}`, data);
                        Toast.success(__('profile.msg_updated'));
                    } else {
                        await API.post('/profiles', data);
                        Toast.success(__('profile.msg_created'));
                    }
                    this.showModal = false;
                    await this.loadProfiles();
                } catch (error) {
                    Toast.error(error.message);
                }
            },

            async deleteProfile(profile) {
                if (!confirm(__('profile.msg_confirm_delete').replace(':name', profile.name))) return;

                try {
                    await API.delete(`/profiles/${profile.id}`);
                    Toast.success(__('profile.msg_deleted'));
                    await this.loadProfiles();
                } catch (error) {
                    Toast.error(error.message);
                }
            },

            isSelected(id) {
                return this.selectedProfiles.includes(id);
            },

            toggleSelect(id) {
                if (this.isSelected(id)) {
                    this.selectedProfiles = this.selectedProfiles.filter(p => p !== id);
                } else {
                    this.selectedProfiles.push(id);
                }
            },

            toggleSelectAll() {
                if (this.selectedProfiles.length === this.filteredProfiles.length && this.filteredProfiles.length > 0) {
                    this.selectedProfiles = [];
                } else {
                    this.selectedProfiles = this.filteredProfiles.map(p => p.id);
                }
            },

            selectAll() {
                this.selectedProfiles = this.filteredProfiles.map(p => p.id);
            },

            deleteSelected() {
                if (this.selectedProfiles.length === 0) return;
                this.showDeleteModal = true;
            },

            async confirmDeleteSelected() {
                this.deleting = true;
                let successCount = 0;
                let errorCount = 0;

                for (const id of this.selectedProfiles) {
                    try {
                        await API.delete(`/profiles/${id}`);
                        successCount++;
                    } catch (error) {
                        errorCount++;
                    }
                }

                this.deleting = false;
                this.showDeleteModal = false;
                this.selectedProfiles = [];

                if (successCount > 0) {
                    Toast.success(__('profile.msg_deleted_count').replace(':count', successCount));
                }
                if (errorCount > 0) {
                    Toast.error(__('profile.msg_delete_error').replace(':count', errorCount));
                }
                await this.loadProfiles();
            },

            getZoneName(zoneId) {
                if (!zoneId) return '';
                const zone = this.zones.find(z => z.id == zoneId);
                return zone ? zone.name : '';
            },

            getZoneColor(zoneId) {
                if (!zoneId) return null;
                const zone = this.zones.find(z => z.id == zoneId);
                return zone ? zone.color : null;
            },

            getSpeedText(profile) {
                if (!profile.download_speed && !profile.upload_speed) return __('common.unlimited');
                const down = profile.download_speed ? formatSpeed(profile.download_speed) : '∞';
                const up = profile.upload_speed ? formatSpeed(profile.upload_speed) : '∞';
                return `${down} / ${up}`;
            },

            formatValidity(profile) {
                if (!profile.validity) return __('profile.not_defined');
                const seconds = parseInt(profile.validity);
                const unit = profile.validity_unit || 'hours';

                switch (unit) {
                    case 'days':
                        const days = seconds / 86400;
                        return days === 1 ? '1 ' + __('time.day') : `${days} ` + __('time.days');
                    case 'hours':
                        const hours = seconds / 3600;
                        return hours === 1 ? '1 ' + __('time.hour') : `${hours} ` + __('time.hours');
                    case 'minutes':
                        const minutes = seconds / 60;
                        return minutes === 1 ? '1 ' + __('time.minute') : `${minutes} ` + __('time.minutes');
                    default:
                        return formatTime(seconds);
                }
            },

            async showPaymentLink(profile) {
                this.selectedProfileForLink = profile;
                try {
                    const response = await API.get(`/payments/link/${profile.id}`);
                    this.paymentLink = response.data.payment_link;
                    this.showLinkModal = true;
                } catch (error) {
                    Toast.error(__('profile.msg_link_error'));
                }
            },

            copyPaymentLink() {
                navigator.clipboard.writeText(this.paymentLink).then(() => {
                    Toast.success(__('profile.msg_link_copied'));
                }).catch(() => {
                    const input = document.getElementById('payment-link-input');
                    input.select();
                    document.execCommand('copy');
                    Toast.success(__('profile.msg_link_copied'));
                });
            },

            formatTimeLimit(seconds) {
                if (!seconds) return __('common.unlimited');
                seconds = parseInt(seconds);
                if (seconds % 86400 === 0) {
                    const d = seconds / 86400;
                    return d === 1 ? '1 ' + __('time.day') : `${d} ` + __('time.days');
                }
                if (seconds % 3600 === 0) {
                    const h = seconds / 3600;
                    return h === 1 ? '1 ' + __('time.hour') : `${h} ` + __('time.hours');
                }
                const m = Math.round(seconds / 60);
                return m === 1 ? '1 ' + __('time.minute') : `${m} ` + __('time.minutes');
            },

            formatTime(seconds) { return formatTime(seconds); },
            formatBytes(bytes) { return formatBytes(bytes); }
        }
    }
</script>
"""

with open(file_path, 'w') as f:
    f.writelines(lines[:738])
    f.write(new_content)

