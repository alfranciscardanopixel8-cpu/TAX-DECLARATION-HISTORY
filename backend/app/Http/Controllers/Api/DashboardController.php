<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AssessmentRecord;
use App\Models\Document;
use App\Models\Property;
use App\Models\TaxDeclaration;

class DashboardController extends Controller
{
    public function __invoke(): array
    {
        return [
            'properties' => Property::count(),
            'active_properties' => Property::where('status', 'Active')->count(),
            'for_review' => Property::where('status', 'For Review')->count(),
            'tax_declarations' => TaxDeclaration::count(),
            'active_tax_declarations' => TaxDeclaration::where('status', 'Active')->count(),
            'assessment_records' => AssessmentRecord::count(),
            'documents' => Document::count(),
            'physical_on_file' => Document::where('physical_copy_status', 'On File')->count(),
            'physical_for_scanning' => Document::where('physical_copy_status', 'For Scanning')->count(),
            'physical_released' => Document::where('physical_copy_status', 'Released')->count(),
            'physical_missing' => Document::where('physical_copy_status', 'Missing')->count(),
            'pending_review' => Property::where('status', 'For Review')->count()
                + TaxDeclaration::where('status', 'For Review')->count(),
            'recent_activity' => ActivityLog::query()
                ->with('user:id,name,role')
                ->latest()
                ->limit(12)
                ->get(['id', 'property_id', 'action', 'description', 'created_at', 'user_id']),
        ];
    }
}
