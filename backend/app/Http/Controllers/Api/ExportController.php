<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AssessmentRecord;
use App\Models\Document;
use App\Models\PhysicalRecordMovement;
use App\Models\Property;
use App\Models\TaxDeclaration;
use App\Services\PropertyDossierService;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    public function __construct(private readonly PropertyDossierService $dossierService)
    {
    }

    public function propertyDossier(Property $property): Response
    {
        $payload = $this->dossierService->build($property);

        return response(json_encode($payload, JSON_PRETTY_PRINT), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="property-'.($property->pin ?: $property->id).'-dossier.json"',
        ]);
    }

    public function propertyActivityCsv(Property $property): Response
    {
        $property->load(['activityLogs.user:id,name,role', 'activityLogs.taxDeclaration:id,td_number']);

        $lines = [['Date', 'Action', 'Description', 'Staff', 'TD Number']];

        foreach ($property->activityLogs->sortBy('created_at') as $log) {
            $lines[] = [
                $log->created_at?->format('Y-m-d H:i') ?? '',
                $log->action,
                $log->description,
                $log->user?->name ?? 'System',
                $log->taxDeclaration?->td_number ?? '',
            ];
        }

        return response($this->csv($lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="property-'.($property->pin ?: $property->id).'-activity.csv"',
        ]);
    }

    public function property(Property $property): Response
    {
        $property->load([
            'taxDeclarations.owner',
            'taxDeclarations.assessmentRecords',
            'documents.movements.user:id,name,role',
            'activityLogs',
        ]);

        return response(json_encode($property, JSON_PRETTY_PRINT), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="property-'.$property->id.'-record.json"',
        ]);
    }

    public function propertiesCsv(): Response
    {
        $rows = Property::with(['taxDeclarations.owner', 'documents'])->latest()->get();

        $lines = [[
            'PIN',
            'Lot Number',
            'Title Number',
            'Barangay',
            'Municipality',
            'Classification',
            'Status',
            'Current TD',
            'Current Owner',
            'Documents',
        ]];

        foreach ($rows as $property) {
            $current = $property->taxDeclarations->firstWhere('status', 'Active') ?? $property->taxDeclarations->first();
            $lines[] = [
                $property->pin,
                $property->lot_number,
                $property->title_number,
                $property->barangay,
                $property->municipality,
                $property->classification,
                $property->status,
                $current?->td_number,
                $current?->owner?->name,
                $property->documents->count(),
            ];
        }

        return response($this->csv($lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="property-records.csv"',
        ]);
    }

    public function backup(): Response
    {
        $payload = [
            'exported_at' => now()->toISOString(),
            'properties' => Property::with([
                'taxDeclarations.owner',
                'taxDeclarations.assessmentRecords',
                'documents.movements',
                'activityLogs',
            ])->get(),
            'tax_declarations_count' => TaxDeclaration::count(),
            'assessment_records_count' => AssessmentRecord::count(),
            'documents_count' => Document::count(),
            'physical_movements_count' => PhysicalRecordMovement::count(),
            'activity_logs_count' => ActivityLog::count(),
        ];

        return response(json_encode($payload, JSON_PRETTY_PRINT), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="assessor-system-backup-'.now()->format('Ymd-His').'.json"',
        ]);
    }

    private function csv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);

        return stream_get_contents($handle);
    }
}
