<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 160)->unique();
            $table->string('category', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
            $table->index('category');
            $table->index('is_active');
        });

        Schema::create('job_listing_skill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained('job_listings')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();

            $table->unique(['job_listing_id', 'skill_id']);
            $table->index('job_listing_id');
            $table->index('skill_id');
        });

        Schema::create('jobseeker_skill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jobseeker_id')->constrained('jobseekers')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->enum('proficiency', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->timestamps();

            $table->unique(['jobseeker_id', 'skill_id']);
            $table->index('jobseeker_id');
            $table->index('skill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobseeker_skill_items');
        Schema::dropIfExists('job_listing_skill_items');
        Schema::dropIfExists('skills');
    }
};

