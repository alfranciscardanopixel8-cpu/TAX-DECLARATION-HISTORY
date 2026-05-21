<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'tax_declaration_id',
        'document_type',
        'reference_number',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'issued_at',
        'notes',
        'ocr_text',
        'ocr_extracted_at',
        'physical_copy_status',
        'storage_location',
        'shelf_number',
        'box_number',
        'folder_number',
        'custodian',
        'received_at',
        'released_at',
        'returned_at',
        'digitized_at',
        'digitized_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'received_at' => 'date',
            'released_at' => 'date',
            'returned_at' => 'date',
            'digitized_at' => 'datetime',
            'ocr_extracted_at' => 'datetime',
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

    public function movements(): HasMany
    {
        return $this->hasMany(PhysicalRecordMovement::class)->latest();
    }

    public function digitizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'digitized_by_user_id');
    }
}
