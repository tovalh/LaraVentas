<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de ventas ("ven_") — patrón maestro-detalle del ERP original.
 *  - ven_venta          → cabecera (cliente, totales en CLP)
 *  - ven_venta_detalle  → líneas (producto, cantidad, precio, subtotal)
 * La cabecera y el detalle se guardan en una sola transacción.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ven_venta', function (Blueprint $table) {
            $table->id('idven_venta');
            $table->unsignedBigInteger('idgen_empresa')->index();
            $table->unsignedBigInteger('idgen_cliente')->nullable();
            $table->string('numero', 20);                 // 001-001-0000001
            $table->date('fecha');
            $table->integer('neto')->default(0);          // CLP
            $table->integer('iva')->default(0);           // CLP (19%)
            $table->integer('total')->default(0);         // CLP
            $table->boolean('habilitado')->default(true);
            $table->string('userins', 60)->nullable();
            $table->dateTime('fechains')->nullable();
            $table->string('usermod', 60)->nullable();
            $table->dateTime('fechamod')->nullable();
        });

        Schema::create('ven_venta_detalle', function (Blueprint $table) {
            $table->id('idven_venta_detalle');
            $table->unsignedBigInteger('idven_venta');
            $table->unsignedBigInteger('idgen_producto')->nullable();
            $table->string('descripcion', 160);
            $table->integer('cantidad')->default(1);
            $table->integer('precio')->default(0);        // CLP
            $table->integer('subtotal')->default(0);      // CLP
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ven_venta_detalle');
        Schema::dropIfExists('ven_venta');
    }
};
