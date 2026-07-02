#!/bin/bash

# Script pour copier les dépendances npm vers le dossier public/vendor
# Améliore les performances en utilisant les assets locaux au lieu des CDN

set -e

echo "📦 Copie des dépendances vendor..."

# Créer les répertoires
mkdir -p public/vendor/bootstrap/css public/vendor/bootstrap/js
mkdir -p public/vendor/fontawesome/css public/vendor/fontawesome/fonts
mkdir -p public/vendor/leaflet/css public/vendor/leaflet/js

# Bootstrap
echo "📦 Bootstrap..."
cp node_modules/bootstrap/dist/css/bootstrap.min.css public/vendor/bootstrap/css/
cp node_modules/bootstrap/dist/js/bootstrap.bundle.min.js public/vendor/bootstrap/js/

# Font Awesome
echo "📦 Font Awesome..."
cp node_modules/@fortawesome/fontawesome-free/css/all.min.css public/vendor/fontawesome/css/
cp -r node_modules/@fortawesome/fontawesome-free/webfonts/* public/vendor/fontawesome/fonts/

# Leaflet
echo "📦 Leaflet..."
cp node_modules/leaflet/dist/leaflet.css public/vendor/leaflet/css/
cp node_modules/leaflet/dist/leaflet.js public/vendor/leaflet/js/
mkdir -p public/vendor/leaflet/css/images
cp -r node_modules/leaflet/dist/images/* public/vendor/leaflet/css/images/

# Statistiques
echo ""
echo "✅ Copie complète!"
echo ""
du -sh public/vendor/
echo ""
echo "📊 Fichiers copiés:"
echo "  - Bootstrap CSS: $(du -h public/vendor/bootstrap/css/ | cut -f1)"
echo "  - Bootstrap JS: $(du -h public/vendor/bootstrap/js/ | cut -f1)"
echo "  - Font Awesome: $(du -h public/vendor/fontawesome/ | cut -f1)"
echo "  - Leaflet: $(du -h public/vendor/leaflet/ | cut -f1)"
echo ""
echo "🚀 Les assets locaux sont maintenant prêts à être utilisés!"
echo "   Les performances de chargement sont améliorées (pas de requêtes CDN)"
