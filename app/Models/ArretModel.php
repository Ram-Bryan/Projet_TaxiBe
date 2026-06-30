<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ArretModel
 *
 * Gère la table `arret` (avec champ GEOMETRY PostGIS).
 *
 * IMPORTANT : Le champ `point` (GEOMETRY) ne peut pas être manipulé via le
 * Query Builder CI4 standard. Toutes les opérations spatiales passent par
 * $this->db->query() avec des fonctions PostGIS natives.
 */
class ArretModel extends Model
{
    protected $table      = 'arret';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    // 'point' est exclu de allowedFields car il doit être inséré via ST_MakePoint
    protected $allowedFields = ['nom'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'nom' => 'required|max_length[150]',
    ];

    protected $validationMessages = [
        'nom' => [
            'required'   => 'Le nom de l\'arrêt est obligatoire.',
            'max_length' => 'Le nom de l\'arrêt ne doit pas dépasser 150 caractères.',
        ],
    ];

    // -------------------------------------------------------------------------
    // Méthodes d'insertion spatiale
    // -------------------------------------------------------------------------

    /**
     * Insère un arrêt avec sa position géographique.
     *
     * @param string $nom  Nom de l'arrêt
     * @param float  $lat  Latitude (WGS84)
     * @param float  $lng  Longitude (WGS84)
     * @return int|false   ID de l'arrêt inséré, ou false en cas d'erreur
     */
    public function insertWithPoint(string $nom, float $lat, float $lng): int|false
    {
        $result = $this->db->query(
            "INSERT INTO arret (nom, point)
             VALUES (?, ST_SetSRID(ST_MakePoint(?, ?), 4326))
             RETURNING id",
            [$nom, $lng, $lat]   // ST_MakePoint(longitude, latitude) ← ordre PostGIS
        );

        if (!$result) {
            return false;
        }

        return (int) $result->getRow()->id;
    }

    /**
     * Met à jour la position géographique d'un arrêt existant.
     *
     * @param int   $id   ID de l'arrêt
     * @param float $lat  Latitude
     * @param float $lng  Longitude
     */
    public function updatePoint(int $id, float $lat, float $lng): bool
    {
        return (bool) $this->db->query(
            "UPDATE arret
             SET point = ST_SetSRID(ST_MakePoint(?, ?), 4326)
             WHERE id = ?",
            [$lng, $lat, $id]
        );
    }

    // -------------------------------------------------------------------------
    // Requêtes spatiales
    // -------------------------------------------------------------------------

    /**
     * Trouve les N arrêts les plus proches d'une position donnée.
     *
     * Utilise ST_Distance (en mètres, via ::geography) pour un calcul précis
     * sur la sphère terrestre.
     *
     * @param float $lat    Latitude de l'utilisateur
     * @param float $lng    Longitude de l'utilisateur
     * @param int   $limit  Nombre maximum de résultats (défaut : 5)
     * @return array        Liste d'arrêts avec leur distance en mètres
     */
    public function findNearest(float $lat, float $lng, int $limit = 5): array
    {
        return $this->db->query(
            "SELECT
                a.id,
                a.nom,
                ST_AsGeoJSON(a.point) AS geojson,
                ST_X(a.point)         AS longitude,
                ST_Y(a.point)         AS latitude,
                ST_Distance(
                    a.point::geography,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                ) AS distance_metres
             FROM arret a
             ORDER BY a.point::geography <-> ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
             LIMIT ?",
            [$lng, $lat, $lng, $lat, $limit]
        )->getResultArray();
    }

    /**
     * Trouve les arrêts dans un rayon donné autour d'une position.
     *
     * @param float $lat      Latitude
     * @param float $lng      Longitude
     * @param int   $rayonM   Rayon de recherche en mètres (défaut : 500m)
     * @return array
     */
    public function findInRadius(float $lat, float $lng, int $rayonM = 500): array
    {
        return $this->db->query(
            "SELECT
                a.id,
                a.nom,
                ST_AsGeoJSON(a.point) AS geojson,
                ST_X(a.point)         AS longitude,
                ST_Y(a.point)         AS latitude,
                ST_Distance(
                    a.point::geography,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                ) AS distance_metres
             FROM arret a
             WHERE ST_DWithin(
                a.point::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                ?
             )
             ORDER BY distance_metres ASC",
            [$lng, $lat, $lng, $lat, $rayonM]
        )->getResultArray();
    }

    /**
     * Trouve l'arrêt le plus proche d'une position parmi une liste d'IDs donnée.
     *
     * Utile pour trouver l'arrêt de départ optimal parmi les arrêts
     * qui appartiennent réellement au trajet voulu.
     *
     * @param float $lat   Latitude de l'utilisateur
     * @param float $lng   Longitude de l'utilisateur
     * @param array $ids   Liste d'IDs d'arrêts candidats
     * @return array|null  L'arrêt le plus proche, ou null si $ids est vide
     */
    public function findNearestAmong(float $lat, float $lng, array $ids): ?array
    {
        if (empty($ids)) {
            return null;
        }

        // Construction de la liste de placeholders : ?, ?, ?...
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $results = $this->db->query(
            "SELECT
                a.id,
                a.nom,
                ST_X(a.point) AS longitude,
                ST_Y(a.point) AS latitude,
                ST_Distance(
                    a.point::geography,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                ) AS distance_metres
             FROM arret a
             WHERE a.id IN ($placeholders)
             ORDER BY a.point::geography <-> ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
             LIMIT 1",
            array_merge([$lng, $lat], $ids, [$lng, $lat])
        )->getRowArray();

        return $results ?: null;
    }

    /**
     * Retourne tous les arrêts avec leurs coordonnées pour l'affichage Leaflet.
     *
     * @return array Arrêts avec longitude, latitude, et GeoJSON
     */
    public function getAllWithCoords(): array
    {
        return $this->db->query(
            "SELECT
                id,
                nom,
                ST_X(point) AS longitude,
                ST_Y(point) AS latitude,
                ST_AsGeoJSON(point) AS geojson
             FROM arret
             ORDER BY nom ASC"
        )->getResultArray();
    }

    /**
     * Retourne les noms de plusieurs arrêts en une seule requête.
     * Résultat : [ id => nom, ... ]
     *
     * @param array $ids  Liste d'IDs d'arrêts
     * @return array      Map [id => nom]
     */
    public function getNamesByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $ids = array_values($ids); // Réindexer les clés pour le bind de CI4

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->query(
            "SELECT id, nom FROM arret WHERE id IN ($placeholders)",
            $ids
        )->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row['nom'];
        }
        return $map;
    }
}
