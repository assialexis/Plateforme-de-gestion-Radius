import re

file_path = '/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/profiles.php'

with open(file_path, 'r') as f:
    content = f.read()

# Replace filteredProfiles() in HTML
content = content.replace("profile in filteredProfiles()", "profile in filteredProfilesData")
content = content.replace("filteredProfiles().length", "filteredProfilesData.length")
content = content.replace("this.filteredProfiles()", "this.filteredProfilesData")

# In the JS state object, replace filteredProfiles() method with applyFilters() and add filteredProfilesData array
js_method = """filteredProfiles() {
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
            }"""

js_method_new = """applyFilters() {
                let filtered = this.profiles;

                if (this.search) {
                    filtered = filtered.filter(profile =>
                        profile.name?.toLowerCase().includes(this.search.toLowerCase()) ||
                        (profile.description && profile.description.toLowerCase().includes(this.search.toLowerCase()))
                    );
                }

                if (this.selectedZoneFilter) {
                    filtered = filtered.filter(profile => profile.zone_id == this.selectedZoneFilter);
                }

                this.filteredProfilesData = filtered;
            }"""

content = content.replace(js_method, js_method_new)

# Add filteredProfilesData: [] to the state
content = content.replace("profiles: [],", "profiles: [],\n            filteredProfilesData: [],")

# Update init() to watch modifiers
init_old = """async init() {
                await Promise.all([
                    this.loadProfiles(),
                    this.loadZones()
                ]);
                this.$watch('viewMode', value => localStorage.setItem('profilesViewMode', value));
            }"""

init_new = """async init() {
                await Promise.all([
                    this.loadProfiles(),
                    this.loadZones()
                ]);
                this.$watch('viewMode', value => localStorage.setItem('profilesViewMode', value));
                this.$watch('search', () => this.applyFilters());
                this.$watch('selectedZoneFilter', () => this.applyFilters());
            }"""

content = content.replace(init_old, init_new)

# Update loadProfiles() to populate array
load_old = """async loadProfiles() {
                try {
                    const response = await API.get('/profiles');
                    this.profiles = response.data;
                } catch (error) {"""
                
load_new = """async loadProfiles() {
                try {
                    const response = await API.get('/profiles');
                    this.profiles = response.data;
                    this.applyFilters();
                } catch (error) {"""
                
content = content.replace(load_old, load_new)

with open(file_path, 'w') as f:
    f.write(content)
