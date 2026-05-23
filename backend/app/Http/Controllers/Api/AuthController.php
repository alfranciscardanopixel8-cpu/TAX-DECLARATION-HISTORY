<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.$request->ip().':'.strtolower($validated['email']);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->logActivity($request, $validated['email'], null, 'locked', "Rate limited for {$seconds}s");
            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Try again in {$seconds} seconds."],
            ]);
        }

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            RateLimiter::hit($key, 60);
            $this->logActivity($request, $validated['email'], null, 'failed', 'User not found');
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
            RateLimiter::hit($key, 60);
            $this->logActivity($request, $validated['email'], $user->id, 'failed', 'Wrong password');
            throw ValidationException::withMessages([
                'email' => ['The login credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'Active') {
            $this->logActivity($request, $validated['email'], $user->id, 'failed', 'Account not active');
            throw ValidationException::withMessages([
                'email' => ['This user account is not active.'],
            ]);
        }

        RateLimiter::clear($key);
        $this->logActivity($request, $validated['email'], $user->id, 'success', null);

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

    public function loginActivity(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAdminister(), 403);

        $logs = LoginActivity::query()
            ->with('user:id,name,email,role')
            ->when($request->filled('email'), fn ($q) => $q->where('email', 'like', '%'.$request->email.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('attempted_at')
            ->limit(200)
            ->get();

        return response()->json($logs);
    }

    private function logActivity(Request $request, string $email, ?int $userId, string $status, ?string $reason): void
    {
        try {
            LoginActivity::create([
                'email' => $email,
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'status' => $status,
                'reason' => $reason,
                'attempted_at' => now(),
            ]);
        } catch (\Throwable) {
            // Don't fail login if logging fails
        }
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
