<?php
session_start();
// If not logged in, redirect
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php");
    exit;
}

$apiConfig = require '../backend/ors_api.php';
$orsApiKey = $apiConfig['ors_api_key'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Hakot | Routes Optimization</title>
  <link rel="icon" type="image/x-icon" href="img/hakot-icon.png">
  <!-- External CSS and Fonts -->
  <link rel="stylesheet" type="text/css" href="navs.css"/>
  <!-- Bootstrap CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
  />

  <!-- OpenLayers CSS -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/ol@v7.3.0/ol.css"
  />

  <!-- Custom CSS -->
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f9f9f9;
      margin: 0;
    }
    /* Loading Screen */
    #loadingScreen {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background-color: rgba(255,255,255,0.8);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      transition: opacity 0.3s ease-in-out;
    }
    #loadingScreen.show {
      display: flex;
    }
    /* Center spinner and text vertically */
    #loadingScreen .text-center {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.75rem;
    }

    /* Offcanvas Sidebar */
    .offcanvas.offcanvas-start {
      width: 250px;
      z-index: 1040;
    }
    .content {
      padding-top: 60px;
      padding-left: 20px;
      padding-right: 20px;
      max-width: 100%;
    }
    @media (min-width: 992px) {
      .content { margin-left: 250px; }
    }
    @media (max-width: 991.98px) {
      .content { margin-left: 0; }
    }
    .schedule-container {
      margin-top: 20px;
    }
    .schedule-table {
      width: 100%;
      border-collapse: collapse;
    }
    .schedule-table th, .schedule-table td {
      padding: 10px;
      border: 1px solid #d4f7d4;
      text-align: left;
      vertical-align: top;
    }
    .schedule-table th {
      background-color: #d4f7d4;
    }
    .schedule-table tbody tr:nth-child(even) {
      background-color: #f2fff2;
    }
    .schedule-table tbody tr:hover {
      background-color: #e6ffe6;
    }

    html, body {
      height: 100%;
      overflow-y: auto;
    }
    .content {
      min-height: 100vh; /* Ensure content fills viewport height */
    }

    /* Map & search results */
    #map {
      width: 100%;
      height: 500px;
    }
    #searchResults {
      max-height: 200px;
      overflow-y: auto;
      margin-top: 10px;
    }
    #searchResults li {
      cursor: pointer;
    }

    /* Button Alignment */
    .button-group {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-bottom: 15px;
    }
    .button-group-left {
      display: flex;
      justify-content: flex-start;
      gap: 10px;
      margin-bottom: 15px;
    }

    /* Additional Styles for Remove Buttons in Input Groups */
    .input-group .btn {
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
    }

    /* Popup styling */
    .ol-popup {
      position: absolute;
      background-color: white;
      padding: 15px;
      border: 1px solid #ccc;
      bottom: 12px;
      left: -50px;
      min-width: 200px;
      z-index: 1000;
    }

    .ol-popup:after, .ol-popup:before {
      top: 100%;
      border: solid transparent;
      content: " ";
      height: 0;
      width: 0;
      position: absolute;
      pointer-events: none;
    }

    .ol-popup:after {
      border-top-color: white;
      border-width: 10px;
      left: 48px;
      margin-left: -10px;
    }

    .ol-popup-closer {
      text-decoration: none;
      position: absolute;
      top: 2px;
      right: 8px;
    }

    /* Popup content */
    #popup-content {
      font-size: 14px;
    }
    /* Modal Styles */
    .modal-content {
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .modal-header, .modal-footer {
      border: none;
    }
    .modal-title {
      color: #198754;
      font-weight: bold;
    }

  </style>
</head>
<body>

  <!-- Loading Screen -->
  <div id="loadingScreen">
    <div class="text-center">
      <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <div>Loading...</div>
    </div>
  </div>

  <!-- Offcanvas Sidebar -->
  <div
    class="offcanvas offcanvas-start sidebar"
    tabindex="-1"
    id="sidebarOffcanvas"
    aria-labelledby="sidebarOffcanvasLabel"
    data-bs-backdrop="false"
  >
    <div class="offcanvas-header d-lg-none">
      <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Menu</h5>
      <button
        type="button"
        class="btn-close text-reset"
        data-bs-dismiss="offcanvas"
        aria-label="Close"
      ></button>
    </div>
    <div class="offcanvas-body p-0">
      <img src="img/hakot-logo.png" alt="HAKOT Logo" style="height:120px; width:120px;">
      <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
      <a href="truckers.php"><i class="fas fa-users"></i> Truck Drivers</a>
      <a href="trucks.php"><i class="fas fa-truck"></i> Trucks</a>
      <a href="tracker.php"><i class="fas fa-map-marker-alt"></i> Truck Tracker</a>
      <a href="schedule.php"><i class="fas fa-calendar-alt"></i> Truck Schedules</a>
      <a href="user_announcement.php"><i class="fa-solid fa-bullhorn"></i> User Announcement</a>
      <!-- <a href="routes.php"><i class="fas fa-route"></i> Routes Optimization</a> -->
      <div class="bottom-links">
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
      </div>
    </div>
  </div>

  <!-- Topbar -->
  <div 
    class="topbar d-flex align-items-center px-3"
    style="position: fixed; top: 0; left: 0; right: 0;
           height: 60px; background-color: #fff;
           border-bottom: 1px solid #ddd; z-index: 1050;"
  >
    <!-- Hamburger (only on small screens) -->
    <button
      class="btn btn-outline-success d-lg-none"
      type="button"
      data-bs-toggle="offcanvas"
      data-bs-target="#sidebarOffcanvas"
      aria-controls="sidebarOffcanvas"
      aria-label="Toggle sidebar"
    >
      <span class="fas fa-bars"></span>
    </button>

    <!-- Avatar on the right -->
    <div class="d-flex align-items-center ms-auto">

      <div class="dropdown">
        <img
          id="profileImg"
          src="img/default-profile.jpg"
          width="35"
          height="35"
          style="border-radius:50%; cursor:pointer;"
          title=""
          class="dropdown-toggle"
          data-bs-toggle="dropdown"
          aria-expanded="false"
        >
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileImg" id="profileDropdown">
          <li><a class="dropdown-item" id="dropdownUsername">Loading...</a></li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="content">
    <div class="schedule-container">
      <!-- Removed <h1> heading, but kept dropdown -->
      <div class="mb-3">
        <select id="truckSelect" class="form-select">
          <option value="">Loading...</option>
        </select>
      </div>

      <!-- Button Groups -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Undo Button on the Left -->
        <div class="button-group-left">
          <button id="undoButton" class="btn btn-warning" disabled>
            <i class="fas fa-undo"></i> Undo Optimization
          </button>
        </div>
        <!-- Generate and Save Buttons on the Right -->
        <div class="button-group">
          <button id="generateButton" class="btn btn-primary">
            <i class="fas fa-sync-alt"></i> Generate Route Optimization
          </button>
          <button id="saveButton" class="btn btn-success">
            <i class="fas fa-save"></i> Save
          </button>
        </div>
      </div>

      <table class="schedule-table">
        <thead>
          <tr>
            <th>Day</th>
            <th>Tasks</th>
          </tr>
        </thead>
        <tbody id="scheduleTableBody">
          <!-- Dynamically populated rows go here -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- Generate Route Optimization Modal: to set Start/End -->
  <div
    class="modal fade"
    id="generateRouteModal"
    tabindex="-1"
    aria-labelledby="generateRouteModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="generateRouteModalLabel">Generate Route Optimization</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <!-- Nominatim-based search input -->
          <div class="mb-3">
            <label for="placeSearch">Search Location:</label>
            <input
              type="text"
              id="placeSearch"
              class="form-control"
              placeholder="Type an address..."
            />
            <!-- A list for Nominatim results -->
            <ul id="searchResults" class="list-group"></ul>
          </div>
          <div id="map"></div>
          <!-- Input Group for Starting Point -->
          <div class="mt-3">
            <label for="startingPoint">Starting Point (S):</label>
            <div class="input-group">
              <input
                type="text"
                id="startingPoint"
                class="form-control"
                readonly
              />
              <button
                id="removeStartButton"
                class="btn btn-danger"
                type="button"
              >
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>
          <!-- Input Group for Dumping Site -->
          <div class="mt-3">
            <label for="dumpingSite">Dumping Site (D):</label>
            <div class="input-group">
              <input
                type="text"
                id="dumpingSite"
                class="form-control"
                readonly
              />
              <button
                id="removeDumpButton"
                class="btn btn-danger"
                type="button"
              >
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <!-- Icons added to Cancel and Confirm buttons -->
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal"
          >
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="button" class="btn btn-success" id="confirmGenerate">
            <i class="fas fa-check"></i> Confirm
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Popup Overlay -->
  <div id="popup" class="ol-popup">
    <a href="#" id="popup-closer" class="ol-popup-closer"></a>
    <div id="popup-content"></div>
  </div>

  <!-- Error/Success Modal -->
  <div
    class="modal fade"
    id="errorModal"
    tabindex="-1"
    aria-labelledby="errorModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <!-- Dynamically switched between "Error" / "Success" in JS -->
          <h5 class="modal-title" id="errorModalLabel">Error</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <p id="errorMessage"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
  ></script>

  <!-- OpenLayers JS -->
  <script
    src="https://cdn.jsdelivr.net/npm/ol@v7.3.0/dist/ol.js"
  ></script>

  <!-- Main JS Logic -->
  <script>
/* =====================================
   1) Global Variables & DOM Elements
   ===================================== */
   let map;
let searchLayer;
let startFeature = null;
let dumpFeature = null;

let loadingScreen,
    truckSelect,
    scheduleTableBody,
    errorModalElement,
    errorModal,
    errorModalLabel,
    errorMessage,
    generateButton,
    saveButton,
    generateRouteModal,
    confirmGenerateButton,
    startingPointInput,
    dumpingSiteInput,
    placeSearchInput,
    searchResults,
    undoButton,
    removeStartButton,
    removeDumpButton;

// We'll store the entire schedule in one object:
// schedule.days[dayName].places = [...]
let schedule = {};

// Store previous schedule for undo functionality
let previousSchedule = null;

// Truck data from the backend
let trucks = {};
let selectedTruckKey = "";

// Keep track of an abort controller for fetch requests
let currentFetchController = null;

// ORS API Key from PHP
const orsApiKey = "<?php echo htmlspecialchars($orsApiKey, ENT_QUOTES, 'UTF-8'); ?>";

// Vector sources and layers
const vectorSource = new ol.source.Vector({});

const clusterSource = new ol.source.Cluster({
  distance: 40,
  source: vectorSource
});

const clusters = new ol.layer.Vector({
  source: clusterSource,
  style: function (feature) {
    const size = feature.get('features').length;
    let style;
    if (size > 1) {
      style = new ol.style.Style({
        image: new ol.style.Circle({
          radius: 15,
          stroke: new ol.style.Stroke({
            color: '#fff'
          }),
          fill: new ol.style.Fill({
            color: '#3399CC'
          })
        }),
        text: new ol.style.Text({
          text: size.toString(),
          fill: new ol.style.Fill({
            color: '#fff'
          })
        })
      });
    } else {
      const originalFeature = feature.get('features')[0];
      const iconSrc = originalFeature.get('icon') || 'https://maps.google.com/mapfiles/ms/icons/gray-dot.png'; // Default icon if 'icon' is undefined
      const name = originalFeature.get('name') || '';
      style = new ol.style.Style({
        image: new ol.style.Icon({
          src: iconSrc,
          anchor: [0.5, 1],
          scale: 1
        }),
        text: new ol.style.Text({
          text: name,
          offsetY: -25,
          scale: 1.2,
          fill: new ol.style.Fill({
            color: '#000'
          }),
          stroke: new ol.style.Stroke({
            color: '#fff',
            width: 2
          })
        })
      });
    }
    return style;
  }
});

/* =====================================
   2) DOMContentLoaded
   ===================================== */
document.addEventListener("DOMContentLoaded", () => {
  // References
  loadingScreen = document.getElementById("loadingScreen");
  truckSelect = document.getElementById("truckSelect");
  scheduleTableBody = document.getElementById("scheduleTableBody");
  errorModalElement = document.getElementById("errorModal");
  errorModal = new bootstrap.Modal(errorModalElement);
  errorModalLabel = document.getElementById("errorModalLabel");
  errorMessage = document.getElementById("errorMessage");

  generateButton = document.getElementById("generateButton");
  saveButton = document.getElementById("saveButton");
  undoButton = document.getElementById("undoButton");

  generateRouteModal = new bootstrap.Modal(document.getElementById("generateRouteModal"));
  confirmGenerateButton = document.getElementById("confirmGenerate");
  startingPointInput = document.getElementById("startingPoint");
  dumpingSiteInput = document.getElementById("dumpingSite");
  placeSearchInput = document.getElementById("placeSearch");
  searchResults = document.getElementById("searchResults");
  removeStartButton = document.getElementById("removeStartButton");
  removeDumpButton = document.getElementById("removeDumpButton");
  fetchUserData();
  initOpenLayersMap();
  setupNominatimSearch();

  // Truck selection -> fetch schedule
  truckSelect.addEventListener("change", () => {
    selectedTruckKey = truckSelect.value;
    if (selectedTruckKey) {
      fetchSchedule(selectedTruckKey);
    } else {
      clearScheduleTable();
    }
  });

  // Generate (open modal)
  generateButton.addEventListener("click", () => {
    if (!selectedTruckKey) {
      showModal("Warning", "Please select a truck before generating routes.", false);
      return;
    }
    generateButton.disabled = true; // Disable to prevent multiple clicks
    generateRouteModal.show();
  });

  // Confirm optimization
  confirmGenerateButton.addEventListener("click", () => {
    if (!startFeature || !dumpFeature) {
      showModal("Error", "Please set both a Starting Point and a Dumping Site.");
      return;
    }
    const startCoord = ol.proj.toLonLat(startFeature.getGeometry().getCoordinates());
    const dumpCoord = ol.proj.toLonLat(dumpFeature.getGeometry().getCoordinates());

    previousSchedule = JSON.parse(JSON.stringify(schedule));
    globalOptimizeAllPlaces(startCoord, dumpCoord);
  });

  // Save schedule
  saveButton.addEventListener("click", () => {
    if (!selectedTruckKey) {
      showModal("Warning", "Please select a truck before saving.", false);
      return;
    }
    if (!schedule.days) {
      showModal("Warning", "No schedule data to save.", false);
      return;
    }
    saveSchedule(selectedTruckKey, schedule);
  });

  // Undo optimization
  undoButton.addEventListener("click", () => {
    if (previousSchedule) {
      schedule = JSON.parse(JSON.stringify(previousSchedule));
      populateScheduleTable(schedule);
      showModal("Message", "Optimization undone successfully!", false);
      undoButton.disabled = true;
    } else {
      showModal("Info", "No optimization to undo.", false);
    }
  });

  // Remove start/dump from input boxes
  removeStartButton.addEventListener("click", () => {
    removeStartFeature();
  });
  removeDumpButton.addEventListener("click", () => {
    removeDumpFeature();
  });

  // Initialize Popup Overlay
  initializePopup();

  // Finally, fetch trucks on page load
  fetchTrucks();
});

/* =====================================
   3) Show/Hide Loading Screen
   ===================================== */
function showLoading(isLoading) {
  loadingScreen.classList.toggle("show", isLoading);
}

/* =====================================
   4) Initialize OpenLayers Map
   ===================================== */
function initOpenLayersMap() {
  map = new ol.Map({
    target: 'map',
    layers: [
      new ol.layer.Tile({
        source: new ol.source.OSM()
      }),
      clusters
    ],
    view: new ol.View({
      center: ol.proj.fromLonLat([125.809347, 7.447173]), // [lon, lat]
      zoom: 12
    })
  });

  // Click event to add start or dump feature
  map.on('singleclick', function (evt) {
    const coordinate = evt.coordinate;
    if (!startFeature) {
      addStartFeature(coordinate);
    } else if (!dumpFeature) {
      addDumpFeature(coordinate);
    } else {
      showModal("Info", "Starting Point and Dumping Site are already set. Remove one if you need to change it.", false);
    }
  });

  // Add interaction for feature selection
  const selectClick = new ol.interaction.Select({
    condition: ol.events.condition.click,
    layers: [clusters]
  });

  map.addInteraction(selectClick);

  selectClick.on('select', function (e) {
    const selected = e.selected[0];
    if (selected && selected.get('features').length === 1) {
      const feature = selected.get('features')[0];
      if (feature.get('type') === 'start' || feature.get('type') === 'dump') {
        // Display popup or handle accordingly
        showPopup(feature);
      }
    }
  });
}

/* =====================================
   5) Marker Management Functions
   ===================================== */
function addStartFeature(coordinate) {
  const feature = new ol.Feature({
    geometry: new ol.geom.Point(coordinate),
    name: 'Starting Point (S)',
    type: 'start',
    icon: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png' // Ensure 'icon' property is set
  });
  vectorSource.addFeature(feature);
  startFeature = feature;
  startingPointInput.value = ol.proj.toLonLat(coordinate).join(", ");
}

function addDumpFeature(coordinate) {
  const feature = new ol.Feature({
    geometry: new ol.geom.Point(coordinate),
    name: 'Dumping Site (D)',
    type: 'dump',
    icon: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png' // Ensure 'icon' property is set
  });
  vectorSource.addFeature(feature);
  dumpFeature = feature;
  dumpingSiteInput.value = ol.proj.toLonLat(coordinate).join(", ");
}

function removeStartFeature() {
  if (startFeature) {
    vectorSource.removeFeature(startFeature);
    startFeature = null;
    startingPointInput.value = "";
  }
}

function removeDumpFeature() {
  if (dumpFeature) {
    vectorSource.removeFeature(dumpFeature);
    dumpFeature = null;
    dumpingSiteInput.value = "";
  }
}

/* =====================================
   6) Nominatim Search
   ===================================== */
function setupNominatimSearch() {
  let searchTimeout = null;
  placeSearchInput.addEventListener("input", () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    const query = placeSearchInput.value.trim();
    if (query.length < 3) {
      searchResults.innerHTML = "";
      return;
    }
    searchTimeout = setTimeout(() => {
      fetchNominatim(query);
    }, 300);
  });
}

