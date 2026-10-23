<!-- ========================================================================= -->
<!-- LIVE SOCIAL API & TOKEN DIAGNOSTIC MODAL (FACEBOOK & YOUTUBE)             -->
<!-- ========================================================================= -->
<div x-show="socialTestModalOpen"
     style="display: none;"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click.self="socialTestModalOpen = false"
     @keydown.escape.window="if (socialTestModalOpen) { socialTestModalOpen = false; $event.stopPropagation(); }"
     class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6"
     x-cloak>
    
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full border border-gray-100 overflow-hidden relative flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b flex items-center justify-between"
             :class="socialTestPlatform === 'facebook' ? 'bg-gradient-to-r from-blue-900 to-indigo-800 text-white border-blue-900' : 'bg-gradient-to-r from-rose-900 to-red-800 text-white border-rose-900'">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center text-xl shrink-0">
                    <span x-show="socialTestPlatform === 'facebook'">📘</span>
                    <span x-show="socialTestPlatform === 'youtube'">▶️</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-base tracking-tight"
                        x-text="socialTestPlatform === 'facebook' ? 'Meta Facebook Connection & Permission Diagnostic' : 'Google YouTube Data API v3 Diagnostic'"></h3>
                    <p class="text-xs text-white/80"
                       x-text="socialTestPlatform === 'facebook' ? 'Live Meta Graph API check: token status, page bindings, and publish permissions' : 'Live Google API check: key validity, channel stats, and daily quota'"></p>
                </div>
            </div>
            <button type="button" @click="socialTestModalOpen = false" class="text-white/70 hover:text-white text-2xl font-bold p-1 cursor-pointer transition leading-none">
                &times;
            </button>
        </div>

        <!-- Modal Scrollable Body -->
        <div class="p-6 overflow-y-auto space-y-5 flex-1">
            
            <!-- Loading State -->
            <div x-show="socialTestLoading" class="py-12 flex flex-col items-center justify-center text-center space-y-4">
                <div class="relative">
                    <div class="w-14 h-14 border-4 border-gray-200 border-t-indigo-600 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-lg">
                        <span x-show="socialTestPlatform === 'facebook'">📘</span>
                        <span x-show="socialTestPlatform === 'youtube'">▶️</span>
                    </div>
                </div>
                <div>
                    <p class="font-bold text-gray-800 text-sm">
                        Testing live API connection...
                    </p>
                    <p class="text-xs text-gray-500 mt-1 max-w-sm"
                       x-text="socialTestPlatform === 'facebook' ? 'Inspecting Facebook Page Access Token and querying granted permissions on graph.facebook.com...' : 'Sending verification query to Google YouTube Data API v3 on www.googleapis.com...'"></p>
                </div>
            </div>

            <!-- Results State -->
            <div x-show="!socialTestLoading && socialTestResult" class="space-y-4">
                
                <!-- Status Banner: Success / Partial -->
                <template x-if="socialTestResult && socialTestResult.success">
                    <div class="p-4 rounded-xl border flex items-start gap-3"
                         :class="socialTestResult.status === 'healthy' ? 'bg-emerald-50/80 border-emerald-200 text-emerald-900' : 'bg-amber-50/80 border-amber-200 text-amber-900'">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-lg shrink-0"
                             :class="socialTestResult.status === 'healthy' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                            <span x-show="socialTestResult.status === 'healthy'">✅</span>
                            <span x-show="socialTestResult.status !== 'healthy'">⚠️</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="font-bold text-sm"
                                    x-text="socialTestResult.status === 'healthy' ? 'API Connection Healthy & Verified' : (socialTestResult.token_type === 'user_token' ? '⚠️ User Access Token Detected (Page Token Required for Publishing)' : 'Connected with Missing Permissions')"></h4>
                                <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded-full"
                                      :class="socialTestResult.status === 'healthy' ? 'bg-emerald-200 text-emerald-800' : 'bg-amber-200 text-amber-800'"
                                      x-text="socialTestResult.status === 'healthy' ? 'Active' : (socialTestResult.token_type === 'user_token' ? 'Page Token Required' : 'Attention Needed')"></span>
                            </div>
                            <p class="text-xs mt-1 leading-relaxed font-medium" x-text="socialTestResult.summary_message"></p>
                        </div>
                    </div>
                </template>

                <!-- User Access Token Dedicated Warning & Step-by-Step Guidance Banner -->
                <template x-if="socialTestResult && socialTestResult.token_type === 'user_token'">
                    <div class="p-4 rounded-xl bg-amber-50/90 border-2 border-amber-300 text-amber-950 space-y-3.5 shadow-xs">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-200 text-amber-800 flex items-center justify-center text-xl shrink-0 font-bold">
                                ⚠️
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-extrabold text-sm text-amber-900">
                                        ⚠️ User Access Token Detected (Page Token Required for Publishing)
                                    </h4>
                                    <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase bg-amber-200 text-amber-900 rounded-full">
                                        Action Required
                                    </span>
                                </div>
                                <p class="text-xs mt-1 text-amber-900/95 leading-relaxed font-medium">
                                    আপনি যে টোকেনটি দিয়েছেন এটি একটি <strong>User Access Token</strong> (ব্যবহারকারীর ব্যক্তিগত টোকেন)। ফেসবুক পেজে সরাসরি ভিডিও/রিলস ও পোস্ট পাবলিশ করার জন্য Meta পলিসি অনুযায়ী <strong>Page Access Token</strong> প্রয়োজন।
                                </p>
                                <p class="text-[11px] text-amber-800 mt-0.5">
                                    The token provided is an account user token. Meta Graph API requires a dedicated <strong>Page Access Token</strong> to create feed posts and upload video reels to your page.
                                </p>
                            </div>
                        </div>

                        <!-- Step-by-Step Guidance Box -->
                        <div class="bg-white p-3.5 rounded-lg border border-amber-200 text-xs space-y-2">
                            <span class="font-bold text-amber-950 flex items-center gap-1.5 text-xs">
                                <span>📋</span>
                                <span>কীভাবে Page Access Token পাবেন (How to get Page Access Token):</span>
                            </span>
                            <ol class="list-decimal list-inside space-y-1.5 text-slate-700 font-medium text-[11.5px] leading-relaxed">
                                <li>
                                    <span>Meta Graph API Explorer-এ যান: </span>
                                    <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-800 hover:underline font-bold inline-flex items-center gap-0.5">
                                        developers.facebook.com/tools/explorer
                                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                    </a>
                                </li>
                                <li>
                                    ডানপাশের <strong>"User or Page"</strong> ড্রপডাউনে "User Token" এর জায়গায় আপনার <strong>Facebook Page</strong> সিলেক্ট করুন।
                                </li>
                                <li>
                                    <strong>"Generate Access Token"</strong> এ ক্লিক করে Page Access Token কপি করে সেটিংস পেজে পেস্ট করুন।
                                </li>
                            </ol>
                        </div>

                        <!-- Detected Managed Pages List -->
                        <template x-if="socialTestResult.pages && socialTestResult.pages.length > 0">
                            <div class="space-y-2 pt-1">
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-amber-900 flex items-center gap-1">
                                    <span>📄</span>
                                    <span>Detected Facebook Pages Managed by this Account (<span x-text="socialTestResult.pages.length"></span>)</span>
                                </span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <template x-for="page in socialTestResult.pages" :key="page.id">
                                        <div class="bg-white p-2.5 rounded-lg border border-amber-200 flex items-center justify-between gap-2 shadow-2xs">
                                            <div class="min-w-0">
                                                <span class="font-bold text-xs text-slate-900 truncate block" x-text="page.name"></span>
                                                <span class="text-[10px] text-gray-500 font-mono block" x-text="'ID: ' + page.id"></span>
                                            </div>
                                            <span class="px-2 py-0.5 text-[9px] font-bold bg-blue-50 text-blue-700 rounded border border-blue-200 shrink-0" x-text="page.category || 'Page'"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <template x-if="!socialTestResult.pages || socialTestResult.pages.length === 0">
                            <p class="text-[11px] text-amber-800 italic bg-amber-100/60 p-2.5 rounded-lg border border-amber-200">
                                No Facebook Pages found under this user account, or 'pages_show_list' permission was not granted. Ensure your Facebook account has admin/editor access to your Page in Meta Business Suite.
                            </p>
                        </template>
                    </div>
                </template>

                <!-- Status Banner: Failure / Error -->
                <template x-if="socialTestResult && !socialTestResult.success">
                    <div class="p-4 rounded-xl bg-rose-50/90 border border-rose-200 text-rose-900 flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center text-lg shrink-0">
                            ❌
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="font-bold text-sm text-rose-800" x-text="socialTestResult.error || 'Connection Failed'"></h4>
                                <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase bg-rose-200 text-rose-800 rounded-full">Error</span>
                            </div>
                            <template x-if="socialTestResult.raw_message">
                                <p class="text-xs text-rose-700 mt-1 font-mono bg-white/70 p-2 rounded border border-rose-200" x-text="socialTestResult.raw_message"></p>
                            </template>
                            
                            <!-- Troubleshooting Guide -->
                            <div class="mt-3 p-3 bg-white rounded-lg border border-rose-200 shadow-xs">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-800 flex items-center gap-1.5 mb-1">
                                    <span>💡 How to resolve this issue:</span>
                                </span>
                                <p class="text-xs text-slate-700 leading-relaxed font-medium whitespace-pre-line" x-text="socialTestResult.troubleshooting"></p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Facebook Identity Details Card (Page Token) -->
                <template x-if="socialTestResult && socialTestPlatform === 'facebook' && socialTestResult.account_name && socialTestResult.token_type !== 'user_token'">
                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Page Name</span>
                            <span class="font-bold text-slate-800 truncate block" x-text="socialTestResult.account_name"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Page ID</span>
                            <span class="font-mono text-slate-700 truncate block" x-text="socialTestResult.account_id || 'N/A'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Category</span>
                            <span class="text-slate-700 truncate block" x-text="socialTestResult.category || 'General'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Page Status</span>
                            <span class="font-semibold text-emerald-600 block" x-text="socialTestResult.is_published ? 'Published (Live)' : 'Unpublished'"></span>
                        </div>
                    </div>
                </template>

                <!-- Facebook Identity Details Card (User Token) -->
                <template x-if="socialTestResult && socialTestPlatform === 'facebook' && socialTestResult.token_type === 'user_token'">
                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Facebook User Account</span>
                            <span class="font-bold text-slate-800 truncate block" x-text="socialTestResult.account_name"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">User ID</span>
                            <span class="font-mono text-slate-700 truncate block" x-text="socialTestResult.account_id || 'N/A'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Token Type</span>
                            <span class="font-bold text-amber-700 block">User Access Token</span>
                        </div>
                    </div>
                </template>

                <!-- YouTube Identity Details Card -->
                <template x-if="socialTestResult && socialTestPlatform === 'youtube' && socialTestResult.success">
                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 text-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-base">▶️</span>
                                <div>
                                    <span class="font-bold text-slate-800 block" x-text="socialTestResult.channel_title"></span>
                                    <span x-show="socialTestResult.channel_id" class="text-[10px] text-gray-500 font-mono" x-text="'ID: ' + socialTestResult.channel_id"></span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-100 text-emerald-800">API Key Verified</span>
                        </div>
                        <template x-if="socialTestResult.subscriber_count || socialTestResult.video_count || socialTestResult.view_count">
                            <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-200 text-center">
                                <div class="bg-white p-2 rounded border border-gray-100">
                                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Subscribers</span>
                                    <span class="font-extrabold text-slate-800" x-text="socialTestResult.subscriber_count || 'Hidden'"></span>
                                </div>
                                <div class="bg-white p-2 rounded border border-gray-100">
                                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Total Videos</span>
                                    <span class="font-extrabold text-slate-800" x-text="socialTestResult.video_count || '0'"></span>
                                </div>
                                <div class="bg-white p-2 rounded border border-gray-100">
                                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Total Views</span>
                                    <span class="font-extrabold text-slate-800" x-text="socialTestResult.view_count || '0'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Facebook Permissions Diagnostic Section -->
                <template x-if="socialTestResult && socialTestPlatform === 'facebook' && socialTestResult.success">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h5 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                <span>🔐 Facebook Token Permissions Breakdown</span>
                            </h5>
                            <span class="text-[11px] font-semibold text-gray-500"
                                  x-text="(Object.keys(socialTestResult.granted_details || {}).length) + ' / 5 Recommended Permissions Active'"></span>
                        </div>

                        <!-- Missing Permissions List (Prominent Warning) -->
                        <template x-if="socialTestResult.missing_permissions && Object.keys(socialTestResult.missing_permissions).length > 0">
                            <div class="space-y-2">
                                <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1">
                                    <span>⚠️ Missing Recommended Permissions</span>
                                </div>
                                <template x-for="(item, permKey) in socialTestResult.missing_permissions" :key="permKey">
                                    <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-xs text-amber-900 flex items-center gap-1.5">
                                                <span class="text-amber-600">❌</span>
                                                <span x-text="item.title || permKey"></span>
                                            </span>
                                            <span class="px-2 py-0.5 text-[9px] font-mono font-bold bg-amber-200/70 text-amber-900 rounded" x-text="permKey"></span>
                                        </div>
                                        <p class="text-[11px] text-slate-600" x-text="item.description"></p>
                                        <p class="text-[11px] font-medium text-amber-900 bg-white/70 p-1.5 rounded border border-amber-200 mt-1" x-text="'💡 Action: ' + item.advice"></p>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Granted Permissions List -->
                        <div class="space-y-2">
                            <div class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider flex items-center gap-1">
                                <span>✅ Granted &amp; Active Permissions</span>
                            </div>
                            <template x-if="socialTestResult.granted_details && Object.keys(socialTestResult.granted_details).length > 0">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <template x-for="(item, permKey) in socialTestResult.granted_details" :key="permKey">
                                        <div class="p-2.5 bg-emerald-50/60 border border-emerald-200/80 rounded-xl">
                                            <div class="flex items-center justify-between mb-0.5">
                                                <span class="font-bold text-xs text-emerald-900 flex items-center gap-1">
                                                    <span>✓</span>
                                                    <span x-text="item.title || permKey"></span>
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-emerald-700 block font-mono" x-text="permKey"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!socialTestResult.granted_details || Object.keys(socialTestResult.granted_details).length === 0">
                                <p class="text-xs text-gray-500 italic p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    No granular page permissions found in token response. Token has basic Page access only.
                                </p>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Feature Readiness Matrix -->
                <template x-if="socialTestResult && socialTestResult.capabilities">
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5">
                        <h5 class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                            ⚡ Feature Readiness Matrix
                        </h5>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                            <!-- 1. Video & Reel Upload -->
                            <div class="bg-white p-2.5 rounded-lg border border-gray-200 flex flex-col justify-between">
                                <div class="font-bold text-slate-800 mb-1 flex items-center gap-1">
                                    <span>🎬 Video &amp; Reels</span>
                                </div>
                                <div class="mt-1">
                                    <template x-if="socialTestPlatform === 'facebook'">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold"
                                              :class="socialTestResult.capabilities.can_publish_reels ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">
                                            <span x-text="socialTestResult.capabilities.can_publish_reels ? '🟢 Ready to Publish' : (socialTestResult.token_type === 'user_token' ? '🔴 Needs Page Token' : '🔴 Missing Scopes')"></span>
                                        </span>
                                    </template>
                                    <template x-if="socialTestPlatform === 'youtube'">
                                        <span class="text-[10px] text-slate-500 leading-tight block">
                                            Requires OAuth2 user upload scope
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- 2. Post Publishing -->
                            <div class="bg-white p-2.5 rounded-lg border border-gray-200 flex flex-col justify-between">
                                <div class="font-bold text-slate-800 mb-1 flex items-center gap-1">
                                    <span>📝 Feed Posts</span>
                                </div>
                                <div class="mt-1">
                                    <template x-if="socialTestPlatform === 'facebook'">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold"
                                              :class="socialTestResult.capabilities.can_publish_posts ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">
                                            <span x-text="socialTestResult.capabilities.can_publish_posts ? '🟢 Ready to Publish' : (socialTestResult.token_type === 'user_token' ? '🔴 Needs Page Token' : '🔴 Missing Scopes')"></span>
                                        </span>
                                    </template>
                                    <template x-if="socialTestPlatform === 'youtube'">
                                        <span class="text-[10px] text-slate-400 block">—</span>
                                    </template>
                                </div>
                            </div>

                            <!-- 3. Metrics & Analytics Sync -->
                            <div class="bg-white p-2.5 rounded-lg border border-gray-200 flex flex-col justify-between">
                                <div class="font-bold text-slate-800 mb-1 flex items-center gap-1">
                                    <span>📊 Metrics Sync</span>
                                </div>
                                <div class="mt-1">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold"
                                          :class="(socialTestResult.capabilities.can_sync_analytics || socialTestResult.capabilities.can_read_metrics) ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">
                                        <span x-text="(socialTestResult.capabilities.can_sync_analytics || socialTestResult.capabilities.can_read_metrics) ? '🟢 Ready to Sync' : '🔴 Needs Permissions'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Direct upload informative note (for YouTube) -->
                        <template x-if="socialTestPlatform === 'youtube' && socialTestResult.direct_upload_note">
                            <p class="text-[11px] text-slate-600 bg-white p-2 rounded border border-gray-100 leading-relaxed font-medium"
                               x-text="'ℹ️ ' + socialTestResult.direct_upload_note"></p>
                        </template>
                    </div>
                </template>

            </div>

        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-2 shrink-0">
            <div>
                <button type="button"
                        x-show="!socialTestLoading"
                        @click="runSocialTest(socialTestPlatform, socialTestTargetName)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-white hover:bg-slate-100 border border-gray-200 transition cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3z"></path></svg>
                    <span>Re-test Connection</span>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="socialTestModalOpen = false"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold text-gray-700 bg-gray-200 hover:bg-gray-300 transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
