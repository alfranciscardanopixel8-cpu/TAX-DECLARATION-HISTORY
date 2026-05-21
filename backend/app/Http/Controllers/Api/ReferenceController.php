<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ReferenceController extends Controller
{
    public function __invoke(): array
    {
        return [
            'classifications' => ['Residential', 'Agricultural', 'Commercial', 'Industrial', 'Special'],
            'statuses' => ['Active', 'Draft', 'For Review', 'Cancelled', 'Superseded', 'Archived'],
            'transaction_types' => ['Original', 'Transfer', 'Revision', 'Subdivision', 'Consolidation', 'Reclassification', 'Correction'],
            'document_types' => ['Tax Declaration', 'Deed of Sale', 'Transfer Certificate of Title', 'Survey Plan', 'FAAS', 'Certification', 'Owner Request'],
            'assessment_types' => ['Land', 'Building', 'Machinery', 'Improvement', 'Special'],
            'physical_copy_statuses' => ['On File', 'For Scanning', 'Released', 'Returned', 'Missing', 'Archived'],
            'user_roles' => [
                ['label' => 'Administrator', 'value' => 'admin'],
                ['label' => 'Assessor', 'value' => 'assessor'],
                ['label' => 'Records Staff', 'value' => 'records_staff'],
                ['label' => 'Viewer', 'value' => 'viewer'],
            ],
        ];
    }
}
