@extends('superadmin.layout')
@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')
@section('page-subtitle', 'Kelola semua akun pelanggan Emotext')

@section('content')
<div class="space-y-5" x-data="{ activeModal: null, selectedUser: null }">

    {{-- Toolbar --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <form method="GET" action="{{ route('superadmin.users') }}" class="flex flex-1 flex-col sm:flex-row gap-3 w-full">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, atau perusahaan..."
                    class="w-full pl-9 pr-4 py-2.5 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none">
            </div>
            <select name="status" onchange="this.form.submit()"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2.5 focus:ring-2 focus:ring-brand-500 outline-none bg-white">
                <option value="all" {{ request('status','all') === 'all' ? 'selected' : '' }}>Semua Paket</option>
                <option value="trial"      {{ request('status') === 'trial'      ? 'selected' : '' }}>Trial</option>
                <option value="starter"    {{ request('status') === 'starter'    ? 'selected' : '' }}>Starter</option>
                <option value="pro"        {{ request('status') === 'pro'        ? 'selected' : '' }}>Pro</option>
                <option value="enterprise" {{ request('status') === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                <option value="expired"    {{ request('status') === 'expired'    ? 'selected' : '' }}>Expired</option>
                <option value="inactive"   {{ request('status') === 'inactive'   ? 'selected' : '' }}>Inactive</option>
            </select>
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Cari
            </button>
        </form>
        <a href="{{ route('superadmin.users.export') }}" class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Pengguna</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Perusahaan</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Paket</th>
                        <th class="text-center px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Pesan</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Tgl Daftar</th>
                        <th class="text-center px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold text-sm shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-sm text-gray-700">{{ $user->company_name ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-4">
                        <td class="px-4 py-4">
                            @if($user->isActive())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-100 text-brand-700">Aktif</span>
                            @elseif($user->subscription_status === 'expired')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Expired</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            @php
                                $pkgBadges = [
                                    'trial'      => 'bg-amber-100 text-amber-700',
                                    'starter'    => 'bg-blue-100 text-blue-700',
                                    'pro'        => 'bg-brand-100 text-brand-700',
                                    'enterprise' => 'bg-purple-100 text-purple-700',
                                ];
                                $pkgColor = $pkgBadges[$user->subscription_status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <div class="flex flex-col gap-1 items-start">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $pkgColor }}">
                                    {{ $user->subscription_status }}
                                </span>
                                @if($user->subscription_status === 'trial')
                                    @php $days = $user->trialDaysLeft(); @endphp
                                    @if($days <= 0)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-red-600">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                            Berakhir
                                        </span>
                                    @elseif($days <= 1)
                                        <span class="text-[11px] font-bold text-red-500">{{ $days }} hari tersisa</span>
                                    @elseif($days <= 3)
                                        <span class="text-[11px] font-semibold text-amber-500">{{ $days }} hari tersisa</span>
                                    @else
                                        <span class="text-[11px] font-medium text-gray-500">{{ $days }} hari tersisa</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($user->messages_count) }}</span>
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-sm text-gray-600">{{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-center gap-1.5"
                                 x-data="{ open: false }">
                                <div class="relative">
                                    <button @click="open = !open"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition">
                                        Aksi
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-transition
                                         class="absolute right-0 z-50 mt-1 w-48 bg-white rounded-xl shadow-xl border border-gray-200 py-1">

                                        {{-- Perpanjang Trial --}}
                                        <form method="POST" action="{{ route('superadmin.users.extend', $user) }}">
                                            @csrf
                                            <input type="hidden" name="days" value="7">
                                            <button type="submit" class="w-full text-left px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Perpanjang +7 Hari
                                            </button>
                                        </form>

                                        {{-- Set Packages --}}
                                        <hr class="my-1 border-gray-100">
                                        <p class="px-4 py-1 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Ubah Paket</p>
                                        
                                        @foreach(['starter' => ['blue', 'Starter'], 'pro' => ['brand', 'Pro'], 'enterprise' => ['purple', 'Enterprise']] as $pkg => [$color, $label])
                                        @if($user->subscription_status !== $pkg)
                                        <form method="POST" action="{{ route('superadmin.users.status', $user) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $pkg }}">
                                            <button type="submit" class="w-full text-left px-4 py-2 text-xs font-medium text-{{ $color }}-700 hover:bg-{{ $color }}-50 flex items-center gap-2">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Set {{ $label }}
                                            </button>
                                        </form>
                                        @endif
                                        @endforeach

                                        {{-- Set Expired --}}
                                        @if($user->subscription_status !== 'expired')
                                        <form method="POST" action="{{ route('superadmin.users.status', $user) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="expired">
                                            <button type="submit" class="w-full text-left px-4 py-2 text-xs font-medium text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Set Expired
                                            </button>
                                        </form>
                                        @endif

                                        <hr class="my-1 border-gray-100">

                                        {{-- Reset Token --}}
                                        <form method="POST" action="{{ route('superadmin.users.reset-token', $user) }}"
                                              onsubmit="return confirm('Reset API Token {{ $user->name }}?')">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                Reset API Token
                                            </button>
                                        </form>

                                        {{-- Delete User --}}
                                        <hr class="my-1 border-gray-100">
                                        <form method="POST" action="{{ route('superadmin.users.destroy', $user) }}"
                                              onsubmit="return confirm('HAPUS PERMANEN Pelanggan {{ $user->name }} beserta seluruh pesan dan dokumen RAG-nya? Tindakan ini tidak bisa dibatalkan!')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full text-left px-4 py-2 text-xs font-medium text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                Hapus Pelanggan
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center">
                            <p class="text-gray-400 text-sm">Tidak ada pengguna yang sesuai.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $users->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

    <p class="text-xs text-gray-400 text-right">Total: {{ $users->total() }} pengguna ditemukan</p>
</div>
@endsection
