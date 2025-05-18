<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First, update any existing records to prevent data loss
        DB::table('tenants')->update([
            'data' => DB::raw("JSON_SET(
                COALESCE(data, '{}'),
                '$.subscription_plan', subscription_plan,
                '$.subscription_expires_at', subscription_expires_at
            )")
        ]);

        // Now remove the columns
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'subscription_plan')) {
                $table->dropColumn('subscription_plan');
            }
            if (Schema::hasColumn('tenants', 'subscription_expires_at')) {
                $table->dropColumn('subscription_expires_at');
            }
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'subscription_plan')) {
                $table->string('subscription_plan')->default('basic');
            }
            if (!Schema::hasColumn('tenants', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable();
            }
        });

        // Restore data from JSON if it exists
        DB::table('tenants')->update([
            'subscription_plan' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.subscription_plan'))"),
            'subscription_expires_at' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.subscription_expires_at'))")
        ]);
    }
}; 