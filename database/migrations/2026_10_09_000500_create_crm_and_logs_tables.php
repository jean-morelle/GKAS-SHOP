<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('demo'); // demo | contact | manual
            $table->string('name', 120);
            $table->string('business_name', 150)->nullable();
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('activity', 150)->nullable();
            $table->string('social_network', 30)->nullable();
            $table->string('social_handle', 150)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->date('next_follow_up_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('commercial_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demo_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60)->index();
            $table->nullableMorphs('subject');
            $table->string('description');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('created_at')->nullable()->index();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('commercial_notes');
        Schema::dropIfExists('demo_requests');
    }
};
