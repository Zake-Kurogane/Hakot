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
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Hakot | User Announcement</title>
  <link rel="icon" type="image/x-icon" href="img/hakot-icon.png">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" type="text/css" href="navs.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <!-- Bootstrap Icons CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <style>
    body {
      font-family: "Poppins", sans-serif;
      background-color: #f9f9f9;
      margin: 0;
    }
    /* Global Loading Screen (if needed later) */
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
    /* Container-level Loading Overlay */
    .container-loading {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.8);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10;
      display: none;
    }
    .container-loading .spinner-border {
      width: 2rem;
      height: 2rem;
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
    /* Offcanvas Sidebar */
    .offcanvas.offcanvas-start { width: 250px; }
    /* Main Content Container */
    .content {
      padding-top: 80px;
      padding-left: 20px;
      padding-right: 20px;
      margin-left: 250px;
    }
    @media (max-width: 992px) {
      .content { margin-left: 0; }
    }
    /* Card styling */
    .card {
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    /* Fixed card body height with relative position for overlay */
    .col-lg-7 .card .card-body,
    .col-lg-5 .card .card-body {
      height: 500px;
      position: relative;
    }

    .scrollable-table {
      height: calc(100% - 120px);
      overflow-y: auto;
    }
    .scrollable-table::-webkit-scrollbar {
      width: 8px;
    }
    .scrollable-table::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 4px;
    }
    .scrollable-table::-webkit-scrollbar-thumb {
      background: #32CD32;
      border-radius: 4px;
    }
    .scrollable-table::-webkit-scrollbar-thumb:hover {
      background: #32CD32;
    }
    .btn-group .btn {
      border-radius: 0.375rem !important;
    }
  </style>
</head>
<body>

  <!-- Offcanvas Sidebar -->
  <div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
    <div class="offcanvas-header d-lg-none">
      <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Menu</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
      <img src="img/hakot-new.png" alt="HAKOT Logo" style="height:120px; width:120px; margin-bottom: 10px;">
      <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
      <a href="tracker.php"><i class="fas fa-map-marker-alt"></i> Tracker</a>
      <a href="schedule.php"><i class="fas fa-calendar-alt"></i> Truck Schedules</a>
      <a href="user_announcement.php" class="active"><i class="fa-solid fa-bullhorn"></i> User Announcement</a>
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

  <!-- Message Modal (for success/info) -->
  <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-success" id="messageModalLabel">Message</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p id="messageModalText"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Confirmation Modal (for Delete and Push) -->
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmModalLabel">Confirm Action</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p id="confirmModalMessage">Are you sure you want to proceed?</p>
        </div>
        <div class="modal-footer">
          <button type="button" id="confirmModalYes" class="btn btn-success"><i class="fa-solid fa-square-check"></i> Yes</button>
          <button type="button" id="confirmModalNo" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-square-xmark"></i> No</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Error Modal (for errors) -->
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

  <!-- Main Content -->
  <div class="content">
    <div class="row">
      <!-- Left Column: User Announcement Form -->
      <div class="col-lg-5 col-md-12 mb-4">
        <div class="card">
          <div class="card-body">
            <!-- Container overlay for the form -->
            <div class="container-loading" id="formLoadingOverlay">
              <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            <h2>User Announcement</h2>
            <form id="announcementForm" enctype="multipart/form-data">
              <div class="mb-3">
                <label for="header" class="form-label">Header</label>
                <input type="text" class="form-control" id="header" name="header" value="Hakot Basura - Announcement" required>
              </div>
              <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
              </div>
              <div class="mb-3">
                <label for="image" class="form-label">Upload Image (Optional)</label>
                
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                <div class="form-label" style="font-size: 75%; color: red">Note: When you upload an image, it will be displayed in the user app highlights.</div>
              </div>
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="pushToUserUID" name="pushToUserUID">
                <label class="form-check-label" for="pushToUserUID">Push to All Users</label>
              </div>
              <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-arrow-up-from-bracket"></i> Post Announcement</button>
            </form>
            <!-- Inline message placeholder -->
            <div id="announcementResponse" class="mt-3"></div>
          </div>
        </div>
      </div>

      <!-- Right Column: Announcements List -->
      <div class="col-lg-7 col-md-12">
        <div class="card">
          <div class="card-body">
            <!-- Container overlay for announcements list -->
            <div class="container-loading" id="announcementsLoadingOverlay">
              <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            <h2>Announcements List</h2>
            <!-- Search input (optional) -->
            <div class="d-flex justify-content-end mb-3">
              <input type="text" id="announcementSearchInput" class="form-control" placeholder="Search announcements..." style="max-width:300px;">
            </div>
            <!-- Wrap the table in a scrollable container -->
            <div class="table-responsive scrollable-table">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Header</th>
                    <th>Message</th>
                    <th>Image</th>
                    <th>Timestamp</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="announcementsTableBody">
                  <!-- Announcements will be dynamically inserted here -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editAnnouncementForm" enctype="multipart/form-data">
      <div class="modal-content" style="position: relative;">
        <!-- Loading overlay for edit modal -->
        <div class="container-loading" id="editLoadingOverlay" style="position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(255,255,255,0.8); display: none; z-index: 1000; justify-content: center; align-items: center;">
          <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div class="modal-header">
          <h5 class="modal-title" id="editAnnouncementModalLabel">Edit Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editAnnouncementKey" name="announcementKey">
          <div class="mb-3">
            <label for="editHeader" class="form-label">Header</label>
            <input type="text" class="form-control" id="editHeader" name="header" required>
          </div>
          <div class="mb-3">
            <label for="editMessage" class="form-label">Message</label>
            <textarea class="form-control" id="editMessage" name="message" rows="4" required></textarea>
          </div>
          <div class="mb-3">
            <label for="editImage" class="form-label">Upload New Image (Optional)</label>
            <input type="file" class="form-control" id="editImage" name="image" accept="image/*">
            <small class="form-text text-muted">Leave blank to keep existing image.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-square-xmark"></i> Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>


  <!-- Bootstrap & JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Utility: Show confirmation modal
    function showConfirmation(message, callback) {
      const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
      document.getElementById('confirmModalMessage').textContent = message;
      
      const yesBtn = document.getElementById('confirmModalYes');
      // Remove previous click listeners by replacing the button with a clone
      yesBtn.replaceWith(yesBtn.cloneNode(true));
      const newYesBtn = document.getElementById('confirmModalYes');
      newYesBtn.addEventListener('click', function() {
        confirmModal.hide();
        callback(true);
      });
      confirmModal.show();
    }

    // Utility: Show error modal
    function showErrorModal(message) {
      document.getElementById('errorMessage').textContent = message;
      new bootstrap.Modal(document.getElementById('errorModal')).show();
    }

    // Utility: Show message modal for success/info
    function showMessageModal(message) {
      document.getElementById('messageModalText').textContent = message;
      new bootstrap.Modal(document.getElementById('messageModal')).show();
    }

    // Load user data (username/profile image)
    async function fetchUserData() {
      try {
        const response = await fetch('../backend/fetch_users.php');
        const data = await response.json();
        if (data.status === 'success') {
          document.getElementById('dropdownUsername').textContent = data.name || 'Unknown';
          if (data.profile_image) {
            document.getElementById('profileImg').src = data.profile_image;
          }
        }
      } catch (error) {
        console.error('Error fetching user data:', error);
      }
    }
    fetchUserData();

    // Announcement Form Submission (Create)
    document.getElementById('announcementForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append('action', 'create');
      // Show the form container overlay
      document.getElementById('formLoadingOverlay').style.display = 'flex';
      try {
        const response = await fetch('../backend/announcement_crud.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        // Hide the form overlay
        document.getElementById('formLoadingOverlay').style.display = 'none';
        if (result.status === 'success') {
          showMessageModal(result.message);
          document.getElementById('announcementForm').reset();
          fetchAnnouncements();
        } else {
          showErrorModal(result.message);
        }
      } catch (error) {
        document.getElementById('formLoadingOverlay').style.display = 'none';
        showErrorModal('An error occurred. Please try again.');
        console.error('Error posting announcement:', error);
      }
    });

    // Fetch Announcements and populate table (sorted from latest to least)
    async function fetchAnnouncements() {
      // Show the announcements container overlay
      document.getElementById('announcementsLoadingOverlay').style.display = 'flex';
      try {
        const response = await fetch('../backend/fetch_announcements.php');
        const data = await response.json();
        const tbody = document.getElementById('announcementsTableBody');
        tbody.innerHTML = '';
        if (data.status === 'success' && data.announcements) {
          // Convert announcements object to an array, sort by timestamp descending
          const sortedAnnouncements = Object.entries(data.announcements).sort((a, b) => {
            return b[1].timestamp - a[1].timestamp;
          });
          sortedAnnouncements.forEach(([key, announcement]) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${announcement.header}</td>
              <td>${announcement.message}</td>
              <td>${announcement.image ? `<img src="${announcement.image}" alt="img" style="max-width:80px;">` : 'No Image'}</td>
              <td>${new Date(announcement.timestamp * 1000).toLocaleString()}</td>
              <td>
                <div class="btn-group" role="group" aria-label="Actions">
                  <button class="btn btn-sm btn-warning me-1" onclick="confirmPush('${key}')"><i class="fa-solid fa-bell"></i></button>
                  <button class="btn btn-sm btn-info me-1" onclick="openEditModal('${key}', \`${announcement.header}\`, \`${announcement.message}\`)"><i class="fa-solid fa-pen-to-square"></i></button>
                  <button class="btn btn-sm btn-danger" onclick="confirmDelete('${key}')"><i class="fa-solid fa-trash-can"></i></button>
                </div>
              </td>
            `;
            tbody.appendChild(tr);
          });
        } else {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center">No announcements found.</td></tr>';
        }
        // Hide the announcements overlay
        document.getElementById('announcementsLoadingOverlay').style.display = 'none';
      } catch (error) {
        document.getElementById('announcementsLoadingOverlay').style.display = 'none';
        console.error('Error fetching announcements:', error);
      }
    }
    fetchAnnouncements();

    // Filter announcements on search
    document.getElementById('announcementSearchInput').addEventListener('input', function() {
      const query = this.value.toLowerCase();
      document.querySelectorAll('#announcementsTableBody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(query) ? '' : 'none';
      });
    });

    // Open Edit Modal and populate fields
    function openEditModal(key, header, message) {
      document.getElementById('editAnnouncementKey').value = key;
      document.getElementById('editHeader').value = header;
      document.getElementById('editMessage').value = message;
      new bootstrap.Modal(document.getElementById('editAnnouncementModal')).show();
    }

    // Handle Edit Announcement form submission
   // Handle Edit Announcement form submission
document.getElementById('editAnnouncementForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append('action', 'update');
  
  // Show the loading overlay for the edit modal
  document.getElementById('editLoadingOverlay').style.display = 'flex';
  
  try {
    const response = await fetch('../backend/announcement_crud.php', {
      method: 'POST',
      body: formData
    });
    const result = await response.json();
    
    // Hide the loading overlay once done
    document.getElementById('editLoadingOverlay').style.display = 'none';
    
    if (result.status === 'success') {
      bootstrap.Modal.getInstance(document.getElementById('editAnnouncementModal')).hide();
      showMessageModal(result.message);
      fetchAnnouncements();
    } else {
      showErrorModal(result.message);
    }
  } catch (error) {
    document.getElementById('editLoadingOverlay').style.display = 'none';
    showErrorModal('An error occurred. Please try again.');
    console.error('Error updating announcement:', error);
  }
});


    
    // Delete announcement (with confirmation)
    function confirmDelete(key) {
      showConfirmation("Are you sure you want to delete this announcement?", function(confirmed) {
        if (confirmed) deleteAnnouncement(key);
      });
    }

    async function deleteAnnouncement(key) {
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('announcementKey', key);
      // Using the announcements overlay for deletion process
      document.getElementById('announcementsLoadingOverlay').style.display = 'flex';
      try {
        const response = await fetch('../backend/announcement_crud.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        document.getElementById('announcementsLoadingOverlay').style.display = 'none';
        if (result.status === 'success') {
          showMessageModal(result.message);
          fetchAnnouncements();
        } else {
          showErrorModal(result.message);
        }
      } catch (error) {
        document.getElementById('announcementsLoadingOverlay').style.display = 'none';
        showErrorModal('An error occurred. Please try again.');
        console.error('Error deleting announcement:', error);
      }
    }

    // Push announcement (with confirmation)
    function confirmPush(key) {
      showConfirmation("Are you sure you want to push this announcement to all users?", function(confirmed) {
        if (confirmed) pushAnnouncement(key);
      });
    }

    async function pushAnnouncement(key) {
      const formData = new FormData();
      formData.append('action', 'push');
      formData.append('announcementKey', key);
      // Using the announcements overlay for pushing process
      document.getElementById('announcementsLoadingOverlay').style.display = 'flex';
      try {
        const response = await fetch('../backend/announcement_crud.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        document.getElementById('announcementsLoadingOverlay').style.display = 'none';
        if(result.status === 'success'){
          showMessageModal(result.message);
        } else {
          showErrorModal(result.message);
        }
      } catch (error) {
        document.getElementById('announcementsLoadingOverlay').style.display = 'none';
        console.error('Error pushing announcement:', error);
        showErrorModal('An error occurred. Please try again.');
      }
    }
  </script>
</body>
</html>
