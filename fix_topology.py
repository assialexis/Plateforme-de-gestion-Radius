import sys

file_path = '/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/topology.php'
with open(file_path, 'r') as f:
    lines = f.readlines()

with open('topology_js.txt', 'r') as f:
    new_content = f.read()

start_idx = -1
end_idx = -1

for i, line in enumerate(lines):
    if '<script>' in line and 'function topologyPage' in "".join(lines[i:min(len(lines), i+5)]):
        start_idx = i
        break

for i in range(len(lines)-1, -1, -1):
    if '</script>' in lines[i]:
        end_idx = i
        break

if start_idx != -1 and end_idx != -1:
    with open(file_path, 'w') as f:
        f.writelines(lines[:start_idx])
        f.write("<script>\n")
        f.write(new_content)
        f.write("\n</script>\n")
        f.writelines(lines[end_idx+1:])
    print("Successfully replaced.")
else:
    print(f"Failed. start: {start_idx}, end: {end_idx}")

