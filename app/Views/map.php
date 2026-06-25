<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxi Be Madagascar</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
        }

        #map {
            height: 100vh;
            width: 100%;
        }

        .header {
            position: absolute;
            top: 20px;
            left: 50px;
            z-index: 1000;
            background: white;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>🚕 Taxi Be Tana</h1>
    </div>

    <!-- Container for the map -->
    <div id="map"></div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize the map and set its view to Antananarivo
            const map = L.map('map').setView([-18.91449, 47.53635], 13);

            // Add OpenStreetMap tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Custom icon for bus stops (optional, using default for now)
            const stopIcon = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/3448/3448339.png', // Just an example bus stop icon
                iconSize: [30, 30],
                iconAnchor: [15, 30],
                popupAnchor: [0, -30]
            });

            // Fetch stops from the API
            fetch('/api/arrets')
                .then(response => response.json())
                .then(data => {
                    data.forEach(arret => {
                        // Leaflet uses [latitude, longitude]
                        const lat = parseFloat(arret.latitude);
                        const lng = parseFloat(arret.longitude);

                        if (!isNaN(lat) && !isNaN(lng)) {
                            // Add marker to the map
                            const marker = L.marker([lat, lng], { icon: stopIcon }).addTo(map);

                            // Add a popup with the stop name
                            marker.bindPopup(`<b>${arret.nom}</b>`);
                        }
                    });
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des arrêts:', error);
                });
        });
    </script>
</body>

</html>