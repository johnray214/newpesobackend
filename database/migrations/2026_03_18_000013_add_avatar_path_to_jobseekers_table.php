<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobseekers', function (Blueprint $table) {
            $table->string('avatar_path', 255)->nullable()->after('resume_path');
            $table->index('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('jobseekers', function (Blueprint $table) {
            $table->dropIndex(['avatar_path']);
            $table->dropColumn('avatar_path');
        });
    }
};

