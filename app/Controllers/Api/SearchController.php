<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ArretModel;
use App\Models\TrajetModel;

class SearchController extends BaseController
{
    public function search()
    {
        $data = $this->request->getJSON(true);
        if (!isset($data['lat_dep']) || !isset($data['lng_dep']) || !isset($data['lat_arr']) || !isset($data['lng_arr'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Paramètres invalides (lat_dep, lng_dep, lat_arr, lng_arr requis)']);
        }

        $latDep = (float)$data['lat_dep'];
        $lngDep = (float)$data['lng_dep'];
        $latArr = (float)$data['lat_arr'];
        $lngArr = (float)$data['lng_arr'];

        $arretModel  = new ArretModel();
        $trajetModel = new TrajetModel();

        // ÉTAPE 1 — Arrêt arrivée = le plus proche du marker arrivée, aucune autre contrainte.
        $nearestArr = $arretModel->findNearest($latArr, $lngArr, 1);
        if (empty($nearestArr)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Aucun arrêt trouvé à proximité de l\'arrivée']);
        }
        $arretArrivee    = $nearestArr[0];
        $idArrivee       = (int)$arretArrivee['id'];
        $responseArrivee = [
            'id'              => $arretArrivee['id'],
            'nom'             => $arretArrivee['nom'],
            'latitude'        => $arretArrivee['latitude'],
            'longitude'       => $arretArrivee['longitude'],
            'distance_metres' => round($arretArrivee['distance_metres'])
        ];

        // ÉTAPE 2 — Trajets qui passent par cet arrêt d'arrivée.
        $trajetsCandidats = $trajetModel->getByArret($idArrivee);
        if (empty($trajetsCandidats)) {
            return $this->response->setJSON([
                'status'        => 'success',
                'arret_depart'  => null,
                'arret_arrivee' => $responseArrivee,
                'trajets'       => [],
            ]);
        }

        // ÉTAPE 3 — Pour chaque trajet, trouver l'arrêt le plus proche du départ
        //           parmi TOUS les arrêts du trajet (pas de filtre d'ordre ici).
        $bestDep = null;
        foreach ($trajetsCandidats as $trajet) {
            $tousLesArrets = $trajetModel->getArretIds((int)$trajet['id']);
            if (empty($tousLesArrets)) {
                continue;
            }

            $ids        = array_column($tousLesArrets, 'id');
            $nearestDep = $arretModel->findNearestAmong($latDep, $lngDep, $ids);
            if (!$nearestDep) {
                continue;
            }

            if ($bestDep === null || $nearestDep['distance_metres'] < $bestDep['distance_metres']) {
                $bestDep = $nearestDep;
            }
        }

        if ($bestDep === null) {
            return $this->response->setJSON([
                'status'        => 'success',
                'arret_depart'  => null,
                'arret_arrivee' => $responseArrivee,
                'trajets'       => [],
            ]);
        }

        // ÉTAPE 4 — Trajets qui relient bestDep → arretArrivee dans le bon ordre.
        $trajets = $trajetModel->findTrajetsBetween((int)$bestDep['id'], $idArrivee);

        return $this->response->setJSON([
            'status'        => 'success',
            'arret_depart'  => [
                'id'              => $bestDep['id'],
                'nom'             => $bestDep['nom'],
                'latitude'        => $bestDep['latitude'],
                'longitude'       => $bestDep['longitude'],
                'distance_metres' => round($bestDep['distance_metres'])
            ],
            'arret_arrivee' => $responseArrivee,
            'trajets'       => $trajets
        ]);
    }
}
