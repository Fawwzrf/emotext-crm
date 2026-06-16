<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install Emotext-CRM Ekstensi</title>
    
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
                        }
                    }
                }
            }
        </script>
    @endif
    
    <!-- AlpineJS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans text-gray-900 bg-gray-50 antialiased" x-data="{ agreed: false, package: null, downloaded: false }">

    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-white font-bold text-xl">
                        E
                    </div>
                    <span class="font-bold text-xl tracking-tight text-gray-900">Emotext<span class="text-brand-600">CRM</span></span>
                </a>
                <a href="{{ url('/') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Kembali ke Beranda</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 pt-32 pb-24 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-200 bg-gray-50">
                <h1 class="text-2xl font-bold text-gray-900">Persiapan Instalasi</h1>
                <p class="mt-2 text-sm text-gray-600">Selesaikan langkah-langkah di bawah untuk mengunduh Ekstensi dan membuat akun Anda.</p>
            </div>
            
            <div class="p-8 md:p-10">
                
                <!-- 1. Persetujuan -->
                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center text-sm">1</span> 
                        Persetujuan Layanan
                    </h2>
                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 text-sm text-gray-600 h-32 overflow-y-auto mb-4">
                        <p class="mb-2"><strong>Syarat dan Ketentuan Penggunaan Emotext-CRM</strong></p>
                        <p class="mb-2">Dengan menggunakan Emotext-CRM, Anda menyetujui bahwa sistem AI akan memproses pesan masuk di WhatsApp Web untuk analisis sentimen dan klasifikasi. Data ini diolah dan dijaga kerahasiaannya dengan arsitektur isolasi data kami secara luring.</p>
                        <p>Anda bertanggung jawab untuk mematuhi ketentuan dari penyedia layanan pesan (WhatsApp) mengenai penggunaan akun Anda. Kami tidak bertanggung jawab atas pemblokiran akun yang disebabkan oleh pelanggaran spam yang dilakukan oleh Anda sendiri.</p>
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer group mt-2">
                        <input type="checkbox" x-model="agreed" class="w-5 h-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                        <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors">Saya telah membaca dan menyetujui syarat & ketentuan layanan.</span>
                    </label>
                </section>
                
                <hr class="border-gray-200 my-10">

                <!-- 2. Pilih Paket -->
                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center text-sm">2</span> 
                        Pilih Paket Anda
                    </h2>
                    <p class="text-sm text-gray-500 mb-5">Pilih paket lisensi Anda. Pembayaran akan ditagih setelah masa uji coba berakhir melalui Dasbor.</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Trial -->
                        <div @click="package = 'trial'" :class="package === 'trial' ? 'border-brand-500 ring-1 ring-brand-500 bg-brand-50/50 shadow-md' : 'border-gray-200 hover:border-brand-300'" class="border rounded-xl p-5 cursor-pointer transition-all relative">
                            <span class="absolute top-0 right-0 -mt-2 -mr-2 bg-brand-100 text-brand-600 text-[10px] px-2 py-0.5 rounded-full border border-brand-200 font-bold">Gratis</span>
                            <h3 class="font-bold text-gray-900">Trial 7 Hari</h3>
                            <p class="text-xs text-brand-600 mt-1 font-semibold">Rp 0 / 7 Hari</p>
                            <p class="text-xs text-gray-400 mt-2">Akses penuh fitur Pro untuk dicoba.</p>
                        </div>
                        <!-- Starter -->
                        <div @click="package = 'starter'" :class="package === 'starter' ? 'border-brand-500 ring-1 ring-brand-500 bg-brand-50/50 shadow-md' : 'border-gray-200 hover:border-brand-300'" class="border rounded-xl p-5 cursor-pointer transition-all">
                            <h3 class="font-bold text-gray-900">Starter</h3>
                            <p class="text-xs text-gray-500 mt-1">Rp 99k / bln</p>
                            <p class="text-xs text-gray-400 mt-2">1 Agen, 500 AI calls.</p>
                        </div>
                        <!-- Pro -->
                        <div @click="package = 'pro'" :class="package === 'pro' ? 'border-brand-500 ring-1 ring-brand-500 bg-brand-50/50 shadow-md' : 'border-gray-200 hover:border-brand-300'" class="border rounded-xl p-5 cursor-pointer transition-all relative">
                            <span class="absolute top-0 right-0 -mt-2 -mr-2 bg-gray-900 text-white text-[10px] px-2 py-0.5 rounded-full font-bold">Laris</span>
                            <h3 class="font-bold text-gray-900">Pro</h3>
                            <p class="text-xs text-gray-500 mt-1">Rp 299k / bln</p>
                            <p class="text-xs text-gray-400 mt-2">5 Agen, Unlimited AI.</p>
                        </div>
                        <!-- Enterprise -->
                        <div @click="package = 'enterprise'" :class="package === 'enterprise' ? 'border-gray-900 ring-1 ring-gray-900 bg-gray-50 shadow-md' : 'border-gray-200 hover:border-gray-300'" class="border rounded-xl p-5 cursor-pointer transition-all">
                            <h3 class="font-bold text-gray-900">Enterprise</h3>
                            <p class="text-xs text-gray-500 mt-1">Harga Khusus</p>
                            <p class="text-xs text-gray-400 mt-2">Unlimited, Server Private.</p>
                        </div>
                    </div>
                </section>
                
                <hr class="border-gray-200 my-10">

                <!-- 3. Panduan Instalasi & Download -->
                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center text-sm">3</span> 
                        Panduan Penginstalan & Download
                    </h2>
                    
                    <div class="mb-6 bg-yellow-50/50 border border-yellow-100 p-5 rounded-xl">
                        <p class="text-sm font-semibold text-yellow-800 mb-2">Cara Memasang Ekstensi (Developer Mode):</p>
                        <ol class="list-decimal pl-5 text-sm text-yellow-700 space-y-1.5">
                            <li>Klik tombol Download di bawah ini dan <strong>ekstrak file</strong> <code>EmoText.zip</code> ke sebuah folder.</li>
                            <li>Buka Google Chrome lalu ketikkan <code>chrome://extensions/</code> di URL bar.</li>
                            <li>Aktifkan <strong>Developer mode</strong> (Mode Pengembang) di pojok kanan atas layar.</li>
                            <li>Klik tombol <strong>Load unpacked</strong> (Muat yang tidak dikemas) di pojok kiri atas.</li>
                            <li>Pilih folder EmoText yang sudah diekstrak tadi.</li>
                        </ol>
                    </div>

                    <div class="flex items-center gap-4 p-5 bg-gray-50 border border-gray-200 rounded-xl transition-all"
                         :class="(agreed && package) ? 'border-brand-300 bg-brand-50/30' : ''">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm">File Ekstensi Emotext-CRM</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Versi 1.0.0 | Ukuran: ~5MB</p>
                        </div>
                        <a 
                            @click="downloaded = true"
                            href="{{ route('download.extension') }}" 
                            :class="(!agreed || !package) ? 'bg-gray-400 opacity-50 cursor-not-allowed pointer-events-none grayscale' : 'bg-blue-600 hover:scale-105 shadow-md'"
                            class="inline-flex items-center justify-center rounded-full px-6 py-2.5 text-sm font-semibold text-white transition-all">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download .zip
                        </a>
                    </div>
                    
                    <div class="h-6 mt-2">
                        <p x-show="!agreed || !package" x-transition.opacity class="text-xs text-red-500 text-right font-medium">
                            * Silakan setujui syarat layanan dan pilih paket terlebih dahulu.
                        </p>
                        <p x-show="agreed && package && !downloaded" x-transition.opacity class="text-xs text-brand-600 text-right font-medium flex justify-end items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Siap untuk diunduh! Klik Download untuk melanjutkan.
                        </p>
                    </div>
                </section>
                
                <hr class="border-gray-100">

                <!-- 4. Buat Akun -->
                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 transition-all" :class="!downloaded ? 'opacity-40' : ''">
                        <span class="w-6 h-6 rounded-full bg-gray-900 text-white flex items-center justify-center text-sm">4</span> 
                        Buat Akun Anda
                    </h2>
                    
                    <div :class="!downloaded ? 'opacity-40 pointer-events-none grayscale' : 'animate-pulse bg-brand-50/30 p-6 border border-brand-200 rounded-xl'" class="transition-all p-4">
                        <p class="text-sm text-gray-600 mb-4">Setelah Ekstensi diunduh, Anda <strong>wajib mendaftar akun</strong>. Kredensial akun ini digunakan untuk <i>Login</i> di dalam WhatsApp Web agar ekstensi dapat terhubung dengan Dashboard.</p>
                        
                        <a :href="'{{ route('register') }}?plan=' + package" class="inline-flex items-center justify-center rounded-full bg-gray-900 px-8 py-3 text-sm font-semibold text-white transition-all hover:bg-brand-600 hover:scale-105 shadow-md">
                            Lanjut Mendaftar Akun &rarr;
                        </a>
                    </div>
                </section>
                
            </div>
        </div>
    </div>

</body>
</html>
