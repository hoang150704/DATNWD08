<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Jobs\SendEmailVerificationUserJob;
use App\Jobs\SendEmailVerifyNewEmailJob;
use App\Jobs\SendResetPasswordEmailJob;
use App\Models\Cart;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;



class AuthController extends Controller
{

    // Register
    public function register(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'username' => 'required|max:50|unique:users,username',
                'password' => 'required|min:8|max:25|confirmed',
            ]);

            // Tạo user nhưng chưa xác thực email
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => bcrypt($request->password),
                // 'email_verified_at'=>now()
            ]);

            $cart = Cart::create([
                'user_id' => $user->id
            ]);

            // Dispatch Job gửi email xác thực
            SendEmailVerificationUserJob::dispatch($user);

            DB::commit(); // Lưu thay đổi vào database

            return response()->json(['message' => 'Vui lòng kiểm tra email để xác thực tài khoản!'], 200);
        } catch (\Throwable $th) {
            DB::rollBack(); // Nếu lỗi, quay lại trạng thái cũ
            Log::error("Lỗi đăng ký: " . $th->getMessage());
            return response()->json(['message' => 'Lỗi đăng ký', 'errors' => $th->getMessage()], 500);
        }
    }


    public function verifyEmail(Request $request)
    {
        DB::beginTransaction();
        try {
            $token = $request->query('token');

            if (!$token) {
                Log::error('Xác thực email thất bại: Token không tồn tại trong request.');
                return response()->json(['message' => 'Token không hợp lệ!', "code" => 401], 200);
            }

            $record = DB::table('email_verification_tokens')->where('token', $token)->first();

            if (!$record) {
                Log::error("Xác thực email thất bại: Token không hợp lệ - Token: $token");
                return response()->json(['message' => 'Token không hợp lệ!', "code" => 400], 200);
            }

            $user = User::where('email', $record->email)->first();
            if (!$user) {
                Log::error("Xác thực email thất bại: Không tìm thấy người dùng có email - Email: " . $record->email);
                return response()->json(['message' => 'Người dùng không tồn tại!', "code" => 404], 200);
            }

            // Cập nhật email_verified_at
            $user->email_verified_at = Carbon::now();
            $user->save();

            // Xóa token sau khi xác thực
            DB::table('email_verification_tokens')->where('email', $record->email)->delete();

            DB::commit(); // Lưu thay đổi vào database
            Log::info("Xác thực email thành công: Email " . $user->email . " đã được xác thực.");

            return response()->json(['message' => 'Email đã được xác thực!', "code" => 200]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Lỗi xác thực email: " . $th->getMessage());
            return response()->json(['message' => 'Đã xảy ra lỗi khi xác thực email.'], 500);
        }
    }


    // Login
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'remember' => 'boolean' // Thêm tùy chọn "Remember Me"
        ]);

        $user = User::with('library')->where('username', $request->username)->first();

        // Kiểm tra email có tồn tại không
        if (!$user) {
            return response()->json(['message' => 'Email hoặc mật khẩu không chính xác!'], 401);
        }

        // Kiểm tra email đã xác thực chưa
        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Vui lòng xác thực email trước khi đăng nhập!'], 403);
        }

        // Kiểm tra mật khẩu
        if (!Auth::attempt($request->only('username', 'password'))) {
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

    //Đổi email mới
    public function requestChangeEmail(Request $request)
    {
        $request->validate([
            'new_email' => 'required|email|unique:users,email',
            'password' => 'required',
        ], [
            'new_email.required' => 'Email không được để trống!',
            'new_email.email' => 'Email không hợp lệ!',
            'new_email.unique' => 'Email đã tồn tại trong hệ thống!',
            'password.required' => 'Mật khẩu không được để trống!',
        ]);

        // Lấy user đang đăng nhập
        $user = Auth::user();

        // Kiểm tra email mới có giống email hiện tại không
        if ($request->new_email == $user->email) {
            return response()->json(['message' => 'Email mới không được trùng với email hiện tại!'], 400);
        }

        // Kiểm tra mật khẩu
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Mật khẩu không chính xác!'], 401);
        }

        // Giới hạn gửi OTP 1 lần mỗi phút
        $existingOtp = DB::table('email_verification_tokens')
            ->where('user_id', $user->id)
            ->where('email', $request->new_email)
            ->where('created_at', '>', Carbon::now()->subMinutes(1))
            ->first();

        if ($existingOtp) {
            return response()->json(['message' => 'Vui lòng gửi lại mã sau 1 phút!'], 429);
        }

        // Tạo OTP ngẫu nhiên, đảm bảo không bị trùng
        do {
            $otp = mt_rand(100000, 999999);
            $otpExists = DB::table('email_verification_tokens')->where('token', $otp)->exists();
        } while ($otpExists);

        // Xóa mã OTP cũ của user cho cùng email (nếu có)
        DB::table('email_verification_tokens')
            ->where('user_id', $user->id)
            ->delete();

        // Lưu OTP mới vào database
        DB::table('email_verification_tokens')->insert([
            'email' => $request->new_email,
            'user_id' => $user->id,
            'token' => $otp,
            'created_at' => Carbon::now(),
        ]);

        // chạy email
        SendEmailVerifyNewEmailJob::dispatch($user, $request->new_email, $otp);

        return response()->json(['message' => 'Vui lòng kiểm tra email để nhận mã xác thực!'], 200);
    }


    // Xác thực và đổi email
    public function verifyNewEmail(Request $request)
    {
        DB::beginTransaction();
        try {

            $data = $request->validate([
                'token' => 'required',
            ], ['token.required' => 'Không tìm thấy token']);

            // Tìm token trong database
            $record = DB::table('email_verification_tokens')->where('token', $data['token'])->first();

            if (!$record) {
                Log::error("Đổi thất bại: Token không hợp lệ - Token: " . $data['token']);
                return response()->json(['message' => 'Token không hợp lệ!'], 400);
            }
            // 
            $expiredTime = Carbon::parse($record->created_at)->addSeconds(15);
            if (now()->greaterThan($expiredTime)) {
                // Xóa token khỏi DB
                DB::table('email_verification_tokens')->where('token', $data['token'])->delete();
                DB::commit();
                return response()->json(['message' => 'Token đã hết hạn!'], 410); // 410 Gone: Token không còn hợp lệ
            }
            // Kiểm tra xem người dùng có tồn tại không
            $user = User::find($record->user_id);
            if (!$user) {
                Log::error("Đổi email thất bại: Không tìm thấy người dùng cần thay đổi email");
                return response()->json(['message' => 'Người dùng không tồn tại!'], 404);
            }

            // Kiểm tra email đã được sử dụng chưa
            if (User::where('email', $record->email)->exists()) {
                Log::error("Email này đã có người dùng xác thực: " . $record->email);
                DB::table('email_verification_tokens')->where('email', $record->email)->delete();
                DB::commit();
                return response()->json(['message' => 'Email này đã có người dùng xác thực'], 409);
            }

            // Cập nhật email và thời gian xác thực
            $user->update([
                'email' => $record->email,
                'email_verified_at' => now(),
            ]);

            // Xóa token sau khi xác thực thành công
            DB::table('email_verification_tokens')->where('email', $record->email)->delete();

            DB::commit(); // Lưu thay đổi vào database
            Log::info("Xác thực email thành công: Email " . $user->email . " đã được xác thực.");

            return response()->json(['message' => 'Email đã được xác thực!'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Lỗi xác thực email: " . $th->getMessage());

            return response()->json([
                'message' => 'Đã xảy ra lỗi khi xác thực email.',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    // 
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        SendResetPasswordEmailJob::dispatch($request->email);

        return response()->json(['message' => 'Vui lòng kiểm tra email để đặt lại mật khẩu!']);
    }

    public function resetPassword(Request $request)
    {
        DB::beginTransaction();
        try {
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

            DB::commit(); // Lưu thay đổi vào database
            Log::info("Mật khẩu của người dùng {$user->email} đã được đặt lại thành công.");

            return response()->json(['message' => 'Mật khẩu đã được đặt lại thành công!']);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Lỗi đặt lại mật khẩu: " . $th->getMessage());
            return response()->json(['message' => 'Đã xảy ra lỗi khi đặt lại mật khẩu.'], 500);
        }
    }
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleAuth()
    {

        try {
            // Xác thực token từ Google
            $googleUser = Socialite::driver('google')->user();

            // Kiểm tra user có tồn tại không
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Nếu chưa có user, tạo mới
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(uniqid()), 
                ]);
            }

            // Đăng nhập user và tạo token API
            $token = $user->createToken('google-auth')->plainTextToken;

            return response()->json([
                'message' => 'Đăng nhập thành công!',
                'user' => $user,
                'token' => $token
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi khi xác thực Google!', 'error' => $e->getMessage()], 400);
        }
    }
}
