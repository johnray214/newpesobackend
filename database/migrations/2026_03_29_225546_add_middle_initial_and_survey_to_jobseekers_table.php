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
        Schema::table('jobseekers', function (Blueprint $table) {
            $table->string('middle_initial', 5)->nullable()->after('first_name');
            $table->boolean('has_received_satisfaction_survey')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobseekers', function (Blueprint $table) {
            $table->dropColumn(['middle_initial', 'has_received_satisfaction_survey']);
        });
    }
};
