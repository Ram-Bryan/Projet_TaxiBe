<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 0 : Activation de l'extension PostGIS
 * Doit être exécutée en premier (nécessite les droits superuser postgres)
 */
class CreatePostgisExtension extends Migration
{
    public function up(): void
    {
        $this->db->query('CREATE EXTENSION IF NOT EXISTS postgis;');
    }

    public function down(): void
    {
        // On ne supprime pas PostGIS car d'autres bases/schémas pourraient l'utiliser
        // $this->db->query('DROP EXTENSION IF EXISTS postgis;');
    }
}
