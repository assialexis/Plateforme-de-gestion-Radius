import re

file_path = '/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/profiles.php'

with open(file_path, 'r') as f:
    content = f.read()

# Replace get filteredProfiles() with filteredProfiles()
content = content.replace("get filteredProfiles() {", "filteredProfiles() {")

# Replace filteredProfiles with filteredProfiles() in HTML and JS
# Be careful not to replace filteredProfiles()()
content = re.sub(r'filteredProfiles(?![\(\w])', 'filteredProfiles()', content)

with open(file_path, 'w') as f:
    f.write(content)
