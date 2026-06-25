<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ArretModel;

class ArretController extends BaseController
{
    public function getAll()
    {
        $arretModel = new ArretModel();
        // Le modèle contient déjà la logique pour récupérer les coordonnées et le GeoJSON
        $arrets = $arretModel->getAllWithCoords();
        
        return $this->response->setJSON($arrets);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        $model = new ArretModel();
        
        // data expects: nom, lat, lng
        if(isset($data['nom'], $data['lat'], $data['lng'])) {
            $id = $model->insertWithPoint($data['nom'], (float)$data['lat'], (float)$data['lng']);
            if($id) {
                return $this->response->setJSON(['status' => 'success', 'id' => $id]);
            }
        }
        return $this->response->setStatusCode(400)->setJSON(['error' => 'Données invalides']);
    }
}
