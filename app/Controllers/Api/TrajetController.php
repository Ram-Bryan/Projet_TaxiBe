<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\TrajetModel;
use App\Models\TrajetArretModel;

class TrajetController extends BaseController
{
    public function index()
    {
        $model = new TrajetModel();
        return $this->response->setJSON($model->getAllWithBus());
    }

    public function get($id)
    {
        $model = new TrajetModel();
        $trajet = $model->getWithArrets((int)$id);
        
        if ($trajet) {
            return $this->response->setJSON($trajet);
        }
        return $this->response->setStatusCode(404)->setJSON(['error' => 'Non trouvé']);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        $trajetModel = new TrajetModel();
        $taModel = new TrajetArretModel();
        
        if (isset($data['id_bus']) && isset($data['arrets'])) {
            // Créer le trajet
            $idTrajet = $trajetModel->insert([
                'id_bus' => $data['id_bus'],
                'description' => $data['description'] ?? ''
            ]);

            if ($idTrajet) {
                // Ajouter les arrêts dans l'ordre
                foreach ($data['arrets'] as $index => $idArret) {
                    $taModel->insert([
                        'id_trajet' => $idTrajet,
                        'id_arret' => $idArret,
                        'ordre' => $index + 1
                    ]);
                }
                return $this->response->setJSON(['status' => 'success', 'id' => $idTrajet]);
            }
        }
        return $this->response->setStatusCode(400)->setJSON(['error' => 'Données invalides']);
    }

    public function update($id)
    {
        $data = $this->request->getJSON(true);
        $trajetModel = new TrajetModel();
        $taModel = new TrajetArretModel();
        
        if (isset($data['id_bus']) && isset($data['arrets'])) {
            if ($trajetModel->update($id, ['id_bus' => $data['id_bus'], 'description' => $data['description'] ?? ''])) {
                // Supprimer les anciens arrêts
                $taModel->where('id_trajet', $id)->delete();
                // Assigner les nouveaux
                foreach ($data['arrets'] as $index => $idArret) {
                    $taModel->insert([
                        'id_trajet' => $id,
                        'id_arret' => $idArret,
                        'ordre' => $index + 1
                    ]);
                }
                return $this->response->setJSON(['status' => 'success']);
            }
        }
        return $this->response->setStatusCode(400)->setJSON(['error' => 'Données invalides pour la mise à jour']);
    }

    public function delete($id)
    {
        $model = new TrajetModel();
        if ($model->delete($id)) {
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setStatusCode(400)->setJSON(['error' => 'Erreur lors de la suppression']);
    }
}
