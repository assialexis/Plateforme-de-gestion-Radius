<?php $pageTitle = 'Chat Support'; $currentPage = 'chat'; ?>

<div x-data="chatPage()" x-init="init()" class="h-[calc(100vh-8rem)]">
    <div class="flex h-full bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <!-- Liste des conversations -->
        <div class="w-80 border-r border-gray-200 dark:border-[#30363d] flex flex-col">
            <!-- En-tête -->
            <div class="p-4 border-b border-gray-200 dark:border-[#30363d]">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('chat.conversations') ?></h2>
                    <button @click="showWidgetPanel = true; loadWidgetCode()"
                            class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-[#30363d] rounded-lg transition-colors"
                            title="<?= __('chat.widget_embeddable') ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </button>
                </div>
                <div class="mt-2 relative">
                    <input type="text" x-model="search" @input="loadConversations()" placeholder="<?= __('chat.search') ?>"
                           class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <!-- Filtre par statut -->
                <div class="mt-2 flex gap-1">
                    <button @click="statusFilter = 'active'; loadConversations()"
                            :class="statusFilter === 'active' ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-[#30363d]'"
                            class="px-3 py-1 text-xs rounded-full transition-colors"><?= __('chat.filter_active') ?></button>
                    <button @click="statusFilter = 'closed'; loadConversations()"
                            :class="statusFilter === 'closed' ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-[#30363d]'"
                            class="px-3 py-1 text-xs rounded-full transition-colors"><?= __('chat.filter_closed') ?></button>
                    <button @click="statusFilter = 'all'; loadConversations()"
                            :class="statusFilter === 'all' ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-[#30363d]'"
                            class="px-3 py-1 text-xs rounded-full transition-colors"><?= __('chat.filter_all') ?></button>
                </div>
            </div>

            <!-- Liste -->
            <div class="flex-1 overflow-y-auto">
                <template x-if="loadingConversations">
                    <div class="flex justify-center py-8">
                        <svg class="animate-spin h-6 w-6 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </template>

                <template x-if="!loadingConversations && conversations.length === 0">
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="mt-2 text-sm"><?= __('chat.no_conversation') ?></p>
                    </div>
                </template>

                <template x-for="conv in conversations" :key="conv.id">
                    <button @click="selectConversation(conv)"
                            :class="selectedConversation?.id === conv.id ? 'bg-primary-50 dark:bg-primary-900/20 border-l-4 border-primary-500' : 'hover:bg-gray-50 dark:hover:bg-[#30363d]/50'"
                            class="w-full p-4 text-left border-b border-gray-100 dark:border-[#30363d] transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                                    <span x-text="(conv.customer_name || conv.phone).charAt(0).toUpperCase()"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white truncate" x-text="conv.customer_name || '<?= __('chat.client_label') ?>'"></p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate" x-text="conv.phone"></p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end flex-shrink-0 ml-2">
                                <span x-show="conv.unread_admin > 0"
                                      class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"
                                      x-text="conv.unread_admin"></span>
                                <span class="text-xs text-gray-400 mt-1" x-text="formatTime(conv.last_message_at)"></span>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 truncate" x-text="formatLastMessage(conv.last_message)"></p>
                    </button>
                </template>
            </div>
        </div>

        <!-- Zone de chat -->
        <div class="flex-1 flex flex-col h-full overflow-hidden">
            <!-- Si aucune conversation selectionnee -->
            <template x-if="!selectedConversation">
                <div class="flex-1 flex items-center justify-center text-gray-400 dark:text-gray-500">
                    <div class="text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="mt-4 text-lg"><?= __('chat.select_conversation') ?></p>
                        <p class="text-sm"><?= __('chat.start_chatting') ?></p>
                    </div>
                </div>
            </template>

            <!-- Conversation active -->
            <div x-show="selectedConversation" class="flex-1 flex flex-col h-full overflow-hidden">
                    <!-- Header de la conversation -->
                    <div class="flex-shrink-0 p-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold">
                                <span x-text="(selectedConversation?.customer_name || selectedConversation?.phone || '?').charAt(0).toUpperCase()"></span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white" x-text="selectedConversation?.customer_name || '<?= __('chat.client_label') ?>'"></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="selectedConversation?.phone"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Call status / button -->
                            <template x-if="callState === 'idle' && selectedConversation?.status === 'active'">
                                <button @click="startCall()"
                                        class="p-2 text-green-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                        title="<?= __('chat.call') ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </button>
                            </template>
                            <template x-if="callState === 'calling' || callState === 'connected'">
                                <div class="flex items-center gap-2">
                                    <span class="flex items-center gap-1.5 px-2 py-1 text-xs font-medium rounded-full"
                                          :class="callState === 'connected' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'">
                                        <span class="w-2 h-2 rounded-full animate-pulse" :class="callState === 'connected' ? 'bg-green-500' : 'bg-yellow-500'"></span>
                                        <span x-text="callState === 'connected' ? callDurationFormatted : '<?= __('chat.calling') ?>'"></span>
                                    </span>
                                    <button @click="toggleMute()"
                                            class="p-2 rounded-lg transition-colors"
                                            :class="isMuted ? 'text-red-500 bg-red-50 dark:bg-red-900/20' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-[#30363d]'"
                                            :title="isMuted ? '<?= __('chat.unmute') ?>' : '<?= __('chat.mute') ?>'">
                                        <svg x-show="!isMuted" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                        </svg>
                                        <svg x-show="isMuted" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                                        </svg>
                                    </button>
                                    <button @click="endCall()"
                                            class="p-2 text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors"
                                            title="<?= __('chat.hangup') ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.516l2.257-1.13a1 1 0 00.502-1.21L8.228 3.684A1 1 0 007.28 3H5z"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <span x-show="selectedConversation?.status === 'active'"
                                  class="px-2 py-1 text-xs font-medium text-green-700 bg-green-100 dark:bg-green-900/30 dark:text-green-400 rounded-full"><?= __('chat.status_active') ?></span>
                            <span x-show="selectedConversation?.status === 'closed'"
                                  class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 dark:bg-[#21262d] dark:text-gray-400 rounded-full"><?= __('chat.status_closed') ?></span>
                            <button x-show="selectedConversation?.status === 'active'" @click="closeConversation()"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] rounded-lg transition-colors"
                                    title="<?= __('chat.close_conversation') ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                            <button @click="deleteConversation()"
                                    class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-[#30363d] rounded-lg transition-colors"
                                    title="<?= __('chat.delete_conversation') ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Incoming call overlay -->
                    <div x-show="callState === 'incoming'" x-transition
                         class="flex-shrink-0 bg-gradient-to-r from-green-500 to-emerald-600 p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3 text-white">
                            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center animate-pulse">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold"><?= __('chat.incoming_call') ?></p>
                                <p class="text-sm text-white/80" x-text="(selectedConversation?.customer_name || '<?= __('chat.client_label') ?>') + ' <?= __('chat.is_calling_you') ?>'"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="acceptCall()"
                                    class="px-4 py-2 bg-white text-green-600 font-semibold rounded-lg hover:bg-green-50 transition-colors flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <?= __('chat.accept') ?>
                            </button>
                            <button @click="rejectCall()"
                                    class="px-4 py-2 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600 transition-colors flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.516l2.257-1.13a1 1 0 00.502-1.21L8.228 3.684A1 1 0 007.28 3H5z"/>
                                </svg>
                                <?= __('chat.reject') ?>
                            </button>
                        </div>
                    </div>

                    <!-- Audio element for remote stream -->
                    <audio id="remoteAudio" autoplay></audio>

                    <!-- Messages -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-4 min-h-0" id="adminMessagesContainer">
                        <!-- Loading -->
                        <div x-show="loadingMessages" class="flex justify-center py-8">
                            <svg class="animate-spin h-6 w-6 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>

                        <!-- Empty state -->
                        <div x-show="messages.length === 0 && !loadingMessages" class="text-center text-gray-500 py-8">
                            <p class="text-sm">Aucun message dans cette conversation</p>
                        </div>

                        <!-- Messages list -->
                        <template x-for="msg in messages" :key="msg.id">
                            <div>
                                <!-- Call history message -->
                                <div x-show="isCallMessage(msg)" class="flex justify-center my-2">
                                    <div class="bg-gray-100 dark:bg-[#21262d] rounded-full px-4 py-2 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <span class="text-sm text-gray-600 dark:text-gray-300" x-text="getCallText(msg)"></span>
                                        <span class="text-xs text-gray-400" x-text="formatTime(msg.created_at)"></span>
                                    </div>
                                </div>
                                <!-- Regular message -->
                                <div x-show="!isCallMessage(msg)" :class="msg.sender_type === 'admin' ? 'flex justify-end' : 'flex justify-start'">
                                    <div :class="msg.sender_type === 'admin'
                                            ? 'bg-primary-600 text-white rounded-tl-2xl rounded-tr-2xl rounded-bl-2xl'
                                            : 'bg-gray-100 dark:bg-[#21262d] text-gray-900 dark:text-white rounded-tl-2xl rounded-tr-2xl rounded-br-2xl'"
                                         class="max-w-xs lg:max-w-md px-4 py-2">
                                        <p class="text-sm whitespace-pre-wrap" x-text="msg.message"></p>
                                        <div class="mt-1 flex items-center gap-2 justify-end">
                                            <span :class="msg.sender_type === 'admin' ? 'text-white/70' : 'text-gray-400'"
                                                  class="text-xs" x-text="formatTime(msg.created_at)"></span>
                                            <svg x-show="msg.sender_type === 'admin' && msg.is_read" class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Zone de saisie -->
                    <div class="flex-shrink-0 p-4 border-t border-gray-200 dark:border-[#30363d] bg-white dark:bg-[#161b22]">
                        <form @submit.prevent="sendMessage()" class="flex gap-3">
                            <input type="text" x-model="newMessage" :disabled="sending || selectedConversation?.status === 'closed'"
                                   placeholder="Ecrire un message..."
                                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-xl bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed">
                            <button type="submit" :disabled="!newMessage.trim() || sending || selectedConversation?.status === 'closed'"
                                    class="px-6 py-2 bg-primary-600 text-white rounded-xl hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                <template x-if="sending">
                                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </template>
                                <template x-if="!sending">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                </template>
                                Envoyer
                            </button>
                        </form>
                        <p x-show="selectedConversation?.status === 'closed'" class="mt-2 text-sm text-gray-500 dark:text-gray-400 text-center">
                            Cette conversation est fermee. Le client peut la reouvrir en envoyant un nouveau message.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    <!-- Modal Widget Chat -->
    <div x-show="showWidgetPanel" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="showWidgetPanel = false"></div>
        <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-[#30363d]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Widget Chat Embeddable</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Integrez le chat sur votre portail captif</p>
                    </div>
                </div>
                <button @click="showWidgetPanel = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-[#30363d]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-5 space-y-5">
                <!-- Loading -->
                <template x-if="widgetLoading">
                    <div class="flex justify-center py-12">
                        <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </div>
                </template>

                <!-- Error -->
                <template x-if="!widgetLoading && widgetError">
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 text-sm text-red-700 dark:text-red-400" x-text="widgetError"></div>
                </template>

                <!-- Content -->
                <template x-if="!widgetLoading && !widgetError && widgetEmbedCode">
                    <div class="space-y-5">
                        <!-- Instructions -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div class="text-sm text-blue-800 dark:text-blue-300">
                                    <p class="font-medium mb-1">Instructions</p>
                                    <ol class="list-decimal list-inside space-y-1">
                                        <li>Copiez le code ci-dessous (selectionnez le texte ou cliquez sur "Copier")</li>
                                        <li>Ouvrez le fichier HTML de votre portail captif MikroTik</li>
                                        <li>Collez le code juste avant la balise <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">&lt;/body&gt;</code></li>
                                        <li>Enregistrez et testez votre portail</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Cle du widget -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Cle du Widget</label>
                                <button @click="regenerateWidgetKey()" class="text-xs text-orange-600 dark:text-orange-400 hover:underline flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Regenerer
                                </button>
                            </div>
                            <input type="text" readonly :value="widgetKey" @click="$event.target.select()"
                                   class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#21262d] border border-gray-200 dark:border-[#30363d] rounded-lg font-mono text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        </div>

                        <!-- Code d'integration -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Code d'integration</label>
                                <span x-show="widgetCopied" x-transition class="text-xs text-green-600 dark:text-green-400 font-medium">Copie!</span>
                            </div>
                            <textarea readonly @click="$event.target.select()" x-ref="widgetCodeArea"
                                      :value="widgetEmbedCode" rows="3"
                                      class="w-full px-4 py-3 bg-gray-900 dark:bg-[#0d1117] text-green-400 border border-gray-700 rounded-xl font-mono text-sm resize-none cursor-pointer focus:ring-2 focus:ring-primary-500 focus:outline-none"></textarea>
                            <button @click="$refs.widgetCodeArea.select(); document.execCommand('copy'); widgetCopied = true; setTimeout(() => widgetCopied = false, 2000);"
                                    class="mt-2 w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Copier le code
                            </button>
                        </div>

                        <!-- Exemple -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Exemple dans votre page HTML</label>
                            <div class="bg-gray-900 dark:bg-[#0d1117] rounded-xl p-4 text-sm font-mono overflow-x-auto leading-6">
                                <div class="text-gray-500">&lt;!DOCTYPE html&gt;</div>
                                <div class="text-gray-500">&lt;html&gt;</div>
                                <div class="text-gray-500">&lt;body&gt;</div>
                                <div class="text-gray-500 pl-4">&lt;!-- ... votre portail captif ... --&gt;</div>
                                <div class="pl-4 mt-1"><span class="text-blue-400">&lt;!-- Widget Chat Support --&gt;</span></div>
                                <div class="pl-4 text-green-400 break-all" x-text="widgetEmbedCode"></div>
                                <div class="text-gray-500">&lt;/body&gt;</div>
                                <div class="text-gray-500">&lt;/html&gt;</div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function chatPage() {
    return {
        conversations: [],
        selectedConversation: null,
        messages: [],
        newMessage: '',
        search: '',
        statusFilter: 'active',
        loadingConversations: false,
        loadingMessages: false,
        sending: false,
        pollInterval: null,
        lastMessageId: 0,

        // WebRTC
        callState: 'idle', // idle, calling, incoming, connected
        peerConnection: null,
        localStream: null,
        isMuted: false,
        callDuration: 0,
        callDurationFormatted: '00:00',
        callTimer: null,
        rtcPollInterval: null,
        lastRtcMessageId: 0,
        pendingCandidates: [],
        incomingOffer: null,
        _callHistorySent: false,

        // Widget
        showWidgetPanel: false,
        widgetKey: '',
        widgetEmbedCode: '',
        widgetAppName: '',
        widgetLoading: false,
        widgetError: '',
        widgetCopied: false,

        async init() {
            await this.loadConversations();
            // Polling pour les nouvelles conversations
            setInterval(() => this.loadConversations(), 30000);
        },

        async loadConversations() {
            this.loadingConversations = true;
            try {
                const params = new URLSearchParams({
                    status: this.statusFilter,
                    search: this.search
                });
                const response = await fetch(`api.php?route=/chat/conversations&${params}`);
                const data = await response.json();
                if (data.success && data.data) {
                    this.conversations = data.data.conversations || [];
                }
            } catch (error) {
                console.error('Erreur chargement conversations:', error);
            } finally {
                this.loadingConversations = false;
            }
        },

        async selectConversation(conv) {
            // Nettoyer l'appel en cours si on change de conversation
            if (this.callState !== 'idle') this.endCall();

            this.selectedConversation = conv;
            this.messages = [];
            this.lastMessageId = 0;
            this.lastRtcMessageId = 0;
            await this.loadMessages();
            this.lastRtcMessageId = this.lastMessageId;
            await this.markAsRead();

            // Demarrer le polling pour cette conversation (texte + signaling)
            if (this.pollInterval) clearInterval(this.pollInterval);
            this.pollInterval = setInterval(() => this.pollNewMessages(), 3000);

            // Demarrer le polling WebRTC (plus rapide)
            if (this.rtcPollInterval) clearInterval(this.rtcPollInterval);
            this.rtcPollInterval = setInterval(() => this.pollRtcMessages(), 1000);
        },

        async loadMessages() {
            if (!this.selectedConversation) return;

            this.loadingMessages = true;
            try {
                const response = await fetch(`api.php?route=/chat/conversations/${this.selectedConversation.id}/messages`);
                const data = await response.json();
                if (data.success && data.data) {
                    this.messages = data.data.messages || [];
                    if (this.messages.length > 0) {
                        this.lastMessageId = this.messages[this.messages.length - 1].id;
                    }
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (error) {
                console.error('Erreur chargement messages:', error);
            } finally {
                this.loadingMessages = false;
            }
        },

        async pollNewMessages() {
            if (!this.selectedConversation) return;

            try {
                const response = await fetch(`api.php?route=/chat/conversations/${this.selectedConversation.id}/messages&after_id=${this.lastMessageId}`, {
                    cache: 'no-store'
                });
                const data = await response.json();
                const messages = data.data?.messages || [];
                if (data.success && messages.length > 0) {
                    this.messages.push(...messages);
                    this.lastMessageId = messages[messages.length - 1].id;
                    this.$nextTick(() => this.scrollToBottom());
                    // Marquer comme lu
                    this.markAsRead();
                    // Mettre a jour la liste des conversations
                    this.loadConversations();
                }
            } catch (error) {
                console.error('Erreur polling:', error);
            }
        },

        async sendMessage() {
            if (!this.newMessage.trim() || !this.selectedConversation) {
                console.log('sendMessage blocked:', {newMessage: this.newMessage, selectedConversation: this.selectedConversation});
                return;
            }

            const messageText = this.newMessage.trim();
            this.newMessage = ''; // Vider immédiatement pour feedback
            this.sending = true;

            // Ajouter un message temporaire pour feedback instantané
            const tempId = 'temp_' + Date.now();
            const tempMessage = {
                id: tempId,
                message: messageText,
                sender_type: 'admin',
                admin_name: 'Vous',
                created_at: new Date().toISOString(),
                _pending: true
            };
            this.messages.push(tempMessage);
            console.log('Message temporaire ajouté:', tempMessage, 'Total messages:', this.messages.length);
            this.$nextTick(() => this.scrollToBottom());

            try {
                const response = await fetch(`api.php?route=/chat/conversations/${this.selectedConversation.id}/messages`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: messageText,
                        sender_type: 'admin',
                        admin_id: <?= $_SESSION['admin_id'] ?? 'null' ?>
                    })
                });
                const data = await response.json();
                console.log('Réponse API sendMessage:', data);
                if (data.success && data.data?.message) {
                    // Remplacer le message temporaire par le vrai
                    const idx = this.messages.findIndex(m => m.id === tempId);
                    if (idx !== -1) {
                        this.messages.splice(idx, 1, data.data.message);
                    }
                    this.lastMessageId = data.data.message.id;
                    console.log('Message confirmé, total:', this.messages.length);
                    this.loadConversations();
                } else {
                    // Erreur - retirer le message temporaire
                    this.messages = this.messages.filter(m => m.id !== tempId);
                    this.showNotification('Erreur lors de l\'envoi', 'error');
                }
            } catch (error) {
                console.error('Erreur envoi message:', error);
                this.messages = this.messages.filter(m => m.id !== tempId);
                this.showNotification('Erreur lors de l\'envoi du message', 'error');
            } finally {
                this.sending = false;
            }
        },

        async markAsRead() {
            if (!this.selectedConversation) return;
            try {
                await fetch(`api.php?route=/chat/conversations/${this.selectedConversation.id}/read`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' }
                });
                // Mettre a jour le compteur dans la liste
                const conv = this.conversations.find(c => c.id === this.selectedConversation.id);
                if (conv) conv.unread_admin = 0;
            } catch (error) {
                console.error('Erreur markAsRead:', error);
            }
        },

        async closeConversation() {
            if (!this.selectedConversation || !confirm(__('chat.confirm_close'))) return;

            try {
                const response = await fetch(`api.php?route=/chat/conversations/${this.selectedConversation.id}/close`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.selectedConversation.status = 'closed';
                    this.loadConversations();
                    this.showNotification('Conversation fermee', 'success');
                }
            } catch (error) {
                console.error('Erreur fermeture:', error);
                this.showNotification('Erreur lors de la fermeture', 'error');
            }
        },

        async deleteConversation() {
            if (!this.selectedConversation || !confirm(__('chat.confirm_delete'))) return;

            try {
                const response = await fetch(`api.php?route=/chat/conversations/${this.selectedConversation.id}`, {
                    method: 'DELETE'
                });
                const data = await response.json();
                if (data.success) {
                    this.selectedConversation = null;
                    this.messages = [];
                    if (this.pollInterval) clearInterval(this.pollInterval);
                    this.loadConversations();
                    this.showNotification('Conversation supprimee', 'success');
                }
            } catch (error) {
                console.error('Erreur suppression:', error);
                this.showNotification('Erreur lors de la suppression', 'error');
            }
        },

        scrollToBottom() {
            const container = document.getElementById('adminMessagesContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            const now = new Date();
            const diff = now - date;

            if (diff < 60000) return 'Maintenant';
            if (diff < 3600000) return `${Math.floor(diff / 60000)}m`;
            if (diff < 86400000) return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
        },

        showNotification(message, type = 'info') {
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type);
            }
        },

        // ==========================================
        // WebRTC Audio Call
        // ==========================================

        getRtcConfig() {
            return {
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' }
                ]
            };
        },

        async sendRtcSignal(action, payload = {}) {
            if (!this.selectedConversation) return;
            const data = JSON.stringify({ action, ...payload });
            await fetch(`api.php?route=/chat/conversations/${this.selectedConversation.id}/messages`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: data,
                    sender_type: 'admin',
                    admin_id: <?= $_SESSION['admin_id'] ?? 'null' ?>,
                    message_type: 'webrtc'
                })
            });
        },

        async pollRtcMessages() {
            if (!this.selectedConversation) return;
            try {
                const response = await fetch(
                    `api.php?route=/chat/conversations/${this.selectedConversation.id}/messages&after_id=${this.lastRtcMessageId}&type=webrtc`,
                    { cache: 'no-store' }
                );
                const data = await response.json();
                const messages = data.data?.messages || [];
                for (const msg of messages) {
                    this.lastRtcMessageId = Math.max(this.lastRtcMessageId, parseInt(msg.id));
                    // Ignorer les signaux de plus de 30 secondes
                    const msgAge = (Date.now() - new Date(msg.created_at).getTime()) / 1000;
                    if (msgAge > 30) continue;
                    // Only process messages from customer
                    if (msg.sender_type === 'customer') {
                        try {
                            const signal = JSON.parse(msg.message);
                            await this.handleRtcSignal(signal);
                        } catch (e) { /* ignore parse errors */ }
                    }
                }
            } catch (e) { /* ignore poll errors */ }
        },

        async handleRtcSignal(signal) {
            switch (signal.action) {
                case 'call_offer':
                    if (this.callState !== 'idle') break;
                    this.incomingOffer = signal.sdp;
                    this.callState = 'incoming';
                    break;
                case 'call_answer':
                    if (this.peerConnection && this.callState === 'calling') {
                        await this.peerConnection.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: signal.sdp }));
                        this.pendingCandidates.forEach(c => {
                            try { this.peerConnection.addIceCandidate(new RTCIceCandidate(c)); } catch(e) {}
                        });
                        this.pendingCandidates = [];
                        // State transition to 'connected' is handled by onconnectionstatechange
                        // But set a fallback in case the event fires before this point
                        if (this.peerConnection?.connectionState === 'connected') {
                            this.callState = 'connected';
                            this.startCallTimer();
                        }
                    }
                    break;
                case 'ice_candidate':
                    if (this.peerConnection && this.peerConnection.remoteDescription) {
                        try { await this.peerConnection.addIceCandidate(new RTCIceCandidate(signal.candidate)); } catch(e) {}
                    } else {
                        this.pendingCandidates.push(signal.candidate);
                    }
                    break;
                case 'call_reject':
                    await this.sendCallHistory('rejected', 0);
                    this.cleanupCall();
                    this.showNotification('Appel refuse par le client', 'info');
                    break;
                case 'call_end':
                    await this.sendCallHistory(
                        this.callState === 'connected' ? 'completed' : 'missed',
                        this.callDuration
                    );
                    this.cleanupCall();
                    this.showNotification('Appel termine', 'info');
                    break;
            }
        },

        async startCall() {
            if (this.callState !== 'idle' || !this.selectedConversation) return;
            this.callState = 'calling';
            this._callHistorySent = false;

            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
                this.peerConnection = new RTCPeerConnection(this.getRtcConfig());

                this.localStream.getTracks().forEach(track => {
                    this.peerConnection.addTrack(track, this.localStream);
                });

                this.peerConnection.ontrack = (event) => {
                    const audio = document.getElementById('remoteAudio');
                    if (audio) audio.srcObject = event.streams[0];
                };

                this.peerConnection.onicecandidate = (event) => {
                    if (event.candidate) {
                        this.sendRtcSignal('ice_candidate', { candidate: event.candidate.toJSON() });
                    }
                };

                this.peerConnection.onconnectionstatechange = () => {
                    const state = this.peerConnection?.connectionState;
                    console.log('[Chat] connectionState:', state, 'callState:', this.callState);
                    if (state === 'connected' && this.callState === 'calling') {
                        this.callState = 'connected';
                        this.startCallTimer();
                    } else if (state === 'failed') {
                        this.sendCallHistory('failed', this.callDuration);
                        this.cleanupCall();
                    } else if (state === 'closed' && this.callState === 'connected') {
                        this.sendCallHistory('completed', this.callDuration);
                        this.cleanupCall();
                    }
                    // 'disconnected' is transient — ignore it
                };

                const offer = await this.peerConnection.createOffer();
                await this.peerConnection.setLocalDescription(offer);
                await this.sendRtcSignal('call_offer', { sdp: offer.sdp });

            } catch (error) {
                console.error('Erreur demarrage appel:', error);
                this.cleanupCall();
                this.showNotification('Impossible de demarrer l\'appel. Verifiez les permissions du microphone.', 'error');
            }
        },

        async acceptCall() {
            if (this.callState !== 'incoming' || !this.incomingOffer) return;
            this._callHistorySent = false;

            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
                this.peerConnection = new RTCPeerConnection(this.getRtcConfig());

                this.localStream.getTracks().forEach(track => {
                    this.peerConnection.addTrack(track, this.localStream);
                });

                this.peerConnection.ontrack = (event) => {
                    const audio = document.getElementById('remoteAudio');
                    if (audio) audio.srcObject = event.streams[0];
                };

                this.peerConnection.onicecandidate = (event) => {
                    if (event.candidate) {
                        this.sendRtcSignal('ice_candidate', { candidate: event.candidate.toJSON() });
                    }
                };

                this.peerConnection.onconnectionstatechange = () => {
                    const state = this.peerConnection?.connectionState;
                    console.log('[Chat] acceptCall connectionState:', state, 'callState:', this.callState);
                    if (state === 'connected' && this.callState !== 'connected') {
                        this.callState = 'connected';
                        this.incomingOffer = null;
                        this.startCallTimer();
                    } else if (state === 'failed') {
                        this.sendCallHistory('failed', this.callDuration);
                        this.cleanupCall();
                    } else if (state === 'closed' && this.callState === 'connected') {
                        this.sendCallHistory('completed', this.callDuration);
                        this.cleanupCall();
                    }
                    // 'disconnected' is transient — ignore it
                };

                await this.peerConnection.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.incomingOffer }));

                // Apply pending ICE candidates
                this.pendingCandidates.forEach(c => {
                    try { this.peerConnection.addIceCandidate(new RTCIceCandidate(c)); } catch(e) {}
                });
                this.pendingCandidates = [];

                const answer = await this.peerConnection.createAnswer();
                await this.peerConnection.setLocalDescription(answer);
                await this.sendRtcSignal('call_answer', { sdp: answer.sdp });

                // Let onconnectionstatechange handle the transition to 'connected'
                // But set a fallback if ICE already connected
                if (this.peerConnection?.connectionState === 'connected') {
                    this.callState = 'connected';
                    this.incomingOffer = null;
                    this.startCallTimer();
                }

            } catch (error) {
                console.error('Erreur acceptation appel:', error);
                this.cleanupCall();
                this.showNotification('Erreur lors de l\'acceptation de l\'appel', 'error');
            }
        },

        async rejectCall() {
            await this.sendRtcSignal('call_reject');
            this.cleanupCall();
        },

        async endCall() {
            if (this.callState !== 'idle') {
                const status = this.callState === 'connected' ? 'completed' : 'missed';
                const duration = this.callDuration;
                await this.sendRtcSignal('call_end');
                await this.sendCallHistory(status, duration);
            }
            this.cleanupCall();
        },

        toggleMute() {
            if (this.localStream) {
                this.isMuted = !this.isMuted;
                this.localStream.getAudioTracks().forEach(track => {
                    track.enabled = !this.isMuted;
                });
            }
        },

        startCallTimer() {
            this.callDuration = 0;
            this.callDurationFormatted = '00:00';
            if (this.callTimer) clearInterval(this.callTimer);
            this.callTimer = setInterval(() => {
                this.callDuration++;
                const m = String(Math.floor(this.callDuration / 60)).padStart(2, '0');
                const s = String(this.callDuration % 60).padStart(2, '0');
                this.callDurationFormatted = `${m}:${s}`;
            }, 1000);
        },

        async sendCallHistory(status, duration) {
            if (!this.selectedConversation || this._callHistorySent) return;
            this._callHistorySent = true;

            const callData = JSON.stringify({
                _type: 'call',
                status: status,
                duration: duration || 0
            });

            try {
                const response = await fetch(`api.php?route=/chat/conversations/${this.selectedConversation.id}/messages`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: callData,
                        sender_type: 'admin',
                        admin_id: <?= $_SESSION['admin_id'] ?? 'null' ?>
                    })
                });
                const data = await response.json();
                if (data.success && data.data?.message) {
                    this.messages.push(data.data.message);
                    this.lastMessageId = data.data.message.id;
                    this.$nextTick(() => this.scrollToBottom());
                    this.loadConversations();
                }
            } catch (e) {
                console.error('Erreur envoi historique appel:', e);
            }
        },

        formatLastMessage(message) {
            if (!message) return 'Nouvelle conversation';
            try {
                const data = JSON.parse(message);
                if (data._type === 'call') {
                    const d = data.duration || 0;
                    const m = String(Math.floor(d / 60)).padStart(2, '0');
                    const s = String(d % 60).padStart(2, '0');
                    switch (data.status) {
                        case 'completed': return `Appel vocal - ${m}:${s}`;
                        case 'rejected': return 'Appel refuse';
                        case 'missed': return 'Appel manque';
                        case 'failed': return 'Appel echoue';
                        default: return 'Appel';
                    }
                }
            } catch {}
            return message;
        },

        isCallMessage(msg) {
            try {
                return JSON.parse(msg.message)?._type === 'call';
            } catch { return false; }
        },

        getCallText(msg) {
            try {
                const data = JSON.parse(msg.message);
                if (data._type !== 'call') return msg.message;
                const d = data.duration || 0;
                const m = String(Math.floor(d / 60)).padStart(2, '0');
                const s = String(d % 60).padStart(2, '0');
                switch (data.status) {
                    case 'completed': return `Appel vocal - ${m}:${s}`;
                    case 'rejected': return 'Appel refuse';
                    case 'missed': return 'Appel manque';
                    case 'failed': return 'Appel echoue';
                    default: return 'Appel';
                }
            } catch { return msg.message; }
        },

        cleanupCall() {
            if (this.peerConnection) {
                this.peerConnection.close();
                this.peerConnection = null;
            }
            if (this.localStream) {
                this.localStream.getTracks().forEach(t => t.stop());
                this.localStream = null;
            }
            const audio = document.getElementById('remoteAudio');
            if (audio) audio.srcObject = null;
            if (this.callTimer) { clearInterval(this.callTimer); this.callTimer = null; }
            this.callState = 'idle';
            this.isMuted = false;
            this.callDuration = 0;
            this.callDurationFormatted = '00:00';
            this.incomingOffer = null;
            this.pendingCandidates = [];
        },

        // ==========================================
        // Widget Chat Embeddable
        // ==========================================

        async loadWidgetCode() {
            this.widgetLoading = true;
            this.widgetError = '';
            this.widgetCopied = false;
            try {
                const response = await fetch('api.php?route=/chat/widget/code');
                const data = await response.json();
                if (data.success && data.data) {
                    this.widgetKey = data.data.key || '';
                    this.widgetEmbedCode = data.data.embed_code || '';
                    this.widgetError = '';
                } else {
                    this.widgetError = data.message || 'Erreur lors du chargement du widget';
                }
            } catch (error) {
                console.error('Erreur chargement widget code:', error);
                this.widgetError = 'Erreur de connexion au serveur';
            } finally {
                this.widgetLoading = false;
            }
        },

        async regenerateWidgetKey() {
            if (!confirm(__('chat.confirm_regenerate_key'))) return;

            this.widgetLoading = true;
            try {
                const response = await fetch('api.php?route=/chat/widget/key', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await response.json();
                if (data.success && data.data) {
                    this.widgetKey = data.data.key;
                    // Reload the full code with new key
                    await this.loadWidgetCode();
                    this.showNotification('Cle widget regeneree avec succes', 'success');
                }
            } catch (error) {
                console.error('Erreur regeneration cle:', error);
                this.showNotification('Erreur lors de la regeneration', 'error');
            } finally {
                this.widgetLoading = false;
            }
        },

        copyToClipboard(text) {
            if (!text) return;
            const fallback = () => {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px;opacity:0';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                try { document.execCommand('copy'); } catch(e) {}
                document.body.removeChild(ta);
                this.showNotification('Copie dans le presse-papier', 'success');
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showNotification('Copie dans le presse-papier', 'success');
                }).catch(fallback);
            } else {
                fallback();
            }
        }
    };
}
</script>
