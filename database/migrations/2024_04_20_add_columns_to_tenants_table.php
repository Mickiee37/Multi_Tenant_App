<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('tenants', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'domain')) {
                $table->string('domain')->unique();
            }
            if (!Schema::hasColumn('tenants', 'database')) {
                $table->string('database')->unique();
            }
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['name', 'domain', 'database']);
        });
    }
}; 