<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 3 : Création de la table trajet
 *
 * Un trajet est associé à un bus (FK id_bus → bus.id).
 * La suppression d'un bus entraîne la suppression en cascade de ses trajets.
 */
class CreateTrajetTable extends Migration
{
    public function up(): void
    {
        $this->db->query('
            CREATE TABLE IF NOT EXISTS trajet (
                id          SERIAL PRIMARY KEY,
                id_bus      INTEGER NOT NULL REFERENCES bus(id) ON DELETE CASCADE,
                description TEXT
            );
        ');

        $this->db->query('
            CREATE INDEX IF NOT EXISTS idx_trajet_bus
            ON trajet(id_bus);
        ');
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS trajet CASCADE;');
    }
}
