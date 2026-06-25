<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxi Be Madagascar - Administration</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        #map { height: 100vh; width: 100%; position: absolute; top:0; left:0; z-index: 1;}
        .panel {
            position: absolute; top: 20px; left: 20px; z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            padding: 20px; border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            width: 320px; max-height: 90vh; overflow-y: auto;
            backdrop-filter: blur(5px);
        }
        h1, h2 { margin-top: 0; color: #333; }
        h1 { font-size: 20px; padding-bottom: 10px; border-bottom: 2px solid #007bff; }
        h2 { font-size: 16px; margin-top: 20px; }
        .form-group { margin-bottom: 10px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #555; }
        input[type="text"], input[type="number"], select {
            width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 12px;
        }
        button {
            width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: background 0.3s;
        }
        button:hover { background: #0056b3; }
        .btn-success { background: #28a745; margin-bottom: 10px; }
        .btn-success:hover { background: #218838; }
        hr { border: 1px solid #eee; margin: 15px 0; }
        .list-group { list-style: none; padding: 0; margin: 0; }
        .list-group li { 
            padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;
        }
        .list-group li:hover { background: #f0f8ff; }
        .status { font-size: 12px; color: green; margin-top: 5px; display: none; font-weight:bold; }
    </style>
</head>
<body>
    <div id="map"></div>
    <div class="panel">
        <h1>🚕 Taxi Be Admin</h1>
        
        <!-- Form Bus -->
        <h2>Créer un Bus</h2>
        <form id="formBus">
            <div class="form-group">
                <label for="nomBus">Nom / Ligne</label>
                <input type="text" id="nomBus" required placeholder="Ex: Taxi-Be 006">
            </div>
            <button type="submit">Enregistrer le Bus</button>
            <div id="statusBus" class="status">Bus enregistré avec succès !</div>
        </form>

        <hr>

        <!-- Form Arrêt -->
        <h2>Créer un Arrêt</h2>
        <p style="font-size:11px;color:#666; font-style:italic;">Cliquez sur la carte pour positionner l'arrêt</p>
        <form id="formArret">
            <div class="form-group">
                <label for="nomArret">Nom de l'arrêt</label>
                <input type="text" id="nomArret" required placeholder="Nom">
            </div>
            <div class="form-group" style="display:flex;gap:10px;">
                <div style="flex:1;">
                    <label>Latitude</label>
                    <input type="text" id="latArret" readonly required placeholder="Auto">
                </div>
                <div style="flex:1;">
                    <label>Longitude</label>
                    <input type="text" id="lngArret" readonly required placeholder="Auto">
                </div>
            </div>
            <button type="submit">Enregistrer l'Arrêt</button>
            <div id="statusArret" class="status">Arrêt enregistré avec succès !</div>
        </form>

        <hr>

        <!-- Affichage Trajets -->
        <h2>Trajets Existants</h2>
        <button type="button" id="btnLoadTrajets" class="btn-success">Actualiser les Trajets</button>
        <ul id="listTrajets" class="list-group"></ul>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const map = L.map('map').setView([-18.91449, 47.53635], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            const stopIcon = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/3448/3448339.png',
                iconSize: [24, 24], iconAnchor: [12, 24], popupAnchor: [0, -24]
            });

            // Gérer les clics sur la carte pour ajouter un arrêt
            let tempMarker = null;
            map.on('click', function(e) {
                document.getElementById('latArret').value = e.latlng.lat.toFixed(6);
                document.getElementById('lngArret').value = e.latlng.lng.toFixed(6);
                
                if (tempMarker) map.removeLayer(tempMarker);
                tempMarker = L.marker(e.latlng).addTo(map).bindPopup("<b>Nouvel arrêt</b><br>Remplissez le formulaire").openPopup();
            });

            // Charger les points initiaux
            function loadArrets() {
                fetch('/api/arrets')
                    .then(r => { if (!r.ok) throw new Error(); return r.json(); })
                    .then(data => {
                        if (Array.isArray(data)) {
                            data.forEach(a => {
                                const lat = parseFloat(a.latitude);
                                const lng = parseFloat(a.longitude);
                                if (!isNaN(lat) && !isNaN(lng)) {
                                    L.marker([lat, lng], {icon: stopIcon}).addTo(map).bindPopup(`<b>${a.nom}</b>`);
                                }
                            });
                        }
                    }).catch(e => console.warn("Impossible de charger les arrêts (Base de données config?)"));
            }
            loadArrets();

            // Interagir CRUD Bus
            document.getElementById('formBus').addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button'); btn.disabled = true;
                
                fetch('/api/bus', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({nom: document.getElementById('nomBus').value})
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    const st = document.getElementById('statusBus');
                    if(data.status === 'success') {
                        st.style.display = 'block'; st.style.color = 'green'; st.textContent = 'Bus créé !';
                        this.reset();
                        setTimeout(() => st.style.display='none', 3000);
                    } else {
                        st.style.display = 'block'; st.style.color = 'red'; st.textContent = 'Erreur serveur';
                    }
                }).catch(() => { btn.disabled = false; });
            });

            // Interagir CRUD Arret
            document.getElementById('formArret').addEventListener('submit', function(e) {
                e.preventDefault();
                const payload = {
                    nom: document.getElementById('nomArret').value,
                    lat: document.getElementById('latArret').value,
                    lng: document.getElementById('lngArret').value
                };
                fetch('/api/arrets', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                })
                .then(r => r.json())
                .then(data => {
                    const st = document.getElementById('statusArret');
                    if(data.status === 'success') {
                        st.style.display = 'block'; st.style.color = 'green'; st.textContent = 'Arrêt créé !';
                        this.reset();
                        if (tempMarker) {
                            tempMarker.closePopup();
                            tempMarker.setIcon(stopIcon);
                            tempMarker.bindPopup(`<b>${payload.nom}</b>`);
                            tempMarker = null; 
                        }
                        setTimeout(() => st.style.display='none', 3000);
                    } else {
                        st.style.display = 'block'; st.style.color = 'red'; st.textContent = 'Erreur serveur';
                    }
                });
            });

            // Charger les trajets et afficher la polyline
            let currentPolyline = null;
            document.getElementById('btnLoadTrajets').addEventListener('click', function() {
                const ul = document.getElementById('listTrajets');
                ul.innerHTML = '<li>Chargement...</li>';
                
                fetch('/api/trajets')
                .then(r => r.json())
                .then(data => {
                    ul.innerHTML = '';
                    if(!Array.isArray(data) || data.length === 0) {
                        ul.innerHTML = '<li style="color:#888;">Aucun trajet trouvé</li>';
                        return;
                    }
                    data.forEach(t => {
                        const li = document.createElement('li');
                        li.innerHTML = `<span><b>${t.nom_bus}</b><br><small>${t.description}</small></span> <span style="font-size:18px;">🛣️</span>`;
                        li.onclick = () => showPolyline(t.id);
                        ul.appendChild(li);
                    });
                }).catch(() => { ul.innerHTML = '<li style="color:red;">Non configurable/BDD vide</li>'; });
            });

            function showPolyline(idTrajet) {
                fetch('/api/trajets/' + idTrajet)
                .then(r => r.json())
                .then(data => {
                    if (currentPolyline) map.removeLayer(currentPolyline);
                    
                    if(data.arrets && data.arrets.length > 0) {
                        const latLngs = data.arrets.map(a => [parseFloat(a.latitude), parseFloat(a.longitude)]);
                        currentPolyline = L.polyline(latLngs, {color: '#007bff', weight: 4, opacity: 0.8}).addTo(map);
                        map.fitBounds(currentPolyline.getBounds());
                    } else {
                        alert("Ce trajet n'a aucun arrêt assigné !");
                    }
                }).catch(e => console.error(e));
            }
        });
    </script>
</body>
</html>