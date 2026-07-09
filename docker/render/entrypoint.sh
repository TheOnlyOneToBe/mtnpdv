#!/bin/sh
set -e

PORT="${PORT:-10000}"
DB_NAME="${MYSQL_DATABASE:-mtnpdv}"
DB_USER="${MYSQL_USER:-mtnpdv}"
DB_PASSWORD="${MYSQL_PASSWORD:-mtnpdv}"
DATA_DIR="${DATA_DIR:-/data}"

echo "[render] Préparation du stockage persistant dans ${DATA_DIR}..."
mkdir -p "$DATA_DIR/mysql" \
         "$DATA_DIR/uploads/profils" \
         "$DATA_DIR/uploads/preuves" \
         "$DATA_DIR/uploads/pos"
chown -R mysql:mysql "$DATA_DIR/mysql"
chown -R www-data:www-data "$DATA_DIR/uploads"

# Les uploads vivent sur le disque persistant, servis via un lien symbolique
rm -rf /var/www/html/public/uploads
ln -sfn "$DATA_DIR/uploads" /var/www/html/public/uploads

if [ ! -d "$DATA_DIR/mysql/mysql" ]; then
    echo "[render] Initialisation du datadir MariaDB..."
    mariadb-install-db --user=mysql --datadir="$DATA_DIR/mysql" >/dev/null
fi

echo "[render] Démarrage de MariaDB..."
mysqld_safe --datadir="$DATA_DIR/mysql" --bind-address=127.0.0.1 &

until mysqladmin ping --silent 2>/dev/null; do
    sleep 1
done
echo "[render] MariaDB disponible."

mysql -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

MARIADB_VERSION="$(mysql -uroot -N -e 'SELECT VERSION();')"
DATABASE_URL="${DATABASE_URL:-mysql://$DB_USER:$DB_PASSWORD@127.0.0.1:3306/$DB_NAME?serverVersion=$MARIADB_VERSION&charset=utf8mb4}"
DEFAULT_URI="${DEFAULT_URI:-${RENDER_EXTERNAL_URL:-http://localhost}}"

# Fichier d'env Symfony : garantit que mod_php voit la config quel que soit le SAPI
cat > /var/www/html/.env.local <<EOF
APP_ENV=${APP_ENV:-prod}
APP_SECRET=${APP_SECRET:?La variable APP_SECRET doit être définie}
DATABASE_URL=${DATABASE_URL}
DEFAULT_URI=${DEFAULT_URI}
LOCK_DSN=flock
EOF
chown www-data:www-data /var/www/html/.env.local

if [ "${FORCE_DB_INIT:-false}" = "true" ]; then
    echo "[render] FORCE_DB_INIT=true -> réinitialisation complète du schéma + données factices."
    php bin/console app:init:database --with-data --force --no-interaction
else
    echo "[render] Application des migrations Doctrine (sans réinitialisation)."
    php bin/console doctrine:migrations:migrate --no-interaction
fi

php bin/console cache:clear --no-interaction
php bin/console assets:install public --no-interaction || true
chown -R www-data:www-data var

echo "[render] Configuration d'Apache sur le port ${PORT}..."
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\*:80/*:${PORT}/" /etc/apache2/sites-available/000-default.conf

echo "[render] Démarrage: $*"
exec "$@"
