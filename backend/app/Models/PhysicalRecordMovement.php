<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysicalRecordMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'document_id',
        'user_id',
        'movement_type',
        'from_status',
        'to_status',
        'from_location',
        'to_location',
        'from_box_number',
        'to_box_number',
        'from_folder_number',
        'to_folder_number',
        'released_to',
        'custodian',
        'movement_date',
        'expected_return_at',
        'returned_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'expected_return_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
