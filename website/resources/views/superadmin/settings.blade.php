@extends('superadmin.layout')
@section('title', 'Pengaturan Sistem')
@section('page-title', 'Pengaturan Sistem')
@section('page-subtitle', 'Konfigurasi sistem dan informasi internal Emotext')

@section('content')
<div class="space-y-6 max-w-3xl">

    {{-- Paket & Harga (Statis) --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900">Paket & Harga Layanan</h3>
                <p class="text-xs text-gray-500">Referensi paket yang tersedia (statis)</p>
            </div>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['Trial', 'Rp 0', '7 Hari', '1 Agen, Akses penuh', 'amber'],
                ['Starter', 'Rp 99.000', '/ bulan', '1 Agen, 500 AI Calls', 'blue'],
                ['Pro', 'Rp 299.000', '/ bulan', '5 Agen, Unlimited AI', 'brand'],
                ['Enterprise', 'Custom', '/ bulan', 'Unlimited Agen, Dedicated Support', 'purple'],
            ] as [$name, $price, $period, $desc, $color])
            <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-bold text-gray-900">{{ $name }}</p>
                <p class="text-lg font-bold text-{{ $color }}-600 mt-1">{{ $price }}</p>
                <p class="text-xs text-gray-500">{{ $period }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
        <div class="px-6 pb-5">
            <div class="flex items-start gap-2 text-xs text-gray-500 bg-gray-50 rounded-lg p-3">
                <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Harga saat ini bersifat statis. Untuk mengubah paket secara dinamis, tambahkan tabel <code class="bg-gray-200 px-1 rounded">plans</code> di database dan hubungkan ke model User.
            </div>
        </div>
    </div>

    {{-- Info Koneksi --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center text-brand-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900">Koneksi Backend AI</h3>
                <p class="text-xs text-gray-500">Konfigurasi yang sedang aktif</p>
            </div>
        </div>
        <div class="p-6 space-y-3">
            @php
                $fastapiUrl = env('FASTAPI_URL', 'http://127.0.0.1:8000');
                $isCloud = !str_contains($fastapiUrl, '127.0.0.1') && !str_contains($fastapiUrl, 'localhost');
            @endphp
            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <div>
                    <p class="text-sm font-medium text-gray-900">URL Backend FastAPI</p>
                    <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ $fastapiUrl }}</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $isCloud ? 'bg-brand-100 text-brand-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $isCloud ? '☁️ Cloud' : '💻 Local' }}
                </span>
            </div>
            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <div>
                    <p class="text-sm font-medium text-gray-900">Database</p>
                    <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ env('DB_HOST', '-') }}</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-brand-100 text-brand-700">
                    PostgreSQL
                </span>
            </div>
            <div class="flex items-center justify-between py-3">
                <div>
                    <p class="text-sm font-medium text-gray-900">Internal API Key</p>
                    <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ substr(env('INTERNAL_API_KEY', '—'), 0, 8) }}••••••••</p>
                </div>
                <span class="text-xs text-gray-400">Disembunyikan</span>
            </div>
        </div>
    </div>

    {{-- Daftar Super Admin --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center text-red-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900">Akun Super Admin</h3>
                <p class="text-xs text-gray-500">Tim internal yang bisa mengakses panel ini</p>
            </div>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($superadmins as $admin)
            <div class="flex items-center gap-4 px-6 py-4">
                <div class="w-9 h-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold shrink-0">
                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-900">{{ $admin->name }}</p>
                    <p class="text-xs text-gray-500">{{ $admin->email }}</p>
                </div>
                @if($admin->id === auth()->id())
                <span class="text-xs font-semibold bg-brand-100 text-brand-700 px-2.5 py-1 rounded-full">Anda</span>
                @endif
            </div>
            @endforeach
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            <p class="text-xs text-gray-500">Untuk menambah super admin baru, jalankan perintah Artisan atau ubah nilai kolom <code class="bg-gray-200 px-1 rounded">is_superadmin</code> di database.</p>
        </div>
    </div>
</div>
@endsection
