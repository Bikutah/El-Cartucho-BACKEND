<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductoSubcategoriaTable extends Migration
{
    public function up()
    {
        Schema::create('producto_subcategoria', function (Blueprint $table) {
            $table->id(); // Optional: primary key for the pivot table
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('subcategoria_id')->constrained('subcategorias')->onDelete('cascade');
            $table->timestamps(); // Optional: if you want created_at/updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('producto_subcategoria');
    }
}