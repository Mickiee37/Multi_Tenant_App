<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'domain')) {
                $table->string('domain')->unique()->after('id');
            }
            if (!Schema::hasColumn('tenants', 'database_name')) {
                $table->string('database_name')->unique()->after('domain')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['domain', 'database_name']);
        });
    }
}; 