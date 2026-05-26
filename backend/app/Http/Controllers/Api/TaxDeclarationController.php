<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaxDeclarationController extends Controller
{
    public function store(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'owner.name' => ['required', 'string', 'max:180'],
            'owner.address' => ['nullable', 'string'],
            'td_number' => ['required', 'string', 'max:100', Rule::unique('tax_declarations', 'td_number')],
            'arp_number' => ['nullable', 'string', 'max:100'],
            'effectivity_year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'classification' => ['nullable', 'string', 'max:80'],
            'actual_use' => ['nullable', 'string', 'max:80'],
            'market_value' => ['nullable', 'numeric', 'min:0'],
            'assessed_value' => ['nullable', 'numeric', 'min:0'],
            'assessment_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'string', 'max:40'],
            'transaction_type' => ['nullable', 'string', 'max:80'],
            'memoranda' => ['nullable', 'string'],
            'approved_at' => ['nullable', 'date'],
            'assessment.assessment_type' => ['nullable', 'string', 'max:80'],
            'assessment.area' => ['nullable', 'numeric', 'min:0'],
            'assessment.unit_of_measure' => ['nullable', 'string', 'max:40'],
            'assessment.unit_value' => ['nullable', 'numeric', 'min:0'],
            'assessment.base_market_value' => ['nullable', 'numeric', 'min:0'],
            'assessment.adjustment' => ['nullable', 'numeric'],
            'assessment.depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'assessment.taxable' => ['nullable', 'boolean'],
            'assessment.notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $property, $validated) {
            $status = $validated['status'] ?? 'Draft';
            $activeDeclaration = $property->taxDeclarations()
                ->where('status', 'Active')
                ->orderByDesc('effectivity_year')
                ->orderByDesc('id')
                ->first();

            if ($status === 'Active' && $activeDeclaration) {
                $activeDeclaration->update(['status' => 'Superseded']);
            }

            $owner = Owner::firstOrCreate(
                ['name' => $validated['owner']['name']],
                ['address' => $validated['owner']['address'] ?? null]
            );

            if (! empty($validated['owner']['address']) && $owner->address !== $validated['owner']['address']) {
                $owner->update(['address' => $validated['owner']['address']]);
            }

            $declaration = $property->taxDeclarations()->create([
                'owner_id' => $owner->id,
                'previous_tax_declaration_id' => $activeDeclaration?->id,
                'td_number' => $validated['td_number'],
                'arp_number' => $validated['arp_number'] ?? null,
                'effectivity_year' => $validated['effectivity_year'],
                'classification' => $validated['classification'] ?? $property->classification,
                'actual_use' => $validated['actual_use'] ?? $property->actual_use,
                'market_value' => $validated['market_value'] ?? 0,
                'assessed_value' => $validated['assessed_value'] ?? 0,
                'assessment_level' => $validated['assessment_level'] ?? null,
                'status' => $status,
                'transaction_type' => $validated['transaction_type'] ?? 'Revision',
                'memoranda' => $validated['memoranda'] ?? null,
                'approved_at' => $validated['approved_at'] ?? null,
            ]);

            $assessment = $validated['assessment'] ?? [];

            $declaration->assessmentRecords()->create([
                'property_id' => $property->id,
                'assessment_type' => $assessment['assessment_type'] ?? 'Land',
                'classification' => $declaration->classification,
                'actual_use' => $declaration->actual_use,
                'area' => $assessment['area'] ?? $property->land_area,
                'unit_of_measure' => $assessment['unit_of_measure'] ?? $property->unit_of_measure,
                'unit_value' => $assessment['unit_value'] ?? 0,
                'base_market_value' => $assessment['base_market_value'] ?? $declaration->market_value,
                'adjustment' => $assessment['adjustment'] ?? 0,
                'depreciation_rate' => $assessment['depreciation_rate'] ?? null,
                'market_value' => $declaration->market_value,
                'assessment_level' => $declaration->assessment_level,
                'assessed_value' => $declaration->assessed_value,
                'taxable' => $assessment['taxable'] ?? true,
                'notes' => $assessment['notes'] ?? 'Assessment summary for this tax declaration.',
            ]);

            if ($status === 'Active') {
                $property->update([
                    'status' => 'Active',
                    'classification' => $declaration->classification,
                    'actual_use' => $declaration->actual_use,
                ]);
            }

            $property->activityLogs()->create([
                'user_id' => $request->user()?->id,
                'tax_declaration_id' => $declaration->id,
                'action' => 'tax_declaration_added',
                'description' => "Tax declaration {$declaration->td_number} added to the property history.",
                'new_values' => $declaration->load('owner')->toArray(),
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

    public function update(Request $request, Property $property, \App\Models\TaxDeclaration $taxDeclaration): JsonResponse
    {
        abort_unless($taxDeclaration->property_id === $property->id, 404);

        $validated = $request->validate([
            'owner.name' => ['required', 'string', 'max:180'],
            'owner.address' => ['nullable', 'string'],
            'td_number' => ['required', 'string', 'max:100', Rule::unique('tax_declarations', 'td_number')->ignore($taxDeclaration->id)],
            'arp_number' => ['nullable', 'string', 'max:100'],
            'effectivity_year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'classification' => ['nullable', 'string', 'max:80'],
            'actual_use' => ['nullable', 'string', 'max:80'],
            'market_value' => ['nullable', 'numeric', 'min:0'],
            'assessed_value' => ['nullable', 'numeric', 'min:0'],
            'assessment_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'string', 'max:40'],
            'transaction_type' => ['nullable', 'string', 'max:80'],
            'memoranda' => ['nullable', 'string'],
            'approved_at' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($request, $property, $taxDeclaration, $validated) {
            $oldValues = $taxDeclaration->load('owner')->toArray();

            $owner = Owner::firstOrCreate(
                ['name' => $validated['owner']['name']],
                ['address' => $validated['owner']['address'] ?? null]
            );

            if (! empty($validated['owner']['address']) && $owner->address !== $validated['owner']['address']) {
                $owner->update(['address' => $validated['owner']['address']]);
            }

            $taxDeclaration->update([
                'owner_id' => $owner->id,
                'td_number' => $validated['td_number'],
                'arp_number' => $validated['arp_number'] ?? null,
                'effectivity_year' => $validated['effectivity_year'],
                'classification' => $validated['classification'] ?? $taxDeclaration->classification,
                'actual_use' => $validated['actual_use'] ?? $taxDeclaration->actual_use,
                'market_value' => $validated['market_value'] ?? 0,
                'assessed_value' => $validated['assessed_value'] ?? 0,
                'assessment_level' => $validated['assessment_level'] ?? null,
                'status' => $validated['status'] ?? $taxDeclaration->status,
                'transaction_type' => $validated['transaction_type'] ?? $taxDeclaration->transaction_type,
                'memoranda' => $validated['memoranda'] ?? null,
                'approved_at' => $validated['approved_at'] ?? null,
            ]);

            $property->activityLogs()->create([
                'user_id' => $request->user()?->id,
                'tax_declaration_id' => $taxDeclaration->id,
                'action' => 'tax_declaration_updated',
                'description' => "Tax declaration {$taxDeclaration->td_number} updated.",
                'old_values' => $oldValues,
                'new_values' => $taxDeclaration->fresh('owner')->toArray(),
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
        ]));
    }

    public function approve(Request $request, Property $property, \App\Models\TaxDeclaration $taxDeclaration): JsonResponse
    {
        abort_unless($taxDeclaration->property_id === $property->id, 404);
        abort_unless($request->user()?->canApproveRecords(), 403);

        DB::transaction(function () use ($request, $property, $taxDeclaration) {
            $oldValues = $taxDeclaration->toArray();

            $property->taxDeclarations()
                ->where('id', '!=', $taxDeclaration->id)
                ->where('status', 'Active')
                ->update(['status' => 'Superseded']);

            $taxDeclaration->update([
                'status' => 'Active',
                'approved_at' => now(),
            ]);

            $property->update([
                'status' => 'Active',
                'classification' => $taxDeclaration->classification,
                'actual_use' => $taxDeclaration->actual_use,
            ]);

            $property->activityLogs()->create([
                'user_id' => $request->user()?->id,
                'tax_declaration_id' => $taxDeclaration->id,
                'action' => 'tax_declaration_approved',
                'description' => "Tax declaration {$taxDeclaration->td_number} approved as active.",
                'old_values' => $oldValues,
                'new_values' => $taxDeclaration->fresh()->toArray(),
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
        ]));
    }

    public function destroy(Request $request, Property $property, \App\Models\TaxDeclaration $taxDeclaration): JsonResponse
    {
        abort_unless($taxDeclaration->property_id === $property->id, 404);
        abort_unless($request->user()?->canAdminister(), 403);

        $oldValues = $taxDeclaration->toArray();
        $taxDeclaration->update(['status' => 'Cancelled']);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'tax_declaration_id' => $taxDeclaration->id,
            'action' => 'tax_declaration_cancelled',
            'description' => "Tax declaration {$taxDeclaration->td_number} cancelled.",
            'old_values' => $oldValues,
            'new_values' => $taxDeclaration->fresh()->toArray(),
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
}
