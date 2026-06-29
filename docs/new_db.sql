-- ============================================================================
-- TaxiBe — Script complet de configuration et de peuplement de la base
-- PostgreSQL + PostGIS
--
-- Usage :
--   psql -U postgres -f ce_fichier.sql
--
-- Contient 10 trajets (5 allers + 5 retours) avec des arrêts partagés.
-- ============================================================================

-- 1. Créer la base de données (à exécuter en tant que superuser postgres)
-- Décommenter si la base n'existe pas encore :

-- psql -U postgres -d taxibe -f new_db.sql
CREATE DATABASE taxibe
    WITH ENCODING = 'UTF8'
    LC_COLLATE = 'fr_FR.UTF-8'
    LC_CTYPE   = 'fr_FR.UTF-8'
    TEMPLATE   = template0;

-- ============================================================================
-- 2. Activer l'extension PostGIS
-- ============================================================================
CREATE EXTENSION IF NOT EXISTS postgis;

-- ============================================================================
-- 3. Suppression des tables dans l'ordre inverse des FK (pour réexécution propre)
-- ============================================================================
DROP TABLE IF EXISTS historique_frais CASCADE;
DROP TABLE IF EXISTS frais            CASCADE;
DROP TABLE IF EXISTS trajet_arret     CASCADE;
DROP TABLE IF EXISTS trajet           CASCADE;
DROP TABLE IF EXISTS arret            CASCADE;
DROP TABLE IF EXISTS bus              CASCADE;
DROP TABLE IF EXISTS moyen            CASCADE;

-- ============================================================================
-- 4. Création des tables
-- ============================================================================

-- Table moyen
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
CREATE INDEX idx_trajet_arret_trajet ON trajet_arret(id_trajet);
CREATE INDEX idx_trajet_arret_arret  ON trajet_arret(id_arret);

-- ============================================================================
-- 5. Données de test — Antananarivo (15 arrêts, 5 bus, 10 trajets)
-- ============================================================================

-- 5.1 Moyens de transport
INSERT INTO moyen (nom, vitesse) VALUES
    ('Marche', 5),
    ('Moto', 40),
    ('Bus', 30);

-- 5.2 Frais et historique
INSERT INTO frais (montant) VALUES (600);
INSERT INTO historique_frais (id_frais, montant)
SELECT id, montant FROM frais WHERE montant = 600;

-- 5.3 Bus (5 lignes)
INSERT INTO bus (nom) VALUES
    ('Ligne 1 — Nord ↔ Sud'),
    ('Ligne 2 — Est ↔ Ouest'),
    ('Ligne 3 — Périphérique Ouest'),
    ('Ligne 4 — Périphérique Est'),
    ('Ligne 5 — Diagonale Centre');

-- 5.4 Arrêts (15 points)
INSERT INTO arret (nom, point) VALUES
    ('A1 - Gare routière Sud',       ST_SetSRID(ST_MakePoint(47.5100, -18.9500), 4326)),
    ('A2 - Marché d''Itaosy',        ST_SetSRID(ST_MakePoint(47.4990, -18.9450), 4326)),
    ('A3 - Rond‑point Isotry',       ST_SetSRID(ST_MakePoint(47.5258, -18.9271), 4326)),
    ('A4 - Université d''Ankatso',   ST_SetSRID(ST_MakePoint(47.5278, -18.8983), 4326)),
    ('A5 - Ambohibao (Ouest)',       ST_SetSRID(ST_MakePoint(47.5140, -18.8820), 4326)),
    ('A6 - Ankorondrano (Score)',    ST_SetSRID(ST_MakePoint(47.5352, -18.8956), 4326)),
    ('A7 - Place du 13 Mai',         ST_SetSRID(ST_MakePoint(47.5363, -18.9145), 4326)),
    ('A8 - Behoririka (Centre)',     ST_SetSRID(ST_MakePoint(47.5323, -18.9198), 4326)),
    ('A9 - Stade Mahamasina',        ST_SetSRID(ST_MakePoint(47.5390, -18.9258), 4326)),
    ('A10 - Ankadifotsy (Est)',      ST_SetSRID(ST_MakePoint(47.5510, -18.9110), 4326)),
    ('A11 - Ambohijatovo',           ST_SetSRID(ST_MakePoint(47.5220, -18.9140), 4326)),
    ('A12 - Ampefiloha (Faculté)',   ST_SetSRID(ST_MakePoint(47.5299, -18.9063), 4326)),
    ('A13 - Antsahamanitra (Université)', ST_SetSRID(ST_MakePoint(47.5278, -18.8983), 4326)),
    ('A14 - Andraharo (Nord)',       ST_SetSRID(ST_MakePoint(47.5400, -18.8900), 4326)),
    ('A15 - Anosy (Lac)',            ST_SetSRID(ST_MakePoint(47.5300, -18.9200), 4326));

