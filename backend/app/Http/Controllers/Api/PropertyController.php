<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Models\Owner;
use App\Models\Property;
use App\Services\PropertyDossierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    public function __construct(private readonly PropertyDossierService $dossierService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';
        $searchKeyword = $request->string('search')->toString();

        $properties = Property::query()
            ->with([
                // List view only needs the active TD + owner for the table.
                // Heavy relations (movements, activity logs, assessments) load on demand via /dossier.
                'taxDeclarations' => fn ($q) => $q->select(
                    'id', 'property_id', 'owner_id', 'td_number', 'arp_number',
                    'effectivity_year', 'classification', 'market_value',
                    'assessed_value', 'status', 'transaction_type'
                ),
                'taxDeclarations.owner:id,name,address',
            ])
            ->withCount(['taxDeclarations', 'documents'])
            ->search($searchKeyword)
            ->when($request->filled('lot_number'), fn ($query) => $query->where('lot_number', $operator, '%'.$request->lot_number.'%'))
            ->when($request->filled('property_kind'), fn ($query) => $query->where('property_kind', $request->property_kind))
            ->when($request->filled('municipality'), fn ($query) => $query->where('municipality', $request->municipality))
            ->when($request->filled('barangay'), fn ($query) => $query->where('barangay', $operator, '%'.$request->barangay.'%'))
            ->when($request->filled('classification'), fn ($query) => $query->where('classification', $request->classification))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('document_type'), fn ($query) => $query->whereHas(
                'documents',
                fn ($documentQuery) => $documentQuery->where('document_type', $request->document_type)
            ))
            ->when($request->filled('physical_copy_status'), fn ($query) => $query->whereHas(
                'documents',
                fn ($documentQuery) => $documentQuery->where('physical_copy_status', $request->physical_copy_status)
            ))
            ->when($request->filled('storage_location'), fn ($query) => $query->whereHas(
                'documents',
                fn ($documentQuery) => $documentQuery->where('storage_location', $operator, '%'.$request->storage_location.'%')
            ))
            ->when($request->filled('owner'), fn ($query) => $query->whereHas(
                'taxDeclarations.owner',
                fn ($ownerQuery) => $ownerQuery->where('name', $operator, '%'.$request->owner.'%')
            ))
            ->when($request->filled('td_status'), fn ($query) => $query->whereHas(
                'taxDeclarations',
                fn ($tdQuery) => $tdQuery->where('status', $request->td_status)
            ))
            ->when($request->filled('year_from'), fn ($query) => $query->whereHas(
                'taxDeclarations',
                fn ($tdQuery) => $tdQuery->where('effectivity_year', '>=', $request->integer('year_from'))
            ))
            ->when($request->filled('year_to'), fn ($query) => $query->whereHas(
                'taxDeclarations',
                fn ($tdQuery) => $tdQuery->where('effectivity_year', '<=', $request->integer('year_to'))
            ))
            ->when($searchKeyword, function ($query) use ($searchKeyword) {
                $query->orderByRaw(
                    config('database.default') === 'pgsql'
                        ? 'CASE WHEN lot_number ILIKE ? THEN 0 ELSE 1 END'
                        : 'CASE WHEN lot_number LIKE ? THEN 0 ELSE 1 END',
                    ['%'.$searchKeyword.'%']
                );
            })
            ->latest()
            ->paginate($request->integer('per_page', 25));

        if ($searchKeyword) {
            $properties->getCollection()->transform(function (Property $property) use ($searchKeyword) {
                $property->setAttribute(
                    'search_match',
                    $this->dossierService->detectSearchMatch($property, $searchKeyword)
                );

                return $property;
            });
        }

        return response()->json($properties);
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = DB::transaction(function () use ($request) {
            $propertyData = $request->safe()->except(['owner', 'tax_declaration', 'assessment', 'extra']);
            $propertyData['extra_attributes'] = $request->input('extra', []);

            $property = Property::create($propertyData);

            $owner = Owner::firstOrCreate(
                ['name' => $request->validated('owner.name')],
                ['address' => $request->validated('owner.address')]
            );

            $declaration = $property->taxDeclarations()->create([
                ...$request->validated('tax_declaration'),
                'owner_id' => $owner->id,
                'classification' => $property->classification,
                'actual_use' => $property->actual_use,
            ]);

            $assessment = $request->validated('assessment') ?? [];
            $marketValue = $request->validated('tax_declaration.market_value') ?? 0;
            $assessmentLevel = $request->validated('tax_declaration.assessment_level');

            $declaration->assessmentRecords()->create([
                'property_id' => $property->id,
                'assessment_type' => $assessment['assessment_type'] ?? 'Land',
                'classification' => $property->classification,
                'actual_use' => $property->actual_use,
                'area' => $assessment['area'] ?? $property->land_area,
                'unit_of_measure' => $assessment['unit_of_measure'] ?? $property->unit_of_measure,
                'unit_value' => $assessment['unit_value'] ?? 0,
                'base_market_value' => $assessment['base_market_value'] ?? $marketValue,
                'adjustment' => $assessment['adjustment'] ?? 0,
                'depreciation_rate' => $assessment['depreciation_rate'] ?? null,
                'market_value' => $marketValue,
                'assessment_level' => $assessmentLevel,
                'assessed_value' => $request->validated('tax_declaration.assessed_value') ?? 0,
                'taxable' => $assessment['taxable'] ?? true,
                'notes' => $assessment['notes'] ?? 'Initial assessment summary for this tax declaration.',
            ]);

            $property->activityLogs()->create([
                'user_id' => $request->user()?->id,
                'tax_declaration_id' => $declaration->id,
                'action' => 'created',
                'description' => 'Property master record and initial tax declaration encoded.',
                'new_values' => $property->fresh(['taxDeclarations.owner'])->toArray(),
            ]);

            return $property;
        });

        return response()->json($this->loadPropertyRelations($property), 201);
    }

    public function show(Property $property): JsonResponse
    {
        return response()->json($this->loadPropertyRelations($property));
    }

    public function dossier(Property $property): JsonResponse
    {
        return response()->json($this->dossierService->build($property));
    }

    public function update(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'pin' => ['required', 'string', 'max:80'],
            'property_index_number' => ['nullable', 'string', 'max:80'],
            'property_kind' => ['nullable', 'string', 'in:Land,Building,Machinery'],
            'lot_number' => ['nullable', 'string', 'max:80'],
            'survey_number' => ['nullable', 'string', 'max:80'],
            'title_number' => ['nullable', 'string', 'max:120'],
            'land_pin_reference' => ['nullable', 'string', 'max:80'],
            'barangay' => ['required', 'string', 'max:120'],
            'municipality' => ['required', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'classification' => ['required', 'string', 'max:80'],
            'actual_use' => ['nullable', 'string', 'max:80'],
            'land_area' => ['nullable', 'numeric', 'min:0'],
            'unit_of_measure' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:40'],
            'remarks' => ['nullable', 'string'],
            'extra' => ['nullable', 'array'],
        ]);

        if (array_key_exists('extra', $validated)) {
            $validated['extra_attributes'] = $validated['extra'] ?? [];
            unset($validated['extra']);
        }

        $oldValues = $property->toArray();

        $property->update($validated);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'action' => 'updated',
            'description' => 'Property master record updated.',
            'old_values' => $oldValues,
            'new_values' => $property->fresh()->toArray(),
        ]);

        return response()->json($this->loadPropertyRelations($property->fresh()));
    }

    public function approve(Request $request, Property $property): JsonResponse
    {
        abort_unless($request->user()?->canApproveRecords(), 403);

        $oldValues = $property->toArray();

        $property->update(['status' => 'Active']);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'action' => 'approved',
            'description' => 'Property record approved.',
            'old_values' => $oldValues,
            'new_values' => $property->fresh()->toArray(),
        ]);

        return response()->json($this->loadPropertyRelations($property->fresh()));
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        abort_unless($request->user()?->canAdminister(), 403);

        $oldValues = $property->toArray();

        $property->update(['status' => 'Archived']);

        $property->activityLogs()->create([
            'user_id' => $request->user()?->id,
            'action' => 'archived',
            'description' => 'Property record archived.',
            'old_values' => $oldValues,
            'new_values' => $property->fresh()->toArray(),
        ]);

        return response()->json($this->loadPropertyRelations($property->fresh()));
    }

    private function loadPropertyRelations(Property $property): Property
    {
        return $property->load([
            'taxDeclarations.owner',
            'taxDeclarations.previousTaxDeclaration.owner',
            'taxDeclarations.assessmentRecords',
            'taxDeclarations.documents',
            'assessmentRecords.taxDeclaration',
            'documents.taxDeclaration',
            'documents.digitizedBy:id,name,role',
            'documents.movements.user:id,name,role',
            'activityLogs.user:id,name,role',
            'activityLogs.taxDeclaration:id,td_number',
        ]);
    }
}
