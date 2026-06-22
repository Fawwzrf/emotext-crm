<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') — Emotext</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">
<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="hidden md:flex flex-col w-64 bg-white border-r border-gray-200 shrink-0">
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
            <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-900">Emotext Admin</p>
                <p class="text-xs text-gray-400">Panel Internal</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Menu Utama</p>

            <a href="{{ route('superadmin.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('superadmin.dashboard') ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Overview & KPI</span>
            </a>

            <a href="{{ route('superadmin.users') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('superadmin.users*') ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Manajemen User</span>
            </a>

            <a href="{{ route('superadmin.feedback') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('superadmin.feedback') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Koreksi AI
            </a>
            <a href="{{ route('superadmin.product-feedbacks') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('superadmin.product-feedbacks') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Pesan Pelanggan
                @php $unreadCount = \App\Models\ProductFeedback::where('status', 'unread')->count(); @endphp
                @if($unreadCount > 0)
                    <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $unreadCount }}</span>
                @endif
            </a>

            <a href="{{ route('superadmin.analytics') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('superadmin.analytics') ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Analytics AI</span>
            </a>

            <div class="pt-4 mt-3 border-t border-gray-100">
                <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Sistem</p>
                <a href="{{ route('superadmin.settings') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('superadmin.settings') ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                    <span>Pengaturan</span>
                </a>
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all text-gray-600 hover:bg-gray-100 hover:text-gray-900 mt-0.5">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>Dashboard Pelanggan</span>
                </a>
            </div>
        </nav>

        {{-- Admin Info --}}
        <div class="px-4 py-4 border-t border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold text-sm shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-brand-600 font-medium">Super Admin</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-red-500 transition" title="Logout">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- MAIN CONTENT --}}
    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Top Header --}}
        <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between shrink-0">
            <div>
                <h1 class="text-lg font-bold text-gray-900">@yield('page-title', 'Admin Panel')</h1>
                <p class="text-xs text-gray-500 mt-0.5">@yield('page-subtitle', 'Panel Internal Tim Emotext')</p>
            </div>
            <div class="flex items-center gap-3">
                @if(session('success'))
                    <div class="flex items-center gap-2 bg-brand-50 border border-brand-200 text-brand-700 text-xs font-medium px-3 py-2 rounded-lg" x-data x-init="setTimeout(() => $el.remove(), 4000)">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ session('success') }}
                    </div>
                @endif
                <span class="text-xs text-gray-400">{{ now()->format('d M Y, H:i') }} WIB</span>
            </div>
        </header>

        {{-- Page Content --}}
        <div class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
