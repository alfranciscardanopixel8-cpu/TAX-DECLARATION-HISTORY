<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\PhysicalRecordMovement;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function store(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'tax_declaration_id' => [
                'nullable',
                Rule::exists('tax_declarations', 'id')->where('property_id', $property->id),
            ],
            'document_type' => ['required', 'string', 'max:120'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'issued_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'physical_copy_status' => ['nullable', 'string', 'max:80'],
            'storage_location' => ['nullable', 'string', 'max:160'],
            'shelf_number' => ['nullable', 'string', 'max:80'],
            'box_number' => ['nullable', 'string', 'max:80'],
            'folder_number' => ['nullable', 'string', 'max:80'],
            'custodian' => ['nullable', 'string', 'max:160'],
            'received_at' => ['nullable', 'date'],
            'released_at' => ['nullable', 'date'],
            'returned_at' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        $uploadedFile = $request->file('file');
        $filePath = $uploadedFile
            ? $uploadedFile->store("assessor-documents/{$property->id}")
            : 'pending-upload/'.now()->format('YmdHis').'-'.str($validated['document_type'])->slug().'.pdf';

        $document = $property->documents()->create([
            'tax_declaration_id' => $validated['tax_declaration_id'] ?? null,
            'document_type' => $validated['document_type'],
            'reference_number' => $validated['reference_number'] ?? null,
            'file_name' => $uploadedFile?->getClientOriginalName() ?? ($validated['reference_number'] ?? $validated['document_type']).'.pdf',
            'file_path' => $filePath,
            'mime_type' => $uploadedFile?->getMimeType(),
            'file_size' => $uploadedFile?->getSize(),
            'issued_at' => $validated['issued_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'physical_copy_status' => $validated['physical_copy_status'] ?? ($uploadedFile ? 'On File' : 'For Scanning'),
            'storage_location' => $validated['storage_location'] ?? null,
            'shelf_number' => $validated['shelf_number'] ?? null,
            'box_number' => $validated['box_number'] ?? null,
            'folder_number' => $validated['folder_number'] ?? null,
            'custodian' => $validated['custodian'] ?? null,
            'received_at' => $validated['received_at'] ?? null,
            'released_at' => $validated['released_at'] ?? null,
            'returned_at' => $validated['returned_at'] ?? null,
        ]);

        PhysicalRecordMovement::create([
            'property_id' => $property->id,
            'document_id' => $document->id,
            'user_id' => $request->user()?->id,
            'movement_type' => 'Initial Entry',
            'to_status' => $document->physical_copy_status,
            'to_location' => $document->storage_location,
            'to_box_number' => $document->box_number,
            'to_folder_number' => $document->folder_number,
            'custodian' => $document->custodian,
            'movement_date' => $document->received_at ?? now()->toDateString(),
            'remarks' => 'Physical record registered.',
        ]);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'tax_declaration_id' => $document->tax_declaration_id,
            'document_id' => $document->id,
            'action' => 'document_added',
            'description' => $uploadedFile
                ? "{$document->document_type} document registered and uploaded."
                : "{$document->document_type} physical record indexed — awaiting scan upload.",
            'new_values' => $document->toArray(),
        ]);

        return response()->json($property->fresh()->load([
            'taxDeclarations.owner',
            'taxDeclarations.previousTaxDeclaration.owner',
            'taxDeclarations.assessmentRecords',
            'assessmentRecords.taxDeclaration',
            'documents.taxDeclaration',
            'documents.movements.user:id,name,role',
            'activityLogs',
        ]), 201);
    }

    public function update(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'tax_declaration_id' => [
                'nullable',
                Rule::exists('tax_declarations', 'id')->where('property_id', $document->property_id),
            ],
            'document_type' => ['required', 'string', 'max:120'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'issued_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'physical_copy_status' => ['nullable', 'string', 'max:80'],
            'storage_location' => ['nullable', 'string', 'max:160'],
            'shelf_number' => ['nullable', 'string', 'max:80'],
            'box_number' => ['nullable', 'string', 'max:80'],
            'folder_number' => ['nullable', 'string', 'max:80'],
            'custodian' => ['nullable', 'string', 'max:160'],
            'received_at' => ['nullable', 'date'],
            'released_at' => ['nullable', 'date'],
            'returned_at' => ['nullable', 'date'],
        ]);

        $property = $document->property;
        $oldValues = $document->toArray();

        $document->update($validated);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'tax_declaration_id' => $document->tax_declaration_id,
            'document_id' => $document->id,
            'action' => 'document_updated',
            'description' => "{$document->document_type} document record updated.",
            'old_values' => $oldValues,
            'new_values' => $document->fresh()->toArray(),
        ]);

        return response()->json($property->fresh()->load([
            'taxDeclarations.owner',
            'taxDeclarations.previousTaxDeclaration.owner',
            'taxDeclarations.assessmentRecords',
            'assessmentRecords.taxDeclaration',
            'documents.taxDeclaration',
            'documents.movements.user:id,name,role',
            'activityLogs',
        ]));
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        abort_unless($request->user()?->canAdminister(), 403);

        $property = $document->property;
        $oldValues = $document->toArray();

        $document->update([
            'physical_copy_status' => 'Archived',
            'notes' => trim(($document->notes ? $document->notes."\n" : '').'Archived from active document registry.'),
        ]);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'tax_declaration_id' => $document->tax_declaration_id,
            'document_id' => $document->id,
            'action' => 'document_archived',
            'description' => "{$document->document_type} document archived.",
            'old_values' => $oldValues,
            'new_values' => $document->fresh()->toArray(),
        ]);

        return response()->json($property->fresh()->load([
            'taxDeclarations.owner',
            'taxDeclarations.previousTaxDeclaration.owner',
            'taxDeclarations.assessmentRecords',
            'assessmentRecords.taxDeclaration',
            'documents.taxDeclaration',
            'documents.movements.user:id,name,role',
            'activityLogs',
        ]));
    }

    public function digitize(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'notes' => ['nullable', 'string'],
            'ocr_text' => ['nullable', 'string'],
        ]);

        $property = $document->property;
        $oldValues = $document->toArray();

        if (Storage::exists($document->file_path) && ! str_starts_with($document->file_path, 'pending-upload/')) {
            Storage::delete($document->file_path);
        }

        $uploadedFile = $request->file('file');
        $filePath = $uploadedFile->store("assessor-documents/{$property->id}");

        $document->update([
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $filePath,
            'mime_type' => $uploadedFile->getMimeType(),
            'file_size' => $uploadedFile->getSize(),
            'physical_copy_status' => $document->physical_copy_status === 'For Scanning'
                ? 'On File'
                : $document->physical_copy_status,
            'digitized_at' => now(),
            'digitized_by_user_id' => $request->user()?->id,
            'notes' => trim(($document->notes ? $document->notes."\n" : '').($validated['notes'] ?? 'Physical record digitized and attached.')),
            'ocr_text' => $validated['ocr_text'] ?? $document->ocr_text,
            'ocr_extracted_at' => isset($validated['ocr_text']) ? now() : $document->ocr_extracted_at,
        ]);

        PhysicalRecordMovement::create([
            'property_id' => $property->id,
            'document_id' => $document->id,
            'user_id' => $request->user()?->id,
            'movement_type' => 'Digitized',
            'to_status' => $document->physical_copy_status,
            'to_location' => $document->storage_location,
            'to_box_number' => $document->box_number,
            'to_folder_number' => $document->folder_number,
            'custodian' => $document->custodian,
            'movement_date' => now()->toDateString(),
            'remarks' => 'Scanned copy uploaded to complete digitization.',
        ]);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'tax_declaration_id' => $document->tax_declaration_id,
            'document_id' => $document->id,
            'action' => 'document_digitized',
            'description' => "{$document->document_type} scan uploaded — physical record digitized.",
            'old_values' => $oldValues,
            'new_values' => $document->fresh()->toArray(),
        ]);

        return response()->json($property->fresh()->load([
            'taxDeclarations.owner',
            'taxDeclarations.previousTaxDeclaration.owner',
            'taxDeclarations.assessmentRecords',
            'taxDeclarations.documents.digitizedBy:id,name,role',
            'assessmentRecords.taxDeclaration',
            'documents.taxDeclaration',
            'documents.digitizedBy:id,name,role',
            'documents.movements.user:id,name,role',
            'activityLogs.user:id,name,role',
            'activityLogs.taxDeclaration:id,td_number',
        ]));
    }

    public function storeOcr(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'ocr_text' => ['required', 'string'],
        ]);

        $property = $document->property;
        $oldValues = $document->only(['ocr_text', 'ocr_extracted_at']);

        $document->update([
            'ocr_text' => $validated['ocr_text'],
            'ocr_extracted_at' => now(),
        ]);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'tax_declaration_id' => $document->tax_declaration_id,
            'document_id' => $document->id,
            'action' => 'document_ocr',
            'description' => "OCR text saved for {$document->document_type} ({$document->reference_number}).",
            'old_values' => $oldValues,
            'new_values' => $document->only(['ocr_text', 'ocr_extracted_at']),
        ]);

        return response()->json([
            'document' => $document->fresh(),
            'property' => $property->fresh()->load([
                'taxDeclarations.owner',
                'documents',
                'activityLogs.user:id,name,role',
            ]),
        ]);
    }

    public function download(Document $document)
    {
        if (! Storage::exists($document->file_path) || str_starts_with($document->file_path, 'pending-upload/')) {
            return response()->json([
                'message' => 'The scanned file is not available in storage yet. Register the physical record, then upload a scan to digitize it.',
                'document' => $document,
                'needs_digitization' => true,
            ], 404);
        }

        return Storage::download($document->file_path, $document->file_name);
    }
}
