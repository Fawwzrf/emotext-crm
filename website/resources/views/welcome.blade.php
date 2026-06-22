<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Emotext-CRM | Supercharge Your Customer Service</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Tailwind / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: { sans: ['Inter', 'sans-serif'] },
                        colors: {
                            brand: { 50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 500: '#10b981', 600: '#059669', 900: '#064e3b' }
                        },
                        animation: {
                            'border-beam': 'border-beam 4s linear infinite',
                            'sparkle': 'sparkle 2s ease-in-out infinite',
                            'float': 'float 3s ease-in-out infinite',
                        },
                        keyframes: {
                            'border-beam': {
                                '100%': { transform: 'rotate(360deg)' }
                            },
                            'sparkle': {
                                '0%, 100%': { opacity: '0.4', transform: 'scale(0.8)' },
                                '50%': { opacity: '1', transform: 'scale(1.2)' }
                            },
                            'float': {
                                '0%, 100%': { transform: 'translateY(0)' },
                                '50%': { transform: 'translateY(-10px)' }
                            }
                        }
                    }
                }
            }
        </script>
    @endif
    
    <!-- AlpineJS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Modern Spotlight Card Effect */
        .spotlight-card {
            position: relative;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(229, 231, 235, 1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .spotlight-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(800px circle at var(--mouse-x) var(--mouse-y), rgba(16, 185, 129, 0.06), transparent 40%);
            z-index: 0;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .spotlight-card:hover::before { opacity: 1; }
        .spotlight-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); }

        /* Border Beam Effect */
        .border-beam-container {
            position: relative;
            overflow: hidden;
            background: white;
            border-radius: 1rem;
        }
        .border-beam-container::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background: conic-gradient(from 0deg, transparent 70%, rgba(16, 185, 129, 0.3) 80%, rgba(16, 185, 129, 0.8) 100%);
            animation: border-beam 4s linear infinite;
            z-index: 0;
        }
        .border-beam-content {
            position: absolute;
            inset: 2px;
            border-radius: calc(1rem - 2px);
            z-index: 1;
        }

        /* Subtle Pattern Background */
        .bg-grid-pattern {
            background-image: linear-gradient(to right, #f0fdf4 1px, transparent 1px), linear-gradient(to bottom, #f0fdf4 1px, transparent 1px);
            background-size: 40px 40px;
        }
    </style>
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-50 selection:bg-brand-500 selection:text-white" x-data="landingPage()">

    <!-- 1. Navbar (Glassmorphism) -->
    <nav class="fixed top-0 w-full z-50 backdrop-blur-md bg-white/70 border-b border-gray-200/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-white font-bold text-xl">
                        E
                    </div>
                    <span class="font-bold text-xl tracking-tight text-gray-900">Emotext<span class="text-brand-600">CRM</span></span>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex space-x-8">
                    <a href="#features" class="text-gray-600 hover:text-brand-600 transition font-medium">Fitur</a>
                    <a href="#pricing" class="text-gray-600 hover:text-brand-600 transition font-medium">Harga</a>
                    <a href="#faq" class="text-gray-600 hover:text-brand-600 transition font-medium">FAQ</a>
                </div>

                <!-- CTA Auth -->
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-gray-900 hover:text-brand-600 transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex text-sm font-semibold text-gray-600 hover:text-gray-900 transition">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('install') }}" class="inline-flex items-center justify-center rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 transition-all duration-300">
                                Mulai Gratis
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- 2. Hero Section -->
    <div class="relative pt-32 pb-20 sm:pt-40 sm:pb-24 overflow-hidden bg-white">
        <!-- Abstract Background -->
        <div class="absolute inset-0 bg-grid-pattern opacity-50"></div>
        <div class="absolute top-0 right-0 -translate-y-12 translate-x-1/3">
            <div class="w-[600px] h-[600px] bg-brand-100 rounded-full blur-[100px] opacity-60"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-50 border border-brand-100 text-brand-600 text-sm font-medium mb-8 animate-float">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-500 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-600"></span>
                </span>
                Telah Terintegrasi AI IndoBERT
            </div>

            <h1 class="text-5xl sm:text-7xl font-extrabold tracking-tight text-gray-900 mb-6">
                Supercharge <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 to-emerald-400">Customer Service</span> Anda.
            </h1>
            
            <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-600 mb-10">
                Analisis sentimen, respons instan, dan asisten RAG langsung di dalam WhatsApp Web Anda. 
                Tanpa biaya API WhatsApp resmi.
            </p>

            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="{{ route('install') }}" class="relative group inline-flex items-center justify-center rounded-full bg-gray-900 px-8 py-3.5 text-base font-semibold text-white overflow-hidden transition-all hover:scale-105">
                    <span class="absolute w-0 h-0 transition-all duration-500 ease-out bg-brand-500 rounded-full group-hover:w-56 group-hover:h-56"></span>
                    <span class="relative flex items-center gap-2">
                        Install Ekstensi Gratis
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </span>
                </a>
                <a href="#features" class="inline-flex items-center justify-center rounded-full bg-white border border-gray-200 px-8 py-3.5 text-base font-semibold text-gray-900 hover:bg-gray-50 transition-all">
                    Lihat Cara Kerja
                </a>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Mulai Gratis. Termasuk Trial 7 Hari paket Pro.</p>
            
            <!-- Hero Dashboard Mockup -->
            <div class="mt-16 mx-auto max-w-5xl relative">
                <div class="rounded-xl bg-gray-900/5 p-2 ring-1 ring-inset ring-gray-900/10 lg:-m-4 lg:rounded-2xl lg:p-4">
                    <div class="rounded-md shadow-2xl border border-gray-200 bg-white overflow-hidden flex h-[400px]">
                        <!-- Mockup Sidebar -->
                        <div class="w-16 sm:w-48 bg-gray-50 border-r border-gray-100 flex flex-col p-4 gap-4 hidden md:flex">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="h-8 w-8 bg-brand-500 rounded-lg flex items-center justify-center text-white font-bold text-xs">E</div>
                                <span class="font-bold text-gray-900 text-sm hidden sm:block">EmotextCRM</span>
                            </div>
                            <div class="h-8 w-full bg-brand-100 rounded flex items-center px-2"><div class="h-3 w-1/2 bg-brand-500 rounded"></div></div>
                            <div class="h-4 w-full bg-gray-200 rounded mt-2"></div>
                            <div class="h-4 w-3/4 bg-gray-200 rounded"></div>
                            <div class="h-4 w-5/6 bg-gray-200 rounded"></div>
                        </div>
                        <!-- Mockup Content -->
                        <div class="flex-1 p-6 flex flex-col gap-6">
                            <div class="flex justify-between items-center border-b border-gray-100 pb-4">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">Dashboard Analytics</h2>
                                    <p class="text-xs text-gray-500">Overview performa CS Anda hari ini</p>
                                </div>
                                <div class="flex gap-2">
                                    <div class="flex flex-col items-end">
                                        <span class="text-xs text-gray-500">Health Score</span>
                                        <span class="text-lg font-bold text-brand-600">95/100</span>
                                    </div>
                                    <div class="h-10 w-10 bg-brand-100 rounded-full flex items-center justify-center text-brand-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                    </div>
                                </div>
                            </div>
                            <!-- Mockup Chart -->
                            <div class="flex-1 w-full bg-gradient-to-t from-brand-50/50 to-white border border-gray-100 rounded-lg relative overflow-hidden flex items-end">
                                <div class="absolute inset-0 grid grid-cols-4 grid-rows-3 gap-0">
                                    <div class="border-b border-r border-gray-50"></div><div class="border-b border-r border-gray-50"></div><div class="border-b border-r border-gray-50"></div><div class="border-b border-gray-50"></div>
                                    <div class="border-b border-r border-gray-50"></div><div class="border-b border-r border-gray-50"></div><div class="border-b border-r border-gray-50"></div><div class="border-b border-gray-50"></div>
                                    <div class="border-r border-gray-50"></div><div class="border-r border-gray-50"></div><div class="border-r border-gray-50"></div><div></div>
                                </div>
                                <svg class="w-full h-24 text-brand-400 relative z-10" viewBox="0 0 100 30" preserveAspectRatio="none">
                                    <path d="M0,30 L0,20 Q20,10 40,22 T80,15 L100,5 L100,30 Z" fill="currentColor" opacity="0.3"></path>
                                    <path d="M0,20 Q20,10 40,22 T80,15 L100,5" fill="none" stroke="currentColor" stroke-width="1.5"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Features (Bento Grid) -->
    <div id="features" class="py-24 bg-gray-50 border-y border-gray-200/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Semua yang Anda butuhkan dalam satu layar.</h2>
                <p class="mt-4 text-lg text-gray-600">Teknologi NLP canggih yang bekerja di belakang layar WhatsApp Anda.</p>
            </div>

            <!-- Bento Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 auto-rows-[280px]">
                
                <!-- Card 1: Large (Span 2) -->
                <div class="md:col-span-2 spotlight-card rounded-2xl p-8 shadow-sm group flex flex-col justify-between" x-on:mousemove="handleMouseMove($event)">
                    <div>
                        <div class="w-12 h-12 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Smart Sentiment Analysis</h3>
                        <p class="text-gray-600 max-w-md">Otomatis mendeteksi apakah pelanggan Anda sedang marah, senang, atau sekadar bertanya menggunakan AI IndoBERT berbahasa Indonesia.</p>
                    </div>
                    <div class="mt-6 flex gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">Negatif (30%)</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-100 text-brand-800 border border-brand-200">Positif (70%)</span>
                    </div>
                </div>

                <!-- Card 2: Small -->
                <div class="spotlight-card rounded-2xl p-8 shadow-sm group" x-on:mousemove="handleMouseMove($event)">
                    <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mb-4 group-hover:rotate-12 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Ekstensi Chrome</h3>
                    <p class="text-gray-600 text-sm">Menyatu langsung dengan WhatsApp Web tanpa perlu repot daftar Official API berbayar.</p>
                </div>

                <!-- Card 3: Small -->
                <div class="spotlight-card rounded-2xl p-8 shadow-sm group" x-on:mousemove="handleMouseMove($event)">
                    <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center mb-4 group-hover:-translate-y-1 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">SOP Intelligence (RAG)</h3>
                    <p class="text-gray-600 text-sm">AI membaca dokumen PDF SOP Anda untuk memberikan saran balasan yang akurat secara instan.</p>
                </div>

                <!-- Card 4: Large (Span 2) -->
                <div class="md:col-span-2 spotlight-card rounded-2xl p-8 shadow-sm group flex flex-col justify-center items-start" x-on:mousemove="handleMouseMove($event)">
                    <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4 group-hover:translate-x-1 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Real-time Analytics Dashboard</h3>
                    <p class="text-gray-600 max-w-md">Pantau performa Customer Service, kesehatan pelanggan (Health Score), dan metrik waktu penyelesaian secara langsung.</p>
                    <a href="{{ route('register') }}" class="mt-6 text-brand-600 hover:text-brand-500 font-medium inline-flex items-center gap-1 group-hover:gap-2 transition-all">
                        Eksplorasi Dashboard <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- 4. Pricing (Magic Cards) -->
    <div id="pricing" class="py-24 bg-white relative">
        <div class="absolute inset-0 bg-grid-pattern opacity-30 pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Harga Fleksibel untuk Bisnis Anda</h2>
                <p class="mt-4 text-lg text-gray-600">Pilih paket yang sesuai dengan volume percakapan pelanggan Anda.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-5xl mx-auto items-center">
                <!-- Starter Plan -->
                <div class="spotlight-card rounded-3xl p-8 flex flex-col h-full" x-on:mousemove="handleMouseMove($event)">
                    <h3 class="text-lg font-semibold text-gray-900">Starter</h3>
                    <div class="mt-4 flex items-baseline text-4xl font-extrabold text-gray-900">
                        Rp 99k <span class="ml-1 text-xl font-medium text-gray-500">/bln</span>
                    </div>
                    <p class="mt-4 text-sm text-gray-600">Cocok untuk UMKM dan bisnis baru.</p>
                    <ul class="mt-8 space-y-4 flex-1">
                        <li class="flex items-center gap-3 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-brand-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            1 Agen WhatsApp
                        </li>
                        <li class="flex items-center gap-3 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-brand-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            500 Analisis AI / bulan
                        </li>
                    </ul>
                    <a href="{{ route('install') }}" class="mt-8 block w-full py-3 px-4 rounded-full border border-gray-300 text-center text-sm font-semibold text-gray-900 hover:bg-gray-50 transition">Mulai Starter</a>
                </div>

                <!-- Pro Plan (Recommended) -->
                <div class="spotlight-card rounded-3xl p-8 flex flex-col border-brand-500 shadow-2xl scale-100 lg:scale-105 relative bg-white h-full z-10" x-on:mousemove="handleMouseMove($event)">
                    <div class="absolute top-5 right-5">
                        <span class="inline-flex items-center rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-600 shadow-sm border border-brand-200">Paling Laris</span>
                    </div>
                    <h3 class="text-lg font-semibold text-brand-600">Pro</h3>
                    <div class="mt-4 flex items-baseline text-4xl font-extrabold text-gray-900">
                        Rp 299k <span class="ml-1 text-xl font-medium text-gray-500">/bln</span>
                    </div>
                    <p class="mt-4 text-sm text-gray-600">Didesain untuk tim CS profesional yang sibuk.</p>
                    <ul class="mt-8 space-y-4 flex-1">
                        <li class="flex items-center gap-3 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-brand-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Hingga 5 Agen WhatsApp
                        </li>
                        <li class="flex items-center gap-3 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-brand-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Unlimited Analisis AI
                        </li>
                        <li class="flex items-center gap-3 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-brand-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Upload SOP PDF (RAG)
                        </li>
                    </ul>
                    <a href="{{ route('install') }}" class="mt-8 block w-full py-3 px-4 rounded-full bg-brand-600 text-center text-sm font-semibold text-white hover:bg-brand-500 shadow-lg shadow-brand-500/30 transition">Langganan Pro</a>
                </div>

                <!-- Enterprise Plan -->
                <div class="spotlight-card rounded-3xl p-8 flex flex-col h-full" x-on:mousemove="handleMouseMove($event)">
                    <h3 class="text-lg font-semibold text-gray-900">Enterprise</h3>
                    <div class="mt-4 flex items-baseline text-4xl font-extrabold text-gray-900">
                        Custom
                    </div>
                    <p class="mt-4 text-sm text-gray-600">Skala tanpa batas untuk perusahaan besar.</p>
                    <ul class="mt-8 space-y-4 flex-1">
                        <li class="flex items-center gap-3 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Agen Unlimited
                        </li>
                        <li class="flex items-center gap-3 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Server Private
                        </li>
                    </ul>
                    <a href="mailto:contact@emotext.com" class="mt-8 block w-full py-3 px-4 rounded-full border border-gray-300 text-center text-sm font-semibold text-gray-900 hover:bg-gray-50 transition">Hubungi Sales</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Us Section -->
    <div id="contact" class="py-24 bg-gray-50 border-t border-gray-200" x-data="{ sending: false }">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Hubungi Kami</h2>
                <p class="mt-4 text-lg text-gray-600">Punya pertanyaan atau masukan tentang Emotext? Jangan ragu untuk menghubungi kami.</p>
            </div>

            @if(session('success'))
                <div class="mb-8 p-4 bg-brand-50 border border-brand-200 rounded-xl text-brand-700 text-center font-medium">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('contact.store') }}" method="POST" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-200" @submit="sending = true">
                @csrf
                <div class="grid grid-cols-1 gap-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input type="text" name="name" id="name" required class="mt-2 block w-full rounded-xl border-gray-300 px-4 py-3 text-gray-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 bg-gray-50/50" placeholder="John Doe">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="email" id="email" required class="mt-2 block w-full rounded-xl border-gray-300 px-4 py-3 text-gray-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 bg-gray-50/50" placeholder="john@example.com">
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700">Pesan / Masukan</label>
                        <textarea id="message" name="message" rows="4" required class="mt-2 block w-full rounded-xl border-gray-300 px-4 py-3 text-gray-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 bg-gray-50/50" placeholder="Tuliskan pesan Anda di sini..."></textarea>
                    </div>
                    <button type="submit" x-bind:disabled="sending" class="mt-4 w-full rounded-xl bg-brand-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 hover:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 transition flex justify-center items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                        <span x-show="!sending">Kirim Pesan</span>
                        <span x-show="sending">Mengirim...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white py-12 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="font-bold text-lg text-gray-900">Emotext<span class="text-brand-600">CRM</span></span>
            </div>
            <p class="text-sm text-gray-500">&copy; {{ date('Y') }} Emotext. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('landingPage', () => ({
                handleMouseMove(e) {
                    const rect = e.currentTarget.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    e.currentTarget.style.setProperty('--mouse-x', `${x}px`);
                    e.currentTarget.style.setProperty('--mouse-y', `${y}px`);
                }
            }))
        })
    </script>
</body>
</html>
