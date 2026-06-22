@extends('superadmin.layout')
@section('title', 'Pesan Pelanggan')
@section('page-title', 'Pesan & Masukan Pelanggan')
@section('page-subtitle', 'Daftar pesan dari form contact us di landing page dan dashboard pelanggan')

@section('content')
<div class="space-y-5">
    {{-- Toolbar --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <form method="GET" action="{{ route('superadmin.product-feedbacks') }}" class="flex flex-1 flex-col sm:flex-row gap-3 w-full">
            <select name="status" onchange="this.form.submit()"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2.5 focus:ring-2 focus:ring-brand-500 outline-none bg-white">
                <option value="all" {{ request('status','all') === 'all' ? 'selected' : '' }}>Semua Status</option>
                <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Belum Dibaca</option>
                <option value="read"   {{ request('status') === 'read' ? 'selected' : '' }}>Sudah Dibaca</option>
            </select>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Pengirim</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Pesan</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Tgl Kirim</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="text-center px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($feedbacks as $fb)
                    <tr class="hover:bg-gray-50 transition-colors {{ $fb->status === 'unread' ? 'bg-brand-50/30' : '' }}">
                        <td class="px-5 py-4 align-top">
                            <p class="font-bold text-gray-900 text-sm">{{ $fb->name }}</p>
                            <p class="text-xs text-gray-500">{{ $fb->email }}</p>
                            @if($fb->user_id)
                                <span class="inline-block mt-1 px-2 py-0.5 bg-brand-100 text-brand-700 text-[10px] rounded font-bold uppercase tracking-wide">Pelanggan Terdaftar</span>
                            @else
                                <span class="inline-block mt-1 px-2 py-0.5 bg-gray-100 text-gray-600 text-[10px] rounded font-bold uppercase tracking-wide">Guest</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 align-top max-w-md">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $fb->message }}</p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <p class="text-sm text-gray-600">{{ $fb->created_at->format('d M Y H:i') }}</p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            @if($fb->status === 'unread')
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-amber-100 text-amber-700">Belum Dibaca</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-500">Sudah Dibaca</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 align-top text-center">
                            @if($fb->status === 'unread')
                                <form method="POST" action="{{ route('superadmin.product-feedbacks.read', $fb->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-50 text-brand-700 hover:bg-brand-100 rounded-lg text-xs font-semibold transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Tandai Dibaca
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center">
                            <p class="text-gray-400 text-sm">Tidak ada pesan yang sesuai.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($feedbacks->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $feedbacks->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
