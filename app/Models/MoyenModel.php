<?php

namespace App\Models;

use CodeIgniter\Model;

class MoyenModel extends Model
{
    protected $table            = 'moyen';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['nom', 'vitesse'];
}
