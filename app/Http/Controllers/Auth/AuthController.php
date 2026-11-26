<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Create a company together with its first user, who becomes the owner.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['company_name'],
                'slug' => $this->uniqueSlug($data['company_name']),
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
        });

        return $this->tokenResponse($this->guard()->login($user), Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->guard()->attempt($request->only('email', 'password'));

        abort_unless($token, Response::HTTP_UNAUTHORIZED, 'These credentials do not match our records.');

        return $this->tokenResponse($token);
    }

    public function refresh(): JsonResponse
    {
        return $this->tokenResponse($this->guard()->refresh());
    }

    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('tenant'));
    }

    private function tokenResponse(string $token, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $this->guard()->factory()->getTTL() * 60,
            ],
        ], $status);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard */
        return auth('api');
    }
}
