## Tech:
CodeIgniter + PostGres (PostGIS) + Leaflet 

## Fonctionnalite:

- Backoffice:
    - CRUD arret, trajet et bus

- Frontoffice:
    - geolocalisation: on peut savoir via geolocalistaiont ou se trouve l'user
    - arrive et depart donne --> savoir le chemin
    - possiblite de plusieur trajets/bus qui se chevauchent:
    - optimisation des frais

## Conception BD:
- table bus: id, nom
- table trajet: id, id_bus, description
- table arret: id, nom, point (geometry)
- table trajet_arret: id, id_trajet, id_arret, ordre

