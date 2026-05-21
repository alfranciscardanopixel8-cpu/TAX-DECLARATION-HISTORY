<?php

namespace App\Services;

use App\Models\Property;

class PropertyDossierService
{
    public function build(Property $property): array
    {
        $property->load([
            'taxDeclarations.owner',
            'taxDeclarations.previousTaxDeclaration',
            'taxDeclarations.assessmentRecords',
            'taxDeclarations.documents.movements.user:id,name,role',
            'taxDeclarations.documents.digitizedBy:id,name,role',
            'assessmentRecords.taxDeclaration',
            'documents.taxDeclaration',
            'documents.movements.user:id,name,role',
            'documents.digitizedBy:id,name,role',
            'activityLogs.user:id,name,role',
            'activityLogs.taxDeclaration:id,td_number',
            'activityLogs.document:id,document_type,reference_number',
        ]);

        $taxDeclarations = $property->taxDeclarations;
        $currentDeclaration = $taxDeclarations->firstWhere('status', 'Active') ?? $taxDeclarations->first();
        $propertyDocuments = $property->documents->whereNull('tax_declaration_id')->values();
        $documentsByTd = $property->documents->whereNotNull('tax_declaration_id')->groupBy('tax_declaration_id');

        $timeline = $taxDeclarations->map(function ($declaration) use ($documentsByTd, $property) {
            $documents = ($documentsByTd->get($declaration->id) ?? collect())
                ->merge($declaration->documents)
                ->unique('id')
                ->values()
                ->map(fn ($document) => $this->presentDocument($document));

            $dataEntryEvents = $property->activityLogs
                ->filter(fn ($log) => $log->tax_declaration_id === $declaration->id
                    || ($log->document && $log->document->tax_declaration_id === $declaration->id))
                ->values()
                ->map(fn ($log) => $this->presentActivityLog($log));

            return [
                'tax_declaration' => $declaration,
                'assessment_records' => $declaration->assessmentRecords->values(),
                'documents' => $documents,
                'document_count' => $documents->count(),
                'assessment_count' => $declaration->assessmentRecords->count(),
                'data_entry_events' => $dataEntryEvents,
            ];
        })->values();

        $ownerHistory = $taxDeclarations
            ->map(fn ($declaration) => [
                'owner_id' => $declaration->owner?->id,
                'owner_name' => $declaration->owner?->name,
                'owner_address' => $declaration->owner?->address,
                'td_number' => $declaration->td_number,
                'effectivity_year' => $declaration->effectivity_year,
                'transaction_type' => $declaration->transaction_type,
                'status' => $declaration->status,
            ])
            ->values();

        $pendingDigitization = $property->documents
            ->filter(fn ($document) => $this->needsDigitization($document))
            ->values()
            ->map(fn ($document) => $this->presentDocument($document));

        return [
            'property' => $property,
            'current_tax_declaration' => $currentDeclaration,
            'tax_declaration_history' => $taxDeclarations->values(),
            'tax_declaration_timeline' => $timeline,
            'property_documents' => $propertyDocuments->map(fn ($document) => $this->presentDocument($document))->values(),
            'owner_history' => $ownerHistory,
            'documents_by_type' => $property->documents->groupBy('document_type')->map->values(),
            'documents_by_tax_declaration' => $documentsByTd->map(fn ($docs) => $docs->map(fn ($document) => $this->presentDocument($document))->values()),
            'assessments_by_tax_declaration' => $property->assessmentRecords
                ->groupBy('tax_declaration_id')
                ->map(fn ($records) => $records->values()),
            'data_entry_timeline' => $property->activityLogs->map(fn ($log) => $this->presentActivityLog($log))->values(),
            'pending_digitization' => $pendingDigitization,
            'valuation_summary' => [
                'highest_market_value' => $taxDeclarations->max('market_value') ?? 0,
                'highest_assessed_value' => $taxDeclarations->max('assessed_value') ?? 0,
                'assessment_record_market_value' => $property->assessmentRecords->sum('market_value'),
                'assessment_record_assessed_value' => $property->assessmentRecords->sum('assessed_value'),
                'earliest_effectivity_year' => $taxDeclarations->min('effectivity_year'),
                'latest_effectivity_year' => $taxDeclarations->max('effectivity_year'),
            ],
            'counts' => [
                'tax_declarations' => $taxDeclarations->count(),
                'assessment_records' => $property->assessmentRecords->count(),
                'documents' => $property->documents->count(),
                'digitized_documents' => $property->documents->filter(fn ($document) => ! $this->needsDigitization($document))->count(),
                'pending_digitization' => $pendingDigitization->count(),
                'owners' => $taxDeclarations->pluck('owner_id')->unique()->count(),
                'audit_events' => $property->activityLogs->count(),
            ],
        ];
    }

