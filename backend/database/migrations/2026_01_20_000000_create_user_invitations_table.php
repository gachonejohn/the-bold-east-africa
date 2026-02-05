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
        Schema::create('user_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('role')->default('Contributor');
            $table->string('otp_code', 8);
            $table->string('otp_hash');
            $table->timestamp('otp_expires_at');
            $table->string('status')->default('pending'); // pending, accepted, expired
            $table->foreignId('invited_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('image')->nullable();
            $table->text('bio')->nullable();
            $table->text('department')->nullable();
            $table->text('phone')->nullable();
            $table->timestamps();
        });

        // Add additional fields to users table for better tracking
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable()->after('bio');
            $table->string('phone')->nullable()->after('department');
            $table->string('invited_via')->nullable()->after('phone'); // 'invitation', 'direct', 'import'
            $table->foreignId('invited_by')->nullable()->after('invited_via')->constrained('users')->onDelete('set null');
            $table->timestamp('invitation_accepted_at')->nullable()->after('invited_by');
            $table->integer('login_count')->default(0)->after('invitation_accepted_at');
            $table->timestamp('last_login_at')->nullable()->after('login_count');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_invitations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'department', 'phone', 'invited_via', 'invited_by',
                'invitation_accepted_at', 'login_count', 'last_login_at', 'last_login_ip'
            ]);
        });
    }
};
