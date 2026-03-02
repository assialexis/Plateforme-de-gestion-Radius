<?php $pageTitle = 'Bibliothèque'; $currentPage = 'library'; ?>

<div x-data="libraryPage()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <p class="text-gray-600 dark:text-gray-400"><?= __('library.subtitle') ?></p>
    </div>

    <!-- Section Logo -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <?= __('library.company_logo') ?>
        </h2>
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
            <template x-if="logo">
                <div class="flex items-center gap-6">
                    <!-- Aperçu du logo -->
                    <div class="w-40 h-40 rounded-lg border-2 border-dashed border-gray-300 dark:border-[#30363d] flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-[#21262d]">
                        <template x-if="logo.url">
                            <img :src="logo.url" :alt="logo.description" class="max-w-full max-h-full object-contain">
                        </template>
                        <template x-if="!logo.url">
                            <div class="text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm"><?= __('library.no_logo') ?></p>
                            </div>
                        </template>
                    </div>
                    <!-- Infos et actions -->
                    <div class="flex-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2" x-text="logo.description"></p>
                        <template x-if="logo.original_name">
                            <div class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                                <p><?= __('library.file_label') ?> <span class="font-mono" x-text="logo.original_name"></span></p>
                                <p><?= __('library.size_label') ?> <span x-text="formatFileSize(logo.file_size)"></span></p>
                            </div>
                        </template>
                        <div class="flex gap-3">
                            <label class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 cursor-pointer transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span x-text="logo.url ? '<?= __('library.change') ?>' : '<?= __('library.upload') ?>'"></span>
                                <input type="file" class="hidden" accept="image/*" @change="uploadFile(logo, $event)">
                            </label>
                            <button x-show="logo.url" @click="deleteFile(logo)"
                                    class="inline-flex items-center px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <?= __('library.delete') ?>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-2"><?= __('library.image_formats') ?> • <?= __('library.max_size') ?></p>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Section Images -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <?= __('library.hotspot_images') ?>
            <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400"><?= __('library.carousel_hint') ?></span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <template x-for="(image, index) in images" :key="image.id">
                <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                    <!-- Aperçu -->
                    <div class="aspect-video bg-gray-100 dark:bg-[#21262d] flex items-center justify-center overflow-hidden">
                        <template x-if="image.url">
                            <img :src="image.url" :alt="image.description" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!image.url">
                            <div class="text-center text-gray-400">
                                <svg class="w-10 h-10 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-xs">Image <span x-text="index + 1"></span></p>
                            </div>
                        </template>
                    </div>
                    <!-- Infos -->
                    <div class="p-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-1" x-text="'Image ' + (index + 1)"></p>
                        <template x-if="image.original_name">
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate mb-2" x-text="image.original_name"></p>
                        </template>
                        <template x-if="!image.original_name">
                            <p class="text-xs text-gray-400 mb-2"><?= __('library.no_image') ?></p>
                        </template>
                        <div class="flex gap-2">
                            <label class="flex-1 inline-flex items-center justify-center px-3 py-1.5 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 cursor-pointer transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span x-text="image.url ? '<?= __('library.change') ?>' : '<?= __('library.upload') ?>'"></span>
                                <input type="file" class="hidden" accept="image/*" @change="uploadFile(image, $event)">
                            </label>
                            <button x-show="image.url" @click="deleteFile(image)"
                                    class="p-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                    title="<?= __('library.delete') ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <p class="text-xs text-gray-400 mt-2"><?= __('library.image_formats') ?> • <?= __('library.max_size') ?> <?= __('misc.per_image') ?> • <?= __('library.recommended_size') ?></p>
    </div>

    <!-- Section Audio -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
            </svg>
            <?= __('library.welcome_audio') ?>
        </h2>
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
            <template x-if="audio">
                <div class="flex items-center gap-6">
                    <!-- Icône audio -->
                    <div class="w-24 h-24 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-12 h-12 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                        </svg>
                    </div>
                    <!-- Infos et actions -->
                    <div class="flex-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2" x-text="audio.description"></p>
                        <template x-if="audio.original_name">
                            <div class="mb-4">
                                <div class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                    <p><?= __('library.file_label') ?> <span class="font-mono" x-text="audio.original_name"></span></p>
                                    <p><?= __('library.size_label') ?> <span x-text="formatFileSize(audio.file_size)"></span></p>
                                </div>
                                <!-- Lecteur audio -->
                                <audio controls class="w-full max-w-md">
                                    <source :src="audio.url" :type="audio.mime_type">
                                    Votre navigateur ne supporte pas l'audio.
                                </audio>
                            </div>
                        </template>
                        <template x-if="!audio.original_name">
                            <p class="text-sm text-gray-400 mb-4"><?= __('library.no_audio') ?></p>
                        </template>
                        <div class="flex gap-3">
                            <label class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 cursor-pointer transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span x-text="audio.url ? '<?= __('library.change') ?>' : '<?= __('library.upload') ?>'"></span>
                                <input type="file" class="hidden" accept="audio/*" @change="uploadFile(audio, $event)">
                            </label>
                            <button x-show="audio.url" @click="deleteFile(audio)"
                                    class="inline-flex items-center px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <?= __('library.delete') ?>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-2"><?= __('library.audio_formats') ?> • <strong><?= __('library.max_audio_size') ?></strong></p>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Loading overlay -->
    <div x-show="uploading" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-8 text-center">
            <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-900 dark:text-white font-medium"><?= __('library.uploading') ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="uploadProgress"></p>
        </div>
    </div>
</div>

<script>
function libraryPage() {
    return {
        media: [],
        logo: null,
        images: [],
        audio: null,
        uploading: false,
        uploadProgress: '',

        async init() {
            await this.loadMedia();
        },

        async loadMedia() {
            try {
                const response = await API.get('/library');
                this.media = response.data;

                // Séparer par type
                this.logo = this.media.find(m => m.media_key === 'company_logo');
                this.images = this.media.filter(m => m.media_type === 'image');
                this.audio = this.media.find(m => m.media_type === 'audio');
            } catch (error) {
                showToast('<?= __('library.msg_loading_error') ?>', 'error');
            }
        },

        async uploadFile(media, event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validation côté client
            const maxSize = media.media_type === 'audio' ? 800 * 1024 : 2 * 1024 * 1024;
            if (file.size > maxSize) {
                showToast(`Fichier trop volumineux. Max: ${media.media_type === 'audio' ? '800KB' : '2MB'}`, 'error');
                event.target.value = '';
                return;
            }

            this.uploading = true;
            this.uploadProgress = `Upload de ${file.name}...`;

            try {
                const formData = new FormData();
                formData.append('file', file);

                const response = await fetch(`api.php?route=/library/${media.id}/upload`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Upload failed');
                }

                showToast('<?= __('library.msg_upload_success') ?>');
                await this.loadMedia();
            } catch (error) {
                showToast(error.message || 'Erreur lors de l\'upload', 'error');
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },

        async deleteFile(media) {
            if (!confirmAction('<?= __('library.confirm_delete_file') ?>')) return;

            try {
                await API.delete(`/library/${media.id}/file`);
                showToast('<?= __('library.msg_file_deleted') ?>');
                await this.loadMedia();
            } catch (error) {
                showToast(error.message || 'Erreur lors de la suppression', 'error');
            }
        },

        formatFileSize(bytes) {
            if (!bytes) return '-';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }
    }
}
</script>
