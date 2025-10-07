<?php

session_start();
// If not logged in, redirect
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../frontend/index.php");
    exit;
}

$apiConfig = require '../backend/gmaps_api.php';
$googleMapsClientApiKey = $apiConfig['google_maps_api_key'];

// Load the OpenRouteService API key from the external PHP file
$orsConfig = require '../backend/ors_api.php';
$orsApiKey = $orsConfig['ors_api_key'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... (existing head content) ... -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartTrashRoute | Truck Tracker</title>
    <link rel="stylesheet" type="text/css" href="navs.css"/>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <!-- SimpleBar CSS for Custom Scrollbar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.css" />
    <!-- OpenLayers CSS + JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@latest/ol.css" />
    <script src="https://cdn.jsdelivr.net/npm/ol@latest/dist/ol.js"></script>

    <!-- Custom CSS (original design unchanged) -->
    <style>
       /* (Your existing CSS snippet remains unchanged) */
       /* Ensure box-sizing is border-box for all elements */
       *, *::before, *::after {
           box-sizing: border-box;
       }

       /* Body and HTML take full height */
       body, html {
           height: 100%;
           margin: 0;
           padding: 0;
       }

       body {
           font-family: "Poppins", sans-serif;
           background-color: #f9f9f9;
           margin: 0;
           height: 100%;
       }

       /* Loading Screen Styles */
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

       /* Offcanvas Sidebar */
       .offcanvas.offcanvas-start {
           width: 250px;
           z-index: 1040;
       }

       /* Main Content Container */
       .content {
           padding-top: 60px; /* Height of the topbar */
           padding-left: 20px;
           padding-right: 20px;
           max-width: 100%;
           height: calc(100vh - 60px); /* Full viewport height minus topbar */
           display: flex;
           flex-direction: column;
           overflow: hidden; /* Prevent overflow */
       }

       @media (min-width: 992px) {
           .content {
               margin-left: 250px; /* Width of the sidebar */
           }
       }

       @media (max-width: 991.98px) {
           .content {
               margin-left: 0;
           }
       }

       /* Tracker List and Map Container */
       .tracker-map-container {
           flex: 1 1 auto; /* Allow the container to grow and shrink as needed */
           display: flex;
           flex-direction: row;
           gap: 20px; /* Space between the list and the map */
           overflow: hidden; /* Prevent content from overflowing */
           height: 100%; /* Occupies full height of the parent */
           min-height: 0; /* Allows Flexbox to manage height */
       }

       /* Tracker List Styling */
       .tracker-list {
           flex: 0 0 333px; /* Fixed width */
           display: flex;
           flex-direction: column;
           background: white;
           border: 1px solid #e0e0e0;
           border-radius: 8px;
           padding: 10px;
           box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
           height: 100%; /* Occupies full height of the parent */
           min-height: 0; /* Allows Flexbox to manage height */
           position: relative;
       }
       /* Search and Filter Container */
       .search-filter-container {
           background: #ffffff;
           padding: 10px 14px;
           border-radius: 8px;
           box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
           z-index: 100;
           display: block; /* Ensure it doesn't flex */
           flex-shrink: 0; /* Prevent it from shrinking */
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

       /* Truck List Container Styling (Scrollable) */
       .truck-list-container {
           flex: 1 1 auto; /* Takes up remaining space */
           padding: 0;
           margin: 0;
           position: relative;
           min-height: 0; /* Crucial for Flexbox to manage height */
       }

       /* Truck List Items */
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

       /* SimpleBar Customization */
       .simplebar-scrollbar::before {
           background-color: #32CD32;
       }

       /* Map Container Styling */
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

       /* Truck Details Modal */
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

       /* Custom Scrollbar for WebKit Browsers */
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
       /* Custom Scrollbar for Firefox */
       .truck-list-container {
           scrollbar-width: thin;
           scrollbar-color: #32CD32 #f1f1f1;
       }
    </style>
</head>
<body>
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
            <!-- Sidebar Content -->
            <img src="hakot-logo.png" alt="HAKOT Logo" style="height:105px; width:150px;">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="truckers.php"><i class="fas fa-users"></i> Truckers</a>
            <a href="trucks.php"><i class="fas fa-truck"></i> Trucks</a>
            <a href="tracker.php" class="active"><i class="fas fa-map-marker-alt"></i> Tracker</a>
            <a href="schedule.php"><i class="fas fa-calendar-alt"></i> Schedule</a>
            <a href="routes.php"><i class="fas fa-route"></i> Routes Optimization</a>
            <div class="bottom-links">
                <a href="settings.html"><i class="fas fa-cog"></i> Settings</a>
                <a href="#"><i class="fas fa-sign-out-alt"></i> Sign out</a>
            </div>
        </div>
    </div>

    <!-- Topbar -->
    <div 
        class="topbar d-flex align-items-center px-3"
    >
        <!-- Hamburger (only shown on small screens) -->
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

        <!-- Bell & Avatar on the right -->
        <div class="d-flex align-items-center ms-auto">
            <i class="fas fa-bell me-3" aria-label="Notifications"></i>
            <img
                src="assets/img/user-avatar.png"
                alt="User Avatar"
                width="35"
                height="35"
                style="border-radius:50%;"
            >
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
                            <!-- Search Input -->
                            <input 
                                type="text" 
                                class="form-control flex-grow-1" 
                                id="searchInput" 
                                placeholder="Search Truck ID or Plate Number" 
                                aria-label="Search Truck"
                            >
                            
                            <!-- Filter Button -->
                            <button  
                                class="btn btn-success btn-icon" 
                                id="filterBtn"
                                aria-label="Filter"
                                data-bs-toggle="modal"
                                data-bs-target="#filterModal"
                                title="Filter"
                            >
                                <i class="fas fa-filter"></i>
                            </button>
                            
                            <!-- Clear Filter Button -->
                            <button
                                class="btn btn-danger btn-icon"
                                id="clearFilterBtn"
                                aria-label="Clear Filters"
                                title="Clear Filters"
                            >
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
                    <!-- Loading Screen for Map -->
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
                        <button type="button" class="btn-close" aria-label="Close" onclick="hideTruckDetails()"></button>
                    </div>
                </div>
            </div>
        </div>
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
                    <!-- Dynamically switch between "Error" and "Success" -->
                    <h5 class="modal-title text-danger" id="errorModalLabel">Error</h5>
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

    <!-- Filter Modal -->
    <div
        class="modal fade"
        id="filterModal"
        tabindex="-1"
        aria-labelledby="filterModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Select Trucks to Display</h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                value="all"
                                id="selectAll"
                            >
                            <label class="form-check-label" for="selectAll">
                                Select All
                            </label>
                        </div>
                        <hr>
                        <div id="truckCheckboxes">
                            <!-- Dynamically generated checkboxes will go here -->
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    ><i class="bi bi-x-square-fill"></i> Cancel</button>
                    <button
                        type="button"
                        class="btn btn-success"
                        id="applyFilterBtn"
                    ><i class="fa-solid fa-filter"></i> Apply Filter</button>
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
     * Generates a Bootstrap-styled SVG marker for OpenLayers.
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


    // ORS API Key from PHP
    const orsApiKey = "<?php echo $orsApiKey; ?>";

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
            modalTitle.textContent = 'Success';
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

    // DOM references
    let mapLoading, listLoading, truckList, truckDetails;
    let detailVehicleName, detailRoute, detailDriver, detailPlateNumber;
    let searchInput, filterBtn, daySelector;

    // Data references
    let trucksData = {};
    let filteredTrucksData = {};
    let colors = {};
    let selectedDay = getToday();

    // OpenLayers references
    let olMap;
    let markerLayer, routeLayer;
    let markerSource, routeSource;

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
     * Initializes the OpenLayers map and event listeners.
     */
    function initOpenLayersMap() {
        showLoading(mapLoading);

        markerSource = new ol.source.Vector();
        routeSource = new ol.source.Vector();

        markerLayer = new ol.layer.Vector({
            source: markerSource
        });
        routeLayer = new ol.layer.Vector({
            source: routeSource
        });

        olMap = new ol.Map({
            target: 'map',
            view: new ol.View({
                center: ol.proj.fromLonLat([125.814239, 7.443433]), // Adjust based on your area
                zoom: 12
            }),
            layers: [
                new ol.layer.Tile({ source: new ol.source.OSM() }), // Base map
                routeLayer,  // Route layer
                markerLayer  // Marker layer
            ]
        });

        hideLoading(mapLoading);

        // Initialize Popup Overlay
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
        popupContainer.style.pointerEvents = 'none'; // Allow clicks to pass through

        const overlay = new ol.Overlay({
            element: popupContainer,
            positioning: 'bottom-center',
            stopEvent: false,
            offset: [0, -20],
        });
        olMap.addOverlay(overlay);

        // Map click event to detect marker clicks and show popup
        olMap.on('click', function(event) {
            const feature = olMap.forEachFeatureAtPixel(event.pixel, function(feature) {
                return feature;
            });
            if (feature && feature.get('locationName')) {
                const coordinates = feature.getGeometry().getCoordinates();
                popupContainer.innerHTML = `<strong>${feature.get('locationName')}</strong>`;
                overlay.setPosition(coordinates);
                popupContainer.style.display = 'block';
            } else {
                popupContainer.style.display = 'none';
            }
        });

        // Change mouse cursor when hovering over markers
        olMap.on('pointermove', function(e) {
            if (e.dragging) {
                return;
            }
            const pixel = olMap.getEventPixel(e.originalEvent);
            const hit = olMap.hasFeatureAtPixel(pixel);
            olMap.getTargetElement().style.cursor = hit ? 'pointer' : '';
        });
    }

    /**
     * Fit map to a given vector source.
     */
    function fitMapToSourceExtent(source) {
        const extent = source.getExtent();
        if (!ol.extent.isEmpty(extent)) {
            olMap.getView().fit(extent, { padding: [50, 50, 50, 50], duration: 500 });
        }
    }

    /**
     * Clears markers and routes from the map.
     */
    function clearMap() {
        markerSource.clear();
        routeSource.clear();
    }

    /**
     * Show truck details for the selected truck.
     */
    function displayTruckDetails(truckId) {
        const truck = trucksData[truckId];
        if (!truck) return;
        detailVehicleName.textContent = truck.vehicleName || 'Unknown';
        detailRoute.textContent       = truck.route || 'N/A';
        detailDriver.textContent      = truck.vehicleDriver || 'Unknown';
        detailPlateNumber.textContent = truck.plateNumber || 'N/A';

        truckDetails.classList.add('active');
        highlightTruckRoutes(truckId);
        highlightSelectedTruck(truckId);
    }

    /**
     * Highlight the selected truck in the list.
     */
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

    /**
     * Creates an OpenLayers marker feature at a given lat/lng.
     * @param {number} lat - Latitude.
     * @param {number} lng - Longitude.
     * @param {string} title - Location name.
     * @param {string} color - Marker color.
     * @returns {ol.Feature} - OpenLayers feature.
     */
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
            // Removed text styling to prevent labels from appearing immediately
        }));

        return feature;
    }

    /**
     * Fetches route geometry from OpenRouteService.
     * @param {Array} coordinates - Array of [latitude, longitude] pairs.
     * @param {string} routeColor - Color for the route.
     * @returns {Promise<ol.Feature | null>} - OpenLayers Feature with route geometry.
     */
    async function fetchORSRouteGeometry(coordinates, routeColor) {
        try {
            const formattedCoordinates = coordinates.map(coord => [coord[1], coord[0]]); // [lng, lat]

            // Log the formatted coordinates for debugging
            console.log("Formatted Coordinates for ORS:", formattedCoordinates);

            const response = await fetch('../backend/ors_proxy.php', {
                method: 'POST',
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    coordinates: formattedCoordinates,
                    format: "geojson"
                })
            });

            if (!response.ok) {
                throw new Error(`ORS API error: ${response.status} - ${errorData.error || 'Unknown error'}`);
            }

            const data = await response.json();
            console.log("ORS Response:", data);

            if (!data.features || data.features.length === 0) {
                console.warn("No route found for the given coordinates.");
                return null;
            }

            const routeCoordinates = data.features[0].geometry.coordinates.map(coord =>
                ol.proj.fromLonLat([coord[0], coord[1]])
            );

            // Create route line with directional arrows
            return new ol.Feature({
                geometry: new ol.geom.LineString(routeCoordinates)
            }).setStyle(new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: routeColor,
                    width: 4
                }),
                // Note: OpenLayers' LineString doesn't support text styling like arrows directly.
                // To add directional arrows, consider using a separate symbol layer or custom styling.
            }));

        } catch (error) {
            console.error("ORS fetch error:", error);
            return null;
        }
    }

    /**
     * Plots routes for all filtered trucks on the selected day.
     */
    async function plotRoutes(data, day) {
        showLoading(mapLoading);
        clearMap();

        const allPromises = [];

        Object.entries(data).forEach(([truckId, truck]) => {
            if (!colors[truckId]) {
                colors[truckId] = getRandomColor();
            }
            const routeColor = colors[truckId];

            if (truck.schedules && truck.schedules.days && truck.schedules.days[day]) {
                const daySchedule = truck.schedules.days[day];

                if (daySchedule.places && daySchedule.places.length > 1) {
                    const coords = daySchedule.places.map(place => [parseFloat(place.latitude), parseFloat(place.longitude)]);

                    // Check for distinct coordinates
                    const uniqueCoords = Array.from(new Set(coords.map(JSON.stringify))).map(JSON.parse);
                    if (uniqueCoords.length < 2) {
                        console.warn('Truck ID ${truckId} has less than two distinct locations.');
                        return;
                    }

                    // Add markers (clickable)
                    daySchedule.places.forEach(place => {
                        const markerFeat = createMarkerFeatureAt(place.latitude, place.longitude, place.name, routeColor);
                        markerSource.addFeature(markerFeat);
                    });

                    // Fetch and add the route
                    allPromises.push(
                        fetchORSRouteGeometry(coords, routeColor).then(routeFeat => {
                            if (routeFeat) {
                                routeSource.addFeature(routeFeat);
                            }
                        })
                    );
                } else {
                    console.warn('Truck ID ${truckId} does not have enough places to plot a route.');
                }
            }
        });

        await Promise.all(allPromises);
        fitMapToSourceExtent(markerSource);
        fitMapToSourceExtent(routeSource);

        hideLoading(mapLoading);
    }

    /**
     * Renders the truck list in the sidebar.
     */
    function renderTruckList(data) {
        truckList.innerHTML = '';
        Object.entries(data).forEach(([key, truck]) => {
            if (!colors[key]) {
                colors[key] = getRandomColor();
            }
            const li = document.createElement('li');
            li.classList.add('d-flex', 'align-items-center');
            li.innerHTML = `
                <div class="color-indicator" style="background-color: ${colors[key]};"></div>
                <img src="${truck.imageUrl || 'https://via.placeholder.com/40'}" alt="Truck" />
                <div>
                    <strong>${truck.vehicleName || 'Unknown'}</strong>
                    <p class="route">Route: ${truck.route || 'N/A'}</p>
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

    /**
     * Populate the filter modal checkboxes.
     */
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
            checkboxInput.id = 'filterTruck_${key}';
            checkboxInput.checked = true;

            const label = document.createElement('label');
            label.classList.add('form-check-label');
            label.htmlFor = 'filterTruck_${key}';
            label.textContent = 'truck.vehicleName || Truck ${key}';

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

    /**
     * Apply the truck filter from the modal.
     */
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
        renderFilteredTruckList(filteredTrucksData);

        const filterModal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
        filterModal.hide();
    }

    /**
     * Clears all truck filters.
     */
    function clearTruckFilters() {
        filteredTrucksData = { ...trucksData };
        const checkboxes = document.querySelectorAll('#truckCheckboxes .form-check-input');
        checkboxes.forEach(cb => cb.checked = true);
        renderFilteredTruckList(filteredTrucksData);
    }

    /**
     * Render the truck list (and map) after filtering.
     */
    function renderFilteredTruckList(data) {
        truckList.innerHTML = '';
        if (Object.keys(data).length > 0) {
            renderTruckList(data);
            plotRoutes(data, selectedDay);
        } else {
            truckList.innerHTML = `<li class="text-danger">No trucks match your search.</li>`;
            clearMap();
        }
    }

    /**
     * Filter the trucks based on search input.
     */
    function filterTrucks() {
        const query = searchInput.value.trim().toLowerCase();
        if (!query) {
            renderFilteredTruckList(filteredTrucksData);
            return;
        }
        const result = {};
        Object.entries(filteredTrucksData).forEach(([key, truck]) => {
            const nameMatch  = truck.vehicleName && truck.vehicleName.toLowerCase().includes(query);
            const plateMatch = truck.plateNumber && truck.plateNumber.toLowerCase().includes(query);
            if (nameMatch || plateMatch) {
                result[key] = truck;
            }
        });
        renderFilteredTruckList(result);
    }

    /**
     * Fetch trucks from backend, render list, plot routes.
     */
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
                    plotRoutes(filteredTrucksData, selectedDay);
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

    /**
     * Highlights the single truck route by clearing map, plotting markers & route.
     */
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
            coords.push([lat, lng]);

            const markerFeat = createMarkerFeatureAt(lat, lng, place.name, routeColor);
            markerSource.addFeature(markerFeat);
        });

        fitMapToSourceExtent(markerSource);

        if (coords.length >= 2) {
            const routeFeat = await fetchORSRouteGeometry(coords, routeColor);
            if (routeFeat) {
                routeSource.addFeature(routeFeat);
                fitMapToSourceExtent(routeSource);
            }
        }
    }

    /**
     * Initializes everything once the window loads.
     */
    window.addEventListener('load', function() {
        // Initialize DOM references
        mapLoading          = document.getElementById('mapLoading');
        listLoading         = document.getElementById('listLoading');
        truckList           = document.getElementById('truckList');
        truckDetails        = document.getElementById('truckDetails');
        detailVehicleName   = document.getElementById('detailVehicleName');
        detailRoute         = document.getElementById('detailRoute');
        detailDriver        = document.getElementById('detailDriver');
        detailPlateNumber   = document.getElementById('detailPlateNumber');
        searchInput         = document.getElementById('searchInput');
        filterBtn           = document.getElementById('filterBtn');
        daySelector         = document.getElementById('daySelector');

        // Default to "today" for daySelector
        selectedDay = getToday();
        if (daySelector) {
            daySelector.value = selectedDay;
            daySelector.addEventListener('change', function() {
                selectedDay = this.value;
                plotRoutes(filteredTrucksData, selectedDay);
            });
        }

        // Debounced search
        if (searchInput) {
            searchInput.addEventListener('input', debounce(filterTrucks, 300));
        }

        // Apply Filter
        const applyFilterBtn = document.getElementById('applyFilterBtn');
        if (applyFilterBtn) {
            applyFilterBtn.addEventListener('click', applyTruckFilter);
        }

        // Clear Filter
        const clearFilterBtn = document.getElementById('clearFilterBtn');
        if (clearFilterBtn) {
            clearFilterBtn.addEventListener('click', clearTruckFilters);
        }

        // Initialize OpenLayers map
        initOpenLayersMap();

        // Fetch and render trucks
        fetchAndRenderTrucks();

        // Hide truck details if clicked outside
        document.addEventListener('click', function(event) {
            const mapContainer = document.getElementById('map');
            if (!truckDetails.contains(event.target) &&
                !event.target.closest('.tracker-list li') &&
                !mapContainer.contains(event.target)) {
                hideTruckDetails();
            }
        });

        // Initialize SimpleBar for custom scrollbar
        const truckListContainer = document.querySelector('.truck-list-container');
        if (truckListContainer) {
            new SimpleBar(truckListContainer);
        }
    });

    /**
     * Resets the map to its original state, showing all trucks and their routes.
     */
    function resetMapView() {
        console.log('Resetting map to original state.');

        // Clear all markers and routes
        clearMap();

        // Ensure there are trucks available before reloading routes
        if (Object.keys(filteredTrucksData).length === 0) {
            console.warn("No truck data available to plot.");
            return;
        }

        // Re-plot all routes based on the default selected day
        plotRoutes(filteredTrucksData, selectedDay)
            .then(() => {
                console.log('Map has been reset with all routes.');
            })
            .catch(error => {
                console.error('Error resetting map:', error);
            });

        // Remove any highlights from the truck list
        highlightSelectedTruck(null);
    }

    /**
     * Hides the truck details overlay and restores all routes.
     * Globally accessible for the close button.
     */
    window.hideTruckDetails = function() {
        console.log('Hiding truck details and restoring all routes');

        // Hide the details overlay
        const truckDetails = document.getElementById('truckDetails');
        truckDetails.classList.remove('active');

        // Reset the map view and restore all markers/routes
        resetMapView();
    };
</script>

</body>
</html>