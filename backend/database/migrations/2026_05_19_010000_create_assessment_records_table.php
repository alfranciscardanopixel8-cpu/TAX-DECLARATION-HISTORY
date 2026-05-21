<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_declaration_id')->constrained()->cascadeOnDelete();
            $table->string('assessment_type')->default('Land')->index();
            $table->string('classification')->index();
            $table->string('actual_use')->nullable();
            $table->decimal('area', 14, 2)->nullable();
            $table->string('unit_of_measure')->default('sqm');
            $table->decimal('unit_value', 14, 2)->default(0);
            $table->decimal('base_market_value', 14, 2)->default(0);
            $table->decimal('adjustment', 14, 2)->default(0);
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            $table->decimal('market_value', 14, 2)->default(0);
            $table->decimal('assessment_level', 5, 2)->nullable();
            $table->decimal('assessed_value', 14, 2)->default(0);
            $table->boolean('taxable')->default(true);
            $table->text('notes')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'tax_declaration_id']);
        });

        $now = now();

        DB::table('tax_declarations')
            ->join('properties', 'properties.id', '=', 'tax_declarations.property_id')
            ->select([
                'tax_declarations.id as tax_declaration_id',
                'tax_declarations.property_id',
                'tax_declarations.classification',
                'tax_declarations.actual_use',
                'tax_declarations.market_value',
                'tax_declarations.assessment_level',
                'tax_declarations.assessed_value',
                'properties.land_area',
                'properties.unit_of_measure',
            ])
            ->orderBy('tax_declarations.id')
            ->chunk(100, function ($rows) use ($now) {
                $records = $rows->map(fn ($row) => [
                    'property_id' => $row->property_id,
                    'tax_declaration_id' => $row->tax_declaration_id,
                    'assessment_type' => 'Land',
                    'classification' => $row->classification,
                    'actual_use' => $row->actual_use,
                    'area' => $row->land_area,
                    'unit_of_measure' => $row->unit_of_measure ?: 'sqm',
                    'unit_value' => 0,
                    'base_market_value' => $row->market_value,
                    'adjustment' => 0,
                    'depreciation_rate' => null,
                    'market_value' => $row->market_value,
                    'assessment_level' => $row->assessment_level,
                    'assessed_value' => $row->assessed_value,
                    'taxable' => true,
                    'notes' => 'Imported from the tax declaration summary values.',
                    'extra_attributes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('assessment_records')->insert($records);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_records');
    }
};
