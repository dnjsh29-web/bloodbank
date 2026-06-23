<?php

namespace App\Http\Controllers;

use App\Services\SupabaseAuthService;
use App\Services\SupabaseRest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class PortalController extends Controller
{
    public function __construct(
        private readonly SupabaseRest $supabase,
        private readonly SupabaseAuthService $auth,
    ) {}

    public function landing(): View
    {
        return view('landing', [
            'campaigns' => $this->landingCampaigns(),
        ]);
    }

    public function donorDashboard(Request $request): View
    {
        return view('donor.dashboard', $this->payload($request, 'dashboard'));
    }

    public function donor(Request $request): View
    {
        return $this->donorView($request, 'dashboard');
    }

    public function donorPanel(Request $request, string $section): View|RedirectResponse
    {
        if (! in_array($section, ['dashboard', 'schedule', 'history', 'personal-info', 'account-security'], true)) {
            return redirect()->route('donor.shell');
        }

        return $this->donorView($request, $section);
    }

    public function donorSchedule(Request $request): View
    {
        return view('donor.schedule', $this->payload($request, 'schedule') + [
            'centers' => $this->supabase->safeSelect($this->token($request), 'donation_centers', ['order' => 'name.asc']),
        ]);
    }

    public function saveAppointment(Request $request): RedirectResponse
    {
        $common = $request->validate([
            'service_type' => ['required', 'in:Donor Appointment,Blood Request'],
            'center_name' => ['required', 'string'],
            'donation_center_id' => ['required', 'string'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required', 'string'],
            'hemoglobin' => ['nullable', 'string'],
            'blood_pressure' => ['nullable', 'string'],
        ]);
        $isBloodRequest = $common['service_type'] === 'Blood Request';

        if ($isBloodRequest) {
            $details = $request->validate([
                'patient_name' => ['required', 'string', 'max:160'],
                'hospital_name' => ['required', 'string', 'max:180'],
                'physician_name' => ['nullable', 'string', 'max:160'],
                'request_contact_person' => ['nullable', 'string', 'max:160'],
                'contact_number' => ['required', 'string', 'max:80'],
                'blood_type' => ['required', 'in:O+,O-,A+,A-,B+,B-,AB+,AB-'],
                'component_type' => ['required', 'string', 'max:80'],
                'units_needed' => ['required', 'integer', 'min:1', 'max:100'],
                'required_at' => ['nullable', 'date'],
                'urgency' => ['required', 'in:routine,urgent,emergency'],
                'diagnosis' => ['nullable', 'string', 'max:1200'],
                'medical_justification' => ['nullable', 'string', 'max:1200'],
                'document_description' => ['nullable', 'string', 'max:500'],
                'supporting_document' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            ]);
        } else {
            $details = $request->validate([
                'donor_full_name' => ['required', 'string', 'max:160'],
                'donor_age' => ['required', 'integer', 'min:18', 'max:120'],
                'donor_blood_type' => ['required', 'in:O+,O-,A+,A-,B+,B-,AB+,AB-'],
                'donor_weight_kg' => ['required', 'numeric', 'min:40', 'max:250'],
                'donor_contact_number' => ['required', 'string', 'max:80'],
                'donor_last_donation_date' => ['nullable', 'date'],
                'donor_address' => ['required', 'string', 'max:500'],
                'donor_medical_flags' => ['nullable', 'array'],
                'donor_medical_flags.*' => ['string', 'max:80'],
                'donor_medical_notes' => ['nullable', 'string', 'max:1200'],
                'donor_certified' => ['accepted'],
            ]);
        }

        $profile = $request->session()->get('profile');
        $answers = [
            'fever' => $request->boolean('fever'),
            'surgery' => $request->boolean('surgery'),
            'tattoo' => $request->boolean('tattoo'),
            'antibiotics' => $request->boolean('antibiotics'),
            'infection_test' => $request->boolean('infection_test'),
            'hemoglobin' => $common['hemoglobin'] ?? null,
            'blood_pressure' => $common['blood_pressure'] ?? null,
        ];

        $appointmentId = 'APT-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        $scheduledDateLabel = date('F j, Y', strtotime($common['scheduled_date']));
        $token = $this->token($request);

        $donorForm = $isBloodRequest ? [] : [
            'full_name' => $details['donor_full_name'],
            'age' => (int) $details['donor_age'],
            'blood_type' => $details['donor_blood_type'],
            'weight_kg' => (float) $details['donor_weight_kg'],
            'contact_number' => $details['donor_contact_number'],
            'last_donation_date' => $details['donor_last_donation_date'] ?? null,
            'address' => $details['donor_address'],
            'medical_history' => array_values($details['donor_medical_flags'] ?? []),
            'medical_notes' => $details['donor_medical_notes'] ?? null,
            'certified' => true,
        ];

        $requestForm = $isBloodRequest ? [
            'patient_name' => $details['patient_name'],
            'hospital_name' => $details['hospital_name'],
            'physician_name' => $details['physician_name'] ?? null,
            'contact_person' => $details['request_contact_person'] ?? null,
            'contact_number' => $details['contact_number'],
            'blood_type' => $details['blood_type'],
            'component_type' => $details['component_type'],
            'units_needed' => (int) $details['units_needed'],
            'required_at' => $details['required_at'] ?? null,
            'urgency' => $details['urgency'],
            'diagnosis' => $details['diagnosis'] ?? null,
            'medical_justification' => $details['medical_justification'] ?? null,
            'document_description' => $details['document_description'] ?? null,
        ] : [];

        $formPayload = [
            'service_type' => $common['service_type'],
            'eligibility' => $answers,
            'donor_form' => $donorForm,
            'blood_request_form' => $requestForm,
            'center' => [
                'id' => $common['donation_center_id'],
                'name' => $common['center_name'],
            ],
            'schedule' => [
                'date' => $common['scheduled_date'],
                'time' => $common['scheduled_time'],
            ],
        ];

        try {
            $this->supabase->upsert($token, 'appointments', [[
                'id' => $appointmentId,
                'profile_id' => $profile['id'] ?? null,
                'center_name' => $common['center_name'],
                'donation_center_id' => $common['donation_center_id'],
                'service_type' => $common['service_type'],
                'scheduled_date' => $scheduledDateLabel,
                'scheduled_time' => $common['scheduled_time'],
                'scheduled_at' => $common['scheduled_date'].' '.$common['scheduled_time'],
                'status' => 'Submitted',
                'eligibility_answers' => $answers,
                'donor_full_name' => $donorForm['full_name'] ?? null,
                'donor_age' => $donorForm['age'] ?? null,
                'donor_blood_type' => $donorForm['blood_type'] ?? null,
                'donor_weight_kg' => $donorForm['weight_kg'] ?? null,
                'donor_contact_number' => $donorForm['contact_number'] ?? null,
                'donor_last_donation_date' => $donorForm['last_donation_date'] ?? null,
                'donor_address' => $donorForm['address'] ?? null,
                'donor_medical_history' => [
                    'flags' => $donorForm['medical_history'] ?? [],
                ],
                'donor_medical_notes' => $donorForm['medical_notes'] ?? null,
                'donor_certified' => (bool) ($donorForm['certified'] ?? false),
                'form_payload' => $formPayload,
            ]], 'id');

            $bloodRequestId = null;
            if ($isBloodRequest) {
                $bloodRequestId = 'BR-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
                $this->supabase->upsert($token, 'blood_requests', [[
                    'id' => $bloodRequestId,
                    'appointment_id' => $appointmentId,
                    'profile_id' => $profile['id'] ?? null,
                    'patient_name' => $requestForm['patient_name'],
                    'hospital_name' => $requestForm['hospital_name'],
                    'physician_name' => $requestForm['physician_name'],
                    'contact_person' => $requestForm['contact_person'],
                    'contact_number' => $requestForm['contact_number'],
                    'blood_type' => $requestForm['blood_type'],
                    'component_type' => $requestForm['component_type'],
                    'units_needed' => $requestForm['units_needed'],
                    'required_at' => $requestForm['required_at'],
                    'urgency' => $requestForm['urgency'],
                    'diagnosis' => $requestForm['diagnosis'],
                    'medical_justification' => $requestForm['medical_justification'],
                    'status' => 'Submitted',
                    'submitted_at' => now()->format('M d, Y h:i A'),
                    'form_payload' => $formPayload,
                ]], 'id');

                $file = $request->file('supporting_document');
                if ($file !== null && $file->isValid()) {
                    $bucket = config('services.supabase.storage_bucket', 'blood-request-documents');
                    $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $safeName = now()->format('YmdHis').'-'.Str::slug($name ?: 'document');
                    if ($extension !== '') {
                        $safeName .= '.'.strtolower($extension);
                    }
                    $storagePath = "requests/{$bloodRequestId}/{$safeName}";

                    $this->supabase->uploadObject(
                        $token,
                        $bucket,
                        $storagePath,
                        file_get_contents($file->getRealPath()) ?: '',
                        $file->getMimeType()
                    );

                    $this->supabase->upsert($token, 'documents', [[
                        'id' => 'DOC-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                        'appointment_id' => $appointmentId,
                        'blood_request_id' => $bloodRequestId,
                        'profile_id' => $profile['id'] ?? null,
                        'storage_bucket' => $bucket,
                        'storage_path' => $storagePath,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                        'document_type' => 'Supporting Document',
                        'description' => $details['document_description'] ?? null,
                        'status' => 'Uploaded',
                    ]], 'id');
                }
            }

            return $this->redirectToPortalTab($request, 'schedule')
                ->with('appointment_confirmed', [
                    'center_name' => $common['center_name'],
                    'donation_center_id' => $common['donation_center_id'],
                    'service_type' => $common['service_type'],
                    'scheduled_date' => $scheduledDateLabel,
                    'scheduled_time' => $common['scheduled_time'],
                    'appointment_id' => $appointmentId,
                    'blood_request_id' => $bloodRequestId,
                ]);
        } catch (RuntimeException $error) {
            return back()->withInput()->withErrors(['appointment' => $error->getMessage()]);
        }
    }

    public function donorHistory(Request $request): View
    {
        return view('donor.history', $this->payload($request, 'history'));
    }

    public function donorProfile(Request $request): View
    {
        return view('donor.profile', $this->payload($request, 'personal-info'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:250'],
            'blood_type' => ['nullable', 'string', 'max:5'],
        ]);

        $profile = $request->session()->get('profile');
        $row = ['id' => $profile['id'], ...$validated, 'updated_at' => now()->toISOString()];
        $saved = $this->supabase->upsert($this->token($request), 'profiles', [$row], 'id');
        $request->session()->put('profile', $saved[0] ?? ($profile + $validated));

        return $this->redirectToPortalTab($request, 'personal-info')->with('success', 'Personal information saved.');
    }

    public function updateStaffProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:250'],
        ]);

        $profile = $request->session()->get('profile', []);
        $profileId = (string) ($profile['id'] ?? '');

        if ($profileId === '') {
            return back()->withErrors(['profile' => 'Your account profile is not available. Please sign in again.']);
        }

        $saved = $this->supabase->update($this->token($request), 'profiles', ['id' => "eq.{$profileId}"], [
            ...$validated,
            'updated_at' => now()->toISOString(),
        ]);

        $request->session()->put('profile', $saved[0] ?? array_replace($profile, $validated));

        return $this->redirectToPortalTab($request, 'profile')
            ->with('success', 'Account profile saved.');
    }

    public function donorSecurity(Request $request): View
    {
        return view('donor.security', $this->payload($request, 'account-security'));
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => [$request->boolean('update_password') ? 'required' : 'nullable', 'string'],
            'new_password' => [$request->boolean('update_password') ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'two_factor_enabled' => ['nullable'],
        ]);

        $profile = $request->session()->get('profile');
        $token = $this->token($request);

        try {
            if (! empty($validated['new_password'])) {
                $email = (string) ($profile['email'] ?? $request->session()->get('supabase.user.email', ''));

                if ($email === '') {
                    throw new RuntimeException('This account does not have an email address available for password verification.');
                }

                try {
                    $verifiedSession = $this->auth->signIn($email, $validated['current_password']);
                } catch (RuntimeException) {
                    return back()
                        ->withInput($request->except(['current_password', 'new_password', 'new_password_confirmation']))
                        ->withErrors(['current_password' => 'Current password is incorrect.']);
                }

                $verifiedToken = (string) ($verifiedSession['access_token'] ?? '');
                $this->auth->updatePassword($verifiedToken, $validated['new_password']);

                $freshSession = $this->auth->signIn($email, $validated['new_password']);
                $token = (string) ($freshSession['access_token'] ?? $verifiedToken);
                $request->session()->put([
                    'supabase.access_token' => $token,
                    'supabase.refresh_token' => $freshSession['refresh_token'] ?? null,
                    'supabase.user' => $freshSession['user'] ?? $request->session()->get('supabase.user'),
                ]);

                if ($verifiedToken !== '' && $verifiedToken !== $token) {
                    $this->auth->logout($verifiedToken);
                }
            }

            $saved = $this->supabase->upsert($token, 'profiles', [[
                'id' => $profile['id'],
                'two_factor_enabled' => $request->boolean('two_factor_enabled'),
                'password_updated_label' => empty($validated['new_password'])
                    ? ($profile['password_updated_label'] ?? '6 months ago')
                    : 'just now',
                'updated_at' => now()->toISOString(),
            ]], 'id');
            $request->session()->put('profile', $saved[0] ?? $profile);
        } catch (RuntimeException $error) {
            return back()
                ->withInput($request->except(['current_password', 'new_password', 'new_password_confirmation']))
                ->withErrors(['new_password' => $error->getMessage()]);
        }

        return $this->redirectToPortalTab($request, 'account-security')
            ->with('success', empty($validated['new_password']) ? 'Account security settings updated.' : 'New password saved.');
    }

    public function bloodRequest(Request $request): View
    {
        return view('donor.blood-request', $this->payload($request, 'blood-request'));
    }

    public function saveBloodRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_name' => ['required', 'string'],
            'hospital_name' => ['required', 'string'],
            'physician_name' => ['nullable', 'string'],
            'contact_number' => ['required', 'string'],
            'blood_type' => ['required', 'string'],
            'component_type' => ['required', 'string'],
            'units_needed' => ['required', 'integer', 'min:1'],
            'required_at' => ['nullable', 'date'],
            'urgency' => ['required', 'string'],
            'diagnosis' => ['nullable', 'string'],
        ]);

        $profile = $request->session()->get('profile');

        try {
            $this->supabase->upsert($this->token($request), 'blood_requests', [[
                'id' => 'BR-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'profile_id' => $profile['id'] ?? null,
                ...$validated,
                'status' => 'Submitted',
                'submitted_at' => now()->format('M d, Y h:i A'),
            ]], 'id');

            return back()->with('success', 'Blood request submitted.');
        } catch (RuntimeException $error) {
            return back()->withInput()->withErrors(['request' => $error->getMessage()]);
        }
    }

    public function markNotificationRead(Request $request, string $notification): JsonResponse
    {
        $token = $this->token($request);
        $profileId = (string) ($request->session()->get('profile.id') ?? '');

        if ($profileId === '') {
            return response()->json(['message' => 'Profile session is missing.'], 403);
        }

        $visibleIds = collect($this->visibleNotifications($request, $token))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (! in_array($notification, $visibleIds, true)) {
            return response()->json(['message' => 'Notification could not be found.'], 404);
        }

        try {
            $this->writeNotificationReads($token, $profileId, [$notification]);
            $state = $this->notificationState($request, $token);

            return response()->json([
                'read_ids' => [$notification],
                'unread_count' => $state['unreadNotificationCount'],
            ]);
        } catch (RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        }
    }

    public function markNotificationsRead(Request $request): JsonResponse
    {
        $token = $this->token($request);
        $profileId = (string) ($request->session()->get('profile.id') ?? '');

        if ($profileId === '') {
            return response()->json(['message' => 'Profile session is missing.'], 403);
        }

        $visibleIds = collect($this->visibleNotifications($request, $token))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values();
        $requestedIds = collect($request->input('ids', $visibleIds->all()))
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->unique()
            ->intersect($visibleIds)
            ->values()
            ->all();

        try {
            $this->writeNotificationReads($token, $profileId, $requestedIds);
            $state = $this->notificationState($request, $token);

            return response()->json([
                'read_ids' => $requestedIds,
                'unread_count' => $state['unreadNotificationCount'],
            ]);
        } catch (RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        }
    }

    public function admin(Request $request, ?string $section = null): View|RedirectResponse
    {
        if ($section !== null) {
            return redirect()->to(route('admin.section').'#'.$section);
        }

        return view('admin.portal', $this->payload($request, 'reports') + [
            'section' => 'reports',
            'portal' => 'admin',
        ]);
    }

    public function adminPanel(Request $request, string $section): View|RedirectResponse
    {
        if (! in_array($section, ['reports', 'notifications', 'map', 'donor-records', 'inventory', 'campaigns', 'security', 'profile'], true)) {
            return redirect()->route('admin.section');
        }

        return view('admin.portal', $this->payload($request, $section) + [
            'section' => $section,
            'portal' => 'admin',
        ]);
    }

    public function super(Request $request, ?string $section = null): View|RedirectResponse
    {
        if ($section !== null) {
            return redirect()->to(route('super.section').'#'.$section);
        }

        return view('admin.portal', $this->payload($request, 'overview') + [
            'section' => 'overview',
            'portal' => 'super',
        ]);
    }

    public function superPanel(Request $request, string $section): View|RedirectResponse
    {
        if (! in_array($section, ['overview', 'inventory', 'donor-records', 'security', 'profile'], true)) {
            return redirect()->route('super.section');
        }

        return view('admin.portal', $this->payload($request, $section) + [
            'section' => $section,
            'portal' => 'super',
        ]);
    }

    public function storeDonor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'blood_type' => ['required', 'string'],
            'age' => ['nullable', 'integer', 'min:18'],
            'weight' => ['nullable', 'numeric', 'min:40'],
            'last_donation_label' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
        ]);

        $status = $request->boolean('medical_risk') ? 'Deferred' : 'Eligible';
        $code = 'D-'.random_int(10000, 99999);

        $this->supabase->upsert($this->token($request), 'donors', [[
            'donor_code' => $code,
            'full_name' => $validated['full_name'],
            'contact' => $validated['contact'],
            'email' => $validated['email'] ?? null,
            'blood_type' => $validated['blood_type'],
            'eligibility_status' => $status,
            'last_donation_label' => $validated['last_donation_label'] ?: 'Never',
            'registered_label' => now()->format('M d, Y'),
            'total_units' => 0,
            'registration_details' => Arr::only($validated, ['age', 'weight', 'address']),
            'screening_details' => [
                'blood_pressure' => $request->input('blood_pressure'),
                'hemoglobin' => $request->input('hemoglobin'),
                'temperature' => $request->input('temperature'),
                'medical_risk' => $request->boolean('medical_risk'),
            ],
        ]], 'donor_code');

        return $this->redirectToPortalTab($request, 'donor-records')->with('success', 'New donor saved and added to records.');
    }

    public function storeInventory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'donor_name' => ['required', 'string'],
            'donor_code' => ['nullable', 'string'],
            'contact_number' => ['nullable', 'string'],
            'blood_type' => ['required', 'string'],
            'units' => ['required', 'integer', 'min:1'],
            'component_type' => ['required', 'string'],
            'collection_date' => ['required', 'date'],
            'collection_time' => ['required', 'string'],
            'facility' => ['required', 'string'],
            'staff_name' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'screening_status' => ['required', 'string'],
            'eligibility_status' => ['required', 'string'],
        ]);

        $id = 'INV-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        $this->supabase->upsert($this->token($request), 'inventory_units', [[
            'id' => $id,
            'unit_code' => $id,
            'blood_type' => $validated['blood_type'],
            'component_type' => $validated['component_type'],
            'units' => $validated['units'],
            'collection_date' => $validated['collection_date'],
            'status' => 'Available',
        ]], 'id');
        $this->supabase->upsert($this->token($request), 'donation_entries', [[
            'id' => 'DE-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
            ...$validated,
            'created_by' => $request->session()->get('profile.id'),
        ]], 'id');

        return $this->redirectToPortalTab($request, 'inventory')->with('success', 'Walk-in donation added and inventory updated.');
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'date_range' => ['required', 'string'],
            'locations' => ['required', 'string'],
            'status' => ['required', 'string'],
            'image_url' => ['required', 'url'],
        ]);

        $this->supabase->upsert($this->token($request), 'campaigns', [[
            'id' => 'CMP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
            ...$validated,
        ]], 'id');

        return $this->redirectToPortalTab($request, 'campaigns')->with('success', 'Campaign posted and synced to the landing page.');
    }

    public function reviewAppointment(Request $request, string $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Approved,Completed,Deferred,Ineligible,Cancelled'],
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $token = $this->token($request);
        $profile = $request->session()->get('profile', []);
        $rows = $this->supabase->safeSelect($token, 'appointments', ['id' => "eq.{$appointment}", 'limit' => 1]);
        $current = $rows[0] ?? null;

        if ($current === null) {
            return $this->redirectToPortalTab($request, 'overview')
                ->withErrors(['appointment' => 'Appointment could not be found for review.']);
        }

        $answers = is_array($current['eligibility_answers'] ?? null)
            ? $current['eligibility_answers']
            : [];
        $answers['review'] = [
            'status' => $validated['status'],
            'notes' => $validated['review_notes'] ?? null,
            'reviewed_by' => $profile['id'] ?? null,
            'reviewed_by_name' => $profile['full_name'] ?? $profile['email'] ?? 'Staff reviewer',
            'reviewed_at' => now()->toISOString(),
        ];

        try {
            $this->supabase->update($token, 'appointments', ['id' => "eq.{$appointment}"], [
                'status' => $validated['status'],
                'eligibility_answers' => $answers,
                'updated_at' => now()->toISOString(),
            ]);
        } catch (RuntimeException $error) {
            return $this->redirectToPortalTab($request, 'overview')
                ->withErrors(['appointment' => $error->getMessage()]);
        }

        return $this->redirectToPortalTab($request, 'overview')
            ->with('success', "Appointment marked {$validated['status']}.");
    }

    public function auditAction(Request $request): RedirectResponse
    {
        $action = $request->input('action', 'Audit action completed.');
        $profile = $request->session()->get('profile');
        $this->supabase->upsert($this->token($request), 'audit_logs', [[
            'id' => 'AUD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
            'profile_id' => $profile['id'] ?? null,
            'actor_name' => $profile['full_name'] ?? 'RedCross Admin',
            'actor_role' => $profile['role'] ?? 'admin',
            'action' => $action,
            'ip_address' => $request->ip(),
            'status' => 'Success',
            'metadata' => ['source' => 'laravel-web'],
        ]], 'id');

        return $this->redirectToPortalTab($request, $request->input('_active_tab', 'security'))->with('success', $action);
    }

    private function donorView(Request $request, string $section): View
    {
        return match ($section) {
            'schedule' => view('donor.schedule', $this->payload($request, 'schedule') + [
                'centers' => $this->supabase->safeSelect($this->token($request), 'donation_centers', ['order' => 'name.asc']),
            ]),
            'history' => view('donor.history', $this->payload($request, 'history')),
            'personal-info' => view('donor.profile', $this->payload($request, 'personal-info')),
            'account-security' => view('donor.security', $this->payload($request, 'account-security')),
            default => view('donor.dashboard', $this->payload($request, 'dashboard')),
        };
    }

    private function redirectToPortalTab(Request $request, string $fallbackTab): RedirectResponse
    {
        $profile = $request->session()->get('profile', []);
        $role = (string) ($profile['role'] ?? 'donor');
        $tab = (string) $request->input('_active_tab', $fallbackTab);

        $base = match ($role) {
            'super_admin' => route('super.section'),
            'admin' => route('admin.section'),
            default => route('donor.shell'),
        };

        return redirect()->to($base.'#'.$tab);
    }

    private function payload(Request $request, string $active): array
    {
        $token = $this->token($request);
        $notificationState = $this->notificationState($request, $token);
        $data = [
            'active' => $active,
            'profile' => $request->session()->get('profile'),
            'donors' => [],
            'appointments' => [],
            'bloodRequests' => [],
            'inventory' => [],
            'donationEntries' => [],
            'campaigns' => [],
            'centers' => [],
            'notifications' => $notificationState['notifications'],
            'notificationReads' => $notificationState['notificationReads'],
            'unreadNotificationCount' => $notificationState['unreadNotificationCount'],
            'auditLogs' => [],
        ];

        foreach ($this->tablesForPage($active) as $key => [$table, $query]) {
            $data[$key] = $this->supabase->safeSelect($token, $table, $query);
        }

        if (in_array($active, ['reports', 'donor-records'], true)) {
            $data['donors'] = $this->attachLatestAppointmentForms(
                $data['donors'],
                $data['appointments']
            );
        }

        return $data;
    }

    private function tablesForPage(string $active): array
    {
        return match ($active) {
            'dashboard' => [
                'appointments' => ['appointments', ['order' => 'created_at.desc', 'limit' => 20]],
                'donors' => ['donors', ['order' => 'created_at.desc', 'limit' => 10]],
            ],
            'history' => [
                'appointments' => ['appointments', ['order' => 'created_at.desc', 'limit' => 20]],
            ],
            'reports' => [
                'donors' => ['donors', ['order' => 'created_at.desc', 'limit' => 25]],
                'appointments' => ['appointments', ['order' => 'created_at.desc', 'limit' => 100]],
            ],
            'donor-records' => [
                'donors' => ['donors', ['order' => 'created_at.desc', 'limit' => 50]],
                'appointments' => ['appointments', ['order' => 'created_at.desc', 'limit' => 100]],
            ],
            'inventory' => [
                'inventory' => ['inventory_units', ['order' => 'created_at.desc', 'limit' => 100]],
                'donationEntries' => ['donation_entries', ['order' => 'created_at.desc', 'limit' => 20]],
            ],
            'campaigns' => [
                'campaigns' => ['campaigns', ['order' => 'created_at.desc', 'limit' => 20]],
            ],
            'map' => [
                'centers' => ['donation_centers', ['order' => 'name.asc', 'limit' => 100]],
            ],
            'security' => [
                'auditLogs' => ['audit_logs', ['order' => 'created_at.desc', 'limit' => 25]],
            ],
            'overview' => [
                'inventory' => ['inventory_units', ['order' => 'created_at.desc', 'limit' => 100]],
                'auditLogs' => ['audit_logs', ['order' => 'created_at.desc', 'limit' => 10]],
                'appointments' => ['appointments', ['order' => 'created_at.desc', 'limit' => 25]],
            ],
            default => [],
        };
    }

    private function attachLatestAppointmentForms(array $donors, array $appointments): array
    {
        $appointmentsByName = collect($appointments)
            ->filter(fn (array $appointment): bool => filled($appointment['donor_full_name'] ?? null))
            ->groupBy(fn (array $appointment): string => Str::lower(trim((string) $appointment['donor_full_name'])));

        return collect($donors)
            ->map(function (array $donor) use ($appointmentsByName): array {
                $name = Str::lower(trim((string) ($donor['full_name'] ?? $donor['name'] ?? '')));
                $appointment = $name === '' ? null : $appointmentsByName->get($name)?->first();

                if (! is_array($appointment)) {
                    return $donor;
                }

                $payload = is_array($appointment['form_payload'] ?? null)
                    ? $appointment['form_payload']
                    : [];
                $donorForm = is_array($payload['donor_form'] ?? null)
                    ? $payload['donor_form']
                    : [];
                $screening = is_array($appointment['eligibility_answers'] ?? null)
                    ? $appointment['eligibility_answers']
                    : [];

                return [
                    ...$donor,
                    'form_payload' => $payload,
                    'registration_details' => [
                        ...(is_array($donor['registration_details'] ?? null) ? $donor['registration_details'] : []),
                        'age' => $donorForm['age'] ?? $appointment['donor_age'] ?? $donor['age'] ?? null,
                        'weight' => $donorForm['weight_kg'] ?? $appointment['donor_weight_kg'] ?? $donor['weight'] ?? null,
                        'address' => $donorForm['address'] ?? $appointment['donor_address'] ?? $donor['address'] ?? null,
                        'last_donation_date' => $donorForm['last_donation_date'] ?? $appointment['donor_last_donation_date'] ?? null,
                    ],
                    'screening_details' => [
                        ...(is_array($donor['screening_details'] ?? null) ? $donor['screening_details'] : []),
                        ...$screening,
                    ],
                ];
            })
            ->values()
            ->all();
    }

    private function notificationState(Request $request, string $token): array
    {
        $profileId = (string) ($request->session()->get('profile.id') ?? '');
        $notifications = $this->visibleNotifications($request, $token);
        $reads = $profileId === ''
            ? []
            : $this->supabase->safeSelect($token, 'notification_reads', [
                'profile_id' => "eq.{$profileId}",
                'select' => 'notification_id,read_at',
            ]);
        $readById = collect($reads)
            ->filter(fn ($read) => isset($read['notification_id']))
            ->pluck('read_at', 'notification_id');
        $decorated = collect($notifications)
            ->map(function (array $notification) use ($readById): array {
                $id = (string) ($notification['id'] ?? '');
                $type = (string) ($notification['type'] ?? $notification['tag'] ?? 'alert');
                $isRead = $id !== '' && $readById->has($id);

                return [
                    ...$notification,
                    'tag' => $notification['tag'] ?? $this->notificationTag($type),
                    'filter_type' => $this->notificationFilter($type),
                    'is_read' => $isRead,
                    'read_at' => $readById->get($id),
                    'display_time' => $this->notificationDisplayTime($notification, $isRead),
                ];
            })
            ->values()
            ->all();

        return [
            'notifications' => $decorated,
            'notificationReads' => $reads,
            'unreadNotificationCount' => collect($decorated)->where('is_read', false)->count(),
        ];
    }

    private function visibleNotifications(Request $request, string $token): array
    {
        $profile = $request->session()->get('profile', []);
        $profileId = (string) ($profile['id'] ?? '');
        $role = (string) ($profile['role'] ?? 'donor');
        $staffRoles = ['admin', 'super_admin'];

        return collect($this->supabase->safeSelect($token, 'notifications', [
            'order' => 'created_at.desc',
            'limit' => 50,
        ]))
            ->filter(function (array $notification) use ($profileId, $role, $staffRoles): bool {
                $targetRole = (string) ($notification['target_role'] ?? '');
                $notificationProfile = (string) ($notification['profile_id'] ?? '');

                if ($notificationProfile !== '' && $notificationProfile === $profileId) {
                    return true;
                }

                if ($targetRole === '') {
                    return true;
                }

                if ($role === 'donor') {
                    return $targetRole === 'donor';
                }

                if (in_array($role, $staffRoles, true)) {
                    return in_array($targetRole, $staffRoles, true);
                }

                return $targetRole === $role;
            })
            ->values()
            ->all();
    }

    private function writeNotificationReads(string $token, string $profileId, array $notificationIds): array
    {
        $now = now()->toISOString();
        $rows = collect($notificationIds)
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->unique()
            ->map(fn (string $id) => [
                'notification_id' => $id,
                'profile_id' => $profileId,
                'read_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        return $rows === []
            ? []
            : $this->supabase->upsert($token, 'notification_reads', $rows, 'notification_id,profile_id');
    }

    private function notificationTag(string $type): string
    {
        return ucwords(str_replace('_', ' ', $type));
    }

    private function notificationFilter(string $type): string
    {
        return str_contains(strtolower($type), 'matching') ? 'matching' : 'general';
    }

    private function notificationDisplayTime(array $notification, bool $isRead): string
    {
        $state = $isRead ? 'Read' : 'Unread';
        $label = (string) ($notification['time_label'] ?? '');

        if ($label !== '') {
            return preg_replace('/^(Unread|Read)/i', $state, $label) ?: "{$state} - now";
        }

        if (! empty($notification['created_at'])) {
            return "{$state} - ".date('M d, Y h:i A', strtotime((string) $notification['created_at']));
        }

        return "{$state} - now";
    }

    private function token(Request $request): string
    {
        return (string) $request->session()->get('supabase.access_token');
    }

    private function landingCampaigns(): array
    {
        try {
            $campaigns = $this->supabase->publicSelect('campaigns', ['order' => 'created_at.desc']);
            if ($campaigns !== []) {
                return $campaigns;
            }
        } catch (RuntimeException) {
            //
        }

        return [
            [
                'title' => 'Spring Drive 2024',
                'description' => 'Annual spring blood collection drive across regional community centers targeting type-O donors.',
                'date_range' => 'March 15 - April 10',
                'locations' => '12 Locations',
                'status' => 'Upcoming',
                'image_url' => 'https://images.unsplash.com/photo-1615461066841-6116e61058f4?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'title' => 'Youth Donor Mission',
                'description' => 'Campus-based blood donation campaign for first-time donors and student volunteers.',
                'date_range' => 'April 12 - April 26',
                'locations' => 'Laguna Campuses',
                'status' => 'Active',
                'image_url' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'title' => 'Santa Rosa Community Care',
                'description' => 'Local RedCross donation drive supporting emergency supply readiness in Santa Rosa.',
                'date_range' => 'May 3 - May 18',
                'locations' => 'Santa Rosa Laguna',
                'status' => 'Upcoming',
                'image_url' => 'https://images.unsplash.com/photo-1582719471384-894fbb16e074?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'title' => 'Corporate Heroes Week',
                'description' => 'Partner company donation week with mobile collection teams and clinical support.',
                'date_range' => 'June 1 - June 7',
                'locations' => 'Business District',
                'status' => 'Upcoming',
                'image_url' => 'https://images.unsplash.com/photo-1551190822-a9333d879b1f?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'title' => 'Emergency Stock Boost',
                'description' => 'Priority campaign for rare blood types and urgent hospital replenishment.',
                'date_range' => 'June 15 - June 30',
                'locations' => 'Regional Centers',
                'status' => 'Priority',
                'image_url' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=900&q=80',
            ],
        ];
    }
}
