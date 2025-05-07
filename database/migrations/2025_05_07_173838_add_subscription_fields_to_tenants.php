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
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'subscription_plan')) {
                $table->string('subscription_plan')->default('basic')->nullable();
            }
            
            if (!Schema::hasColumn('tenants', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'subscription_expires_at']);
        });
    }
};
