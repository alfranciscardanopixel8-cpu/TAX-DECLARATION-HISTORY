<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PropertyImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PropertyImportController extends Controller
{
    public function __construct(private readonly PropertyImportService $importService)
    {
    }

    public function template(): Response
    {
        return response($this->importService->templateCsv(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="property-import-template.csv"',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = $this->importService->importCsv($request->file('file'));

        return response()->json([
            'message' => 'Import completed.',
            ...$result,
            'error_count' => count($result['errors']),
        ]);
    }
}
