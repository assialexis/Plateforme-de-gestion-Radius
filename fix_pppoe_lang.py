import re
import os

for file_path in ['/Applications/XAMPP/xamppfiles/htdocs/nas/web/client.php', '/Applications/XAMPP/xamppfiles/htdocs/nas/web/client-login.php']:
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Add session start
    session_code = """<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['lang']) && in_array(strtolower($_GET['lang']), ['fr', 'en'])) {
    $_SESSION['lang'] = strtolower($_GET['lang']);
}
"""
    content = re.sub(r'<\?php\s*', session_code, content, count=1)

    # 2. Add language switcher to the UI.
    # We will search for a place to put it. 
    # For client.php, beside the phone icon.
    lang_switcher = """
                <!-- Language Selector -->
                <div class="relative" x-data="{ openLang: false }">
                    <button @click="openLang = !openLang" @click.away="openLang = false" 
                            class="flex items-center gap-2 text-gray-400 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-globe text-sm"></i>
                        <span class="text-xs font-medium uppercase"><?= $_SESSION['lang'] ?? 'fr' ?></span>
                        <i class="fas fa-chevron-down text-[10px]"></i>
                    </button>
                    
                    <div x-show="openLang" x-cloak 
                         x-transition.opacity.duration.200ms
                         class="absolute right-0 mt-2 w-32 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50">
                        <a href="?lang=fr<?= isset($_GET['admin']) ? '&admin='.(int)$_GET['admin'] : '' ?>" 
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-indigo-600 <?= ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'bg-indigo-50 text-indigo-600 font-medium' : '' ?>">
                            <span class="mr-2">🇫🇷</span> Français
                        </a>
                        <a href="?lang=en<?= isset($_GET['admin']) ? '&admin='.(int)$_GET['admin'] : '' ?>" 
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-indigo-600 <?= ($_SESSION['lang'] ?? '') === 'en' ? 'bg-indigo-50 text-indigo-600 font-medium' : '' ?>">
                            <span class="mr-2">🇬🇧</span> English
                        </a>
                    </div>
                </div>
"""
    if "client.php" in file_path:
        # insert after companyPhone
        content = content.replace("<?php if ($companyPhone): ?>", lang_switcher + "\n                <?php if ($companyPhone): ?>")
    else:
        # insert top right in client-login.php
        lang_switcher_login = """
    <!-- Language Selector -->
    <div class="absolute top-4 right-4" x-data="{ openLang: false }">
        <button @click="openLang = !openLang" @click.away="openLang = false" 
                class="flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-full backdrop-blur-sm transition-all text-sm font-medium">
            <i class="fas fa-globe"></i>
            <span class="uppercase"><?= $_SESSION['lang'] ?? 'fr' ?></span>
        </button>
        
        <div x-show="openLang" x-cloak 
             x-transition.opacity.duration.200ms
             class="absolute right-0 mt-2 w-32 bg-white rounded-xl shadow-lg py-1 z-50 overflow-hidden">
            <a href="?lang=fr<?= isset($_GET['admin']) ? '&admin='.(int)$_GET['admin'] : '' ?>" 
               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 <?= ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'bg-indigo-50 text-indigo-600 font-medium' : '' ?>">
                <span class="mr-2">🇫🇷</span> Français
            </a>
            <a href="?lang=en<?= isset($_GET['admin']) ? '&admin='.(int)$_GET['admin'] : '' ?>" 
               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 <?= ($_SESSION['lang'] ?? '') === 'en' ? 'bg-indigo-50 text-indigo-600 font-medium' : '' ?>">
                <span class="mr-2">🇬🇧</span> English
            </a>
        </div>
    </div>
"""
        content = content.replace('<div x-data="clientLogin()" class="w-full max-w-md">', '<div x-data="clientLogin()" class="w-full max-w-md">\n' + lang_switcher_login)

    # 3. Handle old $lang replacement or redefine t()
    # Replace the block:
    old_lang_re = r"// Langue.*?function t[^}]+\}"
    replacement = """
// Langue
$lang = $_SESSION['lang'] ?? 'fr';
function t(string $key, string $default = ''): string {
    return __($key);
}
"""
    content = re.sub(old_lang_re, replacement, content, flags=re.DOTALL)
    
    # 4. In client.php replace Javascript t() equivalent where needed, wait, client.php JS uses __()? NO, it doesn't use any __() or t() in JS! It's all Alpine inline! Oh wait, `showToast(__('client_portal.xxx'))` ? Let's check. 
    # Ah, the JS in client.php doesn't seem to do translations natively, but I can add a small snippet to render language strings into a JS variable like in login.php if needed, BUT wait, client.php actually uses Alpine component which might not have strings. Let's see if we missed anything.

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

print("Done")
