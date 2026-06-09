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
        $baseQuery = $user->messages(); // Base isolasi data per perusahaan

        // ── 1. Stat Cards (Berbasis Kontak & Pesan) ───────────────────────────
        $caseSql = "SUM(CASE WHEN sentiment='positive' THEN 100 WHEN sentiment='negative' THEN 30 ELSE 70 END) / COUNT(id) as health_score";
        
        $allContacts = (clone $baseQuery)
            ->select('sender_id', DB::raw("COUNT(id) as total_msgs"), DB::raw($caseSql))
            ->groupBy('sender_id')
            ->get();

        $totalContacts = $allContacts->count();
        $positiveContacts = $allContacts->where('health_score', '>=', 80)->count();
        $negativeContacts = $allContacts->where('health_score', '<', 50)->count();
        $neutralContacts = $totalContacts - $positiveContacts - $negativeContacts;
        
        // Original Message Stats
        $totalMessages  = (clone $baseQuery)->count();
        $positiveCount  = (clone $baseQuery)->where('sentiment', 'positive')->count();
        $negativeCount  = (clone $baseQuery)->where('sentiment', 'negative')->count();
        $avgConfidence  = (clone $baseQuery)->avg('confidence') ?? 0;

        $stats = [
            'total_contacts'    => $totalContacts,
            'positive_contacts' => $positiveContacts,
            'negative_contacts' => $negativeContacts,
            'neutral_contacts'  => $neutralContacts,
            'total_processed'   => $totalMessages,
            'avg_positive'      => $totalMessages > 0 ? round(($positiveCount / $totalMessages) * 100, 1) : 0,
            'avg_negative'      => $totalMessages > 0 ? round(($negativeCount / $totalMessages) * 100, 1) : 0,
            'avg_confidence'    => round($avgConfidence * 100, 1),
        ];

        // ── 2. Pie Chart (Distribusi Sentimen per Pesan) ───────────────────────────────
        $neutralCount = $totalMessages - $positiveCount - $negativeCount;
        $pieData = [$positiveCount, $negativeCount, $neutralCount];

        $driver = DB::connection()->getDriverName();
        $dateTruncRaw = $driver === 'sqlite' 
            ? "strftime('%Y-%m-%d %H:00:00', created_at) as hour"
            : "DATE_TRUNC('hour', created_at) as hour";

        // ── 3. Line Chart (Tren per jam hari ini) ────────────────────────────
        $trendData = $user->messages()
            ->select(
                DB::raw($dateTruncRaw),
                DB::raw("SUM(CASE WHEN sentiment = 'positive' THEN 1 ELSE 0 END) as positive_count"),
                DB::raw("SUM(CASE WHEN sentiment = 'negative' THEN 1 ELSE 0 END) as negative_count"),
                DB::raw("SUM(CASE WHEN sentiment = 'neutral' THEN 1 ELSE 0 END) as neutral_count")
            )
            ->whereDate('created_at', today())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // ── 4. Daftar Kontak (CRM View) & Pesannya ───────────────────────────
        $contactsPaginator = $user->messages()
            ->select(
                'sender_id', 
                DB::raw('MAX(sender_name) as sender_name'), 
                DB::raw('MAX(created_at) as last_interaction'),
                DB::raw("COUNT(id) as total_msgs"),
                DB::raw($caseSql)
            )
            ->groupBy('sender_id')
            ->orderBy('last_interaction', 'desc')
            ->paginate(10)
            ->withQueryString();

        $senderIds = $contactsPaginator->pluck('sender_id');
        $messagesList = $user->messages()
            ->with('resolver')
            ->whereIn('sender_id', $senderIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('sender_id');

        $contactsPaginator->getCollection()->transform(function ($contact) use ($messagesList) {
            $contact->messages = $messagesList->get($contact->sender_id, collect());
            
            foreach ($contact->messages as $msg) {
                $msg->reply_suggestion = $msg->suggestion ?? 'Belum ada saran balasan.';
                $cleanPhone = preg_replace('/[^0-9]/', '', $msg->sender_id);
                if (str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = '62' . substr($cleanPhone, 1);
                }
                $msg->clean_phone = $cleanPhone;
            }
            return $contact;
        });

        // ── 5. Status Trial ──────────────────────────────────────────────────
        $trialStatus = [
            'status'       => $user->subscription_status,
            'is_active'    => $user->isActive(),
            'days_left'    => $user->trialDaysLeft(),
            'ends_at'      => $user->trial_ends_at,
            'company_name' => $user->company_name,
        ];

        return view('dashboard', compact('stats', 'contactsPaginator', 'pieData', 'trendData', 'trialStatus'));
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

}