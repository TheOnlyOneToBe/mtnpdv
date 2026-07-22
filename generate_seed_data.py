#!/usr/bin/env python3
"""
Script de génération de données SQL pour 6 mois d'activité
Avec gestion complète des flux : ventes, dépôts, retraits, alertes

Usage: python3 generate_seed_data.py
"""

import random
import hashlib
from datetime import datetime, timedelta
from decimal import Decimal
from typing import Dict, List, Tuple, Optional, Set, Any
import os

# ============================================================================
# CONFIGURATION
# ============================================================================

NB_MOIS = 2
NB_AGENTS = 10
NB_GERANTS = 15
NB_ADMINS = 3
NB_PDV = 35

# Seuils et montants
SEUIL_MIN_FLOTTE = 50000           # 50 000 FCFA
SEUIL_MIN_CASH = 100000            # 100 000 FCFA
MONTANT_APPROV_MIN = 50000
MONTANT_APPROV_MAX = 1000000
MONTANT_RETRAIT_MIN = 10000
MONTANT_RETRAIT_MAX = 200000

# Probabilités
PROB_DEUX_AGENTS = 0.6
PROB_VENTE_FLOTTE = 0.7
PROB_PAIEMENT_CASH = 0.3

# ============================================================================
# DONNÉES STATIQUES
# ============================================================================

STATUT_UTILISATEUR = ['ACTIF', 'INACTIF']
STATUT_PDV = ['ACTIF', 'INACTIF', 'SUSPENDU']
STATUT_FLUX = ['EN_ATTENTE', 'VALIDE', 'EXPEDIE', 'LIVRE', 'ANNULE']
STATUT_TRANSACTION = ['EN_ATTENTE', 'VALIDEE', 'REJETEE']
STATUT_DEMANDE = ['DEMANDEE', 'ASSIGNEE', 'ACCEPTEE', 'EFFECTUEE', 'VALIDEE', 'REJETEE', 'ANNULEE']
TYPE_DEMANDE = ['APPROVISIONNEMENT_FLOTTE', 'APPROVISIONNEMENT_ESPECE', 'SUPERVISION', 'MAINTENANCE']

# ============================================================================
# CORRECTION : Types de transaction correspondant à l'énumération PHP
# ============================================================================
# D'après App\Domain\Enum\TypeTransaction:
# - VENTE
# - RETOUR
# - ANNULATION
# - VISITE
# - DISTRIBUTION_CASH
# - APPROVISIONNEMENT_FLOTTE
# ============================================================================

# Mapping : type logique -> valeur enum PHP
TYPE_TRANSACTION_MAPPING = {
    # Ventes
    'VENTE_FLOTTE': 'VENTE',
    'VENTE_ESPECE': 'VENTE',
    # Dépôts / Approvisionnements
    'DEPOT_FLOTTE': 'APPROVISIONNEMENT_FLOTTE',
    'DEPOT_ESPECE': 'DISTRIBUTION_CASH',
    # Retraits (remplacé par VISITE ou autre selon le contexte)
    'RETRAIT': 'VISITE',  # Les retraits sont traités comme des visites de contrôle
}

# Types de transaction valides pour l'énumération PHP
TYPE_TRANSACTION_VALIDES = ['VENTE', 'RETOUR', 'ANNULATION', 'VISITE', 'DISTRIBUTION_CASH', 'APPROVISIONNEMENT_FLOTTE']

TYPE_NOTIF = [
    'VENTE_REALISEE', 'DEPOT_REALISE', 'RETRAIT_EFFECTUE',
    'SEUIL_ALERTE_FLOTTE', 'SEUIL_ALERTE_CASH',
    'APPROVISIONNEMENT_DEMANDE', 'APPROVISIONNEMENT_VALIDE',
    'PRODUIT_LIVRE', 'MESSAGE_ADMIN', 'ALERTE_SYSTEME',
    'ATTRIBUTION_PDV', 'RETRAIT_PDV'
]

LOCATIONS = [
    ('Douala', 'Akwa', 4.0511, 9.7679),
    ('Douala', 'Bonanjo', 4.0548, 9.7385),
    ('Douala', 'Bonabéri', 3.9894, 9.6908),
    ('Douala', 'Deido', 4.0189, 9.7428),
    ('Douala', 'Makepe', 4.0850, 9.7206),
    ('Douala', 'Bépanda', 4.0450, 9.7300),
    ('Douala', 'Ndogbong', 4.0620, 9.7550),
    ('Douala', 'New Bell', 4.0320, 9.7180),
    ('Douala', 'Bonamoussadi', 4.0700, 9.7450),
    ('Douala', 'Logbaba', 4.0200, 9.7200),
    ('Yaoundé', 'Bastos', 3.8667, 11.5167),
    ('Yaoundé', 'Biyem-Assi', 3.8420, 11.5420),
    ('Yaoundé', 'Mvog-Ada', 3.8380, 11.4920),
    ('Yaoundé', 'Nkolbisson', 3.8550, 11.4750),
    ('Yaoundé', 'Essos', 3.8920, 11.5520),
    ('Yaoundé', 'Mokolo', 3.8800, 11.5280),
    ('Yaoundé', 'Mfoundi', 3.8600, 11.5100),
    ('Yaoundé', 'Messa', 3.8790, 11.4980),
    ('Yaoundé', 'Obili', 3.8500, 11.5050),
    ('Yaoundé', 'Elig-Essono', 3.8750, 11.5200),
    ('Bafoussam', 'Centre', 5.4720, 10.4170),
    ('Bamenda', 'Nord-Ouest', 5.9590, 10.1520),
    ('Garoua', 'Nord', 9.3050, 13.3980),
    ('Maroua', 'Extrême-Nord', 10.5910, 14.3300),
    ('Ngaoundéré', 'Adamaoua', 7.3150, 13.5750),
    ('Bertoua', 'Est', 4.5770, 13.6900),
    ('Ebolowa', 'Sud', 2.9000, 11.1500),
    ('Kribi', 'Sud', 2.9370, 9.9100),
    ('Limbe', 'Sud-Ouest', 4.0240, 9.2060),
    ('Buea', 'Sud-Ouest', 4.1530, 9.2420),
    ('Dschang', 'Ouest', 5.4470, 10.0570),
    ('Foumban', 'Ouest', 5.7210, 10.9000),
    ('Kumba', 'Sud-Ouest', 4.6360, 9.4450),
    ('Mbouda', 'Ouest', 5.6280, 10.2540),
    ('Bafang', 'Ouest', 5.1570, 10.1760),
]

NOMS = [
    'Ndzi', 'Mbah', 'Tchoua', 'Dibango', 'Tamban', 'Tokoto', 'Ewondo', 'Ndom',
    'Mbarga', 'Ngo', 'Ondoa', 'Bekolo', 'Mbida', 'Nkolo', 'Mballa', 'Nyamsi',
    'Tchoffo', 'Mvondo', 'Ayissi', 'Mengue', 'Zang', 'Ngouana', 'Kouam', 'Fotso',
    'Kamdem', 'Nana', 'Tchoumi', 'Mbia', 'Eballe', 'Manga', 'Ndoumbe', 'Tchinda',
    'Djoumessi', 'Kouafo', 'Ngansop', 'Tchaptchet', 'Kamga', 'Wouafo'
]

PRENOMS = [
    'Jean', 'Paul', 'Marie', 'Sophie', 'Hervé', 'Grace', 'Pascal', 'Mireille',
    'François', 'Anne', 'Joseph', 'Catherine', 'Michel', 'Claire', 'André',
    'Rose', 'Daniel', 'Louise', 'David', 'Martine', 'Éric', 'Jeanne', 'Marc',
    'Hélène', 'Patrick', 'Madeleine', 'Pierre', 'Marie-Claire', 'Georges',
    'Thérèse', 'Roger', 'Simone', 'Maurice', 'Juliette', 'Emmanuel', 'Agnès'
]

