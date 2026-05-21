<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'owner_id',
        'previous_tax_declaration_id',
        'td_number',
        'arp_number',
        'effectivity_year',
        'classification',
        'actual_use',
        'market_value',
        'assessed_value',
        'assessment_level',
        'status',
        'transaction_type',
        'memoranda',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'market_value' => 'decimal:2',
            'assessed_value' => 'decimal:2',
            'assessment_level' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function previousTaxDeclaration(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_tax_declaration_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function assessmentRecords(): HasMany
    {
        return $this->hasMany(AssessmentRecord::class);
    }
}
