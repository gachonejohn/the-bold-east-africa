<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create the table only if it doesn't exist
        if (!Schema::hasTable('user_invitations')) {
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
        }

        // Add additional fields to users table safely
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'invited_via')) {
                $table->string('invited_via')->nullable()->after('phone'); // 'invitation', 'direct', 'import'
            }
            if (!Schema::hasColumn('users', 'invited_by')) {
                $table->foreignId('invited_by')->nullable()->after('invited_via')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('users', 'invitation_accepted_at')) {
                $table->timestamp('invitation_accepted_at')->nullable()->after('invited_by');
            }
            if (!Schema::hasColumn('users', 'login_count')) {
                $table->integer('login_count')->default(0)->after('invitation_accepted_at');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('login_count');
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable()->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('user_invitations')) {
            Schema::dropIfExists('user_invitations');
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department')) {
                $table->dropColumn('department');
            }
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('users', 'invited_via')) {
                $table->dropColumn('invited_via');
            }
            if (Schema::hasColumn('users', 'invited_by')) {
                $table->dropColumn('invited_by');
            }
            if (Schema::hasColumn('users', 'invitation_accepted_at')) {
                $table->dropColumn('invitation_accepted_at');
            }
            if (Schema::hasColumn('users', 'login_count')) {
                $table->dropColumn('login_count');
            }
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }
            if (Schema::hasColumn('users', 'last_login_ip')) {
                $table->dropColumn('last_login_ip');
            }
        });
    }
};
