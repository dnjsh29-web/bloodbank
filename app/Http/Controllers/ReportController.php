<?php

namespace App\Http\Controllers;

use App\Services\SupabaseRest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly SupabaseRest $supabase)
    {
    }

    public function download(Request $request, string $type)
    {
        $token = (string) $request->session()->get('supabase.access_token');
        $data = [
            'type' => $type,
            'profile' => $request->session()->get('profile'),
            'generatedAt' => now()->format('F d, Y h:i A'),
            'donors' => $this->supabase->safeSelect($token, 'donors', ['order' => 'created_at.desc']),
            'appointments' => $this->supabase->safeSelect($token, 'appointments', ['order' => 'created_at.desc']),
            'bloodRequests' => $this->supabase->safeSelect($token, 'blood_requests', ['order' => 'created_at.desc']),
            'inventory' => $this->supabase->safeSelect($token, 'inventory_units', ['order' => 'created_at.desc']),
            'campaigns' => $this->supabase->safeSelect($token, 'campaigns', ['order' => 'created_at.desc']),
            'auditLogs' => $this->supabase->safeSelect($token, 'audit_logs', ['order' => 'created_at.desc']),
        ];

        $filename = match ($type) {
            'security' => 'redcross-security-audit-report-'.now()->format('Y-m-d').'.pdf',
            'inventory' => 'blood-inventory-report-'.now()->format('Y-m-d').'.pdf',
            default => 'redcross-admin-report-'.now()->format('Y-m-d').'.pdf',
        };

        return Pdf::loadView('reports.redcross', $data)->download($filename);
    }
}
