<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EligibilityScreeningTest extends TestCase
{
    public function test_positive_screening_answer_defers_and_blocks_appointment_submission(): void
    {
        Http::fake();

        $this->withSession([
            'supabase.access_token' => 'donor-access-token',
            'profile' => [
                'id' => 'donor-profile-id',
                'role' => 'donor',
            ],
        ])->post(route('donor.schedule.save'), [
            '_active_tab' => 'schedule',
            'service_type' => 'Donor Appointment',
            'center_name' => 'PRC Laguna Chapter - Santa Rosa Branch',
            'donation_center_id' => 'CTR-SR-LAGUNA',
            'scheduled_date' => '2026-06-25',
            'scheduled_time' => '10:30 AM',
            'fever' => '1',
        ])->assertRedirect(route('donor.shell').'#schedule')
            ->assertSessionHasErrors('eligibility');

        Http::assertNothingSent();
    }
}
