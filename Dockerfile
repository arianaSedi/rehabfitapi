FROM php:8.2-cli

# Dependencias del sistema necesarias para extensiones PHP comunes en Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    pkg-config \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql intl zip \
    && rm -rf /var/lib/apt/lists/*

# Verificación: si pdo_pgsql no quedó cargado, el build falla aquí mismo
# en vez de fallar silenciosamente en producción.
RUN php -m | grep -i pdo_pgsql

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiamos primero solo composer.json/lock para aprovechar la cache de Docker
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copiamos el resto del proyecto
COPY . .

RUN composer dump-autoload --optimize

# Permisos necesarios para que Laravel pueda escribir logs, caché y sesiones
RUN chmod -R 775 storage bootstrap/cache

# Render asigna el puerto via la variable de entorno PORT
EXPOSE 10000

# Migra la base de datos (crea tablas nuevas si las hay) y arranca el servidor.
# El seeder de ejercicios NO corre aquí a propósito: ya se sembró una vez.
# Si necesitas re-sembrar manualmente, usa la pestaña "Shell" de Render:
#   php artisan db:seed --class=EjercicioSeeder --force
CMD php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
