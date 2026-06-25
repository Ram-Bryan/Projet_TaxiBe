<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class MapController extends BaseController
{
    public function index()
    {
        return view('map');
    }
}
