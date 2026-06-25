<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * BusModel
 *
 * Gère la table `bus`.
 * CRUD standard géré par le Model CI4.
 */
class BusModel extends Model
{
    protected $table      = 'bus';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = ['nom'];

    // Timestamps désactivés (pas de colonnes created_at / updated_at dans la table)
    protected $useTimestamps = false;

    // Validation
    protected $validationRules = [
        'nom' => 'required|max_length[100]',
    ];

    protected $validationMessages = [
        'nom' => [
            'required'   => 'Le nom du bus est obligatoire.',
            'max_length' => 'Le nom du bus ne doit pas dépasser 100 caractères.',
        ],
    ];

    protected $skipValidation = false;

    // -------------------------------------------------------------------------
    // Méthodes métier
    // -------------------------------------------------------------------------

    /**
     * Retourne tous les bus avec le nombre de trajets associés.
     */
    public function getAllWithTrajetCount(): array
    {
        return $this->db->query('
            SELECT b.id, b.nom, COUNT(t.id) AS nb_trajets
            FROM bus b
            LEFT JOIN trajet t ON t.id_bus = b.id
            GROUP BY b.id, b.nom
            ORDER BY b.nom ASC
        ')->getResultArray();
    }
}