async function fetchNominatim(query) {
  try {
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;
    const resp = await fetch(url, {
      headers: {
        'Accept-Language': 'en' // Optional: Specify language
      }
    });
    if (!resp.ok) throw new Error(`Nominatim fetch error: ${resp.status}`);
    const data = await resp.json();
    searchResults.innerHTML = "";
    if (!data || data.length === 0) {
      searchResults.innerHTML = '<li class="list-group-item">No results found</li>';
      return;
    }
    data.forEach(place => {
      const li = document.createElement("li");
      li.className = "list-group-item list-group-item-action";
      li.textContent = place.display_name;
      li.addEventListener("click", () => {
        const lon = parseFloat(place.lon);
        const lat = parseFloat(place.lat);
        const coordinate = ol.proj.fromLonLat([lon, lat]);
        map.getView().animate({ center: coordinate, zoom: 17 });

        // Add or update search marker
        if (searchLayer) {
          vectorSource.removeFeature(searchLayer);
        }
        const feature = new ol.Feature({
          geometry: new ol.geom.Point(coordinate),
          name: place.display_name,
          type: 'search',
          icon: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png' // Ensure 'icon' property is set
        });
        vectorSource.addFeature(feature);
        searchLayer = feature;

        placeSearchInput.value = place.display_name;
        searchResults.innerHTML = "";
      });
      searchResults.appendChild(li);
    });
  } catch (err) {
    console.error(err);
    showModal("Error", "Failed to fetch search results.");
  }
}

