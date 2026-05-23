<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('property_kind', 30)->default('Land')->index()->after('classification');
            $table->string('land_pin_reference', 80)->nullable()->after('title_number');
            $table->json('extra_attributes')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['property_kind', 'land_pin_reference', 'extra_attributes']);
        });
    }
};
