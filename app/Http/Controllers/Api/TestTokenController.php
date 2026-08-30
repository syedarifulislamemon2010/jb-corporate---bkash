<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TestTokenController extends Controller
{
    /**
     * Issue a personal access token for manual API testing (dev/staging only).
     */
    public function issueToken(Request $request): JsonResponse
    {
        // 1. Strict environment guard: Disable completely in production
        if (app()->environment('production')) {
            abort(403, 'Test auth endpoint disabled in production');
        }

        // 2. Validate input
        $data = $request->isJson() ? $request->json()->all() : $request->all();
        if (empty($data)) {
            $data = json_decode($request->getContent(), true) ?? [];
        }

        $validator = Validator::make($data, [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email    = $data['email'] ?? $request->input('email');
        $password = $data['password'] ?? $request->input('password');

        // 3. Verify user and password
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // 4. Generate Sanctum plainTextToken
        $token = $user->createToken('postman-test-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'organization' => $user->getRawOriginal('organization') ?? $user->organization_id,
                'role'         => $user->roles?->first()?->name ?? 'User',
            ],
        ], 200);
    }
}