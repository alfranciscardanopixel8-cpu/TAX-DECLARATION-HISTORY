<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'status', 'created_at']);

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

    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'can_manage_records' => $user->canManageRecords(),
            'can_approve_records' => $user->canApproveRecords(),
            'can_administer' => $user->canAdminister(),
            'created_at' => $user->created_at,
        ];
    }
}
