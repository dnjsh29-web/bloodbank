<?php

namespace App\Http\Controllers;

use App\Exceptions\SupabaseEmailRateLimitException;
use App\Services\SupabaseAuthService;
use App\Services\SupabaseRest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        private readonly SupabaseAuthService $auth,
        private readonly SupabaseRest $supabase,
    ) {}

    public function show(Request $request): View
    {
        return view('auth.login', [
            'portal' => $request->query('portal', 'donor'),
            'emailConfirmationRequired' => $this->emailConfirmationRequired(),
        ]);
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $portal = $request->input('portal', 'donor');
        $rules = $portal === 'admin'
            ? ['staff_id' => ['required', 'string'], 'password' => ['required', 'string']]
            : ['email' => ['required', 'email'], 'password' => ['required', 'string']];

        $validated = $request->validate($rules);
        $email = $portal === 'admin' ? $this->staffEmail($validated['staff_id']) : $validated['email'];

        try {
            $session = $this->auth->signIn($email, $validated['password']);
            $token = (string) ($session['access_token'] ?? '');
            $user = $session['user'] ?? [];
            $profile = $this->supabase->profileForUser($token, (string) ($user['id'] ?? ''));

            if (! $profile) {
                throw new RuntimeException('No RedCross profile was found for this account.');
            }

            $request->session()->regenerate();
            $request->session()->put([
                'supabase.access_token' => $token,
                'supabase.refresh_token' => $session['refresh_token'] ?? null,
                'supabase.user' => $user,
                'profile' => $profile,
            ]);

            return redirect($this->homeForRole((string) $profile['role']))
                ->with('success', ucfirst(str_replace('_', ' ', (string) $profile['role'])).' credentials verified.');
        } catch (RuntimeException $error) {
            if ($this->isUnconfirmedEmailError($error->getMessage())) {
                if (! $this->emailConfirmationRequired()) {
                    return back()
                        ->withInput($request->except('password'))
                        ->withErrors(['email' => 'This account was created while email confirmation was enabled. Confirm it once, or create a new test account after disabling Confirm Email in Supabase.']);
                }

                return back()
                    ->withInput($request->except('password'))
                    ->with('unconfirmed_email', $email)
                    ->withErrors(['email' => 'Email not confirmed. Check your inbox or resend the confirmation email.']);
            }

            return back()
                ->withInput($request->except('password'))
                ->withErrors(['email' => $error->getMessage()]);
        }
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'age' => ['nullable', 'integer', 'min:18', 'max:120'],
            'blood_type' => ['nullable', 'in:O+,O-,A+,A-,B+,B-,AB+,AB-'],
            'weight' => ['nullable', 'numeric', 'min:40', 'max:250'],
            'phone' => ['nullable', 'string', 'max:40'],
            'last_donation_at' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:250'],
            'medical_flags' => ['nullable', 'array'],
            'medical_flags.*' => ['string', 'max:80'],
            'medical_notes' => ['nullable', 'string', 'max:600'],
            'certify' => ['accepted'],
        ]);
        $email = Str::lower(trim($validated['email']));

        try {
            if ($this->emailConfirmationRequired() && $this->confirmationEmailCoolingDown($email)) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation']))
                    ->withErrors(['email' => $this->confirmationCooldownMessage()]);
            }

            $session = $this->auth->signUp($email, $validated['password'], [
                'full_name' => $validated['full_name'],
            ], route('login'));

            $token = (string) ($session['access_token'] ?? '');
            $user = $session['user'] ?? [];
            $profile = null;

            if ($token && isset($user['id'])) {
                $profiles = $this->supabase->upsert($token, 'profiles', [[
                    'id' => $user['id'],
                    'role' => 'donor',
                    'full_name' => $validated['full_name'],
                    'email' => $email,
                    'phone' => $validated['phone'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'blood_type' => $validated['blood_type'] ?? null,
                ]], 'id');

                $profile = $profiles[0] ?? [
                    'id' => $user['id'],
                    'role' => 'donor',
                    'full_name' => $validated['full_name'],
                    'email' => $email,
                    'phone' => $validated['phone'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'blood_type' => $validated['blood_type'] ?? null,
                ];
            }

            if ($token !== '' && $profile !== null) {
                $request->session()->regenerate();
                $request->session()->put([
                    'supabase.access_token' => $token,
                    'supabase.refresh_token' => $session['refresh_token'] ?? null,
                    'supabase.user' => $user,
                    'profile' => $profile,
                ]);

                return redirect($this->homeForRole('donor'))
                    ->with('success', 'Account created. You are now signed in.');
            }

            if (! $this->emailConfirmationRequired()) {
                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'Supabase did not start a session for this registration. Confirm Email must be disabled in Supabase Authentication > Providers > Email, or sign in if this email already has an account.']);
            }

            $this->startConfirmationEmailCooldown($email);

            return redirect()->route('login')
                ->with('unconfirmed_email', $email)
                ->with('success', 'Registration submitted. Check your email to confirm your account before signing in.');
        } catch (SupabaseEmailRateLimitException) {
            $this->startConfirmationEmailCooldown($email);

            return redirect()
                ->route('login')
                ->with('unconfirmed_email', $email)
                ->withErrors(['email' => $this->emailRateLimitMessage()]);
        } catch (RuntimeException $error) {
            return back()->withInput()->withErrors(['email' => $error->getMessage()]);
        }
    }

    public function resendConfirmation(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);
        $email = Str::lower(trim($validated['email']));

        if (! $this->emailConfirmationRequired()) {
            return redirect()
                ->route('login')
                ->with('success', 'Email confirmation is disabled for testing. Register or sign in directly.');
        }

        try {
            if ($this->confirmationEmailCoolingDown($email)) {
                return back()
                    ->withInput(['email' => $email])
                    ->with('unconfirmed_email', $email)
                    ->withErrors(['email' => $this->confirmationCooldownMessage()]);
            }

            $this->auth->resendSignupConfirmation($email, route('login'));
            $this->startConfirmationEmailCooldown($email);

            return back()
                ->withInput(['email' => $email])
                ->with('unconfirmed_email', $email)
                ->with('success', 'Confirmation email resent. Please check your inbox.');
        } catch (SupabaseEmailRateLimitException) {
            $this->startConfirmationEmailCooldown($email);

            return back()
                ->withInput(['email' => $email])
                ->with('unconfirmed_email', $email)
                ->withErrors(['email' => $this->emailRateLimitMessage()]);
        } catch (RuntimeException $error) {
            return back()
                ->withInput(['email' => $email])
                ->with('unconfirmed_email', $email)
                ->withErrors(['email' => $error->getMessage()]);
        }
    }

    public function forgot(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        try {
            $this->auth->recover($validated['email'], route('password.reset'));

            return back()
                ->withInput($request->only('email'))
                ->with('success', 'Password reset instructions were sent if the account exists.');
        } catch (RuntimeException $error) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $error->getMessage()]);
        }
    }

    public function showPasswordReset(): View
    {
        return view('auth.reset-password');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recovery_token' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $token = $validated['recovery_token'];
            $this->auth->updatePassword($token, $validated['new_password']);

            try {
                $user = $this->supabase->user($token);
                $userId = (string) ($user['id'] ?? '');

                if ($userId !== '') {
                    $this->supabase->update($token, 'profiles', ['id' => "eq.{$userId}"], [
                        'password_updated_label' => 'just now',
                        'updated_at' => now()->toISOString(),
                    ]);
                }
            } catch (RuntimeException) {
                //
            }

            $this->auth->logout($token);

            return redirect()
                ->route('login')
                ->with('success', 'Password updated. Please sign in with your new password.');
        } catch (RuntimeException $error) {
            return back()->withErrors(['new_password' => $error->getMessage()]);
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = (string) $request->session()->get('supabase.access_token');
        if ($token !== '') {
            $this->auth->logout($token);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }

    private function staffEmail(string $staffId): string
    {
        $value = strtolower(trim($staffId));

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $value;
        }

        return str_contains($value, 'super') || str_contains($value, 'sa-')
            ? 'superadmin@redcross.test'
            : 'admin@redcross.test';
    }

    private function homeForRole(string $role): string
    {
        return match ($role) {
            'admin' => route('admin.section').'#reports',
            'super_admin' => route('super.section').'#overview',
            default => route('donor.shell').'#dashboard',
        };
    }

    private function isUnconfirmedEmailError(string $message): bool
    {
        return str_contains(strtolower($message), 'email not confirmed');
    }

    private function confirmationEmailCoolingDown(string $email): bool
    {
        return Cache::has($this->confirmationCooldownKey($email));
    }

    private function startConfirmationEmailCooldown(string $email): void
    {
        Cache::put(
            $this->confirmationCooldownKey($email),
            true,
            now()->addSeconds($this->confirmationCooldownSeconds()),
        );
    }

    private function confirmationCooldownKey(string $email): string
    {
        return 'supabase:confirmation-email:'.hash('sha256', Str::lower(trim($email)));
    }

    private function confirmationCooldownSeconds(): int
    {
        return max(15, (int) config('services.supabase.confirmation_cooldown_seconds', 60));
    }

    private function emailConfirmationRequired(): bool
    {
        return (bool) config('services.supabase.email_confirmation_required', true);
    }

    private function confirmationCooldownMessage(): string
    {
        return 'A confirmation email was recently requested. Please wait a minute before trying again.';
    }

    private function emailRateLimitMessage(): string
    {
        return 'Confirmation email delivery is temporarily limited. Please wait a few minutes before trying again.';
    }
}
