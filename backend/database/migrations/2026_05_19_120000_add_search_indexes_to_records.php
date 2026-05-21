<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('properties', 'properties_lot_number_index')) {
            Schema::table('properties', fn (Blueprint $table) => $table->index('lot_number'));
        }

        if (! Schema::hasIndex('properties', 'properties_pin_index')) {
            Schema::table('properties', fn (Blueprint $table) => $table->index('pin'));
        }

        if (! Schema::hasIndex('properties', 'properties_municipality_index')) {
            Schema::table('properties', fn (Blueprint $table) => $table->index('municipality'));
        }

        if (! Schema::hasIndex('properties', 'properties_barangay_index')) {
            Schema::table('properties', fn (Blueprint $table) => $table->index('barangay'));
        }

        if (! Schema::hasIndex('tax_declarations', 'tax_declarations_td_number_index')) {
            Schema::table('tax_declarations', fn (Blueprint $table) => $table->index('td_number'));
        }

        if (! Schema::hasIndex('tax_declarations', 'tax_declarations_arp_number_index')) {
            Schema::table('tax_declarations', fn (Blueprint $table) => $table->index('arp_number'));
        }

        if (! Schema::hasIndex('tax_declarations', 'tax_declarations_status_index')) {
            Schema::table('tax_declarations', fn (Blueprint $table) => $table->index('status'));
        }

        if (! Schema::hasIndex('owners', 'owners_name_index')) {
            Schema::table('owners', fn (Blueprint $table) => $table->index('name'));
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('properties', 'properties_lot_number_index')) {
            Schema::table('properties', fn (Blueprint $table) => $table->dropIndex(['lot_number']));
        }

        if (Schema::hasIndex('properties', 'properties_pin_index')) {
            Schema::table('properties', fn (Blueprint $table) => $table->dropIndex(['pin']));
        }

        if (Schema::hasIndex('properties', 'properties_municipality_index')) {
            Schema::table('properties', fn (Blueprint $table) => $table->dropIndex(['municipality']));
        }

        if (Schema::hasIndex('properties', 'properties_barangay_index')) {
            Schema::table('properties', fn (Blueprint $table) => $table->dropIndex(['barangay']));
        }

        if (Schema::hasIndex('tax_declarations', 'tax_declarations_td_number_index')) {
            Schema::table('tax_declarations', fn (Blueprint $table) => $table->dropIndex(['td_number']));
        }

        if (Schema::hasIndex('tax_declarations', 'tax_declarations_arp_number_index')) {
            Schema::table('tax_declarations', fn (Blueprint $table) => $table->dropIndex(['arp_number']));
        }

        if (Schema::hasIndex('tax_declarations', 'tax_declarations_status_index')) {
            Schema::table('tax_declarations', fn (Blueprint $table) => $table->dropIndex(['status']));
        }

        if (Schema::hasIndex('owners', 'owners_name_index')) {
            Schema::table('owners', fn (Blueprint $table) => $table->dropIndex(['name']));
        }
    }
};
