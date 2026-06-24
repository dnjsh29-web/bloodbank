<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConfirmationEmailDeliveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.supabase.url' => 'https://supabase.test',
            'services.supabase.anon_key' => 'test-publishable-key',
            'services.supabase.confirmation_cooldown_seconds' => 60,
            'services.supabase.email_confirmation_required' => true,
        ]);
    }

    public function test_registration_limits_duplicate_confirmation_requests(): void
    {
        Http::fake([
            'https://supabase.test/auth/v1/signup*' => Http::response([
                'user' => ['id' => 'test-user-id'],
            ]),
        ]);

        $this->post(route('register.store'), $this->registrationPayload())
            ->assertRedirect(route('login'))
            ->assertSessionHas('unconfirmed_email', 'donor@example.test');

        $this->post(route('register.store'), $this->registrationPayload())
            ->assertRedirect()
            ->assertSessionHas('errors');

        Http::assertSentCount(1);
    }

    public function test_registration_signs_in_immediately_when_supabase_email_confirmation_is_disabled(): void
    {
        Http::fake([
            'https://supabase.test/auth/v1/signup*' => Http::response([
                'access_token' => 'signup-access-token',
                'refresh_token' => 'signup-refresh-token',
                'user' => [
                    'id' => 'test-user-id',
                    'email' => 'donor@example.test',
                ],
            ]),
            'https://supabase.test/rest/v1/profiles*' => Http::response([
                [
                    'id' => 'test-user-id',
                    'role' => 'donor',
                    'full_name' => 'Test Donor',
                    'email' => 'donor@example.test',
                ],
            ]),
        ]);

        $this->post(route('register.store'), $this->registrationPayload())
            ->assertRedirect(route('donor.shell').'#dashboard')
            ->assertSessionHas('profile.role', 'donor')
            ->assertSessionHas('supabase.access_token', 'signup-access-token');
    }

    public function test_resend_limits_duplicate_confirmation_requests(): void
    {
        Http::fake([
            'https://supabase.test/auth/v1/resend*' => Http::response([], 200),
        ]);

        $this->post(route('confirmation.resend'), ['email' => 'donor@example.test'])
            ->assertRedirect()
            ->assertSessionHas('unconfirmed_email', 'donor@example.test');

        $this->post(route('confirmation.resend'), ['email' => 'donor@example.test'])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        Http::assertSentCount(1);
    }

    public function test_registration_handles_supabase_email_rate_limits_without_exposing_provider_message(): void
    {
        Http::fake([
            'https://supabase.test/auth/v1/signup*' => Http::response([
                'message' => 'Email rate limit exceeded',
            ], 429),
        ]);

        $this->post(route('register.store'), $this->registrationPayload())
            ->assertRedirect(route('login'))
            ->assertSessionHas('unconfirmed_email', 'donor@example.test')
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'temporarily limited',
            session('errors')->first('email'),
        );
    }

    public function test_resend_handles_supabase_email_rate_limits_with_a_friendly_message(): void
    {
        Http::fake([
            'https://supabase.test/auth/v1/resend*' => Http::response([
                'message' => 'Email rate limit exceeded',
            ], 429),
        ]);

        $this->post(route('confirmation.resend'), ['email' => 'donor@example.test'])
            ->assertRedirect()
            ->assertSessionHas('unconfirmed_email', 'donor@example.test')
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'temporarily limited',
            session('errors')->first('email'),
        );
    }

    public function test_test_mode_does_not_send_confirmation_email_requests(): void
    {
        config(['services.supabase.email_confirmation_required' => false]);
        Http::fake();

        $this->post(route('confirmation.resend'), ['email' => 'donor@example.test'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        Http::assertNothingSent();
    }

    public function test_test_mode_reports_when_supabase_still_requires_email_confirmation(): void
    {
        config(['services.supabase.email_confirmation_required' => false]);
        Http::fake([
            'https://supabase.test/auth/v1/signup*' => Http::response([
                'user' => ['id' => 'test-user-id'],
            ]),
        ]);

        $this->post(route('register.store'), $this->registrationPayload())
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Supabase',
            session('errors')->first('email'),
        );
    }

    /** @return array<string, mixed> */
    private function registrationPayload(): array
    {
        return [
            'full_name' => 'Test Donor',
            'email' => 'donor@example.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'certify' => '1',
        ];
    }
}
