<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas maestras del esquema "gen_" (general), replicando las convenciones del
 * ERP original:
 *  - PK = id + nombre de tabla  (idgen_producto, idgen_familia, ...)
 *  - idgen_empresa para multi-empresa (salvo datos de referencia globales)
 *  - habilitado (soft-delete por estado, nunca DELETE)
 *  - auditoría: userins/fechains (alta) + usermod/fechamod (modificación)
 */
return new class extends Migration
{
    /** Columnas de auditoría comunes a todos los maestros. */
    private function auditoria(Blueprint $table): void
    {
        $table->string('userins', 60)->nullable();
        $table->dateTime('fechains')->nullable();
        $table->string('usermod', 60)->nullable();
        $table->dateTime('fechamod')->nullable();
    }

    public function up(): void
    {
        // --- Tipo de unidad de medida (gen_unidadmedidatipo) ---
        Schema::create('gen_unidadmedidatipo', function (Blueprint $table) {
            $table->id('idgen_unidadmedidatipo');
            $table->unsignedBigInteger('idgen_empresa')->index();
            $table->string('descripcion', 120);
            $table->boolean('habilitado')->default(true);
            $this->auditoria($table);
        });

        // --- Unidad de medida (gen_unidadmedida) ---
        Schema::create('gen_unidadmedida', function (Blueprint $table) {
            $table->id('idgen_unidadmedida');
            $table->unsignedBigInteger('idgen_empresa')->index();
            $table->unsignedBigInteger('idgen_unidadmedidatipo');
            $table->string('descripcion', 120);
            $table->string('abreviatura', 20);
            $table->boolean('habilitado')->default(true);
            $this->auditoria($table);
        });

        // --- Familia de productos (gen_familia) ---
        Schema::create('gen_familia', function (Blueprint $table) {
            $table->id('idgen_familia');
            $table->unsignedBigInteger('idgen_empresa')->index();
            $table->string('codigo', 30)->nullable();
            $table->string('descripcion', 120);
            $table->boolean('habilitado')->default(true);
            $this->auditoria($table);
        });

        // --- Producto (gen_producto) ---
        Schema::create('gen_producto', function (Blueprint $table) {
            $table->id('idgen_producto');
            $table->unsignedBigInteger('idgen_empresa')->index();
            $table->unsignedBigInteger('idgen_familia')->nullable();
            $table->unsignedBigInteger('idgen_unidadmedida')->nullable();
            $table->string('codigo', 30)->nullable();
            $table->string('descripcion', 160);
            $table->integer('precio')->default(0);          // CLP (entero, sin decimales)
            $table->integer('stock')->default(0);
            $table->integer('stock_critico')->default(0);
            $table->boolean('habilitado')->default(true);
            $this->auditoria($table);
        });

        // --- Región (gen_region) — dato de referencia, global (sin empresa) ---
        Schema::create('gen_region', function (Blueprint $table) {
            $table->id('idgen_region');
            $table->string('descripcion', 120);
            $table->boolean('habilitado')->default(true);
        });

        // --- Comuna (gen_comuna) — referencia, global ---
        Schema::create('gen_comuna', function (Blueprint $table) {
            $table->id('idgen_comuna');
            $table->unsignedBigInteger('idgen_region');
            $table->string('descripcion', 120);
            $table->boolean('habilitado')->default(true);
        });

        // --- Cliente (gen_cliente) ---
        Schema::create('gen_cliente', function (Blueprint $table) {
            $table->id('idgen_cliente');
            $table->unsignedBigInteger('idgen_empresa')->index();
            $table->unsignedBigInteger('idgen_comuna')->nullable();
            $table->string('rut', 20)->nullable();
            $table->string('razon_social', 160);
            $table->string('direccion', 200)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('telefono', 40)->nullable();
            $table->boolean('habilitado')->default(true);
            $this->auditoria($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gen_cliente');
        Schema::dropIfExists('gen_comuna');
        Schema::dropIfExists('gen_region');
        Schema::dropIfExists('gen_producto');
        Schema::dropIfExists('gen_familia');
        Schema::dropIfExists('gen_unidadmedida');
        Schema::dropIfExists('gen_unidadmedidatipo');
    }
};
