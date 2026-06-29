## Tech:
CodeIgniter + PostGres (PostGIS) + Leaflet 

## Fonctionnalite:
- gestion de bus et de trajets (CRUD)
- l'user donne sa position et la ou il veut aller, et le systeme propose les bus a perndre et l'arret le plus pres de lui
- optimisation de frais (mais pas encore urgent)
- API: Overpass API pour avoir les quarters: recherche des arrets dans ce quartiers


## Conception BD:
- table bus: id, nom
- table trajet: id, id_bus, description
- table arret: id, nom, point (geometry)
- table trajet_arret: id, id_trajet, id_arret, ordre

