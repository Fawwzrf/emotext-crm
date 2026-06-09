<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = DB::table('users')->whereNotNull('api_token')->get();
        foreach ($users as $user) {
            // Jika token sudah berupa SHA-256 (64 karakter hex), skip.
            // Plain token kita panjangnya 48 + prefix "EMOTEXT_" = 56 chars.
            if (strlen($user->api_token) !== 64) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['api_token' => hash('sha256', $user->api_token)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hash bersifat satu arah (one-way), jadi tidak bisa di-reverse ke plain text.
    }
};
