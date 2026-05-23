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
        $html = $this->renderDossierHtml($payload);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    private function renderDossierHtml(array $payload): string
    {
        $property = $payload['property'] ?? [];
        $timeline = $payload['tax_declaration_timeline'] ?? [];
        $current = $payload['current_tax_declaration'] ?? null;
        $generatedAt = now()->format('F j, Y · g:i A');

        $pin = $this->safe($property['pin'] ?? '—');
        $lot = $this->safe($property['lot_number'] ?? '—');
        $title = $this->safe($property['title_number'] ?? '—');
        $survey = $this->safe($property['survey_number'] ?? '—');
        $barangay = $this->safe($property['barangay'] ?? '—');
        $municipality = $this->safe($property['municipality'] ?? '—');
        $province = $this->safe($property['province'] ?? '—');
        $classification = $this->safe($property['classification'] ?? '—');
        $actualUse = $this->safe($property['actual_use'] ?? '—');
        $area = number_format((float) ($property['land_area'] ?? 0), 2);
        $unit = $this->safe($property['unit_of_measure'] ?? 'sqm');
        $kind = $this->safe($property['property_kind'] ?? 'Land');
        $status = $this->safe($property['status'] ?? '—');

        $tdRows = '';
        foreach ($timeline as $entry) {
            $td = $entry['tax_declaration'] ?? [];
            $owner = $td['owner']['name'] ?? '—';
            $tdRows .= '<tr>';
            $tdRows .= '<td>' . $this->safe($td['effectivity_year'] ?? '—') . '</td>';
            $tdRows .= '<td><strong>' . $this->safe($td['td_number'] ?? '—') . '</strong></td>';
            $tdRows .= '<td>' . $this->safe($td['arp_number'] ?? '—') . '</td>';
            $tdRows .= '<td>' . $this->safe($td['transaction_type'] ?? '—') . '</td>';
            $tdRows .= '<td>' . $this->safe($owner) . '</td>';
            $tdRows .= '<td class="num">₱ ' . number_format((float) ($td['market_value'] ?? 0), 2) . '</td>';
            $tdRows .= '<td class="num">' . number_format((float) ($td['assessment_level'] ?? 0), 2) . '%</td>';
            $tdRows .= '<td class="num">₱ ' . number_format((float) ($td['assessed_value'] ?? 0), 2) . '</td>';
            $tdRows .= '<td><span class="status status-' . strtolower(str_replace(' ', '-', $td['status'] ?? '')) . '">' . $this->safe($td['status'] ?? '—') . '</span></td>';
            $tdRows .= '</tr>';
        }
        if (empty($tdRows)) {
            $tdRows = '<tr><td colspan="9" class="empty">No tax declarations on record.</td></tr>';
        }

        $currentOwner = $current['owner']['name'] ?? '—';
        $currentTd = $current['td_number'] ?? '—';
        $currentMv = '₱ ' . number_format((float) ($current['market_value'] ?? 0), 2);
        $currentAv = '₱ ' . number_format((float) ($current['assessed_value'] ?? 0), 2);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Property Dossier · {$pin}</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    color: #162742;
    margin: 0;
    padding: 24px;
    background: #fff;
    line-height: 1.5;
  }
  .doc-header {
    border-bottom: 3px solid #2f62af;
    padding-bottom: 16px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  .doc-header h1 {
    margin: 0;
    color: #183154;
    font-size: 1.8rem;
    font-weight: 800;
    letter-spacing: -0.01em;
  }
  .doc-header p {
    margin: 4px 0 0;
    color: #657892;
    font-size: 0.9rem;
  }
  .doc-meta {
    text-align: right;
    color: #657892;
    font-size: 0.78rem;
  }
  .doc-meta strong {
    display: block;
    color: #162742;
    font-size: 0.95rem;
    margin-bottom: 2px;
  }
  .section { margin-bottom: 24px; }
  .section-title {
    background: linear-gradient(90deg, #183154 0%, #2f62af 100%);
    color: #fff;
    padding: 10px 16px;
    font-size: 0.85rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    border-radius: 6px 6px 0 0;
  }
  .section-body {
    border: 1px solid #d0d8e3;
    border-top: none;
    border-radius: 0 0 6px 6px;
    padding: 16px;
    background: #fff;
  }
  .grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
  }
  .field {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .field span {
    font-size: 0.7rem;
    font-weight: 700;
    color: #657892;
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }
  .field strong {
    font-size: 0.95rem;
    font-weight: 700;
    color: #162742;
  }
  .summary-banner {
    display: flex;
    gap: 0;
    background: #f0f4fa;
    border: 1px solid #d0d8e3;
    border-radius: 8px;
    padding: 14px;
    margin-bottom: 16px;
  }
  .summary-banner .field {
    flex: 1;
    padding: 0 14px;
    border-right: 1px solid #d0d8e3;
  }
  .summary-banner .field:last-child { border-right: none; }
  .summary-banner .field strong { font-size: 1.05rem; color: #1e3f78; }
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
  }
  table th {
    background: #f0f4fa;
    color: #162742;
    text-align: left;
    padding: 10px 12px;
    border: 1px solid #d0d8e3;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 800;
  }
  table td {
    padding: 10px 12px;
    border: 1px solid #d0d8e3;
    color: #162742;
  }
  table td.num { text-align: right; font-variant-numeric: tabular-nums; }
  table td.empty { text-align: center; color: #657892; font-style: italic; }
  .status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background: #e6ecf6;
    color: #2f62af;
  }
  .status-active { background: #dcfce7; color: #166534; }
  .status-superseded { background: #f1f5f9; color: #475569; }
  .status-cancelled { background: #fee2e2; color: #991b1b; }
  .footer {
    margin-top: 32px;
    padding-top: 16px;
    border-top: 1px solid #d0d8e3;
    display: flex;
    justify-content: space-between;
    color: #657892;
    font-size: 0.78rem;
  }
  .signatures {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 40px;
    margin-top: 40px;
    page-break-inside: avoid;
  }
  .signature {
    text-align: center;
  }
  .signature-line {
    border-top: 1px solid #162742;
    margin-top: 60px;
    padding-top: 6px;
  }
  .signature-line strong { font-size: 0.85rem; }
  .signature-line span { font-size: 0.78rem; color: #657892; display: block; }
  @media print {
    body { padding: 0; }
    .no-print { display: none; }
    .section { page-break-inside: avoid; }
  }
  .print-btn {
    position: fixed;
    top: 16px;
    right: 16px;
    background: #2f62af;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
    font-size: 0.9rem;
    box-shadow: 0 4px 12px rgba(47, 98, 175, 0.3);
  }
  .print-btn:hover { background: #1e3f78; }
</style>
</head>
<body>
  <button class="print-btn no-print" onclick="window.print()">🖨️ Print / Save as PDF</button>

  <div class="doc-header">
    <div>
      <h1>Property Dossier</h1>
      <p>Republic of the Philippines · Province of {$province}</p>
      <p>Office of the Provincial Assessor</p>
    </div>
    <div class="doc-meta">
      <strong>{$pin}</strong>
      <div>Generated: {$generatedAt}</div>
      <div>Type: {$kind}</div>
    </div>
  </div>

  <div class="summary-banner">
    <div class="field">
      <span>Current Owner</span>
      <strong>{$currentOwner}</strong>
    </div>
    <div class="field">
      <span>Active TD</span>
      <strong>{$currentTd}</strong>
    </div>
    <div class="field">
      <span>Market Value</span>
      <strong>{$currentMv}</strong>
    </div>
    <div class="field">
      <span>Assessed Value</span>
      <strong>{$currentAv}</strong>
    </div>
    <div class="field">
      <span>Status</span>
      <strong>{$status}</strong>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Property Identification</div>
    <div class="section-body">
      <div class="grid">
        <div class="field"><span>PIN</span><strong>{$pin}</strong></div>
        <div class="field"><span>Lot Number</span><strong>{$lot}</strong></div>
        <div class="field"><span>Title Number</span><strong>{$title}</strong></div>
        <div class="field"><span>Survey Number</span><strong>{$survey}</strong></div>
        <div class="field"><span>Property Type</span><strong>{$kind}</strong></div>
        <div class="field"><span>Status</span><strong>{$status}</strong></div>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Location</div>
    <div class="section-body">
      <div class="grid">
        <div class="field"><span>Barangay</span><strong>{$barangay}</strong></div>
        <div class="field"><span>Municipality</span><strong>{$municipality}</strong></div>
        <div class="field"><span>Province</span><strong>{$province}</strong></div>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Classification &amp; Use</div>
    <div class="section-body">
      <div class="grid">
        <div class="field"><span>Classification</span><strong>{$classification}</strong></div>
        <div class="field"><span>Actual Use</span><strong>{$actualUse}</strong></div>
        <div class="field"><span>Land Area</span><strong>{$area} {$unit}</strong></div>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Tax Declaration History</div>
    <div class="section-body" style="padding: 0;">
      <table>
        <thead>
          <tr>
            <th>Year</th>
            <th>TD No.</th>
            <th>ARP No.</th>
            <th>Transaction</th>
            <th>Owner</th>
            <th>Market Value</th>
            <th>Level</th>
            <th>Assessed Value</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {$tdRows}
        </tbody>
      </table>
    </div>
  </div>

  <div class="signatures">
    <div class="signature">
      <div class="signature-line">
        <strong>PREPARED BY</strong>
        <span>Records Officer</span>
      </div>
    </div>
    <div class="signature">
      <div class="signature-line">
        <strong>CERTIFIED CORRECT</strong>
        <span>Provincial Assessor</span>
      </div>
    </div>
  </div>

  <div class="footer">
    <span>This dossier is system-generated from official records.</span>
    <span>Page 1 of 1 · Generated {$generatedAt}</span>
  </div>
</body>
</html>
HTML;
    }

    private function safe($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
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
