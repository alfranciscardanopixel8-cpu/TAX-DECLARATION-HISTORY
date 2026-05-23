<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';

        $owners = Owner::query()
            ->withCount('taxDeclarations')
            ->when($request->filled('search'), fn ($q) => $q->where('name', $operator, '%'.$request->search.'%'))
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'address', 'contact_number', 'email', 'tin']);

        return response()->json($owners);
    }

    public function show(Owner $owner): JsonResponse
    {
        $owner->load([
            'taxDeclarations' => function ($query) {
                $query->with([
                    'property:id,pin,lot_number,survey_number,title_number,barangay,municipality,province,classification,property_kind,actual_use,land_area,unit_of_measure,status'
                ])->orderByDesc('effectivity_year');
            },
        ]);

        $properties = $owner->taxDeclarations
            ->groupBy('property_id')
            ->map(function ($tds) {
                $property = $tds->first()->property;
                if (!$property) return null;

                $activeTd = $tds->firstWhere('status', 'Active') ?? $tds->first();

                return [
                    'property' => $property,
                    'active_td' => $activeTd,
                    'td_count' => $tds->count(),
                    'all_tds' => $tds->map(fn ($td) => [
                        'id' => $td->id,
                        'td_number' => $td->td_number,
                        'effectivity_year' => $td->effectivity_year,
                        'status' => $td->status,
                        'transaction_type' => $td->transaction_type,
                    ])->values(),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'owner' => $owner->only(['id', 'name', 'address', 'contact_number', 'email', 'tin']),
            'properties' => $properties,
            'total_properties' => $properties->count(),
            'total_tds' => $owner->taxDeclarations->count(),
            'total_assessed_value' => $owner->taxDeclarations
                ->where('status', 'Active')
                ->sum('assessed_value'),
        ]);
    }
}
