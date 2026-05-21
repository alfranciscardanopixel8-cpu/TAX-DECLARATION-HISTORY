<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\PhysicalRecordMovement;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhysicalRecordMovementController extends Controller
{
    public function index(Document $document): JsonResponse
    {
        return response()->json($document->movements()->with('user:id,name,role')->get());
    }

    public function store(Request $request, Property $property, Document $document): JsonResponse
    {
        abort_unless($document->property_id === $property->id, 404);

        $validated = $request->validate([
            'movement_type' => ['nullable', 'string', 'max:80'],
            'to_status' => ['required', 'string', 'max:80'],
            'to_location' => ['nullable', 'string', 'max:160'],
            'to_box_number' => ['nullable', 'string', 'max:80'],
            'to_folder_number' => ['nullable', 'string', 'max:80'],
            'released_to' => ['nullable', 'string', 'max:160'],
            'custodian' => ['nullable', 'string', 'max:160'],
            'movement_date' => ['nullable', 'date'],
            'expected_return_at' => ['nullable', 'date'],
            'returned_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $movement = PhysicalRecordMovement::create([
            'property_id' => $property->id,
            'document_id' => $document->id,
            'user_id' => $request->user()?->id,
            'movement_type' => $validated['movement_type'] ?? 'Location Update',
            'from_status' => $document->physical_copy_status,
            'to_status' => $validated['to_status'],
            'from_location' => $document->storage_location,
            'to_location' => $validated['to_location'] ?? $document->storage_location,
            'from_box_number' => $document->box_number,
            'to_box_number' => $validated['to_box_number'] ?? $document->box_number,
            'from_folder_number' => $document->folder_number,
            'to_folder_number' => $validated['to_folder_number'] ?? $document->folder_number,
            'released_to' => $validated['released_to'] ?? null,
            'custodian' => $validated['custodian'] ?? $document->custodian,
            'movement_date' => $validated['movement_date'] ?? now()->toDateString(),
            'expected_return_at' => $validated['expected_return_at'] ?? null,
            'returned_at' => $validated['returned_at'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        $document->update([
            'physical_copy_status' => $movement->to_status,
            'storage_location' => $movement->to_location,
            'box_number' => $movement->to_box_number,
            'folder_number' => $movement->to_folder_number,
            'custodian' => $movement->custodian,
            'released_at' => $movement->to_status === 'Released' ? $movement->movement_date : $document->released_at,
            'returned_at' => $movement->to_status === 'Returned' ? ($movement->returned_at ?? $movement->movement_date) : $document->returned_at,
        ]);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'action' => 'physical_record_moved',
            'description' => "{$document->document_type} physical file moved to {$movement->to_status}.",
            'old_values' => [
                'status' => $movement->from_status,
                'location' => $movement->from_location,
                'box_number' => $movement->from_box_number,
                'folder_number' => $movement->from_folder_number,
            ],
            'new_values' => $movement->toArray(),
        ]);

        return response()->json($property->fresh()->load([
            'taxDeclarations.owner',
            'taxDeclarations.previousTaxDeclaration',
            'taxDeclarations.assessmentRecords',
            'assessmentRecords.taxDeclaration',
            'documents.taxDeclaration',
            'documents.movements.user:id,name,role',
            'activityLogs',
        ]), 201);
    }
}
