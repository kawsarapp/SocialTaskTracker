<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Real-Time Toaster Notifications (Alpine.js) -->
        <div x-data="notificationEngine()" 
             x-init="initEngine()"
             class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-3 max-w-sm w-full pointer-events-none">
            
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="toast.visible"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="translate-y-2 opacity-0 scale-95"
                     x-transition:enter-end="translate-y-0 opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-800 p-4 pointer-events-auto flex flex-col gap-2 relative">
                    
                    <button @click="hideToast(toast.id)" class="absolute top-2 right-2 text-slate-400 hover:text-white font-bold text-lg focus:outline-none">&times;</button>
                    
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-500 text-white" x-text="toast.user_role"></span>
                        <span class="text-xs text-slate-400" x-text="toast.user_name"></span>
                    </div>
                    
                    <p class="text-xs font-semibold text-indigo-300 truncate" x-text="'Task: ' + toast.task_title"></p>
                    <p class="text-sm text-slate-200" x-text="toast.content"></p>
                    
                    <div class="flex justify-between items-center mt-1 text-[11px]">
                        <span class="text-slate-500" x-text="toast.created_at_human"></span>
                        <a :href="toast.edit_url" class="text-indigo-400 hover:underline font-bold">View Task &rarr;</a>
                    </div>
                </div>
            </template>
        </div>

        <script>
            function notificationEngine() {
                return {
                    toasts: [],
                    processedCommentIds: new Set(),
                    lastCommentId: 0,
                    tenantId: {{ Auth::check() ? (Auth::user()->tenant_id ?? 0) : 0 }},
                    currentTaskId: {{ isset($task) ? $task->id : 'null' }},

                    initEngine() {
                        if (!this.tenantId) return;

                        // 1. Initialize Reverb (Laravel Echo) listener if available
                        if (window.Echo) {
                            window.Echo.private('tenant.' + this.tenantId)
                                .listen('.comment.posted', (e) => {
                                    console.log('Reverb Broadcast Received:', e);
                                    this.handleNewComment(e.commentData);
                                });
                        }

                        // 2. Dynamic Polling Fallback (runs every 15 seconds)
                        setInterval(() => {
                            this.pollComments();
                        }, 15000);

                        // Initial poll to set starting lastCommentId so we don't display old historic comments on page load
                        this.pollComments(true);
                    },

                    pollComments(isInitial = false) {
                        fetch('{{ route("tasks.comments.poll") }}?last_id=' + this.lastCommentId)
                            .then(response => response.json())
                            .then(data => {
                                if (data && data.length > 0) {
                                    data.forEach(comment => {
                                        if (isInitial) {
                                            // On page load, just seed the Set and set lastCommentId
                                            this.processedCommentIds.add(comment.id);
                                            if (comment.id > this.lastCommentId) {
                                                this.lastCommentId = comment.id;
                                            }
                                        } else {
                                            // Regular poll execution
                                            this.handleNewComment(comment);
                                        }
                                    });
                                }
                            })
                            .catch(err => console.error('Polling Error:', err));
                    },

                    handleNewComment(comment) {
                        // Prevent double processing (Reverb vs Polling fallback race condition)
                        if (this.processedCommentIds.has(comment.id)) {
                            return;
                        }
                        this.processedCommentIds.add(comment.id);
                        if (comment.id > this.lastCommentId) {
                            this.lastCommentId = comment.id;
                        }

                        // 1. Show floating toaster if comment is written by another user
                        this.showToast(comment);

                        // 2. Dynamically update count badge on Tasks Listing table
                        const countBadge = document.getElementById('comment-badge-count-' + comment.task_id);
                        if (countBadge) {
                            const currentCount = parseInt(countBadge.innerText) || 0;
                            countBadge.innerText = currentCount + 1;
                            
                            // Add nice subtle zoom scale micro-animation
                            const parentBadge = countBadge.closest('a');
                            if (parentBadge) {
                                parentBadge.classList.add('scale-110', 'bg-indigo-100');
                                setTimeout(() => parentBadge.classList.remove('scale-110', 'bg-indigo-100'), 1500);
                            }
                        } else {
                            // Badge doesn't exist, create it inside the container
                            const badgeContainer = document.getElementById('comment-badge-container-' + comment.task_id);
                            if (badgeContainer) {
                                badgeContainer.innerHTML = `
                                    <a href="${comment.edit_url}#comments-loop" class="inline-flex items-center gap-0.5 bg-indigo-50 border border-indigo-200 text-indigo-700 px-1.5 py-0.5 rounded text-[10px] font-bold hover:bg-indigo-100 transition shadow-sm animate-bounce" title="View feedback and comments">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                        <span id="comment-badge-count-${comment.task_id}">1</span>
                                    </a>
                                `;
                            }
                        }

                        // 3. Dynamically append comment on Task Edit view if user is currently viewing this task
                        if (this.currentTaskId === comment.task_id) {
                            const listContainer = document.getElementById('comments-list-container');
                            if (listContainer) {
                                // Hide "No comments yet" placeholder if active
                                const placeholder = document.getElementById('no-comments-placeholder');
                                if (placeholder) {
                                    placeholder.style.display = 'none';
                                }

                                // Build comment element
                                const commentDiv = document.createElement('div');
                                commentDiv.className = "bg-slate-50 p-4 rounded-xl border border-slate-100 flex flex-col gap-2 animate-fade-in";
                                
                                const roleColor = comment.user_role === 'admin' ? 'bg-purple-100 text-purple-800' : (comment.user_role === 'team_leader' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800');
                                const capitalizedRole = comment.user_role.charAt(0).toUpperCase() + comment.user_role.slice(1);

                                commentDiv.innerHTML = `
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-800 text-sm">${comment.user_name}</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold ${roleColor}">
                                                ${capitalizedRole}
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-400 font-semibold">${comment.created_at_human}</span>
                                    </div>
                                    <p class="text-sm text-gray-700 whitespace-pre-line">${comment.content}</p>
                                `;

                                // Append to top of the list (since we sort descending)
                                listContainer.insertBefore(commentDiv, listContainer.firstChild);
                            }
                        }
                    },

                    showToast(comment) {
                        const toast = {
                            id: comment.id,
                            user_name: comment.user_name,
                            user_role: comment.user_role.toUpperCase(),
                            task_title: comment.task_title,
                            content: comment.content.length > 80 ? comment.content.substring(0, 80) + '...' : comment.content,
                            created_at_human: comment.created_at_human,
                            edit_url: comment.edit_url,
                            visible: true
                        };

                        this.toasts.push(toast);

                        // Auto hide after 8 seconds
                        setTimeout(() => {
                            this.hideToast(toast.id);
                        }, 8000);
                    },

                    hideToast(id) {
                        const idx = this.toasts.findIndex(t => t.id === id);
                        if (idx !== -1) {
                            this.toasts[idx].visible = false;
                            // Clean up array after animation
                            setTimeout(() => {
                                this.toasts = this.toasts.filter(t => t.id !== id);
                            }, 500);
                        }
                    }
                };
            }
        </script>
    </body>
</html>
