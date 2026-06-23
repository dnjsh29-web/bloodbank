<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseRest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RedCrossController extends Controller
{
    public function __construct(private readonly SupabaseRest $supabase)
    {
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => $request->attributes->get('supabase_user'),
                'profile' => $request->attributes->get('profile'),
                'role' => $this->role($request),
            ],
        ]);
    }

    public function appData(Request $request): JsonResponse
    {
        $token = $this->token($request);
        $user = $request->attributes->get('supabase_user');

        return response()->json([
            'data' => [
                'user_id' => $user['id'] ?? null,
                'role' => $this->role($request),
                'profile' => $request->attributes->get('profile'),
                'donors' => $this->list($token, 'donors'),
                'appointments' => $this->list($token, 'appointments'),
                'blood_requests' => $this->list($token, 'blood_requests'),
                'inventory_units' => $this->list($token, 'inventory_units'),
                'donation_entries' => $this->list($token, 'donation_entries'),
                'campaigns' => $this->list($token, 'campaigns'),
                'notifications' => $this->list($token, 'notifications'),
                'audit_logs' => $this->list($token, 'audit_logs'),
            ],
        ]);
    }

    public function saveAppData(Request $request): JsonResponse
    {
        $token = $this->token($request);
        $role = $this->role($request);
        $payload = $request->validate([
            'profile' => ['nullable', 'array'],
            'appointments' => ['nullable', 'array'],
            'blood_requests' => ['nullable', 'array'],
            'inventory_units' => ['nullable', 'array'],
            'donors' => ['nullable', 'array'],
            'campaigns' => ['nullable', 'array'],
            'notifications' => ['nullable', 'array'],
            'donation_entries' => ['nullable', 'array'],
        ]);

        $saved = [];
        $saved['profiles'] = $this->upsertIfPresent($token, 'profiles', $payload['profile'] ?? null, 'id');
        $saved['appointments'] = $this->upsertIfPresent($token, 'appointments', $payload['appointments'] ?? [], 'id');
        $saved['blood_requests'] = $this->upsertIfPresent($token, 'blood_requests', $payload['blood_requests'] ?? [], 'id');

        if (in_array($role, ['admin', 'super_admin'], true)) {
            $saved['inventory_units'] = $this->upsertIfPresent($token, 'inventory_units', $payload['inventory_units'] ?? [], 'id');
            $saved['donors'] = $this->upsertIfPresent($token, 'donors', $payload['donors'] ?? [], 'donor_code');
            $saved['campaigns'] = $this->upsertIfPresent($token, 'campaigns', $payload['campaigns'] ?? [], 'id');
            $saved['notifications'] = $this->upsertIfPresent($token, 'notifications', $payload['notifications'] ?? [], 'id');
            $saved['donation_entries'] = $this->upsertIfPresent($token, 'donation_entries', $payload['donation_entries'] ?? [], 'id');
        }

        return response()->json(['data' => $saved]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $data = $this->appData($request)->getData(true)['data'];

        return response()->json([
            'data' => [
                'total_registered' => count($data['donors'] ?? []),
                'eligible_donors' => collect($data['donors'] ?? [])->where('eligibility_status', 'Eligible')->count(),
                'inventory_units' => collect($data['inventory_units'] ?? [])->sum('units'),
                'pending_requests' => count($data['blood_requests'] ?? []),
                'critical_lows' => ['O-', 'AB-'],
            ],
        ]);
    }

    public function donors(Request $request): JsonResponse
    {
        return $this->tableResponse($request, 'donors');
    }

    public function storeDonor(Request $request): JsonResponse
    {
        $this->requireStaff($request);

        return $this->storeRows($request, 'donors', 'donor_code');
    }

    public function centers(Request $request): JsonResponse
    {
        return $this->tableResponse($request, 'donation_centers');
    }

    public function appointments(Request $request): JsonResponse
    {
        return $this->tableResponse($request, 'appointments');
    }

    public function storeAppointment(Request $request): JsonResponse
    {
        return $this->storeRows($request, 'appointments', 'id');
    }

    public function bloodRequests(Request $request): JsonResponse
    {
        return $this->tableResponse($request, 'blood_requests');
    }

    public function storeBloodRequest(Request $request): JsonResponse
    {
        return $this->storeRows($request, 'blood_requests', 'id');
    }

    public function inventory(Request $request): JsonResponse
    {
        return $this->tableResponse($request, 'inventory_units');
    }

    public function storeInventory(Request $request): JsonResponse
    {
        $this->requireStaff($request);

        return $this->storeRows($request, 'inventory_units', 'id');
    }

    public function campaigns(Request $request): JsonResponse
    {
        return $this->tableResponse($request, 'campaigns');
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $this->requireStaff($request);

        return $this->storeRows($request, 'campaigns', 'id');
    }

    public function notifications(Request $request): JsonResponse
    {
        return $this->tableResponse($request, 'notifications');
    }

    public function storeNotification(Request $request): JsonResponse
    {
        $this->requireStaff($request);

        return $this->storeRows($request, 'notifications', 'id');
    }

    public function auditLogs(Request $request): JsonResponse
    {
        return $this->tableResponse($request, 'audit_logs');
    }

    public function storeAuditLog(Request $request): JsonResponse
    {
        $this->requireStaff($request);

        return $this->storeRows($request, 'audit_logs', 'id');
    }

    public function storeDocument(Request $request): JsonResponse
    {
        return $this->storeRows($request, 'documents', 'id');
    }

    private function tableResponse(Request $request, string $table): JsonResponse
    {
        return response()->json(['data' => $this->list($this->token($request), $table)]);
    }

    private function storeRows(Request $request, string $table, string $onConflict): JsonResponse
    {
        $rows = $request->all();
        $rows = array_is_list($rows) ? $rows : [$rows];

        return response()->json([
            'data' => $this->supabase->upsert($this->token($request), $table, $rows, $onConflict),
        ], 201);
    }

    private function list(string $token, string $table): array
    {
        return $this->supabase->select($token, $table, ['order' => 'created_at.desc']);
    }

    private function upsertIfPresent(string $token, string $table, array|null $rows, string $onConflict): array
    {
        if (! $rows) {
            return [];
        }

        $normalized = array_is_list($rows) ? $rows : [$rows];

        return $this->supabase->upsert($token, $table, $normalized, $onConflict);
    }

    private function token(Request $request): string
    {
        return (string) $request->attributes->get('supabase_token');
    }

    private function role(Request $request): string
    {
        return (string) ($request->attributes->get('role') ?: 'donor');
    }

    private function requireStaff(Request $request): void
    {
        abort_unless(in_array($this->role($request), ['admin', 'super_admin'], true), 403, 'Staff access required.');
    }
}
