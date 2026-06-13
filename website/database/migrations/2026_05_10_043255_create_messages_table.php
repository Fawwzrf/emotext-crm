<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->string('sender_id');   // ID dari WA Web [cite: 37]
        $table->text('message');       // Teks pesan asli [cite: 38]
        $table->string('sentiment');    // positive|neutral|negative [cite: 41]
        $table->string('intent');       // inquiry|complaint|order|other [cite: 42]
        $table->float('confidence');    // Skor akurasi BERT [cite: 43]
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
