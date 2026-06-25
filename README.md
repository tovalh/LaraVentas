# KUDOS Demo — ERP (Laravel + Inertia + Vue 3 + PrimeVue)

Demo de portafolio que reproduce la **estructura, los patrones y la visual** de un
ERP real (sidebar, topbar, mantenedores con botonera fija, maestro-detalle,
multi-empresa, auditoría, montos en CLP), pero **conectado a una sola base de datos
MySQL** definida en `.env` y desplegable en **Railway** en pocos minutos.

> Reescritura simplificada hecha desde cero para mostrar la arquitectura.
> No contiene código del sistema original ni conexiones a bases externas.

## Stack

- **Backend:** Laravel 13 (PHP 8.3+)
- **SPA:** Inertia.js + Vue 3
- **UI:** PrimeVue 4 (tema Aura) sobre Tailwind v4
- **BD:** MySQL (una sola conexión, vía variables `DB_*` en `.env`)

## El "mini-framework" de Maestros

Lo más interesante de la demo: igual que en el ERP original (donde creabas un archivo
y ya tenías CRUD, botonera, filtros y auditoría), acá un mantenedor nuevo se declara
en **un solo controller delgado** y obtiene toda la UI gratis.

```php
// app/Http/Controllers/UnidadmedidaController.php
class UnidadmedidaController extends MaestroController
{
    protected function maestro(): Maestro
    {
        return Maestro::make('Unidades de Medida', Unidadmedida::class, '/unidades')
            ->with('tipo')
            ->column('tipo.descripcion', 'Tipo')
            ->column('descripcion', 'Descripción')
            ->filterText('descripcion', 'Descripción')
            ->fieldSwitch('habilitado', 'Habilitado')
            ->fieldText('descripcion', 'Descripción', required: true, unique: true);
    }
}
```

Con eso, el módulo ya tiene: listado paginado, filtros colapsables, botonera fija
(Nuevo / Guardar / Eliminar), formulario con footer de auditoría, soft-delete,
unicidad y multi-empresa. La UI la pinta **una sola** página Vue genérica
(`Pages/Maestro/Index.vue`).

| Pieza | Rol |
|-------|-----|
| `app/Support/Maestro/Maestro.php`      | Builder declarativo (columnas, filtros, campos) |
| `app/Http/Controllers/MaestroController.php` | CRUD genérico + auditoría + empresa + unicidad |
| `resources/js/Pages/Maestro/Index.vue` | Vista genérica (lista ↔ form + botonera fija) |
| `routes/web.php` → `maestro()`         | Registra las 4 rutas de un maestro en 1 línea |

## Módulos demo

| Módulo            | Tipo                      | Qué muestra                                  |
|-------------------|---------------------------|----------------------------------------------|
| Inicio            | Especial (widgets)        | KPIs en CLP, gráfico de ventas, accesos      |
| Productos         | A · CRUD (maestro)        | Precio/stock en CLP, familia y unidad        |
| Familias          | A · CRUD                  | Mantenedor simple                            |
| Unidades de medida| A · CRUD                  | Relación con "tipo de unidad"                |
| Tipos de unidad   | A · CRUD                  | Mantenedor base                              |
| Clientes          | A · CRUD                  | RUT, comuna (referencia global)              |
| Ventas            | B · Maestro-detalle       | Cabecera + líneas, IVA 19%, totales en CLP   |

Patrones de arquitectura del ERP original (versión limpia):

- **Multi-empresa automática** — trait `BelongsToEmpresa`: global scope que filtra por
  `idgen_empresa` del usuario y lo asigna al crear.
- **Auditoría** — trait `HasAuditoria`: `userins`/`fechains` al crear y
  `usermod`/`fechamod` al guardar.
- **Soft-delete por estado** — `habilitado = false`, nunca DELETE.
- **Tablas con convención del original** — prefijos `gen_`/`ven_`, PK `id` + tabla
  (`idgen_producto`, `idven_venta`), CLP en enteros.

## Cuentas demo

| Empresa                   | Correo                  | Contraseña |
|---------------------------|-------------------------|------------|
| Comercial Andes S.A.      | `admin@empresa-a.com`   | `password` |
| Distribuidora Sur Ltda.   | `admin@empresa-b.com`   | `password` |

Entra con A y con B en navegadores distintos: cada una ve únicamente sus propios
productos, clientes y ventas (multi-empresa).

## Correr en local

Requiere un MySQL accesible. Crea la base y ajusta `DB_*` en `.env`:

```bash
mysql -u root -p -e "CREATE DATABASE larasaas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

composer install
npm install
cp .env.example .env        # si no existe
php artisan key:generate
# edita .env -> DB_DATABASE / DB_USERNAME / DB_PASSWORD
php artisan migrate:fresh --seed
npm run build               # o "npm run dev" en desarrollo
php artisan serve
```

Abre http://localhost:8000.

## Desplegar en Railway

1. Sube el repo a GitHub y crea un proyecto en Railway desde ese repo
   (detecta Nixpacks automáticamente; ya hay `nixpacks.toml`).
2. Agrega un servicio **MySQL** al proyecto (New → Database → MySQL).
3. En **Variables** del servicio de la app, agrega:
   ```
   APP_KEY=base64:...        # genera con: php artisan key:generate --show
   APP_ENV=production
   APP_DEBUG=false

   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

   SESSION_DRIVER=database
   ```
   (las referencias `${{MySQL.*}}` las inyecta Railway desde el servicio MySQL).
4. Deploy. En cada arranque, `deploy/start.sh`:
   - espera a que MySQL acepte conexiones,
   - cachea config/rutas/vistas,
   - corre `migrate:fresh --seed` (data demo limpia y determinística),
   - sirve la app en `$PORT`.

> La data se regenera limpia en cada despliegue: justo lo deseable para una demo
> pública. Si quieres datos persistentes, quita `migrate:fresh` del `start.sh`.

## Estructura

```
app/
  Http/Controllers/   Maestro (base) + Producto/Familia/Unidadmedida/.../Venta + Dashboard/Auth
  Models/             MaestroModel (base) + gen_*/ven_* + Empresa/User
  Models/Concerns/    BelongsToEmpresa, HasAuditoria
  Support/Maestro/    Maestro.php (builder declarativo)
resources/js/
  Layouts/AppLayout.vue       sidebar + topbar (la "visual")
  components/Botonera.vue      barra de acciones fija
  lib/format.js                CLP / fechas / paths
  Pages/Maestro/Index.vue      vista genérica de TODOS los mantenedores
  Pages/Ventas/                maestro-detalle (Index + Show)
  Pages/Dashboard.vue          widgets / KPIs
routes/web.php                login + maestro() + ventas
deploy/start.sh               arranque para Railway (MySQL)
nixpacks.toml                 build/deploy
```
