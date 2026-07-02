#!/bin/bash

# Script d'optimisation des images
# Utilise ImageMagick et autres outils pour compresser et convertir les images

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🖼️  Optimisation des images MTNPDV${NC}"
echo "================================================"

# Configuration
PUBLIC_DIR="./public"
IMAGES_DIR="$PUBLIC_DIR/images"
MAX_WIDTH=1920
QUALITY=85
WEBP_QUALITY=80

# Créer répertoire si nécessaire
mkdir -p "$IMAGES_DIR"

# Vérifier les dépendances
check_dependencies() {
    echo -e "\n📋 Vérification des dépendances..."

    if command -v convert &> /dev/null; then
        echo -e "${GREEN}✓${NC} ImageMagick trouvé"
    else
        echo -e "${YELLOW}⚠${NC}  ImageMagick non trouvé (apt install imagemagick)"
    fi

    if command -v cwebp &> /dev/null; then
        echo -e "${GREEN}✓${NC} WebP support trouvé"
    else
        echo -e "${YELLOW}⚠${NC}  WebP non trouvé (apt install webp)"
    fi

    if command -v jpegoptim &> /dev/null; then
        echo -e "${GREEN}✓${NC} jpegoptim trouvé"
    else
        echo -e "${YELLOW}⚠${NC}  jpegoptim non trouvé (apt install jpegoptim)"
    fi

    if command -v optipng &> /dev/null; then
        echo -e "${GREEN}✓${NC} optipng trouvé"
    else
        echo -e "${YELLOW}⚠${NC}  optipng non trouvé (apt install optipng)"
    fi
}

# Optimiser les JPG
optimize_jpg() {
    echo -e "\n${YELLOW}JPG${NC} - Optimisation en cours..."

    if command -v jpegoptim &> /dev/null; then
        find "$IMAGES_DIR" -type f \( -iname "*.jpg" -o -iname "*.jpeg" \) | while read file; do
            echo "  Optimise: $(basename "$file")"
            jpegoptim --max=$QUALITY --overwrite "$file" 2>/dev/null || true
        done
    else
        echo "  ⚠️  jpegoptim non installé, utilise ImageMagick..."
        find "$IMAGES_DIR" -type f \( -iname "*.jpg" -o -iname "*.jpeg" \) | while read file; do
            echo "  Optimise: $(basename "$file")"
            convert "$file" -quality $QUALITY "$file"
        done
    fi
}

# Optimiser les PNG
optimize_png() {
    echo -e "\n${YELLOW}PNG${NC} - Optimisation en cours..."

    if command -v optipng &> /dev/null; then
        find "$IMAGES_DIR" -type f -iname "*.png" | while read file; do
            echo "  Optimise: $(basename "$file")"
            optipng -o2 "$file" 2>/dev/null || true
        done
    else
        echo "  ⚠️  optipng non installé"
    fi
}

# Convertir en WebP
convert_to_webp() {
    echo -e "\n${YELLOW}WebP${NC} - Conversion en cours..."

    if command -v cwebp &> /dev/null; then
        find "$IMAGES_DIR" -type f \( -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.png" \) | while read file; do
            webp_file="${file%.*}.webp"
            echo "  Convertit: $(basename "$file") → $(basename "$webp_file")"
            cwebp -q $WEBP_QUALITY "$file" -o "$webp_file"
        done
    else
        echo "  ⚠️  WebP non disponible"
    fi
}

# Redimensionner les grandes images
resize_large_images() {
    echo -e "\n${YELLOW}Redimensionnement${NC} des images > 1920px..."

    find "$IMAGES_DIR" -type f \( -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.png" \) | while read file; do
        width=$(identify -format "%w" "$file" 2>/dev/null || echo 0)
        if [ $width -gt $MAX_WIDTH ]; then
            echo "  Redimensionne: $(basename "$file") ($width → $MAX_WIDTH)"
            convert "$file" -resize ${MAX_WIDTH}x "$file"
        fi
    done
}

# Générer rapport statistiques
generate_report() {
    echo -e "\n${YELLOW}📊 Rapport d'optimisation${NC}"
    echo "================================================"

    TOTAL_SIZE=$(du -sh "$IMAGES_DIR" 2>/dev/null | cut -f1)
    JPG_COUNT=$(find "$IMAGES_DIR" -type f \( -iname "*.jpg" -o -iname "*.jpeg" \) | wc -l)
    PNG_COUNT=$(find "$IMAGES_DIR" -type f -iname "*.png" | wc -l)
    WEBP_COUNT=$(find "$IMAGES_DIR" -type f -iname "*.webp" | wc -l)

    echo "Dossier: $IMAGES_DIR"
    echo "Taille totale: $TOTAL_SIZE"
    echo "Images JPG: $JPG_COUNT"
    echo "Images PNG: $PNG_COUNT"
    echo "Images WebP: $WEBP_COUNT"
}

# Menu principal
main() {
    check_dependencies

    echo -e "\n${YELLOW}Sélectionner les optimisations à appliquer:${NC}"
    echo "1) Optimiser JPG"
    echo "2) Optimiser PNG"
    echo "3) Convertir en WebP"
    echo "4) Redimensionner les grandes images"
    echo "5) Tous (1-4)"
    echo "6) Rapport seulement"
    echo "0) Quitter"

    read -p "Choix (0-6): " choice

    case $choice in
        1) optimize_jpg ;;
        2) optimize_png ;;
        3) convert_to_webp ;;
        4) resize_large_images ;;
        5)
            optimize_jpg
            optimize_png
            convert_to_webp
            resize_large_images
            ;;
        6) ;;
        0) exit 0 ;;
        *) echo "Choix invalide"; exit 1 ;;
    esac

    generate_report
    echo -e "\n${GREEN}✓ Optimisation complète!${NC}\n"
}

main "$@"
