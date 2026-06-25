# 1. Créer la base (dans psql)
CREATE DATABASE taxibe;

# 2. Adapter le mot de passe dans .env
# database.default.password = <ton_mdp>

# 3. Lancer les migrations
php spark migrate

# 4. Insérer les données de test
php spark db:seed TaxiBeSeeder

# 5. Vérifier
php spark serve
