<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    /**
     * Paginated audit log feed with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->buildQuery($request);

        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(5, min($perPage, 100));

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Reference data for the audit log filters (distinct actions, totals).
     */
    public function summary(Request $request): JsonResponse
    {
        $actions = ActivityLog::query()
            ->select('action', DB::raw('COUNT(*) as total'))
            ->groupBy('action')
            ->orderBy('action')
            ->get();

        $today = ActivityLog::query()->whereDate('created_at', now()->toDateString())->count();
        $week = ActivityLog::query()->where('created_at', '>=', now()->subDays(7))->count();

        return response()->json([
            'actions' => $actions,
            'totals' => [
                'all_time' => ActivityLog::count(),
                'today' => $today,
                'last_7_days' => $week,
            ],
        ]);
    }

    /**
     * Streamed CSV export of the filtered audit log.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildQuery($request);
        $filename = 'audit-logs-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Logged At',
                'Action',
                'Description',
                'User',
                'Role',
                'Property ID',
                'Property PIN',
                'Tax Declaration',
                'Document',
            ]);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        optional($log->created_at)->toDateTimeString(),
                        $log->action,
                        $log->description,
                        $log->user?->name,
                        $log->user?->role,
                        $log->property_id,
                        $log->property?->pin,
                        $log->taxDeclaration?->td_number,
                        $log->document?->reference_number,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Shared filterable query used by index + export.
     */
    protected function buildQuery(Request $request)
    {
        $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';

        $query = ActivityLog::query()
            ->with([
                'user:id,name,role',
                'property:id,pin,lot_number,barangay,municipality',
                'taxDeclaration:id,td_number',
                'document:id,document_type,reference_number',
            ])
            ->latest();

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->input('property_id'));
        }

        if ($request->filled('action')) {
            $actions = (array) $request->input('action');
            $query->whereIn('action', $actions);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        if ($request->filled('search')) {
            $keyword = '%'.trim($request->input('search')).'%';
            $query->where(function ($q) use ($keyword, $operator) {
                $q->where('description', $operator, $keyword)
                    ->orWhereHas('user', fn ($u) => $u->where('name', $operator, $keyword))
                    ->orWhereHas('property', fn ($p) => $p->where('pin', $operator, $keyword)->orWhere('lot_number', $operator, $keyword))
                    ->orWhereHas('taxDeclaration', fn ($t) => $t->where('td_number', $operator, $keyword));
            });
        }

        return $query;
    }
}
