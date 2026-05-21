<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['The login credentials are incorrect.'],
            ]);
        }

        try {
            $passwordValid = Hash::check($validated['password'], $user->password);
        } catch (\RuntimeException) {
            $user->password = $validated['password'];
            $user->save();
            $passwordValid = Hash::check($validated['password'], $user->password);
        }

        if (! $passwordValid) {
            throw ValidationException::withMessages([
                'email' => ['The login credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'Active') {
            throw ValidationException::withMessages([
                'email' => ['This user account is not active.'],
            ]);
        }

        $abilities = match ($user->role) {
            User::ROLE_ADMIN => ['*'],
            User::ROLE_ASSESSOR => ['records:read', 'records:write', 'records:approve', 'exports:read'],
            User::ROLE_RECORDS_STAFF => ['records:read', 'records:write'],
            default => ['records:read'],
        };

        return response()->json([
            'token' => $user->createToken('assessor-api', $abilities)->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): array
    {
        return [
            'user' => $this->userPayload($request->user()),
        ];
    }

    public function logout(Request $request): array
    {
        $request->user()?->currentAccessToken()?->delete();

        return ['message' => 'Logged out.'];
    }

    private function userPayload(User $user): array
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
        ];
    }
}
