<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->enum('recipient_type', ['employer', 'jobseeker']);
            $table->unsignedBigInteger('recipient_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notification_id', 'recipient_type', 'recipient_id'], 'notif_reads_lookup_idx');
            $table->index(['recipient_type', 'recipient_id'], 'notif_reads_recipient_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
    }
};
