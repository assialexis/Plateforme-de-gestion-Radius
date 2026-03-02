import re

with open('/tmp/js_test.js', 'r') as f:
    js_lines = f.readlines()

# exclude first line and last line
js_content = "".join(js_lines[1:-1])

# fix the bug in downloadTemplate
js_content = js_content.replace('''            } catch (error) {
                Toast.error(__('template.msg_download_error'));


                downloadPreviewHtml() {''', 
'''            } catch (error) {
                Toast.error(__('template.msg_download_error'));
            }
        },

        downloadPreviewHtml() {''')

with open('hotspot-templates.php', 'r') as f:
    php_content = f.read()

prefix = php_content.split('<script>')[0]
# remove trailing spaces of prefix before adding <script> later
prefix = prefix.rstrip()

new_content = prefix + "\n\n<script>\n" + js_content + "\n</script>\n"

with open('hotspot-templates.php', 'w') as f:
    f.write(new_content)

