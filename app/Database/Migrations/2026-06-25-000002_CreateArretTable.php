<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 2 : Création de la table arret
 *
 * Le champ `point` est de type GEOMETRY(Point, 4326) — coordonnées WGS84 (lat/lng).
 * Un index spatial GIST est créé pour accélérer les requêtes ST_DWithin / ST_Distance.
 *
 * IMPORTANT : La migration PostGIS (000000) doit avoir été exécutée avant.
 */
class CreateArretTable extends Migration
{
    public function up(): void
    {
        // GEOMETRY n'est pas supporté par le forge CI4, on utilise $db->query()
        $this->db->query('
            CREATE TABLE IF NOT EXISTS arret (
                id    SERIAL PRIMARY KEY,
                nom   VARCHAR(150) NOT NULL,
                point GEOMETRY(Point, 4326) NOT NULL
            );
        ');

        // Index spatial GIST pour les requêtes de proximité
        $this->db->query('
            CREATE INDEX IF NOT EXISTS idx_arret_point
            ON arret USING GIST(point);
        ');
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS arret CASCADE;');
    }
}