NOMS_PDV = [
    'Kiosque {quartier}',
    '{quartier} Express',
    'MTN {quartier}',
    'Point Vente {quartier}',
    'Boutique {quartier} MTN',
    'Agence {quartier}',
    'Supermarché {quartier}',
    'Relais {quartier}',
    'Centre {quartier}',
    'Market {quartier}',
    'Phone {quartier}',
    'Digital {quartier}'
]

CATEGORIES_PDV = [
    'Kiosque MTN',
    'Agence MTN',
    'Boutique Partenaire',
    'Revendeur Agréé',
    'Supermarché Partenaire'
]

PRODUITS = [
    {'nom': 'Flotte 100 FCFA', 'prix': 100, 'categorie': 'Flotte'},
    {'nom': 'Flotte 500 FCFA', 'prix': 500, 'categorie': 'Flotte'},
    {'nom': 'Flotte 1000 FCFA', 'prix': 1000, 'categorie': 'Flotte'},
    {'nom': 'Flotte 2500 FCFA', 'prix': 2500, 'categorie': 'Flotte'},
    {'nom': 'Flotte 5000 FCFA', 'prix': 5000, 'categorie': 'Flotte'},
    {'nom': 'Flotte 10000 FCFA', 'prix': 10000, 'categorie': 'Flotte'},
    {'nom': 'Flotte 25000 FCFA', 'prix': 25000, 'categorie': 'Flotte'},
    {'nom': 'Flotte 50000 FCFA', 'prix': 50000, 'categorie': 'Flotte'},
    {'nom': 'Espèce 1000 FCFA', 'prix': 1000, 'categorie': 'Espèce'},
    {'nom': 'Espèce 5000 FCFA', 'prix': 5000, 'categorie': 'Espèce'},
    {'nom': 'Espèce 10000 FCFA', 'prix': 10000, 'categorie': 'Espèce'},
    {'nom': 'Espèce 25000 FCFA', 'prix': 25000, 'categorie': 'Espèce'},
    {'nom': 'Espèce 50000 FCFA', 'prix': 50000, 'categorie': 'Espèce'},
]

COMMENTAIRES = {
    'VENTE': [
        'Vente de recharge effectuée',
        'Client satisfait du service',
        'Transaction rapide',
        'Paiement en espèces',
        'Paiement par MoMo'
    ],
    'DEPOT': [
        'Approvisionnement en flotte',
        'Réapprovisionnement en cash',
        'Fonds supplémentaires',
        'Livraison reçue'
    ],
    'RETRAIT': [
        'Retrait de fonds par le gérant',
        'Fonds pour les opérations',
        'Retrait pour dépenses'
    ],
    'PROBLEME': [
        'Rupture de stock',
        'Problème de connexion',
        'Terminal défectueux',
        'Absence du gérant',
        'Fermeture exceptionnelle'
    ]
}


# ============================================================================
# GÉNÉRATEUR PRINCIPAL
# ============================================================================

