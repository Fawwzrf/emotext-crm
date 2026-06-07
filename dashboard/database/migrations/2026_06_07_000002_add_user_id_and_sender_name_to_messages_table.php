<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // FK ke users — isolasi data per perusahaan
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            // Nama pengirim (sender_name dari extension)
            $table->string('sender_name')->nullable()->after('sender_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'sender_name']);
        });
    }
};
