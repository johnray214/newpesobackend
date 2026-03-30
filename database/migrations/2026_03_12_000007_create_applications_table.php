<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            // Use explicit constraint names to avoid InnoDB orphan collisions
            $table->unsignedBigInteger('job_listing_id');
            $table->foreign('job_listing_id', 'fk_app_job_listing')
                  ->references('id')->on('job_listings')->cascadeOnDelete();

            $table->unsignedBigInteger('jobseeker_id');
            $table->foreign('jobseeker_id', 'fk_app_jobseeker')
                  ->references('id')->on('jobseekers')->cascadeOnDelete();

            $table->enum('status', ['reviewing', 'shortlisted', 'interview', 'hired', 'rejected'])->default('reviewing');
            $table->unsignedTinyInteger('match_score')->default(0);
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();

            $table->unique(['job_listing_id', 'jobseeker_id'], 'uq_app_job_jobseeker');
            $table->index('job_listing_id', 'idx_app_job_listing');
            $table->index('jobseeker_id',   'idx_app_jobseeker');
            $table->index('status',         'idx_app_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
