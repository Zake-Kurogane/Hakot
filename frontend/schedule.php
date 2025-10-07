<?php
session_start();
// If not logged in, redirect
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php");
    exit;
}
// $apiConfig = require '../backend/gmaps_api.php';
// $googleMapsClientApiKey = $apiConfig['google_maps_api_key'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Hakot | Truck Schedules</title>
  <link rel="icon" type="image/x-icon" href="img/hakot-icon.png">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" type="text/css" href="navs.css"/>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
  />
  <!-- Bootstrap Icons CSS -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
  />

  <!-- OpenLayers CSS + JS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@latest/ol.css" />
  <script src="https://cdn.jsdelivr.net/npm/ol@latest/dist/ol.js"></script>

  <!-- Custom CSS -->
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f0f2f5;
    }

    /* Offcanvas Sidebar */
    .offcanvas.offcanvas-start {
      width: 250px;
    }
    /* Content area */
    .content {
      margin-left: 250px;
      padding: 80px 20px 20px;
      transition: margin-left 0.3s;
    }
    @media (max-width: 992px) {
      .content {
        margin-left: 0; 
        padding: 80px 10px 10px;
      }
    }

    /* Details Panel */
    .details-panel {
      background-color: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      position: relative; /* Ensure the overlay is positioned relative to the panel */
    }
    .details-panel h2 {
      margin-bottom: 20px;
      color: #333;
    }
    .details-loading-overlay {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(255, 255, 255, 0.8);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10; /* Ensure it sits above other content */
      flex-direction: column;
      border-radius: 10px; /* Match the panel's border-radius */
    }

    /* Action Buttons */
    .action-buttons {
      margin-top: 15px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    /* Trucks grid */
    .trucks-section {
      background-color: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .trucks-grid-container {
      max-height: 600px;
      overflow-y: auto;
      padding-right: 10px;
      position: relative;
    }
    .trucks-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
    }
    .truck-card {
      position: relative;
      overflow: hidden;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      cursor: pointer;
      transition: transform 0.3s, box-shadow 0.3s, border 0.3s;
      background-color: #fff;
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    .truck-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .truck-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      display: block;
      flex-shrink: 0;
      border-bottom: 1px solid #ddd;
    }
    .truck-details {
      padding: 10px 15px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .truck-details h5 {
      margin: 0;
      font-size: 18px;
      color: #333;
    }
    .truck-details p {
      margin: 5px 0;
      font-size: 14px;
      color: #555;
    }
    .truck-card.selected {
      border: 2px solid #32CD32;
    }
    /* Scrollbar customization */
    .trucks-grid-container::-webkit-scrollbar {
      width: 8px;
    }
    .trucks-grid-container::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    .trucks-grid-container::-webkit-scrollbar-thumb {
      background: #32CD32;
      border-radius: 10px;
    }
    .trucks-grid-container::-webkit-scrollbar-thumb:hover {
      background: #28a745;
    }

    /* Map Container (OpenLayers) */
    .map-container {
      position: relative;
      width: 100%;
      height: 400px;
    }
    #map {
      width: 100%;
      height: 100%;
    }

    /* Search Results Dropdown */
    #searchResults {
      position: absolute;
      top: 40px;
      left: 50%;
      transform: translateX(-50%);
      width: 300px;
      max-height: 200px;
      overflow-y: auto;
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 5px;
      z-index: 1000;
      display: none;
    }
    #searchResults li {
      padding: 8px 12px;
      cursor: pointer;
    }
    #searchResults li:hover {
      background-color: #f1f1f1;
    }

    /* Map Search Box */
    #placeSearchInput {
      position: absolute;
      top: 10px;            
      left: 50%;            
      transform: translateX(-50%); 
      z-index: 9999;        
      width: 300px;         
      padding: 5px 10px;
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

    /* Loading Screen */
    .loading-screen {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(255, 255, 255, 0.8);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 2000;
      display: none; /* hidden by default */
    }
    .loading-screen .spinner-border {
      width: 3rem;
      height: 3rem;
    }
    .loading-screen .loading-text {
      margin-left: 15px;
      font-size: 1.2rem;
      font-weight: bold;
      color: #198754;
    }

    /* Editable Year Styles */
    .editable-year {
      cursor: pointer;
      border-bottom: 1px dashed #000;
    }
    .editable-year-input {
      width: 80px;
    }
    .edit-place-btn {
      margin-left: 5px;
    }
    .truck-summary {
      page-break-after: always;
    }
    /* Ensure the content fits A4 when printed */
    @media print {
      .modal-content {
        width: 210mm;
        height: 297mm;
        overflow: hidden;
      }
    }
    /* minimal styling */
    .day-btn {
      background: none;
      border: none;
      text-align: center;
      cursor: pointer;
    }
    .day-icon {
      width: 75px;
      height: 75px;
      display: block;
    }
    .day-btn.active div {
      font-weight: bold;
      color: #28a745;
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
      <!-- The actual sidebar content -->
      <img src="img/hakot-new.png" alt="HAKOT Logo" style="height:120px; width:120px; margin-bottom: 10px;">
      <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
      <a href="tracker.php"><i class="fas fa-map-marker-alt"></i> Truck Tracker</a>
      <a href="schedule.php" class="active"><i class="fas fa-calendar-alt"></i> Truck Schedules</a>
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

  <!-- Loading Screen -->
  <div class="loading-screen" id="loadingScreen">
    <div class="d-flex align-items-center">
      <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <div class="loading-text">Saving Schedule...</div>
    </div>
  </div>

  <!-- New All Schedules Loading Screen -->
  <div class="loading-screen" id="allLoadingScreen" style="display: none;">
    <div class="d-flex align-items-center">
      <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <div class="loading-text">Loading All Schedules...</div>
    </div>
  </div>

  <!-- Main Content -->
   <!-- Main Content -->
   <div class="content">
    <div class="row">
      <!-- Left Column: Truck List (now first) -->
      <div class="col-lg-7 col-md-12">
        <div class="trucks-section">
          <h2>Truck List</h2>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <input type="text" id="truckSearchInput" placeholder="Search trucks..." class="form-control" style="max-width:300px;">
            <button id="viewAllScheduleSummaryBtn" class="btn btn-primary" type="button" aria-label="View All Schedule Summary">
              <i class="fas fa-eye"></i> View All Schedule Summary
            </button>
          </div>
          <div class="trucks-grid-container">
            <div id="trucksLoading" class="text-center my-3">
              <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>
              <p class="text-success">Loading trucks...</p>
            </div>
            <div class="trucks-grid" id="trucksGrid" style="display:none;">
              <!-- Truck cards inserted dynamically -->
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Schedule Details (now second) -->
      <div class="col-lg-5 col-md-12 mb-4">
        <div class="details-panel">
          <div id="detailsLoading" class="details-loading-overlay" style="display:none;">
            <div class="d-flex flex-column align-items-center">
              <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>
              <div class="loading-text mt-2">Loading Schedule...</div>
            </div>
          </div>

          <h2>Schedule Details</h2>
          <p><strong>Shift:</strong> <span id="shiftSchedule">---</span></p>
          <p><strong>Collection Time:</strong> <span id="collectionTime">---</span></p>
          <p><strong>Truck Details:</strong> <span id="truckDetails">-Select a truck-</span></p>
          <p><strong>Truck Driver:</strong></p>
          <p><span id="driverName"></span></p>
          <p><strong>Garbage Collector:</strong></p>
          <ol id="collectorList"></ol>

          <div class="action-buttons">
            <button type="button" class="btn btn-success add-schedule-btn" data-bs-toggle="modal" data-bs-target="#scheduleModal" disabled aria-label="Add or Update Schedule">
              <i class="fas fa-plus"></i> Add/Update Schedule
            </button>
            <button type="button" class="btn btn-primary" id="viewScheduleSummaryBtn" data-bs-toggle="modal" data-bs-target="#scheduleSummaryModal" disabled aria-label="View Schedule Summary">
              <i class="fas fa-eye"></i> View Schedule Summary
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Schedule Modal -->
  <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="scheduleForm">
          <div class="modal-header">
            <h5 class="modal-title" id="scheduleModalLabel">Add/Update Schedule</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <!-- SHIFT (Day or Night) -->
            <div class="mb-3">
              <label for="weekShiftSelect" class="form-label">Shift (Entire Week)</label>
              <select
                id="weekShiftSelect"
                class="form-select"
                onchange="toggleWeekTimeFields()"
                aria-label="Select Shift"
              >
                <option value="day">Day Shift</option>
                <option value="night">Night Shift</option>
              </select>
            </div>

            <!-- TIME FIELDS (Morning/Afternoon) for Entire Week -->
            <div id="weekTimeFields" style="display: block; margin-bottom: 1.5rem;">
              <div class="row mb-2">
                <div class="col-md-6">
                  <label for="weekMorningStart" class="form-label">Morning Start</label>
                  <input
                    type="time"
                    id="weekMorningStart"
                    class="form-control"
                    required
                    aria-required="true"
                  >
                </div>
                <div class="col-md-6">
                  <label for="weekMorningEnd" class="form-label">Morning End</label>
                  <input
                    type="time"
                    id="weekMorningEnd"
                    class="form-control"
                    required
                    aria-required="true"
                  >
                </div>
              </div>
              <div class="row mb-2">
                <div class="col-md-6">
                  <label for="weekAfternoonStart" class="form-label">Afternoon Start</label>
                  <input
                    type="time"
                    id="weekAfternoonStart"
                    class="form-control"
                    required
                    aria-required="true"
                  >
                </div>
                <div class="col-md-6">
                  <label for="weekAfternoonEnd" class="form-label">Afternoon End</label>
                  <input
                    type="time"
                    id="weekAfternoonEnd"
                    class="form-control"
                    required
                    aria-required="true"
                  >
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-center mb-3" id="dayToggleBar">
            <script>
              // 1) build your seven buttons exactly as before…
              const DAYS = ['M','T','W','Th','F','Sa','Su'];
              const container = document.getElementById('dayToggleBar');

              DAYS.forEach((label, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'day-btn mx-1';
                btn.dataset.idx = idx;
                btn.innerHTML = `
                  <img src="img/${idx*2+1}.png" class="day-icon" alt="${label}" />
                  <div>${label}</div>
                `;
                container.appendChild(btn);
              });

              // 2) wire up your click-to-toggle routine as before…
              container.addEventListener('click', e => {
                const btn = e.target.closest('.day-btn');
                if (!btn) return;
                container.querySelectorAll('.day-btn').forEach(other => {
                  const i = +other.dataset.idx;
                  const img = other.querySelector('img');
                  if (other === btn) {
                    other.classList.add('active');
                    img.src = `img/${i*2+2}.png`;
                  } else {
                    other.classList.remove('active');
                    img.src = `img/${i*2+1}.png`;
                  }
                });
                document.querySelectorAll('.day-content').forEach((panel, j) => {
                  panel.style.display = j === +btn.dataset.idx ? '' : 'none';
                });
              });

              // 3) now hook up “activate today” so it runs *every* time the modal opens:
              const scheduleModalEl = document.getElementById('scheduleModal');
              scheduleModalEl.addEventListener('shown.bs.modal', () => {
                const dow = new Date().getDay();           // 0=Sun..6=Sat
                const idx = dow === 0 ? 6 : dow - 1;       // map Sun→6, Mon→0…Sat→5
                const todayBtn = container.querySelector(`.day-btn[data-idx="${idx}"]`);
                if (todayBtn) todayBtn.click();
              });
            </script>
            </div>


              <!-- Per-day content sections -->
              <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day): ?>
                <div class="day-content" id="dayContent<?= $day ?>" style="<?= $day!=='Monday'?'display:none;':'' ?>">
                  <ul class="list-group place-list" id="placeList<?= $day ?>">
                    <!-- existing items -->
                  </ul>
                  <button type="button"
                          class="btn btn-primary btn-sm mt-2 add-place-btn"
                          data-day="<?= $day ?>">
                    <i class="fas fa-plus"></i> Add Place
                  </button>
                </div>
              <?php endforeach; ?>

          </div>
          <div class="modal-footer d-flex justify-content-between">
            <!-- Bulk Assign trigger on bottom-left -->
            <button
              type="button"
              class="btn btn-outline-primary"
              data-bs-toggle="modal"
              data-bs-target="#bulkAssignModal"
            >
              <i class="fas fa-copy"></i> Bulk Assign
            </button>

            <!-- original Save/Cancel -->
            <div>
              <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Save Schedule
              </button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="bi bi-x-square-fill"></i> Cancel
              </button>
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>

  <!-- Bulk Assign Modal -->
