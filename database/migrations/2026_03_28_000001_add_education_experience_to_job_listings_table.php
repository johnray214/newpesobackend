<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            if (!Schema::hasColumn('job_listings', 'education_level')) {
                $table->string('education_level', 80)->nullable()->after('salary_range');
            }
            if (!Schema::hasColumn('job_listings', 'experience_required')) {
                $table->string('experience_required', 80)->nullable()->after('education_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('job_listings', 'education_level'))    $cols[] = 'education_level';
            if (Schema::hasColumn('job_listings', 'experience_required')) $cols[] = 'experience_required';
            if ($cols) $table->dropColumn($cols);
        });
    }
};
