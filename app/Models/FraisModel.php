<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * FraisModel
 *
 * Gère le tarif actuel (table `frais`) et son historique (table `historique_frais`).
 *
 * Normalisation : quand on change le tarif, on update `frais`
 * ET on insère un enregistrement dans `historique_frais`.
 */
class FraisModel extends Model
{
    protected $table      = 'frais';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = ['montant'];
    protected $useTimestamps = false;

    // -------------------------------------------------------------------------
    // Méthodes métier
    // -------------------------------------------------------------------------

    /**
     * Retourne le montant actuel (le seul enregistrement dans frais).
     *
     * @return float
     */
    public function getMontantActuel(): float
    {
        $row = $this->first();
        return $row ? (float)$row['montant'] : 600.0;
    }

    /**
     * Met à jour le tarif ET enregistre le changement dans historique_frais.
     *
     * @param float $nouveauMontant
     * @return bool
     */
    public function changerTarif(float $nouveauMontant): bool
    {
        $frais = $this->first();
        if (!$frais) {
            return false;
        }

        // Update du tarif courant
        $this->update($frais['id'], ['montant' => $nouveauMontant]);

        // Insertion dans l'historique (normalisation)
        $this->db->query(
            "INSERT INTO historique_frais (id_frais, montant) VALUES (?, ?)",
            [$frais['id'], $nouveauMontant]
        );

        return true;
    }

    /**
     * Retourne tout l'historique des changements de tarif.
     *
     * @return array
     */
    public function getHistorique(): array
    {
        return $this->db->query(
            "SELECT hf.id, hf.montant, hf.date_changement
             FROM historique_frais hf
             ORDER BY hf.date_changement DESC"
        )->getResultArray();
    }
}