<div class="modal fade" id="bulkAssignModal" tabindex="-1" aria-labelledby="bulkAssignModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bulkAssignModalLabel">Bulk Assign Places</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul class="list-group place-list" id="bulkPlaceList">
          <!-- existing bulk items will remain here -->
        </ul>
        <button
          type="button"
          class="btn btn-primary btn-sm mt-3 add-place-btn"
          data-day="Bulk"
        >
          <i class="fas fa-plus"></i> Add Place
        </button>

        <hr/>

        <label class="form-label fw-bold">Select days to apply:</label>
        <div class="row g-2">
          <?php
            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            foreach ($days as $d) {
              echo <<<HTML
              <div class="col-6 col-md-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="$d" id="chkBulk{$d}">
                  <label class="form-check-label" for="chkBulk{$d}">$d</label>
                </div>
              </div>
              HTML;
            }
          ?>
        </div>

      </div>
      <div class="modal-footer">
        <button
          type="button"
          class="btn btn-success"
          id="applyBulkPlacesBtn"
        >
          <i class="fas fa-copy"></i> Apply to Selected Days
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-square-fill"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>


  <!-- Map Selection Modal -->
  <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="mapModalLabel">Select Place on Map</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <!-- Loading Indicator -->
          <div id="mapLoading" class="text-center my-3">
            <div class="spinner-border text-success" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-success">Loading map...</p>
          </div>

          <div class="map-container">
            <!-- The map goes here -->
            <div id="map"></div>
            <!-- Search Input and Results -->
            <input
              type="text"
              id="placeSearchInput"
              class="form-control"
              placeholder="Search for places..."
              aria-label="Search for places on the map"
            >
            <ul id="searchResults" class="list-group"></ul>
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-primary"
            id="selectPlaceBtn"
            disabled
            aria-label="Select This Place"
          >
            <i class="bi bi-geo-fill"></i> Select Place
          </button>
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal"
            aria-label="Cancel Selection"
          >
            <i class="bi bi-x-square-fill"></i> Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- All Schedule Summary Modal -->
  <div class="modal fade" id="allScheduleSummaryModal" tabindex="-1" aria-labelledby="allScheduleSummaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="allScheduleSummaryModalLabel">All Trucks Schedule Summary</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Loading spinner -->
          <div id="allSummaryLoading" class="text-center my-3" style="display: none;">
            <div class="spinner-border text-success" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-success">Loading all schedules...</p>
          </div>
          <!-- Aggregated schedule content -->
          <div id="allScheduleSummaryContent">
            <!-- Aggregated schedule summary will be inserted here -->
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" id="downloadAllPdfBtn" aria-label="Download All Schedules as PDF">
            <i class="fas fa-download"></i> Download PDF
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close Modal">
            <i class="fas fa-close"></i> Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Schedule Summary Modal -->
  <div class="modal fade" id="scheduleSummaryModal" tabindex="-1" aria-labelledby="scheduleSummaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="scheduleSummaryModalLabel">Schedule Summary</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <!-- We'll fill this dynamically with JS -->
          <div id="scheduleSummaryContent"></div>
        </div>
        <div class="modal-footer">
          <!-- Download PDF Button -->
          <button
            type="button"
            class="btn btn-success"
            id="downloadPdfBtn"
            aria-label="Download Schedule Summary as PDF"
          >
            <i class="fas fa-download"></i> Download PDF
          </button>
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal"
            aria-label="Close Summary Modal"
          >
          <i class="fas fa-close"></i> Close
          </button>
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

  <!-- html2pdf.js Library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    /*
     * Generic function to show an Error/Success Modal
     */
    function showModalMessage(type, message) {
      const modalTitle = document.getElementById('errorModalLabel');
      const modalBody  = document.getElementById('errorMessage');

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

      const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
      errorModal.show();
    }

    /* --------------------------------------------------------
       GLOBAL VARIABLES & EXISTING LOGIC (fixed)
    -------------------------------------------------------- */
    let selectedTruckKey = null;
    let currentDay = null;
    let selectedPlace = null;

    let lastLoadedSchedule = null;
    let lastDriverName = '';
    let lastTruckName = '';
    let lastTruckPlate = '';
    let lastCollectors = [];
    let scheduleYear = '<?php echo date('Y'); ?>'; // Default to current year

    let editingPlaceLi = null;

    const dayNames = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

    function toggleWeekTimeFields() {
      const shiftSelect = document.getElementById('weekShiftSelect');
      const timeFields  = document.getElementById('weekTimeFields');

      const morningStart    = document.getElementById('weekMorningStart');
      const morningEnd      = document.getElementById('weekMorningEnd');
      const afternoonStart  = document.getElementById('weekAfternoonStart');
      const afternoonEnd    = document.getElementById('weekAfternoonEnd');

      if (shiftSelect.value === 'night') {
        timeFields.style.display = 'none';
        morningStart.removeAttribute('required');
        morningEnd.removeAttribute('required');
        afternoonStart.removeAttribute('required');
        afternoonEnd.removeAttribute('required');
      } else {
        timeFields.style.display = 'block';
        morningStart.setAttribute('required', true);
        morningEnd.setAttribute('required', true);
        afternoonStart.setAttribute('required', true);
        afternoonEnd.setAttribute('required', true);
      }
    }

    function convertTo12Hour(time24) {
      if (!time24 || time24 === '--') return '---';
      const [hourStr, minute] = time24.split(':');
      let hour = parseInt(hourStr, 10);
      if (isNaN(hour) || isNaN(parseInt(minute, 10))) {
        return '---';
      }
      const period = hour >= 12 ? 'PM' : 'AM';
      hour = hour % 12 || 12;
      return `${hour}:${minute} ${period}`;
    }

    /*
     * MAP + GEOCODING LOGIC (no geolocation; using updated SVG for marker)
     */
    let olMap;
    let vectorSource;
    let vectorLayer;
    let selectedMarker = null;
    let searchLayer   = null;

    function createMarkerFeature(lonLat) {
      // Using the provided SVG marker
      const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#3c82fa" class="bi bi-pin-map-fill" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8z"/>
  <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/>
</svg>`;
      const iconSrc = 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);

      const iconStyle = new ol.style.Style({
        image: new ol.style.Icon({
          anchor: [0.5, 1],
          src: iconSrc,
          scale: 1,
        })
      });

      const feature = new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.fromLonLat(lonLat))
      });
      feature.setStyle(iconStyle);
      return feature;
    }

    async function reverseGeocode(lat, lon) {
      const url = `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=jsonv2`;
      try {
        const resp = await fetch(url);
        const data = await resp.json();
        if (data && data.display_name) {
          return data.display_name;
        }
        return "";
      } catch (err) {
        console.error("Reverse geocode error:", err);
        return "";
      }
    }

    async function placeMarkerAt(lon, lat, placeName = "") {
      if (selectedMarker) {
        vectorSource.removeFeature(selectedMarker);
        selectedMarker = null;
      }
      let resolvedName = placeName;
      if (!resolvedName) {
        resolvedName = await reverseGeocode(lat, lon);
      }
      selectedMarker = createMarkerFeature([lon, lat]);
      vectorSource.addFeature(selectedMarker);

      selectedPlace = {
        name: resolvedName || "",
        lat: lat,
        lng: lon
      };
      document.getElementById('selectPlaceBtn').disabled = false;

      const placeSearchInput = document.getElementById('placeSearchInput');
      placeSearchInput.value = resolvedName || (lat + ", " + lon);

      const searchResults = document.getElementById('searchResults');
      searchResults.innerHTML = "";
      searchResults.style.display = 'none';
    }

    function initializeOpenLayersMap() {
      document.getElementById('mapLoading').style.display = 'block';

      vectorSource = new ol.source.Vector();
      vectorLayer = new ol.layer.Vector({ source: vectorSource });

      olMap = new ol.Map({
        target: 'map',
        layers: [
          new ol.layer.Tile({ source: new ol.source.OSM() }),
          vectorLayer
        ],
        view: new ol.View({
          center: ol.proj.fromLonLat([125.809347, 7.447173]),
          zoom: 14
        })
      });

      // Remove geolocation code:
      document.getElementById('mapLoading').style.display = 'none';

      // On Map Click => place marker + reverse geocode
      olMap.on('click', async (evt) => {
        const coord = ol.proj.toLonLat(evt.coordinate);
        await placeMarkerAt(coord[0], coord[1]);
      });

      setupNominatimSearch();
      
    }

    function setupNominatimSearch() {
      const placeSearchInput = document.getElementById("placeSearchInput");
      const searchResults = document.getElementById("searchResults");

      let searchTimeout = null;
      placeSearchInput.addEventListener("input", () => {
        if (searchTimeout) clearTimeout(searchTimeout);
        const query = placeSearchInput.value.trim();
        if (query.length < 3) {
          searchResults.innerHTML = "";
          searchResults.style.display = 'none';
          return;
        }
        searchTimeout = setTimeout(() => {
          fetchNominatim(query);
        }, 300);
      });

      document.addEventListener('click', function(event) {
        if (!placeSearchInput.contains(event.target) && !searchResults.contains(event.target)) {
          searchResults.innerHTML = "";
          searchResults.style.display = 'none';
        }
      });
    }

    async function fetchNominatim(query) {
      try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;
        const resp = await fetch(url, {
          headers: {
            'Accept-Language': 'en-US',
            'Referer': window.location.origin
          }
        });
        if (!resp.ok) throw new Error(`Nominatim fetch error: ${resp.status}`);
        const data = await resp.json();
        const searchResults = document.getElementById("searchResults");
        searchResults.innerHTML = "";
        if (!data || data.length === 0) {
          searchResults.innerHTML = '<li class="list-group-item">No results found</li>';
          searchResults.style.display = 'block';
          return;
        }
        data.forEach(place => {
          const li = document.createElement("li");
          li.className = "list-group-item list-group-item-action";
          li.textContent = place.display_name;
          li.addEventListener("click", () => {
            const lon = parseFloat(place.lon);
            const lat = parseFloat(place.lat);
            olMap.getView().animate({ center: ol.proj.fromLonLat([lon, lat]), zoom: 17 });

            if (searchLayer) {
              vectorSource.removeFeature(searchLayer);
            }
            const feature = createMarkerFeature([lon, lat]);
            vectorSource.addFeature(feature);
            searchLayer = feature;

            document.getElementById("placeSearchInput").value = place.display_name;
            searchResults.innerHTML = "";
            searchResults.style.display = 'none';
          });
          searchResults.appendChild(li);
        });
        searchResults.style.display = 'block';
      } catch (err) {
        console.error(err);
        showModalMessage('error', 'Failed to fetch search results.');
      }
    }

    /*
     *  TRUCKS INIT / SCHEDULE LOADING ETC.
     */
    document.addEventListener('DOMContentLoaded', function () {
      const mapModalEl = document.getElementById('mapModal');
      const mapModalInstance = new bootstrap.Modal(mapModalEl);

      mapModalEl.addEventListener('shown.bs.modal', function () {
        initializeOpenLayersMap();
      });
      mapModalEl.addEventListener('hidden.bs.modal', function () {
        if (selectedMarker) vectorSource.removeFeature(selectedMarker);
        selectedMarker = null;
        if (searchLayer) vectorSource.removeFeature(searchLayer);
        searchLayer = null;
        if (olMap) {
          olMap.setTarget(null);
          olMap = null;
        }
        document.getElementById('selectPlaceBtn').disabled = true;
        editingPlaceLi = null;
      });

      initializeTrucks();
      fetchUserData();
      initializeEventListeners();

      // NEW: Truck search functionality
      document.getElementById('truckSearchInput').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const truckCards = document.querySelectorAll('.truck-card');
        truckCards.forEach(card => {
          if (card.textContent.toLowerCase().includes(query)) {
            card.style.display = '';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });

    function showModalMessage(type, message) {
      const modalTitle = document.getElementById('errorModalLabel');
      const modalBody  = document.getElementById('errorMessage');

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

      const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
      errorModal.show();
    }

    function initializeTrucks() {
      const trucksGrid = document.getElementById('trucksGrid');
      const trucksLoading = document.getElementById('trucksLoading');
      const viewAllBtn = document.getElementById('viewAllScheduleSummaryBtn');

      // Disable the button until trucks are loaded
      viewAllBtn.disabled = true;


      trucksLoading.style.display = 'block';
      trucksGrid.style.display = 'none';

      fetch('../backend/fetch_trucks.php')
        .then(resp => resp.json())
        .then(data => {
          trucksLoading.style.display = 'none';
          trucksGrid.style.display = 'grid';

          if (data.status === 'success' && data.data) {
            Object.entries(data.data).forEach(([key, truck]) => {
              const truckCard = document.createElement('div');
              truckCard.className = 'truck-card';
              truckCard.dataset.key = key;

              // Add extra data attributes from Firebase here:
              truckCard.dataset.truckName  = truck.vehicleName || 'Unknown Truck';
              truckCard.dataset.plateNumber = truck.plateNumber || 'N/A';
              truckCard.dataset.driver      = truck.vehicleDriver || 'Unassigned';
              truckCard.dataset.collectors  = Array.isArray(truck.garbageCollectors)
                ? truck.garbageCollectors.join(', ')
                : 'Unassigned';

              const img = document.createElement('img');
              img.src = truck.imageUrl || 'placeholder-truck.png';
              img.alt = truck.vehicleName || 'Truck Image';
              img.loading = 'lazy';

              const detailsOverlay = document.createElement('div');
              detailsOverlay.className = 'truck-details';

              const truckName = document.createElement('h5');
              truckName.innerText = truck.vehicleName || 'Unknown Truck';

              const plateP = document.createElement('p');
              plateP.innerHTML = `<strong>Plate #:</strong> ${truck.plateNumber || 'N/A'}`;

              const driverP = document.createElement('p');
              driverP.innerHTML = `<strong>Driver:</strong> ${truck.vehicleDriver || 'Unassigned'}`;

              const collectorsP = document.createElement('p');
              collectorsP.innerHTML = `<strong>Collectors:</strong> ${
                truck.garbageCollectors && truck.garbageCollectors.length > 0
                  ? truck.garbageCollectors.join(', ')
                  : 'Unassigned'
              }`;

              detailsOverlay.appendChild(truckName);
              detailsOverlay.appendChild(plateP);
              detailsOverlay.appendChild(driverP);
              detailsOverlay.appendChild(collectorsP);

              truckCard.appendChild(img);
              truckCard.appendChild(detailsOverlay);

              truckCard.addEventListener('click', () => {
                selectedTruckKey = key;
                document.getElementById('detailsLoading').style.display = 'flex';
                document.getElementById('driverName').innerText =
                  truck.vehicleDriver || 'Unassigned';
                document.getElementById('truckDetails').innerText =
                  `${truck.vehicleName || 'Unknown'} - Plate #: ${truck.plateNumber || 'N/A'}`;

                lastDriverName = truck.vehicleDriver || 'Unassigned';
                lastTruckName  = truck.vehicleName  || 'Unknown';
                lastTruckPlate = truck.plateNumber  || 'N/A';
                lastCollectors = Array.isArray(truck.garbageCollectors)
                  ? truck.garbageCollectors
                  : ['Unassigned'];

                const collectorList = document.getElementById('collectorList');
                collectorList.innerHTML = '';
                if (truck.garbageCollectors && Array.isArray(truck.garbageCollectors)) {
                  truck.garbageCollectors.forEach(col => {
                    const li = document.createElement('li');
                    li.innerText = col;
                    collectorList.appendChild(li);
                  });
                  
                } else {
                  collectorList.innerHTML = '<li>Unassigned</li>';
                }

                highlightSelectedTruck();
                document.querySelector('.add-schedule-btn').disabled = false;
                document.getElementById('viewScheduleSummaryBtn').disabled = false;
                loadScheduleData(key);
              });

              trucksGrid.appendChild(truckCard);
              viewAllBtn.disabled = false;
            });
          } else {
            trucksGrid.innerHTML = '<p>No trucks available.</p>';
          }
        })
        .catch(error => {
          console.error('Error fetching data:', error);
          trucksLoading.style.display = 'none';
          trucksGrid.style.display = 'block';
          trucksGrid.innerHTML = '<p>Error loading trucks data.</p>';
        });
    }

    function highlightSelectedTruck() {
      const truckCards = document.querySelectorAll('.truck-card');
      truckCards.forEach(card => {
        if (card.dataset.key === selectedTruckKey) {
          card.classList.add('selected');
        } else {
          card.classList.remove('selected');
        }
      });
    }

    function loadScheduleData(truckKey) {
      if (!truckKey) return;
      document.getElementById('detailsLoading').style.display = 'flex';

      fetch(`../backend/fetch_schedule.php?truckKey=${encodeURIComponent(truckKey)}`)
        .then(resp => resp.json())
        .then(data => {
          document.getElementById('detailsLoading').style.display = 'none';

          if (data.status === 'success') {
            const sched = data.schedules || {};
            lastLoadedSchedule = sched;
            scheduleYear = sched.year || '<?php echo date('Y'); ?>';
            // SHIFT
            let shiftValue = '---';
            if (sched.shift === 'day') { shiftValue = 'Day Shift'; }
            else if (sched.shift === 'night') { shiftValue = 'Night Shift'; }
            document.getElementById('shiftSchedule').innerText = shiftValue;

            // TIME
            let timeStr = 'Unassigned';
            if (sched.shift === 'day') {
              const hasMorningTimes    = sched.morningStart || sched.morningEnd;
              const hasAfternoonTimes  = sched.afternoonStart || sched.afternoonEnd;
              if (hasMorningTimes || hasAfternoonTimes) {
                const morningStart   = convertTo12Hour(sched.morningStart);
                const morningEnd     = convertTo12Hour(sched.morningEnd);
                const afternoonStart = convertTo12Hour(sched.afternoonStart);
                const afternoonEnd   = convertTo12Hour(sched.afternoonEnd);
                timeStr = `${morningStart} - ${morningEnd} / ${afternoonStart} - ${afternoonEnd}`;
              }
            } else if (sched.shift === 'night') {
              timeStr = 'Night Shift (No specific times)';
            }
            document.getElementById('collectionTime').innerText = timeStr;

            // SHIFT SELECT
            const shiftSelect = document.getElementById('weekShiftSelect');
            shiftSelect.value = (sched.shift === 'night') ? 'night' : 'day';
            toggleWeekTimeFields();

            if (sched.shift === 'day') {
              document.getElementById('weekMorningStart').value    = sched.morningStart    || '';
              document.getElementById('weekMorningEnd').value      = sched.morningEnd      || '';
              document.getElementById('weekAfternoonStart').value  = sched.afternoonStart  || '';
              document.getElementById('weekAfternoonEnd').value    = sched.afternoonEnd    || '';
            } else {
              document.getElementById('weekMorningStart').value    = '';
              document.getElementById('weekMorningEnd').value      = '';
              document.getElementById('weekAfternoonStart').value  = '';
              document.getElementById('weekAfternoonEnd').value    = '';
            }

            // Clear Bulk list & days
            document.getElementById('bulkPlaceList').innerHTML = '';
            dayNames.forEach(day => {
              document.getElementById('placeList' + day).innerHTML = '';
            });

            // Populate day places
            const daysData = sched.days || {};
            dayNames.forEach(day => {
              const placeList = document.getElementById('placeList' + day);
              const dayNode = daysData[day];
              if (dayNode && dayNode.places) {
                Object.values(dayNode.places).forEach(pl => {
                  const listItem = document.createElement('li');
                  listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                  // Preserve the unique id in a data attribute
                  listItem.dataset.id = pl.id;
                  listItem.innerHTML = `
                    <input
                      type="text"
                      class="form-control form-control-sm me-2 place-name-input"
                      value="${pl.name}"
                      required
                      aria-label="Place Name"
                      style="flex: 1; min-width: 0;"
                    >
                    <span style="display: flex; align-items: center;">
                      <button type="button" class="btn btn-sm btn-secondary edit-place-btn" aria-label="Edit Place" style="width: 30px;">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-danger remove-place-btn" aria-label="Remove Place" style="width: 30px; margin-left: 5px;">
                        &times;
                      </button>
                      <button type="button" class="btn btn-sm btn-info move-place-btn" aria-label="Move Place" style="width: 30px; margin-left: 5px;">
                        <i class="fas fa-exchange-alt"></i>
                      </button>
                    </span>
                  `;
                  listItem.dataset.lat = pl.latitude;
                  listItem.dataset.lng = pl.longitude;
                  placeList.appendChild(listItem);

                  // Remove event listener
                  listItem.querySelector('.remove-place-btn').addEventListener('click', () => {
                    placeList.removeChild(listItem);
                  });
                  // Edit event listener
                  listItem.querySelector('.edit-place-btn').addEventListener('click', () => {
                    editingPlaceLi = listItem;
                    currentDay = day;
                    openMapForEdit(pl.latitude, pl.longitude, pl.name);
                  });
                  // Move event listener with dropdown
                  listItem.querySelector('.move-place-btn').addEventListener('click', () => {
                    // Create a dropdown element with all valid days
                    const validDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                    const select = document.createElement('select');
                    select.className = "form-select form-select-sm";
                    validDays.forEach(d => {
                      const option = document.createElement('option');
                      option.value = d;
                      option.textContent = d;
                      select.appendChild(option);
                    });
                    // Set default option to current day (extracted from parent's id)
                    const currentListId = placeList.id; // e.g., "placeListThursday"
                    const currentDay = currentListId.replace('placeList','');
                    select.value = currentDay;
                    // Append the dropdown to the span containing the buttons
                    const parentSpan = listItem.querySelector('span');
                    parentSpan.appendChild(select);
                    
                    // When a new day is selected, move the list item
                    select.addEventListener('change', function() {
                      const newDay = this.value;
                      if (validDays.includes(newDay)) {
                        const destinationList = document.getElementById('placeList' + newDay);
                        if (destinationList) {
                          destinationList.appendChild(listItem);
                        } else {
                          alert("Destination day list not found.");
                        }
                      }
                      // Remove the dropdown after selection
                      parentSpan.removeChild(select);
                    });
                    // Remove the dropdown if focus is lost
                    select.addEventListener('blur', function() {
                      if (this.parentNode) {
                        this.parentNode.removeChild(this);
                      }
                    });
                    // Automatically focus the dropdown so user can change
                    select.focus();
                  });
                });
              }
            });

            document.getElementById('viewScheduleSummaryBtn').disabled = false;
          } else {
            lastLoadedSchedule = null;
            scheduleYear = '<?php echo date('Y'); ?>';
            document.getElementById('shiftSchedule').innerText = 'Unassigned';
            document.getElementById('collectionTime').innerText = 'Unassigned';

            document.getElementById('weekShiftSelect').value = 'day';
            toggleWeekTimeFields();
            document.getElementById('weekMorningStart').value = '';
            document.getElementById('weekMorningEnd').value = '';
            document.getElementById('weekAfternoonStart').value = '';
            document.getElementById('weekAfternoonEnd').value = '';

            document.getElementById('bulkPlaceList').innerHTML = '';
            dayNames.forEach(d => {
              document.getElementById('placeList' + d).innerHTML = '';
            });
            document.getElementById('viewScheduleSummaryBtn').disabled = true;
          }
        })
        .catch(err => {
          document.getElementById('detailsLoading').style.display = 'none';
          console.error('Error fetching schedule:', err);
          showModalMessage('error', 'Failed to fetch schedule data.');
        });
    }

    function openMapForEdit(lat, lng, placeName) {
      const mapModalEl = document.getElementById('mapModal');
      const mapModalInstance = bootstrap.Modal.getOrCreateInstance(mapModalEl);
      mapModalInstance.show();

      mapModalEl.addEventListener('shown.bs.modal', function handleShown() {
        if (lat && lng) {
          const coordinate = ol.proj.fromLonLat([parseFloat(lng), parseFloat(lat)]);
          olMap.getView().animate({ center: coordinate, zoom: 17 });

          if (searchLayer) {
            vectorSource.removeFeature(searchLayer);
          }
          const feature = createMarkerFeature([parseFloat(lng), parseFloat(lat)]);
          vectorSource.addFeature(feature);
          searchLayer = feature;
        }
        mapModalEl.removeEventListener('shown.bs.modal', handleShown);
      });
    }

    document.getElementById('viewScheduleSummaryBtn').addEventListener('click', showScheduleSummary);

    function showScheduleSummary() {
      const container = document.getElementById('scheduleSummaryContent');
      if (!lastLoadedSchedule) {
        container.innerHTML = '<p>No schedule found for this truck.</p>';
        return;
      }
      const s = lastLoadedSchedule;
      const displayYear = scheduleYear || '<?php echo date('Y'); ?>';

      let shiftStr = 'UNASSIGNED';
      if (s.shift === 'day') shiftStr = 'DAY SHIFT';
      else if (s.shift === 'night') shiftStr = 'NIGHT SHIFT';

      let timeStr = 'Unassigned';
      if (s.shift === 'day') {
        const hasMorningTimes    = s.morningStart || s.morningEnd;
        const hasAfternoonTimes  = s.afternoonStart || s.afternoonEnd;
        if (hasMorningTimes || hasAfternoonTimes) {
          const morningStart   = convertTo12Hour(s.morningStart);
          const morningEnd     = convertTo12Hour(s.morningEnd);
          const afternoonStart = convertTo12Hour(s.afternoonStart);
          const afternoonEnd   = convertTo12Hour(s.afternoonEnd);
          timeStr = `${morningStart} - ${morningEnd} / ${afternoonStart} - ${afternoonEnd}`;
        }
      } else if (s.shift === 'night') {
        timeStr = 'Night Shift (No specific times)';
      }
      document.getElementById('collectionTime').innerText = timeStr;

      let collectorItems = '';
      if (lastCollectors && lastCollectors.length > 0) {
        collectorItems = lastCollectors.map((c, i) => `<li>${i + 1}. ${c}</li>`).join('');
      } else {
        collectorItems = '<li>Unassigned</li>';
      }

      const daysData = s.days || {};
      let rowsHTML = '';
      dayNames.forEach(day => {
        let placeList = '<em>No places assigned</em>';
        if (daysData[day] && daysData[day].places) {
          const placeArray = Object.values(daysData[day].places);
          placeList = placeArray.map(p => p.name).join(', ');
        }
        rowsHTML += `
          <tr>
            <td style="text-align: center; vertical-align: middle;"><strong>${day.toUpperCase()}</strong></td>
            <td style="text-align: left; vertical-align: middle;">${placeList}</td>
          </tr>
        `;
      });

      const summaryHTML = `
        <div style="text-align:center; margin-bottom:1rem;">
          <h4>CENRO-SANITATION AND PUBLIC SERVICES</h4>
          <div>RESIDUAL WASTE COLLECTION SCHEDULE FOR CY <span id="editableYear" class="editable-year">${displayYear}</span></div>
        </div>

        <div style="background-color: #d0f0d0; color: #333; padding: 10px; margin-bottom: 1rem; font-size: 1.1rem; border: 1px solid #a7e0a7;">
          <strong>${shiftStr}</strong>
        </div>

        <div style="margin-bottom: 1rem;">
          <p style="margin-bottom:0;"><strong>Collection Time: </strong> ${timeStr}</p>
          <p style="margin-bottom:0;"><strong>${lastTruckName} - Plate # ${lastTruckPlate}</strong></p>
          <p style="margin-bottom:0;"><strong>Driver:</strong> ${lastDriverName}</p>
          <p style="margin-bottom:0;"><strong>Garbage Collectors:</strong></p>
          <ul style="margin-bottom:0; list-style-type: none;">
            ${collectorItems}
          </ul>
        </div>

        <hr>
        <table class="table table-bordered" style="border: 1px solid #ccc; width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="background-color: #e2ffe2;">
              <th colspan="2" style="width: 120px; border: 1px solid #ccc; text-align: center;">
                NAME OF PUROK/SUBD./SCHOOLS/BUSINESS ESTABLISHMENTS
              </th>
            </tr>
          </thead>
          <tbody>
            ${rowsHTML}
          </tbody>
        </table>
      `;
      document.getElementById('scheduleSummaryContent').innerHTML = summaryHTML;
      makeYearEditable();
    }

    function makeYearEditable() {
      const editableYearSpan = document.getElementById('editableYear');
      editableYearSpan.addEventListener('click', function () {
        if (editableYearSpan.querySelector('input')) return;
        const currentYear = editableYearSpan.innerText.trim();
        const input = document.createElement('input');
        input.type = 'number';
        input.min = 2000;
        input.max = 2100;
        input.value = currentYear;
        input.className = 'editable-year-input';
        editableYearSpan.innerHTML = '';
        editableYearSpan.appendChild(input);
        input.focus();

        input.addEventListener('blur', function () {
          saveEditedYear(input.value, editableYearSpan);
        });
        input.addEventListener('keypress', function (e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            saveEditedYear(input.value, editableYearSpan);
          }
        });
      });
    }

    function saveEditedYear(newYear, spanElement) {
      newYear = newYear.trim();
      if (!newYear || isNaN(newYear) || newYear < 2000 || newYear > 2100) {
        showModalMessage('error', 'Please enter a valid year between 2000 and 2100.');
        spanElement.innerHTML = scheduleYear;
        return;
      }
      scheduleYear = newYear;
      spanElement.innerText = newYear;
    }

    // Attach editable event listeners to aggregated summaries' years
    function attachEditableYearListeners() {
      const editableYearElements = document.querySelectorAll('#allScheduleSummaryContent .editable-year');
      editableYearElements.forEach(editableYearSpan => {
        // Remove previous event listener by cloning (optional)
        const clone = editableYearSpan.cloneNode(true);
        editableYearSpan.parentNode.replaceChild(clone, editableYearSpan);
      });
      
      document.querySelectorAll('#allScheduleSummaryContent .editable-year').forEach(editableYearSpan => {
        editableYearSpan.addEventListener('click', function () {
          if (this.querySelector('input')) return;
          const currentYear = this.innerText.trim();
          const input = document.createElement('input');
          input.type = 'number';
          input.min = 2000;
          input.max = 2100;
          input.value = currentYear;
          input.className = 'editable-year-input';
          this.innerHTML = '';
          this.appendChild(input);
          input.focus();

          input.addEventListener('blur', () => {
            saveEditedYearAll(input.value, editableYearSpan);
          });
          input.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
              e.preventDefault();
              saveEditedYearAll(input.value, editableYearSpan);
            }
          });
        });
      });
    }


    function saveEditedYearAll(newYear, spanElement) {
      newYear = newYear.trim();
      if (!newYear || isNaN(newYear) || newYear < 2000 || newYear > 2100) {
        showModalMessage('error', 'Please enter a valid year between 2000 and 2100.');
        // Reset all editable year spans to a default value if needed
        document.querySelectorAll('#allScheduleSummaryContent .editable-year').forEach(e => e.innerText = '2025');
        return;
      }
      // Update all editable-year spans within the aggregated summary container
      document.querySelectorAll('#allScheduleSummaryContent .editable-year').forEach(e => {
        e.innerText = newYear;
      });
    }


    function initializeEventListeners() {
      const selectPlaceBtn = document.getElementById('selectPlaceBtn');
      const loadingScreen = document.getElementById('loadingScreen');
      const scheduleForm = document.getElementById('scheduleForm');

      // "Select Place" => add or edit
      selectPlaceBtn.addEventListener('click', function () {
        if (selectedPlace && currentDay) {
          const placeListId = (currentDay === 'Bulk') ? 'bulkPlaceList' : 'placeList' + currentDay;
          const placeList = document.getElementById(placeListId);

          if (editingPlaceLi) {
            // Editing existing
            const duplicate = Array.from(placeList.children).some(li => {
              return (li !== editingPlaceLi &&
                li.dataset.lat === selectedPlace.lat.toString() &&
                li.dataset.lng === selectedPlace.lng.toString());
            });
            if (duplicate) {
              showModalMessage('error', 'This place has already been added.');
              return;
            }
            editingPlaceLi.querySelector('.place-name-input').value = selectedPlace.name;
            editingPlaceLi.dataset.lat = selectedPlace.lat;
            editingPlaceLi.dataset.lng = selectedPlace.lng;
            // Preserve the unique id already stored in the element
            editingPlaceLi = null;
          } else {
            // Adding new
            const existingPlaces = placeList.querySelectorAll('li');
            for (let p of existingPlaces) {
              if (
                p.dataset.lat === selectedPlace.lat.toString() &&
                p.dataset.lng === selectedPlace.lng.toString()
              ) {
                showModalMessage('error', 'This place has already been added.');
                return;
              }
            }
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            // When adding a new place from the map modal, no unique id exists yet.
            // The backend (save_schedule.php) will assign one if none is provided.
            li.dataset.id = ""; 
            li.innerHTML = `
              <input
                type="text"
                class="form-control form-control-sm me-2 place-name-input"
                value="${selectedPlace.name}"
                required
                aria-label="Place Name"
                style="flex: 1; min-width: 0;"
              >
              <span style="display: flex; align-items: center;">
                <button type="button" class="btn btn-sm btn-secondary edit-place-btn" aria-label="Edit Place" style="width: 30px;">
                  <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger remove-place-btn" aria-label="Remove Place" style="width: 30px; margin-left: 5px;">
                  &times;
                </button>
              </span>
            `;
            li.dataset.lat = selectedPlace.lat;
            li.dataset.lng = selectedPlace.lng;
            placeList.appendChild(li);

            // Remove event listener
            li.querySelector('.remove-place-btn').addEventListener('click', () => {
              placeList.removeChild(li);
            });
            // Edit event listener
            li.querySelector('.edit-place-btn').addEventListener('click', () => {
              editingPlaceLi = li;
              openMapForEdit(li.dataset.lat, li.dataset.lng, li.querySelector('.place-name-input').value);
            });
          }

          // Close map modal
          const mapModalEl = document.getElementById('mapModal');
          const mapModalInstance = bootstrap.Modal.getInstance(mapModalEl);
          mapModalInstance.hide();
        }
      });

      // "Apply Bulk Places"
      const applyBulkBtn = document.getElementById('applyBulkPlacesBtn');
      applyBulkBtn.addEventListener('click', () => {
        const bulkList = document.getElementById('bulkPlaceList');
        const bulkItems = bulkList.querySelectorAll('li');
        if (bulkItems.length === 0) {
          showModalMessage('error', 'No bulk places to copy.');
          return;
        }
        // Build list of checked days by matching HTML IDs
          const checkedDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']
            .filter(day => {
              const cb = document.getElementById(`chkBulk${day}`);
              return cb && cb.checked;
            });

          if (!checkedDays.length) {
            showModalMessage('error', 'Please select at least one day.');
            return;
          }

        checkedDays.forEach(day => {
          const dayList = document.getElementById('placeList' + day);
          bulkItems.forEach(item => {
            const lat = item.dataset.lat;
            const lng = item.dataset.lng;
            const nameValue = item.querySelector('.place-name-input').value.trim();
            let duplicateFound = false;

            dayList.querySelectorAll('li').forEach(li => {
              if (li.dataset.lat === lat && li.dataset.lng === lng) {
                duplicateFound = true;
              }
            });
            if (!duplicateFound) {
              const li = document.createElement('li');
              li.className = 'list-group-item d-flex justify-content-between align-items-center';
              // Preserve the unique id if it exists
              li.dataset.id = item.dataset.id || "";
              li.innerHTML = `
                <input
                  type="text"
                  class="form-control form-control-sm me-2 place-name-input"
                  value="${nameValue}"
                  required
                  aria-label="Place Name"
                  style="flex: 1; min-width: 0;"
                >
                <span style="display: flex; align-items: center;">
                  <button type="button" class="btn btn-sm btn-secondary edit-place-btn" aria-label="Edit Place" style="width: 30px;">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-danger remove-place-btn" aria-label="Remove Place" style="width: 30px; margin-left: 5px;">
                    &times;
                  </button>
                </span>
              `;
              li.dataset.lat = lat;
              li.dataset.lng = lng;
              dayList.appendChild(li);

              li.querySelector('.remove-place-btn').addEventListener('click', () => {
                dayList.removeChild(li);
              });
              li.querySelector('.edit-place-btn').addEventListener('click', () => {
                editingPlaceLi = li;
                currentDay = day;
                openMapForEdit(lat, lng, nameValue);
              });
            }
          });
        });

        const bulkModalEl = document.getElementById('bulkAssignModal');
        const bulkModal = bootstrap.Modal.getInstance(bulkModalEl);
        if (bulkModal) bulkModal.hide();
        
        showModalMessage('success', 'Bulk places successfully applied to the selected days!');
      });

      // after you add place in bulk assign
        const bulkModalEl = document.getElementById('bulkAssignModal');
        bulkModalEl.addEventListener('hidden.bs.modal', () => {
          // once Bulk Assign closes, re-open the Schedule modal
          const scheduleModalEl = document.getElementById('scheduleModal');
          const scheduleModal = bootstrap.Modal.getOrCreateInstance(scheduleModalEl);
          scheduleModal.show();
      });

      // "Add Place" for each day
      const addPlaceButtons = document.querySelectorAll('.add-place-btn');
      addPlaceButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          const day = btn.getAttribute('data-day');
          currentDay = day;
          editingPlaceLi = null;
          const mapModalEl = document.getElementById('mapModal');
          const mapModalInstance = bootstrap.Modal.getOrCreateInstance(mapModalEl);
          mapModalInstance.show();
        });
      });

      // Submit schedule form
      scheduleForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!selectedTruckKey) {
          showModalMessage('error', 'Please select a truck first.');
          return;
        }
        const shiftValue = document.getElementById('weekShiftSelect').value;
        let morningStart    = '';
        let morningEnd      = '';
        let afternoonStart  = '';
        let afternoonEnd    = '';

        if (shiftValue === 'day') {
          morningStart    = document.getElementById('weekMorningStart').value;
          morningEnd      = document.getElementById('weekMorningEnd').value;
          afternoonStart  = document.getElementById('weekAfternoonStart').value;
          afternoonEnd    = document.getElementById('weekAfternoonEnd').value;
        }

        const scheduleEntries = [];
        try {
          dayNames.forEach(d => {
            const placeList = document.getElementById('placeList' + d);
            const items = placeList.querySelectorAll('li');
            if (items.length > 0) {
              const places = [];
              items.forEach(item => {
                const nameInput = item.querySelector('.place-name-input');
                const placeName = nameInput.value.trim();
                if (!placeName) {
                  throw new Error(`Place name for ${d} cannot be empty.`);
                }
                const lat = parseFloat(item.dataset.lat);
                const lng = parseFloat(item.dataset.lng);
                // Include the unique id if available
                const id = item.dataset.id || "";
                places.push({ id: id, name: placeName, latitude: lat, longitude: lng });
              });
              scheduleEntries.push({ week: d, places });
            }
          });
          if (scheduleEntries.length === 0) {
            showModalMessage('error', 'Please add at least one place (in any day).');
            return;
          }
        } catch (error) {
          console.error('Form error:', error);
          showModalMessage('error', error.message);
          return;
        }

        const payload = {
          truckKey: selectedTruckKey,
          shift: shiftValue,
          morningStart,
          morningEnd,
          afternoonStart,
          afternoonEnd,
          schedules: scheduleEntries
        };

        const scheduleModalEl = document.getElementById('scheduleModal');
        const scheduleModalInstance = bootstrap.Modal.getInstance(scheduleModalEl);
        scheduleModalInstance.hide();

        loadingScreen.style.display = 'flex';

        fetch('../backend/save_schedule.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        })
          .then(resp => resp.json())
          .then(data => {
            loadingScreen.style.display = 'none';
            if (data.status === 'success') {
              showModalMessage('success', 'Schedule saved successfully.');
              loadScheduleData(selectedTruckKey);
            } else {
              showModalMessage('error', data.message || 'An error occurred while saving.');
            }
          })
          .catch(error => {
            console.error('Error saving schedule:', error);
            loadingScreen.style.display = 'none';
            showModalMessage('error', 'An error occurred while saving.');
          });
      });

      const downloadPdfBtn = document.getElementById('downloadPdfBtn');
      if (downloadPdfBtn) {
        downloadPdfBtn.addEventListener('click', downloadScheduleAsPDF);
      }
    }

    function downloadScheduleAsPDF() {
      const summaryContent = document.getElementById('scheduleSummaryContent');
      if (!summaryContent) {
        showModalMessage('error', 'No schedule summary available to download.');
        return;
      }
      const opt = {
        margin:       10,
        filename:     'Schedule_Summary.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };
      html2pdf().set(opt).from(summaryContent).save();
    }

    document.getElementById('scheduleModal').addEventListener('hidden.bs.modal', function () {
      if (selectedTruckKey) {
        loadScheduleData(selectedTruckKey);
      }
    });
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
    // Function to fetch and aggregate all truck schedules with loading indicator
    async function showAllScheduleSummary() {
  // Show the full-page loading overlay
  const allLoadingScreen = document.getElementById('allLoadingScreen');
  allLoadingScreen.style.display = 'flex';

  const trucks = document.querySelectorAll('.truck-card');
  if (trucks.length === 0) {
    showModalMessage('error', 'No trucks available.');
    allLoadingScreen.style.display = 'none';
    return;
  }

  const allSummaryContainer = document.getElementById('allScheduleSummaryContent');
  const loadingEl = document.getElementById('allSummaryLoading');
  loadingEl.style.display = 'block';
  allSummaryContainer.innerHTML = '';

  const allSummaries = [];
  const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

  // Fetch schedules for each truck
  const fetchPromises = Array.from(trucks).map(async (card) => {
    const truckKey = card.dataset.key;
    try {
      const resp = await fetch(`../backend/fetch_schedule.php?truckKey=${encodeURIComponent(truckKey)}`);
      const data = await resp.json();
      if (data.status === 'success' && data.schedules) {
        const s = data.schedules;
        // Use schedule year if provided; otherwise, default to current year
        const displayYear = s.year || '<?php echo date("Y"); ?>';

        // Determine shift and collection time
        let shiftStr = 'UNASSIGNED';
        if (s.shift === 'day') shiftStr = 'DAY SHIFT';
        else if (s.shift === 'night') shiftStr = 'NIGHT SHIFT';

        let timeStr = 'Unassigned';
        if (s.shift === 'day') {
          const hasMorningTimes = s.morningStart || s.morningEnd;
          const hasAfternoonTimes = s.afternoonStart || s.afternoonEnd;
          if (hasMorningTimes || hasAfternoonTimes) {
            const morningStart = convertTo12Hour(s.morningStart);
            const morningEnd = convertTo12Hour(s.morningEnd);
            const afternoonStart = convertTo12Hour(s.afternoonStart);
            const afternoonEnd = convertTo12Hour(s.afternoonEnd);
            timeStr = `${morningStart} - ${morningEnd} / ${afternoonStart} - ${afternoonEnd}`;
          }
        } else if (s.shift === 'night') {
          timeStr = 'Night Shift (No specific times)';
        }

        // Retrieve truck details from the truck card's data attributes.
        const lastTruckName = card.dataset.truckName || 'Unknown Truck';
        const lastTruckPlate = card.dataset.plateNumber || 'N/A';
        const lastDriverName = card.dataset.driver || 'Unassigned';
        let collectorItems = '';
        if (card.dataset.collectors) {
          const collectorsArray = card.dataset.collectors.split(',');
          if (collectorsArray.length > 0) {
            collectorItems = collectorsArray
              .map((c, i) => `<li>${i + 1}. ${c.trim()}</li>`)
              .join('');
          } else {
            collectorItems = '<li>Unassigned</li>';
          }
        } else {
          collectorItems = '<li>Unassigned</li>';
        }

        // Build rows for the days table
        let rowsHTML = '';
        dayNames.forEach(day => {
          let placeList = '<em>No places assigned</em>';
          if (s.days && s.days[day] && s.days[day].places) {
            const placeArray = Object.values(s.days[day].places);
            placeList = placeArray.map(p => p.name).join(', ');
          }
          rowsHTML += `
            <tr>
              <td style="text-align: center; vertical-align: middle;"><strong>${day.toUpperCase()}</strong></td>
              <td style="text-align: left; vertical-align: middle;">${placeList}</td>
            </tr>
          `;
        });

        // Build the summary HTML using the same style as showScheduleSummary()
        const summaryHTML = `
  <div class="truck-summary" style="margin-bottom:2rem; padding-bottom:1rem; border-bottom:1px solid #ccc;">
    <div style="text-align:center; margin-bottom:1rem;">
      <h4>CENRO-SANITATION AND PUBLIC SERVICES</h4>
      <div>RESIDUAL WASTE COLLECTION SCHEDULE FOR CY <span class="editable-year">${displayYear}</span></div>
    </div>

    <div style="background-color: #d0f0d0; color: #333; padding: 10px; margin-bottom: 1rem; font-size: 1.1rem; border: 1px solid #a7e0a7;">
      <strong>${shiftStr}</strong>
    </div>

    <div style="margin-bottom: 1rem;">
      <p style="margin-bottom:0;"><strong>Collection Time: </strong> ${timeStr}</p>
      <p style="margin-bottom:0;"><strong>${lastTruckName} - Plate # ${lastTruckPlate}</strong></p>
      <p style="margin-bottom:0;"><strong>Driver:</strong> ${lastDriverName}</p>
      <p style="margin-bottom:0;"><strong>Garbage Collectors:</strong></p>
      <ul style="margin-bottom:0; list-style-type: none;">
        ${collectorItems}
      </ul>
    </div>

    <hr>
    <table class="table table-bordered" style="border: 1px solid #ccc; width: 100%; border-collapse: collapse;">
      <thead>
        <tr style="background-color: #e2ffe2;">
          <th colspan="2" style="width: 120px; border: 1px solid #ccc; text-align: center;">
            NAME OF PUROK/SUBD./SCHOOLS/BUSINESS ESTABLISHMENTS
          </th>
        </tr>
      </thead>
      <tbody>
        ${rowsHTML}
      </tbody>
    </table>
  </div>
`;
allSummaries.push(summaryHTML);
      }
    } catch (err) {
      console.error(`Error fetching schedule for truckKey ${truckKey}:`, err);
    }
  });

  await Promise.all(fetchPromises);

  // Hide the loading indicators
  loadingEl.style.display = 'none';
  allLoadingScreen.style.display = 'none';

  // Display the summaries or a message if none are available
  if (allSummaries.length === 0) {
    allSummaryContainer.innerHTML = '<p class="text-center">No schedule summaries available.</p>';
  } else {
    allSummaryContainer.innerHTML = allSummaries.join('');
    attachEditableYearListeners();
  }

  // Open the "View All Schedule Summary" modal
  const allModalEl = document.getElementById('allScheduleSummaryModal');
  const allModalInstance = new bootstrap.Modal(allModalEl);
  allModalInstance.show();
}

    
    // Attach the event listener to the "View All Schedule Summary" button
    document.getElementById('viewAllScheduleSummaryBtn').addEventListener('click', showAllScheduleSummary);
    
    
    // Function to download all schedules as a PDF using html2pdf.js
    document.getElementById('downloadAllPdfBtn').addEventListener('click', function() {
      const summaryContent = document.getElementById('allScheduleSummaryContent');
      if (!summaryContent) {
        showModalMessage('error', 'No schedule summary available to download.');
        return;
      }
      const opt = {
        margin:       10,
        filename:     'All_Schedules_Summary.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };
      html2pdf().set(opt).from(summaryContent).save();
    });
  </script>
</body>
</html>