-- 5.5 Trajets (10) — 5 allers + 5 retours
-- On insère d'abord tous les trajets (avec leurs descriptions uniques)
INSERT INTO trajet (id_bus, description) VALUES
    ((SELECT id FROM bus WHERE nom = 'Ligne 1 — Nord ↔ Sud'), 'Trajet 1 : Aller (Nord → Sud)'),
    ((SELECT id FROM bus WHERE nom = 'Ligne 1 — Nord ↔ Sud'), 'Trajet 2 : Retour (Sud → Nord)'),
    ((SELECT id FROM bus WHERE nom = 'Ligne 2 — Est ↔ Ouest'), 'Trajet 3 : Aller (Est → Ouest)'),
    ((SELECT id FROM bus WHERE nom = 'Ligne 2 — Est ↔ Ouest'), 'Trajet 4 : Retour (Ouest → Est)'),
    ((SELECT id FROM bus WHERE nom = 'Ligne 3 — Périphérique Ouest'), 'Trajet 5 : Aller (Sud‑Ouest → Nord‑Ouest)'),
    ((SELECT id FROM bus WHERE nom = 'Ligne 3 — Périphérique Ouest'), 'Trajet 6 : Retour (Nord‑Ouest → Sud‑Ouest)'),
    ((SELECT id FROM bus WHERE nom = 'Ligne 4 — Périphérique Est'), 'Trajet 7 : Aller (Sud‑Est → Nord‑Est)'),
    ((SELECT id FROM bus WHERE nom = 'Ligne 4 — Périphérique Est'), 'Trajet 8 : Retour (Nord‑Est → Sud‑Est)'),
    ((SELECT id FROM bus WHERE nom = 'Ligne 5 — Diagonale Centre'), 'Trajet 9 : Aller (Sud → Nord‑Ouest)'),
    ((SELECT id FROM bus WHERE nom = 'Ligne 5 — Diagonale Centre'), 'Trajet 10 : Retour (Nord‑Ouest → Sud)');


-- Ensuite, pour chaque trajet, on insère ses arrêts en utilisant sa description
-- Ligne 1 : Aller (trajet 1)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    ((SELECT id FROM trajet WHERE description = 'Trajet 1 : Aller (Nord → Sud)'), (SELECT id FROM arret WHERE nom = 'A14 - Andraharo (Nord)'), 1),
    ((SELECT id FROM trajet WHERE description = 'Trajet 1 : Aller (Nord → Sud)'), (SELECT id FROM arret WHERE nom = 'A6 - Ankorondrano (Score)'), 2),
    ((SELECT id FROM trajet WHERE description = 'Trajet 1 : Aller (Nord → Sud)'), (SELECT id FROM arret WHERE nom = 'A12 - Ampefiloha (Faculté)'), 3),
    ((SELECT id FROM trajet WHERE description = 'Trajet 1 : Aller (Nord → Sud)'), (SELECT id FROM arret WHERE nom = 'A3 - Rond‑point Isotry'), 4),
    ((SELECT id FROM trajet WHERE description = 'Trajet 1 : Aller (Nord → Sud)'), (SELECT id FROM arret WHERE nom = 'A2 - Marché d''Itaosy'), 5),
    ((SELECT id FROM trajet WHERE description = 'Trajet 1 : Aller (Nord → Sud)'), (SELECT id FROM arret WHERE nom = 'A1 - Gare routière Sud'), 6);

-- Ligne 1 : Retour (trajet 2)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    ((SELECT id FROM trajet WHERE description = 'Trajet 2 : Retour (Sud → Nord)'), (SELECT id FROM arret WHERE nom = 'A1 - Gare routière Sud'), 1),
    ((SELECT id FROM trajet WHERE description = 'Trajet 2 : Retour (Sud → Nord)'), (SELECT id FROM arret WHERE nom = 'A2 - Marché d''Itaosy'), 2),
    ((SELECT id FROM trajet WHERE description = 'Trajet 2 : Retour (Sud → Nord)'), (SELECT id FROM arret WHERE nom = 'A3 - Rond‑point Isotry'), 3),
    ((SELECT id FROM trajet WHERE description = 'Trajet 2 : Retour (Sud → Nord)'), (SELECT id FROM arret WHERE nom = 'A12 - Ampefiloha (Faculté)'), 4),
    ((SELECT id FROM trajet WHERE description = 'Trajet 2 : Retour (Sud → Nord)'), (SELECT id FROM arret WHERE nom = 'A6 - Ankorondrano (Score)'), 5),
    ((SELECT id FROM trajet WHERE description = 'Trajet 2 : Retour (Sud → Nord)'), (SELECT id FROM arret WHERE nom = 'A14 - Andraharo (Nord)'), 6);

