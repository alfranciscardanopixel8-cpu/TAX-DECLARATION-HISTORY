<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssessmentRecord;
use App\Models\Property;
use App\Models\TaxDeclaration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssessmentRecordController extends Controller
{
    public function store(Request $request, Property $property, TaxDeclaration $taxDeclaration): JsonResponse
    {
        abort_unless($taxDeclaration->property_id === $property->id, 404);

        $validated = $request->validate([
            'assessment_type' => ['required', 'string', 'max:80'],
            'classification' => ['nullable', 'string', 'max:80'],
            'actual_use' => ['nullable', 'string', 'max:80'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'unit_of_measure' => ['nullable', 'string', 'max:40'],
            'unit_value' => ['nullable', 'numeric', 'min:0'],
            'base_market_value' => ['nullable', 'numeric', 'min:0'],
            'adjustment' => ['nullable', 'numeric'],
            'depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'market_value' => ['nullable', 'numeric', 'min:0'],
            'assessment_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'assessed_value' => ['nullable', 'numeric', 'min:0'],
            'taxable' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'extra_attributes' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($property, $taxDeclaration, $validated) {
            $marketValue = $validated['market_value']
                ?? (($validated['base_market_value'] ?? 0) + ($validated['adjustment'] ?? 0));
            $assessmentLevel = $validated['assessment_level'] ?? $taxDeclaration->assessment_level;
            $assessedValue = $validated['assessed_value']
                ?? ($assessmentLevel ? round($marketValue * ($assessmentLevel / 100), 2) : 0);

            $record = $taxDeclaration->assessmentRecords()->create([
                'property_id' => $property->id,
                'assessment_type' => $validated['assessment_type'],
                'classification' => $validated['classification'] ?? $taxDeclaration->classification,
                'actual_use' => $validated['actual_use'] ?? $taxDeclaration->actual_use,
                'area' => $validated['area'] ?? $property->land_area,
                'unit_of_measure' => $validated['unit_of_measure'] ?? $property->unit_of_measure,
                'unit_value' => $validated['unit_value'] ?? 0,
                'base_market_value' => $validated['base_market_value'] ?? $marketValue,
                'adjustment' => $validated['adjustment'] ?? 0,
                'depreciation_rate' => $validated['depreciation_rate'] ?? null,
                'market_value' => $marketValue,
                'assessment_level' => $assessmentLevel,
                'assessed_value' => $assessedValue,
                'taxable' => $validated['taxable'] ?? true,
                'notes' => $validated['notes'] ?? null,
                'extra_attributes' => $validated['extra_attributes'] ?? null,
            ]);

            $taxDeclaration->update([
                'market_value' => $taxDeclaration->assessmentRecords()->sum('market_value'),
                'assessed_value' => $taxDeclaration->assessmentRecords()->sum('assessed_value'),
            ]);

            $property->activityLogs()->create([
                'user_id' => request()->user()?->id,
                'tax_declaration_id' => $taxDeclaration->id,
                'action' => 'assessment_added',
                'description' => "{$record->assessment_type} assessment added to {$taxDeclaration->td_number}.",
                'new_values' => $record->toArray(),
            ]);
        });

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

    public function update(Request $request, Property $property, TaxDeclaration $taxDeclaration, AssessmentRecord $assessmentRecord): JsonResponse
    {
        abort_unless($taxDeclaration->property_id === $property->id, 404);
        abort_unless($assessmentRecord->tax_declaration_id === $taxDeclaration->id, 404);

        $validated = $this->validateAssessment($request);
        $oldValues = $assessmentRecord->toArray();

        $assessmentRecord->update($this->assessmentPayload($property, $taxDeclaration, $validated));

        $this->recalculateDeclaration($taxDeclaration);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'tax_declaration_id' => $taxDeclaration->id,
            'action' => 'assessment_updated',
            'description' => "{$assessmentRecord->assessment_type} assessment updated for {$taxDeclaration->td_number}.",
            'old_values' => $oldValues,
            'new_values' => $assessmentRecord->fresh()->toArray(),
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

    public function destroy(Request $request, Property $property, TaxDeclaration $taxDeclaration, AssessmentRecord $assessmentRecord): JsonResponse
    {
        abort_unless($taxDeclaration->property_id === $property->id, 404);
        abort_unless($assessmentRecord->tax_declaration_id === $taxDeclaration->id, 404);

        $oldValues = $assessmentRecord->toArray();
        $assessmentType = $assessmentRecord->assessment_type;
        $assessmentRecord->delete();

        $this->recalculateDeclaration($taxDeclaration);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'tax_declaration_id' => $taxDeclaration->id,
            'action' => 'assessment_removed',
            'description' => "{$assessmentType} assessment removed from {$taxDeclaration->td_number}.",
            'old_values' => $oldValues,
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

    private function validateAssessment(Request $request): array
    {
        return $request->validate([
            'assessment_type' => ['required', 'string', 'max:80'],
            'classification' => ['nullable', 'string', 'max:80'],
            'actual_use' => ['nullable', 'string', 'max:80'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'unit_of_measure' => ['nullable', 'string', 'max:40'],
            'unit_value' => ['nullable', 'numeric', 'min:0'],
            'base_market_value' => ['nullable', 'numeric', 'min:0'],
            'adjustment' => ['nullable', 'numeric'],
            'depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'market_value' => ['nullable', 'numeric', 'min:0'],
            'assessment_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'assessed_value' => ['nullable', 'numeric', 'min:0'],
            'taxable' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'extra_attributes' => ['nullable', 'array'],
        ]);
    }

    private function assessmentPayload(Property $property, TaxDeclaration $taxDeclaration, array $validated): array
    {
        $marketValue = $validated['market_value']
            ?? (($validated['base_market_value'] ?? 0) + ($validated['adjustment'] ?? 0));
        $assessmentLevel = $validated['assessment_level'] ?? $taxDeclaration->assessment_level;
        $assessedValue = $validated['assessed_value']
            ?? ($assessmentLevel ? round($marketValue * ($assessmentLevel / 100), 2) : 0);

        return [
            'property_id' => $property->id,
            'assessment_type' => $validated['assessment_type'],
            'classification' => $validated['classification'] ?? $taxDeclaration->classification,
            'actual_use' => $validated['actual_use'] ?? $taxDeclaration->actual_use,
            'area' => $validated['area'] ?? $property->land_area,
            'unit_of_measure' => $validated['unit_of_measure'] ?? $property->unit_of_measure,
            'unit_value' => $validated['unit_value'] ?? 0,
            'base_market_value' => $validated['base_market_value'] ?? $marketValue,
            'adjustment' => $validated['adjustment'] ?? 0,
            'depreciation_rate' => $validated['depreciation_rate'] ?? null,
            'market_value' => $marketValue,
            'assessment_level' => $assessmentLevel,
            'assessed_value' => $assessedValue,
            'taxable' => $validated['taxable'] ?? true,
            'notes' => $validated['notes'] ?? null,
            'extra_attributes' => $validated['extra_attributes'] ?? null,
        ];
    }

    private function recalculateDeclaration(TaxDeclaration $taxDeclaration): void
    {
        $taxDeclaration->update([
            'market_value' => $taxDeclaration->assessmentRecords()->sum('market_value'),
            'assessed_value' => $taxDeclaration->assessmentRecords()->sum('assessed_value'),
        ]);
    }
}
