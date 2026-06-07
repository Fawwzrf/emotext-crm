<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('api_token', 80)->unique()->nullable()->after('company_name');
            $table->string('subscription_status')->default('trial')->after('api_token');
            $table->timestamp('trial_started_at')->nullable()->after('subscription_status');
            $table->timestamp('trial_ends_at')->nullable()->after('trial_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name', 'api_token', 'subscription_status',
                'trial_started_at', 'trial_ends_at',
            ]);
        });
    }
};
