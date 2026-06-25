<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Caja (rendición) — modernización del gestion_caja del ERP original.
 *
 * Modelo:
 *  - ven_caja                       → la caja (estado abierta/cerrada) por empresa/usuario
 *  - ven_cajamovimiento             → EVENTOS del ledger: apertura / ingreso / egreso
 *                                     (el saldo se DERIVA de estos eventos, no se almacena)
 *  - ven_cajarendicion              → cierre/conciliación (totales congelados)
 *  - ven_cajarendicionegreso_tipo   → maestro de tipos de egreso
 *  - sys_evento                     → log de eventos de dominio genérico (capa reusable)
 */
return new class extends Migration
{
    private function auditoria(Blueprint $table): void
    {
        $table->string('userins', 60)->nullable();
        $table->dateTime('fechains')->nullable();
        $table->string('usermod', 60)->nullable();
        $table->dateTime('fechamod')->nullable();
    }

    public function up(): void
    {
        // --- Tipos de egreso (maestro Tipo A) ---
        Schema::create('ven_cajarendicionegreso_tipo', function (Blueprint $table) {
            $table->id('idven_cajarendicionegreso_tipo');
            $table->unsignedBigInteger('idgen_empresa')->index();
            $table->string('descripcion', 120);
            $table->boolean('habilitado')->default(true);
            $this->auditoria($table);
        });

        // --- Caja ---
        Schema::create('ven_caja', function (Blueprint $table) {
            $table->id('idven_caja');
            $table->unsignedBigInteger('idgen_empresa')->index();
            $table->unsignedBigInteger('idusu_usuario')->nullable();   // quién la tiene abierta
            $table->unsignedTinyInteger('idven_cajaestado')->default(1); // 1=abierta, 2=cerrada
            $table->string('descripcion', 120)->default('Caja');
            $table->integer('monto_inicial')->default(0);              // CLP
            $table->dateTime('fecha_apertura')->nullable();
            $table->dateTime('fecha_cierre')->nullable();
            $table->boolean('habilitado')->default(true);
            $this->auditoria($table);
        });

        // --- Movimientos = stream de eventos de la caja ---
        Schema::create('ven_cajamovimiento', function (Blueprint $table) {
            $table->id('idven_cajamovimiento');
            $table->unsignedBigInteger('idgen_empresa')->index();
            $table->unsignedBigInteger('idven_caja')->index();
            $table->enum('tipo', ['apertura', 'ingreso', 'egreso']);
            $table->unsignedBigInteger('idven_cajarendicionegreso_tipo')->nullable(); // solo egreso
            $table->string('descripcion', 160);
            $table->integer('monto')->default(0);                     // CLP (positivo)
            $table->date('fecha');
            $table->boolean('anulado')->default(false);
            $table->string('anulamotivo', 200)->nullable();
            $table->string('anulauser', 60)->nullable();
            $table->dateTime('anulafecha')->nullable();
            $table->boolean('habilitado')->default(true);
            $this->auditoria($table);
        });

        // --- Rendición (cierre): totales congelados ---
        Schema::create('ven_cajarendicion', function (Blueprint $table) {
            $table->id('idven_cajarendicion');
            $table->unsignedBigInteger('idgen_empresa')->index();
            $table->unsignedBigInteger('idven_caja');
            $table->unsignedBigInteger('idusu_usuario')->nullable();
            $table->integer('rendicion')->default(0);                 // correlativo por empresa
            $table->date('fecha');
            $table->dateTime('apertura_fecha')->nullable();
            $table->string('apertura_usuario', 60)->nullable();
            $table->dateTime('cierre_fecha')->nullable();
            $table->string('cierre_usuario', 60)->nullable();
            $table->integer('monto_inicial')->default(0);
            $table->integer('total_ingresos')->default(0);
            $table->integer('total_egresos')->default(0);
            $table->integer('total_ventas')->default(0);
            $table->integer('saldo_final')->default(0);
            $table->boolean('habilitado')->default(true);
            $this->auditoria($table);
        });

        // --- Log de eventos de dominio (genérico, append-only) ---
        Schema::create('sys_evento', function (Blueprint $table) {
            $table->id('idsys_evento');
            $table->unsignedBigInteger('idgen_empresa')->index();
            $table->unsignedBigInteger('idusu_usuario')->nullable();
            $table->string('tipo', 60)->index();          // 'caja.abierta', 'caja.ingreso', ...
            $table->string('titulo', 160);
            $table->text('detalle')->nullable();          // JSON
            $table->string('referencia_tabla', 60)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->string('userins', 60)->nullable();
            $table->dateTime('fechains')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_evento');
        Schema::dropIfExists('ven_cajarendicion');
        Schema::dropIfExists('ven_cajamovimiento');
        Schema::dropIfExists('ven_caja');
        Schema::dropIfExists('ven_cajarendicionegreso_tipo');
    }
};
