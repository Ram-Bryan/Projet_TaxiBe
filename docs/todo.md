# Todolist — Projet SIG Taxi Be Madagascar
> **Stack :** CodeIgniter · PostgreSQL / PostGIS · Leaflet  
> **Équipe :** 2 développeurs · **Durée :** 1 journée

---

## Légende
| Tag | Rôle |
|-----|------|
| `[Dev A]` | Backend + BDD (CodeIgniter, PostgreSQL, PostGIS, API) |
| `[Dev B]` | Frontend + Carte (HTML/CSS, Leaflet, JavaScript, UI) |
| `[Les 2]` | Tâche collaborative |

---

## 🌅 Matin (8h – 12h) — Setup & Structure

- [ ] `[Dev A]` Créer la BDD PostgreSQL + activer l'extension PostGIS *(30 min)*
- [ ] `[Dev A]` Écrire les migrations SQL : tables `bus`, `arret`, `trajet`, `trajet_arret` *(30 min)*
- [ ] `[Dev A]` Configurer CodeIgniter : connexion BDD, base URL, autoload *(30 min)*
- [ ] `[Dev A]` Créer les Models CI : `BusModel`, `ArretModel`, `TrajetModel` *(45 min)*
- [ ] `[Dev A]` Insérer les données de test : 5 bus, 10 arrêts, 2 trajets *(45 min)*
- [ ] `[Dev B]` Mettre en place le layout HTML + intégrer Leaflet via CDN *(30 min)*
- [ ] `[Dev B]` Afficher la carte Leaflet centrée sur Antananarivo *(20 min)*
- [ ] `[Dev B]` Récupérer les arrêts via fetch JSON et les afficher comme markers *(45 min)*

> ⚠️ **Point de synchro à 10h :** Dev A expose `/api/arrets` en JSON, Dev B consomme l'endpoint.  
> Aligner le format JSON à ce moment-là (ex : `{ id, nom, lat, lng }`).

---

## ☀️ Après-midi 1 (13h – 15h30) — CRUD Bus, Arrêts & Trajets

- [ ] `[Dev A]` CRUD Bus : routes + controllers (list, create, update, delete) *(45 min)*
- [ ] `[Dev A]` CRUD Arrêt : routes + controllers + conversion lat/lng → `POINT` PostGIS *(45 min)*
- [ ] `[Dev A]` CRUD Trajet : créer un trajet, affecter un bus + arrêts ordonnés *(60 min)*
- [ ] `[Dev B]` Formulaire CRUD Bus (HTML + JS fetch, sans rechargement de page) *(45 min)*
- [ ] `[Dev B]` Formulaire CRUD Arrêt + clic sur la carte pour poser un arrêt *(60 min)*
- [ ] `[Dev B]` Afficher le tracé d'un trajet (polyline Leaflet) au clic *(45 min)*

---

## 🌆 Après-midi 2 (15h30 – 18h) — Recherche d'itinéraire

- [ ] `[Dev A]` Endpoint API `/search` : reçoit position user + destination, retourne JSON *(60 min)*
- [ ] `[Dev A]` Requête PostGIS `ST_Distance` pour trouver l'arrêt le plus proche du user *(45 min)*
- [ ] `[Dev A]` Algorithme : trouver les trajets qui passent par l'arrêt départ **ET** destination dans le bon ordre *(45 min)*
- [ ] `[Dev B]` UI recherche : géolocalisation user + saisie de la destination *(45 min)*
- [ ] `[Dev B]` Afficher résultats sur la carte : marker user, arrêt proche, trajet proposé *(60 min)*
- [ ] `[Dev B]` Panel résultats : liste des bus, numéros d'arrêt, distance à pied *(45 min)*
- [ ] `[Les 2]` Test end-to-end + correction des bugs *(30 min)*

---

## 📌 Requêtes PostGIS de référence

### Arrêt le plus proche du user
```sql
SELECT a.id, a.nom,
       ST_Distance(
         a.geom::geography,
         ST_SetSRID(ST_MakePoint(:lng, :lat), 4326)::geography
       ) AS distance_metres
FROM arret a
ORDER BY distance_metres ASC
LIMIT 1;
```

### Bus desservant départ → destination (dans le bon sens)
```sql
SELECT DISTINCT t.id, b.nom AS bus, t.nom AS trajet
FROM trajet t
JOIN bus b ON b.id = t.id_bus
JOIN trajet_arret ta_dep  ON ta_dep.id_trajet  = t.id
JOIN trajet_arret ta_dest ON ta_dest.id_trajet = t.id
WHERE ta_dep.id_arret   = :id_arret_depart
  AND ta_dest.id_arret  = :id_arret_destination
  AND ta_dep.ordre < ta_dest.ordre;
```

---

## 🗄️ Schéma BD (version corrigée)

| Table | Champs clés |
|-------|-------------|
| `bus` | `id`, `nom`|
| `arret` | `id`, `nom`, `geom` (PostGIS POINT),  |
| `trajet` | `id`, `id_bus`, `description` |
| `trajet_arret` | `id`, `id_trajet`, `id_arret`, `ordre` |

> **Changement clé vs conception initiale :** `bus_arret` remplacé par `trajet_arret` avec les champs `ordre` (obligatoire pour le sens du trajet) Un bus peut avoir plusieurs trajets.

---

                                                              
- [ ] Optimisation de frais (calcul du trajet le moins cher)
- [ ] Correspondances : bus A → arrêt X → bus B
- [ ] Estimation du temps de trajet
- [ ] Affichage en temps réel de la position des bus