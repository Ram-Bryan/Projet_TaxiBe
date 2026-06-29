<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\MoyenModel;

class MoyenController extends BaseController
{
    public function index()
    {
        $moyenModel = new MoyenModel();
        return $this->response->setJSON($moyenModel->findAll());
    }
}
