#!/bin/sh
set -e

DATABASE_HOST="${DATABASE_HOST:-mysql}"
DATABASE_PORT="${DATABASE_PORT:-3306}"

echo "[entrypoint] Attente de MySQL sur ${DATABASE_HOST}:${DATABASE_PORT}..."
until php -r "exit(@fsockopen('${DATABASE_HOST}', ${DATABASE_PORT}) ? 0 : 1);" 2>/dev/null; do
    sleep 1
done
echo "[entrypoint] MySQL disponible."

if [ "${FORCE_DB_INIT:-false}" = "true" ]; then
    echo "[entrypoint] FORCE_DB_INIT=true -> réinitialisation complète du schéma + données factices."
    php bin/console app:init:database --with-data --force --no-interaction
else
    echo "[entrypoint] Application des migrations Doctrine (sans réinitialisation)."
    php bin/console doctrine:migrations:migrate --no-interaction
fi

echo "[entrypoint] Démarrage: $*"
exec "$@"
