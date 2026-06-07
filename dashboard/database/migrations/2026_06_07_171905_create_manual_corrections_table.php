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
        Schema::create('manual_corrections', function (Blueprint $table) {
            $table->id();
            $table->text('message_text');
            $table->string('original_sentiment')->nullable();
            $table->string('corrected_sentiment')->nullable();
            $table->string('original_intent')->nullable();
            $table->string('corrected_intent')->nullable();
            $table->string('admin_id');
            // We omit timestamps() because SQLAlchemy's model doesn't define created_at/updated_at for this
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_corrections');
    }
};
