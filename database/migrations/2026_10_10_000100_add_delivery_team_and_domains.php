<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Livraison ----
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->decimal('fee', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['store_id', 'is_active']);
        });

        Schema::table('stores', function (Blueprint $table) {
            // Livraison offerte à partir de ce sous-total (facultatif)
            $table->decimal('free_delivery_threshold', 12, 2)->nullable()->after('delivery_zone');
            // Domaine personnalisé (ex. bellafashion.com), configuré par GKAS-GROUP
            $table->string('custom_domain', 190)->nullable()->unique()->after('slug');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_zone_id')->nullable()->after('delivery_address')->constrained()->nullOnDelete();
            // Instantané de la zone et des frais au moment de la commande
            $table->string('delivery_zone_name', 100)->nullable()->after('delivery_zone_id');
            $table->decimal('delivery_fee', 12, 2)->default(0)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_zone_id');
            $table->dropColumn(['delivery_zone_name', 'delivery_fee']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropUnique(['custom_domain']);
            $table->dropColumn(['free_delivery_threshold', 'custom_domain']);
        });

        Schema::dropIfExists('delivery_zones');
    }
};