    public function presentDocument($document): array
    {
        $data = $document->toArray();
        $data['is_digitized'] = ! $this->needsDigitization($document);
        $data['needs_digitization'] = $this->needsDigitization($document);

        return $data;
    }

    public function needsDigitization($document): bool
    {
        if ($document->physical_copy_status === 'For Scanning') {
            return true;
        }

        return str_starts_with((string) $document->file_path, 'pending-upload/');
    }

    public function presentActivityLog($log): array
    {
        return [
            'id' => $log->id,
            'action' => $log->action,
            'description' => $log->description,
            'created_at' => $log->created_at,
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
                'role' => $log->user->role,
            ] : null,
            'tax_declaration_id' => $log->tax_declaration_id,
            'tax_declaration' => $log->taxDeclaration ? [
                'id' => $log->taxDeclaration->id,
                'td_number' => $log->taxDeclaration->td_number,
            ] : null,
            'document_id' => $log->document_id,
            'document' => $log->document ? [
                'id' => $log->document->id,
                'document_type' => $log->document->document_type,
                'reference_number' => $log->document->reference_number,
            ] : null,
        ];
    }

    public function detectSearchMatch(Property $property, string $keyword): ?array
    {
        $needle = mb_strtolower(trim($keyword));
        $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';

        $matches = static function (?string $value) use ($needle, $operator): bool {
            if (! $value) {
                return false;
            }

            if ($operator === 'ilike') {
                return mb_stripos($value, $needle) !== false;
            }

            return str_contains(mb_strtolower($value), $needle);
        };

        if ($matches($property->lot_number)) {
            return ['matched_on' => 'lot_number', 'matched_value' => $property->lot_number];
        }

        if ($matches($property->pin)) {
            return ['matched_on' => 'pin', 'matched_value' => $property->pin];
        }

        if ($matches($property->property_index_number)) {
            return ['matched_on' => 'property_index_number', 'matched_value' => $property->property_index_number];
        }

        foreach ($property->taxDeclarations as $declaration) {
            if ($matches($declaration->td_number)) {
                return ['matched_on' => 'td_number', 'matched_value' => $declaration->td_number];
            }

            if ($matches($declaration->arp_number)) {
                return ['matched_on' => 'arp_number', 'matched_value' => $declaration->arp_number];
            }

            if ($matches($declaration->owner?->name)) {
                return ['matched_on' => 'owner', 'matched_value' => $declaration->owner->name];
            }
        }

        foreach ($property->documents as $document) {
            if ($matches($document->document_type) || $matches($document->reference_number) || $matches($document->file_name)) {
                return ['matched_on' => 'document', 'matched_value' => $document->reference_number ?: $document->document_type];
            }
        }

        if ($matches($property->survey_number)) {
            return ['matched_on' => 'survey_number', 'matched_value' => $property->survey_number];
        }

        if ($matches($property->title_number)) {
            return ['matched_on' => 'title_number', 'matched_value' => $property->title_number];
        }

        if ($matches($property->barangay)) {
            return ['matched_on' => 'barangay', 'matched_value' => $property->barangay];
        }

        return null;
    }
}
