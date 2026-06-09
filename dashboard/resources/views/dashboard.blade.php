<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sentiment Analysis Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ tab: 'sample' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- ── Banner Status Trial / Langganan ──────────────────────────── --}}
            @if($trialStatus['status'] === 'trial' && $trialStatus['days_left'] <= 3 && $trialStatus['is_active'])
                <div class="mb-6 flex items-center justify-between bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl px-5 py-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm font-semibold">Trial Anda akan berakhir dalam <strong>{{ $trialStatus['days_left'] }} hari</strong>. Upgrade sekarang agar tidak terputus.</span>
                    </div>
                    <a href="#" class="ml-4 shrink-0 px-4 py-1.5 bg-amber-500 text-white text-xs font-bold rounded-lg hover:bg-amber-600 transition">Upgrade Sekarang</a>
                </div>
            @elseif($trialStatus['status'] === 'trial' && $trialStatus['is_active'])
                <div class="mb-6 flex items-center gap-3 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-2xl px-5 py-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-sm font-medium">Trial Aktif &mdash; <strong>{{ $trialStatus['days_left'] }} hari</strong> tersisa untuk <strong>{{ $trialStatus['company_name'] }}</strong>.</span>
                </div>
            @elseif(!$trialStatus['is_active'])
                <div class="mb-6 flex items-center justify-between bg-red-50 border border-red-200 text-red-800 rounded-2xl px-5 py-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm font-semibold">Trial Anda telah berakhir. Analisis pesan baru dihentikan. Data lama masih bisa dilihat di bawah.</span>
                    </div>
                    <a href="#" class="ml-4 shrink-0 px-4 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700 transition">Aktifkan Langganan</a>
                </div>
            @elseif($trialStatus['status'] === 'active')
                <div class="mb-6 flex items-center gap-3 bg-green-50 border border-green-100 text-green-700 rounded-2xl px-5 py-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-sm font-medium">Langganan <strong>Aktif</strong> &mdash; {{ $trialStatus['company_name'] }}</span>
                </div>
            @endif

            <!-- 1. Per-Contact Health Stats -->
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Contact Analytics</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-green-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">Happy Contacts (>80%)</p>
                        <h3 class="text-xl font-bold text-green-600">{{ number_format($stats['positive_contacts']) }}</h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-red-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">At Risk Contacts (<50%)</p>
                        <h3 class="text-xl font-bold text-red-600">{{ number_format($stats['negative_contacts']) }}</h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-gray-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">Neutral Contacts</p>
                        <h3 class="text-xl font-bold text-gray-700">{{ number_format($stats['neutral_contacts']) }}</h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-blue-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">Total Contacts</p>
                        <h3 class="text-xl font-bold text-gray-700">{{ number_format($stats['total_contacts']) }}</h3>
                    </div>
                </div>
            </div>

            <!-- 2. Message Level Stats -->
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Message Analytics</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-blue-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">Pesan Diproses</p>
                        <h3 class="text-xl font-bold text-gray-700">{{ number_format($stats['total_processed']) }}</h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-green-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">Avg Positive</p>
                        <h3 class="text-xl font-bold text-green-600">{{ $stats['avg_positive'] }}%</h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-red-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">Avg Negative</p>
                        <h3 class="text-xl font-bold text-red-600">{{ $stats['avg_negative'] }}%</h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-gray-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">Avg Confidence</p>
                        <h3 class="text-xl font-bold text-gray-700">{{ $stats['avg_confidence'] }}%</h3>
                    </div>
                </div>
            </div>

            <div class="bg-gray-200/50 p-1 rounded-xl inline-flex mb-8 w-full md:w-auto" x-data="{ showTextModal: false, showRagModal: false }">
                <button @click="showTextModal = true" class="px-6 py-2 text-sm font-medium text-gray-700 hover:bg-white hover:shadow-sm rounded-lg flex items-center transition"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Test AI Model</button>
                <button @click="showRagModal = true" class="px-6 py-2 text-sm font-medium text-gray-700 hover:bg-white hover:shadow-sm rounded-lg flex items-center transition"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg> Upload Dokumen RAG</button>
                
                <!-- Text Analyzer Modal -->
                <div x-show="showTextModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen p-4 text-center">
                        <div x-show="showTextModal" x-transition.opacity class="fixed inset-0 transition-opacity" style="background-color: rgba(17, 24, 39, 0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);" @click="showTextModal = false"></div>
                        <div x-show="showTextModal" x-transition class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-lg sm:w-full p-6 border border-gray-100">
                            <h3 class="text-xl font-bold text-gray-900 mb-2" id="modal-title">Test AI Sentiment Model</h3>
                            <p class="text-sm text-gray-500 mb-6">Uji coba model klasifikasi teks IndoBERT secara langsung.</p>
                            
                            <textarea class="w-full border-gray-300 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-4 p-3 text-sm" rows="4" placeholder="Ketik kalimat komplain atau pujian di sini..."></textarea>
                            
                            <div class="flex justify-end gap-3 mt-4">
                                <button @click="showTextModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200">Batal</button>
                                <button @click="alert('API sedang dalam proses integrasi dengan ekstensi.')" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm" style="background-color: #2563eb; color: white;">Analisis Teks</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RAG Upload Modal -->
                <div x-show="showRagModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen p-4 text-center">
                        <div x-show="showRagModal" x-transition.opacity class="fixed inset-0 transition-opacity" style="background-color: rgba(17, 24, 39, 0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);" @click="showRagModal = false"></div>
                        <div x-show="showRagModal" x-transition class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-lg sm:w-full p-6 border border-gray-100">
                            <h3 class="text-xl font-bold text-gray-900 mb-2" id="modal-title">Upload Dokumen Pengetahuan RAG</h3>
                            <p class="text-sm text-gray-500 mb-6">Unggah file PDF atau DOCX berisi FAQ/SOP. AI akan menggunakan dokumen ini sebagai basis pengetahuan untuk meracik balasan.</p>
                            
                            <div class="border-2 border-dashed border-blue-200 bg-blue-50/50 rounded-xl p-8 flex flex-col justify-center items-center mb-6 hover:bg-blue-50 transition cursor-pointer">
                                <svg class="w-10 h-10 text-blue-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <span class="text-sm font-semibold text-blue-600">Klik untuk browse atau drag file kemari</span>
                                <span class="text-xs text-gray-400 mt-1">Maks. 5MB per file (PDF, DOCX)</span>
                            </div>

                            <div class="flex justify-end gap-3 mt-4">
                                <button @click="showRagModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200">Tutup</button>
                                <button @click="alert('Modul VectorDB RAG akan segera diaktifkan.'); showRagModal = false;" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm" style="background-color: #2563eb; color: white;">Upload & Proses</button>
                            </div>
                        </div>
                    </div>
                </div>                
                <button @click="tab = 'sample'" 
                    :class="tab === 'sample' ? 'bg-white text-gray-900 shadow-sm font-bold' : 'text-gray-700 font-medium'"
                    class="px-6 py-2 text-sm rounded-lg flex items-center transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Sample Data
                </button>

                <button @click="tab = 'analytics'; setTimeout(() => window.dispatchEvent(new Event('resize')), 50);" 
                    :class="tab === 'analytics' ? 'bg-white text-gray-900 shadow-sm font-bold' : 'text-gray-700 font-medium'"
                    class="px-6 py-2 text-sm rounded-lg flex items-center transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg> Analytics
                </button>
            </div>

            <div x-show="tab === 'analytics'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Sentiment Trend (Today)</h3>
                    <div class="h-64"><canvas id="trendChart"></canvas></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Current Sentiment Distribution</h3>
                    <div class="h-64 flex justify-center"><canvas id="distributionChart"></canvas></div>
                </div>
            </div>

            <div x-show="tab === 'sample'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95">
                <h3 class="text-xl font-bold text-gray-800">Daftar Kontak (CRM View)</h3>
                <p class="text-sm text-gray-500 mb-6">Klik pada masing-masing kontak untuk melihat detail dan riwayat keluhan/pesan mereka.</p>

                <div class="space-y-4">
                    @forelse($contactsPaginator as $contact)
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ expanded: false }">
                            
                            <!-- Contact Header (Clickable) -->
                            <div @click="expanded = !expanded" class="p-5 cursor-pointer hover:bg-gray-50 flex items-center justify-between transition border-b border-transparent" :class="expanded ? 'border-gray-100 bg-gray-50' : ''">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center font-bold">
                                        {{ substr($contact->sender_name ?? $contact->sender_id, 0, 1) }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800">{{ $contact->sender_name ?? $contact->sender_id }}</h4>
                                        <p class="text-xs text-gray-500">{{ $contact->total_msgs }} Pesan &bull; Terakhir aktif: {{ \Carbon\Carbon::parse($contact->last_interaction)->diffForHumans() }}</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-6">
                                    <!-- Health Status -->
                                    <div class="text-right hidden sm:block">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Health Score</p>
                                        @if($contact->health_score >= 80)
                                            <span class="text-green-600 font-bold">{{ round($contact->health_score) }}% (Positif)</span>
                                        @elseif($contact->health_score < 50)
                                            <span class="text-red-600 font-bold">{{ round($contact->health_score) }}% (At Risk)</span>
                                        @else
                                            <span class="text-gray-600 font-bold">{{ round($contact->health_score) }}% (Netral)</span>
                                        @endif
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>

                            <!-- Expanded Message List -->
                            <div x-show="expanded" x-collapse x-cloak class="bg-gray-50 p-5 border-t border-gray-100">
                                <h5 class="text-sm font-bold text-gray-700 mb-4">Riwayat Pesan Terakhir</h5>
                                <div class="space-y-4">
                                    @foreach($contact->messages as $msg)
                                        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm relative" x-data="{ showReply: false, showDetails: false }">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="flex items-center space-x-3">
                                                    <span class="px-3 py-1 bg-gray-50 border border-gray-100 text-[11px] rounded-lg font-bold text-gray-600 uppercase">{{ $msg->intent }}</span>
                                                    <span class="text-xs text-gray-400">{{ $msg->created_at ? $msg->created_at->format('Y-m-d H:i') : 'Baru saja' }}</span>
                                                </div>
                                                <div class="flex flex-col items-end">
                                                    <span class="px-4 py-1 rounded-lg text-[11px] font-black tracking-widest {{ $msg->sentiment == 'positive' ? 'bg-black text-white' : ($msg->sentiment == 'negative' ? 'bg-red-500 text-white' : 'bg-gray-400 text-white') }}">
                                                        {{ strtoupper($msg->sentiment) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <p class="text-gray-800 text-sm mb-4 leading-relaxed font-medium">
                                                "{{ $msg->message }}"
                                            </p>

                                            <div class="flex justify-between items-center pt-3 border-t border-gray-50">
                                                <div class="text-[11px] font-bold flex gap-3">
                                                    <span class="text-gray-400">Confidence: {{ number_format($msg->confidence * 100, 1) }}%</span>
                                                </div>
                                                
                                                <div class="flex space-x-2">
                                                    <button @click="showReply = true" class="px-3 py-1.5 bg-indigo-50 border border-indigo-100 rounded-lg text-[11px] font-bold text-indigo-700 hover:bg-indigo-100 transition">Generate Reply</button>
                                                    <button @click="showDetails = true" class="px-3 py-1.5 border border-gray-200 rounded-lg text-[11px] font-bold text-gray-700 hover:bg-gray-50 transition">View Details</button>
                                                </div>
                                            </div>

                                            <!-- Reply Popup Inline -->
                                            <div x-show="showReply" x-collapse x-cloak class="mt-4 pt-4 border-t border-gray-100">
                                                <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4">
                                                    <div class="flex justify-between items-center mb-2">
                                                        <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Saran Balasan AI</span>
                                                        <button @click="showReply = false" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                                                    </div>
                                                    <p class="text-xs text-gray-700 mb-4 leading-relaxed bg-white p-3 rounded-lg border border-gray-200 shadow-sm">"{{ $msg->reply_suggestion }}"</p>
                                                    
                                                    <div class="flex flex-col sm:flex-row gap-2">
                                                        <button @click="navigator.clipboard.writeText('{{ $msg->reply_suggestion }}'); alert('Disalin ke clipboard!');" 
                                                            class="flex-1 bg-green-50 text-green-700 py-2 rounded-lg text-xs font-bold hover:bg-green-100 transition border border-green-200">
                                                            📋 Salin Teks
                                                        </button>
                                                        
                                                        <form action="{{ route('messages.resolve', $msg->id) }}" method="POST" class="flex-1">
                                                            @csrf @method('PATCH')
                                                            <button type="submit" class="w-full py-2 bg-gray-800 text-white rounded-lg text-xs font-bold hover:bg-black transition">✓ Tandai Selesai</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Details Modal -->
                                            <div x-show="showDetails" x-cloak class="fixed inset-0 z-[100] overflow-y-auto">
                                                <div class="flex items-center justify-center min-h-screen p-4 text-center">
                                                    <div x-show="showDetails" x-transition.opacity class="fixed inset-0 transition-opacity" style="background-color: rgba(17, 24, 39, 0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);" @click="showDetails = false"></div>
                                                    <div x-show="showDetails" x-transition class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-lg sm:w-full p-6 border border-gray-100">
                                                        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Detail Pesan Masuk</h3>
                                                        <div class="space-y-3 text-sm text-gray-700 mb-6">
                                                            <div class="grid grid-cols-3 gap-2"><span class="font-semibold text-gray-500">ID Pesan:</span> <span class="col-span-2 font-mono">#{{ $msg->id }}</span></div>
                                                            <div class="grid grid-cols-3 gap-2"><span class="font-semibold text-gray-500">Sentimen:</span> <span class="col-span-2 uppercase font-bold">{{ $msg->sentiment }} ({{ number_format($msg->confidence * 100, 1) }}%)</span></div>
                                                            <div class="grid grid-cols-3 gap-2"><span class="font-semibold text-gray-500">Status:</span> 
                                                                <span class="col-span-2 font-bold">{{ $msg->status == 'resolved' ? '✅ Diselesaikan oleh ' . ($msg->resolver->name ?? 'Admin') : '⌛ Menunggu' }}</span>
                                                            </div>
                                                        </div>
                                                        <button @click="showDetails = false" class="w-full inline-flex justify-center rounded-lg border border-gray-300 px-4 py-2 bg-white text-sm font-bold text-gray-700 hover:bg-gray-50">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center bg-white rounded-2xl border border-dashed text-gray-500 italic">
                            Belum ada kontak atau pesan yang masuk.
                        </div>
                    @endforelse
                </div>
                <div class="mt-6">{{ $contactsPaginator->links() }}</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Trend Chart
            new Chart(document.getElementById('trendChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($trendData->pluck('hour')->map(fn($h) => \Carbon\Carbon::parse($h)->format('H:i'))) !!},
                    datasets: [
                        {
                            label: 'Positive',
                            data: {!! json_encode($trendData->pluck('positive_count')) !!},
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Negative',
                            data: {!! json_encode($trendData->pluck('negative_count')) !!},
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Neutral',
                            data: {!! json_encode($trendData->pluck('neutral_count')) !!},
                            borderColor: '#64748b',
                            backgroundColor: 'rgba(100, 116, 139, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Distribution Chart
            new Chart(document.getElementById('distributionChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['Positive', 'Negative', 'Neutral'],
                    datasets: [{
                        data: {!! json_encode($pieData) !!},
                        backgroundColor: ['#22c55e', '#ef4444', '#64748b']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        });
    </script>
    <style>[x-cloak] { display: none !important; }</style>

    {{-- ─── Real-Time Toast Notifications (WebSockets) ─── --}}
    <div x-data="{ toasts: [] }" @new-message.window="
        let msg = $event.detail;
        let toast = { id: Date.now(), text: msg.message, sender: msg.sender_name, sentiment: msg.sentiment };
        toasts.push(toast);
        
        // Audio Notifikasi jika Negatif
        if(msg.sentiment === 'negative') {
            let audio = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
            audio.play().catch(e => console.log('Audio autoplay blocked'));
        }
        
        // Hapus toast setelah 6 detik
        setTimeout(() => { toasts = toasts.filter(t => t.id !== toast.id) }, 6000);
    " class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-3 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="true" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="pointer-events-auto px-5 py-4 rounded-2xl shadow-2xl border text-sm max-w-sm w-80 backdrop-blur-md"
                :class="toast.sentiment === 'negative' ? 'bg-red-50/90 border-red-200 text-red-900' : (toast.sentiment === 'positive' ? 'bg-green-50/90 border-green-200 text-green-900' : 'bg-white/90 border-gray-200 text-gray-800')">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5">
                        <template x-if="toast.sentiment === 'negative'">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </template>
                        <template x-if="toast.sentiment === 'positive'">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
                        </template>
                        <template x-if="toast.sentiment === 'neutral'">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        </template>
                    </div>
                    <div>
                        <div class="font-bold mb-1" x-text="toast.sentiment === 'negative' ? '🚨 Peringatan Komplain!' : (toast.sentiment === 'positive' ? '✨ Pujian Pelanggan' : '💬 Pesan Baru')"></div>
                        <div class="font-semibold text-xs opacity-75 mb-1" x-text="toast.sender"></div>
                        <div class="line-clamp-2 leading-relaxed" x-text="'&quot;' + toast.text + '&quot;'"></div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @vite(['resources/js/app.js'])
    <script type="module">
        document.addEventListener('DOMContentLoaded', function() {
            if (window.Echo) {
                window.Echo.private(`company.{{ auth()->id() }}`)
                    .listen('NewMessageAnalyzed', (e) => {
                        console.log('[WebSockets] New Message Arrived:', e);
                        window.dispatchEvent(new CustomEvent('new-message', { detail: e }));
                        
                        // Auto reload halaman setelah 3 detik agar grafik & tabel ter-update
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    });
            }
        });
    </script>
</x-app-layout>