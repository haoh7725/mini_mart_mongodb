<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        if (! $this->configured()) {
            return redirect()->route('login')->withErrors(['email' => 'Đăng nhập Google chưa được cấu hình. Vui lòng nhập GOOGLE_CLIENT_ID và GOOGLE_CLIENT_SECRET trong file .env.']);
        }

        return $this->driver()->redirect();
    }

    public function callback(Request $request)
    {
        if (! $this->configured() || $request->filled('error')) {
            return redirect()->route('login')->withErrors(['email' => 'Bạn đã hủy đăng nhập bằng Google.']);
        }

        try {
            $google = $this->driver()->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors(['email' => 'Không thể đăng nhập bằng Google. Vui lòng thử lại.']);
        }

        $email = Str::lower((string) $google->getEmail());
        // Chỉ tin email đã được Google xác minh, vì tài khoản sẽ được gắn theo email.
        if ($email === '' || ! ($google->user['email_verified'] ?? $google->user['verified_email'] ?? false)) {
            return redirect()->route('login')->withErrors(['email' => 'Email Google của bạn chưa được xác minh.']);
        }

        $user = DB::transaction(function () use ($google, $email) {
            $user = User::query()->where('google_id', $google->getId())->first()
                ?? User::query()->where('email', $email)->first();

            if (! $user) {
                return User::query()->forceCreate([
                    'name' => $google->getName() ?: Str::before($email, '@'),
                    'email' => $email,
                    'google_id' => $google->getId(),
                    'avatar' => $google->getAvatar(),
                    'role_id' => Role::query()->where('slug', 'customer')->value('id'),
                    'is_active' => true,
                    'password' => Str::random(40),
                    'email_verified_at' => now(),
                ]);
            }

            // Tài khoản cũ chưa xác minh email có thể do người khác đăng ký trước bằng email này:
            // đổi mật khẩu để người đó không còn đăng nhập được.
            if (! $user->email_verified_at) {
                $user->forceFill(['password' => Str::random(40), 'remember_token' => Str::random(60)]);
            }
            $user->forceFill([
                'google_id' => $google->getId(),
                'avatar' => $user->avatar ?: $google->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return $user;
        });

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors(['email' => 'Tài khoản đã bị khóa.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('home'))->with('success', 'Đăng nhập bằng Google thành công.');
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    private function driver()
    {
        return Socialite::driver('google')
            ->redirectUrl(config('services.google.redirect') ?: route('auth.google.callback'))
            ->with(['prompt' => 'select_account']);
    }
}
