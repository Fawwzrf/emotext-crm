<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman utama Dashboard dengan statistik dan data pesan.
     */
    public function index()
    {
        // 1. Ambil Statistik Utama (Stat Cards)
        $totalMessages = Message::count();

        $positiveCount = Message::where('sentiment', 'positive')->count();
        $avgPositive = $totalMessages > 0 ? round(($positiveCount / $totalMessages) * 100, 1) : 0;

        $negativeCount = Message::where('sentiment', 'negative')->count();
        $avgNegative = $totalMessages > 0 ? round(($negativeCount / $totalMessages) * 100, 1) : 0;

        $avgConfidence = Message::avg('confidence') ?? 0;
        $avgConfidencePercent = round($avgConfidence * 100, 1);

        $stats = [
            'total_processed' => $totalMessages,
            'avg_positive' => $avgPositive,
            'avg_negative' => $avgNegative,
            'avg_confidence' => $avgConfidencePercent,
        ];

        // 2. Data untuk Pie Chart (Distribusi Sentimen)
        $pieData = [
            $positiveCount,
            $negativeCount,
            Message::where('sentiment', 'neutral')->count(),
        ];

        // 3. Data untuk Line Chart (Tren per jam - PostgreSQL format)
        $trendData = Message::select(
            DB::raw('count(*) as aggregate'),
            DB::raw("DATE_TRUNC('hour', created_at) as hour")
        )
        ->whereDate('created_at', today())
        ->groupBy('hour')
        ->orderBy('hour')
        ->get();

        // 4. Ambil Data Pesan Terbaru dengan Pagination
        $messages = Message::latest()->paginate(10);

        // 5. Transformasi Data: Menambahkan Saran Balasan & Logic WhatsApp Redirect
        $messages->getCollection()->transform(function ($msg) {
            // A. Set Template Balasan berdasarkan Intent
            $msg->reply_suggestion = $this->getReplyTemplate($msg->intent);
            
            // B. Logic WhatsApp Redirect: Bersihkan nomor telepon
            $cleanPhone = preg_replace('/[^0-9]/', '', $msg->sender_id);
            
            // Konversi awalan 08 menjadi 62 (Standar Internasional WA)
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62' . substr($cleanPhone, 1);
            }
            
            $msg->clean_phone = $cleanPhone;
            
            return $msg;
        });

        return view('dashboard', compact('stats', 'messages', 'pieData', 'trendData'));
    }

    /**
     * Menandai pesan sebagai selesai (Resolved) dan mencatat admin pengolah.
     * Fitur Audit Log Keamanan.
     */
    public function resolve($id)
    {
        $message = Message::findOrFail($id);
        
        // Mencatat siapa yang memproses (Audit Log)
        $message->update([
            'status' => 'resolved',
            'resolved_by' => auth()->id(), // Mengambil ID admin yang sedang login
        ]);

        return back()->with('success', 'Pesan berhasil ditandai sebagai selesai.');
    }

    /**
     * Helper: Template Balasan Otomatis.
     */
    private function getReplyTemplate($intent)
    {
        return match($intent) {
            'complaint' => "Mohon maaf atas ketidaknyamanannya. Keluhan Anda sedang kami proses oleh tim teknis. Mohon tunggu update selanjutnya.",
            'order'     => "Terima kasih atas pesanannya! Mohon kirimkan format alamat lengkap untuk kami hitung total ongkos kirimnya.",
            'inquiry'   => "Halo! Terima kasih sudah bertanya. Untuk informasi lebih lanjut mengenai layanan kami, Anda bisa cek di website resmi kami.",
            default     => "Halo, terima kasih telah menghubungi kami. Ada yang bisa kami bantu?",
        };
    }
}