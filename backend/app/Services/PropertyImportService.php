<?php

namespace App\Services;

use App\Models\Owner;
use App\Models\Property;
use App\Models\TaxDeclaration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PropertyImportService
{
    /**
     * @return array{created: int, updated: int, errors: array<int, string>}
     */
    public function importCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return ['created' => 0, 'updated' => 0, 'errors' => [0 => 'Unable to read upload file.']];
        }

        $headers = null;
        $created = 0;
        $updated = 0;
        $errors = [];
        $line = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if ($headers === null) {
                $headers = $this->normalizeHeaders($row);

                continue;
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $data = $this->mapRow($headers, $row);

            if (empty($data['pin'])) {
                $errors[$line] = 'Missing required column: pin';

                continue;
            }

            try {
                $wasCreated = DB::transaction(fn () => $this->upsertRow($data));

                if ($wasCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $exception) {
                $errors[$line] = $exception->getMessage();
            }
        }

        fclose($handle);

        return compact('created', 'updated', 'errors');
    }

    public function templateHeaders(): array
    {
        return [
            'pin',
            'lot_number',
            'barangay',
            'municipality',
            'province',
            'classification',
            'land_area',
            'owner_name',
            'owner_address',
            'td_number',
            'arp_number',
            'effectivity_year',
            'market_value',
            'assessed_value',
            'status',
        ];
    }

    public function templateCsv(): string
    {
        $lines = [
            implode(',', $this->templateHeaders()),
            '037-01-099-001-000,Lot 99-A,San Isidro,Sample Municipality,Sample Province,Residential,500,Juan Dela Cruz,Brgy. San Isidro,TD-2026-000999,ARP-2026-0999,2026,750000,150000,Active',
        ];

        return implode("\n", $lines)."\n";
    }

    private function normalizeHeaders(array $row): array
    {
        return array_map(
            fn ($header) => strtolower(trim(str_replace(' ', '_', $header))),
            $row
        );
    }

    private function mapRow(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($headers as $index => $header) {
            $mapped[$header] = isset($row[$index]) ? trim($row[$index]) : null;
        }

        return $mapped;
    }

    private function rowIsEmpty(array $row): bool
    {
        return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0;
    }

    private function upsertRow(array $data): bool
    {
        $existing = Property::query()->where('pin', $data['pin'])->exists();

        $property = Property::updateOrCreate(
            ['pin' => $data['pin']],
            [
                'property_index_number' => $data['property_index_number'] ?? ('PIN-'.$data['pin']),
                'lot_number' => $data['lot_number'] ?? null,
                'barangay' => $data['barangay'] ?? 'Unknown',
                'municipality' => $data['municipality'] ?? 'Unknown',
                'province' => $data['province'] ?? 'Unknown',
                'classification' => $data['classification'] ?? 'Residential',
                'actual_use' => $data['actual_use'] ?? 'Residential Lot',
                'land_area' => $data['land_area'] ?? 0,
                'unit_of_measure' => $data['unit_of_measure'] ?? 'sqm',
                'status' => $data['status'] ?? 'Active',
                'remarks' => $data['remarks'] ?? 'Imported from bulk CSV.',
            ]
        );

        if (empty($data['td_number'])) {
            return ! $existing;
        }

        $owner = Owner::firstOrCreate(
            ['name' => $data['owner_name'] ?? 'Unknown Owner'],
            ['address' => $data['owner_address'] ?? '']
        );

        TaxDeclaration::updateOrCreate(
            ['td_number' => $data['td_number']],
            [
                'property_id' => $property->id,
                'owner_id' => $owner->id,
                'arp_number' => $data['arp_number'] ?? null,
                'effectivity_year' => (int) ($data['effectivity_year'] ?? now()->year),
                'classification' => $data['classification'] ?? $property->classification,
                'actual_use' => $property->actual_use,
                'market_value' => (float) ($data['market_value'] ?? 0),
                'assessed_value' => (float) ($data['assessed_value'] ?? 0),
                'assessment_level' => (int) ($data['assessment_level'] ?? 20),
                'status' => $data['td_status'] ?? $data['status'] ?? 'Draft',
                'transaction_type' => $data['transaction_type'] ?? 'New',
            ]
        );

        return ! $existing;
    }
}
