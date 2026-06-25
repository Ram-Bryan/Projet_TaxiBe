<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 1 : Création de la table bus
 */
class CreateBusTable extends Migration
{
    public function up(): void
    {
        $this->db->query('
            CREATE TABLE IF NOT EXISTS bus (
                id  SERIAL PRIMARY KEY,
                nom VARCHAR(100) NOT NULL
            );
        ');
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS bus CASCADE;');
    }
}
