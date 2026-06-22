<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    // ─── 1. Dashboard Overview ────────────────────────────────────────────────

    public function dashboard()
    {
        // KPI Users
        $totalUsers    = User::where('is_superadmin', false)->count();
        $trialUsers    = User::where('is_superadmin', false)->where('subscription_status', 'trial')->count();
        $activeUsers   = User::where('is_superadmin', false)->where('subscription_status', 'active')->count();
        $expiredUsers  = User::where('is_superadmin', false)->where('subscription_status', 'expired')->count();

        // Revenue Estimasi (statis berdasarkan paket)
        // Asumsikan active = Pro (Rp299k) dan trial = 0. Ini dapat dikembangkan jika ada kolom plan.
        $estimatedRevenue = $activeUsers * 299000;

        // Alert: Trial akan berakhir dalam 3 hari
        $expiringTrials = User::where('is_superadmin', false)
            ->where('subscription_status', 'trial')
            ->where('trial_ends_at', '>=', now())
            ->where('trial_ends_at', '<=', now()->addDays(3))
            ->orderBy('trial_ends_at')
            ->get();

        // Grafik Pertumbuhan User (30 hari terakhir)
        $userGrowth = User::where('is_superadmin', false)
            ->where('created_at', '>=', now()->subDays(29))
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM-DD') as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Isi hari yang kosong dengan 0
        $growthLabels = [];
        $growthData   = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $growthLabels[] = now()->subDays($i)->format('d M');
            $growthData[]   = $userGrowth[$day] ?? 0;
        }

        // Total Pesan Hari Ini (Semua User)
        $todayMessages = DB::table('messages')->whereDate('created_at', today())->count();
        $totalMessages = DB::table('messages')->count();

        // Distribusi Sentimen (Global)
        $sentimentStats = DB::table('messages')
            ->selectRaw("sentiment, COUNT(*) as count")
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment');

        // Distribusi Intent (Global)
        $intentStats = DB::table('messages')
            ->selectRaw("intent, COUNT(*) as count")
            ->groupBy('intent')
            ->pluck('count', 'intent');

        return view('superadmin.dashboard', compact(
            'totalUsers', 'trialUsers', 'activeUsers', 'expiredUsers',
            'estimatedRevenue', 'expiringTrials',
            'growthLabels', 'growthData',
            'todayMessages', 'totalMessages',
            'sentimentStats', 'intentStats'
        ));
    }

    // ─── 2. Manajemen User ────────────────────────────────────────────────────

    public function users(Request $request)
    {
        $query = User::where('is_superadmin', false);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('email', 'ilike', '%' . $request->search . '%')
                  ->orWhere('company_name', 'ilike', '%' . $request->search . '%');
            });
        }

        if ($request->status && $request->status !== 'all') {
            $query->where('subscription_status', $request->status);
        }

        $users = $query->withCount('messages')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('superadmin.users', compact('users'));
    }

    public function extendTrial(Request $request, User $user)
    {
        $days = $request->input('days', 7);
        $user->update([
            'subscription_status' => 'trial',
            'trial_ends_at'       => ($user->trial_ends_at && $user->trial_ends_at->isFuture())
                ? $user->trial_ends_at->addDays($days)
                : now()->addDays($days),
        ]);

        return back()->with('success', "Trial {$user->name} diperpanjang {$days} hari.");
    }

    public function changeStatus(Request $request, User $user)
    {
        $request->validate(['status' => 'required|in:trial,starter,pro,enterprise,expired,inactive']);
        $user->update(['subscription_status' => $request->status]);

        return back()->with('success', "Status {$user->name} diubah menjadi '{$request->status}'.");
    }

    public function resetToken(User $user)
    {
        $user->update(['api_token' => User::generateApiToken()]);
        return back()->with('success', "API Token {$user->name} berhasil direset.");
    }

    public function destroy(User $user)
    {
        // Hapus data terkait (opsional, tergantung CASCADE di DB)
        DB::table('messages')->where('user_id', $user->id)->delete();
        DB::table('knowledge_bases')->where('user_id', $user->id)->delete();
        
        $user->delete();
        
        return back()->with('success', "Pelanggan {$user->name} dan datanya berhasil dihapus permanen.");
    }

    public function exportUsers()
    {
        $users = User::where('is_superadmin', false)
            ->withCount('messages')
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="users-emotext.csv"'];
        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Nama', 'Email', 'Perusahaan', 'Status', 'Sisa Trial', 'Total Pesan', 'Tgl Daftar']);
            foreach ($users as $u) {
                fputcsv($file, [
                    $u->id, $u->name, $u->email, $u->company_name,
                    $u->subscription_status, $u->trialDaysLeft(),
                    $u->messages_count, $u->created_at->format('d/m/Y'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─── 3. Feedback & Koreksi AI ────────────────────────────────────────────

    public function feedback(Request $request)
    {
        $query = DB::table('manual_corrections')
            ->leftJoin('users', 'manual_corrections.admin_id', '=', DB::raw('users.id::text'))
            ->select(
                'manual_corrections.*',
                'users.name as user_name',
                'users.company_name'
            );

        if ($request->user_id) {
            $query->where('manual_corrections.admin_id', (string) $request->user_id);
        }

        $corrections = $query->orderByDesc('manual_corrections.created_at')->paginate(25);

        // Statistik Akurasi AI
        $totalCorrections = DB::table('manual_corrections')->count();
        $totalMessages    = DB::table('messages')->count();
        $accuracyRate     = $totalMessages > 0
            ? round((1 - ($totalCorrections / max($totalMessages, 1))) * 100, 1)
            : 100;

        $allUsers = User::where('is_superadmin', false)->orderBy('name')->get(['id', 'name', 'company_name']);

        return view('superadmin.feedback', compact('corrections', 'totalCorrections', 'accuracyRate', 'allUsers'));
    }

    public function productFeedbacks(Request $request)
    {
        $query = \App\Models\ProductFeedback::with('user')->orderBy('created_at', 'desc');
        
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $feedbacks = $query->paginate(20);

        return view('superadmin.product_feedbacks', compact('feedbacks'));
    }

    public function markFeedbackRead($id)
    {
        $feedback = \App\Models\ProductFeedback::findOrFail($id);
        $feedback->update(['status' => 'read']);
        return back()->with('success', 'Pesan ditandai sudah dibaca.');
    }

    // ─── 4. Analytics AI & RAG ────────────────────────────────────────────────

    public function analytics()
    {
        // Top 10 User Paling Aktif
        $topUsers = User::where('is_superadmin', false)
            ->withCount('messages')
            ->orderByDesc('messages_count')
            ->limit(10)
            ->get();

        // Dokumen RAG per User
        $ragStats = DB::table('knowledge_bases')
            ->join('users', 'knowledge_bases.user_id', '=', 'users.id')
            ->selectRaw('users.name, users.company_name, COUNT(knowledge_bases.id) as doc_count')
            ->groupBy('users.id', 'users.name', 'users.company_name')
            ->orderByDesc('doc_count')
            ->get();

        // Volume Pesan per Hari (7 hari terakhir)
        $dailyVolume = DB::table('messages')
            ->where('created_at', '>=', now()->subDays(6))
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM-DD') as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $volumeLabels = [];
        $volumeData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $volumeLabels[] = now()->subDays($i)->format('D d/M');
            $volumeData[]   = $dailyVolume[$day] ?? 0;
        }

        // Global Sentiment & Intent Distribution
        $sentimentDist = DB::table('messages')
            ->selectRaw("sentiment, COUNT(*) as count")
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment');

        $intentDist = DB::table('messages')
            ->selectRaw("intent, COUNT(*) as count")
            ->groupBy('intent')
            ->pluck('count', 'intent');

        // Average Confidence
        $avgConfidence = DB::table('messages')->avg('confidence');

        return view('superadmin.analytics', compact(
            'topUsers', 'ragStats',
            'volumeLabels', 'volumeData',
            'sentimentDist', 'intentDist', 'avgConfidence'
        ));
    }

    // ─── 5. Settings ─────────────────────────────────────────────────────────

    public function settings()
    {
        $superadmins = User::where('is_superadmin', true)->get();
        return view('superadmin.settings', compact('superadmins'));
    }
}
