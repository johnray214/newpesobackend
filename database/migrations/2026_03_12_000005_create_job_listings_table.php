<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('employers')->cascadeOnDelete();
            $table->string('title', 255);
            $table->enum('type', ['full-time', 'part-time', 'contract', 'internship']);
            $table->string('location', 255);
            $table->string('salary_range', 100)->nullable();
            $table->text('description');
            $table->integer('slots')->default(1);
            $table->enum('status', ['open', 'closed', 'draft'])->default('open');
            $table->date('posted_date')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('employer_id');
            $table->index('status');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
