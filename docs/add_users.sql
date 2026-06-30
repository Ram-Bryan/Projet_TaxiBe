-- ============================================================================
-- Ajout de la table utilisateur pour l'authentification
-- ============================================================================

\c taxibe

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
