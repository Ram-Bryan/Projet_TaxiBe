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
}
