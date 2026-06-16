<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $period = $request->query('period', 'today');
        
        $baseQuery = $user->messages(); // Base isolasi data per perusahaan
        
        // Filter by Period
        if ($period === '7days') {
            $baseQuery->where('created_at', '>=', now()->subDays(7));
        } elseif ($period === 'month') {
            $baseQuery->where('created_at', '>=', now()->startOfMonth());
        } elseif ($period === 'all') {
            // no filter
        } else {
            $baseQuery->whereDate('created_at', today());
        }

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
        
        // Optimasi: Konsolidasi 9 Query menjadi 1 Query (Mencegah Lemot)
        $statsObj = (clone $baseQuery)
            ->selectRaw("
                COUNT(id) as total_messages,
                SUM(CASE WHEN sentiment='positive' THEN 1 ELSE 0 END) as positive_count,
                SUM(CASE WHEN sentiment='negative' THEN 1 ELSE 0 END) as negative_count,
                AVG(confidence) as avg_confidence,
                SUM(CASE WHEN intent='complaint' THEN 1 ELSE 0 END) as intent_complaint,
                SUM(CASE WHEN intent='order' THEN 1 ELSE 0 END) as intent_order,
                SUM(CASE WHEN intent='inquiry' THEN 1 ELSE 0 END) as intent_inquiry,
                SUM(CASE WHEN intent='media' THEN 1 ELSE 0 END) as intent_media,
                SUM(CASE WHEN intent='other' THEN 1 ELSE 0 END) as intent_other
            ")->first();

        $totalMessages  = $statsObj->total_messages ?? 0;
        $positiveCount  = $statsObj->positive_count ?? 0;
        $negativeCount  = $statsObj->negative_count ?? 0;
        $avgConfidence  = $statsObj->avg_confidence ?? 0;

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

        // ── 2b. Intent Distribution ────────────────────────────────────────────────
        $intentData = [
            $statsObj->intent_complaint ?? 0,
            $statsObj->intent_order ?? 0,
            $statsObj->intent_inquiry ?? 0,
            $statsObj->intent_media ?? 0,
            $statsObj->intent_other ?? 0,
        ];

        // ── 2c. Top Urgent Complaints ──────────────────────────────────────────────
        $urgentContactsQuery = (clone $baseQuery)
            ->where('status', 'pending')
            ->select('sender_id', DB::raw('MAX(sender_name) as sender_name'), DB::raw($caseSql), DB::raw('COUNT(id) as pending_msgs'))
            ->groupBy('sender_id')
            ->get();
        $urgentContacts = $urgentContactsQuery->where('health_score', '<', 50)->sortBy('health_score')->take(5);

        $driver = DB::connection()->getDriverName();
        if ($period === 'today') {
            $dateTruncRaw = $driver === 'sqlite' 
                ? "strftime('%Y-%m-%d %H:00:00', created_at) as time_label"
                : "DATE_TRUNC('hour', created_at) as time_label";
        } else {
            $dateTruncRaw = $driver === 'sqlite' 
                ? "strftime('%Y-%m-%d 00:00:00', created_at) as time_label"
                : "DATE_TRUNC('day', created_at) as time_label";
        }

        // ── 3. Line Chart (Tren waktu) ────────────────────────────
        $trendData = (clone $baseQuery)
            ->select(
                DB::raw($dateTruncRaw),
                DB::raw("SUM(CASE WHEN sentiment = 'positive' THEN 1 ELSE 0 END) as positive_count"),
                DB::raw("SUM(CASE WHEN sentiment = 'negative' THEN 1 ELSE 0 END) as negative_count"),
                DB::raw("SUM(CASE WHEN sentiment = 'neutral' THEN 1 ELSE 0 END) as neutral_count")
            )
            ->groupBy('time_label')
            ->orderBy('time_label')
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

        // ── 6. Data Knowledge Base (RAG) ─────────────────────────────────────
        $documents = DB::table('knowledge_bases')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($doc) {
                $meta = json_decode($doc->metadata, true) ?? [];
                return [
                    'id' => $doc->id,
                    'filename' => $meta['filename'] ?? 'document.txt',
                    'created_at' => strtotime($doc->created_at),
                    'chunks' => 1,
                    'uploaded_at' => $doc->created_at,
                ];
            });

        return view('dashboard', compact('stats', 'contactsPaginator', 'pieData', 'trendData', 'trialStatus', 'intentData', 'urgentContacts', 'period', 'documents'));
    }

    public function resolve(Request $request, $id)
    {
        $message = auth()->user()->messages()->findOrFail($id); // Pastikan hanya resolve milik sendiri
        $message->update([
            'status'      => 'resolved',
            'resolved_by' => auth()->id(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => 'Pesan berhasil ditandai sebagai selesai.']);
        }

        return back()->with('success', 'Pesan berhasil ditandai sebagai selesai.');
    }

    public function uploadKb(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120|mimetypes:application/pdf,text/plain,text/x-plain',
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();

        $content = '';
        if (strtolower($ext) === 'txt') {
            $content = file_get_contents($file->getRealPath());
        } elseif (strtolower($ext) === 'pdf') {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                $content = $pdf->getText();
                
                // Jika hasil parse kosong, set fallback
                if (trim($content) === '') {
                    $content = "Dokumen PDF berhasil diunggah, namun tidak ada teks yang bisa dibaca. Pastikan ini bukan dokumen hasil scan.";
                }
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal membaca isi PDF: ' . $e->getMessage());
            }
        }

        // Save directly to Supabase DB!
        DB::table('knowledge_bases')->insert([
            'user_id' => auth()->id(),
            'content' => $content,
            'metadata' => json_encode(['filename' => $filename]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Trigger FastAPI to rebuild FAISS index from DB
        try {
            // BUGFIX: Ubah timeout menjadi 1 detik agar tidak blocking UI Laravel. FastAPI akan terus jalan di background.
            \Illuminate\Support\Facades\Http::timeout(1)
                ->withHeaders([
                    'X-Internal-Api-Key' => env('INTERNAL_API_KEY', 'emotext_secret_internal_key_2026')
                ])
                ->post(env('FASTAPI_URL', 'http://127.0.0.1:8000') . '/sync-kb');
        } catch (\Exception $e) {
            // It's okay if FastAPI is offline or times out, it will sync on next startup or run in background
        }

        return back()->with('success', "Dokumen '{$filename}' berhasil disimpan secara permanen di Database.");
    }

    // Method knowledgeBase() telah dihapus dan digabungkan ke index()

    public function deleteKb(Request $request, $id)
    {
        // $id di sini adalah knowledge_base.id
        $kb = DB::table('knowledge_bases')->where('id', $id)->where('user_id', auth()->id())->first();
        
        if ($kb) {
            DB::table('knowledge_bases')->where('id', $id)->delete();
            
            // Beri sinyal ke FastAPI
            try {
                // BUGFIX: Ubah timeout menjadi 1 detik agar tidak blocking UI Laravel
                \Illuminate\Support\Facades\Http::timeout(1)
                    ->withHeaders([
                        'X-Internal-Api-Key' => env('INTERNAL_API_KEY', 'emotext_secret_internal_key_2026')
                    ])
                    ->post(env('FASTAPI_URL', 'http://127.0.0.1:8000') . '/sync-kb');
            } catch (\Exception $e) {}

            $meta = json_decode($kb->metadata, true);
            $filename = $meta['filename'] ?? 'Dokumen';
            return back()->with('success', "Dokumen '{$filename}' berhasil dihapus dari Database.");
        }

        return back()->with('error', 'Gagal menghapus dokumen atau dokumen tidak ditemukan.');
    }

    public function exportCsv(Request $request)
    {
        $user = auth()->user();
        $period = $request->query('period', 'today');

        $query = $user->messages();
        if ($period === '7days') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($period === 'month') {
            $query->where('created_at', '>=', now()->startOfMonth());
        } elseif ($period === 'all') {
            // no filter
        } else {
            $query->whereDate('created_at', today());
        }

        $messages = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Laporan_EmoText_{$period}.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Waktu', 'Pengirim', 'Pesan', 'Sentimen', 'Confidence', 'Intensi', 'Status'];

        $callback = function() use($messages, $columns) {
            $file = fopen('php://output', 'w');
            // Menambahkan BOM untuk kompatibilitas Excel pada UTF-8
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);

            foreach ($messages as $msg) {
                fputcsv($file, [
                    $msg->id,
                    $msg->created_at->format('Y-m-d H:i:s'),
                    $msg->sender_name ?? $msg->sender_id,
                    $msg->message,
                    strtoupper($msg->sentiment),
                    $msg->confidence,
                    strtoupper($msg->intent),
                    strtoupper($msg->status)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}