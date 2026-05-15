<?php

namespace App\Http\Controllers\Api\V1;

use App\Base\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function login(Request $request): JsonResponse
    {
        $data = validator($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ])->validate();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $this->success([
            'token' => $user->createToken('api')->plainTextToken,
            'user'  => UserResource::make($user),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = validator($request->all(), [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ])->validate();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return $this->created([
            'token' => $user->createToken('api')->plainTextToken,
            'user'  => UserResource::make($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(UserResource::make($request->user()));
    }
}
