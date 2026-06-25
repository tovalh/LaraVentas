<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notificaciones por usuario (usu_notificacion) — réplica del ERP original.
 * Bandeja por (empresa, usuario, leido). El servicio Go lee esta tabla por polling
 * y empuja por WebSocket; el front también la consume por la API REST.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usu_notificacion', function (Blueprint $table) {
            $table->id('idusu_notificacion');
            $table->unsignedBigInteger('idgen_empresa');
            $table->unsignedBigInteger('idusu_usuario');
            $table->string('titulo', 150);
            $table->text('mensaje');
            $table->string('url', 255)->nullable();
            $table->boolean('leido')->default(false);
            $table->dateTime('fecha_leido')->nullable();

            $table->string('userins', 50)->nullable();
            $table->dateTime('fechains')->nullable();
            $table->string('usermod', 50)->nullable();
            $table->dateTime('fechamod')->nullable();
            $table->boolean('habilitado')->default(true);

            $table->index(['idgen_empresa', 'idusu_usuario', 'leido', 'habilitado'], 'idx_bandeja');
            $table->index('idusu_usuario', 'idx_usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usu_notificacion');
    }
};
