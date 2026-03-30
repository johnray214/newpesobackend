<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type')->nullable()->after('subject');
            $table->unsignedBigInteger('job_listing_id')->nullable()->after('type');
            $table->foreign('job_listing_id')->references('id')->on('job_listings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['job_listing_id']);
            $table->dropColumn(['type', 'job_listing_id']);
        });
    }
};
