<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('jobseekers', 'certificate_path')) {
            Schema::table('jobseekers', function (Blueprint $table) {
                $table->string('certificate_path', 255)->nullable()->after('resume_path');
            });
        }

        if (! Schema::hasColumn('jobseekers', 'barangay_clearance_path')) {
            Schema::table('jobseekers', function (Blueprint $table) {
                $after = Schema::hasColumn('jobseekers', 'certificate_path')
                    ? 'certificate_path'
                    : 'resume_path';
                $table->string('barangay_clearance_path', 255)->nullable()->after($after);
            });
        }
    }

    public function down(): void
    {
        Schema::table('jobseekers', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('jobseekers', 'barangay_clearance_path') ? 'barangay_clearance_path' : null,
                Schema::hasColumn('jobseekers', 'certificate_path') ? 'certificate_path' : null,
            ]);
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