/* =====================================
   7) Fetch Trucks & Schedule
   ===================================== */
async function fetchTrucks() {
  try {
    truckSelect.disabled = true;
    truckSelect.innerHTML = '<option value="">Loading...</option>';
    const response = await fetch('../backend/fetch_trucks.php');
    if (!response.ok) {
      throw new Error(`[fetchTrucks] Network response was not ok (${response.status})`);
    }
    const data = await response.json();
    if (data.status !== "success") {
      throw new Error(data.message || "Failed to fetch trucks.");
    }
    trucks = data.data;

    populateTruckSelection(trucks);
  } catch (error) {
    console.error("[fetchTrucks] ERROR:", error);
    showModal("Error", error.message);
    truckSelect.innerHTML = '<option value="">-- Select a Truck --</option>';
  } finally {
    truckSelect.disabled = false;
  }
}

function populateTruckSelection(trucks) {
  truckSelect.innerHTML = '<option value="">-- Select a Truck --</option>';
  for (const [key, truck] of Object.entries(trucks)) {
    const option = document.createElement("option");
    option.value = key;
    option.textContent = truck.vehicleName || `Truck ${key}`;
    truckSelect.appendChild(option);
  }
}

async function fetchSchedule(truckKey) {
  if (!truckKey) {
    clearScheduleTable();
    return;
  }
  if (currentFetchController) {
    currentFetchController.abort();
  }
  currentFetchController = new AbortController();
  const { signal } = currentFetchController;

  try {
    showLoading(true);
    const url = `../backend/fetch_schedule.php?truckKey=${encodeURIComponent(truckKey)}`;
    const response = await fetch(url, { signal });
    if (!response.ok) {
      throw new Error(`[fetchSchedule] Network response was not ok (${response.status})`);
    }
    const data = await response.json();
    if (data.status === "success") {
      schedule = data.schedules;
      populateScheduleTable(schedule);
      undoButton.disabled = true;
    } else {
      clearScheduleTable();
      showModal("Info", data.message || "No existing schedule found.", false);
    }
  } catch (error) {
    if (error.name !== "AbortError") {
      console.error("[fetchSchedule] ERROR:", error);
      showModal("Error", error.message);
      clearScheduleTable();
    }
  } finally {
    showLoading(false);
    currentFetchController = null;
  }
}

