with open('/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/hotspot-templates.php', 'r') as f:
    text = f.read()

# Replace the complicated scaled iframe with a simple 100% x 100% iframe
old_block = """                                <div
                                    class="relative border border-gray-200/60 dark:border-[#30363d] rounded-lg overflow-hidden aspect-[9/16] w-full max-h-[600px] bg-white dark:bg-gray-100 flex flex-col justify-center">
                                    <template x-if="livePreviewHtml">
                                        <iframe :srcdoc="livePreviewHtml"
                                            class="absolute top-0 left-0 border-0 transform scale-[0.6] origin-top-left"
                                            style="width: 166.66%; height: 166.66%;"
                                            sandbox="allow-scripts allow-same-origin"></iframe>
                                    </template>"""

new_block = """                                <div
                                    class="relative border border-gray-200/60 dark:border-[#30363d] rounded-lg overflow-hidden aspect-[9/16] w-full max-w-[360px] mx-auto max-h-[640px] bg-white dark:bg-[#0d1117] flex flex-col shadow-inner">
                                    <template x-if="livePreviewHtml">
                                        <iframe :srcdoc="livePreviewHtml"
                                            class="absolute inset-0 w-full h-full border-0"
                                            sandbox="allow-scripts allow-same-origin"></iframe>
                                    </template>"""

if old_block in text:
    text = text.replace(old_block, new_block)
else:
    print("Block not found!")
    
with open('/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/hotspot-templates.php', 'w') as f:
    f.write(text)
