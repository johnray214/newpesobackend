<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobseekers', function (Blueprint $table) {
            if (!Schema::hasColumn('jobseekers', 'education_level')) {
                $table->string('education_level', 120)->nullable()->after('bio');
            }
            if (!Schema::hasColumn('jobseekers', 'job_experience')) {
                $table->text('job_experience')->nullable()->after('education_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobseekers', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('jobseekers', 'job_experience')) {
                $cols[] = 'job_experience';
            }
            if (Schema::hasColumn('jobseekers', 'education_level')) {
                $cols[] = 'education_level';
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};

