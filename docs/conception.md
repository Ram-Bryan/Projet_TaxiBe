## Projet:
Theme: Itineraire taxi Be 
Centre sur: 137, 187 et 172

Besoin repondu:
- savoir le temps optimale pour arrive qqpart en bus
- voir les trajets
- savoir frais
- et si on allait en d autre alternative

PROBLEMATIQUE ????
PLAN ???

## Fonctionnalite:
- Login admin\simple

- Backoffice:
    - CRUD arret, trajet et bus
    - creer trajet: creer des arrets d'abords ensuite on peut cliquer les arrets ou on veut aller.

- Frontoffice:
    - geolocalisation: on peut savoir via geolocalistaiont ou se trouve l'user
    - arrive et depart donne --> savoir le chemin
    - possiblite de plusieur trajets/bus qui se chevauchent:
    - optimisation des frais
    - voir le temps d'un trajet et la distance d un trajet.

- Technologie:
    - php CodeIgniter
    - postgresql + postgis
    - leaflet + OSM pour avoir des routes comme reel: 

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>

    - icons cdn lucide.

## Conception BD:
CREATE TABLE moyen (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    vitesse NUMERIC NOT NULL
);

-- Table frais : contient le tarif actuel par trajet de bus
CREATE TABLE frais (
    id      SERIAL PRIMARY KEY,
    montant NUMERIC NOT NULL
);

-- Table historique_frais : trace chaque changement de tarif
CREATE TABLE historique_frais (
    id              SERIAL PRIMARY KEY,
    id_frais        INTEGER NOT NULL REFERENCES frais(id) ON DELETE CASCADE,
    montant         NUMERIC NOT NULL,
    date_changement TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table bus
CREATE TABLE bus (
    id  SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
);

-- Table arret (avec géométrie PostGIS)
CREATE TABLE arret (
    id    SERIAL PRIMARY KEY,
    nom   VARCHAR(150) NOT NULL,
    point GEOMETRY(Point, 4326) NOT NULL
);
CREATE INDEX idx_arret_point ON arret USING GIST(point);

-- Table trajet
CREATE TABLE trajet (
    id          SERIAL PRIMARY KEY,
    id_bus      INTEGER NOT NULL REFERENCES bus(id) ON DELETE CASCADE,
    description TEXT
);
CREATE INDEX idx_trajet_bus ON trajet(id_bus);

-- Table de liaison trajet_arret
CREATE TABLE trajet_arret (
    id        SERIAL PRIMARY KEY,
    id_trajet INTEGER NOT NULL REFERENCES trajet(id) ON DELETE CASCADE,
    id_arret  INTEGER NOT NULL REFERENCES arret(id)  ON DELETE CASCADE,
    ordre     INTEGER NOT NULL,
    UNIQUE (id_trajet, id_arret),
    UNIQUE (id_trajet, ordre)
);

CREATE TABLE IF NOT EXISTS utilisateur (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'simple' -- 'simple' ou 'admin'
);


