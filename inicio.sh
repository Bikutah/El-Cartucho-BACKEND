# Crear archivo SQLite si no existe
#!/bin/bash

# Configuración
DB_NAME="el_cartucho_db"
DB_PATH="./database/database.sqlite"
ADMIN_EMAIL="admin@admin.com"
ADMIN_PASSWORD="admin"  # ¡Solo para desarrollo!

# Paso 1: Crear archivo SQLite si no existe
echo "🔧 Creando base de datos SQLite en $DB_PATH..."
mkdir -p "$(dirname "$DB_PATH")"
touch "$DB_PATH"

# Paso 2: Limpiar cache y generar claves
echo "🚀 Preparando entorno Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan key:generate

# Paso 3: Migrar tablas
echo "📦 Ejecutando migraciones..."
php artisan migrate:fresh --seed

# Paso 4: Crear usuario admin manualmente
echo "👤 Creando usuario administrador..."
php artisan tinker --execute="
\\App\\Models\\User::updateOrCreate(
    ['email' => '$ADMIN_EMAIL'],
    [
        'name' => 'admin',
        'email' => '$ADMIN_EMAIL',
        'password' => bcrypt('$ADMIN_PASSWORD'),
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now()
    ]
);
"

echo "✅ Usuario admin creado: $ADMIN_EMAIL / $ADMIN_PASSWORD"

# Correr el servidor
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
