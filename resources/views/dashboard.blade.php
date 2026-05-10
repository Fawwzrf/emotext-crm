<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sentiment Analysis Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ tab: 'sample' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-green-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">Avg Positive</p>
                        <h3 class="text-xl font-bold text-green-600">{{ $stats['avg_positive'] }}%</h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-red-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">Avg Negative</p>
                        <h3 class="text-xl font-bold text-red-600">{{ $stats['avg_negative'] }}%</h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-gray-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">Avg Confidence</p>
                        <h3 class="text-xl font-bold text-gray-700">{{ $stats['avg_confidence'] }}%</h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center h-24">
                    <div class="p-2 bg-blue-50 rounded-xl mr-3">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium leading-none mb-1">Processed</p>
                        <h3 class="text-xl font-bold text-gray-700">{{ number_format($stats['total_processed']) }}</h3>
                    </div>
                </div>
            </div>

            <div class="bg-gray-200/50 p-1 rounded-xl inline-flex mb-8 w-full md:w-auto">
                <button class="px-6 py-2 text-sm font-medium text-gray-500 flex items-center opacity-50 cursor-not-allowed"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Text Analyzer</button>
                <button class="px-6 py-2 text-sm font-medium text-gray-500 flex items-center opacity-50 cursor-not-allowed"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg> Batch Upload</button>
                
                <button @click="tab = 'sample'" 
                    :class="tab === 'sample' ? 'bg-white text-gray-900 shadow-sm font-bold' : 'text-gray-700 font-medium'"
                    class="px-6 py-2 text-sm rounded-lg flex items-center transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Sample Data
                </button>

                <button @click="tab = 'analytics'" 
                    :class="tab === 'analytics' ? 'bg-white text-gray-900 shadow-sm font-bold' : 'text-gray-700 font-medium'"
                    class="px-6 py-2 text-sm rounded-lg flex items-center transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg> Analytics
                </button>
            </div>

            <div x-show="tab === 'analytics'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Sentiment Trend (Today)</h3>
                    <div class="h-64"><canvas id="trendChart"></canvas></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Current Sentiment Distribution</h3>
                    <div class="h-64 flex justify-center"><canvas id="distributionChart"></canvas></div>
                </div>
            </div>

            <div x-show="tab === 'sample'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95">
                <h3 class="text-xl font-bold text-gray-800">Sample Data Exploration</h3>
                <p class="text-sm text-gray-500 mb-6">Explore pre-analyzed customer reviews and social media posts to understand sentiment patterns and see the analysis system in action.</p>

                <div class="space-y-4">
                    @forelse($messages as $msg)
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative" x-data="{ showReply: false }">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center space-x-3">
                                    <span class="px-3 py-1 bg-gray-50 border border-gray-100 text-[11px] rounded-lg font-bold text-gray-600 uppercase">{{ $msg->intent }}</span>
                                    <span class="text-xs text-gray-400">{{ $msg->created_at->format('Y-m-d') }}</span>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="px-4 py-1 rounded-lg text-[11px] font-black tracking-widest {{ $msg->sentiment == 'positive' ? 'bg-black text-white' : ($msg->sentiment == 'negative' ? 'bg-red-500 text-white' : 'bg-gray-400 text-white') }}">
                                        {{ strtoupper($msg->sentiment) }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 mt-1 font-semibold">{{ number_format($msg->confidence * 100, 1) }}% confidence</span>
                                </div>
                            </div>

                            <p class="text-gray-700 text-sm mb-6 leading-relaxed font-medium">
                                {{ $msg->message }}
                            </p>

                            <div class="flex justify-between items-center pt-4 border-t border-gray-50 relative">
                                <div class="flex items-center space-x-6 text-[12px] font-bold">
                                    <span class="text-[#22c55e] flex items-center">
                                        Positive: <span class="ml-1 opacity-90">{{ $msg->sentiment == 'positive' ? round($msg->confidence * 100) : '0' }}%</span>
                                    </span>
                                    <span class="text-[#ef4444] flex items-center">
                                        Negative: <span class="ml-1 opacity-90">{{ $msg->sentiment == 'negative' ? round($msg->confidence * 100) : '0' }}%</span>
                                    </span>
                                    <span class="text-gray-400 flex items-center">
                                        Neutral: <span class="ml-1 opacity-90">23%</span>
                                    </span>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <button @click="showReply = true" class="px-4 py-1.5 bg-indigo-50 border border-indigo-100 rounded-lg text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition">
                                        Generate Reply
                                    </button>
                                    <button class="px-4 py-1.5 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 transition font-medium">
                                        View Details
                                    </button>
                                </div>

                                <div x-show="showReply" x-cloak @click.away="showReply = false" 
                                    class="absolute z-50 bottom-16 right-0 w-80 bg-white border border-indigo-100 shadow-xl rounded-xl p-4 transition-all">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest">Saran Balasan ({{ $msg->intent }})</span>
                                        <button @click="showReply = false" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                                    </div>
                                    <p class="text-[11px] text-gray-600 italic mb-4 leading-relaxed">"{{ $msg->reply_suggestion }}"</p>
                                    
                                    <div class="flex items-center space-x-2 mt-4">
                                        <a href="https://wa.me/{{ $msg->clean_phone }}?text={{ rawurlencode($msg->reply_suggestion) }}" 
                                           target="_blank" 
                                           class="flex-[2] inline-flex items-center justify-center bg-white border border-gray-200 rounded-lg py-2 px-3 hover:bg-gray-50 transition shadow-sm">
                                            <svg class="w-4 h-4 mr-1.5 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.445 0 .081 5.391.079 11.99c0 2.112.552 4.175 1.598 5.987L0 24l6.149-1.613a11.78 11.78 0 005.894 1.57h.005c6.604 0 11.967-5.391 11.97-11.99a11.812 11.812 0 00-3.483-8.47z"/>
                                            </svg>
                                            <span class="text-[10px] font-bold text-[#25D366]">Kirim</span>
                                        </a>

                                        <button @click="navigator.clipboard.writeText('{{ $msg->reply_suggestion }}'); alert('Copied!');" 
                                            class="flex-1 bg-gray-100 text-gray-700 py-2 px-3 rounded-lg text-[10px] font-bold hover:bg-gray-200 transition shadow-sm border border-gray-200">
                                            Salin Teks
                                        </button>
                                    </div>
                                    
                                    <form action="{{ route('messages.resolve', $msg->id) }}" method="POST" class="mt-2">
                                        @csrf
                                        @patch
                                        <button type="submit" 
                                            class="w-full py-2 bg-gray-800 text-white rounded-lg text-[10px] font-bold hover:bg-black transition">
                                            Tandai Selesai (Resolve)
                                        </button>
                                    </form>

                                    @if($msg->status == 'resolved')
                                        <p class="text-[9px] text-green-600 mt-2 font-bold italic">
                                            ✓ Diselesaikan oleh: {{ $msg->resolver->name ?? 'Admin' }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center bg-white rounded-2xl border border-dashed text-gray-500 italic">
                            No data available.
                        </div>
                    @endforelse
                </div>
                <div class="mt-6">{{ $messages->links() }}</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Trend Chart
            new Chart(document.getElementById('trendChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($trendData->pluck('hour')->map(fn($h) => \Carbon\Carbon::parse($h)->format('H:i'))) !!},
                    datasets: [{
                        label: 'Messages',
                        data: {!! json_encode($trendData->pluck('aggregate')) !!},
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Distribution Chart
            new Chart(document.getElementById('distributionChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['Positive', 'Negative', 'Neutral'],
                    datasets: [{
                        data: {!! json_encode($pieData) !!},
                        backgroundColor: ['#22c55e', '#ef4444', '#64748b']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        });
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>