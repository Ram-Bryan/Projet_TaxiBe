-- ============================================================================
-- TaxiBe — Script complet (arrêts, bus, trajets, associations)
-- PostgreSQL + PostGIS
--
-- Utilisation :
--   psql -U postgres -f taxibe_final_complet.sql
-- ============================================================================

CREATE DATABASE taxibe;

-- \c taxibe

-- 1. Extension PostGIS
CREATE EXTENSION IF NOT EXISTS postgis;

-- 2. Nettoyage (ordre inverse des FK4)
DROP TABLE IF EXISTS historique_frais CASCADE;
DROP TABLE IF EXISTS frais            CASCADE;
DROP TABLE IF EXISTS trajet_arret     CASCADE;
DROP TABLE IF EXISTS trajet           CASCADE;
DROP TABLE IF EXISTS arret            CASCADE;
DROP TABLE IF EXISTS bus              CASCADE;
DROP TABLE IF EXISTS moyen            CASCADE;
DROP TABLE IF EXISTS utilisateur      CASCADE;

-- 3. Création des tables
CREATE TABLE utilisateur (
    id           SERIAL PRIMARY KEY,
    nom          VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role         VARCHAR(50) NOT NULL DEFAULT 'simple'
);

CREATE TABLE moyen (
    id      SERIAL PRIMARY KEY,
    nom     VARCHAR(50) NOT NULL,
    vitesse NUMERIC NOT NULL
);

CREATE TABLE frais (
    id      SERIAL PRIMARY KEY,
    montant NUMERIC NOT NULL
);

