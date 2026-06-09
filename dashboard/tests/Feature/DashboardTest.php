<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_without_n_plus_1_problem()
    {
        $user = User::factory()->create([
            'subscription_status' => 'active',
            'company_name' => 'PT Testing'
        ]);
        
        // Buat 50 pesan dummy (campuran sentimen)
        Message::factory()->count(50)->create([
            'user_id' => $user->id,
            'status' => 'resolved',
            'resolved_by' => $user->id // Trigger relasi resolver
        ]);

        // Aktifkan query log
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get('/dashboard');
        
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        
        // Query biasanya: 
        // 1. Session / Auth
        // 2. Count Total
        // 3. Positive Count
        // 4. Negative Count
        // 5. Avg Confidence
        // 6. Neutral Count
        // 7. Trend groupBy
        // 8. Paginate Count
        // 9. Fetch Messages (paginate 10)
        // 10. Eager Load Resolver User (1 query IN)
        // Total query harusnya sedikit (sekitar 10-15), bukan 50+.
        $this->assertLessThan(20, count($queries), "Terlalu banyak query dieksekusi! N+1 problem terdeteksi.");
    }

    public function test_contact_crm_view_shows_correct_health_score()
    {
        $user = User::factory()->create([
            'subscription_status' => 'active',
            'company_name' => 'PT Testing'
        ]);

        // Buat 1 pesan marah
        $pesanMarah = Message::factory()->create([
            'user_id' => $user->id,
            'sender_id' => 'Pelanggan 1',
            'sentiment' => 'negative',
            'message' => 'Pesan marah dummy 123'
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        
        $response->assertStatus(200);
        
        // Assert pesan marah terlihat di layar di bawah nama kontak
        $response->assertSee('Pesan marah dummy 123');
        
        // Assert health score dari contact ini muncul sebagai "At Risk" (di bawah 50%)
        $response->assertSee('(At Risk)');
    }
}
