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
 * un point de départ et un point d'arrivée.
 *
 * Algorithme BFS multi-sources / multi-destinations, limité à 3 bus maximum
 * (2 correspondances). Les correspondances sont autorisées non seulement au
 * même arrêt exact, mais aussi entre arrêts proches à pied (ex: un arrêt
 * "Aller" et son équivalent "Retour" à quelques centaines de mètres, ou deux
 * lignes qui ne se croisent pas exactement au même arrêt).
 *
 * Élagage des combinaisons qui font un détour excessif par rapport à la
 * distance à vol d'oiseau. Tri par distance réelle totale (marche + bus)
 * croissante.
 */
class SearchController extends BaseController
{
    // Rayon de marche par défaut pour chercher des arrêts candidats au départ/arrivée (mètres)
    private const RAYON_MARCHE_DEFAUT = 700.0;

    // Rayon de marche accepté pour une correspondance entre deux arrêts proches mais distincts (mètres)
    private const RAYON_CORRESPONDANCE_DEFAUT = 400.0;

    // Nombre max d'arrêts candidats à considérer au départ / à l'arrivée
    private const MAX_CANDIDATS_DEFAUT = 4;

    // Nombre max de bus (correspondances) dans une combinaison
    private const MAX_BUS_DEFAUT = 3;

    // Facteur de tolérance : un trajet ne doit pas dépasser X fois la
    // distance à vol d'oiseau entre départ et arrivée, sous peine d'être
    // élagué pendant le BFS (évite les grandes boucles inutiles).
    private const FACTEUR_DETOUR_MAX = 2.5;

    // Distance plancher (mètres) en dessous de laquelle on n'applique pas
    // la limite de détour, pour ne pas pénaliser les très courts trajets.
    private const DISTANCE_PLANCHER_M = 3000.0;

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

        $latDep = (float) $data['lat_dep'];
        $lngDep = (float) $data['lng_dep'];
        $latArr = (float) $data['lat_arr'];
        $lngArr = (float) $data['lng_arr'];

        // Paramètres optionnels de réglage
        $rayonMarche = isset($data['rayon_marche']) ? (float) $data['rayon_marche'] : self::RAYON_MARCHE_DEFAUT;
        $rayonCorrespondance = isset($data['rayon_correspondance']) ? (float) $data['rayon_correspondance'] : self::RAYON_CORRESPONDANCE_DEFAUT;
        $maxCandidats = isset($data['max_candidats']) ? (int) $data['max_candidats'] : self::MAX_CANDIDATS_DEFAUT;
        $maxBus = isset($data['max_bus']) ? (int) $data['max_bus'] : self::MAX_BUS_DEFAUT;

        $arretModel = new ArretModel();
        $trajetModel = new TrajetModel();
        $fraisModel = new FraisModel();

        // --- Récupérer le tarif actuel depuis la DB ---
        $prixParTrajet = $fraisModel->getMontantActuel();

