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
            'pending_approvals' => TaxDeclaration::whereIn('status', ['Draft', 'For Review'])->count(),

            // Chart data
            'by_classification' => Property::query()
                ->selectRaw('classification, COUNT(*) as count')
                ->groupBy('classification')
                ->pluck('count', 'classification'),
            'by_property_kind' => Property::query()
                ->selectRaw('property_kind, COUNT(*) as count')
                ->groupBy('property_kind')
                ->pluck('count', 'property_kind'),
            'by_municipality' => Property::query()
                ->selectRaw('municipality, COUNT(*) as count')
                ->groupBy('municipality')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'municipality'),
            'tds_by_year' => TaxDeclaration::query()
                ->selectRaw('effectivity_year, COUNT(*) as count')
                ->groupBy('effectivity_year')
                ->orderBy('effectivity_year')
                ->pluck('count', 'effectivity_year'),
            'tds_by_status' => TaxDeclaration::query()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'total_assessed_value' => TaxDeclaration::where('status', 'Active')->sum('assessed_value'),
            'total_market_value' => TaxDeclaration::where('status', 'Active')->sum('market_value'),

            'recent_activity' => ActivityLog::query()
                ->with('user:id,name,role')
                ->latest()
                ->limit(12)
                ->get(['id', 'property_id', 'action', 'description', 'created_at', 'user_id']),
        ];
    }
}
