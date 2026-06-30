<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backoffice - TaxiBe Madagascar</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

    <style>
        :root {
            --primary: #8D6E63;
            --primary-dark: #a46653ff;
            --primary-light: #D7CCC8;
            --bg-color: #F5F5F6;
            --surface: #FFFFFF;
            --text-main: #3E2723;
            --text-muted: #795548;
            --danger: #D32F2F;
            --success: #2E7D32;
        }

        body, html {
            margin: 0; padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            overflow: hidden;
        }

        .app-container {
            display: flex;
            height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: var(--surface);
            border-right: 1px solid var(--primary-light);
            display: flex;
            flex-direction: column;
            z-index: 10;
        }

        .sidebar-header {
            height: 60px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            border-bottom: 1px solid var(--primary-light);
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary-dark);
            gap: 10px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            list-style: none;
            margin: 0;
            overflow-y: auto;
        }

        .sidebar-nav li {
            padding: 0 15px;
            margin-bottom: 5px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: var(--text-main);
            text-decoration: none;
            border-radius: 8px;
            gap: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar-nav a:hover {
            background-color: rgba(141, 110, 99, 0.1);
            color: var(--primary-dark);
        }

        .sidebar-nav a.active {
            background-color: var(--primary);
            color: white;
        }

        /* Main Content */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            min-width: 0;
        }

        .topbar {
            height: 60px;
            background-color: var(--surface);
            border-bottom: 1px solid var(--primary-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            z-index: 5;
        }

        .topbar-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--danger);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 6px 12px;
            border: 1px solid var(--danger);
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background-color: var(--danger);
            color: white;
        }

        /* Content Area */
        .content-area {
            flex: 1;
            display: flex;
            position: relative;
            min-height: 0;
            min-width: 0;
        }

        .crud-panel {
            width: 350px;
            background-color: var(--surface);
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            z-index: 2;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .crud-panel-header {
            padding: 20px;
            border-bottom: 1px solid var(--primary-light);
        }

        .crud-panel-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: var(--primary-dark);
        }

        .crud-panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .map-container {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        #map {
            width: 100%;
            height: 100%;
        }

        /* Forms & Lists */
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        .btn-primary {
            width: 100%;
            padding: 10px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-danger-sm {
            padding: 4px 8px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .data-list {
            list-style: none;
            padding: 0;
            margin: 20px 0 0 0;
        }
        .data-list li {
            padding: 12px;
            border: 1px solid var(--primary-light);
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafafa;
            cursor: pointer;
        }
        .data-list li:hover {
            border-color: var(--primary);
        }
        .data-list li .info {
            flex: 1;
        }
        .data-list li .title {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 2px;
        }
        .data-list li .desc {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .status-msg {
            font-size: 0.85rem;
            padding: 8px;
            border-radius: 4px;
            margin-top: 10px;
            display: none;
        }
        .status-msg.success { background: #E8F5E9; color: var(--success); display: block; }
        .status-msg.error { background: #FFEBEE; color: var(--danger); display: block; }

        .hidden { display: none !important; }

    </style>
</head>
<body>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i data-lucide="bus-front"></i>
                TaxiBe Admin
            </div>
            <ul class="sidebar-nav">
                <li><a href="#" class="active" data-view="bus"><i data-lucide="bus"></i> Gestion Bus</a></li>
                <li><a href="#" data-view="arret"><i data-lucide="map-pin"></i> Gestion Arrêts</a></li>
                <li><a href="#" data-view="trajet"><i data-lucide="route"></i> Gestion Trajets</a></li>
            </ul>
        </aside>

        <!-- Main Wrapper -->
        <main class="main-wrapper">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-title" id="topbarTitle">Dashboard</div>
                <div class="topbar-actions">
                    <span style="font-size: 0.9rem; font-weight: 500;"><i data-lucide="user" style="width:16px; height:16px; vertical-align:text-bottom;"></i> <?= session()->get('nom') ?></span>
                    <a href="/logout" class="btn-logout">
                        <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                        Quitter
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                
                <!-- CRUD Panel -->
                <div class="crud-panel" id="crudPanel">
                    <div class="crud-panel-header">
                        <h2 id="panelTitle">Vue d'ensemble</h2>
                    </div>
                    <div class="crud-panel-body" id="panelBody">
                        <!-- Contenu dynamique injecté ici -->
                        <div class="dashboard-stats" style="display:flex; flex-direction:column; gap:15px;">
                            <div style="background:var(--primary-light); padding:15px; border-radius:8px; text-align:center;">
                                <i data-lucide="bus" style="width:32px; height:32px; color:var(--primary-dark)"></i>
                                <h3>Gérez votre réseau</h3>
                                <p style="font-size:0.9rem;">Sélectionnez une option dans le menu de gauche pour commencer.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map Container -->
                <div class="map-container">
                    <div id="map"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>
        lucide.createIcons();

        let map;
        let globalArretsLayer;
        let highlightArretsLayer;
        let routesLayer;
        let tempMarker = null;
        let currentView = 'bus';

        let allArrets = []; 
        let selectedTrajetArrets = []; 
        let tempTrajetLine = null; 

        const svgPinDep = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>`;
        const svgFlagArr = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/></svg>`;
        const svgBus = `<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6v6"/><path d="M15 6v6"/><path d="M2 12h19.6"/><path d="M18 18h3s.5-1.7.8-2.8c.1-.4.2-.8.2-1.2 0-.4-.1-.8-.2-1.2l-1.4-5C20.1 6.8 19.1 6 18 6H4a2 2 0 0 0-2 2v10h3"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>`;

        function makeCustomMarker(color, glowColor, svgIcon, label) {
            return L.divIcon({ className:'', html:`
                <div title="${label}" style="width:34px;height:34px;background:${color};border-radius:50%;border:3px solid white;box-shadow:0 3px 12px ${glowColor};display:flex;align-items:center;justify-content:center;">
                  ${svgIcon}
                </div>`, iconSize:[34,34], iconAnchor:[17,17], popupAnchor:[0,-22] });
        }

        const mkStop = makeCustomMarker('#D32F2F', 'rgba(211,47,47,.3)', svgBus, 'Arrêt');

        const iconTrajetDep = makeCustomMarker('#D32F2F','rgba(211,47,47,.5)', svgPinDep, 'Départ');
        const iconTrajetArr = makeCustomMarker('#2E7D32','rgba(46,125,50,.5)', svgFlagArr, 'Arrivée');

        const iconDep = iconTrajetDep;
        const iconArr = iconTrajetArr;
        const iconStopInter = L.divIcon({ className:'', html:`<div title="Arrêt" style="width:16px;height:16px;background:#1a73e8;border-radius:50%;border:2px solid white;box-shadow:0 1px 4px rgba(26,115,232,.4);"></div>`, iconSize:[16,16], iconAnchor:[8,8] });

        // Initialisation de la carte
        function initMap() {
            map = L.map('map').setView([-18.91449, 47.53635], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' }).addTo(map);
            globalArretsLayer = L.layerGroup().addTo(map);
            highlightArretsLayer = L.layerGroup().addTo(map);
            routesLayer = L.layerGroup().addTo(map);

            map.on('click', function(e) {
                if (currentView === 'arret') {
                    if (tempMarker) map.removeLayer(tempMarker);
                    tempMarker = L.marker(e.latlng).addTo(map).bindPopup("<b>Nouvel arrêt</b>").openPopup();
                    
                    const latInput = document.getElementById('arretLat');
                    const lngInput = document.getElementById('arretLng');
                    if(latInput && lngInput) {
                        latInput.value = e.latlng.lat.toFixed(6);
                        lngInput.value = e.latlng.lng.toFixed(6);
                    }
                }
            });

            loadGlobalArrets();
        }

        function loadGlobalArrets() {
            fetch('/api/arrets').then(r=>r.json()).then(data => {
                allArrets = data;
                globalArretsLayer.clearLayers();
                data.forEach(a => {
                    const marker = L.marker([parseFloat(a.latitude), parseFloat(a.longitude)], {icon: mkStop})
                        .addTo(globalArretsLayer)
                        .bindPopup(`<b>${a.nom}</b>`);
                    
                    if(currentView === 'trajet') {
                        marker.on('click', () => handleTrajetArretClick(a));
                    }
                });
            });
        }

        // Navigation SPA
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.sidebar-nav a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const view = this.getAttribute('data-view');
                currentView = view;
                document.getElementById('topbarTitle').innerText = this.innerText;
                
                loadView(view);
            });
        });

        function loadView(view) {
            const panelTitle = document.getElementById('panelTitle');
            const panelBody = document.getElementById('panelBody');
            
            if (routesLayer.routingControl) {
                map.removeControl(routesLayer.routingControl);
                routesLayer.routingControl = null;
            }
            if (tempMarker) { map.removeLayer(tempMarker); tempMarker = null; }
            if (typeof resetTrajetSelection === 'function') resetTrajetSelection();
            highlightArretsLayer.clearLayers();

            switch(view) {
                case 'bus':
                    panelTitle.innerText = "Gestion des Bus";
                    panelBody.innerHTML = `
                        <form id="busForm">
                            <div class="form-group">
                                <label>Nom du Bus</label>
                                <input type="text" id="busNom" class="form-control" required placeholder="Ex: Ligne 1">
                            </div>
                            <button type="submit" class="btn-primary">Ajouter Bus</button>
                            <div id="busMsg" class="status-msg"></div>
                        </form>
                        <h3 style="margin-top:20px; font-size:1rem;">Liste des Bus</h3>
                        <ul class="data-list" id="busList"></ul>
                    `;
                    loadBusList();
                    document.getElementById('busForm').addEventListener('submit', handleBusSubmit);
                    break;
                case 'arret':
                    panelTitle.innerText = "Gestion des Arrêts";
                    panelBody.innerHTML = `
                        <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:15px;">
                            <i data-lucide="mouse-pointer-click" style="width:14px;height:14px;vertical-align:middle;"></i> 
                            Cliquez sur la carte pour définir les coordonnées.
                        </p>
                        <form id="arretForm">
                            <div class="form-group">
                                <label>Nom de l'Arrêt</label>
                                <input type="text" id="arretNom" class="form-control" required placeholder="Ex: Analakely">
                            </div>
                            <div style="display:flex; gap:10px;">
                                <div class="form-group" style="flex:1;">
                                    <label>Latitude</label>
                                    <input type="text" id="arretLat" class="form-control" readonly required>
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label>Longitude</label>
                                    <input type="text" id="arretLng" class="form-control" readonly required>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary">Ajouter Arrêt</button>
                            <div id="arretMsg" class="status-msg"></div>
                        </form>
                        <h3 style="margin-top:20px; font-size:1rem;">Liste des Arrêts</h3>
                        <ul class="data-list" id="arretList"></ul>
                    `;
                    lucide.createIcons();
                    loadArretList();
                    document.getElementById('arretForm').addEventListener('submit', handleArretSubmit);
                    break;
                case 'trajet':
                    panelTitle.innerText = "Gestion des Trajets";
                    panelBody.innerHTML = `
                        <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:15px;">
                            <i data-lucide="mouse-pointer-click" style="width:14px;height:14px;vertical-align:middle;"></i> 
                            Cliquez sur les arrêts sur la carte pour définir l'ordre du trajet.
                        </p>
                        <form id="trajetForm">
                            <div class="form-group">
                                <label>Sélectionner un Bus</label>
                                <select id="trajetBusId" class="form-control" required></select>
                            </div>
                            <div class="form-group">
                                <label>Description du Trajet</label>
                                <input type="text" id="trajetDesc" class="form-control" required placeholder="Ex: Aller (Nord -> Sud)">
                            </div>
                            
                            <div class="form-group">
                                <label>Arrêts sélectionnés (Ordre)</label>
                                <ul id="selectedArretsList" style="list-style:none; padding:0; font-size:0.85rem; border:1px solid #ccc; border-radius:6px; min-height:40px; padding:8px; background:#fafafa;">
                                    <li style="color:#999; font-style:italic;">Aucun arrêt sélectionné</li>
                                </ul>
                                <button type="button" class="btn-danger-sm" onclick="resetTrajetSelection()" style="width:100%; margin-top:5px; padding:8px;">Réinitialiser la sélection</button>
                            </div>

                            <button type="button" class="btn-primary" onclick="confirmTrajet()">Créer Trajet</button>
                            <div id="trajetMsg" class="status-msg"></div>
                        </form>
                        <h3 style="margin-top:20px; font-size:1rem;">Liste des Trajets</h3>
                        <ul class="data-list" id="trajetList"></ul>
                    `;
                    loadTrajetBusSelect();
                    loadTrajetList();
                    break;
            }
        }

        // --- BUS ---
        function loadBusList() {
            const ul = document.getElementById('busList');
            ul.innerHTML = '<li>Chargement...</li>';
            fetch('/api/bus').then(r=>r.json()).then(data => {
                ul.innerHTML = '';
                data.forEach(b => {
                    ul.innerHTML += `
                        <li>
                            <div class="info"><div class="title">${b.nom}</div></div>
                            <button class="btn-danger-sm" onclick="deleteItem('bus', ${b.id})">Supprimer</button>
                        </li>
                    `;
                });
            });
        }
        function handleBusSubmit(e) {
            e.preventDefault();
            const nom = document.getElementById('busNom').value;
            fetch('/api/bus', {
                method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({nom})
            }).then(r=>r.json()).then(d => {
                showMessage('busMsg', d.status === 'success' ? 'Bus ajouté !' : 'Erreur', d.status);
                if(d.status==='success') { document.getElementById('busForm').reset(); loadBusList(); }
            });
        }

        // --- ARRET ---
        function loadArretList() {
            const ul = document.getElementById('arretList');
            ul.innerHTML = '<li>Chargement...</li>';
            fetch('/api/arrets').then(r=>r.json()).then(data => {
                ul.innerHTML = '';
                data.forEach(a => {
                    // List
                    ul.innerHTML += `
                        <li onclick="focusMap(${a.latitude}, ${a.longitude})">
                            <div class="info">
                                <div class="title">${a.nom}</div>
                                <div class="desc">${a.latitude}, ${a.longitude}</div>
                            </div>
                            <button class="btn-danger-sm" onclick="event.stopPropagation(); deleteItem('arrets', ${a.id})">Suppr.</button>
                        </li>
                    `;
                });
            });
        }
        function handleArretSubmit(e) {
            e.preventDefault();
            const nom = document.getElementById('arretNom').value;
            const lat = document.getElementById('arretLat').value;
            const lng = document.getElementById('arretLng').value;
            fetch('/api/arrets', {
                method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({nom, lat, lng})
            }).then(r=>r.json()).then(d => {
                showMessage('arretMsg', d.status === 'success' ? 'Arrêt ajouté !' : 'Erreur', d.status);
                if(d.status==='success') { 
                    document.getElementById('arretForm').reset(); 
                    if(tempMarker) { map.removeLayer(tempMarker); tempMarker = null; }
                    loadArretList();
                    loadGlobalArrets(); 
                }
            });
        }

        // --- TRAJET ---
        function loadTrajetBusSelect() {
            const select = document.getElementById('trajetBusId');
            fetch('/api/bus').then(r=>r.json()).then(data => {
                select.innerHTML = '<option value="">Choisir un bus...</option>';
                data.forEach(b => {
                    select.innerHTML += `<option value="${b.id}">${b.nom}</option>`;
                });
            });
        }
        function loadTrajetList() {
            const ul = document.getElementById('trajetList');
            ul.innerHTML = '<li>Chargement...</li>';
            fetch('/api/trajets').then(r=>r.json()).then(data => {
                ul.innerHTML = '';
                data.forEach(t => {
                    ul.innerHTML += `
                        <li onclick="showTrajetOnMap(${t.id})">
                            <div class="info">
                                <div class="title">${t.description}</div>
                                <div class="desc"><i data-lucide="bus" style="width:12px;height:12px;"></i> ${t.nom_bus}</div>
                            </div>
                            <button class="btn-danger-sm" onclick="event.stopPropagation(); deleteItem('trajets', ${t.id})">Suppr.</button>
                        </li>
                    `;
                });
                lucide.createIcons();
            });
        }
        function handleTrajetArretClick(arret) {
            if(selectedTrajetArrets.find(a => a.id === arret.id)) return; 
            
            selectedTrajetArrets.push(arret);
            renderSelectedArrets();
            drawTempTrajetLine();
        }

        function renderSelectedArrets() {
            const ul = document.getElementById('selectedArretsList');
            if(!ul) return;
            
            if(selectedTrajetArrets.length === 0) {
                ul.innerHTML = '<li style="color:#999; font-style:italic;">Aucun arrêt sélectionné</li>';
                return;
            }
            
            ul.innerHTML = '';
            selectedTrajetArrets.forEach((a, index) => {
                ul.innerHTML += `<li style="padding:4px 0; border-bottom:1px solid #eee;"><b>${index + 1}.</b> ${a.nom}</li>`;
            });
        }

        function drawTempTrajetLine() {
            if(tempTrajetLine) map.removeLayer(tempTrajetLine);
            if(selectedTrajetArrets.length < 2) return;

            const latlngs = selectedTrajetArrets.map(a => [parseFloat(a.latitude), parseFloat(a.longitude)]);
            tempTrajetLine = L.polyline(latlngs, {color: 'var(--primary)', weight: 3, dashArray: '5, 10'}).addTo(map);
        }

        function resetTrajetSelection() {
            selectedTrajetArrets = [];
            if(tempTrajetLine) { map.removeLayer(tempTrajetLine); tempTrajetLine = null; }
            renderSelectedArrets();
        }

        function confirmTrajet() {
            const id_bus = document.getElementById('trajetBusId').value;
            const description = document.getElementById('trajetDesc').value;
            
            if(!id_bus || !description) {
                alert("Veuillez sélectionner un bus et fournir une description.");
                return;
            }
            if(selectedTrajetArrets.length < 2) {
                alert("Veuillez sélectionner au moins 2 arrêts sur la carte pour former un trajet.");
                return;
            }

            const confirmMsg = `Confirmez-vous la création de ce trajet avec ${selectedTrajetArrets.length} arrêts ?\n` + 
                               selectedTrajetArrets.map((a, i) => `${i+1}. ${a.nom}`).join('\n');
            
            if(confirm(confirmMsg)) {
                const arretIds = selectedTrajetArrets.map(a => a.id);
                fetch('/api/trajets', {
                    method: 'POST', headers: {'Content-Type':'application/json'}, 
                    body: JSON.stringify({id_bus, description, arrets: arretIds})
                }).then(r=>r.json()).then(d => {
                    showMessage('trajetMsg', d.status === 'success' ? 'Trajet créé !' : 'Erreur', d.status);
                    if(d.status==='success') { 
                        document.getElementById('trajetForm').reset(); 
                        resetTrajetSelection();
                        loadTrajetList(); 
                    }
                });
            }
        }

        function showTrajetOnMap(id) {
            fetch('/api/trajets/' + id).then(r=>r.json()).then(data => {
                if (routesLayer.routingControl) {
                    map.removeControl(routesLayer.routingControl);
                    routesLayer.routingControl = null;
                }
                highlightArretsLayer.clearLayers();

                if (!data.arrets || data.arrets.length === 0) {
                    alert("Ce trajet n'a pas encore d'arrêts.");
                    return;
                }

                const waypoints = data.arrets.map(a => L.latLng(parseFloat(a.latitude), parseFloat(a.longitude)));

                // Marqueurs consistants avec le frontoffice
                data.arrets.forEach((a, index) => {
                    const isDep = (index === 0);
                    const isArr = (index === data.arrets.length - 1);
                    const label = isDep ? '🔴 Départ' : isArr ? '🟢 Arrivée' : '🔵 Arrêt du trajet';
                    const icon = isDep ? iconDep : isArr ? iconArr : iconStopInter;

                    L.marker([parseFloat(a.latitude), parseFloat(a.longitude)], {icon: icon})
                        .addTo(highlightArretsLayer)
                        .bindPopup(`<b>${label}</b><br>${a.nom}`);
                });

                routesLayer.routingControl = L.Routing.control({
                    waypoints: waypoints,
                    routeWhileDragging: false,
                    router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                    lineOptions: { styles: [{ color: '#1a73e8', opacity: 0.85, weight: 6 }] },
                    show: false, addWaypoints: false, fitSelectedRoutes: true,
                    createMarker: function() { return null; }
                }).addTo(map);
            });
        }

        // --- UTILS ---
        function deleteItem(endpoint, id) {
            if(!confirm("Êtes-vous sûr de vouloir supprimer cet élément ?")) return;
            fetch('/api/' + endpoint + '/' + id, { method: 'DELETE' })
            .then(r=>r.json()).then(d => {
                if(d.status === 'success') {
                    if(endpoint === 'bus') loadBusList();
                    if(endpoint === 'arrets') loadArretList();
                    if(endpoint === 'trajets') loadTrajetList();
                } else {
                    alert("Erreur lors de la suppression.");
                }
            });
        }

        function showMessage(elementId, text, type) {
            const el = document.getElementById(elementId);
            el.innerText = text;
            el.className = 'status-msg ' + (type === 'success' ? 'success' : 'error');
            setTimeout(() => { el.className = 'status-msg hidden'; }, 3000);
        }

        function focusMap(lat, lng) {
            map.setView([lat, lng], 16);
        }

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            initMap();
            document.getElementById('topbarTitle').innerText = 'Gestion des Bus';
            loadView('bus');
        });

    </script>
</body>
</html>
