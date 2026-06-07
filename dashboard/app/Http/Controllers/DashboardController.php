<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = $user->messages(); // Isolasi data per perusahaan

        // ── 1. Stat Cards ────────────────────────────────────────────────────
        $totalMessages  = $query->count();
        $positiveCount  = $query->where('sentiment', 'positive')->count();
        $negativeCount  = $query->where('sentiment', 'negative')->count();
        $avgConfidence  = $query->avg('confidence') ?? 0;

        $stats = [
            'total_processed' => $totalMessages,
            'avg_positive'    => $totalMessages > 0 ? round(($positiveCount / $totalMessages) * 100, 1) : 0,
            'avg_negative'    => $totalMessages > 0 ? round(($negativeCount / $totalMessages) * 100, 1) : 0,
            'avg_confidence'  => round($avgConfidence * 100, 1),
        ];

        // ── 2. Pie Chart (Distribusi Sentimen) ───────────────────────────────
        $neutralCount = $query->where('sentiment', 'neutral')->count();
        $pieData = [$positiveCount, $negativeCount, $neutralCount];

        // ── 3. Line Chart (Tren per jam hari ini) ────────────────────────────
        $trendData = $user->messages()
            ->select(
                DB::raw('count(*) as aggregate'),
                DB::raw("DATE_TRUNC('hour', created_at) as hour")
            )
            ->whereDate('created_at', today())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // ── 4. Pesan Terbaru dengan Pagination ───────────────────────────────
        $messages = $user->messages()->latest()->paginate(10);

        $messages->getCollection()->transform(function ($msg) {
            $msg->reply_suggestion = $this->getReplyTemplate($msg->intent);

            $cleanPhone = preg_replace('/[^0-9]/', '', $msg->sender_id);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62' . substr($cleanPhone, 1);
            }
            $msg->clean_phone = $cleanPhone;

            return $msg;
        });

        // ── 5. Status Trial ──────────────────────────────────────────────────
        $trialStatus = [
            'status'       => $user->subscription_status,
            'is_active'    => $user->isActive(),
            'days_left'    => $user->trialDaysLeft(),
            'ends_at'      => $user->trial_ends_at,
            'company_name' => $user->company_name,
        ];

        return view('dashboard', compact('stats', 'messages', 'pieData', 'trendData', 'trialStatus'));
    }

    public function resolve($id)
    {
        $message = auth()->user()->messages()->findOrFail($id); // Pastikan hanya resolve milik sendiri
        $message->update([
            'status'      => 'resolved',
            'resolved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Pesan berhasil ditandai sebagai selesai.');
    }

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