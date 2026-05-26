<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function matrix(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAdminister(), 403);

        $userCounts = User::query()
            ->selectRaw('role, status, COUNT(*) as total')
            ->groupBy('role', 'status')
            ->get()
            ->groupBy('role')
            ->map(fn ($rows) => [
                'total' => $rows->sum('total'),
                'active' => (int) ($rows->firstWhere('status', 'Active')->total ?? 0),
                'inactive' => (int) ($rows->firstWhere('status', 'Inactive')->total ?? 0),
            ]);

        $roles = collect(Permissions::roles())->map(function ($role) use ($userCounts) {
            $counts = $userCounts->get($role['value'], ['total' => 0, 'active' => 0, 'inactive' => 0]);

            return array_merge($role, ['user_counts' => $counts]);
        })->values();

        return response()->json([
            'roles' => $roles,
            'permission_groups' => Permissions::CATALOG,
            'totals' => [
                'users' => User::count(),
                'active_users' => User::where('status', 'Active')->count(),
                'inactive_users' => User::where('status', 'Inactive')->count(),
                'admins' => User::where('role', 'admin')->count(),
            ],
        ]);
    }

    public function loginStats(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAdminister(), 403);

        $since24h = now()->subDay();
        $since7d = now()->subDays(7);

        return response()->json([
            'last_24h' => [
                'success' => LoginActivity::where('attempted_at', '>=', $since24h)->where('status', 'success')->count(),
                'failed' => LoginActivity::where('attempted_at', '>=', $since24h)->where('status', 'failed')->count(),
                'locked' => LoginActivity::where('attempted_at', '>=', $since24h)->where('status', 'locked')->count(),
            ],
            'last_7d' => [
                'success' => LoginActivity::where('attempted_at', '>=', $since7d)->where('status', 'success')->count(),
                'failed' => LoginActivity::where('attempted_at', '>=', $since7d)->where('status', 'failed')->count(),
                'locked' => LoginActivity::where('attempted_at', '>=', $since7d)->where('status', 'locked')->count(),
            ],
            'top_failures' => LoginActivity::query()
                ->selectRaw('email, COUNT(*) as total')
                ->where('attempted_at', '>=', $since7d)
                ->where('status', 'failed')
                ->groupBy('email')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
        ]);
    }
}
