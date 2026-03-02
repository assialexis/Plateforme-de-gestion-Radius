with open('/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/hotspot-templates.php', 'r') as f:
    text = f.read()

text = text.replace('class="w-full h-full border-0 transform scale-[0.6] origin-top-left"', 'class="absolute top-0 left-0 border-0 transform scale-[0.6] origin-top-left"')
with open('/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/hotspot-templates.php', 'w') as f:
    f.write(text)
