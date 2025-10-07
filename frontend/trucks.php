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
    <title>Hakot | Trucks</title>
    <link rel="icon" type="image/x-icon" href="img/hakot-icon.png">
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
    <style>
      body {
        font-family: "Poppins", sans-serif;
        background-color: #f9f9f9;
        margin: 0;
      }
      /* Loading Screen Styles */
      #loadingScreen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.3s ease-in-out;
      }
      #loadingScreen.hidden {
        opacity: 0;
        pointer-events: none;
      }
      /* Offcanvas Sidebar */
      .offcanvas.offcanvas-start {
        width: 250px;
      }
      /* Main Content Container */
      .content {
        padding-top: 85px;
        padding-left: 20px;
        padding-right: 20px;
        max-width: 100%;
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
      /* Search Bar Container */
      .search-bar-container {
        display: flex;
        align-items: center;
        max-width: 350px;
        width: 100%;
        position: relative;
      }
      .search-bar {
        flex: 1;
        height: 40px;
        padding-right: 10px;
      }
      .sort-dropdown-container {
        position: relative;
        margin-left: 10px;
        display: flex;
        align-items: center;
      }
      .sort-icon {
        font-size: 1.2rem;
        color: #333;
        cursor: pointer;
        padding: 5px;
      }
      .sort-dropdown-container .dropdown-menu {
        top: 100%;
        left: 0;
      }
      @media (max-width: 576px) {
        .search-bar-container {
          max-width: 100%;
        }
      }
      .search-container {
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 500px;
        width: 100%;
      }
      .btn-create {
        background-color: #26a541;
        color: #fff;
      }
      .btn-create:hover {
        background-color: #21933d;
      }
      .truck-card {
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: all 0.3s ease-in-out;
        position: relative;
        height: 100%;
        display: flex;
        flex-direction: column;
      }
      .truck-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      }
      .card-icons {
        display: flex;
        gap: 8px;
      }
      .card-icons i {
        cursor: pointer;
        font-size: 1rem;
      }
      .truck-card img {
        width: 100%;
        height: auto;
        object-fit: cover;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
      }
      .truck-card .card-body {
        padding: 15px;
        flex-grow: 1;
      }
      .truck-card .card-title {
        font-size: 1rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      .truck-card .card-text {
        font-size: 0.875rem;
        color: #777;
      }
      .modal-content {
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      }
      .form-select {
        height: 40px;
      }
      .modal-header {
        border-bottom: none;
      }
      .modal-footer {
        border-top: none;
      }
      .modal-title {
        color: #198754;
        font-weight: bold;
      }
      .upload-image {
        text-align: center;
        margin-bottom: 20px;
      }
      .upload-image img {
        max-width: 150px;
        margin-bottom: 15px;
      }
      #garbageCollectorContainer .d-flex.align-items-center.mb-2 {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
      }
      #garbageCollectorContainer .d-flex.align-items-center.mb-2 input.form-control {
        height: 40px;
      }
      #garbageCollectorContainer .d-flex.align-items-center.mb-2 .btn-danger {
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 10px;
      }
      #garbageCollectorContainer .d-flex.align-items-center.mb-2 .btn-danger i {
        font-size: 1rem;
      }
      .fas {
        font-size: 1rem;
        line-height: 1;
      }
      .dropdown-item:hover {
        background-color: #32CD32;
        color: #fff;
      }
      .is-invalid {
        border-color: #dc3545 !important;
      }
      #truckCards .truck-card-item {
        display: flex;
        flex-direction: column;
      }
      .truck-card img {
        height: 150px;
        object-fit: cover;
      }
      @media (min-width: 1200px) {
        .modal-lg {
          max-width: 800px;
        }
      }
      .modal-body form .row {
        flex-wrap: wrap;
      }
      .modal-body form .col-md-4,
      .modal-body form .col-md-8 {
      }
      @media (min-width: 768px) {
        .modal-body form .col-md-4,
        .modal-body form .col-md-8 {
        }
      }
      /* inactive tabs = black text */
      .nav-tabs .nav-link {
        color: #000 !important;
        background-color: transparent;
      }

      /* active tab = white text on green bg */
      .nav-tabs .nav-link.active {
        color: #fff !important;
        background-color: #32CD32 !important;
        border-color: #32CD32 #32CD32 #fff !important;
      }

      /* hover on any tab */
      .nav-tabs .nav-link:hover {
        color: #fff !important;
        background-color: rgba(25,135,84,.1) !important;
        border-color: transparent !important;
      }

      /* underline bar at bottom of the tabs */
      .nav-tabs {
        border-bottom-color: #32CD32 !important;
      }
    </style>
  </head>
  <body>

    <!-- Loading Screen -->
    <div id="loadingScreen" class="hidden">
      <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <!-- Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
      <div class="offcanvas-header d-lg-none">
        <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Menu</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body p-0">
        <img src="img/hakot-new.png" alt="HAKOT Logo" style="height:120px; width:120px; margin-bottom: 10px;">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="tracker.php"><i class="fas fa-map-marker-alt"></i> Truck Tracker</a>
        <a href="schedule.php"><i class="fas fa-calendar-alt"></i> Truck Schedule</a>
        <a href="user_announcement.php"><i class="fa-solid fa-bullhorn"></i> User Announcement</a>
        <!-- <a href="routes.php"><i class="fas fa-route"></i> Routes Optimization</a> -->
        <div class="bottom-links">
          <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
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
          <img id="profileImg" src="img/default-profile.jpg" width="35" height="35" style="border-radius:50%; cursor:pointer;" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          <div aria-labelledby="profileImg" id="profileDropdown"></div>
        </div>
        
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container-fluid">
         <!-- Entity Tabs -->
  <ul class="nav nav-tabs flex-wrap mb-3" id="entityTabs">
    <li class="nav-item">
      <button class="nav-link" data-entity="operators">OPERATORS</button>
    </li>
    <li class="nav-item">
      <button class="nav-link active" data-entity="trucks">TRUCKS</button>
    </li>
  </ul>
        <!-- Search & Register Section -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center flex-wrap mb-4">
          <div class="search-bar-container">
            <input type="text" class="form-control search-bar" id="searchBar" placeholder="Search the trucks you want" />
            <div class="sort-dropdown-container">
              <i class="fas fa-sort sort-icon" id="sortIcon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Sort Options"></i>
              <ul class="dropdown-menu" aria-labelledby="sortIcon">
                <li><a class="dropdown-item sort-option" href="#" data-sort="vehicleName">Vehicle</a></li>
                <li><a class="dropdown-item sort-option" href="#" data-sort="plateNumber">Plate Number</a></li>
                <li><a class="dropdown-item sort-option" href="#" data-sort="vehicleDriver">Driver</a></li>
              </ul>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3">
            <button class="btn btn-create" data-bs-toggle="modal" data-bs-target="#createTruckModal" onclick="prepareCreateTruck()">
              <i class="bi bi-truck"></i> Register Truck
            </button>
          </div>
        </div>

        <!-- Truck Cards Container -->
        <div class="row mt-4" id="truckCards">
          <!-- Truck cards will be dynamically loaded here -->
        </div>
      </div>
    </div>
    <!-- End .content -->

    <!-- Register/Edit Truck Modal -->
    <div class="modal fade" id="createTruckModal" tabindex="-1" aria-labelledby="createTruckModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <!-- Header -->
          <div class="modal-header">
            <h5 class="modal-title" id="createTruckModalLabel">Register Truck</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <!-- Body -->
          <div class="modal-body">
            <form id="truckForm">
              <!-- Hidden input for updates -->
              <input type="hidden" id="truckId" name="truckId" />
              <div class="row">
                <div class="col-md-4 upload-image">
                  <label for="vehicleImage" class="form-label">Upload Vehicle Image</label>
                  <input type="file" name="vehicleImage" id="vehicleImage" class="form-control" accept="image/*" />
                </div>
                <div class="col-md-8">
                  <!-- Vehicle Name -->
                  <div class="mb-3">
                    <label for="vehicleName" class="form-label">Vehicle Name</label>
                    <input type="text" class="form-control" id="vehicleName" name="vehicleName" placeholder="Enter the vehicle name" required />
                  </div>
                  <!-- Plate Number -->
                  <div class="mb-3">
                    <label for="plateNumber" class="form-label">Plate Number</label>
                    <input type="text" class="form-control" id="plateNumber" name="plateNumber" placeholder="Enter vehicle plate number" required />
                  </div>
                  <!-- Truck Type -->
                  <div class="mb-3">
                    <label for="truckType" class="form-label">Truck Type</label>
                    <select class="form-select" id="truckType" name="truckType" required>
                      <option value="Garbage Truck">Garbage Truck</option>
                      <option value="Sewage Truck">Sewage Truck</option>
                    </select>
                  </div>
                  <!-- Vehicle Driver (dropdown) -->
                  <div class="mb-3">
                    <label for="vehicleDriver" class="form-label">Vehicle Driver</label>
                    <select class="form-select" id="vehicleDriver" name="vehicleDriver" required>
                      <!-- Drivers will be populated via JS -->
                    </select>
                  </div>
                  <!-- ADDED: KM/L -->
                  <div class="mb-3">
                    <label for="kmPerLiter" class="form-label">Fuel Consumption (Km/L)</label>
                    <input 
                      type="number" 
                      class="form-control" 
                      id="kmPerLiter" 
                      name="kmPerLiter" 
                      placeholder="Enter Km/L" 
                      min="0" 
                      step="0.1" 
                    />
                  </div>
                  <!-- Garbage Collectors -->
                  <div class="mb-3">
                    <label class="form-label">Garbage Collectors</label>
                    <small class="text-muted d-block mb-1">Full Name (First Name, Lastname)</small>
                    <div id="garbageCollectorContainer"></div>
                    <button type="button" class="btn btn-outline-primary mt-2" id="addCollectorBtn">
                      <i class="fas fa-plus"></i> Add Collector
                    </button>
                  </div>
                  <!-- Modal Footer (Save/Cancel) -->
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                      <i class="bi bi-x-square-fill"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Save</button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this truck?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="bi bi-x-square-fill"></i> Cancel
            </button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
              <i class="bi bi-trash3-fill"></i> Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Admin Password Modal (for delete confirmation when admin) -->
    <div class="modal fade" id="adminPasswordModal" tabindex="-1" aria-labelledby="adminPasswordModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="adminPasswordModalLabel">Admin Confirmation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Please enter your admin password to confirm deletion:</p>
            <input type="password" id="adminPasswordInput" class="form-control" placeholder="Admin Password">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-square-fill"></i> Cancel</button>
            <button type="button" class="btn btn-success" id="adminPasswordConfirmBtn"><i class="bi bi-check-square-fill"></i> Confirm</button>
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

    <!-- Bootstrap & JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script>
       // wire up our “Entity Tabs” buttons to navigate
      document.querySelectorAll('#entityTabs button').forEach(btn=>{
        btn.addEventListener('click', () => {
          // remove .active from all, add to the one just clicked
          document.querySelectorAll('#entityTabs .nav-link').forEach(n=>n.classList.remove('active'));
          btn.classList.add('active');

          // do the navigation
          const which = btn.dataset.entity;
          if (which === 'operators') {
            window.location.href = 'settings.php';
          } else if (which === 'trucks') {
            window.location.href = 'trucks.php';
          }
        });
      });
      // Initial call to update username and profile image from the server
      fetchUserData();
      let allDrivers = [];
      let allTrucks = {};
      let collectorCount = 0;
      const maxCollectors = 6;
      let selectedTruckId = null; // for delete confirmation
      let currentSort = { key: 'vehicleName', order: 'asc' }; // default sort

      // ************************
      // *** RENDER TRUCKS ***
      // ************************
      function renderTrucks(trucks) {
        const trucksContainer = document.getElementById('truckCards');
        trucksContainer.innerHTML = '';
        const sortedTrucks = sortTrucks(trucks, currentSort.key, currentSort.order);
        let cardsHTML = '';
        for (const [key, truck] of Object.entries(sortedTrucks)) {
          let collectorsText = '';
          if (truck.garbageCollectors && Array.isArray(truck.garbageCollectors)) {
            collectorsText = truck.garbageCollectors.join(', ');
          }
          cardsHTML += `
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4 truck-card-item">
              <div class="truck-card">
                <img src="${truck.imageUrl}" alt="${truck.vehicleName}" class="img-fluid" loading="lazy">
                <div class="card-body">
                  <div class="card-title">
                    ${truck.vehicleName}
                    <div class="card-icons">
                      <i class="fas fa-edit text-primary"
                        data-truck="${encodeURIComponent(JSON.stringify({ id: key, ...truck }))}"
                        onclick="editTruck(this)"></i>
                      <i class="fas fa-trash-alt text-danger"
                        data-id="${key}"
                        onclick="showDeleteModal(this)"></i>
                    </div>
                  </div>
                  <p class="card-text">Driver: ${truck.vehicleDriver}</p>
                  <p class="card-text">Plate Number: ${truck.plateNumber}</p>
                  <p class="card-text">Collectors: ${collectorsText}</p>
                </div>
              </div>
            </div>`;
        }
        trucksContainer.innerHTML = cardsHTML;
      }

      // ************************
      // *** LOADING SCREEN ***
      // ************************
      function showLoadingScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        loadingScreen.classList.remove('hidden');
        loadingScreen.style.display = 'flex';
      }
      function hideLoadingScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        loadingScreen.classList.add('hidden');
        setTimeout(() => {
          loadingScreen.style.display = 'none';
        }, 300);
      }

      // ************************
      // *** MODAL HELPERS ***
      // ************************
      function showSuccessModal(message) {
        const modalTitle = document.getElementById('errorModalLabel');
        const modalMessage = document.getElementById('errorMessage');
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        const closeButton = document.querySelector('#errorModal .btn-close');
        const newCloseButton = closeButton.cloneNode(true);
        closeButton.parentNode.replaceChild(newCloseButton, closeButton);
        modalTitle.textContent = 'Message';
        modalTitle.classList.remove('text-danger');
        modalTitle.classList.add('text-success');
        modalMessage.textContent = message;
        newCloseButton.addEventListener('click', () => {
          loadTrucks();
          window.location.reload();
        });
        errorModal.show();
      }
      function showErrorModal(message) {
        const modalTitle = document.getElementById('errorModalLabel');
        const modalMessage = document.getElementById('errorMessage');
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        modalTitle.textContent = 'Error';
        modalTitle.classList.remove('text-success');
        modalTitle.classList.add('text-danger');
        modalMessage.textContent = message;
        errorModal.show();
      }

      // ========== LOAD DRIVERS ==========
      async function loadDrivers() {
        try {
          const driverResponse = await fetch('../backend/fetch_drivers.php', { method: 'GET' });
          const driverResult = await driverResponse.json();
          if (driverResult.status === 'success') {
            allDrivers = driverResult.data;
            populateDriverSelect();
            console.log('Fetched drivers from server.');
          } else {
            console.error(driverResult.message);
            showErrorModal(driverResult.message);
          }
        } catch (err) {
          console.error('Error fetching drivers:', err);
          showErrorModal('An error occurred while fetching drivers.');
        }
      }
      // UPDATED FUNCTION: Populate the vehicle driver dropdown
      function populateDriverSelect() {
        const driverSelect = document.getElementById('vehicleDriver');
        driverSelect.innerHTML = '';
        const unassignedOption = document.createElement('option');
        unassignedOption.value = 'Unassigned';
        unassignedOption.textContent = 'Unassigned';
        driverSelect.appendChild(unassignedOption);
        // Check if we are editing a truck so we can allow the current assignment.
        const currentTruckId = document.getElementById('truckId').value;
        let currentTruckDriver = '';
        if (currentTruckId && allTrucks[currentTruckId]) {
          currentTruckDriver = allTrucks[currentTruckId].vehicleDriver;
        }
        for (const [key, driver] of Object.entries(allDrivers)) {
          const option = document.createElement('option');
          option.value = driver.fullName;
          option.textContent = driver.fullName;
          // Check if driver is already assigned in any truck.
          let assigned = false;
          for (const [truckKey, truck] of Object.entries(allTrucks)) {
            if (truck.vehicleDriver === driver.fullName) {
              assigned = true;
              break;
            }
          }
          // If assigned and not the driver of the current truck in edit mode, disable the option.
          if (assigned && driver.fullName !== currentTruckDriver) {
            option.disabled = true;
            option.style.color = 'gray';
          }
          driverSelect.appendChild(option);
        }
      }

      // ========== LOAD TRUCKS ==========
      async function loadTrucks() {
        try {
          const truckResponse = await fetch('../backend/fetch_trucks.php', { method: 'GET' });
          const truckResult = await truckResponse.json();
          if (truckResult.status === 'success') {
            allTrucks = truckResult.data;
            renderTrucks(allTrucks);
            console.log('Fetched trucks from server.');
          } else {
            console.error(truckResult.message);
            showErrorModal(truckResult.message);
          }
        } catch (err) {
          console.error('Error fetching trucks:', err);
          showErrorModal('An error occurred while fetching trucks.');
        }
      }

      // ========== SORT TRUCKS ==========
      function sortTrucks(trucks, key, order) {
        const sorted = { ...trucks };
        const entries = Object.entries(sorted);
        entries.sort((a, b) => {
          const aValue = a[1][key] ? a[1][key].toLowerCase() : '';
          const bValue = b[1][key] ? b[1][key].toLowerCase() : '';
          if (aValue < bValue) return order === 'asc' ? -1 : 1;
          if (aValue > bValue) return order === 'asc' ? 1 : -1;
          return 0;
        });
        return Object.fromEntries(entries);
      }

      // ========== INITIALIZE SEARCH ==========
      function initializeSearch() {
        const searchBar = document.getElementById('searchBar');
        searchBar.addEventListener('input', debounce(handleSearch, 300));
        function handleSearch() {
          const query = searchBar.value.toLowerCase();
          const filteredTrucks = Object.entries(allTrucks).filter(([key, truck]) => {
            return (
              truck.vehicleName.toLowerCase().includes(query) ||
              truck.plateNumber.toLowerCase().includes(query) ||
              truck.vehicleDriver.toLowerCase().includes(query) ||
              (truck.garbageCollectors && truck.garbageCollectors.join(' ').toLowerCase().includes(query))
            );
          });
          const filteredTrucksObj = Object.fromEntries(filteredTrucks);
          renderTrucks(filteredTrucksObj);
        }
      }

      // ========== INITIALIZE SORT ==========
      function initializeSort() {
        const sortOptions = document.querySelectorAll('.sort-option');
        sortOptions.forEach(option => {
          option.addEventListener('click', (e) => {
            e.preventDefault();
            const sortKey = option.getAttribute('data-sort');
            if (currentSort.key === sortKey) {
              currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
            } else {
              currentSort.key = sortKey;
              currentSort.order = 'asc';
            }
            renderTrucks(allTrucks);
          });
        });
      }

      // ========== PREP CREATE ==========
      function prepareCreateTruck() {
        document.getElementById('truckId').value = '';
        document.getElementById('createTruckModalLabel').textContent = 'Register Truck';
        document.getElementById('vehicleName').value = '';
        document.getElementById('plateNumber').value = '';
        document.getElementById('vehicleDriver').value = 'Unassigned';
        document.getElementById('vehicleImage').value = '';
        // ADDED: Clear Km/L on create
        document.getElementById('kmPerLiter').value = '';

        const container = document.getElementById('garbageCollectorContainer');
        container.innerHTML = '';
        collectorCount = 0;
        addCollectorField();
        populateDriverSelect();
      }

      // ========== EDIT TRUCK ==========
      function editTruck(icon) {
        const encodedData = icon.getAttribute('data-truck');
        const jsonString  = decodeURIComponent(encodedData);
        const truckData   = JSON.parse(jsonString);

        document.getElementById('truckId').value = truckData.id;
        document.getElementById('createTruckModalLabel').textContent = 'Edit Truck';
        document.getElementById('vehicleName').value = truckData.vehicleName || '';
        document.getElementById('plateNumber').value = truckData.plateNumber || '';
        document.getElementById('truckType').value = truckData.truckType || 'Garbage Truck';
        document.getElementById('vehicleDriver').value = truckData.vehicleDriver || 'Unassigned';

        // ADDED: Set Km/L if present
        document.getElementById('kmPerLiter').value = truckData.kmPerLiter || '';

        const container = document.getElementById('garbageCollectorContainer');
        container.innerHTML = '';
        collectorCount = 0;

        if (truckData.garbageCollectors && Array.isArray(truckData.garbageCollectors)) {
          truckData.garbageCollectors.forEach(col => addCollectorField(col));
        } else {
          addCollectorField();
        }

        const createTruckModal = new bootstrap.Modal(document.getElementById('createTruckModal'));
        createTruckModal.show();
        populateDriverSelect();
      }

      // ========== SHOW DELETE MODAL ==========
      function showDeleteModal(icon) {
        selectedTruckId = icon.getAttribute('data-id') || null;
        const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        deleteModal.show();
      }

      // ========== ADMIN PASSWORD PROMPT FUNCTION ==========
      function promptAdminPassword() {
        return new Promise((resolve, reject) => {
          let adminModalEl = document.getElementById('adminPasswordModal');
          if (!adminModalEl) {
            const modalHtml = `
              <div class="modal fade" id="adminPasswordModal" tabindex="-1" aria-labelledby="adminPasswordModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="adminPasswordModalLabel">Admin Confirmation</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <p>Please enter your admin password to confirm deletion:</p>
                      <input type="password" id="adminPasswordInput" class="form-control" placeholder="Admin Password">
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="button" class="btn btn-primary" id="adminPasswordConfirmBtn">Confirm</button>
                    </div>
                  </div>
                </div>
              </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            adminModalEl = document.getElementById('adminPasswordModal');
          }
          const adminModal = new bootstrap.Modal(adminModalEl);
          const confirmBtn = adminModalEl.querySelector('#adminPasswordConfirmBtn');
          const passwordInput = adminModalEl.querySelector('#adminPasswordInput');
          passwordInput.value = '';
          confirmBtn.onclick = () => {
            adminModal.hide();
            resolve(passwordInput.value);
          };
          adminModal.show();
        });
      }

      // ========== DELETE TRUCK WITH ADMIN VERIFICATION ==========
      document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
        if (!selectedTruckId) return;
        const deleteModalEl = document.getElementById('confirmDeleteModal');
        const deleteModal = bootstrap.Modal.getInstance(deleteModalEl) || new bootstrap.Modal(deleteModalEl);
        deleteModal.hide();

        // Always prompt for admin password regardless of the user's position.
        const adminPassword = await promptAdminPassword();
        try {
          const verifyResponse = await fetch('../backend/verify_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password: adminPassword })
          });
          const verifyResult = await verifyResponse.json();
          if (verifyResult.status !== 'success') {
            showErrorModal('Invalid admin password.');
            return;
          }
        } catch (error) {
          console.error('Error verifying admin password:', error);
          showErrorModal('Error verifying admin password.');
          return;
        }

        showLoadingScreen();
        try {
          const response = await fetch('../backend/truck_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ truckId: selectedTruckId })
          });
          const result = await response.json();
          if (result.status === 'success') {
            showSuccessModal('Truck deleted successfully!');
          } else {
            showErrorModal(result.message);
          }
        } catch (err) {
          console.error('Error deleting truck:', err);
          showErrorModal('An error occurred while deleting the truck.');
        } finally {
          hideLoadingScreen();
        }
      });

      // ========== GARBAGE COLLECTORS ==========
      const addCollectorBtn = document.getElementById('addCollectorBtn');
      addCollectorBtn.addEventListener('click', () => {
        if (collectorCount < maxCollectors) {
          addCollectorField();
        }
      });
      function addCollectorField(defaultValue = '') {
        const container = document.getElementById('garbageCollectorContainer');
        collectorCount++;
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center mb-2';
        
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'garbageCollectors[]';
        input.value = defaultValue;
        input.className = 'form-control me-2';
        input.placeholder = 'Full Name (First Name, Lastname)';
        
        // Prevent numeric input in this field.
        input.addEventListener('keypress', function(e) {
          const char = String.fromCharCode(e.which);
          if (/\d/.test(char)) {
            e.preventDefault();
          }
        });

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-danger';
        removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
        removeBtn.addEventListener('click', () => {
          if (collectorCount > 1) {
            row.remove();
            collectorCount--;
          }
        });
        
        row.appendChild(input);
        row.appendChild(removeBtn);
        container.appendChild(row);
      }

      // ========== FORM SUBMISSION (CREATE/EDIT) ==========
      const form = document.getElementById('truckForm');
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        showLoadingScreen();
        const collectorInputs = document.querySelectorAll('input[name="garbageCollectors[]"]');
        collectorInputs.forEach(input => {
          if (input.value.trim() === '') {
            input.value = 'Unassigned';
          }
        });
        const formData = new FormData(form);
        const truckId = formData.get('truckId');
        let endpointUrl = '../backend/truck_register.php';
        if (truckId) {
          endpointUrl = '../backend/truck_update.php';
        }
        try {
          const response = await fetch(endpointUrl, {
            method: 'POST',
            body: formData,
          });
          const result = await response.json();
          if (result.status === 'success') {
            const createTruckModalEl = document.getElementById('createTruckModal');
            const createTruckModalInstance = bootstrap.Modal.getInstance(createTruckModalEl);
            if (createTruckModalInstance) {
              createTruckModalInstance.hide();
            } else {
              const newModalInstance = new bootstrap.Modal(createTruckModalEl);
              newModalInstance.hide();
            }
            showSuccessModal(result.message);
          } else {
            showErrorModal(result.message);
            highlightInvalidFields();
          }
        } catch (err) {
          console.error('Error:', err);
          showErrorModal('An unexpected error occurred.');
        } finally {
          hideLoadingScreen();
        }
      });

      // ***********************
      // ** Highlight Fields **
      // ***********************
      function highlightInvalidFields() {
        const fields = ['vehicleName', 'plateNumber', 'vehicleDriver'];
        fields.forEach(f => {
          const input = document.getElementById(f);
          if (input && !input.value.trim()) {
            input.classList.add('is-invalid');
          }
        });
      }
      function clearInvalidFields() {
        const fields = ['vehicleName', 'plateNumber', 'vehicleDriver'];
        fields.forEach(f => {
          const input = document.getElementById(f);
          if (input) {
            input.classList.remove('is-invalid');
          }
        });
      }

      // ========== RESET FORM ON MODAL HIDE ==========
      const createTruckModalElement = document.getElementById('createTruckModal');
      createTruckModalElement.addEventListener('hidden.bs.modal', () => {
        document.getElementById('truckForm').reset();
        document.getElementById('truckId').value = '';
        const container = document.getElementById('garbageCollectorContainer');
        container.innerHTML = '';
        collectorCount = 0;
        addCollectorField();
        document.getElementById('createTruckModalLabel').textContent = 'Create Truck';
        clearInvalidFields();
      });

      // ========== SEARCH FUNCTION ==========
      function initializeSearch() {
        const searchBar = document.getElementById('searchBar');
        searchBar.addEventListener('input', debounce(handleSearch, 300));
        function handleSearch() {
          const query = searchBar.value.toLowerCase();
          const filteredTrucks = Object.entries(allTrucks).filter(([key, truck]) => {
            return (
              truck.vehicleName.toLowerCase().includes(query) ||
              truck.plateNumber.toLowerCase().includes(query) ||
              truck.vehicleDriver.toLowerCase().includes(query) ||
              (truck.garbageCollectors && truck.garbageCollectors.join(' ').toLowerCase().includes(query))
            );
          });
          const filteredTrucksObj = Object.fromEntries(filteredTrucks);
          renderTrucks(filteredTrucksObj);
        }
      }

      // ========== SORT FUNCTION ==========
      function initializeSort() {
        const sortOptions = document.querySelectorAll('.sort-option');
        sortOptions.forEach(option => {
          option.addEventListener('click', (e) => {
            e.preventDefault();
            const sortKey = option.getAttribute('data-sort');
            if (currentSort.key === sortKey) {
              currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
            } else {
              currentSort.key = sortKey;
              currentSort.order = 'asc';
            }
            renderTrucks(allTrucks);
          });
        });
      }

      // ========== DEBOUNCE FUNCTION ==========
      function debounce(func, delay) {
        let debounceTimer;
        return function() {
          const context = this;
          const args = arguments;
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(() => func.apply(context, args), delay);
        };
      }

      // ========== INITIAL LOAD ==========
      document.addEventListener('DOMContentLoaded', async () => {
        await Promise.all([loadDrivers(), loadTrucks()]);
        addCollectorField();
        initializeSearch();
        initializeSort();
        clearInvalidFields();
      });

      // --------------[ LOAD USERNAME & OPTIONAL PROFILE IMAGE ]--------------
      async function fetchUserData() {
        try {
          const response = await fetch('../backend/fetch_users.php');
          const data = await response.json();
          if (data.status === 'success') {
            const usernameEl = document.getElementById('dropdownUsername');
            const profileImg = document.getElementById('profileImg');
            usernameEl.textContent = data.name || 'Unknown';
            if (data.profile_image) {
              profileImg.src = data.profile_image;
            }
            // Update currentPosition if available
            if (data.position) {
              currentPosition = data.position;
            }
          }
        } catch (error) {
          console.error('Error fetching user data:', error);
        }
      }


    </script>
    
  <script src="km-per-liter-display-observer.js"></script> 
  </body>
  </html>
