import sys

file_path = '/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/voucher-templates.php'
with open(file_path, 'r') as f:
    lines = f.readlines()

# find "showPresets: true,"
start_idx = -1
for i, line in enumerate(lines):
    if 'showPresets: true,' in line:
        start_idx = i
        break

end_idx = -1
for i, line in enumerate(lines):
    if 'resetForm() {' in line:
        end_idx = i
        break

if start_idx != -1 and end_idx != -1:
    with open(file_path, 'w') as f:
        f.writelines(lines[:start_idx])
        f.writelines(lines[end_idx:])
    print("Successfully replaced.")
else:
    print(f"Failed. start: {start_idx}, end: {end_idx}")