function clearScheduleTable() {
  scheduleTableBody.innerHTML = "";
  vectorSource.clear(); // Remove all markers
  const daysOfWeek = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
  daysOfWeek.forEach(day => {
    const row = document.createElement("tr");
    const dayCell = document.createElement("td");
    dayCell.textContent = day;
    const tasksCell = document.createElement("td");
    tasksCell.textContent = "No task assigned";
    row.appendChild(dayCell);
    row.appendChild(tasksCell);
    scheduleTableBody.appendChild(row);
  });
}

function populateScheduleTable(scheduleObj) {
  scheduleTableBody.innerHTML = "";
  vectorSource.clear();

  const daysOfWeek = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
  daysOfWeek.forEach(day => {
    const row = document.createElement("tr");

    const dayCell = document.createElement("td");
    dayCell.textContent = day;
    row.appendChild(dayCell);

    const tasksCell = document.createElement("td");
    let dayPlaces = [];
    if (scheduleObj.days && scheduleObj.days[day] && scheduleObj.days[day].places && scheduleObj.days[day].places.length > 0) {
      dayPlaces = scheduleObj.days[day].places;
      const tasks = dayPlaces
        .map(place => {
          if (place && place.name && place.longitude && place.latitude) {
            return `${place.name} [${place.longitude}, ${place.latitude}]`;
          } else {
            console.warn('Invalid place:', place);
            return 'Invalid Place';
          }
        })
        .join(", ");
      tasksCell.textContent = tasks;

      dayPlaces.forEach(place => {
        if (place && place.name && place.longitude && place.latitude) {
          const feature = new ol.Feature({
            geometry: new ol.geom.Point(ol.proj.fromLonLat([place.longitude, place.latitude])),
            name: place.name,
            icon: 'https://maps.google.com/mapfiles/ms/icons/yellow-dot.png' // Ensure 'icon' property is set
          });
          vectorSource.addFeature(feature);
        } else {
          console.warn('Invalid place:', place);
        }
      });
    } else {
      tasksCell.textContent = "No task assigned";
    }
    row.appendChild(tasksCell);
    scheduleTableBody.appendChild(row);
  });

  clusterSource.refresh(); // Refresh clusters
}

