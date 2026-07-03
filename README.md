# TaxiBe - Système de Gestion des Trajets en Transport Public

TaxiBe est une application web complète pour la gestion et l'optimisation des trajets en bus à Bruxelles, permettant aux utilisateurs de planifier leurs itinéraires et aux administrateurs de gérer les arrêts, bus et tarifs.

## 📋 Table des matières

- [Fonctionnalités](#-fonctionnalités)
- [Architecture](#-architecture)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Structure du projet](#-structure-du-projet)
- [Utilisation](#-utilisation)
- [Base de données](#-base-de-données)
- [Technologies](#-technologies)

## ✨ Fonctionnalités

### Pour les utilisateurs (Frontoffice)
- **Géolocalisation** : Localisation en temps réel de l'utilisateur
- **Planification de trajets** : Entrée d'un lieu de départ et d'arrivée
- **Trajets optimisés** : Affichage de plusieurs options de trajets disponibles
- **Estimation des frais** : Calcul automatique des tarifs selon le trajet choisi
- **Informations détaillées** : Temps d'arrivée estimé et distance du trajet
- **Visualisation cartographique** : Affichage des trajets sur une carte interactive avec Leaflet/OSM

### Pour les administrateurs (Backoffice)
- **Authentification sécurisée** : Gestion des utilisateurs avec rôles (admin/simple)
- **Gestion des arrêts** : CRUD complet pour créer, modifier, supprimer les arrêts
- **Gestion des trajets** : Création de trajets en sélectionnant les arrêts sur la carte
- **Gestion des bus** : CRUD pour les véhicules disponibles
- **Gestion des tarifs** : Configuration des frais et historique des modifications
- **API REST** : Endpoints pour intégration avec d'autres services

## 🏗️ Architecture

L'application suit le pattern **MVC** (Modèle-Vue-Contrôleur) fourni par CodeIgniter 4.

```
Frontoffice (Public)
├── MapController          # Gestion des trajets utilisateur
└── Vues utilisateur

Backoffice (Admin)
├── AdminController        # Gestion complète
├── AuthController         # Authentification
└── Vues administrateur

API
└── Controllers/Api/       # Endpoints REST
```

## 🚀 Installation

### Prérequis
- PHP 7.4 ou supérieur
- PostgreSQL 12+ avec extension PostGIS
- Composer
- Node.js (optionnel, pour les assets front)

### Étapes

1. **Cloner le repository**
   ```bash
   git clone <url-repo>
   cd Projet_TaxiBe
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer l'environnement**
   ```bash
   cp env .env
   # Éditer .env et configurer les paramètres
   ```

4. **Créer la base de données PostgreSQL**
   ```bash
   createdb taxibe
   ```

5. **Adapter les identifiants dans `.env`**
   ```
   database.default.hostname = localhost
   database.default.database = taxibe
   database.default.username = postgres
   database.default.password = <votre_mdp>
   ```

6. **Exécuter les migrations**
   ```bash
   php spark migrate
   ```

7. **Insérer les données de test**
   ```bash
   php spark db:seed TaxiBeSeeder
   ```

8. **Vérifier que l'application fonctionne**
   ```bash
   php spark serve
   ```

   Accédez à : `http://localhost:8080`

## ⚙️ Configuration

### Fichier `.env`

Les paramètres importants à configurer :

```env
# Base de données
database.default.hostname = localhost
database.default.database = taxibe
database.default.username = postgres
database.default.password = votre_mot_de_passe

# URL de l'application
app.baseURL = http://localhost:8080

# Mode de sécurité
app.forceGlobalSecureRequests = false
```

## 📁 Structure du projet

```
Projet_TaxiBe/
├── app/
│   ├── Config/              # Fichiers de configuration
│   ├── Controllers/         # Contrôleurs (Admin, Auth, Map, Api)
│   ├── Models/              # Modèles (Arret, Bus, Trajet, etc.)
│   ├── Views/               # Templates HTML/PHP
│   ├── Filters/             # Filtres d'authentification
│   └── Database/
│       ├── Migrations/      # Migrations de schéma
│       └── Seeds/           # Données de test
├── public/
│   └── index.php            # Point d'entrée
├── docs/                    # Documentation
│   ├── conception.md        # Architecture et conception
│   └── indication.md        # Instructions d'installation
├── writable/                # Dossiers en écriture (logs, cache)
├── composer.json            # Dépendances PHP
└── phpunit.xml.dist         # Configuration tests
```

## 💾 Base de données

### Schéma principal

**Table `utilisateur`** - Gestion des utilisateurs
```sql
- id (PK)
- nom, email (UNIQUE)
- mot_de_passe (hashé)
- role ('admin' ou 'simple')
```

**Table `arret`** - Arrêts de bus avec géolocalisation (PostGIS)
```sql
- id (PK)
- nom
- point (GEOMETRY - latitude/longitude)
```

**Table `bus`** - Véhicules
```sql
- id (PK)
- nom
```

**Table `trajet`** - Trajets de bus
```sql
- id (PK)
- id_bus (FK)
- description
```

**Table `trajet_arret`** - Liaison trajet ↔ arrêts
```sql
- id (PK)
- id_trajet (FK)
- id_arret (FK)
- ordre (ordre des arrêts)
```

**Table `frais`** - Tarifs actuels
```sql
- id (PK)
- montant
```

**Table `historique_frais`** - Historique des modifications de tarifs
```sql
- id (PK)
- id_frais (FK)
- montant
- date_changement
```

**Table `moyen`** - Types de transport
```sql
- id (PK)
- nom
- vitesse
```

## 🎯 Utilisation

### Pour un utilisateur
1. Accéder au site public
2. Autoriser la géolocalisation
3. Entrer destination finale
4. Voir les trajets disponibles avec prix et durée
5. Sélectionner un trajet

### Pour un administrateur
1. Se connecter avec identifiants admin
2. Accéder au backoffice
3. Gérer arrêts, bus, trajets et tarifs
4. Voir les statistiques d'utilisation

## 🛠️ Technologies

| Couche | Technologies |
|--------|--------------|
| **Backend** | PHP 7.4+, CodeIgniter 4 |
| **Base de données** | PostgreSQL 12+, PostGIS (géospatial) |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) |
| **Cartographie** | Leaflet, OpenStreetMap (OSM) |
| **Calcul d'itinéraires** | Leaflet Routing Machine (OSRM) |
| **Icons UI** | Lucide (CDN) |
| **Tests** | PHPUnit |

## 📖 Documentation supplémentaire

- [Conception technique](docs/conception.md)
- [Instructions d'installation détaillées](docs/indication.md)

## 👥 Contribution

Pour toute contribution, veuillez :
1. Créer une branche (`git checkout -b feature/AmazingFeature`)
2. Commiter vos changements (`git commit -m 'Add AmazingFeature'`)
3. Pousser la branche (`git push origin feature/AmazingFeature`)
4. Ouvrir une Pull Request

## 📝 Licence

Ce projet est licencié sous la Licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

**Version** : 1.0.0  
**Dernière mise à jour** : Juillet 2026