        // --- Arrêts candidats au départ (plusieurs, pas un seul) ---
        $candidatsDepart = $arretModel->findNearest($latDep, $lngDep, $maxCandidats);
        $candidatsDepart = $this->filtrerParRayon($candidatsDepart, $rayonMarche);
        if (empty($candidatsDepart)) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'Aucun arrêt trouvé à proximité du départ'
            ]);
        }

        // --- Arrêts candidats à l'arrivée ---
        $candidatsArrivee = $arretModel->findNearest($latArr, $lngArr, $maxCandidats);
        $candidatsArrivee = $this->filtrerParRayon($candidatsArrivee, $rayonMarche);
        if (empty($candidatsArrivee)) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'Aucun arrêt trouvé à proximité de l\'arrivée'
            ]);
        }

        // Arrêts affichés en résumé : le plus proche de chaque côté
        $arretDepart = $candidatsDepart[0];
        $arretArrivee = $candidatsArrivee[0];

        // Si un même arrêt est candidat des deux côtés, pas besoin de bus
        $idsDepart = array_map(fn($a) => (int) $a['id'], $candidatsDepart);
        $idsArrivee = array_map(fn($a) => (int) $a['id'], $candidatsArrivee);
        if (array_intersect($idsDepart, $idsArrivee)) {
            return $this->response->setJSON([
                'status' => 'success',
                'arret_depart' => $this->formatArret($arretDepart),
                'arret_arrivee' => $this->formatArret($arretArrivee),
                'trajets' => [],
                'prix_unitaire' => $prixParTrajet,
            ]);
        }

        // --- Construire le graphe : tous les trajets avec arrêts + coordonnées ---
        $allTrajets = $trajetModel->getAllTrajetsWithArretsGeo();

        $trajetsByArret = [];
        $trajetIndex = [];
        foreach ($allTrajets as $trajet) {
            $trajetIndex[$trajet['id']] = $trajet;
            foreach ($trajet['arrets'] as $arret) {
                $trajetsByArret[(int) $arret['id_arret']][] = [
                    'trajet_id' => $trajet['id'],
                    'ordre' => (int) $arret['ordre'],
                ];
            }
        }

        // --- Arrêts voisins (correspondances à pied entre arrêts proches) ---
        $voisinsProches = $arretModel->getVoisinsProches($rayonCorrespondance);

        // --- Distance à vol d'oiseau départ -> arrivée, pour l'élagage ---
        $beeline = $this->distanceMetres($latDep, $lngDep, $latArr, $lngArr);
        $distanceMaxAutorisee = max($beeline * self::FACTEUR_DETOUR_MAX, self::DISTANCE_PLANCHER_M);

        // --- Candidats formatés pour le BFS ---
        $departsCandidats = array_map(fn($a) => [
            'id_arret' => (int) $a['id'],
            'distance_metres' => (float) $a['distance_metres'],
        ], $candidatsDepart);

        $arriveesCandidats = array_map(fn($a) => [
            'id_arret' => (int) $a['id'],
            'distance_metres' => (float) $a['distance_metres'],
        ], $candidatsArrivee);

        // --- BFS multi-sources / multi-destinations, avec élagage anti-détour ---
        $combinaisons = $this->findCombinations(
            $departsCandidats,
            $arriveesCandidats,
            $trajetsByArret,
            $trajetIndex,
            $voisinsProches,
            $maxBus,
            $distanceMaxAutorisee
        );

        // --- Résolution des noms d'arrêts pour l'affichage ---
        $allArretIds = [];
        foreach ($combinaisons as $combo) {
            foreach ($combo['legs'] as $leg) {
                $allArretIds[] = (int) $leg['from_arret'];
                $allArretIds[] = (int) $leg['to_arret'];
            }
        }
        $arretNoms = $arretModel->getNamesByIds(array_unique($allArretIds));

        // --- Construction des résultats + dédoublonnage ---
        $results = [];
        $vus = []; // signature de legs déjà vue -> index dans $results

        foreach ($combinaisons as $combo) {
            $nbBus = count($combo['legs']);
            $prix = $nbBus * $prixParTrajet;

            $distanceTotale = $combo['marche_depart']
                + $combo['distance_bus']
                + $combo['marche_correspondances']
                + $combo['marche_arrivee'];

            $enrichedLegs = [];
            $signatureParts = [];
            foreach ($combo['legs'] as $leg) {
                $leg['from_arret_nom'] = $arretNoms[$leg['from_arret']] ?? ('Arrêt #' . $leg['from_arret']);
                $leg['to_arret_nom'] = $arretNoms[$leg['to_arret']] ?? ('Arrêt #' . $leg['to_arret']);
                $enrichedLegs[] = $leg;
                $signatureParts[] = $leg['id'] . ':' . $leg['from_arret'] . '-' . $leg['to_arret'];
            }
            $signature = implode('|', $signatureParts);

            // Si cette combinaison exacte de segments existe déjà (via un
            // autre couple d'arrêts candidats), on garde celle avec la plus
            // petite distance totale.
            if (isset($vus[$signature])) {
                $idxExistant = $vus[$signature];
                if ($distanceTotale >= $results[$idxExistant]['distance_totale_m']) {
                    continue;
                }
            }

            $journeyLabel = $enrichedLegs[0]['from_arret_nom'];
            foreach ($enrichedLegs as $leg) {
                if (!empty($leg['marche_avant_m'])) {
                    $journeyLabel .= ' → (marche ' . round($leg['marche_avant_m']) . 'm) → ' . $leg['from_arret_nom'];
                }
                $journeyLabel .= ' → ' . $leg['nom_bus'] . ' → ' . $leg['to_arret_nom'];
            }

            $resultat = [
                'legs' => $enrichedLegs,
                'correspondances' => $combo['correspondances'],
                'nb_bus' => $nbBus,
                'prix_total' => $prix,
                'id' => implode('-', array_column($enrichedLegs, 'id')),
                'nom_bus' => implode(' ➔ ', array_column($enrichedLegs, 'nom_bus')),
                'journey_label' => $journeyLabel,
                'description' => $this->buildDescription($combo),
                'type' => $nbBus === 1 ? 'direct' : 'correspondance',
                'marche_depart_m' => round($combo['marche_depart']),
                'marche_correspondances_m' => round($combo['marche_correspondances']),
                'marche_arrivee_m' => round($combo['marche_arrivee']),
                'distance_bus_m' => round($combo['distance_bus']),
                'distance_totale_m' => round($distanceTotale),
            ];

            if (isset($vus[$signature])) {
                $results[$vus[$signature]] = $resultat;
            } else {
                $vus[$signature] = count($results);
                $results[] = $resultat;
            }
        }

        // Tri : d'abord par distance réelle totale (évite les détours /
        // trajets trop longs), puis par prix, puis par nombre de bus.
        usort($results, function ($a, $b) {
            if ($a['distance_totale_m'] !== $b['distance_totale_m']) {
                return $a['distance_totale_m'] <=> $b['distance_totale_m'];
            }
            if ($a['prix_total'] !== $b['prix_total']) {
                return $a['prix_total'] <=> $b['prix_total'];
            }
            return $a['nb_bus'] <=> $b['nb_bus'];
        });

        // On ne garde qu'un nombre raisonnable de propositions
        $results = array_slice($results, 0, 5);

        return $this->response->setJSON([
            'status' => 'success',
            'arret_depart' => $this->formatArret($arretDepart),
            'arret_arrivee' => $this->formatArret($arretArrivee),
            'trajets' => $results,
            'prix_unitaire' => $prixParTrajet,
        ]);
    }

    // =========================================================================
    // Algorithme BFS
    // =========================================================================

    /**
     * Trouve toutes les combinaisons de trajets (BFS multi-sources /
     * multi-destinations, max $maxBus bus), en élaguant les branches dont la
     * distance parcourue dépasse $maxDistanceAutorisee.
     *
     * À chaque étape, la correspondance est possible non seulement à l'arrêt
     * exact où le bus précédent dépose le passager, mais aussi à tout arrêt
     * voisin dans $voisinsProches (avec la marche correspondante comptée
     * dans la distance totale).
     *
     * @param array $departsCandidats   [ ['id_arret','distance_metres'], ... ]
     * @param array $arriveesCandidats  [ ['id_arret','distance_metres'], ... ]
     * @param array $trajetsByArret     Index [id_arret => [{trajet_id, ordre}]]
     * @param array $trajetIndex        Index [trajet_id => trajet complet (avec coords)]
     * @param array $voisinsProches     Index [id_arret => [{id, distance_metres}]]
     * @param int   $maxBus             Nombre maximum de bus (profondeur BFS)
     * @param float $maxDistanceAutorisee Distance bus max (mètres) avant élagage
     * @return array Tableau de combinaisons
     */
    private function findCombinations(
        array $departsCandidats,
        array $arriveesCandidats,
        array $trajetsByArret,
        array $trajetIndex,
        array $voisinsProches,
        int $maxBus,
        float $maxDistanceAutorisee
    ): array {
        $found = [];

        $arriveeIds = array_column($arriveesCandidats, 'id_arret');
        $arriveeMarche = array_column($arriveesCandidats, 'distance_metres', 'id_arret');

        // File BFS initialisée avec TOUS les arrêts candidats de départ
        $queue = [];
        foreach ($departsCandidats as $dep) {
            $queue[] = [
                'current_arret' => $dep['id_arret'],
                'legs' => [],
                'correspondances' => [],
                'used_trajets' => [],
                'marche_depart' => $dep['distance_metres'],
                'marche_correspondances' => 0.0,
                'distance_bus' => 0.0,
            ];
        }

        while (!empty($queue)) {
            $state = array_shift($queue);

            if (count($state['legs']) >= $maxBus) {
                continue;
            }

            $currentArret = $state['current_arret'];

            // -----------------------------------------------------------------
// Détermination des points d'embarquement.
//
// Au départ du trajet (aucun bus encore pris), on ne doit utiliser
// QUE l'arrêt candidat trouvé depuis la position GPS.
//
// Les arrêts voisins ne sont autorisés qu'après avoir déjà pris un
// premier bus, c'est-à-dire uniquement pour les correspondances.
// -----------------------------------------------------------------
            $pointsEmbarquement = [
                [
                    'id' => $currentArret,
                    'distance_metres' => 0.0
                ]
            ];

            if (!empty($state['legs'])) {
                foreach ($voisinsProches[$currentArret] ?? [] as $voisin) {
                    $pointsEmbarquement[] = [
                        'id' => $voisin['id'],
                        'distance_metres' => $voisin['distance_metres']
                    ];
                }
            }

            foreach ($pointsEmbarquement as $embarquement) {
                $arretEmbarquement = $embarquement['id'];
                $marcheAvant = $embarquement['distance_metres'];

                $trajetsPossibles = $trajetsByArret[$arretEmbarquement] ?? [];

                foreach ($trajetsPossibles as $tp) {
                    $trajetId = $tp['trajet_id'];
                    $ordreDepart = $tp['ordre'];

                    if (in_array($trajetId, $state['used_trajets'], true)) {
                        continue;
                    }

                    $trajet = $trajetIndex[$trajetId];

                    foreach ($trajet['arrets'] as $arretInfo) {
                        $arretId = (int) $arretInfo['id_arret'];
                        $ordreArret = (int) $arretInfo['ordre'];

                        if ($ordreArret <= $ordreDepart) {
                            continue;
                        }

                        $distanceLeg = $this->distanceLeg($trajet['arrets'], $ordreDepart, $ordreArret);
                        $nouvelleDistanceBus = $state['distance_bus'] + $distanceLeg;

                        if ($nouvelleDistanceBus > $maxDistanceAutorisee) {
                            continue;
                        }

                        $leg = [
                            'id' => $trajetId,
                            'nom_bus' => $trajet['nom_bus'],
                            'description' => $trajet['description'],
                            'from_arret' => $arretEmbarquement,
                            'to_arret' => $arretId,
                            'ordre_from' => $ordreDepart,
                            'ordre_to' => $ordreArret,
                            'distance_m' => round($distanceLeg),
                            'marche_avant_m' => round($marcheAvant),
                        ];

                        $newLegs = array_merge($state['legs'], [$leg]);
                        $newUsedTrajets = array_merge($state['used_trajets'], [$trajetId]);
                        $newCorrespondances = $state['correspondances'];
                        $newMarcheCorrespondances = $state['marche_correspondances'] + $marcheAvant;

                        if (!empty($state['legs'])) {
                            $newCorrespondances[] = $currentArret;
                        }

                        if (in_array($arretId, $arriveeIds, true)) {
                            $found[] = [
                                'legs' => $newLegs,
                                'correspondances' => $newCorrespondances,
                                'marche_depart' => $state['marche_depart'],
                                'marche_correspondances' => $newMarcheCorrespondances,
                                'marche_arrivee' => $arriveeMarche[$arretId] ?? 0.0,
                                'distance_bus' => $nouvelleDistanceBus,
                            ];
                            continue;
                        }

                        if (count($newLegs) < $maxBus) {
                            $queue[] = [
                                'current_arret' => $arretId,
                                'legs' => $newLegs,
                                'correspondances' => $newCorrespondances,
                                'used_trajets' => $newUsedTrajets,
                                'marche_depart' => $state['marche_depart'],
                                'marche_correspondances' => $newMarcheCorrespondances,
                                'distance_bus' => $nouvelleDistanceBus,
                            ];
                        }
                    }
                }
            }
        }

        return $found;
    }

    // =========================================================================
    // Helpers géométriques
    // =========================================================================

    /**
     * Distance à vol d'oiseau entre deux points GPS (formule de Haversine).
     */
    private function distanceMetres(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $rayonTerre = 6371000.0; // mètres

        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dPhi = deg2rad($lat2 - $lat1);
        $dLambda = deg2rad($lon2 - $lon1);

        $a = sin($dPhi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($dLambda / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $rayonTerre * $c;
    }

    /**
     * Distance réelle parcourue sur un trajet entre deux ordres donnés,
     * en sommant les distances entre arrêts consécutifs (coordonnées GPS).
     *
     * @param array $arretsTrajet  Arrêts du trajet, triés par ordre croissant,
     *                             chacun avec ['ordre','latitude','longitude']
     * @param int   $ordreFrom
     * @param int   $ordreTo
     */
    private function distanceLeg(array $arretsTrajet, int $ordreFrom, int $ordreTo): float
    {
        $distance = 0.0;
        $precedent = null;

        foreach ($arretsTrajet as $arret) {
            $ordre = (int) $arret['ordre'];
            if ($ordre < $ordreFrom || $ordre > $ordreTo) {
                continue;
            }
            if ($precedent !== null) {
                $distance += $this->distanceMetres(
                    (float) $precedent['latitude'],
                    (float) $precedent['longitude'],
                    (float) $arret['latitude'],
                    (float) $arret['longitude']
                );
            }
            $precedent = $arret;
        }

        return $distance;
    }

    /**
     * Filtre une liste d'arrêts (résultat de findNearest) pour ne garder que
     * ceux situés dans un rayon de marche acceptable.
     */
    private function filtrerParRayon(array $arrets, float $rayonMetres): array
    {
        $filtres = array_values(array_filter(
            $arrets,
            fn($a) => (float) $a['distance_metres'] <= $rayonMetres
        ));

        // Si le filtre est trop strict et n'exclut aucun résultat exploitable,
        // on garde au moins le plus proche pour ne jamais renvoyer une liste vide
        // alors que findNearest() avait trouvé quelque chose.
        if (empty($filtres) && !empty($arrets)) {
            $filtres = [$arrets[0]];
        }

        return $filtres;
    }

    // =========================================================================
    // Helpers d'affichage
    // =========================================================================

    private function formatArret(array $arret): array
    {
        return [
            'id' => $arret['id'],
            'nom' => $arret['nom'],
            'latitude' => $arret['latitude'],
            'longitude' => $arret['longitude'],
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