#!/bin/sh
set -e

echo "Starting Hnefatafl API Entrypoint..."

# 1. Installer les dépendances Composer si absentes ou mettre à jour
echo "Checking composer dependencies..."
composer install --no-interaction --optimize-autoloader

# 2. Générer les clés JWT si elles n'existent pas
echo "Checking JWT keys..."
php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction

# 3. Attendre que la base de données soit prête
echo "Waiting for database connection..."
until php bin/console doctrine:database:create --if-not-exists --no-interaction; do
  echo "Database is not ready yet. Retrying in 2 seconds..."
  sleep 2
done
echo "Database exists and is accessible!"

# 4. Mettre à jour le schéma de la base de données à partir des entités
echo "Updating database schema from entities..."
php bin/console doctrine:schema:update --force --no-interaction

# 5. Enregistrer les migrations comme exécutées pour éviter des conflits futurs
echo "Marking migrations as migrated..."
php bin/console doctrine:migrations:version --add --all --no-interaction

# 6. Charger les fixtures
echo "Loading fixtures..."
php bin/console doctrine:fixtures:load --no-interaction

# 7. Lancer la commande principale du conteneur (php-fpm)
echo "Starting PHP-FPM..."
exec "$@"
