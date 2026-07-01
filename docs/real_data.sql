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

DROP DATABASE taxibe;

CREATE DATABASE taxibe
    WITH ENCODING = 'UTF8'
    LC_COLLATE = 'fr_FR.UTF-8'
    LC_CTYPE   = 'fr_FR.UTF-8'
    TEMPLATE   = template0;

\c taxibe 
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
DROP TABLE IF EXISTS utilisateur      CASCADE;



CREATE TABLE IF NOT EXISTS utilisateur (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'simple' -- 'simple' ou 'admin'
);

-- Insertion de l'utilisateur Admin
-- Mot de passe en clair : admin123
INSERT INTO utilisateur (nom, email, mot_de_passe, role) 
VALUES ('Administrateur', 'admin@taxibe.mg', '$2y$10$kCTgTmMQf1eEuJIUhou.C.oAKE3twCwEPkjpVD2n70yQd5c47cCaK', 'admin')
ON CONFLICT (email) DO NOTHING;

-- Insertion de l'utilisateur Simple
-- Mot de passe en clair : user123
INSERT INTO utilisateur (nom, email, mot_de_passe, role) 
VALUES ('Utilisateur Test', 'user@taxibe.mg', '$2y$10$.YQY9/k8dFfmsm/heEuZBOWXmKqw2MqkP2vnTwAG.6wsUp3HjRI6K', 'simple')
ON CONFLICT (email) DO NOTHING;

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

-- =====================================================
-- 1. INSERTION DES ARRÊTS (avec IDs explicites)
-- =====================================================

-- =====================================================
-- 1. INSERTION DES ARRÊTS (avec IDs explicites)
-- =====================================================

-- Bus 172 (IDs: 1 à 27)
INSERT INTO arret (id, nom, point) VALUES
(1, 'Fiadanamanga', ST_SetSRID(ST_MakePoint(47.535819598355076, -18.99802217333703), 4326)),
(2, 'Mandrimena', ST_SetSRID(ST_MakePoint(47.532186751745364, -18.989767923702164), 4326)),
(3, 'Galana', ST_SetSRID(ST_MakePoint(47.532383836875, -18.987362884410643), 4326)),
(4, 'Boulangerie', ST_SetSRID(ST_MakePoint(47.532825463434804, -18.984075889399275), 4326)),
(5, 'Pharmacie', ST_SetSRID(ST_MakePoint(47.531817783382856, -18.974095734841153), 4326)),
(6, 'Andoharanofotsy', ST_SetSRID(ST_MakePoint(47.5329382524463, -18.97787453298064), 4326)),
(7, 'Malaza', ST_SetSRID(ST_MakePoint(47.52993359546712, -18.97107298192147), 4326)),
(8, 'Ambany Atsimo', ST_SetSRID(ST_MakePoint(47.531338346843334, -18.968540000589364), 4326)),
(9, 'Magasin M', ST_SetSRID(ST_MakePoint(47.529595976687794, -18.963582012691816), 4326)),
(10, 'Andohavato', ST_SetSRID(ST_MakePoint(47.52924845953833, -18.95632040655983), 4326)),
(11, 'Tetezan''I Tanjombato', ST_SetSRID(ST_MakePoint(47.529868049814596, -18.955145890126563), 4326)),
(12, 'Barage', ST_SetSRID(ST_MakePoint(47.52778614972066, -18.952620694325184), 4326)),
(13, 'Fasika', ST_SetSRID(ST_MakePoint(47.5257625474618, -18.948452229901058), 4326)),
(14, 'Ankadimbahoaka', ST_SetSRID(ST_MakePoint(47.523574946670834, -18.943032000361207), 4326)),
(15, 'Gare Soanierana', ST_SetSRID(ST_MakePoint(47.5223171623387, -18.940191092882305), 4326)),
(16, 'Tetezana - Soanierana', ST_SetSRID(ST_MakePoint(47.52148403914081, -18.938134490170565), 4326)),
(17, 'Tsena Namontana', ST_SetSRID(ST_MakePoint(47.51196162953417, -18.935461024186655), 4326)),
(18, 'Rond-Point Namontana', ST_SetSRID(ST_MakePoint(47.50985706546555, -18.93559853482395), 4326)),
(19, 'Mpivaro-Kena Nosybe', ST_SetSRID(ST_MakePoint(47.509726861494286, -18.930457574268672), 4326)),
(20, 'Akoho Anosibe', ST_SetSRID(ST_MakePoint(47.512780020530606, -18.92638450368764), 4326)),
(21, 'Tsena Anosibe', ST_SetSRID(ST_MakePoint(47.51229474665079, -18.925824213639768), 4326)),
(22, 'Sonatra - Anosibe', ST_SetSRID(ST_MakePoint(47.50854018338151, -18.92258927412193), 4326)),
(23, 'Shell Andavamamba', ST_SetSRID(ST_MakePoint(47.50824305537744, -18.921399740158495), 4326)),
(24, 'Andavamamba', ST_SetSRID(ST_MakePoint(47.509825000430105, -18.916344898746125), 4326)),
(25, 'Fokontany - Andavamamba', ST_SetSRID(ST_MakePoint(47.50894120659439, -18.91259920918786), 4326)),
(26, 'Trente - 67ha', ST_SetSRID(ST_MakePoint(47.50585902783202, -18.910414348788294), 4326)),
(27, 'NY Havana 67ha', ST_SetSRID(ST_MakePoint(47.50802588563883, -18.904911788115474), 4326));

