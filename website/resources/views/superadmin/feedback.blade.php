@extends('superadmin.layout')
@section('title', 'Feedback & Koreksi AI')
@section('page-title', 'Feedback & Koreksi AI')
@section('page-subtitle', 'Log koreksi manual dari agen kasir pelanggan')

@section('content')
<div class="space-y-5">

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Koreksi</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($totalCorrections) }}</p>
            <p class="text-xs text-gray-400 mt-1">Dari seluruh akun</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Akurasi AI di Lapangan</p>
            <p class="text-3xl font-bold {{ $accuracyRate >= 90 ? 'text-brand-600' : ($accuracyRate >= 75 ? 'text-amber-500' : 'text-red-500') }} mt-1">{{ $accuracyRate }}%</p>
            <p class="text-xs text-gray-400 mt-1">Berdasarkan rasio koreksi</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm flex items-center justify-center">
            {{-- Accuracy Gauge Visual --}}
            <div class="text-center">
                <div class="relative w-20 h-20 mx-auto">
                    <svg viewBox="0 0 36 36" class="w-20 h-20 -rotate-90">
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" stroke-width="3"/>
                        <circle cx="18" cy="18" r="15.9" fill="none"
                            stroke="{{ $accuracyRate >= 90 ? '#10b981' : ($accuracyRate >= 75 ? '#f59e0b' : '#ef4444') }}"
                            stroke-width="3" stroke-dasharray="{{ $accuracyRate }}, 100" stroke-linecap="round"/>
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-900">{{ $accuracyRate }}%</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">Akurasi Model</p>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <form method="GET" action="{{ route('superadmin.feedback') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <select name="user_id" onchange="this.form.submit()"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2.5 focus:ring-2 focus:ring-brand-500 outline-none bg-white flex-1 sm:max-w-xs">
                <option value="">Semua Pengguna</option>
                @foreach($allUsers as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }} ({{ $u->company_name }})
                    </option>
                @endforeach
            </select>
            <a href="{{ route('superadmin.feedback') }}" class="text-xs text-gray-500 hover:text-brand-600 transition">Reset Filter</a>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Pengguna</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Isi Pesan Pelanggan</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Label Lama (AI)</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Koreksi Agen</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($corrections as $c)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-medium text-gray-800 text-xs">{{ $c->user_name }}</p>
                            <p class="text-[10px] text-gray-400">{{ $c->company_name }}</p>
                        </td>
                        <td class="px-5 py-4 max-w-xs">
                            <p class="text-xs text-gray-700 line-clamp-2">{{ $c->message_text ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <div class="space-y-1">
                                @if($c->original_intent)
                                <span class="inline-block text-[10px] font-medium bg-red-50 text-red-600 px-2 py-0.5 rounded-full">{{ $c->original_intent }}</span>
                                @endif
                                @if($c->original_sentiment)
                                <span class="inline-block text-[10px] font-medium bg-red-50 text-red-600 px-2 py-0.5 rounded-full">{{ $c->original_sentiment }}</span>
                                @endif
                                @if(!$c->original_intent && !$c->original_sentiment)
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="space-y-1">
                                @if($c->corrected_intent)
                                <span class="inline-block text-[10px] font-medium bg-brand-50 text-brand-700 px-2 py-0.5 rounded-full">{{ $c->corrected_intent }}</span>
                                @endif
                                @if($c->corrected_sentiment)
                                <span class="inline-block text-[10px] font-medium bg-brand-50 text-brand-700 px-2 py-0.5 rounded-full">{{ $c->corrected_sentiment }}</span>
                                @endif
                                @if(!$c->corrected_intent && !$c->corrected_sentiment)
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($c->created_at)->diffForHumans() }}</p>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center">
                            <p class="text-gray-400 text-sm">Belum ada data koreksi AI.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($corrections->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $corrections->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
