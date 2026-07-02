#!/bin/bash

set -e

echo "🚀 Initialisation du projet MTNPDV..."
echo ""

# Couleurs pour l'output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Vérifier que PHP est installé
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé. Veuillez installer PHP 8.4+"
    exit 1
fi

# Vérifier que Composer est installé
if ! command -v composer &> /dev/null; then
    echo "❌ Composer n'est pas installé."
    echo "Téléchargement de Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Vérifier que Node.js/npm est installé
if ! command -v npm &> /dev/null; then
    echo "⚠️  npm n'est pas installé. Paquets npm ne seront pas installés."
    HAS_NPM=0
else
    HAS_NPM=1
fi

echo -e "${YELLOW}1️⃣  Installation des dépendances PHP (Composer)...${NC}"
composer install --optimize-autoloader

if [ "$HAS_NPM" -eq 1 ]; then
    echo -e "${YELLOW}2️⃣  Installation des dépendances npm...${NC}"
    npm install
else
    echo "⚠️  npm non disponible - dépendances npm non installées"
fi

echo -e "${YELLOW}3️⃣  Configuration de l'application...${NC}"

# Créer les fichiers .env s'ils n'existent pas
if [ ! -f .env.local ]; then
    echo "Création de .env.local..."
    cp .env .env.local
    # Générer une clé APP_SECRET aléatoire
    php -r 'echo "APP_SECRET=" . bin2hex(random_bytes(16)) . "\n";' >> .env.local
fi

# Vérifier si .env.test existe
if [ ! -f .env.test ]; then
    echo "Création de .env.test..."
    cat > .env.test << 'ENVTEST'
APP_ENV=test
APP_DEBUG=1
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
ENVTEST
fi

echo -e "${YELLOW}4️⃣  Création de la base de données...${NC}"
php bin/console doctrine:database:create --if-not-exists 2>/dev/null || true
php bin/console doctrine:migrations:migrate --no-interaction

echo -e "${YELLOW}5️⃣  Création des dossiers de uploads...${NC}"
mkdir -p public/uploads/{profils,preuves,pos}
chmod -R 755 public/uploads

echo -e "${YELLOW}6️⃣  Construction des assets (si webpack-encore est installé)...${NC}"
npm run build 2>/dev/null || true

echo ""
echo -e "${GREEN}✅ Installation terminée avec succès!${NC}"
echo ""
echo "📝 Prochaines étapes:"
echo "1. Créer le premier utilisateur admin:"
echo "   php bin/console app:utilisateur:creer"
echo ""
echo "2. Lancer le serveur de développement:"
echo "   symfony serve:start"
echo ""
echo "3. Accéder à l'application:"
echo "   http://localhost:8000"
echo ""
