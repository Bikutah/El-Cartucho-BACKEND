<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint', 2048);
            $table->string('p256dh', 512);
            $table->string('auth', 128);
            $table->timestamps();

            // Un usuario puede tener múltiples dispositivos, pero no duplicar el mismo endpoint
            $table->unique(['user_id', 'endpoint'], 'push_user_endpoint_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
