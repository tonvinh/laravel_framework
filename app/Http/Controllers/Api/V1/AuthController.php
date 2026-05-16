<?php

namespace App\Http\Controllers\Api\V1;

use App\Base\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Đăng nhập
     *
     * Xác thực thông tin đăng nhập và trả về Bearer token để dùng cho các request tiếp theo.
     *
     * @group Xác thực
     * @unauthenticated
     *
     * @bodyParam email string required Địa chỉ email đã đăng ký. Example: user@example.com
     * @bodyParam password string required Mật khẩu. Example: password123
     *
     * @response 200 {"data":{"token":"1|abc123xyz...","user":{"uuid":"550e8400-e29b-41d4-a716-446655440000","name":"Nguyen Van A","email":"user@example.com","created_at":"2026-05-16T04:00:00+00:00"}}}
     * @response 422 {"message":"The given data was invalid.","errors":{"email":["The provided credentials are incorrect."]}}
     */
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

    /**
     * Đăng ký tài khoản
     *
     * Tạo tài khoản mới và trả về Bearer token.
     *
     * @group Xác thực
     * @unauthenticated
     *
     * @bodyParam name string required Tên người dùng (tối đa 255 ký tự). Example: Nguyen Van A
     * @bodyParam email string required Địa chỉ email hợp lệ, chưa được đăng ký. Example: user@example.com
     * @bodyParam password string required Mật khẩu (tối thiểu 8 ký tự). Example: password123
     * @bodyParam password_confirmation string required Xác nhận mật khẩu (phải trùng với password). Example: password123
     *
     * @response 201 {"data":{"token":"1|abc123xyz...","user":{"uuid":"550e8400-e29b-41d4-a716-446655440000","name":"Nguyen Van A","email":"user@example.com","created_at":"2026-05-16T04:00:00+00:00"}}}
     * @response 422 {"message":"The given data was invalid.","errors":{"email":["The email has already been taken."]}}
     */
    public function register(Request $request): JsonResponse
    {
        $data = validator($request->all(), [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
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

    /**
     * Đăng xuất
     *
     * Hủy token hiện tại. Token sẽ không còn hợp lệ sau khi gọi endpoint này.
     *
     * @group Xác thực
     *
     * @response 204 {}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->noContent();
    }

    /**
     * Thông tin người dùng hiện tại
     *
     * Trả về thông tin tài khoản đang đăng nhập dựa trên Bearer token.
     *
     * @group Xác thực
     *
     * @response 200 {"data":{"uuid":"550e8400-e29b-41d4-a716-446655440000","name":"Nguyen Van A","email":"user@example.com","created_at":"2026-05-16T04:00:00+00:00"}}
     * @response 401 {"message":"Unauthenticated."}
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(UserResource::make($request->user()));
    }
}