/* =====================================
   8) Global Optimization
   ===================================== */
/**
 * Splits an array of items into smaller batches.
 *
 * @param {Array} items - The array of items to split.
 * @param {number} batchSize - The maximum number of items per batch.
 * @returns {Array[]} An array of batches, each being an array of items.
 */
function splitIntoBatches(items, batchSize) {
  const batches = [];
  for (let i = 0; i < items.length; i += batchSize) {
    batches.push(items.slice(i, i + batchSize));
  }
  return batches;
}

// Define the maximum number of locations per optimization request
const MAX_LOCATIONS_PER_BATCH = 50; // Reduced from 70

// Define maximum total routes allowed
const MAX_TOTAL_ROUTES = 3500;

// Define rate limiting parameters
const MAX_REQUESTS_PER_MINUTE = 40;
const REQUEST_INTERVAL_MS = 1500; // 1.5 seconds between requests

/**
 * Delays execution for a specified number of milliseconds.
 * @param {number} ms - Milliseconds to delay.
 * @returns {Promise} Promise that resolves after the delay.
 */
function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

async function globalOptimizeAllPlaces(startCoord, endCoord) {
  showLoading(true);
  try {
    if (!schedule.days) {
      showModal("Warning", "No schedule data loaded.", false);
      generateButton.disabled = false;
      return;
    }

    const allPlaces = [];
    const daysOfWeek = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
    daysOfWeek.forEach(day => {
      if (schedule.days[day] && schedule.days[day].places) {
        schedule.days[day].places.forEach(place => {
          allPlaces.push(place);
        });
      }
    });

    if (allPlaces.length === 0) {
      showModal("Warning", "No places to optimize.", false);
      generateButton.disabled = false;
      return;
    }

    // Check total routes limit
    if (allPlaces.length > MAX_TOTAL_ROUTES) {
      showModal("Error", `The total number of routes (${allPlaces.length}) exceeds the allowed limit of ${MAX_TOTAL_ROUTES}. Please reduce the number of optimization points.`, true);
      generateButton.disabled = false;
      return;
    }

    // Split locations into batches
    const batches = splitIntoBatches(allPlaces, MAX_LOCATIONS_PER_BATCH);

    const optimizedAll = [];

    for (const [index, batch] of batches.entries()) {
      // Prepare jobs and vehicles for each batch
      const jobs = batch.map((place, idx) => ({
        id: idx + 1 + index * MAX_LOCATIONS_PER_BATCH, // Unique ID across batches
        location: [place.longitude, place.latitude],
        description: place.name
      }));

      const vehicles = [{
        id: 1,
        profile: "driving-car",
        start: [startCoord[0], startCoord[1]],
        end: [endCoord[0], endCoord[1]]
      }];

      // Make the optimization request to the backend
      const response = await fetchWithRetry('../backend/optimization_proxy.php', {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ jobs, vehicles })
      });

      console.log("Optimization Response:", response); // Added log

      if (response.status !== "success") {
        throw new Error(response.message || "An unknown error occurred during optimization.");
      }

      const steps = response.routes[0].steps;
      steps.forEach(step => {
        if (step.type === "job") {
          const jobId = step.id;
          // Calculate local index within the current batch
          const localIndex = jobId - 1 - (index * MAX_LOCATIONS_PER_BATCH);
          if (localIndex >= 0 && localIndex < batch.length) {
            const place = batch[localIndex];
            if (place) {
              optimizedAll.push(place);
            } else {
              console.warn(`Place at local index ${localIndex} is undefined.`);
            }
          } else {
            console.warn(`Job ID ${jobId} is out of range for the current batch.`);
          }
        }
      });

      // Implement rate limiting: wait before sending the next batch
      if (index < batches.length - 1) { // No need to wait after the last batch
        await delay(REQUEST_INTERVAL_MS);
      }
    }

    // Assign optimized places back to the schedule
    let currentIndex = 0;
    daysOfWeek.forEach(day => {
      const count = schedule.days[day]?.places?.length || 0;
      if (count > 0) {
        schedule.days[day].places = optimizedAll.slice(currentIndex, currentIndex + count);
        currentIndex += count;
      }
    });

    populateScheduleTable(schedule);
    showModal("Message", "Global optimization completed successfully!", false);
    undoButton.disabled = false;
    generateRouteModal.hide();
  } catch (error) {
    console.error("[globalOptimizeAllPlaces] Error:", error);
    showModal("Error", error.message, true);
  } finally {
    showLoading(false);
    generateButton.disabled = false;
  }
}

