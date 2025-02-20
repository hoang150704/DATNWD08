<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    // Register
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        // Tạo user nhưng chưa xác thực email
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // Tạo token xác thực email
        $verificationToken = Str::random(64);

        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $verificationToken, 'created_at' => Carbon::now()]
        );

        // Gửi email chứa link xác thực
        $verificationUrl = url("/api/verify-email?token=$verificationToken");

        Mail::raw("Click vào đây để xác thực email của bạn: $verificationUrl", function ($message) use ($user) {
            $message->to($user->email)->subject('Xác thực email');
        });

        return response()->json(['message' => 'Vui lòng kiểm tra email để xác thực tài khoản!']);
    }

    public function verifyEmail(Request $request)
    {
        $token = $request->query('token');

        $record = DB::table('email_verification_tokens')->where('token', $token)->first();

        if (!$record) {
            return response()->json(['message' => 'Token không hợp lệ!'], 400);
        }

        $user = User::where('email', $record->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Người dùng không tồn tại!'], 404);
        }

        // Cập nhật email_verified_at
        $user->email_verified_at = Carbon::now();
        $user->save();

        // Xóa token sau khi xác thực
        DB::table('email_verification_tokens')->where('email', $record->email)->delete();

        return response()->json(['message' => 'Email đã được xác thực!']);
    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'remember' => 'boolean' // Thêm tùy chọn "Remember Me"
        ]);

        $user = User::where('email', $request->email)->first();

        // Kiểm tra email có tồn tại không
        if (!$user) {
            return response()->json(['message' => 'Email hoặc mật khẩu không chính xác!'], 401);
        }

        // Kiểm tra email đã xác thực chưa
        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Vui lòng xác thực email trước khi đăng nhập!'], 403);
        }

        // Kiểm tra mật khẩu
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Email hoặc mật khẩu không chính xác!'], 401);
        }

        // Xóa token cũ nếu người dùng đăng nhập lại (tránh trùng lặp token)
        $user->tokens()->delete();

        // Nếu chọn "Remember Me", token sẽ có thời gian sống dài hơn
        $tokenExpiration = $request->remember ? now()->addWeeks(2) : now()->addHours(2);

        // Tạo token đăng nhập
        $token = $user->createToken('auth_token')->plainTextToken;

        // Kiểm tra vai trò và trả về đường dẫn phù hợp
        $redirect_url = $user->role === 'admin' ? '/admin/dashboard' : '/shop/home';

        return response()->json([
            'message' => 'Đăng nhập thành công!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'redirect_url' => $redirect_url,
        ]);
    }

    // Out
    public function logout(Request $request)
    {
        $user = $request->user(); // Lấy người dùng hiện tại

        if ($user) {
            $user->currentAccessToken()->delete(); // Xóa token hiện tại của người dùng
            return response()->json(['message' => 'Đăng xuất thành công!'], 200);
        }

        return response()->json(['message' => 'Không thể đăng xuất, vui lòng thử lại!'], 400);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        // $resetToken = random_int(1000000, 9999999);
        $resetToken = Str::random(64); // Tạo token bảo mật hơn

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $resetToken, 'created_at' => Carbon::now()]
        );

        $resetUrl = url("/api/reset-password?token=$resetToken");

        Mail::raw("Click vào đây để đặt lại mật khẩu: $resetUrl", function ($message) use ($request) {
            $message->to($request->email)->subject('Đặt lại mật khẩu');
        });

        return response()->json(['message' => 'Vui lòng kiểm tra email để đặt lại mật khẩu!']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        $record = DB::table('password_reset_tokens')->where('token', $request->token)->first();

        if (!$record) {
            return response()->json(['message' => 'Token không hợp lệ hoặc đã hết hạn!'], 400);
        }

        $user = User::where('email', $record->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Người dùng không tồn tại!'], 404);
        }

        // Cập nhật mật khẩu mới
        $user->password = bcrypt($request->password);
        $user->save();

        // Xóa token sau khi đặt lại mật khẩu thành công
        DB::table('password_reset_tokens')->where('email', $record->email)->delete();

        return response()->json(['message' => 'Mật khẩu đã được đặt lại thành công!']);
    }






}
