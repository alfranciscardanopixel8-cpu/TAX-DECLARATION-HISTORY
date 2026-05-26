<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Composite indexes targeting the most common filter combinations
     * exposed by the search UI on big datasets.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (! $this->hasIndex('properties', 'properties_municipality_status_index')) {
                $table->index(['municipality', 'status'], 'properties_municipality_status_index');
            }
            if (! $this->hasIndex('properties', 'properties_classification_status_index')) {
                $table->index(['classification', 'status'], 'properties_classification_status_index');
            }
            if (! $this->hasIndex('properties', 'properties_kind_status_index')) {
                $table->index(['property_kind', 'status'], 'properties_kind_status_index');
            }
        });

        Schema::table('tax_declarations', function (Blueprint $table) {
            if (! $this->hasIndex('tax_declarations', 'tax_declarations_property_status_index')) {
                $table->index(['property_id', 'status'], 'tax_declarations_property_status_index');
            }
            if (! $this->hasIndex('tax_declarations', 'tax_declarations_status_year_index')) {
                $table->index(['status', 'effectivity_year'], 'tax_declarations_status_year_index');
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            if (! $this->hasIndex('documents', 'documents_property_type_index')) {
                $table->index(['property_id', 'document_type'], 'documents_property_type_index');
            }
            if (! $this->hasIndex('documents', 'documents_property_physical_index')) {
                $table->index(['property_id', 'physical_copy_status'], 'documents_property_physical_index');
            }
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            if (! $this->hasIndex('activity_logs', 'activity_logs_property_created_index')) {
                $table->index(['property_id', 'created_at'], 'activity_logs_property_created_index');
            }
            if (! $this->hasIndex('activity_logs', 'activity_logs_action_created_index')) {
                $table->index(['action', 'created_at'], 'activity_logs_action_created_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('properties_municipality_status_index');
            $table->dropIndex('properties_classification_status_index');
            $table->dropIndex('properties_kind_status_index');
        });

        Schema::table('tax_declarations', function (Blueprint $table) {
            $table->dropIndex('tax_declarations_property_status_index');
            $table->dropIndex('tax_declarations_status_year_index');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_property_type_index');
            $table->dropIndex('documents_property_physical_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_property_created_index');
            $table->dropIndex('activity_logs_action_created_index');
        });
    }

    protected function hasIndex(string $table, string $name): bool
    {
        return Schema::hasIndex($table, $name);
    }
};