/* =====================================
   9) Save Schedule
   ===================================== */
async function saveSchedule(truckKey, scheduleObj) {
  if (!truckKey || !scheduleObj) {
    showModal("Error", "Invalid truck key or schedule data.", true);
    return;
  }
  showLoading(true);
  try {
    const response = await fetch("../backend/rsave_schedule.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ truckKey, schedule: scheduleObj })
    });
    if (!response.ok) {
      const errorData = await response.json().catch(() => null);
      throw new Error(errorData ? errorData.message : "Unknown error occurred.");
    }
    const data = await response.json();
    if (data.status === "success") {
      showModal("Message", "Schedule saved successfully!", false);
    } else {
      throw new Error(data.message || "Failed to save schedule.");
    }
  } catch (error) {
    console.error("[saveSchedule] ERROR:", error);
    showModal("Error", error.message, true);
  } finally {
    showLoading(false);
  }
}

/* =====================================
   10) Modal and UI Helpers
   ===================================== */
function showModal(title, message, isError = true) {
  errorModalLabel.textContent = title;
  errorModalLabel.classList.toggle("text-danger", isError);
  errorModalLabel.classList.toggle("text-success", !isError);
  errorMessage.textContent = message;
  errorModal.show();
}

/* =====================================
   11) Popup Overlay Initialization
   ===================================== */
