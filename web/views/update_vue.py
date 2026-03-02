with open('/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/hotspot-templates.php', 'r') as f:
    text = f.read()

# Add preview mode variable
text = text.replace("activeTab: 'general',", "activeTab: 'general',\n            previewMode: 'mobile',")

# Update HTML component header to include desktop/mobile switch
header_old = """                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    <?= __('template.live_preview')?>
                                </h4>"""

header_new = """                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <?= __('template.live_preview')?>
                                    </h4>
                                    <div class="flex items-center bg-gray-100 dark:bg-[#21262d] rounded-lg p-1">
                                        <button @click="previewMode = 'mobile'" type="button" 
                                            :class="previewMode === 'mobile' ? 'bg-white dark:bg-[#30363d] shadow-sm text-primary-600' : 'text-gray-500 hover:text-gray-700'"
                                            class="px-3 py-1 text-xs font-medium rounded-md transition-all flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            Mobile
                                        </button>
                                        <button @click="previewMode = 'desktop'" type="button"
                                            :class="previewMode === 'desktop' ? 'bg-white dark:bg-[#30363d] shadow-sm text-primary-600' : 'text-gray-500 hover:text-gray-700'"
                                            class="px-3 py-1 text-xs font-medium rounded-md transition-all flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            Desktop
                                        </button>
                                    </div>
                                </div>"""

text = text.replace(header_old, header_new)

# Update HTML component sizing based on active preview variable
iframe_old = """                                <div
                                    class="relative border border-gray-200/60 dark:border-[#30363d] rounded-lg overflow-hidden aspect-[9/16] w-full max-w-[360px] mx-auto max-h-[640px] bg-white dark:bg-[#0d1117] flex flex-col shadow-inner">
                                    <template x-if="livePreviewHtml">
                                        <iframe :srcdoc="livePreviewHtml"
                                            class="absolute inset-0 w-full h-full border-0"
                                            sandbox="allow-scripts allow-same-origin"></iframe>
                                    </template>
                                    <template x-if="!livePreviewHtml">
                                        <div
                                            class="absolute inset-0 flex items-center justify-center bg-gray-50/50 backdrop-blur-sm">
                                            <div
                                                class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent">
                                            </div>
                                        </div>
                                    </template>
                                </div>"""

iframe_new = """                                <div
                                    :class="previewMode === 'mobile' ? 'max-w-[360px] aspect-[9/16] max-h-[640px]' : 'w-full aspect-video'"
                                    class="relative border border-gray-200/60 dark:border-[#30363d] rounded-lg overflow-hidden mx-auto bg-white dark:bg-[#0d1117] flex flex-col shadow-inner transition-all duration-300">
                                    <template x-if="livePreviewHtml">
                                        <iframe :srcdoc="livePreviewHtml"
                                            class="absolute inset-0 w-full h-full border-0"
                                            sandbox="allow-scripts allow-same-origin"></iframe>
                                    </template>
                                    <template x-if="!livePreviewHtml">
                                        <div
                                            class="absolute inset-0 flex items-center justify-center bg-gray-50/50 backdrop-blur-sm">
                                            <div
                                                class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent">
                                            </div>
                                        </div>
                                    </template>
                                </div>"""

text = text.replace(iframe_old, iframe_new)

with open('/Applications/XAMPP/xamppfiles/htdocs/nas/web/views/hotspot-templates.php', 'w') as f:
    f.write(text)
