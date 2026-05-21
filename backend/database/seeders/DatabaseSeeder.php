<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Owner;
use App\Models\Property;
use App\Models\TaxDeclaration;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@assessor.local'],
            [
                'name' => 'System Administrator',
                'password' => 'password',
                'role' => 'admin',
                'status' => 'Active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'records@assessor.local'],
            [
                'name' => 'Records Staff',
                'password' => 'password',
                'role' => 'records_staff',
                'status' => 'Active',
            ]
        );

        $previousOwner = Owner::firstOrCreate(
            ['name' => 'Maria Santos', 'address' => 'Capital City'],
            ['name' => 'Maria Santos', 'address' => 'Capital City']
        );

        $currentOwner = Owner::firstOrCreate(
            ['name' => 'Juan Dela Cruz', 'address' => 'Brgy. San Isidro, Sample Municipality'],
            ['name' => 'Juan Dela Cruz', 'address' => 'Brgy. San Isidro, Sample Municipality']
        );

        $property = Property::updateOrCreate(
            ['pin' => '037-01-004-012-000'],
            [
                'property_index_number' => 'PIN-037-01-004-012-000',
                'lot_number' => 'Lot 1247-B',
                'survey_number' => 'PSD-03-001248',
                'title_number' => 'TCT-102938',
                'barangay' => 'San Isidro',
                'municipality' => 'Sample Municipality',
                'province' => 'Sample Province',
                'classification' => 'Residential',
                'actual_use' => 'Residential Lot',
                'land_area' => 480,
                'status' => 'Active',
                'remarks' => 'Sample chain with previous cancelled declaration.',
            ]
        );

        $previous = TaxDeclaration::updateOrCreate(
            ['td_number' => 'TD-2018-000441'],
            [
                'property_id' => $property->id,
                'owner_id' => $previousOwner->id,
                'arp_number' => 'ARP-2018-0441',
                'effectivity_year' => 2018,
                'classification' => 'Residential',
                'actual_use' => 'Residential Lot',
                'market_value' => 420000,
                'assessed_value' => 84000,
                'assessment_level' => 20,
                'status' => 'Superseded',
                'transaction_type' => 'Transfer',
                'memoranda' => 'Superseded by TD-2026-000128 after deed of sale.',
                'approved_at' => now()->subYears(8),
            ]
        );

        $current = TaxDeclaration::updateOrCreate(
            ['td_number' => 'TD-2026-000128'],
            [
                'property_id' => $property->id,
                'owner_id' => $currentOwner->id,
                'previous_tax_declaration_id' => $previous->id,
                'arp_number' => 'ARP-2026-0128',
                'effectivity_year' => 2026,
                'classification' => 'Residential',
                'actual_use' => 'Residential Lot',
                'market_value' => 690000,
                'assessed_value' => 138000,
                'assessment_level' => 20,
                'status' => 'Active',
                'transaction_type' => 'Transfer',
                'memoranda' => 'Current active declaration based on approved transfer documents.',
                'approved_at' => now()->subMonths(2),
            ]
        );

        $previous->assessmentRecords()->updateOrCreate(
            [
                'property_id' => $property->id,
                'assessment_type' => 'Land',
            ],
            [
                'classification' => 'Residential',
                'actual_use' => 'Residential Lot',
                'area' => 480,
                'unit_of_measure' => 'sqm',
                'base_market_value' => 420000,
                'market_value' => 420000,
                'assessment_level' => 20,
                'assessed_value' => 84000,
                'notes' => 'Sample previous land assessment.',
            ]
        );

        $current->assessmentRecords()->updateOrCreate(
            [
                'property_id' => $property->id,
                'assessment_type' => 'Land',
            ],
            [
                'classification' => 'Residential',
                'actual_use' => 'Residential Lot',
                'area' => 480,
                'unit_of_measure' => 'sqm',
                'base_market_value' => 690000,
                'market_value' => 690000,
                'assessment_level' => 20,
                'assessed_value' => 138000,
                'notes' => 'Sample current land assessment.',
            ]
        );

        $documents = [
            [
                'reference_number' => 'TD-2026-000128',
                'tax_declaration_id' => $current->id,
                'document_type' => 'Tax Declaration',
                'file_name' => 'TD-2026-000128.pdf',
                'file_path' => 'documents/sample/TD-2026-000128.pdf',
                'mime_type' => 'application/pdf',
                'issued_at' => now()->subMonths(2)->toDateString(),
                'physical_copy_status' => 'On File',
                'storage_location' => 'Records Room A',
                'shelf_number' => 'Shelf 01',
                'box_number' => 'Box 2026-03',
                'folder_number' => 'Folder 128',
                'custodian' => 'Assessment Records Section',
                'received_at' => now()->subMonths(2)->toDateString(),
            ],
            [
                'reference_number' => 'DOS-2026-0331',
                'tax_declaration_id' => $current->id,
                'document_type' => 'Deed of Sale',
                'file_name' => 'deed-of-sale-2026-0331.pdf',
                'file_path' => 'documents/sample/deed-of-sale-2026-0331.pdf',
                'mime_type' => 'application/pdf',
                'issued_at' => now()->subMonths(3)->toDateString(),
                'physical_copy_status' => 'On File',
                'storage_location' => 'Records Room A',
                'shelf_number' => 'Shelf 01',
                'box_number' => 'Box 2026-03',
                'folder_number' => 'Folder 128',
                'custodian' => 'Assessment Records Section',
                'received_at' => now()->subMonths(3)->toDateString(),
            ],
            [
                'reference_number' => 'TD-2018-000441',
                'tax_declaration_id' => $current->id,
                'document_type' => 'Tax Declaration',
                'file_name' => 'TD-2018-000441-physical.pdf',
                'file_path' => 'pending-upload/2018-td.pdf',
                'mime_type' => 'application/pdf',
                'issued_at' => now()->subYears(8)->toDateString(),
                'physical_copy_status' => 'For Scanning',
                'storage_location' => 'Records Room B',
                'shelf_number' => 'Shelf 14',
                'box_number' => 'Box 2018-07',
                'folder_number' => 'Folder 441',
                'custodian' => 'Assessment Records Section',
                'received_at' => now()->subYears(8)->toDateString(),
            ],
        ];

        foreach ($documents as $document) {
            Document::updateOrCreate(
                [
                    'property_id' => $property->id,
                    'reference_number' => $document['reference_number'],
                ],
                $document
            );
        }
    }
}
