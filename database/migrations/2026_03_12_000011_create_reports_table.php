<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['placement', 'registration', 'skills', 'events', 'employer', 'skillmatch']);
            $table->date('date_from');
            $table->date('date_to');
            $table->string('group_by', 50)->nullable();
            $table->json('columns')->nullable();
            $table->enum('export_format', ['pdf', 'csv', 'xlsx'])->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('generated_by');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
