with open('hotspot-templates.php', 'r') as f:
    text = f.read()
    js = text.split('<script>')[1].split('</script>')[0]
    with open('/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/js_test.js', 'w') as out:
        out.write(js)
