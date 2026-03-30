<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employers', function (Blueprint $table) {
            $table->id();

            // ── Account ───────────────────────────────────────────────
            $table->string('company_name', 255);
            $table->string('contact_person', 255);
            $table->string('email', 191)->unique();
            $table->string('password', 255);

            // ── Company Info ──────────────────────────────────────────
            $table->string('industry', 100);
            $table->string('company_size', 30);
            $table->string('tagline', 255)->nullable();
            $table->text('about')->nullable();
            $table->string('business_type', 100)->nullable();
            $table->year('founded')->nullable();
            $table->json('perks')->nullable();

            // ── Address ───────────────────────────────────────────────
            $table->string('barangay', 100)->nullable();
            $table->string('city', 100);
            $table->string('province', 100)->nullable();
            $table->string('address_full', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('map_visible')->default(true);

            // ── Contact ───────────────────────────────────────────────
            $table->string('phone', 20);
            $table->string('website', 255)->nullable();
            $table->string('tin', 50)->nullable();

            // ── Documents ─────────────────────────────────────────────
            $table->string('biz_permit_path', 255)->nullable();
            $table->string('bir_cert_path', 255)->nullable();

            // ── Stats (cached) ────────────────────────────────────────
            $table->unsignedInteger('total_hired')->default(0);

            // ── Status ────────────────────────────────────────────────
            $table->enum('status', ['pending', 'verified', 'rejected', 'suspended'])->default('pending');
            $table->timestamp('verified_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('status');
            $table->index('deleted_at');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};