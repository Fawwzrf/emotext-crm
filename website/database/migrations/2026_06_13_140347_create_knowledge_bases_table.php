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
        // Aktifkan ekstensi pgvector di PostgreSQL (Supabase)
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');

        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index(); // ID Perusahaan
            $table->text('content'); // Konten teks dokumen
            $table->jsonb('metadata')->nullable(); // Metadata: filename, page, dsb
            // Kolom vector tidak didukung bawaan oleh blueprint dasar laravel,
            // jadi kita gunakan statement DB raw untuk menambahkannya.
            $table->timestamps();
        });

        // Tambahkan kolom vector dengan dimensi 384 (model MiniLM)
        DB::statement('ALTER TABLE knowledge_bases ADD COLUMN embedding vector(384);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};
