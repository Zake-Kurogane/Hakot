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
  <title>Hakot | Profile</title>
  <link rel="icon" type="image/x-icon" href="img/hakot-icon.png">
  <link rel="stylesheet" type="text/css" href="navs.css"/>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">

  <!-- Custom CSS -->
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f9f9f9;
      margin: 0;
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
    }
    @media (min-width: 992px) {
      .content {
        margin-left: 250px;
      }
    }
    .modal-content {
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    html, body {
      height: 100%;
    }
    .content {
      min-height: 100vh;
    }
    .settings-container {
      position: relative;
      background: #fff;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      max-width: 900px;
      margin: 20px auto;
    }
    .settings-container h2 {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 10px;
    }
    .settings-container p {
      font-size: 14px;
      color: #666;
      margin-bottom: 20px;
    }
    .form-group {
      margin-bottom: 15px;
      position: relative;
    }
    .form-group input,
    .form-group select {
      width: 100%;
      padding: 10px;
      padding-right: 40px;
      font-size: 14px;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-sizing: border-box;
    }
    .form-group .input-icon {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #aaa;
      font-size: 16px;
    }
    .btn-green {
      width: 100%;
      padding: 12px;
      background-color: #32CD32;
      color: white;
      font-size: 14px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      text-align: center;
    }
    .btn-green:hover {
      background-color: #28a745;
    }
    .save-button {
      /* Fixed minimum width for consistency */
      min-width: 150px;
      padding: 10px 20px;
      background-color: #32CD32;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .save-button:hover {
      background-color: #28a745;
    }
    .upload-button {
      background-color: #32CD32;
      color: white;
      padding: 5px 10px;
      font-size: 12px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .upload-button:hover {
      background-color: #28a745;
    }
    /* Registered Users Cards Container */
    .user-cards-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: flex-start;
      max-height: 400px;
      overflow-y: auto;
      padding-right: 5px;
    }
    /* Custom Material Design Scrollbar */
    .user-cards-container::-webkit-scrollbar {
      width: 8px;
    }
    .user-cards-container::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 4px;
    }
    .user-cards-container::-webkit-scrollbar-thumb {
      background: #32CD32;
      border-radius: 4px;
    }
    .user-cards-container::-webkit-scrollbar-thumb:hover {
      background: #28a745;
    }
    .user-card {
      border: 1px solid #ccc;
      border-radius: 10px;
      padding: 15px;
      width: calc(50% - 20px);
      text-align: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      background-color: #fff;
      cursor: pointer;
    }
    .user-card img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 10px;
    }
    .user-card h6 {
      margin: 5px 0;
      font-size: 16px;
      font-weight: bold;
    }
    .user-card p {
      margin: 0;
      font-size: 14px;
      color: #555;
    }
    /* New buttons for update mode in User Management form */
    .update-actions {
      display: flex;
      gap: 10px;
      margin-top: 10px;
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
  <!-- Global Loading Screen (if needed) -->
  <div id="loadingScreen" class="loading-container">
    <div class="spinner-border text-success" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
  </div>

  <!-- Offcanvas Sidebar -->
  <div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" data-bs-backdrop="false">
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
        <a href="settings.php" ><i class="fas fa-cog"></i> Settings</a>
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

  <div class="content">
    <!-- USER MANAGEMENT (hidden by default for non-admins) -->
    <div class="settings-container" id="userMgmtContainer" style="display:none; position:relative;">
      <!-- Container-specific loading overlay for User Management -->
      <div class="loading-container" id="userMgmtLoading">
        <div class="spinner-border text-success" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <h2>USER MANAGEMENT</h2>
      <p>Manage user accounts below.</p>
      <div class="row">
        <div class="col-md-6">
          <!-- The form will now act as Register/Update -->
          <form id="registerUserForm" enctype="multipart/form-data">
            <div class="form-group">
              <label for="reg-image" style="display:block; margin-bottom:5px; font-size:14px;">Upload Image (Optional)</label>
              <input type="file" id="reg-image" name="image" accept="image/*">
            </div>
            <div class="form-group">
              <input type="text" id="reg-username" name="username" placeholder="Username" required>
            </div>
            <!-- Replace Full Name with separate First and Last Name fields -->
            <div class="form-group">
              <input type="text" id="reg-firstname" name="firstname" placeholder="First Name" required>
            </div>
            <div class="form-group">
              <input type="text" id="reg-lastname" name="lastname" placeholder="Last Name" required>
            </div>
            <!-- Hidden input to combine first and last name for backend compatibility -->
            <input type="hidden" id="reg-fullname-hidden" name="fullname">
            <div class="form-group">
              <select id="reg-position" name="position" required>
                <option value="" disabled selected>Select Position</option>
                <option value="Colleague">Colleague</option>
                <option value="Officer">Officer</option>
              </select>
            </div>
            <div class="form-group">
              <input type="password" id="reg-password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
              <input type="password" id="reg-confirm-password" placeholder="Confirm Password" required>
            </div>
            <!-- Buttons container for update mode -->
            <div class="update-actions" id="updateActions" style="display:none;">
              <button type="button" class="btn-green" id="updateBtn"><i class="fa-solid fa-user-pen"></i> Update User</button>
              <button type="button" class="btn-green" id="deleteBtn"><i class="fa-solid fa-user-minus"></i> Delete User</button>
              <button type="button" class="btn-green" id="clearBtn"><i class="fa-solid fa-rotate-left"></i> Clear</button>
            </div>
            <!-- Default register button -->
            <button type="submit" class="btn-green" id="registerBtn"><i class="fa-solid fa-user-plus"></i> Register User</button>
          </form>
        </div>

        <!-- Registered Users Cards Container -->
        <div class="col-md-6" style="position:relative;">
          <!-- Container-specific loading overlay for user cards -->
          <div class="loading-container" id="userCardsLoading">
            <div class="spinner-border text-success" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          <h2>REGISTERED USERS</h2>
          <div class="user-cards-container" id="userCardsContainer">
            <!-- User cards will be injected here -->
          </div>
        </div>
      </div>
    </div>

    <!-- PERSONAL INFORMATION -->
    <div class="settings-container" id="personalInfoContainer" style="position:relative;">
      <!-- Container-specific loading overlay for personal info -->
      <div class="loading-container" id="personalInfoLoading">
        <div class="spinner-border text-success" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <h2>PERSONAL INFORMATION</h2>
      <p>Information about your account</p>
      <div class="d-flex align-items-start">
        <div class="me-4 text-center">
          <img id="personalInfoAvatar" src="img/default-profile.jpg" alt="User Avatar"
               style="width:120px; height:120px; border-radius:50%; object-fit: cover; border:2px solid #ddd;">
          <input type="file" id="profileImageFile" name="profileImage" style="display:none" accept="image/*">
          <button class="upload-button" id="updateImageBtn" style="display:block; margin-top:15px; margin-left:13px;"><i class="fa-solid fa-upload"></i> Update Image</button>
        </div>
        <div style="flex:1;">
          <form id="saveDetailsForm">
            <div class="form-group" style="margin-bottom:15px;">
              <label>Username:</label>
              <input type="text" id="userInfoUsername" placeholder="Username">
            </div>
            <!-- Replace the single Full Name field with separate First and Last Name fields -->
            <div class="form-group" style="margin-bottom:15px;">
              <label>First Name:</label>
              <input type="text" id="userInfoFirstName" placeholder="First Name" required>
            </div>
            <div class="form-group" style="margin-bottom:15px;">
              <label>Last Name:</label>
              <input type="text" id="userInfoLastName" placeholder="Last Name" required>
            </div>
            <!-- Hidden field to combine names before submission -->
            <input type="hidden" id="userInfoFullNameHidden" name="fullname">
            <!-- Position controls -->
            <div class="form-group" style="margin-bottom:15px;" id="positionSelectGroup">
              <label>Position:</label>
              <input type="text" id="userInfoPosition" placeholder="Position" readonly>
            </div>
            <div class="form-group" style="margin-bottom:15px; display:none;" id="adminPositionGroup">
              <label>Position:</label>
              <input type="text" id="adminPosition" value="Admin" readonly>
            </div>
            <!-- Right aligned Save Details button -->
            <div class="text-end">
              <button type="submit" class="save-button" style="margin-top:10px;"><i class="fa-solid fa-floppy-disk"></i> Save Details</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- LOGIN INFORMATION -->
    <div class="settings-container" id="loginInfoContainer" style="position:relative;">
      <!-- Container-specific loading overlay for login info -->
      <div class="loading-container" id="loginInfoLoading">
        <div class="spinner-border text-success" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <h2>LOGIN INFORMATION</h2>
      <p>The credentials for authorization</p>
      <form id="savePasswordForm">
        <div class="form-group">
          <input type="password" id="old-password" placeholder="Old Password">
          <i class="fas fa-eye input-icon" onclick="togglePassword('old-password', this)"></i>
        </div>
        <div class="form-group">
          <input type="password" id="new-password" placeholder="New Password">
          <i class="fas fa-eye input-icon" onclick="togglePassword('new-password', this)"></i>
        </div>
        <div class="form-group">
          <input type="password" id="confirm-password" placeholder="Confirm New Password">
          <i class="fas fa-eye input-icon" onclick="togglePassword('confirm-password', this)"></i>
        </div>
        <!-- Right aligned Save Password button -->
        <div class="text-end">
          <button type="submit" class="save-button"><i class='fas fa-key'></i> Change Password</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Error/Success Modal -->
  <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="errorModalLabel">Message</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p id="errorMessage"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" defer></script>

  <script>
    let currentPosition = '';
    let currentPassword = ''; // Insecure for demo only
    let selectedUserKey = null;  // Holds the Firebase key of the selected user
    let selectedUserData = null; // Stores the full data for the selected user

    document.addEventListener('DOMContentLoaded', () => {
      // Show global loading screen if needed (currently controlled via container loaders)
      fetchUserData();   // load current user's personal info
      loadAllUsers();    // load all registered users into cards
      setupImagePreview();

      // Attach event listener for updating image
      document.getElementById('updateImageBtn').addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('profileImageFile').click();
      });

      // Registration form submission handler:
      document.getElementById('registerUserForm').addEventListener('submit', (e) => {
        e.preventDefault();
        // Before submitting, combine first and last names into the hidden fullname field
        const firstName = document.getElementById('reg-firstname').value.trim();
        const lastName = document.getElementById('reg-lastname').value.trim();
        document.getElementById('reg-fullname-hidden').value = firstName + ' ' + lastName;
        if (!selectedUserKey) {
          console.log("No user selected; registering new user...");
          handleRegisterUser();
        } else {
          console.log("User selected; update mode active.");
        }
      });

      // Attach event listeners for update, delete, and clear buttons
      document.getElementById('updateBtn').addEventListener('click', handleUpdateUser);
      document.getElementById('deleteBtn').addEventListener('click', handleDeleteUser);
      document.getElementById('clearBtn').addEventListener('click', clearUpdateMode);

      // Attach event listeners for Save Details and Save Password forms
      document.getElementById('saveDetailsForm').addEventListener('submit', handleSaveDetails);
      document.getElementById('savePasswordForm').addEventListener('submit', handleSavePassword);

      // Add event listeners to remove numbers from first and last name fields
      document.getElementById('reg-firstname').addEventListener('input', removeNumbers);
      document.getElementById('reg-lastname').addEventListener('input', removeNumbers);
      document.getElementById('userInfoFirstName').addEventListener('input', removeNumbers);
      document.getElementById('userInfoLastName').addEventListener('input', removeNumbers);
    });

    // Helper: Show modal with message
    function showModal(message) {
      const modalEl = document.getElementById('errorModal');
      const modalTitleEl = document.getElementById('errorModalLabel');
      const modalBodyEl = document.getElementById('errorMessage');
      
      // Set the title and message for a simple alert
      modalTitleEl.innerText = 'Message';
      modalBodyEl.innerText = message;
      
      // Remove any existing modal footer (confirmation buttons)
      const modalFooter = modalEl.querySelector('.modal-footer');
      if (modalFooter) {
        modalFooter.remove();
      }
      
      // Show the modal (create a new Bootstrap instance)
      const bootstrapModal = new bootstrap.Modal(modalEl);
      bootstrapModal.show();
    }

    function showConfirmModal(message) {
      return new Promise((resolve, reject) => {
        // Get your existing error modal elements
        const modalEl = document.getElementById('errorModal');
        const modalTitleEl = document.getElementById('errorModalLabel');
        const modalBodyEl = document.getElementById('errorMessage');
        
        // Set the title and message for confirmation
        modalTitleEl.innerText = 'Confirm Deletion';
        modalBodyEl.innerText = message;
        
        // Check if a modal footer exists; if not, create one
        let modalFooter = modalEl.querySelector('.modal-footer');
        if (!modalFooter) {
          modalFooter = document.createElement('div');
          modalFooter.classList.add('modal-footer');
          modalEl.querySelector('.modal-content').appendChild(modalFooter);
        }
        
        // Clear any existing footer content
        modalFooter.innerHTML = '';
        
        // Create Cancel button
        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'btn btn-secondary';
        cancelBtn.innerHTML = '<i class="bi bi-x-square-fill"></i> Cancel';
        
        // Create Yes button with red color and icon (using Bootstrap Icons)
        const yesBtn = document.createElement('button');
        yesBtn.type = 'button';
        yesBtn.className = 'btn btn-danger';
        yesBtn.innerHTML = '<i class="bi bi-trash-fill"></i> Delete';
        
        // Append buttons to the footer
        modalFooter.appendChild(cancelBtn);
        modalFooter.appendChild(yesBtn);
        
        // Create a new Bootstrap modal instance from the existing modal
        const bootstrapModal = new bootstrap.Modal(modalEl);
        
        // Set up click handlers for the buttons
        cancelBtn.onclick = () => {
          resolve(false);
          bootstrapModal.hide();
        };
        yesBtn.onclick = () => {
          resolve(true);
          bootstrapModal.hide();
        };
        
        // Show the modal
        bootstrapModal.show();
      });
    }

    // Setup image preview using FileReader
    function setupImagePreview() {
      const fileInput = document.getElementById('profileImageFile');
      const avatarImg = document.getElementById('personalInfoAvatar');
      fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = function(evt) {
            avatarImg.src = evt.target.result;
          };
          reader.readAsDataURL(file);
        }
      });
    }

    // Toggle password visibility
    function togglePassword(inputId, icon) {
      const input = document.getElementById(inputId);
      if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
      } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
      }
    }

    // Remove numbers from input fields
    function removeNumbers(event) {
      event.target.value = event.target.value.replace(/\d+/g, '');
    }

    // A helper to split full names using robust logic.
    function splitFullName(fullname) {
      const tokens = fullname.trim().split(' ');
      const suffixes = ['Jr', 'Jr.', 'Sr', 'Sr.', 'II', 'III', 'IV', 'V'];
      let firstName, lastName;
      
      if (tokens.length > 1) {
        if (suffixes.includes(tokens[tokens.length - 1]) && tokens.length >= 3) {
          lastName = tokens.slice(-2).join(' ');
          firstName = tokens.slice(0, -2).join(' ');
        } else {
          lastName = tokens[tokens.length - 1];
          firstName = tokens.slice(0, tokens.length - 1).join(' ');
        }
      } else {
        firstName = tokens[0];
        lastName = '';
      }
      return { firstName, lastName };
    }

    // Fetch current user data from fetch_users.php
    async function fetchUserData() {
      // Show personal info container loader
      document.getElementById('personalInfoLoading').classList.add('show');
      try {
        const response = await fetch('../backend/fetch_users.php');
        const data = await response.json();
        if (data.status === 'success') {
          currentPosition = data.position || '';
          currentPassword = data.password || '';

          // Topbar (if exists)
          const usernameEl = document.getElementById('dropdownUsername');
          if (usernameEl) usernameEl.textContent = data.name || 'Unknown';
          const profileImg = document.getElementById('profileImg');
          if (profileImg && data.profile_image) profileImg.src = data.profile_image;

          // Personal Information fields
          const personalAvatar = document.getElementById('personalInfoAvatar');
          const infoUsername = document.getElementById('userInfoUsername');
          const infoFirstName = document.getElementById('userInfoFirstName');
          const infoLastName = document.getElementById('userInfoLastName');
          const infoPosition = document.getElementById('userInfoPosition');
          const posSelectGroup = document.getElementById('positionSelectGroup');
          const adminPosGroup = document.getElementById('adminPositionGroup');

          if (data.profile_image) personalAvatar.src = data.profile_image;
          infoUsername.value = data.username || '';
          // Use robust splitting for the full name
          const { firstName, lastName } = splitFullName(data.name || '');
          infoFirstName.value = firstName;
          infoLastName.value = lastName;

          // Case-insensitive check for admin
          if (currentPosition.toLowerCase() === 'admin') {
            posSelectGroup.style.display = 'none';
            adminPosGroup.style.display = 'block';
          } else {
            posSelectGroup.style.display = 'block';
            adminPosGroup.style.display = 'none';
            infoPosition.value = currentPosition;
          }

          // Hide User Management if not admin (case-insensitive)
          const userMgmtContainer = document.getElementById('userMgmtContainer');
          if (userMgmtContainer) {
            if (currentPosition.toLowerCase() !== 'admin') {
              userMgmtContainer.style.display = 'none';
            } else {
              userMgmtContainer.style.display = 'block';
            }
          }
        } else {
          console.error('Error fetching user data:', data.message);
          showModal('Error fetching user data: ' + data.message);
        }
      } catch (error) {
        console.error('Error fetching user data:', error);
        showModal('Error fetching user data.');
      }
      // Hide personal info loader (do not remove global loading screen here)
      document.getElementById('personalInfoLoading').classList.remove('show');
    }

    // Save Details (for personal info section)
    async function handleSaveDetails(e) {
      e.preventDefault();
      // Show container-specific loading overlay for personal info
      document.getElementById('personalInfoLoading').classList.add('show');
      const infoUsername = document.getElementById('userInfoUsername').value.trim();
      const firstName = document.getElementById('userInfoFirstName').value.trim();
      const lastName = document.getElementById('userInfoLastName').value.trim();
      // Combine first and last names into the hidden field
      document.getElementById('userInfoFullNameHidden').value = firstName + ' ' + lastName;
      const infoPosition = document.getElementById('userInfoPosition').value;
      const fileInput = document.getElementById('profileImageFile');
      const formData = new FormData();
      // Append new username as well as full name and position
      formData.append('username', infoUsername);
      formData.append('name', firstName + ' ' + lastName);
      if (currentPosition.toLowerCase() !== 'admin') {
        formData.append('position', infoPosition);
      }
      if (fileInput.files.length > 0) {
        formData.append('profileImage', fileInput.files[0]);
      }
      try {
        const response = await fetch('../backend/update_profile.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if (result.status === 'success') {
          showModal('Profile updated successfully!');
          fetchUserData();
        } else {
          showModal('Error updating profile: ' + result.message);
        }
      } catch (err) {
        console.error('Error updating profile:', err);
        showModal('Error updating profile.');
      } finally {
        document.getElementById('personalInfoLoading').classList.remove('show');
      }
    }

    // Save Password
    async function handleSavePassword(e) {
      e.preventDefault();
      // Show container-specific loading overlay for login info
      document.getElementById('loginInfoLoading').classList.add('show');
      const oldPassInput = document.getElementById('old-password').value.trim();
      const newPassInput = document.getElementById('new-password').value.trim();
      const confPassInput = document.getElementById('confirm-password').value.trim();
      if (!oldPassInput || !newPassInput || !confPassInput) {
        document.getElementById('loginInfoLoading').classList.remove('show');
        return showModal('Please fill all fields.');
      }
      if (newPassInput !== confPassInput) {
        document.getElementById('loginInfoLoading').classList.remove('show');
        return showModal('New password and confirm password do not match.');
      }
      const formData = new FormData();
      formData.append('oldPassword', oldPassInput);
      formData.append('newPassword', newPassInput);
      try {
        const response = await fetch('../backend/update_password.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if (result.status === 'success') {
          // Show a message including a redirect notification
          showModal('Password updated successfully! Redirecting to login page...');
          // Clear the password fields
          document.getElementById('old-password').value = '';
          document.getElementById('new-password').value = '';
          document.getElementById('confirm-password').value = '';
          // After a short delay, redirect to logout.php
          setTimeout(() => {
            window.location.href = 'logout.php';
          }, 2000);
        } else {
          showModal('Error updating password: ' + result.message);
        }
      } catch (error) {
        console.error('Error updating password:', error);
        showModal('Error updating password.');
      } finally {
        document.getElementById('loginInfoLoading').classList.remove('show');
      }
    }

    // Load all users for the Registered Users cards
    async function loadAllUsers() {
      // Show container loader for user cards
      document.getElementById('userCardsLoading').classList.add('show');
      const container = document.getElementById('userCardsContainer');
      container.innerHTML = '';
      try {
        const response = await fetch('../backend/fetch_all_users.php');
        const data = await response.json();
        if (data.status === 'success' && data.users) {
          data.users.forEach(user => {
            // Skip users with position admin (case-insensitive)
            if (user.position && user.position.toLowerCase() === 'admin') return;
            const card = document.createElement('div');
            card.classList.add('user-card');
            card.setAttribute('data-key', user.key); // Assume each user object includes a "key" property
            card.setAttribute('data-username', user.username);
            card.setAttribute('data-fullname', user.name);
            card.setAttribute('data-position', user.position);
            card.setAttribute('data-profile', user['profile-image'] ? user['profile-image'] : '');
            card.innerHTML = `
              <img src="${user['profile-image'] ? user['profile-image'] : 'img/default-profile.jpg'}" alt="Profile Image">
              <h6>${user.name || user.username}</h6>
              <p>${user.position || ''}</p>
            `;
            card.addEventListener('click', () => {
              selectUserForUpdate(card);
            });
            container.appendChild(card);
          });
        }
      } catch (error) {
        console.error('Error fetching users:', error);
      }
      // Hide container loader for user cards
      document.getElementById('userCardsLoading').classList.remove('show');
    }

    // When a registered user card is clicked, fill the form for update
    function selectUserForUpdate(card) {
      selectedUserKey = card.getAttribute('data-key');
      selectedUserData = {
        username: card.getAttribute('data-username'),
        fullname: card.getAttribute('data-fullname'),
        position: card.getAttribute('data-position'),
        profile: card.getAttribute('data-profile')
      };

      // Populate the registration form fields with the selected user's data
      document.getElementById('reg-username').value = selectedUserData.username;
      if(selectedUserData.fullname) {
        const { firstName, lastName } = splitFullName(selectedUserData.fullname);
        document.getElementById('reg-firstname').value = firstName;
        document.getElementById('reg-lastname').value = lastName;
      }
      if (selectedUserData.position.toLowerCase() !== 'admin') {
        document.getElementById('reg-position').value = selectedUserData.position;
      }
      if (selectedUserData.profile) {
        document.getElementById('reg-image').setAttribute('data-preview', selectedUserData.profile);
      }
      // Hide the default Register button and show the update actions container
      document.getElementById('registerBtn').style.display = 'none';
      document.getElementById('updateActions').style.display = 'flex';
    }

    // Clear update mode – reset the form fields and buttons
    function clearUpdateMode() {
      selectedUserKey = null;
      selectedUserData = null;
      document.getElementById('registerUserForm').reset();
      document.getElementById('updateActions').style.display = 'none';
      document.getElementById('registerBtn').style.display = 'block';
    }

    // Update the selected user using the form data
    async function handleUpdateUser() {
      if (!selectedUserKey) {
        return showModal('No user selected for update.');
      }
      
      // Show the loading overlay for User Management
      document.getElementById('userMgmtLoading').classList.add('show');

      // Before submission, combine first and last names into the hidden fullname field
      const firstName = document.getElementById('reg-firstname').value.trim();
      const lastName = document.getElementById('reg-lastname').value.trim();
      document.getElementById('reg-fullname-hidden').value = firstName + ' ' + lastName;

      const formData = new FormData(document.getElementById('registerUserForm'));
      formData.append('userKey', selectedUserKey); // send the key to backend for update
      
      try {
        const response = await fetch('../backend/update_user.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if (result.status === 'success') {
          showModal('User updated successfully!');
          clearUpdateMode();
          loadAllUsers();
        } else {
          showModal('Error updating user: ' + result.message);
        }
      } catch (error) {
        console.error('Error updating user:', error);
        showModal('Error updating user.');
      } finally {
        // Hide the loading overlay
        document.getElementById('userMgmtLoading').classList.remove('show');
      }
    }

    // Delete the selected user with User Management container loading overlay
    async function handleDeleteUser() {
      if (!selectedUserKey) {
        return showModal('No user selected for deletion.');
      }
      // Replace native confirm() with our modal confirmation:
      const confirmed = await showConfirmModal('Are you sure you want to delete this user?');
      if (!confirmed) return;
      
      // Show the container-specific loading overlay for User Management
      document.getElementById('userMgmtLoading').classList.add('show');
      
      const formData = new FormData();
      formData.append('userKey', selectedUserKey);
      try {
        const response = await fetch('../backend/delete_user.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if (result.status === 'success') {
          showModal('User deleted successfully!');
          clearUpdateMode();
          loadAllUsers();
        } else {
          showModal('Error deleting user: ' + result.message);
        }
      } catch (error) {
        console.error('Error deleting user:', error);
        showModal('Error deleting user.');
      } finally {
        // Hide the User Management container loader
        document.getElementById('userMgmtLoading').classList.remove('show');
      }
    }

    // Register new user (if no user is selected for update)
    async function handleRegisterUser() {
      // Show the container loading overlay for user management
      document.getElementById('userMgmtLoading').classList.add('show');
      console.log("handleRegisterUser called");
      const formData = new FormData(document.getElementById('registerUserForm'));
      try {
        const response = await fetch('../backend/register_user.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        console.log("Response from register_user.php:", result);
        if (result.status === 'success') {
          showModal('User registered successfully!');
          document.getElementById('registerUserForm').reset();
          loadAllUsers();
        } else {
          showModal('Error registering user: ' + result.message);
        }
      } catch (error) {
        console.error('Error registering user:', error);
        showModal('Error registering user.');
      }
      // Hide the container loading overlay for user management
      document.getElementById('userMgmtLoading').classList.remove('show');
    }
    
  </script>
</body>
</html>
