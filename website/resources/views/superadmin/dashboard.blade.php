@extends('superadmin.layout')
@section('title', 'Overview & KPI')
@section('page-title', 'Overview & KPI')
@section('page-subtitle', 'Ringkasan kinerja bisnis Emotext secara keseluruhan')

@section('content')
<div class="space-y-6" x-data>

    {{-- KPI CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Users --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Pelanggan</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($totalUsers) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Semua akun terdaftar</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
        </div>

        {{-- Trial --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Masa Trial</p>
                    <p class="text-3xl font-bold text-amber-500 mt-1">{{ number_format($trialUsers) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Sedang uji coba</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        {{-- Active --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Berlangganan</p>
                    <p class="text-3xl font-bold text-brand-600 mt-1">{{ number_format($activeUsers) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Akun berbayar aktif</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center text-brand-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        {{-- Revenue Estimasi --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Est. Pendapatan</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($estimatedRevenue / 1000) }}k</p>
                    <p class="text-xs text-gray-400 mt-1">Per bulan (estimasi)</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- CHARTS ROW --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Grafik Pertumbuhan --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Pertumbuhan Pengguna</h3>
                    <p class="text-xs text-gray-500 mt-0.5">30 hari terakhir</p>
                </div>
                <span class="text-xs bg-brand-50 text-brand-600 font-medium px-2.5 py-1 rounded-full border border-brand-100">Registrasi Baru</span>
            </div>
            <canvas id="growthChart" height="80"></canvas>
        </div>

        {{-- Distribusi Sentimen --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-1">Sentimen Global</h3>
            <p class="text-xs text-gray-500 mb-4">Semua pesan dari semua akun</p>
            <canvas id="sentimentChart" height="160"></canvas>
            <div class="mt-4 space-y-2">
                @foreach(['positive' => ['Positif','#10b981'], 'neutral' => ['Netral','#6b7280'], 'negative' => ['Negatif','#ef4444']] as $key => [$label, $color])
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full" style="background:{{ $color }}"></div>
                        <span class="text-gray-600">{{ $label }}</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ number_format($sentimentStats[$key] ?? 0) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- BOTTOM ROW --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Alert Trial Hampir Habis --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-amber-200 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <h3 class="text-sm font-semibold text-gray-900">Trial Hampir Berakhir</h3>
                @if($expiringTrials->count() > 0)
                    <span class="ml-auto text-xs bg-amber-100 text-amber-700 font-semibold px-2 py-0.5 rounded-full">{{ $expiringTrials->count() }} akun</span>
                @endif
            </div>
            @if($expiringTrials->isEmpty())
                <p class="text-sm text-gray-400 text-center py-4">✅ Tidak ada trial yang akan berakhir dalam 3 hari ke depan</p>
            @else
                <div class="space-y-2">
                    @foreach($expiringTrials as $u)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $u->name }}</p>
                            <p class="text-xs text-gray-500">{{ $u->company_name }} · {{ $u->email }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-amber-600">{{ $u->trialDaysLeft() }} hari lagi</span>
                            <p class="text-[10px] text-gray-400">{{ $u->trial_ends_at->format('d M Y') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Aktivitas Pesan --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Aktivitas Sistem</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-700">Pesan Hari Ini</p>
                    </div>
                    <span class="text-lg font-bold text-gray-900">{{ number_format($todayMessages) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-brand-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-700">Total Pesan</p>
                    </div>
                    <span class="text-lg font-bold text-gray-900">{{ number_format($totalMessages) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-700">Akun Expired</p>
                    </div>
                    <span class="text-lg font-bold text-red-500">{{ number_format($expiredUsers) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Grafik Pertumbuhan User
new Chart(document.getElementById('growthChart'), {
    type: 'line',
    data: {
        labels: @json($growthLabels),
        datasets: [{
            label: 'Registrasi Baru',
            data: @json($growthData),
            fill: true,
            backgroundColor: 'rgba(16, 185, 129, 0.08)',
            borderColor: '#10b981',
            borderWidth: 2,
            pointBackgroundColor: '#10b981',
            pointRadius: 3,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 }, maxTicksLimit: 10 } },
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f1f5f9' } }
        }
    }
});

// Grafik Sentimen Donut
new Chart(document.getElementById('sentimentChart'), {
    type: 'doughnut',
    data: {
        labels: ['Positif', 'Netral', 'Negatif'],
        datasets: [{
            data: [
                {{ $sentimentStats['positive'] ?? 0 }},
                {{ $sentimentStats['neutral'] ?? 0 }},
                {{ $sentimentStats['negative'] ?? 0 }}
            ],
            backgroundColor: ['#10b981', '#9ca3af', '#ef4444'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        cutout: '70%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed.toLocaleString()}` } }
        }
    }
});
</script>
@endsection
