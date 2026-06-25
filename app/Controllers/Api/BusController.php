<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BusModel;

class BusController extends BaseController
{
    public function index()
    {
        $model = new BusModel();
        return $this->response->setJSON($model->findAll());
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        $model = new BusModel();
        
        if (isset($data['nom'])) {
            if ($model->insert($data)) {
                return $this->response->setJSON(['status' => 'success']);
            }
        }
        return $this->response->setStatusCode(400)->setJSON(['error' => 'Données invalides']);
    }
}