-- Bus 128 (IDs: 28 à 61)
INSERT INTO arret (id, nom, point) VALUES
(28, 'Ankatso', ST_SetSRID(ST_MakePoint(47.5553, -18.9162), 4326)),
(29, 'Akoho - Tsiadana', ST_SetSRID(ST_MakePoint(47.5491, -18.9195), 4326)),
(30, 'Pave Tsiadana', ST_SetSRID(ST_MakePoint(47.5448, -18.9204), 4326)),
(31, 'Sampanana - Ampasanimalo', ST_SetSRID(ST_MakePoint(47.5419, -18.9172), 4326)),
(32, 'Buffare Ampasanimalo', ST_SetSRID(ST_MakePoint(47.5392, -18.9158), 4326)),
(33, 'Ampasanimalo (Gazety)', ST_SetSRID(ST_MakePoint(47.5385, -18.9135), 4326)),
(34, 'Ankorahotra', ST_SetSRID(ST_MakePoint(47.5368, -18.9102), 4326)),
(35, 'Scav - Antsakaviro', ST_SetSRID(ST_MakePoint(47.5327, -18.9081), 4326)),
(36, 'Mascotte (Vers Anosy) - Antsahabe', ST_SetSRID(ST_MakePoint(47.5284, -18.9095), 4326)),
(37, 'Ambohijatovo (Vers Anosy)', ST_SetSRID(ST_MakePoint(47.5242, -18.9114), 4326)),
(38, 'Zaridaina - Mahamasina', ST_SetSRID(ST_MakePoint(47.5244, -18.9152), 4326)),
(39, 'Gerb''Or - Mahamasina', ST_SetSRID(ST_MakePoint(47.5255, -18.9179), 4326)),
(40, 'College De France', ST_SetSRID(ST_MakePoint(47.5262, -18.9213), 4326)),
(41, 'Ankadilalana', ST_SetSRID(ST_MakePoint(47.5257, -18.9248), 4326)),
(42, 'Tsimbazaza Parc', ST_SetSRID(ST_MakePoint(47.5266, -18.9284), 4326)),
(43, 'Tsimbazaza Terminus', ST_SetSRID(ST_MakePoint(47.5260, -18.9318), 4326)),
(44, '115 - Tsimbazaza', ST_SetSRID(ST_MakePoint(47.5278, -18.9311), 4326)),
(45, 'Maroroho', ST_SetSRID(ST_MakePoint(47.5306, -18.9304), 4326)),
(46, 'Epp - Manakambahiny', ST_SetSRID(ST_MakePoint(47.5342, -18.9298), 4326)),
(47, 'Restaurant Miangoly', ST_SetSRID(ST_MakePoint(47.5361, -18.9283), 4326)),
(48, 'Cite Manakabahiny', ST_SetSRID(ST_MakePoint(47.5375, -18.9264), 4326)),
(49, 'Manakambahiny', ST_SetSRID(ST_MakePoint(47.5385, -18.9250), 4326)),
(50, 'Bird - Manakambahiny', ST_SetSRID(ST_MakePoint(47.5392, -18.9240), 4326)),
(51, 'Quartz - Manakambahiny', ST_SetSRID(ST_MakePoint(47.5401, -18.9234), 4326)),
(52, 'Arret Bus 6', ST_SetSRID(ST_MakePoint(47.5414, -18.9247), 4326)),
(53, 'Volosarika', ST_SetSRID(ST_MakePoint(47.5434, -18.9253), 4326)),
(54, 'Ambanidia', ST_SetSRID(ST_MakePoint(47.5451, -18.9237), 4326)),
(55, 'Rond - Point Ambanidia', ST_SetSRID(ST_MakePoint(47.5457, -18.9221), 4326)),
(56, 'Tsena - Ambanidia', ST_SetSRID(ST_MakePoint(47.5454, -18.9205), 4326)),
(57, 'Poste - Ambanidia', ST_SetSRID(ST_MakePoint(47.5447, -18.9189), 4326)),
(58, 'Tfm - Ambanidia', ST_SetSRID(ST_MakePoint(47.5433, -18.9161), 4326)),
(59, 'Pharmacie Hanitra - Antsakaviro', ST_SetSRID(ST_MakePoint(47.5347, -18.9098), 4326)),
(60, 'Gazety Ankorahotra', ST_SetSRID(ST_MakePoint(47.5372, -18.9113), 4326)),
(61, 'Shoprite Ampasanimalo', ST_SetSRID(ST_MakePoint(47.5380, -18.9126), 4326));

