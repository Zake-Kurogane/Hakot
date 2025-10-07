<?php
session_start();
// If not logged in, redirect
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Hakot | Dashboard</title>
  <link rel="icon" type="image/x-icon" href="img/hakot-icon.png">
  <link rel="stylesheet" type="text/css" href="navs.css"/>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  
  <!-- Custom CSS -->
  <style>
    /* -------------------------------------------------
       GENERIC STYLES – original code is retained
       ------------------------------------------------- */
    body {
      font-family: Arial, sans-serif;
      background-color: #f9f9f9;
      margin: 0;
    }
    .loading-screen {
      position: fixed;
      top: 0; 
      left: 0;
      width: 100%; 
      height: 100%;
      background: rgba(255, 255, 255, 0.8);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 2000;
    }
    .loading-screen .loading-text {
      margin-left: 15px;
      font-size: 1.2rem;
      font-weight: bold;
      color: #198754;
    }
    /* Off-canvas Sidebar */
    .offcanvas.offcanvas-start { width: 250px; z-index: 1040; }
    /* Main Content */
    .content        { padding-top: 80px; padding-left: 20px; padding-right: 20px; max-width: 100%; min-height: 100vh; }
    @media (min-width: 992px){ .content{ margin-left: 250px; } }
    @media (max-width: 991.98px){ .content{ margin-left: 0; } }
    .card-icons     { display:flex; gap:8px; justify-content:flex-end; }
    .card-icons i   { cursor:pointer; font-size:1rem; }
    .modal-content  { border-radius:10px; padding:20px; box-shadow:0 4px 8px rgba(0,0,0,0.2); }
    .modal-footer   { border-top:none; }
    .modal-header   { border-bottom:none; }
    .modal-title    { color:#198754; font-weight:bold; }
    .is-invalid     { border-color:#dc3545!important; }
    html,body       { height:100%; }

    /* -------------------------------------------------
       SUMMARY CARDS
       ------------------------------------------------- */
    .summary-cards .card{
      background-color:#28a745;
      color:#fff;
      border-radius:8px;
      text-align:center;
      padding:15px;
      margin-bottom:15px;
      font-weight:bold;
      display:flex;
      flex-direction:column;
      justify-content:center;
      position:relative;
    }
    .summary-cards .card h4{ font-size:1rem; margin:0 0 10px; font-weight:normal; }
    .summary-cards .card span{ font-size:2rem; font-weight:bold; }
    .card .card-loading{ display:flex; justify-content:center; align-items:center; margin:10px 0; }

    /* -------------------------------------------------
       STATISTICS SECTION
       ------------------------------------------------- */
    .statistics{ margin-top:30px; }
    .statistics h2{ font-size:1.25rem; font-weight:bold; }

    /* Chart Wrapper */
    .chart{
      background:#fff;
      border-radius:8px;
      box-shadow:0 2px 4px rgba(0,0,0,0.1);
      height:350px;
      overflow:hidden;
      display:flex;
      flex-direction:column;
    }
    .chart-content{ position:relative; flex:1; margin:10px 20px; }
    .chart-content canvas{ display:block; width:100%; height:100%; }
    .loading-overlay{
      position:absolute; top:0; left:0; right:0; bottom:0;
      display:flex; justify-content:center; align-items:center;
      background:rgba(255,255,255,0.8);
    }
    .chart-header-buttons{ display:flex; gap:4px; }
    .chart-header-buttons .btn.active{ background:#28a745; color:#fff; }

    /* Truck Usage Reports */
    .reports-card{
      height:350px;
      display:flex;
      flex-direction:column;
      background:#fff;
      border-radius:8px;
      box-shadow:0 2px 4px rgba(0,0,0,0.1);
      overflow:hidden;
    }
    /* ---------- slimmer horizontal scrollbar on the usage-reports table ---------- */
.table-responsive::-webkit-scrollbar {
  height: 8px;              /* default was 8px — shrink to 6px */
}

.table-responsive::-webkit-scrollbar-thumb {
  background: #32CD32;      /* keep the same green thumb */
  border-radius: 4px;
}



    .reports-card .card-header{
      display:flex; justify-content:space-between; align-items:center;
      padding:10px 20px; background:#fff; border-bottom:1px solid #ddd;
    }
    .reports-card .card-body{ flex:1; padding:10px 20px 20px; overflow:hidden; }
    .reports-card .table-responsive{ max-height:100%; overflow-y:auto; }
    .reports-card .table-responsive::-webkit-scrollbar{ width:8px; }
    .reports-card .table-responsive::-webkit-scrollbar-track{ background:#f1f1f1; border-radius:4px; }
    .reports-card .table-responsive::-webkit-scrollbar-thumb{ background:#32CD32; border-radius:4px; }
    .reports-card .table-responsive::-webkit-scrollbar-thumb:hover{ background:#32CD32; }

    @media (min-width: 1920px){ .content{ max-width:1900px; margin:0 auto; } }
  </style>
</head>
<body>
  <!-- -------------------------------------------------
       SIDEBAR (unchanged)
       ------------------------------------------------- -->
  <div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" data-bs-backdrop="false">
    <div class="offcanvas-header d-lg-none">
      <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Menu</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
      <img src="img/hakot-new.png" alt="HAKOT Logo" style="height:120px; width:120px; margin-bottom: 10px;">
      <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
      <a href="tracker.php"><i class="fas fa-map-marker-alt"></i> Truck Tracker</a>
      <a href="schedule.php"><i class="fas fa-calendar-alt"></i> Truck Schedules</a>
      <a href="user_announcement.php"><i class="fa-solid fa-bullhorn"></i> User Announcement</a>
      <div class="bottom-links">
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
      </div>
    </div>
  </div>

  <!-- -------------------------------------------------
       TOPBAR (unchanged)
       ------------------------------------------------- -->
  <div class="topbar d-flex align-items-center px-3" style="position: fixed; top:0; left:0; right:0; height:60px; background:#fff; border-bottom:1px solid #ddd; z-index:1050;">
    <button class="btn btn-outline-success d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
      <span class="fas fa-bars"></span>
    </button>
    <div class="d-flex align-items-center ms-auto">
      <a class="dropdown-item" id="dropdownUsername"></a>
      <div>
        <img id="profileImg" src="img/default-profile.jpg" width="35" height="35" style="border-radius:50%; cursor:pointer;" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <div aria-labelledby="profileImg" id="profileDropdown"></div>
      </div>
    </div>
  </div>

  <!-- -------------------------------------------------
       MAIN CONTENT
       ------------------------------------------------- -->
  <div class="content">
    <div class="container-fluid">
      <!-- ---------- SUMMARY CARDS ---------- -->
      <div class="row row-cols-5 g-3 summary-cards">
  <div class="col">
    <div class="card">
      <h4>NO. OF OPERATORS</h4>
      
      <div class="card-loading" id="cardDriverLoading">
        <div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading…</span></div>
      </div>
      <span id="driverSpan" style="display:none;">00</span>
    </div>
  </div>
  <div class="col">
  <div class="card">
    <h4>DISPATCHED OPTR</h4>
    <div class="card-loading" id="cardDispatchedLoading">
      <div class="spinner-border text-light" role="status">
        <span class="visually-hidden">Loading…</span>
      </div>
    </div>
    <span id="dispatchedSpan" style="display:none;">0</span>
  </div>
</div>
  <div class="col">
    <div class="card">
      <h4>NO. OF TRUCKS</h4>
      <div class="card-loading" id="cardTrucksLoading">
        <div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading…</span></div>
      </div>
      <span id="numTrucksSpan" style="display:none;">00</span>

    </div>
  </div>
  <div class="col">
    <div class="card">
      <h4>AREAS COVERED</h4>
      <div class="card-loading" id="cardAreasLoading">
        <div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading…</span></div>
      </div>
      <span id="areasCoveredSpan" style="display:none;">00</span>
      
    </div>
  </div>
   <!-- TRASH COLLECTED TODAY -->
   <div class="col">
      <div class="card">
        <h4>COLLECTED TODAY</h4>
       <div class="card-loading" id="cardTrashLoading">
          <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Loading…</span>
          </div>
        </div>
       <span id="trashCollectedSpan" style="display:none;">0KG</span>
      </div>
    </div>
  </div><!-- /summary cards -->

      <!-- ---------- STATISTICS SECTION ---------- -->
      <div class="row statistics">
        <!-- LEFT: TRUCK USAGE REPORTS -->
        <div class="col-md-6 mb-4">
          <div class="card reports-card">
            <div class="card-header justify-content-between align-items-center">
              <h2 class="mb-0" style="font-size:1.25rem;">Truck Usage Reports</h2>
              <div class="d-flex gap-2">
                <select id="reportTimeframeSelector" class="form-control" style="max-width:120px;">
                  <option value="daily" selected>Daily</option>
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
                <input type="date"   id="reportDateSelector"  class="form-control" style="max-width:200px;">
                <input type="month"  id="reportMonthSelector" class="form-control" style="max-width:200px; display:none;">
                <input type="number" id="reportYearSelector"  class="form-control" style="max-width:200px; display:none;" placeholder="Year">
                <button id="printTruckUsageReport" class="btn btn-outline-secondary btn-sm">Print</button>
              </div>
            </div>
            <div class="card-body">
              <div id="truckUsageLoading" class="loading-overlay">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>
              </div>
              <div id="truckUsageContent" class="table-responsive" style="display:none;">
                <table class="table table-striped table-sm mb-0">
                  <thead id="truckUsageTableHead">
                    <tr>
                      <th>Truck Name</th>
                      <th>Fuel Loaded</th>
                      <th>Odometer</th>
                      <th>Distanced Travelled</th>
                      <th>Fuel Efficiency</th>
                      <th>Time Travelled(mins)</th>
                      <th>Garbage Collected</th>
                    </tr>
                  </thead>
                  <tbody id="truckUsageTableBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: COLLECTION STATISTICS (UPDATED) -->
        <div class="col-md-6 mb-4">
          <div class="chart">
          <div
  class="d-flex flex-column flex-sm-row justify-content-between
         align-items-start align-items-sm-center gap-2 px-3 pt-3"
  style="row-gap:.75rem;">

  <!-- title grows / wraps nicely -->
  <h2 id="truckStatsChartTitle" class="mb-0 flex-grow-1 text-sm-nowrap">
    Collection Statistics (Monthly)
  </h2>

  <!-- controls: dropdown  +  picker   (stay together as a unit)  -->
  <div class="d-flex flex-row align-items-center gap-2 flex-wrap">

    <!-- Monthly / Yearly -->
    <select id="collectionTimeframeSelector"
            class="form-control form-control-sm w-auto">
      <option value="monthly" selected>Monthly</option>
      <option value="yearly">Yearly</option>
    </select>

    <!-- Month picker -->
    <input type="month"
           id="collectionMonthPicker"
           class="form-control form-control-sm w-auto">

    <!-- Year picker -->
    <input type="number"
           id="collectionYearPicker"
           class="form-control form-control-sm w-auto"
           min="2020" max="2100" step="1"
           style="display:none;">
  </div>
</div>

  

            <div class="chart-content">
              <canvas id="truckStatsChart"></canvas>
              <div id="truckStatsChartLoading" class="loading-overlay">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>
              </div>
              <div id="truckStatsNoData" class="loading-overlay" style="display:none;">
                <span class="loading-text">No data found</span>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /statistics row -->
      <!-- ───────────── MAP (live truck location) ───────────── -->
<div class="row mb-4">
  <div class="col-12">
    <div class="chart mb-4" style="height:480px">   <!-- uses your “chart” skin -->
      <div class="px-3 pt-3 d-flex justify-content-between align-items-center">
          <h2 class="mb-0">Truck Locations (Live)</h2>
          <button id="resetMapBtn" class="btn btn-outline-success btn-sm">
              <i class="fas fa-sync-alt"></i> Reset View
          </button>
      </div>
      <div id="dashMap" style="flex:1;"></div>  <!-- the actual map -->
    </div>
  </div>
</div>

    </div><!-- /.container-fluid -->
  </div><!-- /.content -->

  <!-- ---------- ERROR / SUCCESS MODAL (unchanged) ---------- -->
  <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-danger" id="errorModalLabel">Error</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body"><p id="errorMessage"></p></div>
      </div>
    </div>
  </div>

  <!-- ---------- SCRIPTS ---------- -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@latest/ol.css"/>
  <script src="https://cdn.jsdelivr.net/npm/ol@latest/dist/ol.js"></script>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.css"/>
  <script src="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.min.js" defer></script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script type="module">
/* =============== LIVE-MAP for dashboard =============== */

/* fetch trucks endpoint */
async function fetchTrucks() {
  const r = await fetch('../backend/fetch_trucks.php');
  const j = await r.json();
  return (j.status === 'success' && j.data) ? j.data : {};
}

/* OpenLayers map setup */
const map = new ol.Map({
  target: 'dashMap',
  view: new ol.View({
    center: ol.proj.fromLonLat([125.814239, 7.443433]),
    zoom: 12
  }),
  layers: [ new ol.layer.Tile({ source: new ol.source.OSM() }) ]
});
map.addControl(new ol.control.FullScreen());

/* Source for clustered current-location markers */
const markerSource = new ol.source.Vector();

/**
 * Returns a style based on truckType
 */
function truckIconDefaultStyle(feature) {
  const type = (feature.get('truckType')||'').trim().toLowerCase();
  let file = 'truck.png';
  if (type==='garbage truck')    file='garbage-truck.png';
  else if (type==='sewage truck') file='sewage-truck.png';
  return new ol.style.Style({
    image: new ol.style.Icon({
      anchor: [0.5,1],
      src: `img/${file}`,
      scale: 0.10
    })
  });
}

/**
 * Returns [icon, label] for a feature
 */
function truckIconWithLabel(feature) {
  const icon = truckIconDefaultStyle(feature);
  const label = new ol.style.Style({
    text: new ol.style.Text({
      text: feature.get('vehicleName')||'',
      font: '12px Poppins, sans-serif',
      offsetY: -25,
      fill:  new ol.style.Fill({ color:'#000' }),
      stroke:new ol.style.Stroke({ color:'#fff', width:3 })
    })
  });
  return [icon, label];
}

/* Cluster layer: singletons use icon+label */
const clusterLayer = new ol.layer.Vector({
  source: new ol.source.Cluster({ distance:40, source:markerSource }),
  style: feature => {
    const members = feature.get('features'), size = members.length;
    if (size===1) {
      return truckIconWithLabel(members[0]);
    }
    return new ol.style.Style({
      image: new ol.style.Circle({
        radius:15,
        fill:  new ol.style.Fill({color:'#28a745'}),
        stroke:new ol.style.Stroke({color:'#fff',width:2})
      }),
      text: new ol.style.Text({
        text: size.toString(),
        fill: new ol.style.Fill({color:'#fff'})
      })
    });
  }
});
map.addLayer(clusterLayer);

/* Refresh map: tag each feature with truckType and style it */
async function refreshMap() {
  const data = await fetchTrucks();
  markerSource.clear();

  Object.values(data).forEach(t => {
    if (!t.truckCurrentLocation) return;
    const { latitude, longitude } = t.truckCurrentLocation;
    if (!latitude||!longitude) return;

    const feat = new ol.Feature({
      geometry: new ol.geom.Point(
        ol.proj.fromLonLat([parseFloat(longitude), parseFloat(latitude)])
      ),
      vehicleName: t.vehicleName||'Unknown',
      plateNumber: t.plateNumber||''
    });
    // tag & style
    feat.set('truckType', t.truckType);
    feat.setStyle(truckIconDefaultStyle(feat));

    markerSource.addFeature(feat);
  });

  if (!refreshMap._fitted && markerSource.getFeatures().length) {
    map.getView().fit(markerSource.getExtent(),{padding:[50,50,50,50]});
    refreshMap._fitted = true;
  }
}
refreshMap();
setInterval(refreshMap, 5000);

/* Reset view button */
document.getElementById('resetMapBtn')
  .addEventListener('click', () => {
    if (markerSource.getFeatures().length) {
      map.getView().fit(markerSource.getExtent(),{padding:[50,50,50,50],duration:400});
    } else {
      map.getView().animate({center:ol.proj.fromLonLat([125.814239,7.443433]),zoom:12});
    }
  });

/**
 * Spiderfy clusters, carrying over truckType and styling each leg
 */
function setupClusterSpiderfy() {
  const spiderSource = new ol.source.Vector();
  const spiderLayer  = new ol.layer.Vector({ source: spiderSource });
  map.addLayer(spiderLayer);

  function spiderfy(clusterFeature) {
    spiderSource.clear();
    const members = clusterFeature.get('features'), N = members.length;
    if (N<=1) return;

    const centrePx = map.getPixelFromCoordinate(
      clusterFeature.getGeometry().getCoordinates()
    );
    const radius = 40;
    members.forEach((m,i) => {
      const angle = 2*Math.PI/N * i;
      const px = [
        centrePx[0] + radius*Math.cos(angle),
        centrePx[1] + radius*Math.sin(angle)
      ];
      const coord = map.getCoordinateFromPixel(px);

      const f = new ol.Feature({
        geometry   : new ol.geom.Point(coord),
        vehicleName: m.get('vehicleName'),
        plateNumber: m.get('plateNumber'),
        truckType  : m.get('truckType')
      });
      f.setStyle([
        truckIconDefaultStyle(f),
        new ol.style.Style({
          text: new ol.style.Text({
            text: f.get('vehicleName'),
            font: '12px Poppins, sans-serif',
            offsetY: -25,
            fill:  new ol.style.Fill({color:'#000'}),
            stroke:new ol.style.Stroke({color:'#fff',width:3})
          })
        })
      ]);
      spiderSource.addFeature(f);
    });
  }

  map.on('click', e => {
    const wasSpider = map.forEachFeatureAtPixel(
      e.pixel, (f,l)=> l===spiderLayer?f:null
    );
    if (wasSpider) { spiderSource.clear(); return; }

    const cluster = map.forEachFeatureAtPixel(
      e.pixel, (f,l)=> l===clusterLayer?f:null
    );
    if (cluster && cluster.get('features').length>1) {
      spiderfy(cluster);
    } else {
      spiderSource.clear();
    }
  });
}
setupClusterSpiderfy();

</script>


<script>
document.addEventListener('DOMContentLoaded', () => {

  /* ---------- TODAY’S COLLECTED WEIGHT ---------- */
async function loadTrashCollectedToday() {
  const today   = new Date();                   // e.g. 2025-04-29
  const monthId = today.toISOString().slice(0, 7);   // "YYYY-MM"
  const dayNum  = today.getDate();                     // 1-31

  const span    = document.getElementById('trashCollectedSpan');
  const loader  = document.getElementById('cardTrashLoading');

  try {
    const res  = await fetch(`../backend/fetch_collection_stats.php?month=${monthId}`);
    const json = await res.json();

    if (json.status === 'success' && Array.isArray(json.data)) {
      const todayRow = json.data.find(r => Number(r.day) === dayNum);
      const kg       = todayRow ? (todayRow.totalKg ?? todayRow.totalDisposedTrashWeight) : 0;
      span.textContent = `${kg}kg`;
    } else {
      span.textContent = '0kg';
    }
  } catch (e) {
    console.error('loadTrashCollectedToday:', e);
    span.textContent = '0kg';
  } finally {
    loader.style.display = 'none';
    span.style.display   = 'block';
  }
}


  /* ---------- COLLECTION STATISTICS CHART + PICKERS ---------- */
const ctx   = document.getElementById('truckStatsChart').getContext('2d');
const chart = new Chart(ctx, {
  type: 'line',
  data: { labels: [], datasets: [{
    label:'Disposed Trash (kg)', data:[], borderWidth:2, tension:.35, fill:true,
    borderColor:'#28a745', backgroundColor:'rgba(40,167,69,.15)'
  }]},
  options:{ responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:true } } }
});

const monthInp = document.getElementById('collectionMonthPicker');
const yearInp  = document.getElementById('collectionYearPicker');
const tfBtns   = document.getElementById('collectionTimeframeBtnGroup');

/* — core loader — */
async function loadCollectionStats(value, tf){
  const loadEl=document.getElementById('truckStatsChartLoading');
  const noEl  =document.getElementById('truckStatsNoData');
  loadEl.style.display='flex'; noEl.style.display='none';

  try{
    const url=tf==='monthly'
      ? `../backend/fetch_collection_stats.php?month=${value}`    // YYYY-MM
      : `../backend/fetch_collection_stats.php?year=${value}`;    // YYYY
    const j = await (await fetch(url)).json();

    const labs=[], pts=[];
    if(j.status==='success' && Array.isArray(j.data)){
      j.data.forEach(it=>{
        labs.push(tf==='monthly'? it.day : it.monthName);
        pts .push(it.totalKg ?? it.totalDisposedTrashWeight);
      });
    }
    chart.data.labels=labs;
    chart.data.datasets[0].data=pts;
    chart.data.datasets[0].label =
      tf==='monthly' ? 'Disposed Trash (kg)'
                     : 'Disposed Trash (kg)';

    noEl.style.display = labs.length ? 'none' : 'flex';
  }catch(e){
    console.error(e); chart.data.labels=[]; chart.data.datasets[0].data=[];
    noEl.style.display='flex';
  }finally{
    loadEl.style.display='none';
    document.getElementById('truckStatsChartTitle').textContent =
      `Collection Statistics (${tf.charAt(0).toUpperCase()+tf.slice(1)})`;
    chart.update();
  }
}

/* — timeframe dropdown (collectionTimeframeSelector) — */
const csTfSel = document.getElementById('collectionTimeframeSelector');
csTfSel.addEventListener('change', () => {
  const tf = csTfSel.value;                        // 'monthly' | 'yearly'

  monthInp.style.display = tf === 'monthly' ? 'block' : 'none';
  yearInp .style.display = tf === 'yearly'  ? 'block' : 'none';

  if (tf === 'monthly') {
    const m = monthInp.value || new Date().toISOString().slice(0, 7);
    monthInp.value = m;
    loadCollectionStats(m, 'monthly');
  } else {
    const y = yearInp.value || new Date().getFullYear();
    yearInp.value = y;
    loadCollectionStats(y, 'yearly');
  }
});

/* — pickers change handlers — */
monthInp.addEventListener('change',()=> loadCollectionStats(monthInp.value,'monthly'));
yearInp .addEventListener('change',()=> loadCollectionStats(yearInp.value ,'yearly'));

/* — initial values & load — */
monthInp.value = new Date().toISOString().slice(0,7);
yearInp .value = new Date().getFullYear();
loadCollectionStats(monthInp.value,'monthly');



  /* ---------- HELPERS ---------- */
  function computeFuelFromDistance(km, kmpl){ return (parseFloat(km)||0)/(parseFloat(kmpl)||1); }

  function addUsageRow(row, tbody) {
  /* ----- distance travelled ----- */
  const dist = row.distanceTraveled ?? row.totalKilometersTraveled ?? 0;

  /* ----- fuel loaded (today+yesterday where available) ----- */
  const fuel = (row.fuelLoadedToday     ?? row.totalFuelLoaded ?? 0) +
               (row.fuelLoadedYesterday ?? 0);

  /* ----- fuel-efficiency display ----- */
  let fe = 'N/A';
  if (row.fuelEfficiency !== undefined) {
    fe = row.fuelEfficiency.toFixed(2) + ' km/L';
  } else {
    /* fallback for monthly/yearly: dist ÷ kmPerLiter reference */
    const est = computeFuelFromDistance(dist, row.kmPerLiter || 1).toFixed(2);
    fe = est + ' L';
  }

  const travelMins = row.totalTimeTravel
    ? (row.totalTimeTravel / 60).toFixed(1) + ' mins'
    : '0 mins';

  const odo = row.odoToday ?? row.totalOdometerReading ?? 0;

  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>${row.truckName}</td>
    <td>${fuel}L</td>
    <td>${odo}km</td>
    <td>${dist}km</td>
    <td>${fe}</td>
    <td>${travelMins}</td>
    <td>${row.totalDisposedTrashWeight || 0}kg</td>`;
  tbody.appendChild(tr);
}


  /* ---------- TRUCK USAGE REPORTS ---------- */
  const tbody=document.getElementById('truckUsageTableBody');
  async function loadTruckUsageReports(url){
    document.getElementById('truckUsageLoading').style.display='flex';
    document.getElementById('truckUsageContent').style.display='none';
    try{
      const res=await fetch(url);
      const json=await res.json();
      tbody.innerHTML='';
      if(json.status==='success'&&json.data.truckusagedata){
        const rows=Object.values(json.data.truckusagedata);
        if(rows.length){
          rows.forEach(r=>addUsageRow(r,tbody));
        }else{
          tbody.innerHTML='<tr><td colspan="7" class="text-center">No reports found</td></tr>';
        }
      }else{
        tbody.innerHTML='<tr><td colspan="7" class="text-center">No reports found</td></tr>';
      }
    }catch{
      tbody.innerHTML='<tr><td colspan="7" class="text-center text-danger">Error loading reports</td></tr>';
    }finally{
      document.getElementById('truckUsageLoading').style.display='none';
      document.getElementById('truckUsageContent').style.display='block';
    }
  }

  const tfSel  = document.getElementById('reportTimeframeSelector');
  const dateIn = document.getElementById('reportDateSelector');
  const monIn  = document.getElementById('reportMonthSelector');
  const yrIn   = document.getElementById('reportYearSelector');

  function refreshReports(){
    const tf=tfSel.value;
    const url=tf==='daily'
      ? `../backend/fetch_truck_usage_reports.php?date=${dateIn.value}`
      : tf==='monthly'
        ? `../backend/fetch_truck_usage_reports.php?month=${monIn.value}`
        : `../backend/fetch_truck_usage_reports.php?year=${yrIn.value}`;
    loadTruckUsageReports(url);
  }

  tfSel.addEventListener('change',()=>{
    const tf=tfSel.value;
    dateIn.style.display = tf==='daily'   ? 'block':'none';
    monIn.style.display  = tf==='monthly' ? 'block':'none';
    yrIn.style.display   = tf==='yearly'  ? 'block':'none';
    if(tf==='daily')   dateIn.value=new Date().toISOString().slice(0,10);
    if(tf==='monthly') monIn.value =new Date().toISOString().slice(0,7);
    if(tf==='yearly')  yrIn.value  =new Date().getFullYear();
    refreshReports();
  });
  dateIn.addEventListener('change',refreshReports);
  monIn.addEventListener('change',refreshReports);
  yrIn.addEventListener('change',refreshReports);

  dateIn.value=new Date().toISOString().slice(0,10);
  refreshReports();


  async function loadDispatchedCount(){
    const span   = document.getElementById('dispatchedSpan');
    const loader = document.getElementById('cardDispatchedLoading');
    try {
      const res = await fetch('../backend/fetch_trucks.php');
      const j   = await res.json();
      if (j.status==='success' && j.data){
        // sum up all dispatchedOperator values
        const count = Object.values(j.data)
                            .reduce((sum,t)=> sum + (t.dispatchedOperator||0), 0);
        span.textContent = count;
      } else {
        span.textContent = '0';
      }
    } catch (e) {
      console.error('loadDispatchedCount', e);
      span.textContent = '0';
    } finally {
      loader.style.display = 'none';
      span.style.display   = 'block';
    }
  }

  /* ---------- COUNTERS, USER DATA, CLOCK ---------- */
  async function loadDriversCount(){
    try{
      const res=await fetch('../backend/fetch_drivers.php');
      const j=await res.json();
      const c=j.status==='success'&&j.data?(Array.isArray(j.data)?j.data.length:Object.keys(j.data).length):0;
      document.getElementById('driverSpan').textContent=c;
    }catch{ document.getElementById('driverSpan').textContent='0'; }
    document.getElementById('cardDriverLoading').style.display='none';
    document.getElementById('driverSpan').style.display='block';
  }

  async function loadTrucksCount(){
    try{
      const res=await fetch('../backend/fetch_trucks.php');
      const j=await res.json();
      if(j.status==='success'){
        const tk=Object.keys(j.data||{});
        const set=new Set();
        tk.forEach(k=>{
          const sch=j.data[k].schedules;
          if(sch&&sch.days){
            Object.values(sch.days).forEach(d=>{
              if(d.places) d.places.forEach(p=>set.add(p.name));
            });
          }
        });
        document.getElementById('numTrucksSpan').textContent=tk.length;
        document.getElementById('areasCoveredSpan').textContent=set.size;
      }else{
        document.getElementById('numTrucksSpan').textContent='0';
        document.getElementById('areasCoveredSpan').textContent='0';
      }
    }catch{
      document.getElementById('numTrucksSpan').textContent='0';
      document.getElementById('areasCoveredSpan').textContent='0';
    }
    document.getElementById('cardTrucksLoading').style.display='none';
    document.getElementById('numTrucksSpan').style.display='block';
    document.getElementById('cardAreasLoading').style.display='none';
    document.getElementById('areasCoveredSpan').style.display='block';
  }

  async function fetchUserData(){
    try{
      const res=await fetch('../backend/fetch_users.php');
      const j=await res.json();
      if(j.status==='success'){
        document.getElementById('dropdownUsername').textContent=j.name||'Unknown';
        if(j.profile_image) document.getElementById('profileImg').src=j.profile_image;
      }
    }catch{}
  }
  fetchUserData();
  loadDriversCount();
  loadDispatchedCount(); 
  loadTrucksCount();
 
  loadTrashCollectedToday();  


  // function updateTimeDate(){
  //   const n=new Date();
  //   document.getElementById('timeDateSpan').innerHTML=
  //     `<div style="font-size:75%">${n.toLocaleTimeString()}<br>${n.toLocaleDateString()}</div>`;
  // }
  // updateTimeDate();
  
  // setInterval(updateTimeDate,1000);

  /* ---------- PRINT ---------- */
  document.getElementById('printTruckUsageReport').addEventListener('click',()=>{
    const reportEl=document.getElementById('truckUsageContent');
    if(!reportEl) return alert('No report to print');
    const tf=tfSel.value;
    const period=tf==='daily'?dateIn.value:tf==='monthly'?monIn.value:yrIn.value;
    const win=window.open('','PRINT','height=600,width=800');
    if(!win) return alert('Popup blocked.');
    win.document.write(`
      <html><head><title>Truck Usage Report</title>
      <style>
        body{font-family:Arial,sans-serif;margin:20px;}
        h2{text-align:center;}
        table{width:100%;border-collapse:collapse;margin-top:20px;}
        table,th,td{border:1px solid #ccc;}
        th,td{padding:8px;text-align:left;}th{background:#f8f8f8;}
      </style></head><body>
      <h2>Truck Usage Report</h2>
      <p style="text-align:center;">Period: ${period}</p>
      ${reportEl.innerHTML}
      </body></html>`);
    win.document.close();
    win.onload=()=>{win.focus();win.print();win.close();};
  });
  // ──────────────── REAL‑TIME REFRESH ─────────────────
  // every 30 seconds, reload all of our cards & reports
  setInterval(() => {
    loadDispatchedCount();       // DISPATCHED OPTR
    loadTrashCollectedToday();   // COLLECTED TODAY
    refreshReports();            // Truck Usage Reports table

    // Collection Statistics chart:
    const tf = csTfSel.value;
    if (tf === 'monthly') {
      loadCollectionStats(monthInp.value, 'monthly');
    } else {
      loadCollectionStats(yearInp.value,  'yearly');
    }
  }, 30_000);

});

</script>

</body>
</html>