CREATE TABLE historique_frais (
    id              SERIAL PRIMARY KEY,
    id_frais        INTEGER NOT NULL REFERENCES frais(id) ON DELETE CASCADE,
    montant         NUMERIC NOT NULL,
    date_changement TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bus (
    id  SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
);

CREATE TABLE arret (
    id    SERIAL PRIMARY KEY,
    nom   VARCHAR(150) NOT NULL,
    point GEOMETRY(Point, 4326) NOT NULL
);
CREATE INDEX idx_arret_point ON arret USING GIST(point);

CREATE TABLE trajet (
    id          SERIAL PRIMARY KEY,
    id_bus      INTEGER NOT NULL REFERENCES bus(id) ON DELETE CASCADE,
    description TEXT
);
CREATE INDEX idx_trajet_bus ON trajet(id_bus);

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

-- 4. Données de base
INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES
    ('Administrateur', 'admin@taxibe.mg', '$2y$10$kCTgTmMQf1eEuJIUhou.C.oAKE3twCwEPkjpVD2n70yQd5c47cCaK', 'admin'),
    ('Utilisateur Test', 'user@taxibe.mg', '$2y$10$.YQY9/k8dFfmsm/heEuZBOWXmKqw2MqkP2vnTwAG.6wsUp3HjRI6K', 'simple')
ON CONFLICT (email) DO NOTHING;

INSERT INTO moyen (nom, vitesse) VALUES
    ('Marche', 5),
    ('Moto', 40),
    ('Bus', 30);

INSERT INTO frais (montant) VALUES (600);
INSERT INTO historique_frais (id_frais, montant) VALUES (1, 600);

-- 5. Insertion des arrêts (avec les coordonnées fournies)
INSERT INTO arret (id, nom, point) VALUES
(2, 'Garage Ambatomainty', ST_GeomFromText('POINT(47.537579 -18.891223)', 4326)),
(3, 'Anjanahary', ST_GeomFromText('POINT(47.536125 -18.893319)', 4326)),
(4, 'Mascar', ST_GeomFromText('POINT(47.533126 -18.89675)', 4326)),
(5, 'Behoririka', ST_GeomFromText('POINT(47.52869 -18.901927)', 4326)),
(6, 'SODIAT', ST_GeomFromText('POINT(47.524077 -18.900836)', 4326)),
(7, 'Shalimar', ST_GeomFromText('POINT(47.519833 -18.906038)', 4326)),
(8, 'SICAM', ST_GeomFromText('POINT(47.522859 -18.907261)', 4326)),
(9, 'Anosy', ST_GeomFromText('POINT(47.522038 -18.917989)', 4326)),
(10, 'Analakely-Tavoahangy', ST_GeomFromText('POINT(47.527167 -18.908017)', 4326)),
(11, 'Ambodifilao', ST_GeomFromText('POINT(47.525949 -18.905414)', 4326)),
(12, 'Andravoahangy-Solomaso', ST_GeomFromText('POINT(47.530841 -18.898588)', 4326)),
(13, 'Anjanahary - IIN', ST_GeomFromText('POINT(47.53598 -18.897933)', 4326)),
(14, 'Anjanahary-Debarras', ST_GeomFromText('POINT(47.537445 -18.894943)', 4326)),
(15, 'Ambatomainty-Jovena', ST_GeomFromText('POINT(47.537037 -18.892111)', 4326)),
(16, 'Analakely-Terminus', ST_GeomFromText('POINT(47.524281 -18.904723)', 4326)),
(17, 'Firaisana faha-4', ST_GeomFromText('POINT(47.521641 -18.919466)', 4326)),
(18, 'Toby-A', ST_GeomFromText('POINT(47.52074 -18.922856)', 4326)),
(19, 'Jirama', ST_GeomFromText('POINT(47.519023 -18.928158)', 4326)),
(20, 'Paraky', ST_GeomFromText('POINT(47.52001 -18.931634)', 4326)),
(21, 'Descourt', ST_GeomFromText('POINT(47.521931 -18.935247)', 4326)),
(22, 'Sampanan''ny Fasan-karana', ST_GeomFromText('POINT(47.522601 -18.941437)', 4326)),
(23, 'Ankadimbahoaka', ST_GeomFromText('POINT(47.524297 -18.944324)', 4326)),
(24, 'Fasika', ST_GeomFromText('POINT(47.526072 -18.949794)', 4326)),
(25, 'Andovato', ST_GeomFromText('POINT(47.529012 -18.954462)', 4326)),
(26, 'Analamanga', ST_GeomFromText('POINT(47.529404 -18.95783)', 4326)),
(27, 'Magasin M', ST_GeomFromText('POINT(47.529597 -18.963807)', 4326)),
(28, 'Ambany Atsimo', ST_GeomFromText('POINT(47.530348 -18.967805)', 4326)),
(29, 'Sampanan''ny Tongarivo', ST_GeomFromText('POINT(47.529425 -18.969712)', 4326)),
(30, 'Malaza', ST_GeomFromText('POINT(47.530385 -18.971843)', 4326)),
(31, 'Pharmacie', ST_GeomFromText('POINT(47.532864 -18.977068)', 4326)),
(32, 'Andoharanofotsy', ST_GeomFromText('POINT(47.532917 -18.977829)', 4326)),
(33, 'Boulangerie', ST_GeomFromText('POINT(47.53274 -18.983769)', 4326)),
(34, 'Galana', ST_GeomFromText('POINT(47.532359 -18.987573)', 4326)),
(35, 'Mandrimena', ST_GeomFromText('POINT(47.532917 -18.992194)', 4326)),
(36, 'Fiadanamanga', ST_GeomFromText('POINT(47.5359 -18.998205)', 4326)),
(37, 'Mandrimena - Retour', ST_GeomFromText('POINT(47.532896 -18.992017)', 4326)),
(38, 'Galana - Retour', ST_GeomFromText('POINT(47.532402 -18.987928)', 4326)),
(39, 'Boulangerie - Retour', ST_GeomFromText('POINT(47.532772 -18.983622)', 4326)),
(40, 'Andoharanofotsy - Retour', ST_GeomFromText('POINT(47.533003 -18.977707)', 4326)),
(41, 'Malaza - Retour', ST_GeomFromText('POINT(47.530611 -18.97197)', 4326)),
(42, 'Sampanan''ny Tongarivo - Retour', ST_GeomFromText('POINT(47.529666 -18.9696)', 4326)),
(43, 'Ambany Atsimo - Retour', ST_GeomFromText('POINT(47.530385 -18.967668)', 4326)),
(44, 'Magasin M - Retour', ST_GeomFromText('POINT(47.529661 -18.963518)', 4326)),
(45, 'Ankady', ST_GeomFromText('POINT(47.529699 -18.960885)', 4326)),
(46, 'Andovato - Retour', ST_GeomFromText('POINT(47.529221 -18.954665)', 4326)),
(47, 'Barrage', ST_GeomFromText('POINT(47.527987 -18.952792)', 4326)),
(48, 'Fasika - Retour', ST_GeomFromText('POINT(47.525847 -18.948566)', 4326)),
(49, 'Ankadimbahoaka', ST_GeomFromText('POINT(47.523599 -18.943005)', 4326)),
(50, 'Descourt - Retour', ST_GeomFromText('POINT(47.522451 -18.935501)', 4326)),
(51, 'Paraky - Retour', ST_GeomFromText('POINT(47.520171 -18.931599)', 4326)),
(52, 'Jirama - Retour', ST_GeomFromText('POINT(47.519254 -18.92893)', 4326)),
(53, 'Toby - Retour', ST_GeomFromText('POINT(47.520778 -18.923348)', 4326)),
(54, 'Anosy - Retour', ST_GeomFromText('POINT(47.522392 -18.917928)', 4326)),
(55, 'Gare Soanierana', ST_GeomFromText('POINT(47.522457 -18.940189)', 4326)),
(56, 'Tetezan''ny Namontana', ST_GeomFromText('POINT(47.521335 -18.93819)', 4326)),
(57, 'Tsenan''ny Namontana', ST_GeomFromText('POINT(47.514544 -18.935907)', 4326)),
(58, 'Rond point Namontana', ST_GeomFromText('POINT(47.510086 -18.935435)', 4326)),
(60, 'Mpivaro-kena Anosibe', ST_GeomFromText('POINT(47.509984 -18.930467)', 4326)),
(61, 'Akoho Anosibe', ST_GeomFromText('POINT(47.511567 -18.927793)', 4326)),
(62, 'Tsena Anosibe', ST_GeomFromText('POINT(47.514464 -18.925088)', 4326)),
(63, 'Sonatra - Anosibe', ST_GeomFromText('POINT(47.509174 -18.923028)', 4326)),
(64, 'Shell - Andavamamba', ST_GeomFromText('POINT(47.508321 -18.921181)', 4326)),
(66, 'Fokontany - Andavamamba', ST_GeomFromText('POINT(47.509813 -18.91633)', 4326)),
(67, 'Ny Havana - 67ha', ST_GeomFromText('POINT(47.50881 -18.91502)', 4326)),
(68, 'Ankatso', ST_GeomFromText('POINT(47.549316 -18.915259)', 4326)),
(69, 'Fiangonana - Ambatoroka', ST_GeomFromText('POINT(47.541651 -18.924424)', 4326)),
(70, 'Sampanana - Ambohimiandra', ST_GeomFromText('POINT(47.543587 -18.927529)', 4326)),
(71, 'Jirama - Mandroseza', ST_GeomFromText('POINT(47.548528 -18.932324)', 4326)),
(72, 'Ankadindratombo', ST_GeomFromText('POINT(47.55474 -18.939306)', 4326)),
(73, 'Mandroseza', ST_GeomFromText('POINT(47.551028 -18.935206)', 4326)),
(75, 'Domaine - Alasora', ST_GeomFromText('POINT(47.556188 -18.945999)', 4326)),
(76, 'Boulevard', ST_GeomFromText('POINT(47.551864 -18.949662)', 4326)),
(77, 'Tetezana', ST_GeomFromText('POINT(47.541661 -18.949835)', 4326)),
(79, 'Pompe - Ambanidia', ST_GeomFromText('POINT(47.537794 -18.9184)', 4326)),
(80, 'Antsakaviro', ST_GeomFromText('POINT(47.536839 -18.914107)', 4326)),
(81, 'Soarano', ST_GeomFromText('POINT(47.524012 -18.903602)', 4326));

-- 6. Bus
INSERT INTO bus (id, nom) VALUES
(1, '186'),
(2, '137'),
(3, '172'),
(4, '187');

-- 7. Trajets (descriptions corrigées : Aller et Retour)
INSERT INTO trajet (id, id_bus, description) VALUES
(1, 1, '186-Aller'),
(3, 2, '137-Aller'),
(4, 2, '137-Retour'),
(5, 3, '172-Aller'),
(7, 4, '187-Aller'),
(8, 1, '186-Retour');

-- 8. Associations trajet ↔ arrêt (ordre de passage)
INSERT INTO trajet_arret (id_trajet, id_arret, ordre) VALUES
-- 186-Aller
(1, 2, 1),
(1, 3, 2),
(1, 4, 3),
(1, 5, 4),
(1, 6, 5),
(1, 7, 6),
(1, 8, 7),
(1, 9, 8),

-- 137-Aller
(3, 16, 1),
(3, 7, 2),
(3, 8, 3),
(3, 9, 4),
(3, 17, 5),
(3, 18, 6),
(3, 19, 7),
(3, 20, 8),
(3, 21, 9),
(3, 22, 10),
(3, 23, 11),
(3, 24, 12),
(3, 25, 13),
(3, 26, 14),
(3, 27, 15),
(3, 28, 16),
(3, 29, 17),
(3, 30, 18),
(3, 31, 19),
(3, 32, 20),
(3, 33, 21),
(3, 34, 22),
(3, 35, 23),
(3, 36, 24),

-- 137-Retour
(4, 36, 1),
(4, 37, 2),
(4, 38, 3),
(4, 39, 4),
(4, 40, 5),
(4, 41, 6),
(4, 42, 7),
(4, 43, 8),
(4, 44, 9),
(4, 45, 10),
(4, 46, 11),
(4, 47, 12),
(4, 48, 13),
(4, 49, 14),
(4, 50, 15),
(4, 51, 16),
(4, 52, 17),
(4, 53, 18),
(4, 54, 19),
(4, 10, 20),
(4, 16, 21),

-- 172-Aller
(5, 36, 1),
(5, 37, 2),
(5, 38, 3),
(5, 39, 4),
(5, 40, 5),
(5, 41, 6),
(5, 42, 7),
(5, 43, 8),
(5, 44, 9),
(5, 45, 10),
(5, 46, 11),
(5, 47, 12),
(5, 48, 13),
(5, 49, 14),
(5, 55, 15),
(5, 56, 16),
(5, 57, 17),
(5, 58, 18),
(5, 60, 19),
(5, 61, 20),
(5, 62, 21),
(5, 63, 22),
(5, 64, 23),
(5, 66, 24),
(5, 67, 25),

-- 187-Aller
(7, 68, 1),
(7, 80, 2),
(7, 79, 3),
(7, 69, 4),
(7, 70, 5),
(7, 71, 6),
(7, 73, 7),
(7, 72, 8),
(7, 75, 9),
(7, 76, 10),
(7, 77, 11),
(7, 24, 12),
(7, 25, 13),
(7, 26, 14),
(7, 27, 15),
(7, 28, 16),
(7, 29, 17),
(7, 30, 18),
(7, 31, 19),
(7, 32, 20),
(7, 33, 21),
(7, 34, 22),
(7, 35, 23),
(7, 36, 24),

-- 186-Retour
(8, 54, 1),
(8, 10, 2),
(8, 81, 3),
(8, 5, 4),
(8, 12, 5),
(8, 4, 6),
(8, 13, 7),
(8, 14, 8),
(8, 15, 9);

SELECT setval('arret_id_seq',    (SELECT MAX(id) FROM arret));
SELECT setval('bus_id_seq',      (SELECT MAX(id) FROM bus));
SELECT setval('trajet_id_seq',   (SELECT MAX(id) FROM trajet));
SELECT setval('frais_id_seq',    (SELECT MAX(id) FROM frais));
SELECT setval('utilisateur_id_seq', (SELECT MAX(id) FROM utilisateur));

-- ============================================================================
-- Vérifications (optionnel)
-- ============================================================================
-- SELECT COUNT(*) FROM arret; -- 77 lignes (IDs 2-81 sauf 59 et 78)
-- SELECT id_trajet, COUNT(*), MIN(ordre), MAX(ordre) FROM trajet_arret GROUP BY id_trajet;
-- SELECT id_arret FROM trajet_arret EXCEPT SELECT id FROM arret; -- doit être vide

-- ============================================================================
-- FIN
-- ============================================================================