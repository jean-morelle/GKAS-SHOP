<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug', 60)->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('primary_color', 7)->default('#4f46e5');
            $table->string('theme', 20)->default('moderne');
            $table->string('whatsapp', 30)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('delivery_zone')->nullable();
            $table->string('opening_hours')->nullable();
            $table->text('order_conditions')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('tiktok_url')->nullable();

            // Statut administratif (indépendant de l'abonnement)
            $table->string('status', 20)->default('active')->index();
            $table->text('suspension_reason')->nullable();
            $table->dateTime('suspended_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_demo')->default(false);

            // Accès commercial : essai et abonnement
            $table->dateTime('trial_starts_at')->nullable();
            $table->dateTime('trial_ends_at')->nullable()->index();
            $table->dateTime('subscription_activated_at')->nullable();
            $table->dateTime('subscription_ends_at')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('store_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('owner');
            $table->timestamps();
            $table->unique(['store_id', 'user_id']);
        });

        Schema::create('trial_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('action', 20); // started, extended, expired, adjusted
            $table->dateTime('previous_ends_at')->nullable();
            $table->dateTime('new_ends_at')->nullable();
            $table->integer('days')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Anti-doublon pour les alertes automatiques (J-7, J-3, J-1, expiration…)
        Schema::create('store_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('key', 120);
            $table->dateTime('created_at')->nullable();
            $table->unique(['store_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_alerts');
        Schema::dropIfExists('trial_histories');
        Schema::dropIfExists('store_user');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('settings');
    }
};
