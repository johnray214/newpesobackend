<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobseekers', function (Blueprint $table) {
            // Province → city/municipality → barangay only (no region).
            $table->string('province_code', 20)->nullable()->after('address');
            $table->string('province_name', 120)->nullable()->after('province_code');
            $table->string('city_code', 20)->nullable()->after('province_name');
            $table->string('city_name', 120)->nullable()->after('city_code');
            $table->string('barangay_code', 20)->nullable()->after('city_name');
            $table->string('barangay_name', 120)->nullable()->after('barangay_code');
            $table->string('street_address', 255)->nullable()->after('barangay_name');

            $table->index('province_code');
            $table->index('city_code');
            $table->index('barangay_code');
        });
    }

    public function down(): void
    {
        Schema::table('jobseekers', function (Blueprint $table) {
            $table->dropIndex(['province_code']);
            $table->dropIndex(['city_code']);
            $table->dropIndex(['barangay_code']);

            $table->dropColumn([
                'province_code',
                'province_name',
                'city_code',
                'city_name',
                'barangay_code',
                'barangay_name',
                'street_address',
            ]);
        });
    }
};
