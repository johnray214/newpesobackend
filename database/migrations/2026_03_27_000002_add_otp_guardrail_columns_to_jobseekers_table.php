<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobseekers', function (Blueprint $table) {
            $table->unsignedSmallInteger('otp_send_count_today')->default(0)->after('otp_expires_at');
            $table->date('otp_send_count_date')->nullable()->after('otp_send_count_today');
            $table->unsignedSmallInteger('otp_resend_count')->default(0)->after('otp_send_count_date');
            $table->timestamp('otp_resend_cooldown_until')->nullable()->after('otp_resend_count');

            $table->index(['otp_send_count_date', 'otp_send_count_today'], 'jobseekers_otp_daily_idx');
            $table->index('otp_resend_cooldown_until', 'jobseekers_otp_cooldown_idx');
        });
    }

    public function down(): void
    {
        Schema::table('jobseekers', function (Blueprint $table) {
            $table->dropIndex('jobseekers_otp_daily_idx');
            $table->dropIndex('jobseekers_otp_cooldown_idx');
            $table->dropColumn([
                'otp_send_count_today',
                'otp_send_count_date',
                'otp_resend_count',
                'otp_resend_cooldown_until',
            ]);
        });
    }
};

