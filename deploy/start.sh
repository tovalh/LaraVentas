#!/usr/bin/env bash
###############################################################################
# Arranque en Railway (LaraVentas)
# - Espera a que MySQL esté disponible
# - Cachea config/rutas/vistas
# - Migra y siembra data demo limpia
# - Sirve la app en el puerto que asigna Railway ($PORT)
#
# Requiere un servicio MySQL en Railway y las variables DB_* apuntando a él
# (ver README → "Desplegar en Railway").
###############################################################################
set -e

# Espera a que la base MySQL acepte conexiones (hasta ~60s).
echo "Esperando a MySQL en ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 30); do
    if php -r '
        try {
            new PDO(
                "mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT"),
                getenv("DB_USERNAME"),
                getenv("DB_PASSWORD")
            );
            exit(0);
        } catch (Exception $e) { exit(1); }
    '; then
        echo "MySQL disponible."
        break
    fi
    sleep 2
done

# Cache de framework (rápido en producción)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Data demo determinística: limpia + semilla en cada despliegue.
php artisan migrate:fresh --seed --force

# Servir. Railway expone la app por $PORT.
php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
