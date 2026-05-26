<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'status', 'permission_grants', 'permission_denies', 'created_at'])
            ->map(fn (User $user) => $this->payload($user));

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAdminister(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_ASSESSOR,
                User::ROLE_RECORDS_STAFF,
                User::ROLE_VIEWER,
            ])],
            'status' => ['nullable', Rule::in(['Active', 'Inactive'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'status' => $validated['status'] ?? 'Active',
        ]);

        return response()->json($this->payload($user), 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->canAdminister(), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_ASSESSOR,
                User::ROLE_RECORDS_STAFF,
                User::ROLE_VIEWER,
            ])],
            'status' => ['sometimes', Rule::in(['Active', 'Inactive'])],
        ]);

        if (isset($validated['password'])) {
            $user->password = $validated['password'];
            unset($validated['password']);
        }

        $user->fill($validated);
        $user->save();

        return response()->json($this->payload($user->fresh()));
    }

    /**
     * Replace the user's permission overrides (grants + denies).
     */
    public function updatePermissions(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->canAdminister(), 403);

        $allKeys = Permissions::allKeys();

        $validated = $request->validate([
            'grants' => ['nullable', 'array'],
            'grants.*' => ['string', Rule::in($allKeys)],
            'denies' => ['nullable', 'array'],
            'denies.*' => ['string', Rule::in($allKeys)],
        ]);

        // Self-protection: an admin cannot deny themselves user.update or security.manage,
        // otherwise they could lock themselves out of the panel.
        if ($request->user()->id === $user->id) {
            $denies = $validated['denies'] ?? [];
            $protected = ['user.update', 'user.permissions', 'security.manage'];
            $forbidden = array_intersect($denies, $protected);

            abort_if(! empty($forbidden), 422, 'You cannot deny critical admin permissions for your own account: '.implode(', ', $forbidden));
        }

        $user->permission_grants = array_values(array_unique($validated['grants'] ?? []));
        $user->permission_denies = array_values(array_unique($validated['denies'] ?? []));
        $user->save();

        return response()->json($this->payload($user->fresh()));
    }

    /**
     * Reset overrides — the user reverts to their role's defaults.
     */
    public function resetPermissions(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->canAdminister(), 403);

        $user->permission_grants = [];
        $user->permission_denies = [];
        $user->save();

        return response()->json($this->payload($user->fresh()));
    }

    private function payload(User $user): array
    {
        $defaults = Permissions::defaultsForRole($user->role);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'can_manage_records' => $user->canManageRecords(),
            'can_approve_records' => $user->canApproveRecords(),
            'can_administer' => $user->canAdminister(),
            'permission_grants' => $user->permission_grants ?? [],
            'permission_denies' => $user->permission_denies ?? [],
            'role_defaults' => $defaults,
            'permissions' => $user->permissions(),
            'has_overrides' => ! empty($user->permission_grants ?? []) || ! empty($user->permission_denies ?? []),
            'created_at' => $user->created_at,
        ];
    }
}
