<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🧠 Knowledge Base RAG
            </h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Dashboard
            </a>
        </div>
    </x-slot>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div id="flash-success" class="fixed top-4 right-4 z-[9999] flex items-center gap-3 bg-green-600 text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-lg" style="animation: slideIn 0.3s ease;">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
        <script>setTimeout(() => document.getElementById('flash-success')?.remove(), 5000);</script>
    @endif
    @if(session('error'))
        <div id="flash-error" class="fixed top-4 right-4 z-[9999] flex items-center gap-3 bg-red-600 text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-lg" style="animation: slideIn 0.3s ease;">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
        <script>setTimeout(() => document.getElementById('flash-error')?.remove(), 7000);</script>
    @endif
    <style>@keyframes slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }</style>

    <div class="py-12" x-data="{ uploading: false, uploadSuccess: null, fileName: null }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Upload Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-1">Upload Dokumen Baru</h3>
                <p class="text-sm text-gray-500 mb-5">Unggah file PDF atau TXT. AI akan menggunakan dokumen ini untuk meracik balasan kepada pelanggan.</p>

                <form method="POST" action="{{ route('dashboard.upload-kb') }}"
                    enctype="multipart/form-data"
                    @submit.prevent="
                        uploading = true;
                        uploadSuccess = null;
                        $el.submit();
                    "
                    x-ref="uploadForm"
                >
                    @csrf
                    <div class="flex flex-col sm:flex-row gap-4 items-start">
                        {{-- Drop Zone --}}
                        <div class="flex-1 border-2 border-dashed border-blue-200 bg-blue-50/50 rounded-xl p-6 flex flex-col items-center justify-center relative hover:bg-blue-50 transition cursor-pointer"
                            :class="fileName ? 'border-green-400 bg-green-50/50' : ''">
                            <input type="file" name="file" accept=".pdf,.txt" required
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                @change="fileName = $event.target.files[0]?.name || null">
                            <template x-if="!fileName">
                                <div class="flex flex-col items-center text-center">
                                    <svg class="w-8 h-8 text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <span class="text-sm font-semibold text-blue-600">Klik atau drag file kemari</span>
                                    <span class="text-xs text-gray-400 mt-1">Maks 5MB · PDF, TXT</span>
                                </div>
                            </template>
                            <template x-if="fileName">
                                <div class="flex flex-col items-center text-center">
                                    <svg class="w-8 h-8 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-sm font-semibold text-green-700" x-text="fileName"></span>
                                    <span class="text-xs text-gray-400 mt-1">File siap diupload</span>
                                </div>
                            </template>
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit"
                            :disabled="uploading || !fileName"
                            class="shrink-0 px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                            style="background-color: #2563eb; color: white;">
                            <template x-if="!uploading">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    Upload & Proses
                                </span>
                            </template>
                            <template x-if="uploading">
                                <span class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Memproses...
                                </span>
                            </template>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Documents List --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Dokumen Tersimpan</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ count($documents) }} dokumen aktif · AI menggunakan semua dokumen di bawah sebagai sumber pengetahuan</p>
                    </div>
                    <button onclick="location.reload()" class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh
                    </button>
                </div>

                @if(count($documents) === 0)
                    <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                        <svg class="w-14 h-14 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p class="text-gray-500 font-semibold">Belum ada dokumen</p>
                        <p class="text-gray-400 text-sm mt-1">Upload dokumen PDF/TXT SOP perusahaan Anda di atas untuk mulai menggunakan RAG.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach($documents as $doc)
                        <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50/50 transition">
                            <div class="flex items-center gap-4">
                                {{-- File Icon --}}
                                @php $ext = strtolower(pathinfo($doc['filename'], PATHINFO_EXTENSION)); @endphp
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                                    {{ $ext === 'pdf' ? 'bg-red-50' : 'bg-blue-50' }}">
                                    @if($ext === 'pdf')
                                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM9.5 16.5c-.3 0-.5-.2-.5-.5V14h-1v2c0 .8.7 1.5 1.5 1.5h5c.8 0 1.5-.7 1.5-1.5v-2h-1v2c0 .3-.2.5-.5.5h-5z"/></svg>
                                    @else
                                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ $doc['filename'] }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $doc['chunks'] }} paragraf vektor ·
                                        @php
                                            $ts = strtotime($doc['uploaded_at']);
                                            $diff = time() - $ts;
                                            if ($diff < 60) echo 'Baru saja';
                                            elseif ($diff < 3600) echo floor($diff/60) . ' menit lalu';
                                            elseif ($diff < 86400) echo floor($diff/3600) . ' jam lalu';
                                            else echo floor($diff/86400) . ' hari lalu';
                                        @endphp
                                    </p>
                                </div>
                            </div>

                            {{-- Badge & Delete --}}
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 bg-green-50 text-green-700 text-xs font-semibold rounded-lg">Aktif</span>
                                <form method="POST" action="{{ route('dashboard.kb.delete', ['id' => 1]) }}"
                                    onsubmit="return confirm('Hapus dokumen \'{{ $doc['filename'] }}\'? AI tidak akan bisa menggunakan dokumen ini lagi.')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="filename" value="{{ $doc['filename'] }}">
                                    <button type="submit"
                                        class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Info Card --}}
            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 flex gap-4">
                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="text-sm font-semibold text-blue-800">Bagaimana cara kerja RAG?</p>
                    <p class="text-sm text-blue-700 mt-1">Setiap pesan pelanggan yang masuk akan di-cocokkan secara semantik dengan paragraf-paragraf dari dokumen yang Anda upload. AI (Qwen 2.5) lalu menggunakan paragraf paling relevan sebagai panduan untuk meracik saran balasan yang akurat, sesuai kebijakan perusahaan Anda.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
