@extends('superadmin.layout')
@section('title', 'Analytics AI')
@section('page-title', 'Analytics AI & RAG')
@section('page-subtitle', 'Monitor penggunaan kecerdasan buatan di seluruh akun pelanggan')

@section('content')
<div class="space-y-6">

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Avg. Confidence</p>
            <p class="text-3xl font-bold text-brand-600 mt-1">{{ number_format($avgConfidence * 100, 1) }}%</p>
            <p class="text-xs text-gray-400 mt-1">Keyakinan rata-rata AI</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Complaint</p>
            <p class="text-3xl font-bold text-red-500 mt-1">{{ number_format($intentDist['complaint'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Pesan komplain terdeteksi</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Order</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">{{ number_format($intentDist['order'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Pesan pemesanan terdeteksi</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Dok. RAG Terunggah</p>
            <p class="text-3xl font-bold text-purple-600 mt-1">{{ $ragStats->sum('doc_count') }}</p>
            <p class="text-xs text-gray-400 mt-1">Total dokumen SOP</p>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Volume Pesan per Hari --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Volume Analisis AI</h3>
                    <p class="text-xs text-gray-500">7 hari terakhir — seluruh akun</p>
                </div>
            </div>
            <canvas id="volumeChart" height="90"></canvas>
        </div>

        {{-- Intent Distribution --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-1">Distribusi Intent</h3>
            <p class="text-xs text-gray-500 mb-4">Global semua akun</p>
            <canvas id="intentChart" height="160"></canvas>
            <div class="mt-4 space-y-2">
                @php
                    $intentColors = ['complaint' => '#ef4444', 'order' => '#3b82f6', 'inquiry' => '#f59e0b', 'other' => '#9ca3af'];
                    $intentLabels = ['complaint' => 'Komplain', 'order' => 'Order', 'inquiry' => 'Pertanyaan', 'other' => 'Lainnya'];
                @endphp
                @foreach($intentColors as $key => $color)
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full" style="background:{{ $color }}"></div>
                        <span class="text-gray-600">{{ $intentLabels[$key] }}</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ number_format($intentDist[$key] ?? 0) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Bottom Tables --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Top 10 User Aktif --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900">Top 10 Pelanggan Teraktif</h3>
                <p class="text-xs text-gray-500 mt-0.5">Berdasarkan jumlah pesan dianalisis</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($topUsers as $idx => $u)
                <div class="flex items-center gap-4 px-6 py-3.5">
                    <span class="text-sm font-bold w-5 {{ $idx === 0 ? 'text-amber-500' : 'text-gray-400' }}">#{{ $idx + 1 }}</span>
                    <div class="w-7 h-7 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold text-xs shrink-0">
                        {{ strtoupper(substr($u->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $u->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $u->company_name }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-sm font-bold text-gray-900">{{ number_format($u->messages_count) }}</span>
                        <p class="text-[10px] text-gray-400">pesan</p>
                    </div>
                </div>
                @empty
                <div class="px-6 py-10 text-center text-sm text-gray-400">Belum ada data.</div>
                @endforelse
            </div>
        </div>

        {{-- Dokumen RAG per Akun --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900">Dokumen RAG per Akun</h3>
                <p class="text-xs text-gray-500 mt-0.5">Jumlah dokumen SOP yang diunggah pelanggan</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($ragStats as $r)
                <div class="flex items-center gap-4 px-6 py-3.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center text-purple-700 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $r->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $r->company_name }}</p>
                    </div>
                    <span class="text-sm font-bold text-purple-600 shrink-0">{{ $r->doc_count }} dok.</span>
                </div>
                @empty
                <div class="px-6 py-10 text-center text-sm text-gray-400">Belum ada dokumen RAG diunggah.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('volumeChart'), {
    type: 'bar',
    data: {
        labels: @json($volumeLabels),
        datasets: [{
            label: 'Pesan Dianalisis',
            data: @json($volumeData),
            backgroundColor: 'rgba(16, 185, 129, 0.15)',
            borderColor: '#10b981',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f1f5f9' } }
        }
    }
});

new Chart(document.getElementById('intentChart'), {
    type: 'doughnut',
    data: {
        labels: ['Komplain', 'Order', 'Pertanyaan', 'Lainnya'],
        datasets: [{
            data: [
                {{ $intentDist['complaint'] ?? 0 }},
                {{ $intentDist['order'] ?? 0 }},
                {{ $intentDist['inquiry'] ?? 0 }},
                {{ $intentDist['other'] ?? 0 }}
            ],
            backgroundColor: ['#ef4444', '#3b82f6', '#f59e0b', '#9ca3af'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        cutout: '70%',
        plugins: { legend: { display: false } }
    }
});
</script>
@endsection
