<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 4 : Création de la table trajet_arret (table de liaison)
 *
 * - Un arrêt ne peut apparaître qu'une fois par trajet : UNIQUE(id_trajet, id_arret)
 * - L'ordre des arrêts dans un trajet est unique : UNIQUE(id_trajet, ordre)
 * - Cascade delete sur trajet et arret
 */
class CreateTrajetArretTable extends Migration
{
    public function up(): void
    {
        $this->db->query('
            CREATE TABLE IF NOT EXISTS trajet_arret (
                id        SERIAL PRIMARY KEY,
                id_trajet INTEGER NOT NULL REFERENCES trajet(id) ON DELETE CASCADE,
                id_arret  INTEGER NOT NULL REFERENCES arret(id)  ON DELETE CASCADE,
                ordre     INTEGER NOT NULL,
                UNIQUE (id_trajet, id_arret),
                UNIQUE (id_trajet, ordre)
            );
        ');

        $this->db->query('
            CREATE INDEX IF NOT EXISTS idx_trajet_arret_trajet
            ON trajet_arret(id_trajet);
        ');

        $this->db->query('
            CREATE INDEX IF NOT EXISTS idx_trajet_arret_arret
            ON trajet_arret(id_arret);
        ');
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS trajet_arret CASCADE;');
    }
}
