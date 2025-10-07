<?php
session_start();
// If not logged in, redirect
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php");
    exit;
}

$orsConfig = require '../backend/ors_api.php';
$orsApiKey = $orsConfig['ors_api_key'];
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hakot | Truck Tracker</title>
    <link rel="icon" type="image/x-icon" href="img/hakot-icon.png">
    <link rel="stylesheet" type="text/css" href="navs.css"/>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.css" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@latest/ol.css" />
    <script src="https://cdn.jsdelivr.net/npm/ol@latest/dist/ol.js"></script>

    <style>

       *, *::before, *::after {
           box-sizing: border-box;
       }
       body, html {
           height: 100%;
           margin: 0;
           padding: 0;
       }
       body {
           font-family: "Poppins", sans-serif;
           background-color: #f9f9f9;
           height: 100%;
       }
       .loading-container {
           position: absolute;
           top: 0;
           left: 0;
           width: 100%;
           height: 100%;
           background-color: rgba(255, 255, 255, 0.8);
           display: none;
           justify-content: center;
           align-items: center;
           z-index: 999;
           transition: opacity 0.3s ease-in-out;
       }
       .loading-container.show {
           display: flex;
       }
       .offcanvas.offcanvas-start {
           width: 250px;
           z-index: 1040;
       }
       .content {
           padding-top: 60px;
           padding-left: 20px;
           padding-right: 20px;
           max-width: 100%;
           height: calc(100vh - 60px);
           display: flex;
           flex-direction: column;
           overflow: hidden;
       }
       @media (min-width: 992px) {
           .content {
               margin-left: 250px;
           }
       }
       @media (max-width: 991.98px) {
           .content {
               margin-left: 0;
           }
       }
       .tracker-map-container {
           flex: 1 1 auto;
           display: flex;
           flex-direction: row;
           gap: 20px;
           overflow: hidden;
           height: 100%;
           min-height: 0;
       }
       .tracker-list {
           flex: 0 0 333px;
           display: flex;
           flex-direction: column;
           background: white;
           border: 1px solid #e0e0e0;
           border-radius: 8px;
           padding: 10px;
           box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
           height: 100%;
           min-height: 0;
           position: relative;
       }
       .search-filter-container {
           background: #ffffff;
           padding: 10px 14px;
           border-radius: 8px;
           box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
           z-index: 100;
           display: block;
           flex-shrink: 0;
       }
       .search-filter-container.mb-3 {
           margin-bottom: 1rem;
       }
       .btn-icon {
           display: flex;
           align-items: center;
           justify-content: center;
           width: 40px;
           height: 40px;
           padding: 0;
           border-radius: 4px;
           transition: background-color 0.3s;
       }
       .d-flex.align-items-center.gap-2 > *:not(:last-child) {
           margin-right: 0.1rem;
       }
       .truck-list-container {
           flex: 1 1 auto;
           padding: 0;
           margin: 0;
           position: relative;
           min-height: 0;
       }
       .tracker-list ul {
           list-style: none;
           padding: 0;
           margin: 0;
       }
       .tracker-list ul li {
           list-style: none;
           display: flex;
           align-items: center;
           padding: 10px;
           border-bottom: 1px solid #e0e0e0;
           transition: background-color 0.3s;
           position: relative;
       }
       .tracker-list ul li:hover {
           background-color: #e6f7ee;
           cursor: pointer;
       }
       .tracker-list ul li.active {
           background-color: #d1e7dd;
       }
       .color-indicator {
           width: 10px;
           height: 100%;
           background-color: #32CD32;
           position: absolute;
           left: 0;
           top: 0;
           border-top-left-radius: 8px;
           border-bottom-left-radius: 8px;
       }
       .tracker-list ul li img {
           width: 40px;
           height: 40px;
           border-radius: 50%;
           margin-right: 10px;
           object-fit: cover;
       }
       .tracker-list ul li .route {
           color: #777;
           font-size: 14px;
       }
       .simplebar-scrollbar::before {
           background-color: #32CD32;
       }
       .map-container {
           flex: 2 1 500px;
           border-radius: 8px;
           overflow: hidden;
           box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
           position: relative;
           height: 100%;
       }
       #map {
           width: 100%;
           height: 100%;
           border: none;
       }
       .truck-details {
           position: absolute;
           bottom: 20px;
           left: 20px;
           background: white;
           border-radius: 8px;
           box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
           padding: 20px;
           width: 250px;
           z-index: 1000;
           display: none;
       }
       .truck-details.active {
           display: block;
       }
       .truck-details h4 {
           margin: 0 0 10px 0;
           font-size: 20px;
           color: #333;
       }
       .truck-details p {
           margin: 5px 0;
           font-size: 14px;
           color: #555;
       }
       @media (max-width: 576px) {
           .truck-details {
               width: 90%;
               left: 50%;
               transform: translateX(-50%);
               bottom: 10px;
           }
       }
       @media (max-width: 768px) {
           .tracker-map-container {
               flex-direction: column;
           }
           .tracker-list, .map-container {
               width: 100%;
               max-height: none;
           }
           .truck-list-container {
               max-height: 60vh;
           }
           .map-container {
               height: 60vh;
           }
           .search-filter-container {
               padding: 10px;
           }
           .search-filter .form-control {
               flex: 1 1 200px;
           }
           .search-filter .day-selector {
               flex: 0 0 140px;
           }
       }
       .reset-map-btn {
           background-color: #fff;
           border: 2px solid #32CD32;
           border-radius: 4px;
           padding: 8px 12px;
           font-size: 14px;
           color: #32CD32;
           cursor: pointer;
           display: flex;
           align-items: center;
           gap: 5px;
           box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
           transition: background-color 0.3s, color 0.3s;
       }
       .reset-map-btn:hover {
           background-color: #32CD32;
           color: #fff;
       }
       .reset-map-btn-container {
           margin: 10px;
       }
       .truck-details .btn-close {
           position: absolute;
           top: 10px;
           right: 10px;
       }
       .truck-list-container::-webkit-scrollbar {
           width: 8px;
       }
       .truck-list-container::-webkit-scrollbar-track {
           background: #f1f1f1;
           border-radius: 4px;
       }
       .truck-list-container::-webkit-scrollbar-thumb {
           background-color: #32CD32;
           border-radius: 4px;
           border: 2px solid #f1f1f1;
       }
       .truck-list-container {
           scrollbar-width: thin;
           scrollbar-color: #32CD32 #f1f1f1;
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
    <!-- Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" data-bs-backdrop="false">
        <div class="offcanvas-header d-lg-none">
            <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Menu</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <!-- Sidebar Content -->
            <img src="img/hakot-new.png" alt="HAKOT Logo" style="height:120px; width:120px; margin-bottom: 10px;">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="tracker.php" class="active"><i class="fas fa-map-marker-alt"></i> Tracker</a>
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
  <div class="topbar d-flex align-items-center px-3" style="position: fixed; top: 0; left: 0; right: 0; height: 60px; background-color: #fff; border-bottom: 1px solid #ddd; z-index: 1050;">
    <button class="btn btn-outline-success d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle sidebar">
      <span class="fas fa-bars"></span>
    </button>
    <div class="d-flex align-items-center ms-auto">
      <a class="dropdown-item" id="dropdownUsername"></a>
      <div>
      <a href="profile.php">
        <img id="profileImg" src="img/default-profile.jpg" width="35" height="35" style="border-radius:50%; cursor:pointer;">
      </a>
      <div aria-labelledby="profileImg" id="profileDropdown"></div>
      </div>
    </div>
  </div>

    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid d-flex flex-column h-100">
            <!-- Tracker Header -->
            <div class="tracker-header">
                <!-- Optional Header Content -->
            </div>
            <!-- Tracker List and Map Container using Flexbox -->
            <div class="tracker-map-container mt-4">
                <!-- Tracker List Column -->
                <div class="tracker-list d-flex flex-column">
                    <!-- Search and Filter Container -->
                    <div class="search-filter-container mb-3">
                        <!-- First Row: Search Input and Buttons -->
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" class="form-control flex-grow-1" id="searchInput" placeholder="Search Truck or Plate Number" aria-label="Search Truck">
                            <button class="btn btn-success btn-icon" id="filterBtn" aria-label="Filter" data-bs-toggle="modal" data-bs-target="#filterModal" title="Filter">
                                <i class="fas fa-filter"></i>
                            </button>
                            <button class="btn btn-danger btn-icon" id="clearFilterBtn" aria-label="Clear Filters" title="Clear Filters">
                                <i class="fa-solid fa-filter-circle-xmark"></i>
                            </button>
                        </div>
                        <!-- Second Row: Day Selector -->
                        <div class="mt-2">
                            <select class="form-select day-selector" id="daySelector">
                                <option value="Sunday">Sunday</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>
                    </div>
                    <!-- Loading Screen for Truck List -->
                    <div class="loading-container" id="listLoading">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <!-- Truck List (Scrollable) -->
                    <div class="truck-list-container" data-simplebar>
                        <ul id="truckList">
                            <!-- Populated via JS -->
                        </ul>
                    </div>
                </div>
                <!-- Map Column -->
                <div class="map-container">
                    <!-- New Reset Truck Location Button (upper right of the map) -->
                    <button id="resetTruckLocationBtn" class="reset-map-btn" style="position: absolute; top: 10px; right: 10px; z-index: 1100;">
                        <i class="fas fa-sync-alt"></i> Reset Truck Location
                    </button>
                    <div class="loading-container" id="mapLoading">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="map"></div>
                    <!-- Truck Details Overlay -->
                    <div class="truck-details" id="truckDetails">
                        <h4 id="detailVehicleName">Vehicle Name</h4>
                        <p><strong>Route:</strong> <span id="detailRoute">N/A</span></p>
                        <p><strong>Driver:</strong> <span id="detailDriver">Unknown</span></p>
                        <p><strong>Plate Number:</strong> <span id="detailPlateNumber">N/A</span></p>
                        <!-- The close button now resets only when clicked -->
                        <button type="button" class="btn-close" aria-label="Close" onclick="hideTruckDetails()"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error/Success Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="errorModalLabel">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="errorMessage"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Select Trucks to Display</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="all" id="selectAll">
                            <label class="form-check-label" for="selectAll">Select All</label>
                        </div>
                        <hr>
                        <div id="truckCheckboxes">
                            <!-- Dynamically generated checkboxes will go here -->
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-square-fill"></i> Cancel</button>
                    <button type="button" class="btn btn-success" id="applyFilterBtn"><i class="fa-solid fa-filter"></i> Apply Filter</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Truck Location Modal -->
    <div class="modal fade" id="resetTruckLocationModal" tabindex="-1" aria-labelledby="resetTruckLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetTruckLocationModalLabel">Reset Truck Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="resetTruckLocationForm">
                        <div id="resetTruckCheckboxes">
                            <!-- Dynamically generated checkboxes will go here -->
                        </div>
                        <hr>
                        <p>Default Location: CENRO Office</p>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-square-fill"></i> Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmResetTruckLocationBtn"><i class="fas fa-sync-alt"></i> Reset Location</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" defer></script>
    <!-- SimpleBar JS for Custom Scrollbar -->
    <script src="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.min.js" defer></script>

    <!-- JavaScript for Interactivity and Data Handling -->
    <script>
    /**
     * Generates a Bootstrap-styled SVG marker for OpenLayers (for static markers).
     * @param {string} color - The marker color.
     * @returns {string} - Data URL containing the SVG marker.
     */
    function generateBootstrapIconMarker(color) {
        return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="${color}" class="bi bi-geo-fill" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999zm2.493 8.574a.5.5 0 0 1-.411.575c-.712.118-1.28.295-1.655.493a1.3 1.3 0 0 0-.37.265.3.3 0 0 0-.057.09V14l.002.008.016.033a.6.6 0 0 0 .145.15c.165.13.435.27.813.395.751.25 1.82.414 3.024.414s2.273-.163 3.024-.414c.378-.126.648-.265.813-.395a.6.6 0 0 0 .146-.15l.015-.033L12 14v-.004a.3.3 0 0 0-.057-.09 1.3 1.3 0 0 0-.37-.264c-.376-.198-.943-.375-1.655-.493a.5.5 0 1 1 .164-.986c.77.127 1.452.328 1.957.594C12.5 13 13 13.4 13 14c0 .426-.26.752-.544.977-.29.228-.68.413-1.116.558-.878.293-2.059.465-3.34.465s-2.462-.172-3.34-.465c-.436-.145-.826-.33-1.116-.558C3.26 14.752 3 14.426 3 14c0-.599.5-1 .961-1.243.505-.266 1.187-.467 1.957-.594a.5.5 0 0 1 .575.411"/>
            </svg>
        `)}`;
    }

    /**
     * Utility function: Generate random color in HEX format.
     */
    function getRandomColor() {
        const letters = '0123456789ABCDEF';
        let color = '#';
        for (let i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

    /**
     * Function to show/hide loading indicators.
     */
    function showLoading(container) {
        container.classList.add('show');
    }
    function hideLoading(container) {
        container.classList.remove('show');
    }

    /**
     * Function to display modal messages.
     */
    function showModalMessage(type, message) {
        const modalTitle = document.getElementById('errorModalLabel');
        const modalBody = document.getElementById('errorMessage');
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        if (type === 'success') {
            modalTitle.textContent = 'Message';
            modalTitle.classList.remove('text-danger');
            modalTitle.classList.add('text-success');
        } else {
            modalTitle.textContent = 'Error';
            modalTitle.classList.remove('text-success');
            modalTitle.classList.add('text-danger');
        }
        modalBody.textContent = message;
        errorModal.show();
    }

    // --- DOM and Data Variables ---
    let mapLoading, listLoading, truckList, truckDetails;
    let detailVehicleName, detailRoute, detailDriver, detailPlateNumber;
    let searchInput, filterBtn, daySelector;
    let trucksData = {};
    let filteredTrucksData = {};
    let colors = {};
    let selectedDay = getToday();
    let olMap, markerLayer, routeLayer, markerSource, routeSource;
    
    // Global variables for current truck location tracking and selection
    let currentLocationSource, currentLocationMarkers = {};
    let clusterLayer;
    let selectedTruck = null; // When non-null, only this truck will be displayed
    // Add these globals near the top of your script
    let lastConnectionUpdateTimestamp = 0;
    const CONNECTION_UPDATE_INTERVAL = 10000; // 10 seconds (in milliseconds)


    // Global variable for the connection line feature (connecting current location to route)
    let connectionRouteFeature = null;

    /**
     * Returns the default style (icon only) for a truck marker.
     * Uses your custom truck icon. (Image path: img/garbage-truck.png)
     * @param {ol.Feature} feature 
     * @returns {ol.style.Style}
     */
    function truckIconDefaultStyle(feature) {
  const type = (feature.get('truckType') || '').trim().toLowerCase();   // <-- normalise

  let file = 'truck.png';            // generic fall-back
  if (type === 'garbage truck')  file = 'garbage-truck.png';
  else if (type === 'sewage truck') file = 'sewage-truck.png';

  return new ol.style.Style({
    image: new ol.style.Icon({
      anchor: [0.5, 1],
      src: `img/${file}`,            // always defined now
      scale: 0.10
    })
  });
}

    /**
     * Returns the current day of the week.
     */
    function getToday() {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return days[new Date().getDay()];
    }

    /**
     * Debounce helper to limit the rate at which a function can fire.
     */
    function debounce(func, delay) {
        let debounceTimer;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => func.apply(context, args), delay);
        };
    }

    /**
     * Fetch reverse geocoding data from ORS via your proxy.
     * Expects a backend endpoint that accepts JSON:
     * { longitude: <lng>, latitude: <lat> }
     * and returns a GeoJSON response with a "features" array.
     */
    async function fetchORSReverseGeocode(lon, lat) {
    try {
        const response = await fetchWithRetry('../backend/ors_proxy_reverse.php', {
        method: 'POST',
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            longitude: lon,
            latitude: lat
        })
        });
        if (!response.ok) {
        throw new Error(`ORS reverse geocode error: ${response.statusText}`);
        }
        const data = await response.json();
        // ORS typically returns a GeoJSON with features having a "properties.label" value.
        if (data && data.features && data.features.length > 0) {
        return data.features[0].properties.label;
        }
        return `${lat}, ${lon}`;
    } catch (error) {
        console.error('Reverse geocode error:', error);
        return `${lat}, ${lon}`;
    }
    }


    /**
     * Helper function to perform a fetch with retry logic.
     */
    async function fetchWithRetry(url, options, retries = 3, backoff = 1000) {
        for (let attempt = 0; attempt < retries; attempt++) {
            try {
                const response = await fetch(url, options);
                if (response.ok) {
                    return response;
                } else {
                    console.warn(`Attempt ${attempt + 1} failed with status ${response.status}`);
                }
            } catch (error) {
                console.warn(`Attempt ${attempt + 1} encountered an error: ${error}`);
            }
            await new Promise(resolve => setTimeout(resolve, backoff * (attempt + 1)));
        }
        throw new Error('Max retries reached');
    }

    // --- Map Initialization ---
    function initOpenLayersMap() {
        showLoading(mapLoading);
        markerSource = new ol.source.Vector();
        routeSource = new ol.source.Vector();
        markerLayer = new ol.layer.Vector({ source: markerSource });
        routeLayer = new ol.layer.Vector({ source: routeSource });
        // Initialize the current truck location source:
        currentLocationSource = new ol.source.Vector();
        // Create a cluster source wrapping the current truck location source:
        const clusterSource = new ol.source.Cluster({
            distance: 40,
            source: currentLocationSource
        });
        // Create a layer to display clusters:
        clusterLayer = new ol.layer.Vector({
            source: clusterSource,
            style: function(feature) {
                const features = feature.get('features');
                const size = features.length;
                if (size > 1) {
                    return new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 15,
                            fill: new ol.style.Fill({ color: '#ff0000' }),
                            stroke: new ol.style.Stroke({ color: '#fff', width: 2 })
                        }),
                        text: new ol.style.Text({
                            text: size.toString(),
                            fill: new ol.style.Fill({ color: '#fff' })
                        })
                    });
                } else {
                    return truckIconDefaultStyle(features[0]);
                }
            }
        });
        olMap = new ol.Map({
            target: 'map',
            view: new ol.View({
                center: ol.proj.fromLonLat([125.814239, 7.443433]),
                zoom: 12
            }),
            layers: [
                new ol.layer.Tile({ source: new ol.source.OSM() }),
                routeLayer,
                markerLayer,
                clusterLayer
            ]
        });
        hideLoading(mapLoading);

        // Create a popup container for displaying marker details.
        const popupContainer = document.createElement('div');
        popupContainer.className = 'ol-popup';
        popupContainer.style.backgroundColor = 'white';
        popupContainer.style.padding = '5px';
        popupContainer.style.border = '1px solid #ccc';
        popupContainer.style.borderRadius = '4px';
        popupContainer.style.minWidth = '150px';
        popupContainer.style.position = 'absolute';
        popupContainer.style.bottom = '12px';
        popupContainer.style.left = '-50px';
        popupContainer.style.display = 'none';
        popupContainer.style.pointerEvents = 'none';
        const overlay = new ol.Overlay({
            element: popupContainer,
            positioning: 'bottom-center',
            stopEvent: false,
            offset: [0, -20]
        });
        olMap.addOverlay(overlay);

        // Updated click event:
        // When a feature is clicked, if it's a cluster with one feature, unwrap it.
        olMap.on('click', function(event) {
            let clickedFeature = olMap.forEachFeatureAtPixel(event.pixel, function(feature) {
                return feature;
            });
            if (clickedFeature) {
                // If the clicked feature is a cluster, unwrap if it contains only one marker.
                if (clickedFeature.get('features')) {
                    const features = clickedFeature.get('features');
                    if (features.length === 1) {
                        clickedFeature = features[0];
                    }
                }
                if (clickedFeature.getGeometry() instanceof ol.geom.Point) {
                    let popupContent = '';
                    if (clickedFeature.get('vehicleName') && clickedFeature.get('plateNumber')) {
                        popupContent = `<strong>${clickedFeature.get('vehicleName')}</strong><br/><em>${clickedFeature.get('plateNumber')}</em>`;
                    } else if (clickedFeature.get('locationName')) {
                        popupContent = `<strong>${clickedFeature.get('locationName')}</strong>`;
                    }
                    if (popupContent) {
                        const coordinates = clickedFeature.getGeometry().getCoordinates();
                        popupContainer.innerHTML = popupContent;
                        overlay.setPosition(coordinates);
                        popupContainer.style.display = 'block';
                    } else {
                        popupContainer.style.display = 'none';
                    }
                } else {
                    popupContainer.style.display = 'none';
                }
            } else {
                popupContainer.style.display = 'none';
            }
        });

        olMap.on('pointermove', function(e) {
            if (e.dragging) return;
            const pixel = olMap.getEventPixel(e.originalEvent);
            const hit = olMap.hasFeatureAtPixel(pixel);
            olMap.getTargetElement().style.cursor = hit ? 'pointer' : '';
        });
    }

    function fitMapToSourceExtent(source) {
        const extent = source.getExtent();
        if (!ol.extent.isEmpty(extent)) {
            olMap.getView().fit(extent, { padding: [50, 50, 50, 50], duration: 500 });
        }
    }

    function clearMap() {
        markerSource.clear();
        routeSource.clear();
    }

    // --- Truck Details and List Functions ---
    async function displayTruckDetails(truckId) {
        // Record the selected truck so only it will be displayed
        selectedTruck = truckId;
        const truck = trucksData[truckId];
        if (!truck) return;
        detailVehicleName.textContent = truck.vehicleName || 'Unknown';
        // detailRoute.textContent = truck.route || 'N/A';
        detailRoute.textContent = truck.truckCurrentLocation
  ? `${truck.truckCurrentLocation.latitude}, ${truck.truckCurrentLocation.longitude}`
  : 'N/A';
        detailDriver.textContent = truck.vehicleDriver || 'Unknown';
        detailPlateNumber.textContent = truck.plateNumber || 'N/A';
        truckDetails.classList.add('active');
        // Clear the map and display only the selected truck’s markers/route
        await highlightTruckRoutes(truckId);
        highlightSelectedTruck(truckId);
    }

    function highlightSelectedTruck(truckId) {
        const items = truckList.querySelectorAll('li');
        const keys = Object.keys(trucksData);
        items.forEach((li, idx) => {
            if (!truckId) {
                li.classList.remove('active');
            } else {
                li.classList.toggle('active', keys[idx] === truckId);
            }
        });
    }

    function createMarkerFeatureAt(lat, lng, title, color) {
        const feature = new ol.Feature({
            geometry: new ol.geom.Point(ol.proj.fromLonLat([lng, lat])),
            locationName: title
        });
        feature.setStyle(new ol.style.Style({
            image: new ol.style.Icon({
                src: generateBootstrapIconMarker(color),
                scale: 1,
                anchor: [0.5, 1]
            })
        }));
        return feature;
    }

    // --- OpenRouteService (ORS) Routing Functions ---
    async function fetchORSRouteGeometry(points, routeColor) {
        try {
            const formattedCoordinates = points;
            const response = await fetchWithRetry('../backend/ors_proxy.php', {
                method: 'POST',
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    coordinates: formattedCoordinates,
                    format: "geojson",
                    profile: "driving-car"
                })
            });
            if (!response.ok) {
                throw new Error(`ORS API error: ${response.status} - ${response.statusText}`);
            }
            const data = await response.json();
            
            if (data.error) {
                console.error("ORS API Error:", data.error.message || data.error);
                return null;
            }
            
            if (!data.features || data.features.length === 0) {
                console.warn("No route found for the given coordinates.");
                return null;
            }
            if (!data.features[0].geometry || !data.features[0].geometry.coordinates) {
                console.error("Invalid geometry data received from ORS.");
                return null;
            }
            
            const routeCoordinates = data.features[0].geometry.coordinates.map(coord =>
                ol.proj.fromLonLat(coord)
            );
            
            const routeFeature = new ol.Feature({
                geometry: new ol.geom.LineString(routeCoordinates)
            });
            routeFeature.setStyle([
                new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#ffffff',
                        width: 8
                    })
                }),
                new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: routeColor,
                        width: 4
                    })
                })
            ]);
            return routeFeature;
        } catch (error) {
            console.error("ORS fetch error:", error);
            return null;
        }
    }

    async function fetchORSRoute(coordinates, routeColor) {
        if (!coordinates || coordinates.length < 2) {
            console.warn("fetchORSRoute: Not enough points to calculate a route.");
            return null;
        }
        try {
            let mergedCoords = [];
            const maxPoints = 80;
            if (coordinates.length > maxPoints) {
                for (let i = 0; i < coordinates.length - 1; i += (maxPoints - 1)) {
                    let chunk = coordinates.slice(i, i + maxPoints);
                    if (i !== 0) {
                        chunk.unshift(coordinates[i]);
                    }
                    const segment = await fetchORSRouteGeometry(chunk, routeColor);
                    if (segment) {
                        const segmentCoords = segment.getGeometry().getCoordinates();
                        if (mergedCoords.length > 0) {
                            mergedCoords = mergedCoords.concat(segmentCoords.slice(1));
                        } else {
                            mergedCoords = segmentCoords;
                        }
                    }
                }
            } else {
                const segment = await fetchORSRouteGeometry(coordinates, routeColor);
                mergedCoords = segment ? segment.getGeometry().getCoordinates() : [];
            }
            if (!mergedCoords || mergedCoords.length === 0) {
                console.warn("No route found from ORS.");
                return null;
            }
            const routeFeature = new ol.Feature({
                geometry: new ol.geom.LineString(mergedCoords)
            });
            routeFeature.setStyle([
                new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#ffffff',
                        width: 8
                    })
                }),
                new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: routeColor,
                        width: 4
                    })
                })
            ]);
            console.log("ORS complete route feature created successfully.");
            const extent = routeLayer.getSource().getExtent();
            if (!ol.extent.isEmpty(extent)) {
                olMap.getView().fit(extent, { padding: [50, 50, 50, 50], duration: 500 });
            }
            return routeFeature;
        } catch (error) {
            console.error("ORS fetch error:", error);
            return null;
        }
    }

    async function plotRoutes(data, day) {
    console.log(`Plotting routes for day: ${day}`);
    showLoading(mapLoading);
    clearMap();
    // If a truck is selected, limit data to that truck only using the filtered data.
    if (selectedTruck && data[selectedTruck]) {
        data = { [selectedTruck]: data[selectedTruck] };
    }
    const allPromises = [];
    Object.entries(data).forEach(([truckId, truck]) => {
        if (!colors[truckId]) {
            colors[truckId] = getRandomColor();
        }
        const routeColor = colors[truckId];
        if (truck.schedules && truck.schedules.days && truck.schedules.days[day]) {
            const daySchedule = truck.schedules.days[day];
            if (daySchedule.places && daySchedule.places.length > 1) {
                const coords = daySchedule.places.map(place => [parseFloat(place.longitude), parseFloat(place.latitude)]);
                const uniqueCoords = Array.from(new Set(coords.map(JSON.stringify))).map(JSON.parse);
                if (uniqueCoords.length < 2) {
                    console.warn(`Truck ID ${truckId} has less than two distinct locations.`);
                    return;
                }
                daySchedule.places.forEach(place => {
                    const markerFeat = createMarkerFeatureAt(place.latitude, place.longitude, place.name, routeColor);
                    markerSource.addFeature(markerFeat);
                });
                allPromises.push(
                    fetchORSRoute(coords, routeColor).then(routeFeat => {
                        if (routeFeat) {
                            routeSource.addFeature(routeFeat);
                        }
                    })
                );
            } else {
                console.warn(`Truck ID ${truckId} does not have enough places to plot a route.`);
            }
        } else {
            console.warn(`Truck ID ${truckId} does not have schedule data for ${day}.`);
        }
    });
    await Promise.all(allPromises);
    fitMapToSourceExtent(markerSource);
    fitMapToSourceExtent(routeSource);
    hideLoading(mapLoading);
}


    function renderTruckList(data) {
  truckList.innerHTML = '';
  Object.entries(data).forEach(([key, truck]) => {
    if (!colors[key]) {
      colors[key] = getRandomColor();
    }
    const li = document.createElement('li');
    li.classList.add('d-flex', 'align-items-center');
    li.dataset.truckId = key; // Important for real-time DOM updates!
    li.innerHTML = `
      <div class="color-indicator" style="background-color: ${colors[key]};"></div>
      <img src="${truck.imageUrl || 'https://via.placeholder.com/40'}" alt="Truck" />
      <div>
          <strong>${truck.vehicleName || 'Unknown'}</strong>
          <p class="route">Route: Loading...</p>
          <p>Driver: ${truck.vehicleDriver || 'Unknown'}</p>
          <p>Plate Number: ${truck.plateNumber || 'N/A'}</p>
      </div>
    `;
    li.addEventListener('click', () => {
      displayTruckDetails(key);
    });
    truckList.appendChild(li);
  });
}



    function populateFilterModal(data) {
        const truckCheckboxesContainer = document.getElementById('truckCheckboxes');
        truckCheckboxesContainer.innerHTML = '';
        Object.entries(data).forEach(([key, truck]) => {
            const div = document.createElement('div');
            div.classList.add('form-check');
            const checkboxInput = document.createElement('input');
            checkboxInput.classList.add('form-check-input');
            checkboxInput.type = 'checkbox';
            checkboxInput.value = key;
            checkboxInput.id = `filterTruck_${key}`;
            checkboxInput.checked = true;
            const label = document.createElement('label');
            label.classList.add('form-check-label');
            label.htmlFor = `filterTruck_${key}`;
            label.textContent = truck.vehicleName || `Truck ${key}`;
            div.appendChild(checkboxInput);
            div.appendChild(label);
            truckCheckboxesContainer.appendChild(div);
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('#truckCheckboxes .form-check-input');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        }
    }

    // Populate the Reset Truck Location Modal with truck checkboxes
    function populateResetTruckModal(data) {
        const resetTruckCheckboxesContainer = document.getElementById('resetTruckCheckboxes');
        resetTruckCheckboxesContainer.innerHTML = '';
        Object.entries(data).forEach(([key, truck]) => {
            const div = document.createElement('div');
            div.classList.add('form-check');
            const checkboxInput = document.createElement('input');
            checkboxInput.classList.add('form-check-input');
            checkboxInput.type = 'checkbox';
            checkboxInput.value = key;
            checkboxInput.id = `resetTruck_${key}`;
            checkboxInput.checked = false;
            const label = document.createElement('label');
            label.classList.add('form-check-label');
            label.htmlFor = `resetTruck_${key}`;
            label.textContent = truck.vehicleName || `Truck ${key}`;
            div.appendChild(checkboxInput);
            div.appendChild(label);
            resetTruckCheckboxesContainer.appendChild(div);
        });
    }

    function applyTruckFilter() {
    const selectedTruckIds = Array.from(document.querySelectorAll('#truckCheckboxes .form-check-input:checked'))
        .map(cb => cb.value);
    if (selectedTruckIds.length === 0) {
        showModalMessage('error', 'No trucks selected. Please select at least one.');
        return;
    }
    filteredTrucksData = {};
    selectedTruckIds.forEach(id => {
        if (trucksData[id]) {
            filteredTrucksData[id] = trucksData[id];
        }
    });
    // If the currently selected truck is not in the filtered trucks, clear the selection.
    if (selectedTruck && !filteredTrucksData[selectedTruck]) {
        selectedTruck = null;
    }
    renderFilteredTruckList(filteredTrucksData);
    const filterModal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
    filterModal.hide();
    }

    function clearTruckFilters() {
        filteredTrucksData = { ...trucksData };
        const checkboxes = document.querySelectorAll('#truckCheckboxes .form-check-input');
        checkboxes.forEach(cb => cb.checked = true);
        renderFilteredTruckList(filteredTrucksData);
    }

    function renderFilteredTruckList(data) {
        truckList.innerHTML = '';
        if (Object.keys(data).length > 0) {
            renderTruckList(data);
            // If a truck is selected, ensure only its route is shown.
            if (selectedTruck) {
                highlightTruckRoutes(selectedTruck);
            } else {
                plotRoutes(data, selectedDay);
            }
        } else {
            truckList.innerHTML = `<li class="text-danger">No trucks match your search.</li>`;
            clearMap();
        }
    }

    function filterTrucks() {
        const query = searchInput.value.trim().toLowerCase();
        if (!query) {
            renderFilteredTruckList(filteredTrucksData);
            return;
        }
        const result = {};
        Object.entries(filteredTrucksData).forEach(([key, truck]) => {
            const nameMatch = truck.vehicleName && truck.vehicleName.toLowerCase().includes(query);
            const plateMatch = truck.plateNumber && truck.plateNumber.toLowerCase().includes(query);
            if (nameMatch || plateMatch) {
                result[key] = truck;
            }
        });
        renderFilteredTruckList(result);
    }

    function fetchAndRenderTrucks() {
        showLoading(listLoading);
        showLoading(mapLoading);
        fetch('../backend/fetch_trucks.php')
            .then(resp => resp.json())
            .then(data => {
                if (data.status === 'success' && data.data) {
                    trucksData = data.data;
                    filteredTrucksData = { ...trucksData };
                    renderTruckList(filteredTrucksData);
                    populateFilterModal(filteredTrucksData);
                    // If a truck is selected, plot only its route; otherwise plot all trucks.
                    if (selectedTruck) {
                        highlightTruckRoutes(selectedTruck);
                    } else {
                        plotRoutes(filteredTrucksData, selectedDay);
                    }
                } else {
                    showModalMessage('error', data.message || 'Failed to fetch trucks data.');
                    truckList.innerHTML = `<li class="text-danger">Error: ${data.message || 'No trucks available'}</li>`;
                    clearMap();
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showModalMessage('error', error.message);
                truckList.innerHTML = `<li class="text-danger">Error: ${error.message}</li>`;
                clearMap();
            })
            .finally(() => {
                hideLoading(listLoading);
                hideLoading(mapLoading);
            });
    }

    // --- Update Truck Current Locations in Real-Time ---
    async function updateTruckLocations() {
  try {
    const response = await fetch('../backend/fetch_trucks.php');
    if (!response.ok) {
      console.error('Error fetching truck locations:', response.statusText);
      return;
    }
    const data = await response.json();
    if (data.status === 'success' && data.data) {
      const trucks = data.data;
      
      // Loop over each truck.
      Object.entries(trucks).forEach(([truckId, truck]) => {
        // If a filter is active and this truck is not in the filtered set,
        // remove its marker and skip.
        // if (Object.keys(filteredTrucksData).length > 0 && !filteredTrucksData.hasOwnProperty(truckId)) {
        //   if (currentLocationMarkers[truckId]) {
        //     currentLocationSource.removeFeature(currentLocationMarkers[truckId]);
        //     delete currentLocationMarkers[truckId];
        //   }
        //   return;
        // }
        const filterActive =
      Object.keys(filteredTrucksData).length !== 0 &&
      Object.keys(filteredTrucksData).length !== Object.keys(trucks).length;

if (!filterActive && !filteredTrucksData.hasOwnProperty(truckId)) {
  // truck is new → add it to the filtered set so we don’t ignore it
  filteredTrucksData[truckId] = trucks[truckId];
}
        // Update global trucksData with the new current location.
        if (trucksData[truckId]) {
          trucksData[truckId].truckCurrentLocation = truck.truckCurrentLocation;
        }
        
        // If a truck is selected, only update its marker.
        if (selectedTruck && truckId !== selectedTruck) {
          if (currentLocationMarkers[truckId]) {
            currentLocationSource.removeFeature(currentLocationMarkers[truckId]);
            delete currentLocationMarkers[truckId];
          }
          return;
        }
        
        // Check for valid truck current location.
        if (truck.truckCurrentLocation &&
            truck.truckCurrentLocation.latitude &&
            truck.truckCurrentLocation.longitude) {
          const lat = parseFloat(truck.truckCurrentLocation.latitude);
          const lng = parseFloat(truck.truckCurrentLocation.longitude);
          const coordinate = ol.proj.fromLonLat([lng, lat]);
          
          // Update or create the current location marker.
          if (currentLocationMarkers[truckId]) {
            currentLocationMarkers[truckId].setGeometry(new ol.geom.Point(coordinate));
            currentLocationMarkers[truckId].set('vehicleName', truck.vehicleName);
            currentLocationMarkers[truckId].set('plateNumber', truck.plateNumber);
            currentLocationMarkers[truckId].set('locationName', `${truck.vehicleName} (${truck.plateNumber})`);
            currentLocationMarkers[truckId].set('truckType', truck.truckType);
            currentLocationMarkers[truckId].setStyle(truckIconDefaultStyle(currentLocationMarkers[truckId]));
          } else {
            const marker = new ol.Feature({
              geometry: new ol.geom.Point(coordinate),
              vehicleName: truck.vehicleName,
              plateNumber: truck.plateNumber,
              locationName: `${truck.vehicleName} (${truck.plateNumber})`,
              truckId: truckId,
              truckType: truck.truckType
            });
            marker.setStyle(truckIconDefaultStyle(marker));
            currentLocationMarkers[truckId] = marker;
            currentLocationSource.addFeature(marker);
          }
          
          // --- Reverse Geocode the Current Location ---
          fetchORSReverseGeocode(lng, lat).then(address => {
            // Update the truck list item.
            const li = truckList.querySelector(`[data-truck-id="${truckId}"]`);
            if (li) {
              const routeElem = li.querySelector('.route');
              if (routeElem) {
                routeElem.textContent = `Route: ${address}`;
              }
            }
            // Update the details overlay if this truck is selected.
            if (selectedTruck && selectedTruck === truckId) {
              detailRoute.textContent = address;
            }
          });
        }
      });
      
      // --- Update the connection line for the selected truck if applicable ---
      if (selectedTruck &&
          trucksData[selectedTruck] &&
          trucksData[selectedTruck].truckCurrentLocation &&
          trucksData[selectedTruck].truckCurrentLocation.latitude &&
          trucksData[selectedTruck].truckCurrentLocation.longitude &&
          trucksData[selectedTruck].schedules &&
          trucksData[selectedTruck].schedules.days &&
          trucksData[selectedTruck].schedules.days[selectedDay] &&
          trucksData[selectedTruck].schedules.days[selectedDay].places &&
          trucksData[selectedTruck].schedules.days[selectedDay].places.length > 0) {

        const truck = trucksData[selectedTruck];
        const currentLat = parseFloat(truck.truckCurrentLocation.latitude);
        const currentLng = parseFloat(truck.truckCurrentLocation.longitude);
        const firstPlace = truck.schedules.days[selectedDay].places[0];
        const firstLat = parseFloat(firstPlace.latitude);
        const firstLng = parseFloat(firstPlace.longitude);
        const connectionCoords = [[currentLng, currentLat], [firstLng, firstLat]];

        if (connectionRouteFeature) {
          routeSource.removeFeature(connectionRouteFeature);
          connectionRouteFeature = null;
        }
        try {
          const connRoute = await fetchORSRoute(connectionCoords, colors[selectedTruck] || '#0000FF');
          if (connRoute) {
            connRoute.setStyle([
              new ol.style.Style({
                stroke: new ol.style.Stroke({
                  color: colors[selectedTruck] || '#0000FF',
                  width: 4,
                  lineDash: [4, 8]
                })
              })
            ]);
            connectionRouteFeature = connRoute;
            routeSource.addFeature(connRoute);
          }
        } catch (error) {
          console.error("Error fetching connection route:", error);
        }
      }
      
      // Refresh the cluster layer's source so that clusters update to show only filtered markers.
      if (clusterLayer && clusterLayer.getSource()) {
        clusterLayer.getSource().refresh();
      }
      
    } else {
      console.error('Failed to update truck locations:', data.message);
    }
  } catch (error) {
    console.error('Error updating truck locations:', error);
  }
}







    // --- Highlight Truck Routes ---
    async function highlightTruckRoutes(truckId) {
        clearMap();
        const truck = trucksData[truckId];
        if (!truck || !truck.schedules || !truck.schedules.days || !truck.schedules.days[selectedDay]) {
            return;
        }
        const places = truck.schedules.days[selectedDay].places;
        if (!places || !places.length) return;
        if (!colors[truckId]) {
            colors[truckId] = getRandomColor();
        }
        const routeColor = colors[truckId];
        const coords = [];
        places.forEach(place => {
            const lat = parseFloat(place.latitude);
            const lng = parseFloat(place.longitude);
            coords.push([lng, lat]); // Correct order for ORS
            const markerFeat = createMarkerFeatureAt(lat, lng, place.name, routeColor);
            markerSource.addFeature(markerFeat);
        });
        fitMapToSourceExtent(markerSource);
        if (coords.length >= 2) {
            const routeFeat = await fetchORSRoute(coords, routeColor);
            if (routeFeat) {
                routeSource.addFeature(routeFeat);
                fitMapToSourceExtent(routeSource);
            }
        }

        if (truck.truckCurrentLocation &&
            truck.truckCurrentLocation.latitude &&
            truck.truckCurrentLocation.longitude &&
            coords.length > 0) {
            const currentLat = parseFloat(truck.truckCurrentLocation.latitude);
            const currentLng = parseFloat(truck.truckCurrentLocation.longitude);
            const connectionCoords = [[currentLng, currentLat], coords[0]];
            try {
                const connRoute = await fetchORSRoute(connectionCoords, routeColor);
                if (connRoute) {
                    connRoute.setStyle([
                        new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: routeColor,
                                width: 4,
                                lineDash: [4, 8]
                            })
                        })
                    ]);
                    routeSource.addFeature(connRoute);
                }
            } catch (error) {
                console.error("Error fetching connection route in highlightTruckRoutes:", error);
            }
        }
    }

    window.addEventListener('load', function() {
        mapLoading = document.getElementById('mapLoading');
        listLoading = document.getElementById('listLoading');
        truckList = document.getElementById('truckList');
        truckDetails = document.getElementById('truckDetails');
        detailVehicleName = document.getElementById('detailVehicleName');
        detailRoute = document.getElementById('detailRoute');
        detailDriver = document.getElementById('detailDriver');
        detailPlateNumber = document.getElementById('detailPlateNumber');
        searchInput = document.getElementById('searchInput');
        filterBtn = document.getElementById('filterBtn');
        daySelector = document.getElementById('daySelector');
        selectedDay = getToday();
        if (daySelector) {
            daySelector.value = selectedDay;
            daySelector.addEventListener('change', function() {
                selectedDay = this.value;

                if (selectedTruck) {
                    highlightTruckRoutes(selectedTruck);
                } else {
                    plotRoutes(filteredTrucksData, selectedDay);
                }
            });
        }
        if (searchInput) {
            searchInput.addEventListener('input', debounce(filterTrucks, 300));
        }
        const applyFilterBtn = document.getElementById('applyFilterBtn');
        if (applyFilterBtn) {
            applyFilterBtn.addEventListener('click', applyTruckFilter);
        }
        const clearFilterBtn = document.getElementById('clearFilterBtn');
        if (clearFilterBtn) {
            clearFilterBtn.addEventListener('click', clearTruckFilters);
        }
        // Event listener for the new Reset Truck Location button
        const resetTruckLocationBtn = document.getElementById('resetTruckLocationBtn');
        if (resetTruckLocationBtn) {
            resetTruckLocationBtn.addEventListener('click', function() {
                populateResetTruckModal(trucksData);
                var resetModal = new bootstrap.Modal(document.getElementById('resetTruckLocationModal'));
                resetModal.show();
            });
        }
        // Event listener for confirming the reset of truck locations
        const confirmResetTruckLocationBtn = document.getElementById('confirmResetTruckLocationBtn');
        if (confirmResetTruckLocationBtn) {
            confirmResetTruckLocationBtn.addEventListener('click', async function() {
                const selectedTruckIds = Array.from(document.querySelectorAll('#resetTruckCheckboxes .form-check-input:checked'))
                    .map(cb => cb.value);
                if (selectedTruckIds.length === 0) {
                    showModalMessage('error', 'Please select at least one truck to reset location.');
                    return;
                }
                const defaultLocation = { latitude: 7.449324, longitude: 125.825484 };
                try {
                    const resetPromises = selectedTruckIds.map(truckId => {
                        return fetchWithRetry('../backend/reset_truck_location.php', {
                            method: 'POST',
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({
                                truckId: truckId,
                                latitude: defaultLocation.latitude,
                                longitude: defaultLocation.longitude
                            })
                        });
                    });
                    const responses = await Promise.all(resetPromises);
                    let allSuccess = true;
                    for (let response of responses) {
                        if (!response.ok) {
                            allSuccess = false;
                            break;
                        }
                    }
                    if (allSuccess) {
                        showModalMessage('success', 'Truck location(s) reset successfully.');
                        updateTruckLocations();
                        bootstrap.Modal.getInstance(document.getElementById('resetTruckLocationModal')).hide();
                    } else {
                        showModalMessage('error', 'Failed to reset one or more truck locations.');
                    }
                } catch (error) {
                    console.error('Error resetting truck locations:', error);
                    showModalMessage('error', error.message);
                }
            });
        }
        fetchUserData();
        initOpenLayersMap();
        fetchAndRenderTrucks();
        updateTruckLocations();

        setInterval(updateTruckLocations, 2000);
        const truckListContainer = document.querySelector('.truck-list-container');
        if (truckListContainer) {
            new SimpleBar(truckListContainer);
        }

        setupClusterSpiderfy();
    });

    function resetMapView() {
        console.log('Resetting map to original state.');
        clearMap();

        if (Object.keys(filteredTrucksData).length === 0) {
            console.warn("No truck data available to plot.");
            return;
        }
        plotRoutes(filteredTrucksData, selectedDay)
            .then(() => {
                console.log('Map has been reset with all routes.');
            })
            .catch(error => {
                console.error('Error resetting map:', error);
            });
        highlightSelectedTruck(null);
    }


    window.hideTruckDetails = function() {
        console.log('Hiding truck details and restoring all routes');
        truckDetails.classList.remove('active');
        selectedTruck = null;
        resetMapView();
    };

    // --------------[ LOAD USERNAME & OPTIONAL PROFILE IMAGE ]--------------
    async function fetchUserData() {
      try {
        const response = await fetch('../backend/fetch_users.php');
        const data = await response.json();
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

    
    function setupClusterSpiderfy() {
  const spiderfySource = new ol.source.Vector();
  const spiderfyLayer = new ol.layer.Vector({
    source: spiderfySource
  });
  olMap.addLayer(spiderfyLayer);

  function spiderfyCluster(clusterFeature, clusterCoordinate) {
    spiderfySource.clear();
    const features = clusterFeature.get('features');
    const numFeatures = features.length;
    if (numFeatures <= 1) return;
    const clusterPixel = olMap.getPixelFromCoordinate(clusterCoordinate);
    const radius = 40;
    for (let i = 0; i < numFeatures; i++) {
      const angle = (2 * Math.PI / numFeatures) * i;
      const offsetX = radius * Math.cos(angle);
      const offsetY = radius * Math.sin(angle);
      const newPixel = [clusterPixel[0] + offsetX, clusterPixel[1] + offsetY];
      const newCoordinate = olMap.getCoordinateFromPixel(newPixel);
      const originalFeature = features[i];
      const spiderFeature = new ol.Feature({
        geometry: new ol.geom.Point(newCoordinate),
        vehicleName: originalFeature.get('vehicleName'),
        plateNumber: originalFeature.get('plateNumber'),
        locationName: originalFeature.get('locationName'),
        truckId: originalFeature.get('truckId'), // Propagate truckId if needed.
        truckType   : originalFeature.get('truckType')  
      });
      spiderFeature.setStyle(truckIconDefaultStyle(spiderFeature));
      spiderfySource.addFeature(spiderFeature);
    }
  }

  olMap.on('click', function (event) {
    let clusterFeature = null;
    let clickedSpider = false;
  
    olMap.forEachFeatureAtPixel(event.pixel, function (feature, layer) {
      if (layer === spiderfyLayer) {
        clickedSpider = true;

        return true;
      }
    });
    
  
    if (!clickedSpider) {
      olMap.forEachFeatureAtPixel(event.pixel, function (feature, layer) {
        if (layer === clusterLayer && feature.get('features') && feature.get('features').length > 1) {
          clusterFeature = feature;
          return true;
        }
      });
      if (clusterFeature) {
        spiderfyCluster(clusterFeature, event.coordinate);
        return;
      } else {
        spiderfySource.clear();
      }
    }
  });
}


    </script>
</body>
</html>
