<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\PropertyDossierService;
use Illuminate\Http\JsonResponse;

class DigitizationQueueController extends Controller
{
    public function __construct(private readonly PropertyDossierService $dossierService)
    {
    }

    public function index(): JsonResponse
    {
        $documents = Document::query()
            ->with([
                'property:id,pin,lot_number,barangay,municipality,status',
                'taxDeclaration:id,td_number,effectivity_year',
                'digitizedBy:id,name',
            ])
            ->latest('updated_at')
            ->get()
            ->filter(fn (Document $document) => $this->dossierService->needsDigitization($document))
            ->values()
            ->map(fn (Document $document) => [
                ...$this->dossierService->presentDocument($document),
                'property' => $document->property,
                'tax_declaration' => $document->taxDeclaration,
            ]);

        return response()->json([
            'count' => $documents->count(),
            'items' => $documents,
        ]);
    }
}
