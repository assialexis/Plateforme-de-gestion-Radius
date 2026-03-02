import re
with open('/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/vouchers.php', 'r') as f:
    text = f.read()

scripts = re.findall(r'<script>(.*?)</script>', text, re.DOTALL)
for i, s in enumerate(scripts):
    with open(f'script_{i}.js', 'w') as f2:
        f2.write(s)
