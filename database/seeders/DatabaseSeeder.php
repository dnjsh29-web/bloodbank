<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('profiles')->insert([
            ['id' => 1, 'role' => 'donor', 'full_name' => 'Juan Dela Cruz', 'email' => 'donor@redcross.test', 'phone' => '+63 912 345 6789', 'blood_type' => 'O+', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'role' => 'admin', 'full_name' => 'Alex Rivera', 'email' => 'admin@redcross.test', 'phone' => '+63 912 000 1000', 'blood_type' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'role' => 'super_admin', 'full_name' => 'Maria Santos', 'email' => 'superadmin@redcross.test', 'phone' => '+63 912 000 2000', 'blood_type' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('donors')->insert([
            ['profile_id' => 1, 'donor_code' => 'D-00001', 'full_name' => 'Juan Dela Cruz', 'blood_type' => 'O+', 'eligibility_status' => 'eligible', 'last_donation_at' => '2023-03-15', 'registered_at' => '2019-01-12', 'total_units' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['profile_id' => null, 'donor_code' => 'D-10294', 'full_name' => 'Arthur Morgan', 'blood_type' => 'O-', 'eligibility_status' => 'eligible', 'last_donation_at' => '2023-11-12', 'registered_at' => '2020-01-05', 'total_units' => 12, 'created_at' => $now, 'updated_at' => $now],
            ['profile_id' => null, 'donor_code' => 'D-22910', 'full_name' => 'Sadie Crawford', 'blood_type' => 'A+', 'eligibility_status' => 'deferred', 'last_donation_at' => '2023-12-01', 'registered_at' => '2021-03-15', 'total_units' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['profile_id' => null, 'donor_code' => 'D-55821', 'full_name' => 'John Dutton', 'blood_type' => 'B-', 'eligibility_status' => 'eligible', 'last_donation_at' => '2023-08-24', 'registered_at' => '2019-05-12', 'total_units' => 7, 'created_at' => $now, 'updated_at' => $now],
            ['profile_id' => null, 'donor_code' => 'D-99012', 'full_name' => 'Beth Rigby', 'blood_type' => 'AB+', 'eligibility_status' => 'ineligible', 'last_donation_at' => null, 'registered_at' => '2023-10-10', 'total_units' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('donation_centers')->insert([
            ['name' => 'Port Area Center', 'center_type' => 'Major Clinic', 'address' => '650 Bonifacio Drive, Port Area, Manila', 'latitude' => 14.5922, 'longitude' => 120.9709, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Makati National HQ', 'center_type' => 'Collection Point', 'address' => 'Makati, Metro Manila', 'latitude' => 14.5547, 'longitude' => 121.0244, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Quezon City Mobile Drive', 'center_type' => 'Mobile Drive', 'address' => 'Quezon City, Metro Manila', 'latitude' => 14.6760, 'longitude' => 121.0437, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('appointments')->insert([
            ['donor_id' => 1, 'donation_center_id' => 1, 'service_type' => 'Whole Blood Donation', 'scheduled_at' => '2024-10-28 10:30:00', 'status' => 'confirmed', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('inventory_units')->insert([
            ['unit_code' => '#88219-BC', 'blood_type' => 'O-', 'component_type' => 'Whole Blood', 'collection_date' => '2024-10-12', 'expiry_date' => '2024-10-25', 'status' => 'expiring_soon', 'storage_zone' => 'Alpha', 'storage_temperature' => 4.2, 'created_at' => $now, 'updated_at' => $now],
            ['unit_code' => '#44102-XY', 'blood_type' => 'A+', 'component_type' => 'Platelets', 'collection_date' => '2024-10-20', 'expiry_date' => '2024-10-26', 'status' => 'available', 'storage_zone' => 'Gamma', 'storage_temperature' => 24.5, 'created_at' => $now, 'updated_at' => $now],
            ['unit_code' => '#11293-ZZ', 'blood_type' => 'B+', 'component_type' => 'Plasma', 'collection_date' => '2024-10-18', 'expiry_date' => '2025-01-18', 'status' => 'available', 'storage_zone' => 'Beta', 'storage_temperature' => -30.0, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('campaigns')->insert([
            ['title' => 'Spring Drive 2024', 'description' => 'Annual spring blood collection drive across regional community centers targeting type-O donors.', 'starts_at' => '2024-03-15', 'ends_at' => '2024-04-10', 'location_summary' => '12 Locations', 'status' => 'upcoming', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('notifications')->insert([
            ['target_role' => 'admin', 'type' => 'matching_alert', 'title' => 'O- Donor Match Available', 'body' => 'A compatible O- donor has been identified for potential donation scheduling.', 'created_at' => $now, 'updated_at' => $now],
            ['target_role' => 'admin', 'type' => 'registration', 'title' => 'New Donor Registration', 'body' => 'John Doe has completed registration and is ready for screening.', 'created_at' => $now, 'updated_at' => $now],
            ['target_role' => 'admin', 'type' => 'system_alert', 'title' => 'Low Stock Warning: B+', 'body' => 'B+ whole blood units are approaching minimum threshold.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('audit_logs')->insert([
            ['profile_id' => 2, 'actor_name' => 'Alex Rivera', 'actor_role' => 'Clinical Director', 'action' => 'Generated Regional Stock Report', 'ip_address' => '192.168.1.12', 'status' => 'success', 'created_at' => $now, 'updated_at' => $now],
            ['profile_id' => null, 'actor_name' => 'Unknown User', 'actor_role' => 'Unauthorized', 'action' => 'Unauthorized Login Attempt', 'ip_address' => '45.122.10.8', 'status' => 'failed', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
