<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('mensaje');
            $table->string('url')->nullable();
            $table->integer('enviadas')->default(0);   // suscripciones a las que se envió
            $table->integer('exitosas')->default(0);   // envíos confirmados OK
            $table->integer('fallidas')->default(0);   // envíos fallidos
            $table->string('tipo')->default('personalizada'); // 'personalizada' | 'nuevo_producto'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