-- =====================================================
-- 2. INSERTION DES BUS
-- =====================================================

INSERT INTO bus (id, nom) VALUES
(1, 'Bus 172'),
(2, 'Bus 128');

-- =====================================================
-- 3. INSERTION DES TRAJETS (avec id_bus au lieu de ligne_id)
-- =====================================================

-- Bus 172 (id_bus = 1)
INSERT INTO trajet (id, id_bus, description) VALUES
(1, 1, 'Fiadanamanga → 67ha (Aller)'),
(2, 1, '67ha → Fiadanamanga (Retour)');

-- Bus 128 (id_bus = 2)
INSERT INTO trajet (id, id_bus, description) VALUES
(3, 2, 'Ankatso → Tsimbazaza (Aller)'),
(4, 2, 'Tsimbazaza → Ankatso (Retour)');

-- =====================================================
-- 4. ASSOCIATION ARRÊTS ↔ TRAJETS (avec id_trajet)
-- =====================================================

-- 4.1 BUS 172 - ALLER (trajet_id = 1)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
(1, 1, 1),   -- Fiadanamanga
(1, 2, 2),   -- Mandrimena
(1, 3, 3),   -- Galana
(1, 4, 4),   -- Boulangerie
(1, 5, 5),   -- Pharmacie
(1, 6, 6),   -- Andoharanofotsy
(1, 7, 7),   -- Malaza
(1, 8, 8),   -- Ambany Atsimo
(1, 9, 9),   -- Magasin M
(1, 10, 10), -- Andohavato
(1, 11, 11), -- Tetezan'I Tanjombato
(1, 12, 12), -- Barage
(1, 13, 13), -- Fasika
(1, 14, 14), -- Ankadimbahoaka
(1, 15, 15), -- Gare Soanierana
(1, 16, 16), -- Tetezana - Soanierana
(1, 17, 17), -- Tsena Namontana
(1, 18, 18), -- Rond-Point Namontana
(1, 19, 19), -- Mpivaro-Kena Nosybe
(1, 20, 20), -- Akoho Anosibe
(1, 21, 21), -- Tsena Anosibe
(1, 22, 22), -- Sonatra - Anosibe
(1, 23, 23), -- Shell Andavamamba
(1, 24, 24), -- Andavamamba
(1, 25, 25), -- Fokontany - Andavamamba
(1, 26, 26), -- Trente - 67ha
(1, 27, 27); -- NY Havana 67ha

-- 4.2 BUS 172 - RETOUR (trajet_id = 2)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
(2, 27, 1),  -- NY Havana 67ha
(2, 26, 2),  -- Trente - 67ha
(2, 25, 3),  -- Fokontany - Andavamamba
(2, 24, 4),  -- Andavamamba
(2, 23, 5),  -- Shell Andavamamba
(2, 22, 6),  -- Sonatra - Anosibe
(2, 21, 7),  -- Tsena Anosibe
(2, 20, 8),  -- Akoho Anosibe
(2, 19, 9),  -- Mpivaro-Kena Nosybe
(2, 18, 10), -- Rond-Point Namontana
(2, 17, 11), -- Tsena Namontana
(2, 16, 12), -- Tetezana - Soanierana
(2, 15, 13), -- Gare Soanierana
(2, 14, 14), -- Ankadimbahoaka
(2, 13, 15), -- Fasika
(2, 12, 16), -- Barage
(2, 11, 17), -- Tetezan'I Tanjombato
(2, 10, 18), -- Andohavato
(2, 9, 19),  -- Magasin M
(2, 8, 20),  -- Ambany Atsimo
(2, 7, 21),  -- Malaza
(2, 6, 22),  -- Andoharanofotsy
(2, 5, 23),  -- Pharmacie
(2, 4, 24),  -- Boulangerie
(2, 3, 25),  -- Galana
(2, 2, 26),  -- Mandrimena
(2, 1, 27); -- Fiadanamanga

