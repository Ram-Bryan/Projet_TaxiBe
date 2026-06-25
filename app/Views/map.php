<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxi Be Madagascar - Administration & Recherche</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

    <style>
        body, html { margin: 0; padding: 0; height: 100%; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        #map { height: 100vh; width: 100%; position: absolute; top:0; left:0; z-index: 1;}
        .panel {
            position: absolute; top: 10px; left: 10px; z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            width: 320px; max-height: 95vh; overflow-y: auto;
            backdrop-filter: blur(5px);
        }
        h1 { font-size: 18px; margin-top:0; padding-bottom: 10px; border-bottom: 2px solid #007bff; }
        h2 { font-size: 15px; margin-top: 15px; margin-bottom: 10px; color: #444;}
        .form-group { margin-bottom: 10px; }
        input[type="text"], input[type="number"] {
            width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 12px;
        }
        button {
            width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: background 0.2s;
        }
        button:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #138496; }
        hr { border: 1px solid #eee; margin: 15px 0; }
        .list-group { list-style: none; padding: 0; margin: 0; }
        .list-group li { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .list-group li:hover { background: #f0f8ff; }
        .status { font-size: 12px; color: green; margin-top: 5px; display: none; font-weight:bold; }
        
        /* Onglets */
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .nav-tabs { display: flex; border-bottom: 2px solid #ccc; margin-bottom: 15px; }
        .nav-tabs button { flex:1; background: none; color:#555; border:none; padding:10px; cursor:pointer; font-size: 13px; font-weight: bold;}
        .nav-tabs button.active { border-bottom: 2px solid #007bff; color:#007bff; }
        
        /* Toggle Switch Depart/Arrivee */
        .toggle-group { display: flex; gap: 5px; margin-bottom: 10px; }
        .toggle-group button { flex:1; padding: 8px; font-size: 12px; border: 1px solid #ccc; background: white; color: #555;}
        .toggle-group button.active-dep { background: #dc3545; color: white; border-color: #dc3545;}
        .toggle-group button.active-arr { background: #28a745; color: white; border-color: #28a745;}

        #posMsg { font-size:11px; color:blue; margin-top:5px; margin-bottom:10px;}
        
        /* Marker custom CSS */
        .marker-dep { background-color: #dc3545; border-radius: 50%; color: white; display: flex; justify-content: center; align-items: center; box-shadow: 0 0 10px #dc3545; font-size: 14px;}
        .marker-arr { background-color: #28a745; border-radius: 50%; color: white; display: flex; justify-content: center; align-items: center; box-shadow: 0 0 10px #28a745; font-size: 14px;}
    </style>
</head>
<body>
    <div id="map"></div>
    <div class="panel">
        <h1>🚕 Taxi Be Madagascar</h1>
        
        <div class="nav-tabs">
            <button class="active" onclick="switchTab('tab-recherche', this)">Recherche</button>
            <button onclick="switchTab('tab-admin', this)">Admin (Dev B)</button>
        </div>

        <!-- TAB RECHERCHE -->
        <div id="tab-recherche" class="tab-content active">
            <h2>Trouver un Itinéraire</h2>
            <div class="toggle-group">
                <button type="button" id="btnToggleDep" class="active-dep">Départ (Rouge)</button>
                <button type="button" id="btnToggleArr">Arrivée (Vert)</button>
            </div>
            
            <p style="font-size:11px;color:#666; margin-top:0;">1) Choisissez le mode ci-dessus.<br>2) Cliquez sur la carte ou utilisez "Me Géolocaliser".</p>
            <button type="button" id="btnGeoloc" class="btn-info">📍 Me Géolocaliser</button>
            <div id="posMsg"></div>

            <button type="button" id="btnDoSearch" class="btn-success">Rechercher les Bus</button>
            <hr>
            <div id="searchResults"></div>
        </div>

        <!-- TAB ADMIN -->
        <div id="tab-admin" class="tab-content">
            <h2>Créer un Bus</h2>
            <form id="formBus">
                <div class="form-group">
                    <input type="text" id="nomBus" required placeholder="Nom: Ex: Taxi-Be 006">
                </div>
                <button type="submit">Ajouter Bus</button>
                <div id="statusBus" class="status">Bus enregistré !</div>
            </form>
            <hr>
            <h2>Créer un Arrêt</h2>
            <p style="font-size:11px;color:#666; font-style:italic;">Cliquez sur la carte pour choisir la position</p>
            <form id="formArret">
                <div class="form-group">
                    <input type="text" id="nomArret" required placeholder="Nom de l'arrêt">
                </div>
                <div class="form-group" style="display:flex;gap:5px;">
                    <input type="text" id="latArret" readonly required placeholder="Lat">
                    <input type="text" id="lngArret" readonly required placeholder="Lng">
                </div>
                <button type="submit">Ajouter Arrêt</button>
                <div id="statusArret" class="status">Arrêt enregistré !</div>
            </form>
            <hr>
            <h2>Voir les Trajets</h2>
            <button type="button" id="btnLoadTrajets" class="btn-info" style="margin-bottom:10px;">Lister les Trajets (Polylignes)</button>
            <ul id="listTrajets" class="list-group"></ul>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>

        const DOM = {
            toggles: { dep: document.getElementById('btnToggleDep'), arr: document.getElementById('btnToggleArr') }
        };
        let appState = {
            currentMapTab: 'tab-recherche',
            searchMode: 'depart' // 'depart' ou 'arrivee'
        };

        function switchTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(e => e.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            document.querySelectorAll('.nav-tabs button').forEach(e => e.classList.remove('active'));
            btn.classList.add('active');
            appState.currentMapTab = tabId;
        }

        // Toggle Events
        DOM.toggles.dep.addEventListener('click', () => {
            appState.searchMode = 'depart';
            DOM.toggles.dep.classList.add('active-dep');
            DOM.toggles.arr.classList.remove('active-arr');
        });
        DOM.toggles.arr.addEventListener('click', () => {
            appState.searchMode = 'arrivee';
            DOM.toggles.arr.classList.add('active-arr');
            DOM.toggles.dep.classList.remove('active-dep');
        });

        document.addEventListener('DOMContentLoaded', function() {
            const map = L.map('map').setView([-18.91449, 47.53635], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' }).addTo(map);

            const stopIcon = L.icon({ iconUrl: 'https://cdn-icons-png.flaticon.com/512/3448/3448339.png', iconSize: [24,24], iconAnchor: [12,24], popupAnchor: [0,-24] });
            const iconDep = L.divIcon({ className: 'marker-dep', html: '📍', iconSize: [30,30], iconAnchor: [15,30] });
            const iconArr = L.divIcon({ className: 'marker-arr', html: '🏁', iconSize: [30,30], iconAnchor: [15,30] });

            let adminTempMarker = null;
            let routingFull    = null; // Routing complet (bleu)
            let routingSegment = null; // Routing portion départ→arrivée (vert)
            let markerDep = null;
            let markerArr = null;

            // Variables de session de recherche
            let lastFoundDepId = null;
            let lastFoundArrId = null;
            let circleDep = null;
            let circleArr = null;

            map.on('click', function(e) {
                if (appState.currentMapTab === 'tab-admin') {
                    document.getElementById('latArret').value = e.latlng.lat.toFixed(6);
                    document.getElementById('lngArret').value = e.latlng.lng.toFixed(6);
                    if (adminTempMarker) map.removeLayer(adminTempMarker);
                    adminTempMarker = L.marker(e.latlng).addTo(map).bindPopup("<b>Nouvel arrêt</b>").openPopup();
                } 
                else if (appState.currentMapTab === 'tab-recherche') {
                    if (appState.searchMode === 'depart') {
                        if (markerDep) map.removeLayer(markerDep);
                        markerDep = L.marker(e.latlng, {icon: iconDep}).addTo(map);
                        setupMarkerRemoval(markerDep, 'depart');
                    } else {
                        if (markerArr) map.removeLayer(markerArr);
                        markerArr = L.marker(e.latlng, {icon: iconArr}).addTo(map);
                        setupMarkerRemoval(markerArr, 'arrivee');
                    }
                }
            });

            function setupMarkerRemoval(marker, mode) {
                marker.on('click', function(e) {
                    if (appState.currentMapTab === 'tab-recherche' && appState.searchMode === mode) {
                        map.removeLayer(marker);
                        if (mode === 'depart') markerDep = null;
                        else markerArr = null;
                        L.DomEvent.stopPropagation(e);
                    }
                });
            }

            function loadBaseArrets() {
                fetch('/api/arrets').then(r=>r.json()).then(data => {
                    if (Array.isArray(data)) {
                        data.forEach(a => {
                            const lat = parseFloat(a.latitude); const lng = parseFloat(a.longitude);
                            if (!isNaN(lat) && !isNaN(lng)) {
                                L.marker([lat, lng], {icon: stopIcon, interactive: false, opacity: 0.6}).addTo(map); // Points de référence
                            }
                        });
                    }
                }).catch(() => console.warn("DB manquante"));
            }
            loadBaseArrets();

            /* ---------- RECHERCHE ITINERAIRE ---------- */
            document.getElementById('btnGeoloc').addEventListener('click', function() {
                const msg = document.getElementById('posMsg');
                msg.textContent = "Obtention...";
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        pos => {
                            const latlng = [pos.coords.latitude, pos.coords.longitude];
                            msg.textContent = `${appState.searchMode === 'depart' ? 'Départ' : 'Arrivée'} placé par Géoloc`;
                            
                            if (appState.searchMode === 'depart') {
                                if (markerDep) map.removeLayer(markerDep);
                                markerDep = L.marker(latlng, {icon: iconDep}).addTo(map);
                                setupMarkerRemoval(markerDep, 'depart');
                            } else {
                                if (markerArr) map.removeLayer(markerArr);
                                markerArr = L.marker(latlng, {icon: iconArr}).addTo(map);
                                setupMarkerRemoval(markerArr, 'arrivee');
                            }
                            map.setView(latlng, 14);
                        },
                        err => { msg.textContent = "Erreur: " + err.message; },
                        { enableHighAccuracy: true }
                    );
                } else msg.textContent = "Non supporté";
            });

            document.getElementById('btnDoSearch').addEventListener('click', function() {
                const resDiv = document.getElementById('searchResults');
                if(!markerDep || !markerArr) {
                    resDiv.innerHTML = '<span style="color:red;font-size:12px;">Veuillez définir un Départ (Rouge) ET une Arrivée (Vert).</span>';
                    return;
                }

                resDiv.innerHTML = '<i>Recherche en cours...</i>';
                const payload = {
                    lat_dep: markerDep.getLatLng().lat, lng_dep: markerDep.getLatLng().lng,
                    lat_arr: markerArr.getLatLng().lat, lng_arr: markerArr.getLatLng().lng
                };

                fetch('/api/search', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                })
                .then(r => { if(!r.ok) throw new Error(); return r.json(); })
                .then(data => {
                    if(data.error) { resDiv.innerHTML = `<span style="color:red;font-size:12px;">${data.error}</span>`; return; }

                    const dep    = data.arret_depart;
                    const arr    = data.arret_arrivee;
                    const trajets = data.trajets;

                    // Sauvegarder pour le sub-routing (seulement si le départ est trouvé)
                    lastFoundDepId = dep ? dep.id : null;
                    lastFoundArrId = arr ? arr.id : null;

                    // Nettoyer les anciens cercles
                    if (circleDep) map.removeLayer(circleDep);
                    if (circleArr) map.removeLayer(circleArr);
                    circleDep = null; circleArr = null;

                    let html = '';

                    if (dep) {
                        html += `<p style="font-size:13px; margin-bottom:5px;">Marche Départ ➡️ <b>${dep.nom}</b>: <b>${dep.distance_metres}m</b></p>`;
                        circleDep = L.circleMarker([dep.latitude, dep.longitude], {color:'red', fillColor:'red', fillOpacity: 0.2, radius:15, weight:4}).addTo(map);
                    }
                    if (arr) {
                        html += `<p style="font-size:13px; margin-top:0;">Arrêt Arrivée ➡️ <b>${arr.nom}</b>: <b>${arr.distance_metres}m</b></p>`;
                        circleArr = L.circleMarker([arr.latitude, arr.longitude], {color:'green', fillColor:'green', fillOpacity: 0.2, radius:15, weight:4}).addTo(map);
                    }

                    if (!trajets || trajets.length === 0) {
                        html += '<p style="color:orange;font-size:12px; font-weight:bold;">Aucun bus direct reliant ces deux arrêts dans ce sens de parcours.</p>';
                    } else {
                        html += '<ul class="list-group">';
                        trajets.forEach(t => {
                            html += `
                                <li onclick="window.showPolyline(${t.id})">
                                    <span><b>${t.nom_bus}</b><br><small style="color:#666;">${t.description}</small></span>
                                    <span style="font-size:16px;">🛣️</span>
                                </li>`;
                        });
                        html += '</ul>';
                    }
                    resDiv.innerHTML = html;

                    // Afficher automatiquement le trajet du premier résultat
                    if (trajets && trajets.length > 0) {
                        window.showPolyline(trajets[0].id);
                    }
                }).catch(() => { resDiv.innerHTML = '<span style="color:red;font-size:12px;">Erreur de connexion BD (mot de passe Postgres).</span>'; });

            });


            /* ---------- FONCTIONS MUTUALISÉES ---------- */
            window.showPolyline = function(idTrajet) {
                fetch('/api/trajets/' + idTrajet)
                .then(r => r.json())
                .then(data => {
                    if (routingFull)    { map.removeControl(routingFull);    routingFull    = null; }
                    if (routingSegment) { map.removeControl(routingSegment); routingSegment = null; }
                    if (!data.arrets || data.arrets.length === 0) return;

                    const allWaypoints = data.arrets.map(a => L.latLng(parseFloat(a.latitude), parseFloat(a.longitude)));

                    // Déterminer s'il y a un segment à surligner
                    let startIndex = -1, endIndex = -1;
                    if (lastFoundDepId && lastFoundArrId) {
                        startIndex = data.arrets.findIndex(a => parseInt(a.id) === parseInt(lastFoundDepId));
                        endIndex   = data.arrets.findIndex(a => parseInt(a.id) === parseInt(lastFoundArrId));
                    }
                    const doHighlight = startIndex !== -1 && endIndex !== -1 && startIndex <= endIndex;

                    // Trajet complet en BLEU (toujours visible, le vert se superpose dessus)
                    routingFull = L.Routing.control({
                        waypoints: allWaypoints,
                        routeWhileDragging: false,
                        router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                        lineOptions: { styles: [{ color: '#1a73e8', opacity: 0.5, weight: 4 }] },
                        show: false, addWaypoints: false, fitSelectedRoutes: !doHighlight, showAlternatives: false,
                        createMarker: function() { return null; }
                    }).addTo(map);

                    // Portion départ → arrivée en VERT
                    if (doHighlight) {
                        const subWaypoints = data.arrets
                            .slice(startIndex, endIndex + 1)
                            .map(a => L.latLng(parseFloat(a.latitude), parseFloat(a.longitude)));

                        routingSegment = L.Routing.control({
                            waypoints: subWaypoints,
                            routeWhileDragging: false,
                            router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                            lineOptions: { styles: [{ color: '#28a745', opacity: 0.95, weight: 7 }] },
                            show: false, addWaypoints: false, fitSelectedRoutes: true, showAlternatives: false,
                            createMarker: function() { return null; }
                        }).addTo(map);
                    }
                }).catch(e => console.error(e));
            };


            /* ---------- ADMIN EXAMPLES ---------- */
            document.getElementById('formBus').addEventListener('submit', function(e) {
                e.preventDefault();
                fetch('/api/bus', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({nom:document.getElementById('nomBus').value}) })
                .then(r=>r.json()).then(d=>{ if(d.status==='success'){ this.reset(); const s=document.getElementById('statusBus'); s.style.display='block'; setTimeout(()=>s.style.display='none',3000); } });
            });

            document.getElementById('formArret').addEventListener('submit', function(e) {
                e.preventDefault();
                fetch('/api/arrets', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({nom:document.getElementById('nomArret').value, lat:document.getElementById('latArret').value, lng:document.getElementById('lngArret').value}) })
                .then(r=>r.json()).then(d=>{ if(d.status==='success'){ this.reset(); const s=document.getElementById('statusArret'); s.style.display='block'; setTimeout(()=>s.style.display='none',3000); if(adminTempMarker){adminTempMarker.closePopup(); adminTempMarker.setIcon(stopIcon); adminTempMarker=null;} } });
            });

            document.getElementById('btnLoadTrajets').addEventListener('click', function() {
                const ul = document.getElementById('listTrajets'); ul.innerHTML = '<li style="font-size:11px;">Chargement...</li>';
                fetch('/api/trajets').then(r=>r.json()).then(data=>{
                    ul.innerHTML = '';
                    if(!Array.isArray(data) || data.length===0) return ul.innerHTML = '<li style="font-size:12px;">Aucun trajet</li>';
                    data.forEach(t=>{ ul.innerHTML += `<li onclick="window.showPolyline(${t.id})"><span><b>${t.nom_bus}</b><br><small>${t.description}</small></span>🛣️</li>`; });
                }).catch(()=>ul.innerHTML = '<li style="color:red;font-size:11px;">Erreur DB</li>');
            });
        });
    </script>
</body>
</html>