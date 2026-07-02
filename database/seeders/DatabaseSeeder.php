<?php

namespace Database\Seeders;

use App\Models\Caja;
use App\Models\CajaEgresoTipo;
use App\Models\Cliente;
use App\Models\Comuna;
use App\Models\Empresa;
use App\Models\Evento;
use App\Models\Familia;
use App\Models\Notificacion;
use App\Models\Producto;
use App\Models\Region;
use App\Models\Unidadmedida;
use App\Models\Unidadmedidatipo;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->referencias();

        $empresas = [
            ['razon_social' => 'Comercial Andes S.A.',    'ruc' => '76.111.111-1', 'color' => '#2563eb', 'email' => 'admin@empresa-a.com', 'user' => 'admina'],
            ['razon_social' => 'Distribuidora Sur Ltda.', 'ruc' => '77.222.222-2', 'color' => '#059669', 'email' => 'admin@empresa-b.com', 'user' => 'adminb'],
        ];

        foreach ($empresas as $i => $data) {
            $empresa = Empresa::create([
                'razon_social' => $data['razon_social'],
                'ruc'          => $data['ruc'],
                'color'        => $data['color'],
                'habilitado'   => true,
            ]);

            $user = User::create([
                'idgen_empresa' => $empresa->idgen_empresa,
                'name'          => 'Administrador ' . chr(65 + $i),
                'username'      => $data['user'],
                'email'         => $data['email'],
                'password'      => Hash::make('password'),
                'habilitado'    => true,
            ]);

            // Transacción: acelera mucho la siembra de miles de filas en MySQL.
            DB::transaction(fn () => $this->datosEmpresa($empresa->idgen_empresa, $user->id));

            $this->notificaciones($user->id, $empresa->idgen_empresa);
        }
    }

    /** Notificaciones demo para un usuario (algunas sin leer). */
    private function notificaciones(int $userId, int $empresaId): void
    {
        $demo = [
            ['Bienvenido a LaraVentas', 'Explora los módulos desde el menú lateral.', '/', false],
            ['Stock crítico', 'Hay productos por reponer. Revisa el dashboard.', '/productos', false],
            ['Nuevo cliente', 'Se registró un cliente en tu cartera.', '/clientes', false],
            ['Cierre del día', 'Recuerda revisar las ventas de hoy.', '/ventas', true],
        ];

        foreach ($demo as $i => [$titulo, $mensaje, $url, $leido]) {
            Notificacion::create([
                'idgen_empresa' => $empresaId,
                'idusu_usuario' => $userId,
                'titulo'        => $titulo,
                'mensaje'       => $mensaje,
                'url'           => $url,
                'leido'         => $leido,
                'fecha_leido'   => $leido ? now() : null,
                'userins'       => 'system',
                'fechains'      => now()->subHours(count($demo) - $i),
                'habilitado'    => true,
            ]);
        }
    }

    /** Datos de referencia globales (regiones / comunas). */
    private function referencias(): void
    {
        $mapa = [
            'Metropolitana' => ['Santiago', 'Providencia', 'Maipú', 'Las Condes', 'Puente Alto'],
            'Valparaíso'    => ['Valparaíso', 'Viña del Mar', 'Quilpué', 'Concón'],
            'Biobío'        => ['Concepción', 'Talcahuano', 'Chillán'],
        ];

        foreach ($mapa as $region => $comunas) {
            $r = Region::create(['descripcion' => $region, 'habilitado' => true]);
            foreach ($comunas as $c) {
                Comuna::create(['idgen_region' => $r->idgen_region, 'descripcion' => $c, 'habilitado' => true]);
            }
        }
    }

    /** Maestros + ventas demo de una empresa. */
    private function datosEmpresa(int $empresaId, int $userId): void
    {
        // Tipos de unidad de medida
        $tipos = [];
        foreach (['Unidad', 'Peso', 'Volumen'] as $t) {
            $tipos[$t] = Unidadmedidatipo::create([
                'idgen_empresa' => $empresaId, 'descripcion' => $t, 'habilitado' => true,
            ])->idgen_unidadmedidatipo;
        }

        // Unidades de medida
        $unidades = [];
        foreach ([['Unidad', 'Unidad', 'UN'], ['Peso', 'Kilogramo', 'KG'], ['Volumen', 'Litro', 'LT'], ['Unidad', 'Caja', 'CJ']] as [$tipo, $desc, $abr]) {
            $unidades[] = Unidadmedida::create([
                'idgen_empresa'          => $empresaId,
                'idgen_unidadmedidatipo' => $tipos[$tipo],
                'descripcion'            => $desc,
                'abreviatura'            => $abr,
                'habilitado'             => true,
            ])->idgen_unidadmedida;
        }

        // Familias
        $familias = [];
        foreach ([['BEB', 'Bebidas'], ['ABA', 'Abarrotes'], ['LIM', 'Limpieza'], ['LAC', 'Lácteos']] as [$cod, $desc]) {
            $familias[] = Familia::create([
                'idgen_empresa' => $empresaId, 'codigo' => $cod, 'descripcion' => $desc, 'habilitado' => true,
            ])->idgen_familia;
        }

        // Productos (cientos, precio en CLP entero). Nombres generados con variedad.
        $bases = [
            'Bebida Cola', 'Agua Mineral', 'Jugo Néctar', 'Bebida Naranja', 'Energética',
            'Arroz', 'Aceite', 'Fideos', 'Azúcar', 'Harina', 'Sal', 'Conserva Atún',
            'Detergente', 'Cloro', 'Jabón', 'Lavaloza', 'Toalla Nova', 'Papel Higiénico',
            'Leche', 'Yogurt', 'Mantequilla', 'Queso', 'Café', 'Té',
        ];
        $variantes = ['500ml', '1L', '1.5L', '2L', '250g', '400g', '500g', '1kg', '2kg', 'Pack 6', 'Familiar', 'Premium'];
        $productos = [];
        $totalProductos = 200;
        for ($n = 0; $n < $totalProductos; $n++) {
            $desc  = $bases[$n % count($bases)] . ' ' . $variantes[($n * 7) % count($variantes)];
            $stock = mt_rand(0, 250);
            $productos[] = Producto::create([
                'idgen_empresa'      => $empresaId,
                'idgen_familia'      => $familias[$n % count($familias)],
                'idgen_unidadmedida' => $unidades[$n % count($unidades)],
                'codigo'             => 'P' . str_pad((string) ($n + 1), 5, '0', STR_PAD_LEFT),
                'descripcion'        => $desc,
                'precio'             => mt_rand(40, 1500) * 10,   // CLP múltiplos de 10
                'stock'              => $stock,
                'stock_critico'      => 50,
                'habilitado'         => true,
            ]);
        }

        $comunas = Comuna::pluck('idgen_comuna')->all();

        // Clientes (cientos)
        $tiposNeg = ['Minimarket', 'Almacén', 'Botillería', 'Supermercado', 'Kiosco', 'Restaurant', 'Distribuidora', 'Comercial'];
        $apellidos = ['El Sol', 'Central', 'Express', 'La Esquina', 'Don Pepe', 'Doña Rosa', 'San José', 'Las Flores', 'El Trébol', 'La Bodega', 'Los Andes', 'El Buen Precio'];
        $clientes = [];
        $totalClientes = 150;
        for ($n = 0; $n < $totalClientes; $n++) {
            $clientes[] = Cliente::create([
                'idgen_empresa' => $empresaId,
                'idgen_comuna'  => $comunas[array_rand($comunas)],
                'rut'           => mt_rand(7, 19) . '.' . mt_rand(100, 999) . '.' . mt_rand(100, 999) . '-' . mt_rand(0, 9),
                'razon_social'  => $tiposNeg[$n % count($tiposNeg)] . ' ' . $apellidos[($n * 5) % count($apellidos)] . ' ' . ($n + 1),
                'direccion'     => 'Av. Principal ' . mt_rand(100, 9999),
                'email'         => 'contacto' . ($n + 1) . '@cliente.cl',
                'telefono'      => '+569 ' . mt_rand(1000, 9999) . ' ' . mt_rand(1000, 9999),
                'habilitado'    => true,
            ])->idgen_cliente;
        }

        // Ventas (miles): cabecera + detalle, totales en CLP, IVA 19%.
        // Fechas repartidas en los últimos 60 días.
        $totalVentas = 800;
        for ($v = 1; $v <= $totalVentas; $v++) {
            $venta = Venta::create([
                'idgen_empresa' => $empresaId,
                'idgen_cliente' => $clientes[array_rand($clientes)],
                'numero'        => '001-001-' . str_pad((string) $v, 7, '0', STR_PAD_LEFT),
                'fecha'         => now()->subDays(mt_rand(0, 59))->toDateString(),
                'neto'          => 0, 'iva' => 0, 'total' => 0,
                'habilitado'    => true,
            ]);

            $neto = 0;
            $lineas = mt_rand(1, 5);
            for ($l = 1; $l <= $lineas; $l++) {
                $prod = $productos[array_rand($productos)];
                $cant = mt_rand(1, 8);
                $sub  = $cant * $prod->precio;
                $neto += $sub;
                $venta->detalles()->create([
                    'idgen_producto' => $prod->idgen_producto,
                    'descripcion'    => $prod->descripcion,
                    'cantidad'       => $cant,
                    'precio'         => $prod->precio,
                    'subtotal'       => $sub,
                ]);
            }
            $iva = (int) round($neto * 0.19);
            $venta->update(['neto' => $neto, 'iva' => $iva, 'total' => $neto + $iva]);
        }

        $this->caja($empresaId, $userId);
    }

    /** Caja abierta demo: tipos de egreso + movimientos + ventas vinculadas + eventos. */
    private function caja(int $empresaId, int $userId): void
    {
        $egresoTipos = [];
        foreach (['Gasto general', 'Retiro de efectivo', 'Pago a proveedor', 'Compra de insumos'] as $d) {
            $egresoTipos[] = CajaEgresoTipo::create([
                'idgen_empresa' => $empresaId, 'descripcion' => $d, 'habilitado' => true,
            ])->idven_cajarendicionegreso_tipo;
        }

        $montoInicial = 50000;
        $caja = Caja::create([
            'idgen_empresa'    => $empresaId,
            'idusu_usuario'    => $userId,
            'idven_cajaestado' => Caja::ABIERTA,
            'descripcion'      => 'Caja',
            'monto_inicial'    => $montoInicial,
            'fecha_apertura'   => now(),
            'habilitado'       => true,
        ]);

        $hoy = now()->toDateString();
        $caja->movimientos()->create(['idgen_empresa' => $empresaId, 'tipo' => 'apertura',
            'descripcion' => 'Monto apertura de caja', 'monto' => $montoInicial, 'fecha' => $hoy]);
        $this->evento($empresaId, $userId, 'caja.abierta', 'Caja abierta', ['monto_inicial' => $montoInicial]);

        $caja->movimientos()->create(['idgen_empresa' => $empresaId, 'tipo' => 'ingreso',
            'descripcion' => 'Cambio inicial adicional', 'monto' => 20000, 'fecha' => $hoy]);
        $this->evento($empresaId, $userId, 'caja.ingreso', 'Ingreso de caja: Cambio inicial adicional', ['monto' => 20000]);

        $caja->movimientos()->create(['idgen_empresa' => $empresaId, 'tipo' => 'egreso',
            'idven_cajarendicionegreso_tipo' => $egresoTipos[0],
            'descripcion' => 'Compra de café para la oficina', 'monto' => 6500, 'fecha' => $hoy]);
        $this->evento($empresaId, $userId, 'caja.egreso', 'Egreso de caja: Compra de café para la oficina', ['monto' => 6500]);

        // Vincula las ventas más recientes a la caja abierta (fecha de hoy) + eventos.
        $recientes = Venta::where('idgen_empresa', $empresaId)->orderByDesc('idven_venta')->limit(6)->get();
        foreach ($recientes as $v) {
            $v->update(['idven_caja' => $caja->idven_caja, 'fecha' => $hoy]);
            $this->evento($empresaId, $userId, 'venta.registrada', "Venta {$v->numero} registrada", ['total' => $v->total]);
        }
    }

    /** Evento de dominio para el seeder (sin auth: empresa/usuario explícitos). */
    private function evento(int $empresaId, int $userId, string $tipo, string $titulo, array $detalle): void
    {
        Evento::create([
            'idgen_empresa' => $empresaId,
            'idusu_usuario' => $userId,
            'tipo'          => $tipo,
            'titulo'        => $titulo,
            'detalle'       => $detalle,
            'userins'       => 'system',
            'fechains'      => now(),
        ]);
    }
}