-- 4.3 BUS 128 - ALLER (trajet_id = 3)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
(3, 28, 1),  -- Ankatso
(3, 29, 2),  -- Akoho - Tsiadana
(3, 30, 3),  -- Pave Tsiadana
(3, 31, 4),  -- Sampanana - Ampasanimalo
(3, 32, 5),  -- Buffare Ampasanimalo
(3, 33, 6),  -- Ampasanimalo (Gazety)
(3, 34, 7),  -- Ankorahotra
(3, 35, 8),  -- Scav - Antsakaviro
(3, 36, 9),  -- Mascotte (Vers Anosy) - Antsahabe
(3, 37, 10), -- Ambohijatovo (Vers Anosy)
(3, 38, 11), -- Zaridaina - Mahamasina
(3, 39, 12), -- Gerb'Or - Mahamasina
(3, 40, 13), -- College De France
(3, 41, 14), -- Ankadilalana
(3, 42, 15), -- Tsimbazaza Parc
(3, 43, 16), -- Tsimbazaza Terminus
(3, 44, 17), -- 115 - Tsimbazaza
(3, 45, 18), -- Maroroho
(3, 46, 19), -- Epp - Manakambahiny
(3, 47, 20), -- Restaurant Miangoly
(3, 48, 21), -- Cite Manakabahiny
(3, 49, 22), -- Manakambahiny
(3, 50, 23), -- Bird - Manakambahiny
(3, 51, 24), -- Quartz - Manakambahiny
(3, 52, 25), -- Arret Bus 6
(3, 53, 26), -- Volosarika
(3, 54, 27), -- Ambanidia
(3, 55, 28), -- Rond - Point Ambanidia
(3, 56, 29), -- Tsena - Ambanidia
(3, 57, 30), -- Poste - Ambanidia
(3, 58, 31), -- Tfm - Ambanidia
(3, 59, 32), -- Pharmacie Hanitra - Antsakaviro
(3, 60, 33), -- Gazety Ankorahotra
(3, 61, 34); -- Shoprite Ampasanimalo

-- 4.4 BUS 128 - RETOUR (trajet_id = 4)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
(4, 61, 1),  -- Shoprite Ampasanimalo
(4, 60, 2),  -- Gazety Ankorahotra
(4, 59, 3),  -- Pharmacie Hanitra - Antsakaviro
(4, 58, 4),  -- Tfm - Ambanidia
(4, 57, 5),  -- Poste - Ambanidia
(4, 56, 6),  -- Tsena - Ambanidia
(4, 55, 7),  -- Rond - Point Ambanidia
(4, 54, 8),  -- Ambanidia
(4, 53, 9),  -- Volosarika
(4, 52, 10), -- Arret Bus 6
(4, 51, 11), -- Quartz - Manakambahiny
(4, 50, 12), -- Bird - Manakambahiny
(4, 49, 13), -- Manakambahiny
(4, 48, 14), -- Cite Manakabahiny
(4, 47, 15), -- Restaurant Miangoly
(4, 46, 16), -- Epp - Manakambahiny
(4, 45, 17), -- Maroroho
(4, 44, 18), -- 115 - Tsimbazaza
(4, 43, 19), -- Tsimbazaza Terminus
(4, 42, 20), -- Tsimbazaza Parc
(4, 41, 21), -- Ankadilalana
(4, 40, 22), -- College De France
(4, 39, 23), -- Gerb'Or - Mahamasina
(4, 38, 24), -- Zaridaina - Mahamasina
(4, 37, 25), -- Ambohijatovo (Vers Anosy)
(4, 36, 26), -- Mascotte (Vers Anosy) - Antsahabe
(4, 35, 27), -- Scav - Antsakaviro
(4, 34, 28), -- Ankorahotra
(4, 33, 29), -- Ampasanimalo (Gazety)
(4, 32, 30), -- Buffare Ampasanimalo
(4, 31, 31), -- Sampanana - Ampasanimalo
(4, 30, 32), -- Pave Tsiadana
(4, 29, 33), -- Akoho - Tsiadana
(4, 28, 34); -- Ankatso
