<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('supabase_user_id')->nullable()->unique();
            $table->string('role')->default('donor');
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('blood_type')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('donors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->string('donor_code')->unique();
            $table->string('full_name');
            $table->string('blood_type')->nullable();
            $table->string('eligibility_status')->default('eligible');
            $table->date('last_donation_at')->nullable();
            $table->date('registered_at')->nullable();
            $table->unsignedInteger('total_units')->default(0);
            $table->json('medical_history')->nullable();
            $table->timestamps();
        });

        Schema::create('donation_centers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('center_type')->default('Major Clinic');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('donor_id')->constrained('donors')->cascadeOnDelete();
            $table->foreignId('donation_center_id')->nullable()->constrained('donation_centers')->nullOnDelete();
            $table->string('service_type')->default('Whole Blood Donation');
            $table->timestamp('scheduled_at');
            $table->string('status')->default('confirmed');
            $table->json('eligibility_answers')->nullable();
            $table->timestamps();
        });

        Schema::create('blood_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('patient_name');
            $table->string('hospital_name');
            $table->string('physician_name')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('blood_type');
            $table->string('component_type')->default('Whole Blood');
            $table->unsignedInteger('units_needed');
            $table->timestamp('required_at')->nullable();
            $table->string('urgency')->default('routine');
            $table->text('diagnosis')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('inventory_units', function (Blueprint $table): void {
            $table->id();
            $table->string('unit_code')->unique();
            $table->string('blood_type');
            $table->string('component_type')->default('Whole Blood');
            $table->date('collection_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('available');
            $table->string('storage_zone')->nullable();
            $table->decimal('storage_temperature', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->string('location_summary')->nullable();
            $table->string('status')->default('upcoming');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('target_role')->nullable();
            $table->foreignId('profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_role')->nullable();
            $table->string('action');
            $table->string('ip_address')->nullable();
            $table->string('status')->default('success');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blood_request_id')->nullable()->constrained('blood_requests')->cascadeOnDelete();
            $table->string('storage_bucket');
            $table->string('storage_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('inventory_units');
        Schema::dropIfExists('blood_requests');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('donation_centers');
        Schema::dropIfExists('donors');
        Schema::dropIfExists('profiles');
    }
};
