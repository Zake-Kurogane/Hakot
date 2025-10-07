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
  <title>Hakot | Truck Drivers</title>
  <link rel="icon" type="image/x-icon" href="img/hakot-icon.png">
  <link rel="stylesheet" type="text/css" href="navs.css"/>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <!-- Bootstrap Icons CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <!-- Custom CSS -->
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
      background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent background */
      display: none; /* Initially hidden */
      justify-content: center;
      align-items: center;
      z-index: 9999; /* Ensure it sits on top of all other elements */
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
    /* Main Content */
    .content {
      padding-top: 85px;
        padding-left: 20px;
        padding-right: 20px;
        max-width: 100%;
    }
    @media (min-width: 992px) {
      .content {
        margin-left: 250px; /* Matches .sidebar width */
      }
    }
    .search-container {
      display: flex;
      align-items: center;
      gap: 10px;
      max-width: 350px;
      width: 100%;
    }
    .search-bar {
      flex: 1 1 auto;
    }
    .btn-create {
      background-color: #26a541;
      color: #fff;
    }
    .btn-create:hover {
      background-color: #21933d;
    }
    /* Card styling */
    .driver-card {
      background-color: #ffffff;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease-in-out;
      overflow: hidden;
      position: relative; /* for top-right icons */
      height: 250px; /* Fixed card height */
    }
    .driver-card:hover {
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    .card-icons {
      position: absolute;
      top: 8px;
      right: 8px;
      display: flex;
      gap: 8px;
    }
    .card-icons i {
      cursor: pointer;
      font-size: 1rem;
    }
  
    .driver-card .col-4 {
      height: 100px;
      overflow: hidden;
    }
   
    .driver-card .col-4 img {
      width: 100% !important;
      height: 100% !important;
      object-fit: cover;
    }

    .driver-card img.h-100 {
      height: 100px !important;
    }
    .driver-card .card-body {
      padding: 15px;
    }
    .driver-card .card-title {
      font-size: 16px;
      font-weight: bold;
      color: #333;
      margin-bottom: 5px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .driver-card .card-text {
      font-size: 14px;
      color: #777;
    }
    .modal-content {
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    .upload-image {
      text-align: center;
      margin-bottom: 20px;
    }
    .modal-footer {
      border-top: none;
    }
    .modal-header {
      border-bottom: none;
    }
    .modal-title {
      color: #198754;
      font-weight: bold;
    }
    /* Red warning styling for invalid fields */
    .is-invalid {
      border-color: #dc3545 !important;
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
      <a href="schedule.php"><i class="fas fa-calendar-alt"></i> Truck Schedules</a>
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
      <button class="nav-link active" data-entity="operators">OPERATORS</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-entity="trucks">TRUCKS</button>
    </li>
  </ul>

  <!-- Search & Create Section -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center flex-wrap mb-4">
    <div class="search-container">
      <input type="text" class="form-control search-bar" id="searchInput" placeholder="Search…" />
      <i class="fas fa-sort" id="sortIcon" style="cursor:pointer"></i>
    </div>
    <button class="btn btn-create" data-bs-toggle="modal" data-bs-target="#createDriverModal">
      <i class="bi bi-person-add"></i> Register Driver
    </button>
  </div>


      <!-- Driver Cards Container -->
      <div class="row mt-4" id="driverCards">
        <!-- Driver cards will be loaded here -->
      </div>
    </div>
  </div>
  <!-- End .content -->

  <!-- Register/Edit Driver Modal -->
  <div class="modal fade" id="createDriverModal" tabindex="-1" aria-labelledby="createDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <!-- Header -->
        <div class="modal-header">
          <h5 class="modal-title" id="createDriverModalLabel">Register Driver</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <!-- Body -->
        <div class="modal-body">
          <form id="driverForm">
            <!-- Hidden input: used to detect if we're editing an existing driver -->
            <input type="hidden" id="driverId" name="driverId" />
            <!-- Hidden fullName field to preserve backend functionality -->
            <input type="hidden" id="fullName" name="fullName" />
            <div class="row">
              <div class="col-md-4 upload-image">
                <label for="driverImage" class="form-label">
                  Upload Driver Image <span id="driverImageRequired" style="color: red; display: none;">*</span>
                </label>
                <input type="file" name="driverImage" id="driverImage" class="form-control" accept="image/*" />
              </div>
              <div class="col-md-8">
                <!-- First Name field -->
                <div class="mb-3">
                  <label for="firstName" class="form-label">
                    First Name <span id="firstNameRequired" style="color: red; display: none;">*</span>
                  </label>
                  <input type="text" class="form-control" id="firstName" name="firstName" placeholder="Enter first name" required />
                </div>
                <!-- Last Name field -->
                <div class="mb-3">
                  <label for="lastName" class="form-label">
                    Last Name <span id="lastNameRequired" style="color: red; display: none;">*</span>
                  </label>
                  <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Enter last name" required />
                </div>
                <!-- Username -->
                <div class="mb-3">
                  <label for="username" class="form-label">
                    Username <span id="usernameRequired" style="color: red; display: none;">*</span>
                  </label>
                  <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required />
                  <span id="usernameError" style="color: red; display: none; font-size: 0.9em;"></span>
                </div>
                <!-- Password -->
                <div class="mb-3">
                  <label for="password" class="form-label">
                    Password <span id="passwordRequired" style="color: red; display: none;">*</span>
                  </label>
                  <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" />
                </div>
                <!-- Confirm Password -->
                <div class="mb-3">
                  <label for="confirmPassword" class="form-label">
                    Confirm Password <span id="confirmPasswordRequired" style="color: red; display: none;">*</span>
                  </label>
                  <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" />
                </div>
                <!-- Modal Footer (Save/Cancel) -->
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-square-fill"></i> Cancel
                  </button>
                  <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Save
                  </button>
                </div>
              </div><!-- /.col-md-8 -->
            </div><!-- /.row -->
          </form>
        </div><!-- /.modal-body -->
      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div><!-- /#createDriverModal -->

  <!-- DELETE CONFIRMATION MODAL -->
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this driver?</p>
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

  <!-- Admin Password Modal (for delete confirmation) -->
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
          <div id="adminErrorMsg" style="color: red; margin-top: 10px;"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-square-fill"></i> Cancel
          </button>
          <button type="button" class="btn btn-success" id="adminPasswordConfirmBtn">
            <i class="bi bi-check-square-fill"></i> Confirm
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Error/Success Modal -->
  <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <!-- We'll dynamically switch this title between "Error" / "Success" in JS -->
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
    // INITIAL LOAD: Fetch user data (updates currentPosition) and drivers
    fetchUserData();
    let allDrivers = [];
    let selectedDriverId = null; // for delete actions
    let sortAsc = true; // track sort order

    // ************************
    // *** RENDER DRIVERS ***
    // ************************
    function renderDrivers(drivers) {
      const driversContainer = document.getElementById('driverCards');
      let cardsHTML = '';
      drivers.forEach(driver => {
        cardsHTML += `
          <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="driver-card h-100">
              <!-- Icons in top-right corner -->
              <div class="card-icons">
                <!-- EDIT ICON -->
                <i class="fas fa-edit text-primary"
                   data-driver='${JSON.stringify(driver)}'
                   onclick="editDriver(this)"></i>
                <!-- DELETE ICON -->
                <i class="fas fa-trash-alt text-danger"
                   data-id="${driver.id}"
                   data-url="${driver.imageUrl || ''}"
                   onclick="deleteDriver(this)"></i>
              </div>
              <div class="row g-0 h-100">
                <!-- Image Column -->
                <div class="col-4">
                  <img src="${driver.imageUrl}" 
                       alt="${driver.fullName || driver.username}"
                       class="img-fluid h-100"
                       style="object-fit: cover; border-radius: 8px 0 0 8px;"
                       loading="lazy" />
                </div>
                <!-- Details Column -->
                <div class="col-8 d-flex flex-column justify-content-center p-2">
                  <h5 class="card-title mb-1">${driver.fullName || driver.username}</h5>
                  <p class="card-text mb-0">${driver.username}</p>
                </div>
              </div>
            </div>
          </div>
        `;
      });
      document.getElementById('driverCards').innerHTML = cardsHTML;
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

    // ************************
    // *** LOAD DRIVERS ***
    // ************************
    document.addEventListener('DOMContentLoaded', async () => {
      try {
        const response = await fetch('../backend/fetch_drivers.php?page=1&limit=20', { method: 'GET' });
        const result = await response.json();
        if (result.status === 'success') {
          allDrivers = Object.entries(result.data).map(([id, driver]) => ({
            id,
            ...driver
          }));
          renderDrivers(allDrivers);
        } else {
          console.error(result.message);
          showErrorModal(result.message);
        }
      } catch (err) {
        console.error('Error:', err);
        showErrorModal('An error occurred while fetching drivers.');
      }
    });

    // *************************
    // *** SEARCH FUNCTION  ***
    // *************************
    const searchInput = document.getElementById('searchInput');
    function handleSearch() {
      const searchTerm = searchInput.value.toLowerCase();
      const filtered = allDrivers.filter(d =>
        ((d.fullName && d.fullName.toLowerCase().includes(searchTerm)) ||
        (d.username && d.username.toLowerCase().includes(searchTerm)))
      );
      renderDrivers(filtered);
    }
    const debouncedSearch = debounce(handleSearch, 300);
    searchInput.addEventListener('input', debouncedSearch);

    // *************************
    // *** SORT FUNCTION     ***
    // *************************
    const sortIcon = document.getElementById('sortIcon');
    sortIcon.addEventListener('click', () => {
      sortAsc = !sortAsc;
      allDrivers.sort((a, b) => {
        const userA = a.username.toLowerCase();
        const userB = b.username.toLowerCase();
        if (userA < userB) return sortAsc ? -1 : 1;
        if (userA > userB) return sortAsc ? 1 : -1;
        return 0;
      });
      renderDrivers(allDrivers);
    });

    // ************************
    // *** EDIT DRIVER     ***
    // ************************
    function editDriver(iconElement) {
      clearInvalidFields();
      const driverData = JSON.parse(iconElement.getAttribute('data-driver'));
      document.getElementById('driverId').value = driverData.id;
      
      let firstName = '';
      let lastName = '';
      if (driverData.fullName) {
        const fullName = driverData.fullName.trim();
        const tokens = fullName.split(' ');
        // Define common suffixes
        const suffixes = ["Jr", "Jr.", "Sr", "Sr.", "II", "III", "IV", "V"];
        if (tokens.length === 1) {
          firstName = fullName;
        } else {
          const lastToken = tokens[tokens.length - 1];
          if (suffixes.includes(lastToken) && tokens.length >= 3) {
            lastName = tokens[tokens.length - 2] + ' ' + lastToken;
            firstName = tokens.slice(0, -2).join(' ');
          } else {
            lastName = tokens[tokens.length - 1];
            firstName = tokens.slice(0, -1).join(' ');
          }
        }
      }
      document.getElementById('firstName').value = firstName;
      document.getElementById('lastName').value = lastName;
      document.getElementById('username').value = driverData.username || '';
      document.getElementById('password').value = '';
      document.getElementById('confirmPassword').value = '';
      // Also update the hidden fullName field
      document.getElementById('fullName').value = driverData.fullName || '';
      document.getElementById('createDriverModalLabel').textContent = 'Edit Driver';
      const createDriverModal = new bootstrap.Modal(document.getElementById('createDriverModal'));
      createDriverModal.show();
    }

    // ************************
    // *** DELETE DRIVER   ***
    // ************************
    function deleteDriver(iconElement) {
      selectedDriverId = iconElement.getAttribute('data-id');
      window.selectedDriverImageUrl = iconElement.getAttribute('data-url') || '';
      const confirmDeleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
      confirmDeleteModal.show();
    }

    // ************************
    // *** ADMIN PASSWORD PROMPT FUNCTION ***
    // ************************
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
                    <div id="adminErrorMsg" style="color: red; margin-top: 10px;"></div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                      <i class="bi bi-x-square-fill"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="adminPasswordConfirmBtn">
                      <i class="bi bi-check-square-fill"></i> Confirm
                    </button>
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
        const errorMsgDiv = adminModalEl.querySelector('#adminErrorMsg');
        // Clear previous input and error message
        passwordInput.value = '';
        errorMsgDiv.textContent = '';
        // Remove previous event listeners by cloning the button
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        newConfirmBtn.addEventListener('click', async () => {
          showLoadingScreen();
          const password = passwordInput.value;
          try {
            const verifyResponse = await fetch('../backend/verify_admin.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ password: password })
            });
            const verifyResult = await verifyResponse.json();
            hideLoadingScreen();
            if (verifyResult.status === 'success') {
              adminModal.hide();
              resolve(password);
            } else {
              errorMsgDiv.textContent = verifyResult.message || 'Invalid admin password.';
            }
          } catch (e) {
            hideLoadingScreen();
            console.error(e);
            errorMsgDiv.textContent = 'Error verifying admin password.';
          }
        });
        adminModal.show();
      });
    }

    // ************************
    // *** DELETE DRIVER WITH ADMIN VERIFICATION ***
    // ************************
    document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
      if (!selectedDriverId) return;
      const deleteModalEl = document.getElementById('confirmDeleteModal');
      const deleteModal = bootstrap.Modal.getInstance(deleteModalEl) || new bootstrap.Modal(deleteModalEl);
      deleteModal.hide();

      // Always prompt for admin password regardless of user position
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
        const response = await fetch('../backend/driver_delete.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ 
            driverId: selectedDriverId,
            imageUrl: window.selectedDriverImageUrl
          })
        });
        const result = await response.json();
        if (result.status === 'success') {
          showSuccessModal('Driver deleted successfully!');
        } else {
          showErrorModal(result.message);
        }
      } catch (err) {
        console.error('Error deleting driver:', err);
        showErrorModal('An error occurred while deleting the driver.');
      } finally {
        hideLoadingScreen();
      }
    });

    // ************************
    // *** FORM SUBMISSION (CREATE/EDIT) ***
    // ************************
    const form = document.getElementById('driverForm');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearInvalidFields();
      // Before submitting, combine first name and last name into the hidden fullName field
      const firstName = document.getElementById('firstName').value.trim();
      const lastName = document.getElementById('lastName').value.trim();
      document.getElementById('fullName').value = firstName + ' ' + lastName;
      
      const formData = new FormData(form);
      const driverId = formData.get('driverId');
      let endpointUrl = '../backend/driver_register.php';
      if (driverId) {
        endpointUrl = '../backend/driver_update.php';
      }
      showLoadingScreen();
      try {
        const response = await fetch(endpointUrl, {
          method: 'POST',
          body: formData,
        });
        const result = await response.json();
        if (result.status === 'success') {
          const createDriverModalEl = document.getElementById('createDriverModal');
          const createDriverModalInstance = bootstrap.Modal.getInstance(createDriverModalEl);
          if (createDriverModalInstance) {
            createDriverModalInstance.hide();
          } else {
            const newModalInstance = new bootstrap.Modal(createDriverModalEl);
            newModalInstance.hide();
          }
          showSuccessModal(result.message);
        } else {
          // Check if the error message indicates the username already exists.
          if (result.message.toLowerCase().includes("username already exist")) {
            const usernameInput = document.getElementById('username');
            usernameInput.classList.add('is-invalid');
            const usernameErrorSpan = document.getElementById('usernameError');
            usernameErrorSpan.textContent = "Username already exist";
            usernameErrorSpan.style.display = "block";
          }
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
      const fields = ['firstName', 'lastName', 'username', 'password', 'confirmPassword'];
      fields.forEach(f => {
        const input = document.getElementById(f);
        if (input && !input.value.trim()) {
          input.classList.add('is-invalid');
        }
      });
    }
    function clearInvalidFields() {
      const fields = ['firstName', 'lastName', 'username', 'password', 'confirmPassword'];
      fields.forEach(f => {
        const input = document.getElementById(f);
        if (input) {
          input.classList.remove('is-invalid');
        }
      });
      // Also clear username error span if visible
      document.getElementById('usernameError').style.display = 'none';
    }

    // ******************************
    // *** RESET FORM ON MODAL HIDE ***
    // ******************************
    const createDriverModalElement = document.getElementById('createDriverModal');
    createDriverModalElement.addEventListener('hidden.bs.modal', () => {
      document.getElementById('driverForm').reset();
      document.getElementById('driverId').value = '';
      clearInvalidFields();
      document.getElementById('createDriverModalLabel').textContent = 'Register Driver';
    });

    // ************************
    // *** Debounce Function ***
    // ************************
    function debounce(func, delay) {
      let debounceTimer;
      return function() {
        const context = this;
        const args = arguments;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => func.apply(context, args), delay);
      };
    }

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
          if (data.position) {
            currentPosition = data.position;
          }
        }
      } catch (error) {
        console.error('Error fetching user data:', error);
      }
    }

    // ******************************
    // *** Restrict Number Input  ***
    // ******************************
    function removeNumbers(event) {
      // Remove any numeric characters
      event.target.value = event.target.value.replace(/\d+/g, '');
    }
    document.getElementById('firstName').addEventListener('input', removeNumbers);
    document.getElementById('lastName').addEventListener('input', removeNumbers);
    //document.getElementById('username').addEventListener('input', removeNumbers);

    // ************************
    // *** Update Required Indicators ***
    // ************************
    // Function to update required star for password fields
    function updatePasswordRequiredIndicators() {
      const driverId = document.getElementById('driverId').value.trim();
      const passwordField = document.getElementById('password');
      const confirmPasswordField = document.getElementById('confirmPassword');
      const passwordReq = document.getElementById('passwordRequired');
      const confirmPasswordReq = document.getElementById('confirmPasswordRequired');
      
      // Only show required indicator if we are in registration mode (no driverId)
      if (!driverId) {
        passwordReq.style.display = passwordField.value.trim() === '' ? 'inline' : 'none';
        confirmPasswordReq.style.display = confirmPasswordField.value.trim() === '' ? 'inline' : 'none';
      } else {
        // In edit mode, hide the asterisk because passwords are optional.
        passwordReq.style.display = 'none';
        confirmPasswordReq.style.display = 'none';
      }
    }

    // Function to update required star for driver image field
    function updateDriverImageRequiredIndicator() {
      const driverId = document.getElementById('driverId').value.trim();
      const driverImageField = document.getElementById('driverImage');
      const imageReq = document.getElementById('driverImageRequired');
      
      // In registration mode (no driverId), require an image if none is selected.
      if (!driverId) {
        imageReq.style.display = (driverImageField.files.length === 0) ? 'inline' : 'none';
      } else {
        // In edit mode, uploading a new image is optional.
        imageReq.style.display = 'none';
      }
    }

    // Function to update required stars for text fields (first name, last name, username)
    function updateTextRequiredIndicators() {
      const driverId = document.getElementById('driverId').value.trim();
      const firstNameField = document.getElementById('firstName');
      const lastNameField = document.getElementById('lastName');
      const usernameField = document.getElementById('username');
      const firstNameReq = document.getElementById('firstNameRequired');
      const lastNameReq = document.getElementById('lastNameRequired');
      const usernameReq = document.getElementById('usernameRequired');
      
      if (!driverId) {
        firstNameReq.style.display = (firstNameField.value.trim() === '') ? 'inline' : 'none';
        lastNameReq.style.display = (lastNameField.value.trim() === '') ? 'inline' : 'none';
        usernameReq.style.display = (usernameField.value.trim() === '') ? 'inline' : 'none';
      } else {
        firstNameReq.style.display = 'none';
        lastNameReq.style.display = 'none';
        usernameReq.style.display = 'none';
      }
    }

    // Update on input events for password fields
    document.getElementById('password').addEventListener('input', updatePasswordRequiredIndicators);
    document.getElementById('confirmPassword').addEventListener('input', updatePasswordRequiredIndicators);
    // Update on change event for the image upload field
    document.getElementById('driverImage').addEventListener('change', updateDriverImageRequiredIndicator);
    // Update on input events for text fields (first name, last name, username)
    document.getElementById('firstName').addEventListener('input', updateTextRequiredIndicators);
    document.getElementById('lastName').addEventListener('input', updateTextRequiredIndicators);
    document.getElementById('username').addEventListener('input', updateTextRequiredIndicators);
    // Also, update the indicators whenever the modal is shown
    createDriverModalElement.addEventListener('show.bs.modal', () => {
      updatePasswordRequiredIndicators();
      updateDriverImageRequiredIndicator();
      updateTextRequiredIndicators();
    });
  </script>
</body>
</html>
