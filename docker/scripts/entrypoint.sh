#!/bin/sh
set -e

# Define USER como www-data se não estiver setado
USER=${USER:-www-data}

echo "Corrigindo permissões..."
chown -R $USER:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

echo "Configurando Git seguro..."
git config --global --add safe.directory /var/www/html

echo "Instalando dependências..."
[ -d vendor ] || (command -v composer >/dev/null && composer install --no-interaction)

echo "Rodando migrações..."
php artisan migrate || echo "⚠️ Migrações falharam"

echo "Limpando cache e otimizando..."
php artisan cache:clear
php artisan optimize:clear

echo "✅ Pronto. Iniciando o PHP-FPM..."
exec php-fpm