-- Ligne 2 : Aller (trajet 3)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    ((SELECT id FROM trajet WHERE description = 'Trajet 3 : Aller (Est → Ouest)'), (SELECT id FROM arret WHERE nom = 'A10 - Ankadifotsy (Est)'), 1),
    ((SELECT id FROM trajet WHERE description = 'Trajet 3 : Aller (Est → Ouest)'), (SELECT id FROM arret WHERE nom = 'A7 - Place du 13 Mai'), 2),
    ((SELECT id FROM trajet WHERE description = 'Trajet 3 : Aller (Est → Ouest)'), (SELECT id FROM arret WHERE nom = 'A3 - Rond‑point Isotry'), 3),
    ((SELECT id FROM trajet WHERE description = 'Trajet 3 : Aller (Est → Ouest)'), (SELECT id FROM arret WHERE nom = 'A11 - Ambohijatovo'), 4),
    ((SELECT id FROM trajet WHERE description = 'Trajet 3 : Aller (Est → Ouest)'), (SELECT id FROM arret WHERE nom = 'A5 - Ambohibao (Ouest)'), 5);

-- Ligne 2 : Retour (trajet 4)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    ((SELECT id FROM trajet WHERE description = 'Trajet 4 : Retour (Ouest → Est)'), (SELECT id FROM arret WHERE nom = 'A5 - Ambohibao (Ouest)'), 1),
    ((SELECT id FROM trajet WHERE description = 'Trajet 4 : Retour (Ouest → Est)'), (SELECT id FROM arret WHERE nom = 'A11 - Ambohijatovo'), 2),
    ((SELECT id FROM trajet WHERE description = 'Trajet 4 : Retour (Ouest → Est)'), (SELECT id FROM arret WHERE nom = 'A3 - Rond‑point Isotry'), 3),
    ((SELECT id FROM trajet WHERE description = 'Trajet 4 : Retour (Ouest → Est)'), (SELECT id FROM arret WHERE nom = 'A7 - Place du 13 Mai'), 4),
    ((SELECT id FROM trajet WHERE description = 'Trajet 4 : Retour (Ouest → Est)'), (SELECT id FROM arret WHERE nom = 'A10 - Ankadifotsy (Est)'), 5);

-- Ligne 3 : Aller (trajet 5)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    ((SELECT id FROM trajet WHERE description = 'Trajet 5 : Aller (Sud‑Ouest → Nord‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A15 - Anosy (Lac)'), 1),
    ((SELECT id FROM trajet WHERE description = 'Trajet 5 : Aller (Sud‑Ouest → Nord‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A3 - Rond‑point Isotry'), 2),
    ((SELECT id FROM trajet WHERE description = 'Trajet 5 : Aller (Sud‑Ouest → Nord‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A12 - Ampefiloha (Faculté)'), 3),
    ((SELECT id FROM trajet WHERE description = 'Trajet 5 : Aller (Sud‑Ouest → Nord‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A6 - Ankorondrano (Score)'), 4),
    ((SELECT id FROM trajet WHERE description = 'Trajet 5 : Aller (Sud‑Ouest → Nord‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A4 - Université d''Ankatso'), 5),
    ((SELECT id FROM trajet WHERE description = 'Trajet 5 : Aller (Sud‑Ouest → Nord‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A13 - Antsahamanitra (Université)'), 6);

-- Ligne 3 : Retour (trajet 6)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    ((SELECT id FROM trajet WHERE description = 'Trajet 6 : Retour (Nord‑Ouest → Sud‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A13 - Antsahamanitra (Université)'), 1),
    ((SELECT id FROM trajet WHERE description = 'Trajet 6 : Retour (Nord‑Ouest → Sud‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A4 - Université d''Ankatso'), 2),
    ((SELECT id FROM trajet WHERE description = 'Trajet 6 : Retour (Nord‑Ouest → Sud‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A6 - Ankorondrano (Score)'), 3),
    ((SELECT id FROM trajet WHERE description = 'Trajet 6 : Retour (Nord‑Ouest → Sud‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A12 - Ampefiloha (Faculté)'), 4),
    ((SELECT id FROM trajet WHERE description = 'Trajet 6 : Retour (Nord‑Ouest → Sud‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A3 - Rond‑point Isotry'), 5),
    ((SELECT id FROM trajet WHERE description = 'Trajet 6 : Retour (Nord‑Ouest → Sud‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A15 - Anosy (Lac)'), 6);

