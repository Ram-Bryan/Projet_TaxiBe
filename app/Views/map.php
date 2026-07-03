<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaxiBe - Trouver un itinéraire</title>
    <meta name="description" content="Trouvez votre bus à Antananarivo facilement avec TaxiBe Madagascar.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css"/>

    <style>
        :root {
            --primary:        #8D6E63;
            --primary-dark:   #5D4037;
            --primary-light:  #D7CCC8;
            --primary-xlight: #EFEBE9;
            --surface:        #FFFFFF;
            --surface-2:      #FAF7F5;
            --text:           #3E2723;
            --text-muted:     #795548;
            --text-light:     #BCAAA4;
            --danger:         #D32F2F;
            --orange:         #E65100;
            --success:        #2E7D32;
            --info:           #1565C0;
            --route-blue:     #1a73e8;
            --route-brown:    #A1887F;
            --shadow-sm:      0 1px 4px rgba(0,0,0,.08);
            --shadow-md:      0 4px 16px rgba(0,0,0,.12);
            --radius:         12px;
            --radius-sm:      8px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, html { height: 100%; font-family: 'Inter', sans-serif; color: var(--text); }

        #map { position: fixed; inset: 0; z-index: 1; }

        /* TOPBAR */
        .topbar {
            position: fixed; top: 0; left: 0; right: 0; height: 56px;
            background: var(--primary-dark); display: flex; align-items: center;
            justify-content: space-between; padding: 0 16px;
            z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,.2);
        }
        .topbar-brand { display: flex; align-items: center; gap: 9px; color: white; font-weight: 700; font-size: 1rem; }
        .brand-icon { width: 32px; height: 32px; background: rgba(255,255,255,.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .topbar-right { display: flex; align-items: center; gap: 8px; }
        .user-chip { display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); border-radius: 20px; padding: 4px 10px 4px 7px; color: white; font-size: 0.82rem; font-weight: 500; }
        .btn-icon-top { width: 34px; height: 34px; border: none; border-radius: 8px; background: rgba(255,255,255,.15); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .2s; }
        .btn-icon-top:hover { background: rgba(255,255,255,.28); }

        /* SIDE PANEL */
        .side-panel {
            position: fixed; top: 66px; left: 12px;
            width: 358px; max-height: calc(100vh - 78px);
            z-index: 900; display: flex; flex-direction: column; gap: 8px;
            pointer-events: none;
        }
        .card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); overflow: hidden; pointer-events: all; display: flex; flex-direction: column; }
        #searchCard { flex-shrink: 0; max-height: calc(100vh - 120px); }
        .card-body { padding: 12px 14px; overflow-y: auto; }
        #resultsCard { flex: 1; min-height: 0; }
        @keyframes slideIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }

        /* SEARCH CARD */
        .card-head { padding: 12px 14px 10px; border-bottom: 1px solid var(--primary-xlight); display: flex; justify-content: space-between; align-items: center; }
        .card-head h2 { font-size: 0.9rem; font-weight: 600; color: var(--primary-dark); display: flex; align-items: center; gap: 7px; }
        .card-body { padding: 12px 14px; }

        .mode-toggle { display: flex; gap: 5px; margin-bottom: 10px; }
        .mode-btn { flex:1; padding: 7px; border: 1.5px solid var(--primary-light); border-radius: var(--radius-sm); background: white; cursor: pointer; font-family: inherit; font-size: 0.8rem; font-weight: 500; color: var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 5px; transition: all .2s; }
        .mode-btn.dep-active { background: #FFEBEE; border-color: var(--danger); color: var(--danger); }
        .mode-btn.arr-active { background: #E8F5E9; border-color: var(--success); color: var(--success); }

        .input-row { display: flex; align-items: center; gap: 9px; padding: 9px 12px; border-radius: var(--radius-sm); border: 1.5px solid var(--primary-light); cursor: pointer; transition: border-color .2s, background .2s; margin-bottom: 5px; }
        .input-row:hover { border-color: var(--primary); background: var(--primary-xlight); }
        .input-row.filled { background: var(--primary-xlight); border-color: var(--primary); }
        .dot { width: 13px; height: 13px; border-radius: 50%; flex-shrink: 0; }
        .dot-dep { background: var(--danger); box-shadow: 0 0 0 3px rgba(211,47,47,.18); }
        .dot-arr { background: var(--success); box-shadow: 0 0 0 3px rgba(46,125,50,.18); }
        .input-lbl { flex:1; font-size: 0.83rem; color: var(--text); }
        .input-lbl.ph { color: var(--text-light); }
        .connector { width: 2px; height: 10px; background: var(--primary-light); margin: 1px 0 1px 18px; }
        .clear-btn { background: none; border: none; cursor: pointer; color: var(--text-light); padding: 0; display: flex; align-items: center; }
        .clear-btn:hover { color: var(--text-muted); }

        .bottom-row { display: flex; gap: 7px; align-items: center; justify-content: space-between; margin-top: 7px; }
        .geo-btn { display: flex; align-items: center; gap: 5px; font-size: 0.78rem; color: var(--info); font-weight: 500; cursor: pointer; background: none; border: none; font-family: inherit; }
        .geo-btn:hover { text-decoration: underline; }
        .btn-reset { font-size: 0.75rem; color: var(--text-muted); cursor: pointer; background: none; border: 1px solid var(--primary-light); border-radius: 6px; padding: 5px 9px; font-family: inherit; display: flex; align-items: center; gap: 4px; transition: all .2s; }
        .btn-reset:hover { background: var(--primary-xlight); }

        #posMsg { font-size: 0.75rem; color: var(--info); margin-top: 5px; min-height: 16px; }

        .btn-search { width: 100%; padding: 10px; background: var(--primary-dark); color: white; border: none; border-radius: var(--radius-sm); font-family: inherit; font-size: 0.88rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px; transition: background .2s; margin-top: 9px; }
        .btn-search:hover:not(:disabled) { background: var(--primary); }
        .btn-search:disabled { opacity: .45; cursor: not-allowed; }

        /* RESULTS CARD */
        .results-inner { flex: 1; overflow-y: auto; min-height: 0; }

        .stops-banner { display: flex; align-items: center; gap: 7px; padding: 7px 12px; background: var(--primary-xlight); font-size: 0.78rem; color: var(--primary-dark); border-bottom: 1px solid var(--primary-light); flex-shrink: 0; }

        /* Result item */
        .result-item { border-bottom: 1px solid var(--primary-xlight); }
        .result-header { display: flex; gap: 10px; align-items: flex-start; padding: 11px 13px; cursor: pointer; transition: background .15s; }
        .result-header:hover { background: var(--surface-2); }
        .result-header.active-item { background: #EBF3FD; }

        .result-icon { width: 34px; height: 34px; border-radius: 8px; background: var(--primary-xlight); flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: var(--primary-dark); }
        .result-info { flex: 1; min-width: 0; }
        .result-route { font-size: 0.85rem; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .result-sub { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }
        .result-badges { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 5px; }
        .badge { padding: 2px 7px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .b-price { background: #FFF3E0; color: var(--orange); }
        .b-time  { background: #E8F5E9; color: var(--success); }
        .b-dist  { background: var(--primary-xlight); color: var(--primary-dark); }
        .chevron-btn { display: flex; align-items: center; color: var(--text-light); margin-top: 2px; transition: transform .25s; }
        .chevron-btn.open { transform: rotate(180deg); }

        /* Inline detail */
        .result-detail-wrapper {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.3s ease-in-out;
            background: #F8F5F3;
        }
        .result-detail-wrapper.open {
            grid-template-rows: 1fr;
        }
        .result-detail-inner {
            overflow: hidden;
        }
        .result-detail {
            padding: 12px 13px 14px 28px;
            border-top: 1px solid transparent;
            transition: border-color 0.3s ease;
        }
        .result-detail-wrapper.open .result-detail {
            border-top: 1px solid var(--primary-xlight);
        }

        .step-list { display: flex; flex-direction: column; gap: 0; }
        .step-row { display: flex; gap: 10px; align-items: flex-start; position: relative; padding-bottom: 2px; }
        .step-row:not(:last-child)::after { content:''; position:absolute; left:12px; top:26px; width:2px; height:calc(100% - 14px); background: var(--primary-light); z-index:0; }
        .step-dot { width:24px; height:24px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; position:relative; z-index:1; }
        .sd-dep  { background:#FFEBEE; border:2px solid var(--danger); color:var(--danger); }
        .sd-xfer { background:#FFF3E0; border:2px solid #FF8F00; color:#E65100; }
        .sd-arr  { background:#E8F5E9; border:2px solid var(--success); color:var(--success); }
        .sd-bus  { background:var(--primary-xlight); border:2px solid var(--primary); color:var(--primary-dark); }
        .step-txt { flex:1; padding: 3px 0 8px; }
        .step-name { font-size: 0.83rem; font-weight: 600; color: var(--text); }
        .step-sub-txt { font-size: 0.73rem; color: var(--text-muted); margin-top: 1px; }

        /* Speed selector */
        .speed-row { display:flex; gap:4px; flex-wrap:wrap; margin-top:8px; align-items:center; }
        .speed-lbl { font-size: 0.72rem; color: var(--text-muted); margin-right:3px; }
        .speed-btn { padding: 3px 8px; border:1px solid var(--primary-light); border-radius:12px; font-size:0.7rem; font-weight:500; cursor:pointer; background:white; color:var(--text-muted); font-family:inherit; transition:all .15s; }
        .speed-btn.active { background:var(--primary-dark); color:white; border-color:var(--primary-dark); }

        /* Voir plus button */
        .voir-plus-btn { width:100%; padding:9px; background:none; border:none; border-top:1px solid var(--primary-xlight); font-family:inherit; font-size:0.8rem; font-weight:600; color:var(--primary); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:5px; transition:background .15s; }
        .voir-plus-btn:hover { background:var(--primary-xlight); }

        /* FABs */
        .fab-group { position:fixed; bottom:18px; right:14px; display:flex; flex-direction:column; gap:7px; z-index:900; }
        .fab { width:42px; height:42px; border-radius:11px; background:var(--surface); box-shadow:var(--shadow-md); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--primary-dark); transition:all .2s; }
        .fab:hover { background:var(--primary-dark); color:white; transform:scale(1.06); }

        /* Info pill */
        .info-pill { position:fixed; bottom:14px; left:50%; transform:translateX(-50%); background:rgba(62,39,35,.9); color:white; padding:7px 18px; border-radius:20px; font-size:0.8rem; font-weight:500; box-shadow:var(--shadow-md); display:none; z-index:900; white-space:nowrap; }
        @keyframes fadeUp { from{opacity:0;transform:translateX(-50%) translateY(8px);} to{opacity:1;transform:translateX(-50%) translateY(0);} }

        /* Empty / loading */
        .empty-state { text-align:center; padding:20px 10px; }
        .empty-state p { font-size:0.82rem; color:var(--text-muted); margin-top:7px; }
        .spinner { width:16px; height:16px; border:2.5px solid var(--primary-light); border-top-color:var(--primary-dark); border-radius:50%; animation:spin .7s linear infinite; display:inline-block; }
        @keyframes spin { to { transform:rotate(360deg); } }

        ::-webkit-scrollbar { width:4px; }
        ::-webkit-scrollbar-thumb { background:var(--primary-light); border-radius:2px; }

        @media(max-width:600px) { .side-panel { width:calc(100vw - 24px); } }
    </style>
</head>
<body>
    <div id="map"></div>

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-brand">
            <div class="brand-icon"><i data-lucide="bus" style="width:16px;height:16px;color:white;"></i></div>
            TaxiBe Madagascar
        </div>
        <div class="topbar-right">
            <div class="user-chip">
                <i data-lucide="user" style="width:13px;height:13px;"></i>
                <?= session()->get('nom') ?>
            </div>
            <a href="/logout">
                <button class="btn-icon-top" title="Déconnexion">
                    <i data-lucide="log-out" style="width:15px;height:15px;"></i>
                </button>
            </a>
        </div>
    </header>

    <!-- SIDE PANEL -->
    <div class="side-panel">

        <!-- CARD 1 : SEARCH -->
        <div class="card" id="searchCard">
            <div class="card-head">
                <h2><i data-lucide="navigation" style="width:15px;height:15px;color:var(--primary);"></i> Trouver un itinéraire</h2>
            </div>
            <div class="card-body">
                <div class="mode-toggle">
                    <button class="mode-btn dep-active" id="btnDep" onclick="setMode('depart')">
                        <i data-lucide="map-pin" style="width:12px;height:12px;"></i> Départ
                    </button>
                    <button class="mode-btn" id="btnArr" onclick="setMode('arrivee')">
                        <i data-lucide="flag" style="width:12px;height:12px;"></i> Arrivée
                    </button>
                </div>

                <div class="input-row" id="depRow" onclick="setMode('depart')">
                    <span class="dot dot-dep"></span>
                    <span class="input-lbl ph" id="depLbl">Cliquez sur la carte — départ</span>
                    <button class="clear-btn" id="clearDep" style="display:none;" onclick="clearPt('dep',event)">
                        <i data-lucide="x" style="width:13px;height:13px;"></i>
                    </button>
                </div>
                <div class="connector"></div>
                <div class="input-row" id="arrRow" onclick="setMode('arrivee')">
                    <span class="dot dot-arr"></span>
                    <span class="input-lbl ph" id="arrLbl">Cliquez sur la carte — arrivée</span>
                    <button class="clear-btn" id="clearArr" style="display:none;" onclick="clearPt('arr',event)">
                        <i data-lucide="x" style="width:13px;height:13px;"></i>
                    </button>
                </div>

                <div class="bottom-row">
                    <button class="geo-btn" onclick="doGeoloc()">
                        <i data-lucide="crosshair" style="width:13px;height:13px;"></i> Me Géolocaliser
                    </button>
                    <button class="btn-reset" onclick="clearAll()">
                        <i data-lucide="rotate-ccw" style="width:11px;height:11px;"></i> Réinit.
                    </button>
                </div>
                <div id="posMsg"></div>

                <button class="btn-search" id="btnSearch" disabled onclick="doSearch()">
                    <i data-lucide="search" style="width:15px;height:15px;"></i>
                    Rechercher les Bus
                </button>
            </div>
        </div>

        <!-- CARD 2 : RESULTS + INLINE DETAIL -->
        <div class="card" id="resultsCard" style="display:none; animation:slideIn .25s ease;">
            <div class="card-head">
                <h2><i data-lucide="list-ordered" style="width:15px;height:15px;color:var(--primary);"></i> Itinéraires disponibles</h2>
                <button class="btn-reset" onclick="clearResults()">
                    <i data-lucide="x" style="width:11px;height:11px;"></i> Effacer
                </button>
            </div>
            <div id="stopsBanner" style="display:none;" class="stops-banner">
                <i data-lucide="map-pin" style="width:12px;height:12px;flex-shrink:0;"></i>
                <span id="stopsBannerTxt"></span>
            </div>
            <div class="results-inner" id="resultsList"></div>
        </div>
    </div>

    <!-- FABs -->
    <div class="fab-group">
        <button class="fab" title="Zoom +" onclick="gMap&&gMap.setZoom(gMap.getZoom()+1)">
            <i data-lucide="plus" style="width:17px;height:17px;"></i>
        </button>
        <button class="fab" title="Zoom -" onclick="gMap&&gMap.setZoom(gMap.getZoom()-1)">
            <i data-lucide="minus" style="width:17px;height:17px;"></i>
        </button>
        <button class="fab" title="Recentrer" onclick="gMap&&gMap.setView([-18.91449,47.53635],13)">
            <i data-lucide="locate" style="width:17px;height:17px;"></i>
        </button>
    </div>

    <div class="info-pill" id="infoPill"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>
    lucide.createIcons();

    // ===== STATE =====
    let gMap = null;
    let searchMode    = 'depart';
    let markerDep     = null, markerArr = null;
    let routeLines    = [];   // L.polyline / L.Routing.control instances
    let highlightLayer = null;
    let arretsLayer    = null;
    let moyensData     = [];
    let trajetDistances = {}, trajetLegsMap = {}, trajetDetailsMap_cache = {};
    let activeComboId  = null;
    let allResultsVisible = false;
    let resultsData    = [];   // all combos

    // ===== ICONS =====
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

    const iconStopDep  = iconTrajetDep;
    const iconStopXfer = makeCustomMarker('#FBC02D','rgba(251,192,45,.5)', svgBus, 'Changement de bus');
    const iconStopArr  = iconTrajetArr;
    
    // For intermediate stops (just a small circle)
    const iconStopInter = L.divIcon({ className:'', html:`<div title="Arrêt" style="width:16px;height:16px;background:#1a73e8;border-radius:50%;border:2px solid white;box-shadow:0 1px 4px rgba(26,115,232,.4);"></div>`, iconSize:[16,16], iconAnchor:[8,8] });

    // Icons for simple manual clicking
    const iconClickDep = L.divIcon({ className:'', html:`<div style="width:20px;height:20px;background:#D32F2F;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(211,47,47,.5);"></div>`, iconSize:[20,20], iconAnchor:[10,10] });
    const iconClickArr = L.divIcon({ className:'', html:`<div style="width:20px;height:20px;background:#2E7D32;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(46,125,50,.5);"></div>`, iconSize:[20,20], iconAnchor:[10,10] });

    // ===== MAP INIT =====
    document.addEventListener('DOMContentLoaded', () => {
        gMap = L.map('map', { zoomControl:false }).setView([-18.91449,47.53635], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OSM' }).addTo(gMap);

        arretsLayer    = L.layerGroup().addTo(gMap);
        highlightLayer = L.layerGroup().addTo(gMap);

        loadBaseArrets();
        fetch('/api/moyens').then(r=>r.json()).then(d=>{ moyensData=d; }).catch(()=>{});

        gMap.on('click', e => {
            searchMode === 'depart' ? placePt('dep', e.latlng) : placePt('arr', e.latlng);
        });
        showPill('Cliquez sur la carte pour définir votre point de départ');
    });

    function loadBaseArrets() {
        fetch('/api/arrets').then(r=>r.json()).then(data=>{
            arretsLayer.clearLayers();
            if (!Array.isArray(data)) return;
            data.forEach(a => {
                const lat=parseFloat(a.latitude), lng=parseFloat(a.longitude);
                if (!isNaN(lat)&&!isNaN(lng))
                    L.marker([lat,lng],{icon:mkStop,interactive:true,opacity:.75}).addTo(arretsLayer).bindPopup(`<b>${a.nom}</b>`);
            });
        }).catch(()=>{});
    }

    // ===== POINTS =====
    function placePt(type, latlng) {
        if (type==='dep') {
            if (markerDep) gMap.removeLayer(markerDep);
            markerDep = L.marker(latlng,{icon:iconClickDep}).addTo(gMap).bindPopup('<b>Départ</b>');
        } else {
            if (markerArr) gMap.removeLayer(markerArr);
            markerArr = L.marker(latlng,{icon:iconClickArr}).addTo(gMap).bindPopup('<b>Arrivée</b>');
        }
        updateLabels(); updateSearchBtn();
        if (type==='dep' && !markerArr) setTimeout(()=>setMode('arrivee'),300);
    }

    function setMode(m) {
        searchMode = m;
        document.getElementById('btnDep').className = 'mode-btn'+(m==='depart'?' dep-active':'');
        document.getElementById('btnArr').className = 'mode-btn'+(m==='arrivee'?' arr-active':'');
        showPill(m==='depart' ? 'Cliquez sur la carte — départ (rouge)' : 'Cliquez sur la carte — arrivée (vert)');
    }

    function updateLabels() {
        const set = (lblId, clearId, rowId, marker, label) => {
            const lbl=document.getElementById(lblId), cl=document.getElementById(clearId), row=document.getElementById(rowId);
            if (marker) { const ll=marker.getLatLng(); lbl.textContent=`${ll.lat.toFixed(5)}, ${ll.lng.toFixed(5)}`; lbl.classList.remove('ph'); cl.style.display='flex'; row.classList.add('filled'); }
            else { lbl.textContent=label; lbl.classList.add('ph'); cl.style.display='none'; row.classList.remove('filled'); }
        };
        set('depLbl','clearDep','depRow',markerDep,'Cliquez sur la carte — départ');
        set('arrLbl','clearArr','arrRow',markerArr,'Cliquez sur la carte — arrivée');
    }

    function updateSearchBtn() { document.getElementById('btnSearch').disabled = !(markerDep&&markerArr); }

    function clearPt(t,e) {
        if(e) e.stopPropagation();
        if(t==='dep'&&markerDep){gMap.removeLayer(markerDep);markerDep=null;}
        if(t==='arr'&&markerArr){gMap.removeLayer(markerArr);markerArr=null;}
        updateLabels(); updateSearchBtn();
    }

    function clearAll() { clearPt('dep'); clearPt('arr'); clearResults(); }

    function clearResults() {
        document.getElementById('resultsCard').style.display='none';
        clearRouteLayers();
    }

    function clearRouteLayers() {
        routeLines.forEach(r=>{ try{ if(r.remove) r.remove(); else if(r.addTo) gMap.removeLayer(r); }catch(e){} });
        routeLines=[];
        highlightLayer.clearLayers();
        activeComboId=null;
    }

    // ===== GEOLOC =====
    let accuracyCircle = null;

function doGeoloc() {
    const msg = document.getElementById("posMsg");
    const geoBtn = document.querySelector('.geo-btn');

    if (!navigator.geolocation) {
        msg.textContent = "La géolocalisation n'est pas supportée par votre navigateur.";
        return;
    }

    msg.innerHTML = '<span class="spinner"></span> Localisation en cours...';
    geoBtn.disabled = true;

    // Un seul appel suffit en WiFi : les fixes successifs n'améliorent
    // quasiment jamais la précision, contrairement au GPS.
    navigator.geolocation.getCurrentPosition(
        (position) => {
            geoBtn.disabled = false;
            const { latitude: lat, longitude: lng, accuracy } = position.coords;
            const acc = Math.round(accuracy);

            msg.textContent = accuracyMessage(acc);

            const ll = L.latLng(lat, lng);
            placePt(searchMode, ll);
            showAccuracyCircle(ll, accuracy);
            gMap.setView(ll, accuracyToZoom(accuracy));
        },
        (err) => {
            geoBtn.disabled = false;
            handleGeoError(err, msg);
        },
        {
            enableHighAccuracy: true, // sans effet réel en WiFi pur, mais inoffensif
            timeout: 15000,           // un peu plus large : le lookup WiFi peut être lent en zone peu cartographiée
            maximumAge: 0         // réutilise un fix récent (< 1 min) au lieu de relancer une requête à chaque clic
        }
    );
}

function accuracyMessage(acc) {
    if (acc <= 30)  return `Position trouvée (±${acc} m) — bonne précision`;
    if (acc <= 150) return `Position trouvée (±${acc} m) — précision WiFi standard`;
    return `Position approximative (±${acc} m) — ajustez si besoin en cliquant sur la carte`;
}

function accuracyToZoom(acc) {
    if (acc <= 30)   return 17;
    if (acc <= 100)  return 16;
    if (acc <= 300)  return 15;
    if (acc <= 1000) return 13;
    return 11;
}

function showAccuracyCircle(latlng, accuracy) {
    if (accuracyCircle) gMap.removeLayer(accuracyCircle);
    accuracyCircle = L.circle(latlng, {
        radius: accuracy,
        color: '#1565C0',
        fillColor: '#1565C0',
        fillOpacity: 0.08,
        weight: 1
    }).addTo(gMap);
}

function handleGeoError(err, msg) {
    switch (err.code) {
        case err.PERMISSION_DENIED:
            msg.textContent = "Localisation refusée. Autorisez l'accès dans les paramètres du site (icône 🔒 à côté de l'URL).";
            break;
        case err.POSITION_UNAVAILABLE:
            msg.textContent = "Position indisponible (WiFi/réseau insuffisant). Cliquez directement sur la carte.";
            break;
        case err.TIMEOUT:
            msg.textContent = "Délai dépassé. Réessayez ou cliquez directement sur la carte.";
            break;
        default:
            msg.textContent = "Erreur de géolocalisation. Cliquez directement sur la carte.";
    }
}

    // ===== SEARCH =====
    function doSearch() {
        if (!markerDep||!markerArr) return;
        const btn=document.getElementById('btnSearch');
        btn.innerHTML='<span class="spinner"></span> Recherche...'; btn.disabled=true;
        clearRouteLayers();
        allResultsVisible=false; activeComboId=null;

        fetch('/api/search',{
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({ lat_dep:markerDep.getLatLng().lat, lng_dep:markerDep.getLatLng().lng, lat_arr:markerArr.getLatLng().lat, lng_arr:markerArr.getLatLng().lng })
        })
        .then(r=>{if(!r.ok)throw new Error(); return r.json();})
        .then(data=>{
            btn.innerHTML='<i data-lucide="search" style="width:15px;height:15px;"></i> Rechercher les Bus'; btn.disabled=false;
            lucide.createIcons();
            renderResults(data);
        })
        .catch(()=>{
            btn.innerHTML='<i data-lucide="search" style="width:15px;height:15px;"></i> Rechercher les Bus'; btn.disabled=false;
            lucide.createIcons();
        });
    }

    // ===== RENDER RESULTS =====
    function renderResults(data) {
        const card=document.getElementById('resultsCard');
        const list=document.getElementById('resultsList');
        const banner=document.getElementById('stopsBanner');
        card.style.display='flex';

        if (data.arret_depart&&data.arret_arrivee) {
            document.getElementById('stopsBannerTxt').innerHTML=`<b>${data.arret_depart.nom}</b> → <b>${data.arret_arrivee.nom}</b>`;
            banner.style.display='flex';
            lucide.createIcons();
        } else banner.style.display='none';

        if (!data.trajets||data.trajets.length===0) {
            list.innerHTML=`<div class="empty-state"><i data-lucide="bus-off" style="width:28px;height:28px;color:var(--text-light);"></i><p>Aucun itinéraire trouvé.<br>Essayez d'autres points.</p></div>`;
            lucide.createIcons(); return;
        }

        resultsData = data.trajets;
        const allIds=[];
        data.trajets.forEach(t=>t.legs.forEach(l=>{if(!allIds.includes(l.id))allIds.push(l.id);}));
        const busObj=moyensData.find(m=>m.nom.toLowerCase().includes('bus'));
        const busSpeed=busObj?busObj.vitesse:30;

        list.innerHTML=`<div style="text-align:center;padding:14px;"><span class="spinner"></span></div>`;

        Promise.all(allIds.map(id=>fetch('/api/trajets/'+id).then(r=>r.json())))
        .then(details=>{
            const dm={};
            allIds.forEach((id,i)=>{ dm[id]=details[i]; });
            trajetDetailsMap_cache=dm;
            trajetDistances={}; trajetLegsMap={};

            list.innerHTML='';

            data.trajets.forEach((t,idx)=>{
                const cid='combo-'+idx;
                trajetLegsMap[cid]=t.legs;

                // compute distance
                let dist=0;
                t.legs.forEach(leg=>{
                    const d=dm[leg.id];
                    if(d&&d.arrets){
                        const fi=d.arrets.findIndex(a=>parseInt(a.id)===parseInt(leg.from_arret));
                        const ti=d.arrets.findIndex(a=>parseInt(a.id)===parseInt(leg.to_arret));
                        if(fi!==-1&&ti!==-1&&fi<=ti){
                            const pts=d.arrets.slice(fi,ti+1).map(a=>L.latLng(parseFloat(a.latitude),parseFloat(a.longitude)));
                            for(let j=0;j<pts.length-1;j++) dist+=pts[j].distanceTo(pts[j+1]);
                        }
                    }
                });
                trajetDistances[cid]=dist;

                const distStr=dist>=1000?`${(dist/1000).toFixed(1)} km`:dist>0?`${Math.round(dist)} m`:'';
                const timeMin=dist>0?(dist/1000)/busSpeed*60:0;
                const timeStr=timeMin>0?formatTime(timeMin):'';
                const busNames=[...new Set(t.legs.map(l=>l.nom_bus))].join(' + ');

                const wrap=document.createElement('div');
                wrap.className='result-item';
                wrap.id='wrap-'+cid;
                // Hidden by default if idx > 0
                if (idx>0) wrap.style.display='none';
                let spdHtml='';
                if(moyensData.length>0) {
                    spdHtml=`<div class="speed-row">
                        <span class="speed-lbl">Vitesse :</span>`;
                    moyensData.forEach((m,i)=>{
                        spdHtml+=`<button class="speed-btn${i===0?' active':''}" onclick="calcSpeed('${cid}',${m.vitesse},this); event.stopPropagation();">${m.nom}</button>`;
                    });
                    spdHtml+=`</div>`;
                }

                wrap.innerHTML=`
                    <div class="result-header" id="hdr-${cid}" onclick="toggleResult('${cid}')">
                        <div class="result-icon"><i data-lucide="bus" style="width:15px;height:15px;"></i></div>
                        <div class="result-info">
                            <div class="result-route">${busNames}</div>
                            <div class="result-sub">${t.legs[0].from_arret_nom} → ${t.legs[t.legs.length-1].to_arret_nom}</div>
                            <div class="result-badges">
                                ${distStr?`<span class="badge b-dist">${distStr}</span>`:''}
                                ${timeStr?`<span class="badge b-time" id="btime-${cid}">~${timeStr}</span>`:''}
                                <span class="badge b-price">${t.prix_total} Ar</span>
                            </div>
                            ${spdHtml}
                        </div>
                        <div class="chevron-btn" id="chev-${cid}">
                            <i data-lucide="chevron-down" style="width:16px;height:16px;"></i>
                        </div>
                    </div>
                    <div class="result-detail-wrapper" id="det-${cid}">
                        <div class="result-detail-inner">
                            <div class="result-detail">
                                ${buildDetailHTML(t)}
                            </div>
                        </div>
                    </div>`;
                list.appendChild(wrap);
            });

            // Voir plus button
            if (data.trajets.length>1) {
                const vp=document.createElement('button');
                vp.className='voir-plus-btn'; vp.id='voirPlusBtn';
                vp.innerHTML=`<i data-lucide="chevrons-down" style="width:14px;height:14px;"></i> Voir ${data.trajets.length-1} autre${data.trajets.length>2?'s':''} itinéraire${data.trajets.length>2?'s':''}`;
                vp.onclick=toggleVoirPlus;
                list.appendChild(vp);
            }

            lucide.createIcons();

            // Auto-expand & show first
            toggleResult('combo-0');
        });
    }

    function buildDetailHTML(t) {
        let html='<div class="step-list">';
        t.legs.forEach((leg,i)=>{
            const isFirst=(i===0), isLast=(i===t.legs.length-1);
            if(isFirst) {
                html+=`<div class="step-row">
                    <div class="step-dot sd-dep"><i data-lucide="map-pin" style="width:11px;height:11px;"></i></div>
                    <div class="step-txt"><div class="step-name">${leg.from_arret_nom}</div><div class="step-sub-txt">Point de départ 🔴</div></div>
                </div>
                <div class="step-row">
                    <div class="step-dot sd-bus"><i data-lucide="bus" style="width:11px;height:11px;"></i></div>
                    <div class="step-txt"><div class="step-name">${leg.nom_bus}</div><div class="step-sub-txt">Jusqu'à ${leg.to_arret_nom}</div></div>
                </div>`;
            } else {
                html+=`<div class="step-row">
                    <div class="step-dot sd-xfer"><i data-lucide="arrow-left-right" style="width:10px;height:10px;"></i></div>
                    <div class="step-txt"><div class="step-name">${leg.from_arret_nom}</div><div class="step-sub-txt">Changer de bus ici 🟠 → ${leg.nom_bus}</div></div>
                </div>
                <div class="step-row">
                    <div class="step-dot sd-bus"><i data-lucide="bus" style="width:11px;height:11px;"></i></div>
                    <div class="step-txt"><div class="step-name">${leg.nom_bus}</div><div class="step-sub-txt">Jusqu'à ${leg.to_arret_nom}</div></div>
                </div>`;
            }
            if(isLast) {
                html+=`<div class="step-row">
                    <div class="step-dot sd-arr"><i data-lucide="flag" style="width:11px;height:11px;"></i></div>
                    <div class="step-txt"><div class="step-name">${leg.to_arret_nom}</div><div class="step-sub-txt">Destination finale 🟢</div></div>
                </div>`;
            }
        });
        html+='</div>';
        return html;
    }

    // ===== TOGGLE RESULT (collapse/expand) =====
    function toggleResult(cid) {
        const det=document.getElementById('det-'+cid);
        const chev=document.getElementById('chev-'+cid);
        const hdr=document.getElementById('hdr-'+cid);
        const isOpen=det.classList.contains('open');

        // Close all others
        document.querySelectorAll('.result-detail-wrapper.open').forEach(el=>{
            const oid=el.id.replace('det-','');
            el.classList.remove('open');
            document.getElementById('chev-'+oid)?.classList.remove('open');
            document.getElementById('hdr-'+oid)?.classList.remove('active-item');
        });

        if (!isOpen) {
            det.classList.add('open');
            chev.classList.add('open');
            hdr.classList.add('active-item');
            activeComboId=cid;
            drawRoute(cid);
        } else {
            clearRouteLayers();
        }
    }

    // ===== VOIR PLUS =====
    function toggleVoirPlus() {
        allResultsVisible=!allResultsVisible;
        const total=resultsData.length;
        document.querySelectorAll('.result-item').forEach((el,i)=>{
            if(i>0) el.style.display=allResultsVisible?'block':'none';
        });
        const btn=document.getElementById('voirPlusBtn');
        if(btn) btn.innerHTML=allResultsVisible
            ?`<i data-lucide="chevrons-up" style="width:14px;height:14px;"></i> Réduire`
            :`<i data-lucide="chevrons-down" style="width:14px;height:14px;"></i> Voir ${total-1} autre${total>2?'s':''} itinéraire${total>2?'s':''}`;
        lucide.createIcons();
    }

    function calcSpeed(cid, vitesse, btn) {
        btn.parentElement.querySelectorAll('.speed-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        const dist=trajetDistances[cid]||0;
        const badge=document.getElementById('btime-'+cid);
        if(badge&&dist>0) badge.textContent='~'+formatTime((dist/1000)/vitesse*60);
    }

    // ===== DRAW ROUTE =====
    function drawRoute(cid) {
        clearRouteLayers();
        const legs=trajetLegsMap[cid];
        const dm=trajetDetailsMap_cache;
        if(!legs||!dm) return;

        legs.forEach((leg, legIdx) => {
            const d=dm[leg.id];
            if(!d||!d.arrets||d.arrets.length<2) return;

            const fi=d.arrets.findIndex(a=>parseInt(a.id)===parseInt(leg.from_arret));
            const ti=d.arrets.findIndex(a=>parseInt(a.id)===parseInt(leg.to_arret));
            if(fi===-1||ti===-1) return;

            const allPts = d.arrets.map(a=>[parseFloat(a.latitude),parseFloat(a.longitude)]);
            const userPts = d.arrets.slice(fi,ti+1).map(a=>[parseFloat(a.latitude),parseFloat(a.longitude)]);

            // Draw FULL bus route in muted brown (parts NOT in user's path)
            if(fi>0) {
                const before=d.arrets.slice(0,fi+1).map(a=>[parseFloat(a.latitude),parseFloat(a.longitude)]);
                routeLines.push(L.polyline(before,{color:'#A1887F',weight:4,opacity:0.45}).addTo(gMap));
            }
            if(ti<d.arrets.length-1) {
                const after=d.arrets.slice(ti,d.arrets.length).map(a=>[parseFloat(a.latitude),parseFloat(a.longitude)]);
                routeLines.push(L.polyline(after,{color:'#A1887F',weight:4,opacity:0.45}).addTo(gMap));
            }

            // Draw user segment in blue (OSRM-routed)
            if(userPts.length>1){
                const wps=userPts.map(p=>L.latLng(p[0],p[1]));
                const rc=L.Routing.control({
                    waypoints:wps, routeWhileDragging:false,
                    router:L.Routing.osrmv1({serviceUrl:'https://router.project-osrm.org/route/v1'}),
                    lineOptions:{styles:[{color:'#1a73e8',opacity:.92,weight:6}]},
                    show:false, addWaypoints:false, fitSelectedRoutes:legIdx===legs.length-1, showAlternatives:false,
                    createMarker:()=>null
                }).addTo(gMap);
                routeLines.push(rc);
            }
        });

        // Place highlight stop markers (only stops the user visits)
        const stopsMap = new Map();

        legs.forEach((leg, i) => {
            const d=dm[leg.id];
            if(!d||!d.arrets) return;

            const fi=d.arrets.findIndex(a=>parseInt(a.id)===parseInt(leg.from_arret));
            const ti=d.arrets.findIndex(a=>parseInt(a.id)===parseInt(leg.to_arret));
            if(fi===-1||ti===-1) return;

            for(let k=fi; k<=ti; k++){
                const arr = d.arrets[k];
                let type = 'inter';
                if(i===0 && k===fi) type = 'dep';
                else if(i===legs.length-1 && k===ti) type = 'arr';
                else if(k===fi || k===ti) type = 'xfer'; 

                if(!stopsMap.has(arr.id) || type !== 'inter') {
                    stopsMap.set(arr.id, {
                        id: arr.id, nom: arr.nom, type: type,
                        lat: parseFloat(arr.latitude), lng: parseFloat(arr.longitude)
                    });
                }
            }
        });

        stopsMap.forEach(s=>{
            if(isNaN(s.lat)||isNaN(s.lng)) return;
            let icon;
            if(s.type==='dep')       icon=iconStopDep;
            else if(s.type==='xfer') icon=iconStopXfer;
            else if(s.type==='arr')  icon=iconStopArr;
            else                     icon=iconStopInter;

            const label=s.type==='dep'?'🔴 Départ':s.type==='xfer'?'🟠 Changement de bus':s.type==='arr'?'🟢 Arrivée':'🔵 Arrêt du trajet';
            L.marker([s.lat,s.lng],{icon,zIndexOffset: s.type==='inter'?900:1000}).addTo(highlightLayer)
                .bindPopup(`<b>${label}</b><br>${s.nom}`);
        });
    }

    function getLatLng(detail, arretId, coord) {
        if(!detail||!detail.arrets) return NaN;
        const a=detail.arrets.find(x=>parseInt(x.id)===parseInt(arretId));
        return a ? parseFloat(coord==='lat'?a.latitude:a.longitude) : NaN;
    }

    // ===== UTILS =====
    function formatTime(minutes) {
        if(minutes<0) return '0 min';
        const h=Math.floor(minutes/60), m=Math.round(minutes%60);
        let r=''; if(h>0) r+=h+'h '; if(m>0||r==='') r+=m+' min';
        return r.trim();
    }

    function showPill(msg) {
        const pill=document.getElementById('infoPill');
        pill.textContent=msg; pill.style.display='block'; pill.style.animation='fadeUp .2s ease';
        clearTimeout(pill._t); pill._t=setTimeout(()=>{ pill.style.display='none'; },4000);
    }
    </script>
</body>
</html>