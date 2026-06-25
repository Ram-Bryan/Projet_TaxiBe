<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TrajetArretModel
 *
 * Gère la table de liaison `trajet_arret`.
 * Chaque enregistrement représente un arrêt à une position donnée (ordre)
 * dans un trajet.
 */
class TrajetArretModel extends Model
{
    protected $table      = 'trajet_arret';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = ['id_trajet', 'id_arret', 'ordre'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'id_trajet' => 'required|integer|is_not_unique[trajet.id]',
        'id_arret'  => 'required|integer|is_not_unique[arret.id]',
        'ordre'     => 'required|integer|greater_than[0]',
    ];

    protected $validationMessages = [
        'id_trajet' => [
            'required'      => 'Le trajet est obligatoire.',
            'is_not_unique' => 'Le trajet spécifié n\'existe pas.',
        ],
        'id_arret' => [
            'required'      => 'L\'arrêt est obligatoire.',
            'is_not_unique' => 'L\'arrêt spécifié n\'existe pas.',
        ],
        'ordre' => [
            'required'       => 'L\'ordre de l\'arrêt dans le trajet est obligatoire.',
            'greater_than'   => 'L\'ordre doit être supérieur à 0.',
        ],
    ];

    // -------------------------------------------------------------------------
    // Méthodes métier
    // -------------------------------------------------------------------------

    /**
     * Retourne les arrêts d'un trajet dans l'ordre, avec coordonnées PostGIS.
     *
     * @param int $idTrajet ID du trajet
     * @return array
     */
    public function getArretsByTrajet(int $idTrajet): array
    {
        return $this->db->query(
            "SELECT
                ta.id,
                ta.ordre,
                a.id          AS id_arret,
                a.nom         AS nom_arret,
                ST_X(a.point) AS longitude,
                ST_Y(a.point) AS latitude
             FROM trajet_arret ta
             JOIN arret a ON a.id = ta.id_arret
             WHERE ta.id_trajet = ?
             ORDER BY ta.ordre ASC",
            [$idTrajet]
        )->getResultArray();
    }

    /**
     * Réordonne tous les arrêts d'un trajet (utile après drag-and-drop UI).
     *
     * @param int   $idTrajet ID du trajet
     * @param array $ordres   Tableau ['id_arret' => ordre, ...]
     */
    public function reordonner(int $idTrajet, array $ordres): void
    {
        foreach ($ordres as $idArret => $ordre) {
            $this->db->query(
                "UPDATE trajet_arret
                 SET ordre = ?
                 WHERE id_trajet = ? AND id_arret = ?",
                [(int) $ordre, $idTrajet, (int) $idArret]
            );
        }
    }

    /**
     * Supprime tous les arrêts d'un trajet (pour réassignation complète).
     *
     * @param int $idTrajet
     */
    public function clearTrajet(int $idTrajet): void
    {
        $this->where('id_trajet', $idTrajet)->delete();
    }

    /**
     * Retourne le prochain numéro d'ordre disponible pour un trajet.
     *
     * @param int $idTrajet
     * @return int
     */
    public function prochainOrdre(int $idTrajet): int
    {
        $result = $this->db->query(
            "SELECT COALESCE(MAX(ordre), 0) + 1 AS prochain
             FROM trajet_arret
             WHERE id_trajet = ?",
            [$idTrajet]
        )->getRow();

        return (int) ($result->prochain ?? 1);
    }
}
