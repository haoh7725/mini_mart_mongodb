<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\DocumentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        if (! $this->configured()) {
            return redirect()->route('login')->withErrors(['email' => 'Đăng nhập Google chưa được cấu hình. Vui lòng nhập GOOGLE_CLIENT_ID và GOOGLE_CLIENT_SECRET trong file .env.']);
        }

        // Google chỉ quay về đúng địa chỉ đã đăng ký (và không nhận tên miền .test của Laragon).
        // Mở web bằng địa chỉ khác thì chuyển sang địa chỉ đó trước, để phiên đăng nhập và callback cùng một nơi.
        $callbackOrigin = $this->origin($this->callbackUrl());
        if ($callbackOrigin !== $request->getSchemeAndHttpHost()) {
            return redirect()->away($callbackOrigin.route('auth.google', absolute: false));
        }

        return $this->driver()->redirect();
    }

    public function callback(Request $request)
    {
        if (! $this->configured() || $request->input('error') === 'access_denied') {
            return redirect()->route('login')->withErrors(['email' => 'Bạn đã hủy đăng nhập bằng Google.']);
        }
        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors(['email' => 'Google từ chối đăng nhập ('.$request->string('error').'). Vui lòng thử lại.']);
        }

        try {
            $google = $this->driver()->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->withErrors(['email' => 'Phiên đăng nhập Google đã hết hạn. Vui lòng bấm "Tiếp tục với Google" lại.']);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors(['email' => 'Không thể đăng nhập bằng Google. Vui lòng thử lại.']);
        }

        $email = Str::lower((string) $google->getEmail());
        // Chỉ tin email đã được Google xác minh, vì tài khoản sẽ được gắn theo email.
        if ($email === '' || ! ($google->user['email_verified'] ?? $google->user['verified_email'] ?? false)) {
            return redirect()->route('login')->withErrors(['email' => 'Email Google của bạn chưa được xác minh.']);
        }

        $user = DocumentTransaction::run(function () use ($google, $email) {
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

    private function callbackUrl(): string
    {
        return config('services.google.redirect') ?: route('auth.google.callback');
    }

    private function origin(string $url): string
    {
        $parts = parse_url($url);

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    private function driver()
    {
        return Socialite::driver('google')
            ->redirectUrl($this->callbackUrl())
            ->with(['prompt' => 'select_account']);
    }
}
