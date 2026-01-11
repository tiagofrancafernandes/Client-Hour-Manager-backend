<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => __('auth.failed'),
            ], 401);
        }

        $user = Auth::user();

        if (! $user->active) {
            Auth::logout();

            return response()->json([
                'message' => __('auth.inactive_account'),
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function info(Request $request): JsonResponse
    {
        if (! $request->user()) {
            return response()->json([
                'message' => __('auth.unauthenticated'),
            ], 401);
        }

        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if (! $request->user()) {
            return response()->json([
                'message' => __('auth.unauthenticated'),
            ], 401);
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => __('auth.logged_out'),
        ]);
    }
}