class SQLGenerator:
    def __init__(self, start_date: datetime, end_date: datetime):
        self.start_date = start_date
        self.end_date = end_date
        self.sql_lines = []
        
        # Compteurs d'IDs
        self.ids = {
            'utilisateur': 1,
            'role': 1,
            'categorie_pdv': 1,
            'categorie_prod': 1,
            'point_vente': 1,
            'produit': 1,
            'attribution_pdv': 1,
            'flux_ravitaillement': 1,
            'flux_produit': 1,
            'transaction': 1,
            'demande_visite': 1,
            'notification': 1,
        }
        
        self.id_maps = {
            'utilisateur': {},
            'role': {},
            'categorie_pdv': {},
            'categorie_prod': {},
            'point_vente': {},
            'produit': {},
        }
        
        # Stockage des données en mémoire
        self.users = []
        self.agents = []
        self.gerants = []
        self.admins = []
        self.pdvs = []
        self.produits = {}
        self.pdv_agents = {}          # {pdv_id: [agent_id, ...]}
        self.pdv_attributions = {}    # {pdv_id: {agent_id: date_attribution}}
        self.pdv_soldes = {}          # {pdv_id: {'flotte': float, 'cash': float}}
        self.pdv_seuils = {}          # {pdv_id: {'flotte': float, 'cash': float}}
        self.pdv_dates = {}           # {pdv_id: date_creation}
        
        # Statistiques
        self.stats = {
            'flux_crees': 0,
            'transactions_crees': 0,
            'demandes_crees': 0,
            'notifications_crees': 0,
            'alertes_seuil': 0,
            'ventes': 0,
            'depots': 0,
            'retraits': 0,
            'total_ventes': 0,
            'total_depots': 0,
            'total_retraits': 0
        }

    # ------------------------------------------------------------------------
    # Méthodes utilitaires
    # ------------------------------------------------------------------------

    def random_date(self, start: datetime, end: datetime) -> datetime:
        """Génère une date aléatoire entre start et end"""
        if start >= end:
            return start
        delta = end - start
        random_days = random.randint(0, delta.days)
        random_seconds = random.randint(0, 86399)
        return start + timedelta(days=random_days, seconds=random_seconds)

    def random_datetime(self, start: datetime, end: datetime) -> str:
        """Génère une datetime aléatoire au format MySQL"""
        dt = self.random_date(start, end)
        return dt.strftime('%Y-%m-%d %H:%M:%S')

    def random_phone(self) -> str:
        """Génère un numéro de téléphone camerounais"""
        return f"+237{random.randint(6, 7)}{random.randint(100000, 999999)}"

    def random_email(self, nom: str, prenom: str) -> str:
        """Génère un email"""
        domains = ['gmail.com', 'yahoo.fr', 'outlook.com', 'yandex.com', 'tinglobal.cm']
        nom_clean = nom.lower().replace(' ', '').replace("'", '').replace('-', '')
        prenom_clean = prenom.lower().replace(' ', '').replace("'", '').replace('-', '')
        return f"{nom_clean}.{prenom_clean}@{random.choice(domains)}"

    def random_coords(self, base_lat: float, base_lng: float, radius_km: float = 0.1) -> Tuple[float, float]:
        """Génère des coordonnées proches d'une position de base"""
        lat_offset = (random.uniform(-radius_km, radius_km)) / 111.0
        lng_offset = (random.uniform(-radius_km, radius_km)) / (111.0 * max(abs(base_lat), 0.1) / 90.0)
        return (base_lat + lat_offset, base_lng + lng_offset)

    def hash_password(self, password: str = 'password123') -> str:
        """Génère un hash bcrypt simulé"""
        salt = hashlib.md5(b'salt').hexdigest()[:22]
        return f"$2y$13${salt}{hashlib.md5(password.encode()).hexdigest()}"

    def sanitize_sql_value(self, value: Any) -> str:
        """Sanitize une valeur pour SQL"""
        if value is None:
            return 'NULL'
        if isinstance(value, str):
            return f"'{value.replace("'", "''")}'"
        if isinstance(value, bool):
            return '1' if value else '0'
        return str(value)

    def add_sql(self, sql: str):
        """Ajoute une ligne SQL"""
        self.sql_lines.append(sql)

    def get_current_solde(self, pdv_id: int, type_solde: str) -> float:
        """Retourne le solde actuel d'un PDV"""
        if pdv_id in self.pdv_soldes:
            return self.pdv_soldes[pdv_id].get(type_solde, 0)
        return 0

    def update_solde(self, pdv_id: int, type_solde: str, montant: float, operation: str = '+'):
        """Met à jour le solde d'un PDV"""
        if pdv_id not in self.pdv_soldes:
            return
        
        if operation == '+':
            self.pdv_soldes[pdv_id][type_solde] += montant
        elif operation == '-':
            self.pdv_soldes[pdv_id][type_solde] -= montant
            
        # S'assurer que le solde n'est pas négatif
        if self.pdv_soldes[pdv_id][type_solde] < 0:
            self.pdv_soldes[pdv_id][type_solde] = 0

    # ========================================================================
    # NOUVELLE MÉTHODE : Mapper les types de transaction
    # ========================================================================
    
    def map_type_transaction(self, type_logique: str) -> str:
        """
        Convertit un type logique en valeur valide pour l'énumération PHP
        """
        if type_logique in TYPE_TRANSACTION_MAPPING:
            return TYPE_TRANSACTION_MAPPING[type_logique]
        # Si le type est déjà valide, le retourner
        if type_logique in TYPE_TRANSACTION_VALIDES:
            return type_logique
        # Fallback
        print(f"⚠️ Type de transaction non reconnu: {type_logique}, utilisation de 'VENTE'")
        return 'VENTE'

    # ------------------------------------------------------------------------
    # PHASE 1: RÔLES
    # ------------------------------------------------------------------------

    def generate_roles(self):
        """Génère les rôles utilisateur"""
        self.add_sql("-- ============================================================")
        self.add_sql("-- 1. RÔLES")
        self.add_sql("-- ============================================================")
        
        roles = [
            ('ADMIN', 'Administrateur du systeme'),
            ('AGENT', 'Agent Terrain'),
            ('GERANT', 'Gérant de PDV')
        ]
        
        for code, libelle in roles:
            sql = f"INSERT INTO `role` (`id`, `code_role`, `libelle`) VALUES ({self.ids['role']}, '{code}', '{libelle}');"
            self.add_sql(sql)
            self.id_maps['role'][code] = self.ids['role']
            self.ids['role'] += 1
        
        self.add_sql("")

    # ------------------------------------------------------------------------
    # PHASE 2: CATÉGORIES
    # ------------------------------------------------------------------------

    def generate_categories(self):
        """Génère les catégories de PDV et de produits"""
        self.add_sql("-- ============================================================")
        self.add_sql("-- 2. CATÉGORIES")
        self.add_sql("-- ============================================================")
        
        # Catégories PDV
        for libelle in CATEGORIES_PDV:
            sql = f"INSERT INTO `categorie_pdv` (`id`, `libelle_catpdv`) VALUES ({self.ids['categorie_pdv']}, '{libelle}');"
            self.add_sql(sql)
            self.id_maps['categorie_pdv'][libelle] = self.ids['categorie_pdv']
            self.ids['categorie_pdv'] += 1
        
        # Catégories Produits
        categories_prod = ['Flotte', 'Espèce']
        for libelle in categories_prod:
            sql = f"INSERT INTO `categorie_prod` (`id`, `libelle`, `type_cat`) VALUES ({self.ids['categorie_prod']}, '{libelle}', 'SERVICE');"
            self.add_sql(sql)
            self.id_maps['categorie_prod'][libelle] = self.ids['categorie_prod']
            self.ids['categorie_prod'] += 1
        
        self.add_sql("")

    # ------------------------------------------------------------------------
    # PHASE 3: PRODUITS
    # ------------------------------------------------------------------------

    def generate_produits(self):
        """Génère les produits"""
        self.add_sql("-- ============================================================")
        self.add_sql("-- 3. PRODUITS")
        self.add_sql("-- ============================================================")
        
        for produit in PRODUITS:
            cat_id = self.id_maps['categorie_prod'][produit['categorie']]
            
            sql = (f"INSERT INTO `produit` (`id`, `nom_prod`, `type_pro`, `prix_unitaire`, `statut_prod`, "
                   f"`code_barre`, `categorie_id`) VALUES "
                   f"({self.ids['produit']}, '{produit['nom']}', 'Produit MTN Cameroon', {produit['prix']}, 1, NULL, {cat_id});")
            self.add_sql(sql)
            
            self.id_maps['produit'][produit['nom']] = self.ids['produit']
            self.produits[produit['nom']] = {
                'id': self.ids['produit'],
                'prix': produit['prix'],
                'categorie': produit['categorie']
            }
            self.ids['produit'] += 1
        
        self.add_sql("")

    # ------------------------------------------------------------------------
    # PHASE 4: UTILISATEURS
    # ------------------------------------------------------------------------

    def generate_utilisateurs(self):
        """Génère les utilisateurs (ADMIN, AGENT, GERANT)"""
        self.add_sql("-- ============================================================")
        self.add_sql("-- 4. UTILISATEURS")
        self.add_sql("-- ============================================================")
        
        random.shuffle(NOMS)
        random.shuffle(PRENOMS)
        
        # --- Administrateurs ---
        admin_role_id = self.id_maps['role']['ADMIN']
        for i in range(NB_ADMINS):
            nom = NOMS[i % len(NOMS)]
            prenom = PRENOMS[i % len(PRENOMS)]
            email = self.random_email(nom, prenom)
            telephone = self.random_phone()
            date_creation = self.random_datetime(self.start_date, self.start_date + timedelta(days=15))
            
            sql = (f"INSERT INTO `utilisateur` (`id`, `nom_ut`, `prenom_ut`, `email`, `mot_pass`, `telephone`, "
                   f"`date_creation`, `statut`, `photo_profil_url`, `date_photo_update`) VALUES "
                   f"({self.ids['utilisateur']}, '{nom}', '{prenom}', '{email}', "
                   f"'{self.hash_password()}', '{telephone}', '{date_creation}', 1, NULL, NULL);")
            self.add_sql(sql)
            
            sql_role = (f"INSERT INTO `utilisateur_role` (`utilisateur_id`, `role_id`) VALUES "
                       f"({self.ids['utilisateur']}, {admin_role_id});")
            self.add_sql(sql_role)
            
            self.id_maps['utilisateur'][email] = self.ids['utilisateur']
            self.admins.append(self.ids['utilisateur'])
            self.users.append(self.ids['utilisateur'])
            self.ids['utilisateur'] += 1
        
        # --- Agents ---
        agent_role_id = self.id_maps['role']['AGENT']
        for i in range(NB_AGENTS):
            nom = NOMS[(i + NB_ADMINS) % len(NOMS)]
            prenom = PRENOMS[(i + NB_ADMINS) % len(PRENOMS)]
            email = self.random_email(nom, prenom)
            telephone = self.random_phone()
            date_creation = self.random_datetime(self.start_date, self.start_date + timedelta(days=30))
            
            sql = (f"INSERT INTO `utilisateur` (`id`, `nom_ut`, `prenom_ut`, `email`, `mot_pass`, `telephone`, "
                   f"`date_creation`, `statut`, `photo_profil_url`, `date_photo_update`) VALUES "
                   f"({self.ids['utilisateur']}, '{nom}', '{prenom}', '{email}', "
                   f"'{self.hash_password()}', '{telephone}', '{date_creation}', 1, NULL, NULL);")
            self.add_sql(sql)
            
            sql_role = (f"INSERT INTO `utilisateur_role` (`utilisateur_id`, `role_id`) VALUES "
                       f"({self.ids['utilisateur']}, {agent_role_id});")
            self.add_sql(sql_role)
            
            self.id_maps['utilisateur'][email] = self.ids['utilisateur']
            self.agents.append(self.ids['utilisateur'])
            self.users.append(self.ids['utilisateur'])
            self.ids['utilisateur'] += 1
        
        # --- Gérants ---
        gerant_role_id = self.id_maps['role']['GERANT']
        for i in range(NB_GERANTS):
            nom = NOMS[(i + NB_ADMINS + NB_AGENTS) % len(NOMS)]
            prenom = PRENOMS[(i + NB_ADMINS + NB_AGENTS) % len(PRENOMS)]
            email = self.random_email(nom, prenom)
            telephone = self.random_phone()
            date_creation = self.random_datetime(self.start_date, self.start_date + timedelta(days=45))
            
            sql = (f"INSERT INTO `utilisateur` (`id`, `nom_ut`, `prenom_ut`, `email`, `mot_pass`, `telephone`, "
                   f"`date_creation`, `statut`, `photo_profil_url`, `date_photo_update`) VALUES "
                   f"({self.ids['utilisateur']}, '{nom}', '{prenom}', '{email}', "
                   f"'{self.hash_password()}', '{telephone}', '{date_creation}', 1, NULL, NULL);")
            self.add_sql(sql)
            
            sql_role = (f"INSERT INTO `utilisateur_role` (`utilisateur_id`, `role_id`) VALUES "
                       f"({self.ids['utilisateur']}, {gerant_role_id});")
            self.add_sql(sql_role)
            
            self.id_maps['utilisateur'][email] = self.ids['utilisateur']
            self.gerants.append(self.ids['utilisateur'])
            self.users.append(self.ids['utilisateur'])
            self.ids['utilisateur'] += 1
        
        self.add_sql("")

    # ------------------------------------------------------------------------
    # PHASE 5: POINTS DE VENTE
    # ------------------------------------------------------------------------

    def generate_points_vente(self):
        """Génère les points de vente avec leurs gérants et soldes initiaux"""
        self.add_sql("-- ============================================================")
        self.add_sql("-- 5. POINTS DE VENTE")
        self.add_sql("-- ============================================================")
        
        selected_locations = random.sample(LOCATIONS, min(NB_PDV, len(LOCATIONS)))
        gerants_available = self.gerants.copy()
        random.shuffle(gerants_available)
        
        for idx, (ville, quartier, base_lat, base_lng) in enumerate(selected_locations):
            gerant_id = gerants_available[idx % len(gerants_available)]
            cat_pdv = random.choice(CATEGORIES_PDV)
            cat_pdv_id = self.id_maps['categorie_pdv'][cat_pdv]
            
            nom_pdv = random.choice(NOMS_PDV).format(quartier=quartier)
            code_ref = f"PDV-{self.ids['point_vente']:03d}"
            
            lat, lng = self.random_coords(base_lat, base_lng, 0.05)
            date_creation = self.random_datetime(self.start_date, self.start_date + timedelta(days=60))
            telephone = self.random_phone()
            
            # Statut: 70% ACTIF, 15% INACTIF, 15% SUSPENDU
            statut = random.choices(STATUT_PDV, weights=[0.7, 0.15, 0.15], k=1)[0]
            
            # Soldes initiaux
            solde_flotte = random.randint(200000, 800000)
            solde_cash = random.randint(300000, 1000000)
            seuil_flotte = SEUIL_MIN_FLOTTE + random.randint(0, 25000)
            seuil_cash = SEUIL_MIN_CASH + random.randint(0, 50000)
            
            sql = (f"INSERT INTO `point_vente` (`id`, `nom_pdv`, `code_ref`, `ville`, `adresse`, "
                f"`date_creation`, `statut_actuel`, `telephone`, `solde_cash`, `solde_flotte`, "
                f"`seuil_min_cash`, `seuil_min_flotte`, `latitude`, `longitude`, "
                f"`categorie_pdv_id`, `gerant_id`) VALUES "
                f"({self.ids['point_vente']}, {self.sanitize_sql_value(nom_pdv)}, {self.sanitize_sql_value(code_ref)}, {self.sanitize_sql_value(ville)}, "
                f"{self.sanitize_sql_value(f'{quartier}, {ville}')}, {self.sanitize_sql_value(date_creation)}, {self.sanitize_sql_value(statut)}, {self.sanitize_sql_value(telephone)}, "
                f"{solde_cash:.2f}, {solde_flotte:.2f}, {seuil_cash:.2f}, {seuil_flotte:.2f}, "
                f"{lat:.8f}, {lng:.8f}, {cat_pdv_id}, {gerant_id});")
            self.add_sql(sql)
            
            pdv_id = self.ids['point_vente']
            self.id_maps['point_vente'][code_ref] = pdv_id
            
            self.pdvs.append({
                'id': pdv_id,
                'code_ref': code_ref,
                'nom': nom_pdv,
                'ville': ville,
                'quartier': quartier,
                'date_creation': date_creation,
                'gerant_id': gerant_id,
                'statut': statut,
                'lat': lat,
                'lng': lng
            })
            
            self.pdv_dates[pdv_id] = date_creation
            self.pdv_agents[pdv_id] = []
            self.pdv_attributions[pdv_id] = {}
            self.pdv_soldes[pdv_id] = {
                'flotte': solde_flotte,
                'cash': solde_cash
            }
            self.pdv_seuils[pdv_id] = {
                'flotte': seuil_flotte,
                'cash': seuil_cash
            }
            
            self.ids['point_vente'] += 1
        
        self.add_sql("")

    # ------------------------------------------------------------------------
    # PHASE 6: ATTRIBUTIONS DES AGENTS
    # ------------------------------------------------------------------------

    def generate_attributions(self):
        """Attribue 1 à 2 agents par PDV"""
        self.add_sql("-- ============================================================")
        self.add_sql("-- 6. ATTRIBUTIONS DES AGENTS")
        self.add_sql("-- ============================================================")
        
        agents_shuffled = self.agents.copy()
        random.shuffle(agents_shuffled)
        agent_idx = 0
        
        for pdv_info in self.pdvs:
            pdv_id = pdv_info['id']
            creation_date = datetime.strptime(pdv_info['date_creation'], '%Y-%m-%d %H:%M:%S')
            
            nb_agents = 1 if random.random() > PROB_DEUX_AGENTS else 2
            nb_agents = min(nb_agents, len(agents_shuffled))
            
            if nb_agents == 0:
                continue
            
            agents_for_pdv = []
            for _ in range(nb_agents):
                agent = agents_shuffled[agent_idx % len(agents_shuffled)]
                if agent not in agents_for_pdv:
                    agents_for_pdv.append(agent)
                agent_idx += 1
            
            date_attribution_end = min(creation_date + timedelta(days=30), self.end_date)
            
            for agent_id in agents_for_pdv:
                date_attribution = self.random_datetime(creation_date, date_attribution_end)
                
                sql = (f"INSERT INTO `attribution_pdv` (`id`, `date_attribution`, `date_retrait`, `actif`, "
                       f"`agent_id`, `point_vente_id`) VALUES "
                       f"({self.ids['attribution_pdv']}, '{date_attribution}', NULL, 1, {agent_id}, {pdv_id});")
                self.add_sql(sql)
                
                self.pdv_agents[pdv_id].append(agent_id)
                self.pdv_attributions[pdv_id][agent_id] = date_attribution
                self.ids['attribution_pdv'] += 1
        
        self.add_sql("")

    # ------------------------------------------------------------------------
    # PHASE 7: FLUX DE RAVITAILLEMENT
    # ------------------------------------------------------------------------

    def generate_flux(self):
        """Génère les flux de ravitaillement avec leurs produits"""
        self.add_sql("-- ============================================================")
        self.add_sql("-- 7. FLUX DE RAVITAILLEMENT")
        self.add_sql("-- ============================================================")
        
        flux_produit_id = 1
        produits_list = list(self.produits.values())
        produits_flotte = [p for p in produits_list if p['categorie'] == 'Flotte']
        
        for pdv_info in self.pdvs:
            pdv_id = pdv_info['id']
            pdv_date = datetime.strptime(pdv_info['date_creation'], '%Y-%m-%d %H:%M:%S')
            
            if pdv_id not in self.pdv_agents or not self.pdv_agents[pdv_id]:
                continue
            
            # 2 à 5 flux sur 6 mois
            nb_flux = random.randint(2, 5)
            
            for f in range(nb_flux):
                flux_date = self.random_date(
                    pdv_date + timedelta(days=f * 30),
                    min(pdv_date + timedelta(days=f * 30 + 30), self.end_date)
                )
                flux_date_str = flux_date.strftime('%Y-%m-%d %H:%M:%S')
                
                agent_id = random.choice(self.pdv_agents[pdv_id])
                statut = self.get_flux_statut(flux_date)
                facture_uniq = f"FLUX-PDV{pdv_id:03d}-{flux_date.strftime('%Y%m%d')}-{f}"
                
                # Sélection des produits
                nb_produits = random.randint(3, 6)
                selected_produits = random.sample(produits_flotte, min(nb_produits, len(produits_flotte)))
                
                montant_total = 0
                
                sql_flux = (f"INSERT INTO `flux_ravitaillement` (`id`, `facture_uniq`, `date_creation`, "
                     f"`montant_total`, `statut_flux`, `utilisateur_id`, `point_vente_id`) VALUES "
                     f"({self.ids['flux_ravitaillement']}, '{facture_uniq}', '{flux_date_str}', "
                     f"0.00, '{statut}', {agent_id}, {pdv_id});")
                self.add_sql(sql_flux)
                
                flux_id = self.ids['flux_ravitaillement']
                self.ids['flux_ravitaillement'] += 1
                
                for produit in selected_produits:
                    quantite = random.randint(10, 50)
                    prix_unitaire = produit['prix']
                    sous_total = quantite * prix_unitaire
                    montant_total += sous_total
                    
                    sql_produit = (f"INSERT INTO `flux_produit` (`id`, `quantite`, `sous_total`, "
                               f"`prix_unitaire_flux`, `flux_ravitaillement_id`, `produit_id`) VALUES "
                               f"({flux_produit_id}, {quantite}, {sous_total:.2f}, {prix_unitaire:.2f}, "
                               f"{flux_id}, {produit['id']});")
                    self.add_sql(sql_produit)
                    flux_produit_id += 1
                
                sql_update = f"UPDATE `flux_ravitaillement` SET `montant_total` = {montant_total:.2f} WHERE `id` = {flux_id};"
                self.add_sql(sql_update)
                
                if statut == 'LIVRE':
                    self.update_solde(pdv_id, 'flotte', montant_total, '+')
                    self.create_notification(
                        pdv_info['gerant_id'],
                        'PRODUIT_LIVRE',
                        f'Livraison reçue pour {pdv_info["nom"]}',
                        f'Un flux de {montant_total:,.0f} FCFA a été livré.',
                        flux_date_str
                    )
                    
                    if self.pdv_soldes[pdv_id]['flotte'] > self.pdv_soldes[pdv_id]['cash'] * 1.5:
                        self.create_notification(
                            pdv_info['gerant_id'],
                            'ALERTE_SYSTEME',
                            'Déséquilibre des soldes',
                            'Le solde flotte est beaucoup plus élevé que le solde cash.',
                            flux_date_str
                        )
                
                self.stats['flux_crees'] += 1
        
        self.add_sql("")

    def get_flux_statut(self, flux_date: datetime) -> str:
        """Détermine un statut de flux avec progression réaliste"""
        days_old = (self.end_date - flux_date).days
        
        if days_old < 15:
            return random.choices(STATUT_FLUX, weights=[0.6, 0.2, 0.1, 0.05, 0.05], k=1)[0]
        elif days_old < 30:
            return random.choices(STATUT_FLUX, weights=[0.2, 0.3, 0.25, 0.15, 0.1], k=1)[0]
        elif days_old < 60:
            return random.choices(STATUT_FLUX, weights=[0.05, 0.1, 0.15, 0.5, 0.2], k=1)[0]
        else:
            return random.choices(STATUT_FLUX, weights=[0, 0, 0.1, 0.7, 0.2], k=1)[0]

    # ------------------------------------------------------------------------
    # PHASE 8: VENTES
    # ------------------------------------------------------------------------

    def generate_ventes(self):
        """Génère les ventes quotidiennes pour chaque PDV"""
        self.add_sql("-- ============================================================")
        self.add_sql("-- 8. VENTES")
        self.add_sql("-- ============================================================")
        
        for pdv_info in self.pdvs:
            pdv_id = pdv_info['id']
            pdv_date = datetime.strptime(pdv_info['date_creation'], '%Y-%m-%d %H:%M:%S')
            
            if pdv_id not in self.pdv_agents or not self.pdv_agents[pdv_id]:
                continue
            
            # Ventes sur toute la période
            current_date = pdv_date
            while current_date <= self.end_date:
                # Nombre de ventes par jour (plus le week-end)
                is_weekend = current_date.weekday() >= 5
                nb_ventes = random.randint(3, 10) if is_weekend else random.randint(2, 6)
                
                for _ in range(nb_ventes):
                    # Créer une vente
                    self.create_vente(pdv_id, current_date, pdv_info)
                
                current_date += timedelta(days=1)
        
        self.add_sql("")

    def create_vente(self, pdv_id: int, date: datetime, pdv_info: dict):
        """Crée une transaction de vente"""
        # Type de vente (flotte ou cash)
        est_flotte = random.random() < PROB_VENTE_FLOTTE
        type_logique = 'VENTE_FLOTTE' if est_flotte else 'VENTE_ESPECE'
        type_solde = 'flotte' if est_flotte else 'cash'
        
        # ========================================================================
        # CORRECTION : Utiliser le mapping pour obtenir la bonne valeur enum
        # ========================================================================
        type_enum = self.map_type_transaction(type_logique)
        
        # Montant de la vente
        montant = self.generate_montant_vente()
        
        # Vérifier si solde suffisant
        solde_actuel = self.get_current_solde(pdv_id, type_solde)
        if solde_actuel < montant:
            # Vente refusée (solde insuffisant)
            return
        
        # Agent (un des attribués)
        agent_id = random.choice(self.pdv_agents[pdv_id])
        
        # Coordonnées
        lat, lng = self.random_coords(pdv_info['lat'], pdv_info['lng'], 0.02)
        
        # Commentaire
        commentaire = random.choice(COMMENTAIRES['VENTE'])
        
        # Statut (95% validée, 5% rejetée)
        statut = random.choices(STATUT_TRANSACTION, weights=[0.05, 0.95, 0], k=1)[0]
        
        date_str = date.strftime('%Y-%m-%d %H:%M:%S')
        
        sql = (f"INSERT INTO `transaction` (`id`, `date_transac`, `commentaire_rapport`, "
               f"`photo_preuve_url`, `latitude_capture`, `longitude_capture`, `type_enum`, "
               f"`statut`, `montant`, `type_probleme`, `point_vente_id`, `utilisateur_id`) VALUES "
               f"({self.ids['transaction']}, {self.sanitize_sql_value(date_str)}, {self.sanitize_sql_value(commentaire)}, "
               f"NULL, {lat:.8f}, {lng:.8f}, {self.sanitize_sql_value(type_enum)}, "
               f"{self.sanitize_sql_value(statut)}, {montant:.2f}, NULL, {pdv_id}, {agent_id});")
        self.add_sql(sql)
        
        self.ids['transaction'] += 1
        self.stats['ventes'] += 1
        self.stats['total_ventes'] += montant
        
        # Mettre à jour le solde
        if statut == 'VALIDEE':
            self.update_solde(pdv_id, type_solde, montant, '-')
            self.check_seuils(pdv_id, date_str)

    def generate_montant_vente(self) -> int:
        """Génère un montant de vente réaliste"""
        # Distribution: beaucoup de petites ventes, quelques grandes
        r = random.random()
        if r < 0.5:
            return random.randint(100, 1000)       # 50%: 100-1000 FCFA
        elif r < 0.8:
            return random.randint(1000, 5000)      # 30%: 1000-5000 FCFA
        elif r < 0.95:
            return random.randint(5000, 10000)     # 15%: 5000-10000 FCFA
        else:
            return random.randint(10000, 50000)    # 5%: 10000-50000 FCFA

    # ------------------------------------------------------------------------
    # PHASE 9: DÉPÔTS (APPROVISIONNEMENTS)
    # ------------------------------------------------------------------------

    def generate_depots(self):
        """Génère des dépôts (approvisionnements en cash)"""
        self.add_sql("-- ============================================================")
        self.add_sql("-- 9. DÉPÔTS")
        self.add_sql("-- ============================================================")
        
        for pdv_info in self.pdvs:
            pdv_id = pdv_info['id']
            pdv_date = datetime.strptime(pdv_info['date_creation'], '%Y-%m-%d %H:%M:%S')
            
            if pdv_id not in self.pdv_agents or not self.pdv_agents[pdv_id]:
                continue
            
            # 1 à 3 dépôts sur 6 mois
            nb_depots = random.randint(1, 3)
            
            for d in range(nb_depots):
                depot_date = self.random_date(
                    pdv_date + timedelta(days=d * 45 + 10),
                    min(pdv_date + timedelta(days=d * 45 + 50), self.end_date)
                )
                
                # Vérifier si le solde cash est bas
                solde_cash = self.get_current_solde(pdv_id, 'cash')
                seuil_cash = self.pdv_seuils[pdv_id]['cash']
                
                if solde_cash < seuil_cash * 2:
                    montant = random.randint(
                        int(seuil_cash * 1.5),
                        int(seuil_cash * 5)
                    )
                else:
                    montant = random.randint(MONTANT_APPROV_MIN, MONTANT_APPROV_MAX // 2)
                
                # Agent
                agent_id = random.choice(self.pdv_agents[pdv_id])
                
                # Commentaire
                commentaire = random.choice([
                    'Dépôt de cash pour approvisionnement',
                    'Réapprovisionnement en espèces',
                    'Fonds pour les opérations',
                    'Dépôt effectué par l\'agent'
                ])
                
                date_str = depot_date.strftime('%Y-%m-%d %H:%M:%S')
                
                # ========================================================================
                # CORRECTION : Utiliser DISTRIBUTION_CASH pour les dépôts en espèces
                # ========================================================================
                type_enum = self.map_type_transaction('DEPOT_ESPECE')
                
                sql = (f"INSERT INTO `transaction` (`id`, `date_transac`, `commentaire_rapport`, "
               f"`photo_preuve_url`, `latitude_capture`, `longitude_capture`, `type_enum`, "
               f"`statut`, `montant`, `type_probleme`, `point_vente_id`, `utilisateur_id`) VALUES "
               f"({self.ids['transaction']}, {self.sanitize_sql_value(date_str)}, {self.sanitize_sql_value(commentaire)}, "
               f"NULL, {pdv_info['lat']:.8f}, {pdv_info['lng']:.8f}, {self.sanitize_sql_value(type_enum)}, "
               f"{self.sanitize_sql_value('VALIDEE')}, {montant:.2f}, NULL, {pdv_id}, {agent_id});")
                self.add_sql(sql)
                
                self.ids['transaction'] += 1
                self.stats['depots'] += 1
                self.stats['total_depots'] += montant
                
                # Mettre à jour le solde
                self.update_solde(pdv_id, 'cash', montant, '+')
                self.check_seuils(pdv_id, date_str)
                
                # Notification
                self.create_notification(
                    pdv_info['gerant_id'],
                    'DEPOT_REALISE',
                    f'Dépôt de {montant:,.0f} FCFA',
                    f'Un dépôt de {montant:,.0f} FCFA a été effectué sur votre PDV.',
                    date_str
                )

    # ------------------------------------------------------------------------
    # PHASE 10: RETRAITS
    # ------------------------------------------------------------------------

    def generate_retraits(self):
        """
        Génère des retraits effectués par les gérants.
        Note: Le type 'RETRAIT' n'existe pas dans l'énumération PHP.
        On utilise 'VISITE' comme substitut pour les retraits de fonds.
        """
        self.add_sql("-- ============================================================")
        self.add_sql("-- 10. RETRAITS (enregistrés comme VISITES) ")
        self.add_sql("-- ============================================================")
        
        for pdv_info in self.pdvs:
            pdv_id = pdv_info['id']
            pdv_date = datetime.strptime(pdv_info['date_creation'], '%Y-%m-%d %H:%M:%S')
            
            # 1 à 3 retraits sur 6 mois
            nb_retraits = random.randint(1, 3)
            
            for r in range(nb_retraits):
                retrait_date = self.random_date(
                    pdv_date + timedelta(days=r * 50 + 20),
                    min(pdv_date + timedelta(days=r * 50 + 60), self.end_date)
                )
                
                # Montant du retrait (entre 10k et 200k)
                solde_cash = self.get_current_solde(pdv_id, 'cash')
                montant_max = min(solde_cash * 0.6, MONTANT_RETRAIT_MAX)
                
                if montant_max < MONTANT_RETRAIT_MIN:
                    continue
                
                montant = random.randint(MONTANT_RETRAIT_MIN, int(montant_max))
                
                # Commentaire
                commentaire = random.choice([
                    'Retrait de fonds pour le gérant',
                    'Fonds pour les dépenses du PDV',
                    'Retrait pour approvisionnement externe',
                    'Retrait effectué par le gérant'
                ])
                
                date_str = retrait_date.strftime('%Y-%m-%d %H:%M:%S')
                
                # ========================================================================
                # CORRECTION : Utiliser 'VISITE' au lieu de 'RETRAIT'
                # ========================================================================
                type_enum = self.map_type_transaction('RETRAIT')  # -> 'VISITE'
                
                sql = (f"INSERT INTO `transaction` (`id`, `date_transac`, `commentaire_rapport`, "
               f"`photo_preuve_url`, `latitude_capture`, `longitude_capture`, `type_enum`, "
               f"`statut`, `montant`, `type_probleme`, `point_vente_id`, `utilisateur_id`) VALUES "
               f"({self.ids['transaction']}, {self.sanitize_sql_value(date_str)}, {self.sanitize_sql_value(commentaire)}, "
               f"NULL, {pdv_info['lat']:.8f}, {pdv_info['lng']:.8f}, {self.sanitize_sql_value(type_enum)}, "
               f"{self.sanitize_sql_value('VALIDEE')}, {montant:.2f}, NULL, {pdv_id}, {pdv_info['gerant_id']});")
                self.add_sql(sql)
                
                self.ids['transaction'] += 1
                self.stats['retraits'] += 1
                self.stats['total_retraits'] += montant
                
                # Mettre à jour le solde
                self.update_solde(pdv_id, 'cash', montant, '-')
                self.check_seuils(pdv_id, date_str)
                
                # Notification
                self.create_notification(
                    pdv_info['gerant_id'],
                    'RETRAIT_EFFECTUE',
                    f'Retrait de {montant:,.0f} FCFA',
                    f'Un retrait de {montant:,.0f} FCFA a été effectué sur votre PDV.',
                    date_str
                )

    # ------------------------------------------------------------------------
    # PHASE 11: DEMANDES DE VISITE
    # ------------------------------------------------------------------------

    def generate_demandes(self):
        """Génère des demandes de visite"""
        self.add_sql("-- ============================================================")
        self.add_sql("-- 11. DEMANDES DE VISITE")
        self.add_sql("-- ============================================================")
        
        for pdv_info in self.pdvs:
            pdv_id = pdv_info['id']
            pdv_date = datetime.strptime(pdv_info['date_creation'], '%Y-%m-%d %H:%M:%S')
            
            if pdv_id not in self.pdv_agents or not self.pdv_agents[pdv_id]:
                continue
            
            nb_demandes = random.randint(2, 4)
            
            for d in range(nb_demandes):
                date_creation = self.random_date(
                    pdv_date + timedelta(days=d * 40 + 10),
                    min(pdv_date + timedelta(days=d * 40 + 50), self.end_date)
                )
                date_creation_str = date_creation.strftime('%Y-%m-%d %H:%M:%S')
                
                # Type de demande
                if self.get_current_solde(pdv_id, 'flotte') < self.pdv_seuils[pdv_id]['flotte']:
                    type_demande = 'APPROVISIONNEMENT_FLOTTE'
                elif self.get_current_solde(pdv_id, 'cash') < self.pdv_seuils[pdv_id]['cash']:
                    type_demande = 'APPROVISIONNEMENT_ESPECE'
                else:
                    type_demande = random.choice(TYPE_DEMANDE)
                
                # Montant
                if type_demande.startswith('APPROVISIONNEMENT'):
                    montant = random.randint(MONTANT_APPROV_MIN, MONTANT_APPROV_MAX)
                else:
                    montant = random.randint(5000, 50000)
                
                # Agent assigné
                agent_id = random.choice(self.pdv_agents[pdv_id]) if random.random() > 0.3 else None
                
                # Créateur (gérant ou agent)
                createur_id = random.choice([pdv_info['gerant_id']] + self.pdv_agents[pdv_id])
                
                # Statut
                statut = self.get_demande_statut(date_creation)
                
                # Dates selon statut
                date_demandee = self.random_date(
                    date_creation + timedelta(days=1),
                    min(date_creation + timedelta(days=7), self.end_date)
                )
                date_demandee_str = date_demandee.strftime('%Y-%m-%d %H:%M:%S')
                
                date_validee = None
                date_effectuee = None
                motif_rejet = None
                transaction_id = None
                
                if statut in ['VALIDE', 'REALISEE']:
                    date_validee = self.random_datetime(date_creation, date_demandee)
                    
                    if statut == 'REALISEE':
                        date_effectuee = self.random_datetime(
                            datetime.strptime(date_validee, '%Y-%m-%d %H:%M:%S'),
                            min(date_demandee + timedelta(days=3), self.end_date)
                        )
                        
                        # Créer une transaction associée si approvisionnement
                        if type_demande.startswith('APPROVISIONNEMENT'):
                            transaction_date = date_effectuee or date_creation_str
                            lat, lng = self.random_coords(pdv_info['lat'], pdv_info['lng'], 0.02)
                            
                            # ========================================================================
                            # CORRECTION : Mapping des types pour les dépôts
                            # ========================================================================
                            if 'FLOTTE' in type_demande:
                                type_logique = 'DEPOT_FLOTTE'
                                type_solde = 'flotte'
                            else:
                                type_logique = 'DEPOT_ESPECE'
                                type_solde = 'cash'
                            
                            type_enum = self.map_type_transaction(type_logique)
                            
                            sql_trans = f"""
    INSERT INTO `transaction` (
        `id`, `date_transac`, `commentaire_rapport`, `photo_preuve_url`,
        `latitude_capture`, `longitude_capture`, `type_enum`, `statut`,
        `montant`, `type_probleme`, `point_vente_id`, `utilisateur_id`
    ) VALUES (
        {self.ids['transaction']},
        {self.sanitize_sql_value(transaction_date)},
        {self.sanitize_sql_value("Approbation par l'agent")},
        NULL,
        {lat:.8f},
        {lng:.8f},
        {self.sanitize_sql_value(type_enum)},
        {self.sanitize_sql_value('VALIDEE')},
        {montant:.2f},
        NULL,
        {pdv_id},
        {agent_id or createur_id}
    );
"""
                            self.add_sql(sql_trans)
                            transaction_id = self.ids['transaction']
                            self.ids['transaction'] += 1
                            
                            # Mettre à jour le solde
                            self.update_solde(pdv_id, type_solde, montant, '+')
                            self.stats['depots'] += 1
                            self.stats['total_depots'] += montant
                
                elif statut == 'REJETEE':
                    motif_rejet = random.choice([
                        'Demande non justifiée',
                        'Fonds insuffisants',
                        'Délai trop court',
                        'Demande en double',
                        'Absence de besoin'
                    ])
                
                sql = (f"INSERT INTO `demande_visite` (`id`, `type`, `montant`, `motif`, `description`, "
               f"`date_demandee`, `date_creation`, `date_effectuee`, `date_validee`, `statut`, "
               f"`motif_rejet`, `point_vente_id`, `agent_id`, `createur_id`, `transaction_id`) VALUES "
               f"({self.ids['demande_visite']}, {self.sanitize_sql_value(type_demande)}, {montant:.2f}, "
               f"{self.sanitize_sql_value(random.choice(['Reapprovisionnement', 'Visite de supervision', 'Maintenance', 'Réparation']))}, "
               f"NULL, {self.sanitize_sql_value(date_demandee_str)}, {self.sanitize_sql_value(date_creation_str)}, "
               f"{self.sanitize_sql_value(date_effectuee)}, {self.sanitize_sql_value(date_validee)}, "
               f"{self.sanitize_sql_value(statut)}, {self.sanitize_sql_value(motif_rejet)}, {pdv_id}, "
               f"{self.sanitize_sql_value(agent_id)}, {createur_id}, {self.sanitize_sql_value(transaction_id)});")
                self.add_sql(sql)
                
                self.stats['demandes_crees'] += 1
                self.ids['demande_visite'] += 1
                
                # Notification
                if statut == 'DEMANDEE':
                    self.create_notification(
                        pdv_info['gerant_id'] if agent_id else self.admins[0],
                        'APPROVISIONNEMENT_DEMANDE',
                        f'Demande {type_demande}',
                        f'Une demande de {montant:,.0f} FCFA a été créée.',
                        date_creation_str
                    )

        def get_demande_statut(self, date_creation: datetime) -> str:
         """Détermine le statut d'une demande"""
         days_old = (self.end_date - date_creation).days
    
        if days_old < 7:
         return random.choices(STATUT_DEMANDE, weights=[0.5, 0.3, 0.15, 0.03, 0.02, 0.0, 0.0], k=1)[0]
        elif days_old < 30:
         return random.choices(STATUT_DEMANDE, weights=[0.1, 0.2, 0.3, 0.3, 0.1, 0.0, 0.0], k=1)[0]
        else:
         return random.choices(STATUT_DEMANDE, weights=[0.02, 0.05, 0.1, 0.7, 0.13, 0.0, 0.0], k=1)[0]
    # ------------------------------------------------------------------------
    # PHASE 12: NOTIFICATIONS
    # ------------------------------------------------------------------------

    def create_notification(self, user_id: int, type_notif: str, titre: str, message: str, date_str: str):
        """Crée une notification"""
        sql = (f"INSERT INTO `notification` (`id`, `type`, `titre`, `message`, `lien`, "
               f"`lu`, `date_creation`, `date_lecture`, `utilisateur_id`) VALUES "
               f"({self.ids['notification']}, {self.sanitize_sql_value(type_notif)}, {self.sanitize_sql_value(titre)}, {self.sanitize_sql_value(message)}, "
               f"{self.sanitize_sql_value('/dashboard')}, 0, {self.sanitize_sql_value(date_str)}, NULL, {user_id});")
        self.add_sql(sql)
        self.ids['notification'] += 1
        self.stats['notifications_crees'] += 1

    def generate_notifications(self):
        """Génère des notifications supplémentaires"""
        self.add_sql("-- ============================================================")
        self.add_sql("-- 12. NOTIFICATIONS SUPPLÉMENTAIRES")
        self.add_sql("-- ============================================================")
        
        messages = {
            'VISITE_VALIDEE': ['Votre visite a été validée.', 'Rapport de visite approuvé.'],
            'VISITE_REJETEE': ['Votre visite a été rejetée.', 'Rapport à corriger.'],
            'VISITE_CREEE': ['Nouvelle visite assignée.', 'Visite programmée.'],
            'MESSAGE_ADMIN': ['Message de l\'administrateur.', 'Nouvelle communication.'],
            'ALERTE_SYSTEME': ['Alerte système générée.', 'Problème technique détecté.']
        }
        
        for user_id in self.users:
            nb_notifs = random.randint(2, 5)
            
            for _ in range(nb_notifs):
                date_creation = self.random_datetime(self.start_date, self.end_date)
                type_notif = random.choice(list(messages.keys()))
                titre = type_notif.replace('_', ' ').title()
                message = random.choice(messages[type_notif])
                
                sql = (f"INSERT INTO `notification` (`id`, `type`, `titre`, `message`, `lien`, "
                       f"`lu`, `date_creation`, `date_lecture`, `utilisateur_id`) VALUES "
                       f"({self.ids['notification']}, {self.sanitize_sql_value(type_notif)}, {self.sanitize_sql_value(titre)}, {self.sanitize_sql_value(message)}, "
                       f"{self.sanitize_sql_value('/dashboard')}, {random.randint(0, 1)}, {self.sanitize_sql_value(date_creation)}, NULL, {user_id});")
                self.add_sql(sql)
                self.ids['notification'] += 1
                self.stats['notifications_crees'] += 1

    # ------------------------------------------------------------------------
    # PHASE 13: GESTION DES SEUILS
    # ------------------------------------------------------------------------

    def check_seuils(self, pdv_id: int, date_str: str):
        """Vérifie si les seuils sont atteints et crée des notifications"""
        if pdv_id not in self.pdv_soldes or pdv_id not in self.pdv_seuils:
            return
        
        soldes = self.pdv_soldes[pdv_id]
        seuils = self.pdv_seuils[pdv_id]
        
        # Trouver le gérant du PDV
        gerant_id = None
        for pdv in self.pdvs:
            if pdv['id'] == pdv_id:
                gerant_id = pdv['gerant_id']
                break
        
        if not gerant_id:
            return
        
        # Vérifier le seuil flotte
        if soldes['flotte'] < seuils['flotte']:
            self.create_notification(
                gerant_id,
                'SEUIL_ALERTE_FLOTTE',
                f'Alerte seuil flotte - {soldes["flotte"]:,.0f} FCFA',
                f'Le solde flotte est à {soldes["flotte"]:,.0f} FCFA (seuil: {seuils["flotte"]:,.0f} FCFA).',
                date_str
            )
            self.stats['alertes_seuil'] += 1
        
        # Vérifier le seuil cash
        if soldes['cash'] < seuils['cash']:
            self.create_notification(
                gerant_id,
                'SEUIL_ALERTE_CASH',
                f'Alerte seuil cash - {soldes["cash"]:,.0f} FCFA',
                f'Le solde cash est à {soldes["cash"]:,.0f} FCFA (seuil: {seuils["cash"]:,.0f} FCFA).',
                date_str
            )
            self.stats['alertes_seuil'] += 1

    # ------------------------------------------------------------------------
    # MÉTHODE PRINCIPALE
    # ------------------------------------------------------------------------

    def generate(self) -> str:
        """Génère tout le fichier SQL"""
        self.generate_roles()
        self.generate_categories()
        self.generate_produits()
        self.generate_utilisateurs()
        self.generate_points_vente()
        self.generate_attributions()
        self.generate_flux()
        self.generate_ventes()
        self.generate_depots()
        self.generate_retraits()
        self.generate_demandes()
        self.generate_notifications()
        
        # Statistiques
        self.add_sql("")
        self.add_sql("-- ============================================================")
        self.add_sql("-- STATISTIQUES DE GÉNÉRATION")
        self.add_sql("-- ============================================================")
        self.add_sql(f"-- Utilisateurs: {len(self.users)}")
        self.add_sql(f"-- Points de vente: {len(self.pdvs)}")
        self.add_sql(f"-- Flux créés: {self.stats['flux_crees']}")
        self.add_sql(f"-- Ventes: {self.stats['ventes']} ({self.stats['total_ventes']:,.0f} FCFA)")
        self.add_sql(f"-- Dépôts: {self.stats['depots']} ({self.stats['total_depots']:,.0f} FCFA)")
        self.add_sql(f"-- Retraits: {self.stats['retraits']} ({self.stats['total_retraits']:,.0f} FCFA)")
        self.add_sql(f"-- Demandes: {self.stats['demandes_crees']}")
        self.add_sql(f"-- Notifications: {self.stats['notifications_crees']}")
        self.add_sql(f"-- Alertes seuil: {self.stats['alertes_seuil']}")
        self.add_sql("-- ============================================================")
        
        return '\n'.join(self.sql_lines)


# ============================================================================
# MAIN
# ============================================================================

def main():
    print("=" * 70)
    print("GENERATEUR DE DONNEES SQL - 6 MOIS")
    print("=" * 70)
    print()
    
    # Période: 6 mois à partir d'aujourd'hui
    end_date = datetime.now()
    start_date = end_date - timedelta(days=NB_MOIS * 30)
    
    print(f"Période: du {start_date.strftime('%d/%m/%Y')} au {end_date.strftime('%d/%m/%Y')}")
    print(f" Configuration:")
    print(f"   - Agents: {NB_AGENTS}")
    print(f"   - Gérants: {NB_GERANTS}")
    print(f"   - Points de vente: {NB_PDV}")
    print()
    
    # Création du générateur
    random.seed(42)  # Pour reproductibilité
    generator = SQLGenerator(start_date, end_date)
    
    print(" Génération en cours...")
    sql_content = generator.generate()
    
    # Écriture du fichier
    output_file = f"seed_data_{datetime.now().strftime('%Y%m%d_%H%M%S')}.sql"
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write("-- ============================================================\n")
        f.write("-- DONNÉES DE TEST - 6 MOIS D'ACTIVITÉ\n")
        f.write(f"-- Généré le: {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}\n")
        f.write("-- ============================================================\n\n")
        
        # Préfixe pour désactiver les contraintes
        f.write("/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n")
        f.write("/*!40101 SET NAMES utf8 */;\n")
        f.write("/*!50503 SET NAMES utf8mb4 */;\n")
        f.write("/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n")
        f.write("/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n")
        f.write("/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n")
        
        f.write("USE `projet_licence`;\n\n")
        f.write(sql_content)
        
        # Suffixe
        f.write("\n/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;\n")
        f.write("/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;\n")
        f.write("/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n")
        f.write("/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;\n")
    
    print()
    print("Génération terminée avec succès !")
    print(f" Fichier généré: {output_file}")
    print(f" Statistiques:")
    print(f"   - Utilisateurs: {len(generator.users)}")
    print(f"   - Points de vente: {len(generator.pdvs)}")
    print(f"   - Flux: {generator.stats['flux_crees']}")
    print(f"   - Ventes: {generator.stats['ventes']} ({generator.stats['total_ventes']:,.0f} FCFA)")
    print(f"   - Dépôts: {generator.stats['depots']} ({generator.stats['total_depots']:,.0f} FCFA)")
    print(f"   - Retraits: {generator.stats['retraits']} ({generator.stats['total_retraits']:,.0f} FCFA)")
    print(f"   - Demandes: {generator.stats['demandes_crees']}")
    print(f"   - Notifications: {generator.stats['notifications_crees']}")
    print(f"   - Alertes seuil: {generator.stats['alertes_seuil']}")
    print()
    print(" Pour importer dans MySQL:")
    print("=" * 70)


if __name__ == "__main__":
    main()