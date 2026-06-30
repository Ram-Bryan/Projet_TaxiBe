<?php

namespace App\Models;

use CodeIgniter\Model;

class UtilisateurModel extends Model
{
    protected $table            = 'utilisateur';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['nom', 'email', 'mot_de_passe', 'role'];

    // Validation
    protected $validationRules      = [
        'email' => 'required|valid_email|is_unique[utilisateur.email]',
        'nom'   => 'required|min_length[3]',
        'mot_de_passe' => 'required|min_length[6]',
        'role'  => 'required|in_list[simple,admin]'
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
