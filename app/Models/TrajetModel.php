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
    // Méthodes métier — CRUD / consultation de base
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
            $trajet['arrets'] = $this->getArretIdsWithOrder((int) $trajet['id']);
        }
        unset($trajet);

        return $trajets;
    }

    /**
     * Comme getAllTrajetsWithArrets(), mais inclut les coordonnées de chaque
     * arrêt (longitude/latitude). Nécessaire pour calculer des distances
     * réelles (Haversine) entre arrêts consécutifs d'un trajet, utilisé par
     * la recherche d'itinéraire pour éviter les trajets trop longs.
     *
     * @return array
     */
    public function getAllTrajetsWithArretsGeo(): array
    {
        $trajets = $this->db->query(
            "SELECT t.id, t.description, b.nom AS nom_bus
             FROM trajet t
             JOIN bus b ON b.id = t.id_bus
             ORDER BY t.id ASC"
        )->getResultArray();

        foreach ($trajets as &$trajet) {
            $trajet['arrets'] = $this->db->query(
                "SELECT
                    ta.id_arret,
                    ta.ordre,
                    ST_X(a.point) AS longitude,
                    ST_Y(a.point) AS latitude
                 FROM trajet_arret ta
                 JOIN arret a ON a.id = ta.id_arret
                 WHERE ta.id_trajet = ?
                 ORDER BY ta.ordre ASC",
                [(int) $trajet['id']]
            )->getResultArray();
        }
        unset($trajet);

        return $trajets;
    }

    // -------------------------------------------------------------------------
    // Méthodes métier — Optimisation "dernier kilomètre" (choix du meilleur arrêt)
    // -------------------------------------------------------------------------
    //
    // Problème résolu : l'algorithme de recherche identifie un trajet candidat
    // et un arrêt "canonique" (ex: terminus Fiadanamanga pour le 137), mais cet
    // arrêt n'est pas forcément le plus proche du point réel de l'utilisateur.
    // Ces méthodes permettent de reclasser les arrêts d'un même trajet par
    // distance géographique réelle (mètres, via PostGIS geography) plutôt que
    // de figer un arrêt en dur.
    // -------------------------------------------------------------------------

    /**
     * Retourne les arrêts d'un trajet situés AVANT un ordre donné (destination),
     * triés par distance réelle par rapport à un point donné (ex: position de
     * l'utilisateur).
     *
     * Permet de proposer un embarquement plus proche que l'arrêt canonique
     * (ex: Ankadimbahoaka au lieu de Fiadanamanga pour le 137).
     *
     * @param int   $idTrajet
     * @param int   $ordreMax  Ordre de l'arrêt destination (exclu)
     * @param float $lon       Longitude du point de référence
     * @param float $lat       Latitude du point de référence
     * @param float $rayonMax  Rayon de recherche en mètres (0 = pas de limite)
     * @return array Liste de ['id', 'nom', 'ordre', 'distance_m'], triée par distance croissante
     */
    public function getArretsAvantOrdreTriesParDistance(
        int $idTrajet,
        int $ordreMax,
        float $lon,
        float $lat,
        float $rayonMax = 0
    ): array {
        $sql = "SELECT
                    a.id,
                    a.nom,
                    ta.ordre,
                    ST_Distance(
                        a.point::geography,
                        ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                    ) AS distance_m
                FROM trajet_arret ta
                JOIN arret a ON a.id = ta.id_arret
                WHERE ta.id_trajet = ?
                  AND ta.ordre < ?";

        $params = [$lon, $lat, $idTrajet, $ordreMax];

        if ($rayonMax > 0) {
            $sql .= " AND ST_DWithin(
                        a.point::geography,
                        ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                        ?
                      )";
            $params = array_merge($params, [$lon, $lat, $rayonMax]);
        }

        $sql .= " ORDER BY distance_m ASC";

        return $this->db->query($sql, $params)->getResultArray();
    }

    /**
     * Retourne les arrêts d'un trajet situés APRÈS un ordre donné (départ),
     * triés par distance réelle par rapport à un point de destination souhaité.
     *
     * Symétrique de getArretsAvantOrdreTriesParDistance : permet de descendre
     * à l'arrêt le plus proche de la destination réelle plutôt qu'au terminus.
     *
     * @param int   $idTrajet
     * @param int   $ordreMin  Ordre de l'arrêt départ (exclu)
     * @param float $lon       Longitude du point de destination souhaité
     * @param float $lat       Latitude du point de destination souhaité
     * @param float $rayonMax  Rayon de recherche en mètres (0 = pas de limite)
     * @return array Liste de ['id', 'nom', 'ordre', 'distance_m'], triée par distance croissante
     */
    public function getArretsApresOrdreTriesParDistance(
        int $idTrajet,
        int $ordreMin,
        float $lon,
        float $lat,
        float $rayonMax = 0
    ): array {
        $sql = "SELECT
                    a.id,
                    a.nom,
                    ta.ordre,
                    ST_Distance(
                        a.point::geography,
                        ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                    ) AS distance_m
                FROM trajet_arret ta
                JOIN arret a ON a.id = ta.id_arret
                WHERE ta.id_trajet = ?
                  AND ta.ordre > ?";

        $params = [$lon, $lat, $idTrajet, $ordreMin];

        if ($rayonMax > 0) {
            $sql .= " AND ST_DWithin(
                        a.point::geography,
                        ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                        ?
                      )";
            $params = array_merge($params, [$lon, $lat, $rayonMax]);
        }

        $sql .= " ORDER BY distance_m ASC";

        return $this->db->query($sql, $params)->getResultArray();
    }

    /**
     * Trouve tous les arrêts (tous trajets confondus) situés dans un rayon
     * donné autour d'un point. Utile pour proposer des embarquements
     * alternatifs quand aucun arrêt du trajet "idéal" n'est assez proche.
     *
     * @param float $lon         Longitude du point de référence
     * @param float $lat         Latitude du point de référence
     * @param float $rayonMetres Rayon de recherche en mètres (défaut 400m)
     * @return array Liste de ['id', 'nom', 'distance_m'], triée par distance croissante
     */
    public function getArretsProchesDunPoint(float $lon, float $lat, float $rayonMetres = 400): array
    {
        return $this->db->query(
            "SELECT
                a.id,
                a.nom,
                ST_Distance(
                    a.point::geography,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                ) AS distance_m
             FROM arret a
             WHERE ST_DWithin(
                 a.point::geography,
                 ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                 ?
             )
             ORDER BY distance_m ASC",
            [$lon, $lat, $lon, $lat, $rayonMetres]
        )->getResultArray();
    }

    /**
     * Trouve le meilleur arrêt d'embarquement pour un trajet donné : parmi les
     * arrêts situés avant l'arrêt destination (ordre < $ordreMax), retourne
     * celui qui est géographiquement le plus proche du point de l'utilisateur.
     *
     * Wrapper pratique autour de getArretsAvantOrdreTriesParDistance quand on
     * ne veut que le meilleur candidat plutôt que la liste complète.
     *
     * @param int   $idTrajet
     * @param int   $ordreMax  Ordre de l'arrêt destination (exclu)
     * @param float $lon       Longitude du point de l'utilisateur
     * @param float $lat       Latitude du point de l'utilisateur
     * @param float $rayonMax  Rayon de recherche en mètres (0 = pas de limite)
     * @return array|null ['id', 'nom', 'ordre', 'distance_m'] ou null si aucun candidat
     */
    public function getMeilleurArretEmbarquement(
        int $idTrajet,
        int $ordreMax,
        float $lon,
        float $lat,
        float $rayonMax = 0
    ): ?array {
        $candidats = $this->getArretsAvantOrdreTriesParDistance($idTrajet, $ordreMax, $lon, $lat, $rayonMax);

        return $candidats[0] ?? null;
    }

    /**
     * Trouve le meilleur arrêt de débarquement pour un trajet donné : parmi
     * les arrêts situés après l'arrêt de départ (ordre > $ordreMin), retourne
     * celui qui est géographiquement le plus proche de la destination souhaitée.
     *
     * @param int   $idTrajet
     * @param int   $ordreMin  Ordre de l'arrêt départ (exclu)
     * @param float $lon       Longitude de la destination souhaitée
     * @param float $lat       Latitude de la destination souhaitée
     * @param float $rayonMax  Rayon de recherche en mètres (0 = pas de limite)
     * @return array|null ['id', 'nom', 'ordre', 'distance_m'] ou null si aucun candidat
     */
    public function getMeilleurArretDebarquement(
        int $idTrajet,
        int $ordreMin,
        float $lon,
        float $lat,
        float $rayonMax = 0
    ): ?array {
        $candidats = $this->getArretsApresOrdreTriesParDistance($idTrajet, $ordreMin, $lon, $lat, $rayonMax);

        return $candidats[0] ?? null;
    }
}