-- Ligne 4 : Aller (trajet 7)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    ((SELECT id FROM trajet WHERE description = 'Trajet 7 : Aller (Sud‑Est → Nord‑Est)'), (SELECT id FROM arret WHERE nom = 'A9 - Stade Mahamasina'), 1),
    ((SELECT id FROM trajet WHERE description = 'Trajet 7 : Aller (Sud‑Est → Nord‑Est)'), (SELECT id FROM arret WHERE nom = 'A8 - Behoririka (Centre)'), 2),
    ((SELECT id FROM trajet WHERE description = 'Trajet 7 : Aller (Sud‑Est → Nord‑Est)'), (SELECT id FROM arret WHERE nom = 'A7 - Place du 13 Mai'), 3),
    ((SELECT id FROM trajet WHERE description = 'Trajet 7 : Aller (Sud‑Est → Nord‑Est)'), (SELECT id FROM arret WHERE nom = 'A10 - Ankadifotsy (Est)'), 4),
    ((SELECT id FROM trajet WHERE description = 'Trajet 7 : Aller (Sud‑Est → Nord‑Est)'), (SELECT id FROM arret WHERE nom = 'A14 - Andraharo (Nord)'), 5);

-- Ligne 4 : Retour (trajet 8)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    ((SELECT id FROM trajet WHERE description = 'Trajet 8 : Retour (Nord‑Est → Sud‑Est)'), (SELECT id FROM arret WHERE nom = 'A14 - Andraharo (Nord)'), 1),
    ((SELECT id FROM trajet WHERE description = 'Trajet 8 : Retour (Nord‑Est → Sud‑Est)'), (SELECT id FROM arret WHERE nom = 'A10 - Ankadifotsy (Est)'), 2),
    ((SELECT id FROM trajet WHERE description = 'Trajet 8 : Retour (Nord‑Est → Sud‑Est)'), (SELECT id FROM arret WHERE nom = 'A7 - Place du 13 Mai'), 3),
    ((SELECT id FROM trajet WHERE description = 'Trajet 8 : Retour (Nord‑Est → Sud‑Est)'), (SELECT id FROM arret WHERE nom = 'A8 - Behoririka (Centre)'), 4),
    ((SELECT id FROM trajet WHERE description = 'Trajet 8 : Retour (Nord‑Est → Sud‑Est)'), (SELECT id FROM arret WHERE nom = 'A9 - Stade Mahamasina'), 5);

-- Ligne 5 : Aller (trajet 9)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    ((SELECT id FROM trajet WHERE description = 'Trajet 9 : Aller (Sud → Nord‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A2 - Marché d''Itaosy'), 1),
    ((SELECT id FROM trajet WHERE description = 'Trajet 9 : Aller (Sud → Nord‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A8 - Behoririka (Centre)'), 2),
    ((SELECT id FROM trajet WHERE description = 'Trajet 9 : Aller (Sud → Nord‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A11 - Ambohijatovo'), 3),
    ((SELECT id FROM trajet WHERE description = 'Trajet 9 : Aller (Sud → Nord‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A4 - Université d''Ankatso'), 4),
    ((SELECT id FROM trajet WHERE description = 'Trajet 9 : Aller (Sud → Nord‑Ouest)'), (SELECT id FROM arret WHERE nom = 'A6 - Ankorondrano (Score)'), 5);

-- Ligne 5 : Retour (trajet 10)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
    ((SELECT id FROM trajet WHERE description = 'Trajet 10 : Retour (Nord‑Ouest → Sud)'), (SELECT id FROM arret WHERE nom = 'A6 - Ankorondrano (Score)'), 1),
    ((SELECT id FROM trajet WHERE description = 'Trajet 10 : Retour (Nord‑Ouest → Sud)'), (SELECT id FROM arret WHERE nom = 'A4 - Université d''Ankatso'), 2),
    ((SELECT id FROM trajet WHERE description = 'Trajet 10 : Retour (Nord‑Ouest → Sud)'), (SELECT id FROM arret WHERE nom = 'A11 - Ambohijatovo'), 3),
    ((SELECT id FROM trajet WHERE description = 'Trajet 10 : Retour (Nord‑Ouest → Sud)'), (SELECT id FROM arret WHERE nom = 'A8 - Behoririka (Centre)'), 4),
    ((SELECT id FROM trajet WHERE description = 'Trajet 10 : Retour (Nord‑Ouest → Sud)'), (SELECT id FROM arret WHERE nom = 'A2 - Marché d''Itaosy'), 5);

-- ============================================================================
-- Fin du script — plus aucune requête de vérification
-- ============================================================================