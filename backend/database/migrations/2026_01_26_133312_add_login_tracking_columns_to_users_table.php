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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('login_count')->default(0)->after('last_active');
            $table->timestamp('last_login_at')->nullable()->after('login_count');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->string('linkedin')->nullable()->after('last_login_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_count', 'last_login_at', 'last_login_ip', 'linkedin']);
        });
    }
};
