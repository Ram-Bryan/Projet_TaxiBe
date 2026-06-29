-- ============================================================================
-- TaxiBe — Script de configuration manuelle de la base de données
-- PostgreSQL + PostGIS
--
-- Usage :
--   psql -U postgres -f docs/setup_db.sql
--
-- Ce script est une ALTERNATIVE aux migrations CI4.
-- En dev normal, préférer : php spark migrate && php spark db:seed TaxiBeSeeder
-- ============================================================================

-- 1. Créer la base de données (à exécuter en tant que superuser postgres)
-- Décommenter si la base n'existe pas encore :


CREATE DATABASE taxibe
    WITH ENCODING = 'UTF8'
    LC_COLLATE = 'fr_FR.UTF-8'
    LC_CTYPE   = 'fr_FR.UTF-8'
    TEMPLATE   = template0;


\c taxibe;

-- ============================================================================
-- 2. Activer l'extension PostGIS
-- ============================================================================
CREATE EXTENSION IF NOT EXISTS postgis;

-- ============================================================================
-- 3. Création des tables
-- ============================================================================

-- Suppression dans l'ordre inverse des FK pour re-run idempotent
DROP TABLE IF EXISTS trajet_arret CASCADE;
DROP TABLE IF EXISTS trajet       CASCADE;
DROP TABLE IF EXISTS arret        CASCADE;
DROP TABLE IF EXISTS bus          CASCADE;
DROP TABLE IF EXISTS moyen        CASCADE;

-- Table moyen
CREATE TABLE moyen (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    vitesse NUMERIC NOT NULL
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

-- Index spatial GIST pour requêtes ST_DWithin / KNN
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

CREATE INDEX idx_trajet_arret_trajet ON trajet_arret(id_trajet);
CREATE INDEX idx_trajet_arret_arret  ON trajet_arret(id_arret);

-- ============================================================================
-- 4. Données de test — Antananarivo
-- ============================================================================

-- Moyens
INSERT INTO moyen (nom, vitesse) VALUES
    ('🚶 Marche', 5),
    ('🛵 Moto', 40),
    ('🚌 Bus', 30);

-- Bus
INSERT INTO bus (nom) VALUES
    ('Taxi-Be 001 — Analakely ↔ Ambohipo'),
    ('Taxi-Be 002 — Isotry ↔ Ankorondrano'),
    ('Taxi-Be 003 — Mahamasina ↔ Ambohibao'),
    ('Taxi-Be 004 — Itaosy ↔ Behoririka'),
    ('Taxi-Be 005 — Tana-Centre ↔ Antsahamanitra');

-- Arrêts (ST_MakePoint prend longitude en premier, latitude en second)
INSERT INTO arret (nom, point) VALUES
    ('Analakely — Place du 13 Mai',          ST_SetSRID(ST_MakePoint(47.53635, -18.91449), 4326)),
    ('Behoririka — Rue Rainandriamampandry', ST_SetSRID(ST_MakePoint(47.53230, -18.91980), 4326)),
    ('Isotry — Marché Isotry',               ST_SetSRID(ST_MakePoint(47.52580, -18.92710), 4326)),
    ('Ampefiloha — Faculté de Droit',        ST_SetSRID(ST_MakePoint(47.52990, -18.90630), 4326)),
    ('Ankorondrano — Hypermarché Score',     ST_SetSRID(ST_MakePoint(47.53520, -18.89560), 4326)),
    ('Tana-Est — Ankadifotsy',               ST_SetSRID(ST_MakePoint(47.55100, -18.91100), 4326)),
    ('Mahamasina — Stade Municipal',         ST_SetSRID(ST_MakePoint(47.53900, -18.92580), 4326)),
    ('Antsahamanitra — Campus Université',   ST_SetSRID(ST_MakePoint(47.52780, -18.89830), 4326)),
    ('Ambohibao — Rond-point',               ST_SetSRID(ST_MakePoint(47.51400, -18.88200), 4326)),
    ('Itaosy — Marché Itaosy',               ST_SetSRID(ST_MakePoint(47.49900, -18.94500), 4326));

-- Trajets
INSERT INTO trajet (id_bus, description) VALUES
    (1, 'Trajet Analakely → Behoririka → Isotry → Mahamasina → Itaosy (ligne Sud-Ouest)'),
    (2, 'Trajet Isotry → Ampefiloha → Ankorondrano → Antsahamanitra → Ambohibao (ligne Nord)');

-- Liaisons trajet_arret (trajet 1)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    (1, (SELECT id FROM arret WHERE nom LIKE 'Analakely%'),          1),
    (1, (SELECT id FROM arret WHERE nom LIKE 'Behoririka%'),         2),
    (1, (SELECT id FROM arret WHERE nom LIKE 'Isotry%'),             3),
    (1, (SELECT id FROM arret WHERE nom LIKE 'Mahamasina%'),         4),
    (1, (SELECT id FROM arret WHERE nom LIKE 'Itaosy%'),             5);

-- Liaisons trajet_arret (trajet 2)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    (2, (SELECT id FROM arret WHERE nom LIKE 'Isotry%'),             1),
    (2, (SELECT id FROM arret WHERE nom LIKE 'Ampefiloha%'),         2),
    (2, (SELECT id FROM arret WHERE nom LIKE 'Ankorondrano%'),       3),
    (2, (SELECT id FROM arret WHERE nom LIKE 'Antsahamanitra%'),     4),
    (2, (SELECT id FROM arret WHERE nom LIKE 'Ambohibao%'),          5);

-- ============================================================================
-- 5. Vérification
-- ============================================================================
SELECT 'bus'          AS table_name, COUNT(*) AS nb FROM bus
UNION ALL
SELECT 'arret',                      COUNT(*)        FROM arret
UNION ALL
SELECT 'trajet',                     COUNT(*)        FROM trajet
UNION ALL
SELECT 'trajet_arret',               COUNT(*)        FROM trajet_arret
UNION ALL
SELECT 'moyen',                      COUNT(*)        FROM moyen;

-- Vérification géométrique
SELECT nom, ST_AsText(point) AS wkt, ST_IsValid(point) AS valide
FROM arret
ORDER BY nom;
