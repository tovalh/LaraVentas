<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Empresas: tabla maestra del esquema multi-empresa.
 * Cada registro de negocio se filtra por idgen_empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id('idgen_empresa');
            $table->string('razon_social');
            $table->string('ruc', 20)->nullable();
            $table->string('color', 9)->default('#2563eb'); // color de marca para el sidebar
            $table->boolean('habilitado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
