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

    /**
     * Trouve les trajets passant par un départ et une destination, dans le bon ordre.
     */
    public function findTrajetsBetween(int $idDepart, int $idDest): array
    {
        return $this->db->query(
            "SELECT DISTINCT t.id, t.description, b.nom AS nom_bus
             FROM trajet t
             JOIN bus b ON b.id = t.id_bus
             JOIN trajet_arret ta_dep  ON ta_dep.id_trajet  = t.id
             JOIN trajet_arret ta_dest ON ta_dest.id_trajet = t.id
             WHERE ta_dep.id_arret   = ?
               AND ta_dest.id_arret  = ?
               AND ta_dep.ordre < ta_dest.ordre
             ORDER BY b.nom ASC",
            [$idDepart, $idDest]
        )->getResultArray();
    }

    /**
     * Retourne les IDs des arrêts d'un trajet situés AVANT un ordre donné.
     *
     * Utilisé pour trouver les arrêts candidats "départ" dans un trajet :
     * on ne garde que ceux qui viennent avant l'arrêt destination.
     *
     * @param int $idTrajet   ID du trajet
     * @param int $ordreMax   Ordre de l'arrêt destination (exclu)
     * @return array          Liste de ['id' => ...]
     */
    public function getArretsBefore(int $idTrajet, int $ordreMax): array
    {
        return $this->db->query(
            "SELECT ta.id_arret AS id, ta.ordre
             FROM trajet_arret ta
             WHERE ta.id_trajet = ?
               AND ta.ordre < ?
             ORDER BY ta.ordre ASC",
            [$idTrajet, $ordreMax]
        )->getResultArray();
    }

    /**
     * Retourne tous les IDs des arrêts d'un trajet (sans filtre d'ordre).
     *
     * @param int $idTrajet
     * @return array  Liste de ['id' => ...]
     */
    public function getArretIds(int $idTrajet): array
    {
        return $this->db->query(
            "SELECT ta.id_arret AS id
             FROM trajet_arret ta
             WHERE ta.id_trajet = ?
             ORDER BY ta.ordre ASC",
            [$idTrajet]
        )->getResultArray();
    }

    /**
     * Retourne tous les arrêts d'un trajet avec leur ordre.
     *
     * @param int $idTrajet
     * @return array  Liste de ['id_arret' => ..., 'ordre' => ...]
     */
    public function getArretIdsWithOrder(int $idTrajet): array
    {
        return $this->db->query(
            "SELECT ta.id_arret, ta.ordre
             FROM trajet_arret ta
             WHERE ta.id_trajet = ?
             ORDER BY ta.ordre ASC",
            [$idTrajet]
        )->getResultArray();
    }

    /**
     * Retourne tous les trajets avec leurs arrêts (pour la construction du graphe BFS).
     * Format : [ [id, nom_bus, description, arrets => [id_arret, ordre]], ... ]
     *
     * @return array
     */
    public function getAllTrajetsWithArrets(): array
    {
        // Tous les trajets
        $trajets = $this->db->query(
            "SELECT t.id, t.description, b.nom AS nom_bus
             FROM trajet t
             JOIN bus b ON b.id = t.id_bus
             ORDER BY t.id ASC"
        )->getResultArray();

        // Pour chaque trajet, charger ses arrêts
        foreach ($trajets as &$trajet) {
            $trajet['arrets'] = $this->getArretIdsWithOrder((int)$trajet['id']);
        }
        unset($trajet);

        return $trajets;
    }
}

