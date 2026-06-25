<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TrajetModel
 *
 * Gère la table `trajet`.
 * Un trajet appartient à un bus et est composé d'une séquence d'arrêts
 * (via la table de liaison trajet_arret).
 */
class TrajetModel extends Model
{
    protected $table      = 'trajet';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = ['id_bus', 'description'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'id_bus' => 'required|integer|is_not_unique[bus.id]',
    ];

    protected $validationMessages = [
        'id_bus' => [
            'required'      => 'Le bus associé au trajet est obligatoire.',
            'integer'       => 'L\'identifiant du bus doit être un entier.',
            'is_not_unique' => 'Le bus spécifié n\'existe pas.',
        ],
    ];

    // -------------------------------------------------------------------------
    // Méthodes métier
    // -------------------------------------------------------------------------

    /**
     * Retourne tous les trajets avec le nom du bus associé.
     */
    public function getAllWithBus(): array
    {
        return $this->select('trajet.*, bus.nom AS nom_bus')
                    ->join('bus', 'bus.id = trajet.id_bus')
                    ->orderBy('bus.nom', 'ASC')
                    ->findAll();
    }

    /**
     * Retourne un trajet avec son bus et la liste ordonnée de ses arrêts.
     *
     * @param int $id ID du trajet
     * @return array|null
     */
    public function getWithArrets(int $id): ?array
    {
        // Récupération du trajet + bus
        $trajet = $this->select('trajet.*, bus.nom AS nom_bus')
                        ->join('bus', 'bus.id = trajet.id_bus')
                        ->find($id);

        if (!$trajet) {
            return null;
        }

        // Récupération des arrêts dans l'ordre
        $trajet['arrets'] = $this->db->query(
            "SELECT
                a.id,
                a.nom,
                ta.ordre,
                ST_X(a.point) AS longitude,
                ST_Y(a.point) AS latitude
             FROM trajet_arret ta
             JOIN arret a ON a.id = ta.id_arret
             WHERE ta.id_trajet = ?
             ORDER BY ta.ordre ASC",
            [$id]
        )->getResultArray();

        return $trajet;
    }

    /**
     * Trouve les trajets passant par un arrêt donné.
     *
     * @param int $idArret ID de l'arrêt
     * @return array
     */
    public function getByArret(int $idArret): array
    {
        return $this->db->query(
            "SELECT
                t.id,
                t.description,
                b.nom AS nom_bus,
                ta.ordre
             FROM trajet t
             JOIN bus b          ON b.id  = t.id_bus
             JOIN trajet_arret ta ON ta.id_trajet = t.id
             WHERE ta.id_arret = ?
             ORDER BY b.nom, ta.ordre",
            [$idArret]
        )->getResultArray();
    }
}
