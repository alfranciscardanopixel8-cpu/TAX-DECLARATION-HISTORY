<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tax_declarations', function (Blueprint $table) {
            // Composite index for common query: filter by property + status, sort by year
            $table->index(['property_id', 'status', 'effectivity_year'], 'idx_td_property_status_year');
            // Index for owner-based searches
            $table->index('owner_id', 'idx_td_owner');
        });

        Schema::table('properties', function (Blueprint $table) {
            // Common search combo
            $table->index(['municipality', 'barangay', 'status'], 'idx_property_location_status');
        });

        Schema::table('documents', function (Blueprint $table) {
            // Filter by property + status
            $table->index(['property_id', 'physical_copy_status'], 'idx_doc_property_status');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            // For audit trail queries
            $table->index(['property_id', 'created_at'], 'idx_activity_property_date');
        });
    }

    public function down(): void
    {
        Schema::table('tax_declarations', function (Blueprint $table) {
            $table->dropIndex('idx_td_property_status_year');
            $table->dropIndex('idx_td_owner');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('idx_property_location_status');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_doc_property_status');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_activity_property_date');
        });
    }
};
