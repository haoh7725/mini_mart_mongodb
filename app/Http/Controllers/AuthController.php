<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }
    public function showRegister() { return view('auth.register'); }
    public function showForgotPassword() { return view('auth.forgot-password'); }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email hoặc mật khẩu không chính xác.'])->onlyInput('email');
        }
        if (! $request->user()->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Tài khoản đã bị khóa.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('home'))->with('success', 'Đăng nhập thành công.');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        $data['role_id'] = Role::query()->where('slug', 'customer')->value('id');
        $user = User::query()->create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Tạo tài khoản thành công.');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));
        return back()->with('success', 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu đã được ghi vào mail log.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->string('email')]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'], 'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Mật khẩu đã được cập nhật.')
            : back()->withErrors(['email' => __($status)]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home')->with('success', 'Đã đăng xuất.');
    }
}
