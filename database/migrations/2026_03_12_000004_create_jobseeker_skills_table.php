<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobseeker_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jobseeker_id')->constrained('jobseekers')->cascadeOnDelete();
            $table->string('skill', 100);
            $table->timestamps();

            $table->index('jobseeker_id');
            $table->index('skill');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobseeker_skills');
    }
};
