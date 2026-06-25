<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * TaxiBeSeeder
 *
 * Insère des données de test réalistes pour Antananarivo :
 * - 5 bus (Taxi-Be)
 * - 10 arrêts avec coordonnées GPS réelles (quartiers d'Antananarivo)
 * - 2 trajets
 * - Liaisons trajet_arret avec ordre cohérent
 *
 * Usage : php spark db:seed TaxiBeSeeder
 */
class TaxiBeSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // 1. BUS
        // =====================================================================
        $bus = [
            ['nom' => 'Taxi-Be 001 — Analakely ↔ Ambohipo'],
            ['nom' => 'Taxi-Be 002 — Isotry ↔ Ankorondrano'],
            ['nom' => 'Taxi-Be 003 — Mahamasina ↔ Ambohibao'],
            ['nom' => 'Taxi-Be 004 — Itaosy ↔ Behoririka'],
            ['nom' => 'Taxi-Be 005 — Tana-Centre ↔ Antsahamanitra'],
        ];

        foreach ($bus as $b) {
            $this->db->table('bus')->insert($b);
        }

        echo "✓ 5 bus insérés\n";

        // Récupère les IDs générés
        $busIds = array_column(
            $this->db->query('SELECT id FROM bus ORDER BY id ASC')->getResultArray(),
            'id'
        );

        // =====================================================================
        // 2. ARRÊTS (coordonnées GPS réelles — Antananarivo, Madagascar)
        //    Format PostGIS : ST_MakePoint(longitude, latitude)
        // =====================================================================
        $arrets = [
            // [nom, latitude, longitude]
            ['Analakely — Place du 13 Mai',          -18.91449,  47.53635],
            ['Behoririka — Rue Rainandriamampandry',  -18.91980,  47.53230],
            ['Isotry — Marché Isotry',                -18.92710,  47.52580],
            ['Ampefiloha — Faculté de Droit',         -18.90630,  47.52990],
            ['Ankorondrano — Hypermarché Score',      -18.89560,  47.53520],
            ['Tana-Est — Ankadifotsy',                -18.91100,  47.55100],
            ['Mahamasina — Stade Municipal',          -18.92580,  47.53900],
            ['Antsahamanitra — Campus Université',    -18.89830,  47.52780],
            ['Ambohibao — Rond-point',                -18.88200,  47.51400],
            ['Itaosy — Marché Itaosy',                -18.94500,  47.49900],
        ];

        $arretIds = [];
        foreach ($arrets as [$nom, $lat, $lng]) {
            $result = $this->db->query(
                "INSERT INTO arret (nom, point)
                 VALUES (?, ST_SetSRID(ST_MakePoint(?, ?), 4326))
                 RETURNING id",
                [$nom, $lng, $lat]
            );
            $arretIds[$nom] = (int) $result->getRow()->id;
        }

        echo "✓ 10 arrêts insérés avec coordonnées GPS\n";

        // =====================================================================
        // 3. TRAJETS
        // =====================================================================
        $trajets = [
            [
                'id_bus'      => $busIds[0], // Taxi-Be 001
                'description' => 'Trajet Analakely → Behoririka → Isotry → Itaosy (ligne Sud-Ouest)',
            ],
            [
                'id_bus'      => $busIds[1], // Taxi-Be 002
                'description' => 'Trajet Isotry → Ampefiloha → Ankorondrano → Ambohibao (ligne Nord)',
            ],
        ];

        foreach ($trajets as $t) {
            $this->db->table('trajet')->insert($t);
        }

        $trajetIds = array_column(
            $this->db->query('SELECT id FROM trajet ORDER BY id ASC')->getResultArray(),
            'id'
        );

        echo "✓ 2 trajets insérés\n";

        // =====================================================================
        // 4. LIAISONS TRAJET ↔ ARRÊTS (avec ordre)
        // =====================================================================

        // Trajet 1 : Analakely → Behoririka → Isotry → Mahamasina → Itaosy
        $trajetArret1 = [
            ['id_trajet' => $trajetIds[0], 'id_arret' => $arretIds['Analakely — Place du 13 Mai'],         'ordre' => 1],
            ['id_trajet' => $trajetIds[0], 'id_arret' => $arretIds['Behoririka — Rue Rainandriamampandry'], 'ordre' => 2],
            ['id_trajet' => $trajetIds[0], 'id_arret' => $arretIds['Isotry — Marché Isotry'],               'ordre' => 3],
            ['id_trajet' => $trajetIds[0], 'id_arret' => $arretIds['Mahamasina — Stade Municipal'],         'ordre' => 4],
            ['id_trajet' => $trajetIds[0], 'id_arret' => $arretIds['Itaosy — Marché Itaosy'],               'ordre' => 5],
        ];

        // Trajet 2 : Isotry → Ampefiloha → Ankorondrano → Antsahamanitra → Ambohibao
        $trajetArret2 = [
            ['id_trajet' => $trajetIds[1], 'id_arret' => $arretIds['Isotry — Marché Isotry'],              'ordre' => 1],
            ['id_trajet' => $trajetIds[1], 'id_arret' => $arretIds['Ampefiloha — Faculté de Droit'],       'ordre' => 2],
            ['id_trajet' => $trajetIds[1], 'id_arret' => $arretIds['Ankorondrano — Hypermarché Score'],    'ordre' => 3],
            ['id_trajet' => $trajetIds[1], 'id_arret' => $arretIds['Antsahamanitra — Campus Université'],  'ordre' => 4],
            ['id_trajet' => $trajetIds[1], 'id_arret' => $arretIds['Ambohibao — Rond-point'],              'ordre' => 5],
        ];

        foreach (array_merge($trajetArret1, $trajetArret2) as $ta) {
            $this->db->table('trajet_arret')->insert($ta);
        }

        echo "✓ 10 liaisons trajet_arret insérées\n";
        echo "\n✅ TaxiBeSeeder terminé avec succès !\n";
    }
}
