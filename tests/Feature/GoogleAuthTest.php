<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config()->set('services.google', ['client_id' => 'test-id', 'client_secret' => 'test-secret', 'redirect' => null]);
    }

    private function fakeGoogle(string $email, bool $verified = true, string $id = 'google-123'): void
    {
        $googleUser = (new SocialiteUser)->setRaw(['email_verified' => $verified])->map([
            'id' => $id, 'name' => 'Nguyễn Google', 'email' => $email, 'avatar' => 'https://example.com/a.png',
        ]);
        $provider = Mockery::mock();
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_login_page_shows_google_button(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Tiếp tục với Google')->assertSee(route('auth.google'));
        $this->get(route('register'))->assertOk()->assertSee('Đăng ký bằng Google');
    }

    public function test_google_login_requires_configuration(): void
    {
        config()->set('services.google.client_id', null);

        $this->get(route('auth.google'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
    }

    public function test_new_google_user_is_registered_as_verified_customer(): void
    {
        $this->fakeGoogle('New.Buyer@gmail.com');

        $this->get(route('auth.google.callback', ['code' => 'abc']))->assertRedirect(route('home'));

        $user = User::where('email', 'new.buyer@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('customer'));
    }

    public function test_existing_unverified_account_is_linked_and_password_rotated(): void
    {
        $existing = User::create(['name' => 'Ai đó', 'email' => 'owner@gmail.com', 'password' => 'Password@123']);
        $this->fakeGoogle('owner@gmail.com');

        $this->get(route('auth.google.callback', ['code' => 'abc']))->assertRedirect(route('home'));

        $existing->refresh();
        $this->assertAuthenticatedAs($existing);
        $this->assertSame('google-123', $existing->google_id);
        $this->assertFalse(Hash::check('Password@123', $existing->password));
        $this->assertSame(1, User::where('email', 'owner@gmail.com')->count());
    }

    public function test_existing_verified_account_keeps_password(): void
    {
        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $this->fakeGoogle($customer->email);

        $this->get(route('auth.google.callback', ['code' => 'abc']))->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($customer);
        $this->assertTrue(Hash::check('Password@123', $customer->fresh()->password));
    }

    public function test_unverified_google_email_and_locked_account_are_rejected(): void
    {
        $this->fakeGoogle('someone@gmail.com', verified: false);
        $this->get(route('auth.google.callback', ['code' => 'abc']))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'someone@gmail.com']);

        User::where('email', 'customer@minimart.test')->update(['is_active' => false]);
        $this->fakeGoogle('customer@minimart.test');
        $this->get(route('auth.google.callback', ['code' => 'abc']))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_cancelled_google_consent_returns_to_login(): void
    {
        $this->get(route('auth.google.callback', ['error' => 'access_denied']))->assertRedirect(route('login'))->assertSessionHasErrors('email');
    }

    public function test_login_moves_to_the_registered_callback_host_before_going_to_google(): void
    {
        config()->set('services.google.redirect', 'http://localhost:8000/dang-nhap/google/callback');

        $this->get('http://mini-mart-laravel.test/dang-nhap/google')->assertRedirect('http://localhost:8000/dang-nhap/google');

        $provider = Mockery::mock();
        $provider->shouldReceive('redirectUrl')->with('http://localhost:8000/dang-nhap/google/callback')->andReturnSelf();
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
        $this->get('http://localhost:8000/dang-nhap/google')->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_expired_state_and_google_errors_show_a_clear_message(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('user')->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback', ['code' => 'abc', 'state' => 'old']))->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Phiên đăng nhập Google đã hết hạn. Vui lòng bấm "Tiếp tục với Google" lại.']);
        $this->get(route('auth.google.callback', ['error' => 'invalid_request']))
            ->assertSessionHasErrors(['email' => 'Google từ chối đăng nhập (invalid_request). Vui lòng thử lại.']);
    }
}
