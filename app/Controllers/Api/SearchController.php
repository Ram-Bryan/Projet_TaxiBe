<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ArretModel;
use App\Models\TrajetModel;
use App\Models\FraisModel;

/**
 * SearchController
 *
 * Recherche des combinaisons de trajets (avec correspondances) entre
 * un arrêt de départ et un arrêt d'arrivée.
 *
 * Algorithme BFS limité à 3 bus maximum (2 correspondances).
 * Les résultats sont triés par prix croissant.
 */
class SearchController extends BaseController
{
    public function search()
    {
        $data = $this->request->getJSON(true);
        if (
            !isset($data['lat_dep']) || !isset($data['lng_dep']) ||
            !isset($data['lat_arr']) || !isset($data['lng_arr'])
        ) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'Paramètres invalides (lat_dep, lng_dep, lat_arr, lng_arr requis)'
            ]);
        }

        $latDep = (float)$data['lat_dep'];
        $lngDep = (float)$data['lng_dep'];
        $latArr = (float)$data['lat_arr'];
        $lngArr = (float)$data['lng_arr'];

        $arretModel  = new ArretModel();
        $trajetModel = new TrajetModel();
        $fraisModel  = new FraisModel();

        // --- Récupérer le tarif actuel depuis la DB ---
        $prixParTrajet = $fraisModel->getMontantActuel();

        // --- Trouver l'arrêt le plus proche du départ ---
        $nearestDep = $arretModel->findNearest($latDep, $lngDep, 1);
        if (empty($nearestDep)) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'Aucun arrêt trouvé à proximité du départ'
            ]);
        }
        $arretDepart = $nearestDep[0];
        $idDepart    = (int)$arretDepart['id'];

        // --- Trouver l'arrêt le plus proche de l'arrivée ---
        $nearestArr = $arretModel->findNearest($latArr, $lngArr, 1);
        if (empty($nearestArr)) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'Aucun arrêt trouvé à proximité de l\'arrivée'
            ]);
        }
        $arretArrivee = $nearestArr[0];
        $idArrivee    = (int)$arretArrivee['id'];

        // Si les deux arrêts sont les mêmes, pas besoin de bus
        if ($idDepart === $idArrivee) {
            return $this->response->setJSON([
                'status'        => 'success',
                'arret_depart'  => $this->formatArret($arretDepart),
                'arret_arrivee' => $this->formatArret($arretArrivee),
                'trajets'       => [],
                'prix_unitaire' => $prixParTrajet,
            ]);
        }

        // --- Construire le graphe : tous les trajets avec leurs arrêts ---
        $allTrajets = $trajetModel->getAllTrajetsWithArrets();

        // Index : id_arret -> liste de trajets qui passent par cet arrêt
        // Pour chaque trajet on garde aussi l'ordre de chaque arrêt
        // Structure: $trajetsByArret[id_arret] = [ [trajet_id, ordre], ... ]
        $trajetsByArret = [];
        $trajetIndex    = []; // id_trajet -> trajet complet
        foreach ($allTrajets as $trajet) {
            $trajetIndex[$trajet['id']] = $trajet;
            foreach ($trajet['arrets'] as $arret) {
                $trajetsByArret[$arret['id_arret']][] = [
                    'trajet_id' => $trajet['id'],
                    'ordre'     => (int)$arret['ordre'],
                ];
            }
        }

        // --- BFS pour trouver toutes les combinaisons (max 3 bus) ---
        $combinaisons = $this->findCombinations(
            $idDepart,
            $idArrivee,
            $trajetsByArret,
            $trajetIndex,
            3 // max 3 bus
        );

        // --- Calculer le prix de chaque combinaison et trier ---
        $results = [];
        foreach ($combinaisons as $combo) {
            $nbBus = count($combo['legs']); // nombre de bus utilisés
            $prix  = $nbBus * $prixParTrajet;

            $results[] = [
                'legs'          => $combo['legs'],         // tableau des segments de trajet
                'correspondances' => $combo['correspondances'], // arrêts de changement
                'nb_bus'        => $nbBus,
                'prix_total'    => $prix,
                // Pour la compatibilité avec le frontend : résumé textuel
                'id'            => implode('-', array_column($combo['legs'], 'id')),
                'nom_bus'       => implode(' ➔ ', array_column($combo['legs'], 'nom_bus')),
                'description'   => $this->buildDescription($combo),
                'type'          => $nbBus === 1 ? 'direct' : 'correspondance',
            ];
        }

        // Tri par prix croissant, puis par nombre de bus
        usort($results, function ($a, $b) {
            if ($a['prix_total'] !== $b['prix_total']) {
                return $a['prix_total'] <=> $b['prix_total'];
            }
            return $a['nb_bus'] <=> $b['nb_bus'];
        });

        return $this->response->setJSON([
            'status'        => 'success',
            'arret_depart'  => $this->formatArret($arretDepart),
            'arret_arrivee' => $this->formatArret($arretArrivee),
            'trajets'       => $results,
            'prix_unitaire' => $prixParTrajet,
        ]);
    }

    // =========================================================================
    // Algorithme BFS
    // =========================================================================

    /**
     * Trouve toutes les combinaisons de trajets (BFS, max $maxBus bus).
     *
     * Un "leg" est un segment de trajet : on prend un bus de l'arrêt A à l'arrêt B.
     *
     * @param int   $idDepart      Arrêt de départ
     * @param int   $idArrivee     Arrêt d'arrivée final
     * @param array $trajetsByArret Index [id_arret => [{trajet_id, ordre}]]
     * @param array $trajetIndex    Index [trajet_id => trajet complet]
     * @param int   $maxBus        Nombre maximum de bus (profondeur BFS)
     * @return array               Tableau de combinaisons
     */
    private function findCombinations(
        int $idDepart,
        int $idArrivee,
        array $trajetsByArret,
        array $trajetIndex,
        int $maxBus
    ): array {
        $found = [];

        // File BFS : chaque état = [arrêt courant, legs déjà utilisés, trajets déjà utilisés (pour éviter cycles)]
        $queue = [[
            'current_arret'   => $idDepart,
            'legs'            => [],
            'correspondances' => [],
            'used_trajets'    => [],
        ]];

        while (!empty($queue)) {
            $state = array_shift($queue);

            // Si on a déjà atteint le maximum de bus, on ne continue pas
            if (count($state['legs']) >= $maxBus) {
                continue;
            }

            $currentArret = $state['current_arret'];

            // Trajets qui passent par l'arrêt courant
            $trajetsPossibles = $trajetsByArret[$currentArret] ?? [];

            foreach ($trajetsPossibles as $tp) {
                $trajetId   = $tp['trajet_id'];
                $ordreDepart = $tp['ordre'];

                // Éviter de reprendre le même trajet
                if (in_array($trajetId, $state['used_trajets'])) {
                    continue;
                }

                $trajet = $trajetIndex[$trajetId];

                // Trouver tous les arrêts de ce trajet APRÈS l'arrêt courant
                foreach ($trajet['arrets'] as $arretInfo) {
                    $arretId    = (int)$arretInfo['id_arret'];
                    $ordreArret = (int)$arretInfo['ordre'];

                    // On doit avancer dans le trajet (ordre > ordre du départ)
                    if ($ordreArret <= $ordreDepart) {
                        continue;
                    }

                    // Construire le leg
                    $leg = [
                        'id'          => $trajetId,
                        'nom_bus'     => $trajet['nom_bus'],
                        'description' => $trajet['description'],
                        'from_arret'  => $currentArret,
                        'to_arret'    => $arretId,
                        'ordre_from'  => $ordreDepart,
                        'ordre_to'    => $ordreArret,
                    ];

                    $newLegs            = array_merge($state['legs'], [$leg]);
                    $newUsedTrajets     = array_merge($state['used_trajets'], [$trajetId]);
                    $newCorrespondances = $state['correspondances'];

                    if (!empty($state['legs'])) {
                        // Cet arrêt est une correspondance
                        $newCorrespondances[] = $currentArret;
                    }

                    // Est-ce qu'on a atteint la destination ?
                    if ($arretId === $idArrivee) {
                        $found[] = [
                            'legs'            => $newLegs,
                            'correspondances' => $newCorrespondances,
                        ];
                        // On ne continue pas à chercher au-delà de la destination
                        continue;
                    }

                    // Sinon, on pousse dans la file pour continuer la recherche
                    if (count($newLegs) < $maxBus) {
                        $queue[] = [
                            'current_arret'   => $arretId,
                            'legs'            => $newLegs,
                            'correspondances' => $newCorrespondances,
                            'used_trajets'    => $newUsedTrajets,
                        ];
                    }
                }
            }
        }

        return $found;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function formatArret(array $arret): array
    {
        return [
            'id'              => $arret['id'],
            'nom'             => $arret['nom'],
            'latitude'        => $arret['latitude'],
            'longitude'       => $arret['longitude'],
            'distance_metres' => round($arret['distance_metres']),
        ];
    }

    private function buildDescription(array $combo): string
    {
        $parts = [];
        foreach ($combo['legs'] as $leg) {
            $parts[] = $leg['nom_bus'];
        }
        if (!empty($combo['correspondances'])) {
            return implode(' → ', $parts) . ' (via correspondance)';
        }
        return implode(' → ', $parts);
    }
}