function initializePopup() {
  // Create an overlay to anchor the popup to the map.
  const overlay = new ol.Overlay({
    element: document.getElementById('popup'),
    autoPan: {
      animation: {
        duration: 250
      }
    }
  });
  map.addOverlay(overlay);

  // Function to show popup
  window.showPopup = function(feature) {
    const coordinates = feature.getGeometry().getCoordinates();
    const name = feature.get('name');
    const type = feature.get('type'); // 'start' or 'dump'
    
    // Determine the action based on feature type
    let actionButton = '';
    if (type === 'start') {
      actionButton = `<button id="deleteMarkerButton" class="btn btn-danger btn-sm mt-2">
                        <i class="fas fa-trash-alt"></i> Delete Starting Point
                      </button>`;
    } else if (type === 'dump') {
      actionButton = `<button id="deleteMarkerButton" class="btn btn-danger btn-sm mt-2">
                        <i class="fas fa-trash-alt"></i> Delete Dumping Site
                      </button>`;
    }

    document.getElementById('popup-content').innerHTML = `<b>${name}</b><br>${actionButton}`;
    overlay.setPosition(coordinates);

    // Attach event listener to the delete button
    const deleteButton = document.getElementById('deleteMarkerButton');
    if (deleteButton) {
      deleteButton.addEventListener('click', () => {
        // Confirm deletion
        if (confirm(`Are you sure you want to delete the ${type === 'start' ? 'Starting Point' : 'Dumping Site'}?`)) {
          if (type === 'start') {
            removeStartFeature();
          } else if (type === 'dump') {
            removeDumpFeature();
          }
          overlay.setPosition(undefined); // Close the popup
        }
      });
    }
  }

  // Close popup when clicking the closer
  const popupCloser = document.getElementById('popup-closer');
  if (popupCloser) {
    popupCloser.onclick = function() {
      overlay.setPosition(undefined);
      popupCloser.blur();
      return false;
    };
  } else {
    console.warn("Popup closer element not found!");
  }
}

/* =====================================
   12) Fetch with Retry (Exponential Backoff)
   ===================================== */
async function fetchWithRetry(url, options, retries = 3, backoff = 1000) {
  try {
    const response = await fetch(url, options);
    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    return await response.json();
  } catch (error) {
    if (retries > 0) {
      console.warn(`Fetch failed. Retrying in ${backoff} ms... (${retries} retries left)`);
      await delay(backoff);
      return fetchWithRetry(url, options, retries - 1, backoff * 2);
    } else {
      throw error;
    }
  }
}

// --------------[ LOAD USERNAME & OPTIONAL PROFILE IMAGE ]--------------
async function fetchUserData() {
  try {
    // We expect something like:
    // { "status": "success", "username": "admin", "profile_image": "" }
    const response = await fetch('../backend/fetch_users.php');
    const data     = await response.json();

    if (data.status === 'success') {
      const usernameEl = document.getElementById('dropdownUsername');
      const profileImg  = document.getElementById('profileImg');

      usernameEl.textContent = data.name || 'Unknown';
      if (data.profile_image) {
        profileImg.src = data.profile_image;
      }
    }
  } catch (error) {
    console.error('Error fetching user data:', error);
  }
}

  </script>
</body>
</html>
