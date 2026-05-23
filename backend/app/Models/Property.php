<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'pin',
        'property_index_number',
        'lot_number',
        'survey_number',
        'title_number',
        'land_pin_reference',
        'barangay',
        'municipality',
        'province',
        'classification',
        'property_kind',
        'actual_use',
        'land_area',
        'unit_of_measure',
        'status',
        'remarks',
        'extra_attributes',
    ];

    protected function casts(): array
    {
        return [
            'land_area' => 'decimal:2',
            'extra_attributes' => 'array',
        ];
    }

    public function taxDeclarations(): HasMany
    {
        return $this->hasMany(TaxDeclaration::class)->orderByDesc('effectivity_year');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function assessmentRecords(): HasMany
    {
        return $this->hasMany(AssessmentRecord::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest();
    }

    public function physicalRecordMovements(): HasMany
    {
        return $this->hasMany(PhysicalRecordMovement::class)->latest();
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (! $keyword) {
            return $query;
        }

        $like = '%'.$keyword.'%';
        $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';

        return $query->where(function (Builder $query) use ($like, $operator) {
            $query->where('pin', $operator, $like)
                ->orWhere('property_index_number', $operator, $like)
                ->orWhere('lot_number', $operator, $like)
                ->orWhere('survey_number', $operator, $like)
                ->orWhere('title_number', $operator, $like)
                ->orWhere('barangay', $operator, $like)
                ->orWhere('municipality', $operator, $like)
                ->orWhereHas('documents', function (Builder $query) use ($like, $operator) {
                    $query->where('document_type', $operator, $like)
                        ->orWhere('reference_number', $operator, $like)
                        ->orWhere('file_name', $operator, $like)
                        ->orWhere('storage_location', $operator, $like)
                        ->orWhere('box_number', $operator, $like)
                        ->orWhere('folder_number', $operator, $like)
                        ->orWhere('ocr_text', $operator, $like);
                })
                ->orWhereHas('taxDeclarations', function (Builder $query) use ($like) {
                    $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';

                    $query->where('td_number', $operator, $like)
                        ->orWhere('arp_number', $operator, $like)
                        ->orWhereHas('owner', fn (Builder $ownerQuery) => $ownerQuery->where('name', $operator, $like));
                });
        });
    }
}
