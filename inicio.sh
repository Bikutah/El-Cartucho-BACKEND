#!/bin/bash

# Crear archivo SQLite si no existe
if [ ! -f /app/database/database.sqlite ]; then
  echo "Creando base de datos SQLite..."
  touch /app/database/database.sqlite
fi

# Correr el servidor
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
