<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'tax_declaration_id',
        'assessment_type',
        'classification',
        'actual_use',
        'area',
        'unit_of_measure',
        'unit_value',
        'base_market_value',
        'adjustment',
        'depreciation_rate',
        'market_value',
        'assessment_level',
        'assessed_value',
        'taxable',
        'notes',
        'extra_attributes',
    ];

    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'unit_value' => 'decimal:2',
            'base_market_value' => 'decimal:2',
            'adjustment' => 'decimal:2',
            'depreciation_rate' => 'decimal:2',
            'market_value' => 'decimal:2',
            'assessment_level' => 'decimal:2',
            'assessed_value' => 'decimal:2',
            'taxable' => 'boolean',
            'extra_attributes' => 'array',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function taxDeclaration(): BelongsTo
    {
        return $this->belongsTo(TaxDeclaration::class);
    }
}
