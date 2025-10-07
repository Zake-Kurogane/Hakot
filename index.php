<?php
session_start();
// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: frontend/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hakot | Login</title>
  <link rel="icon" type="image/x-icon" href="frontend/img/hakot-icon.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Bootstrap (for modal) -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <style>
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

    /* Error states (force red border) */
    .border-red-500 {
      border-color: #dc3545 !important; /* Force red border */
    }

    /* Local loading overlay inside the card */
    #loadingOverlay {
      position: absolute;
      top: 0; 
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.8);
      display: none; /* Hidden by default */
      justify-content: center;
      align-items: center;
      z-index: 10; /* Above card content but below modals */
      transition: opacity 0.3s ease-in-out;
    }
    #loadingOverlay.show {
      display: flex;
    }
  </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  <!-- flex-col md:flex-row for responsiveness on small screens -->
  <div class="w-full max-w-5xl flex flex-col md:flex-row">

    <!-- Left Section -->
    <div class="w-full md:w-1/2 p-12 flex items-center justify-center">
      <img src="frontend/img/truck-image.png" alt="Hakot Truck" class="w-3/4">
    </div>

    <!-- Right Section (login form card) -->
    <div class="w-full md:w-1/2 p-12 bg-white relative">
      <!-- 1) Loading overlay is now INSIDE this card container -->
      <div id="loadingOverlay">
        <div class="spinner-border text-success" role="status" style="width:3rem; height:3rem;">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <div class="text-left mb-9">
        <img src="frontend/img/hakot-new.png" alt="Hakot Logo" class="mx-auto w-36">
        <h1 class="text-3xl font-bold mt-6">Welcome <span class="text-green-500">Chief!</span></h1>
        <p class="text-gray-600 mt-4">I hope you have a good stay and enjoy exploring the application.</p>
      </div>

      <!-- FORM -->
      <form id="loginForm">
        <!-- USERNAME -->
        <div class="mb-6 relative">
          <div
            class="flex items-center justify-start gap-2 border border-gray-400 rounded-md px-5 py-3 focus-within:ring-2 focus-within:ring-green-500"
            id="usernameWrapper"
          >
            <!-- Person Icon -->
            <svg 
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              fill="currentColor"
              class="bi bi-person-fill text-gray-500 mr-3"
              viewBox="0 0 16 16"
            >
              <path d="M3 14s-1 0-1-1 1-4 6-4 
                       6 3 6 4-1 1-1 1zm5-6a3 3 
                       0 1 0 0-6 3 3 0 0 0 0 6"/>
            </svg>
            <input 
              type="text" 
              id="username" 
              name="username" 
              placeholder="Enter your username" 
              class="w-full focus:outline-none"
            />
          </div>
        </div>

        <!-- PASSWORD -->
        <div class="mb-6 relative">
          <div
            class="flex items-center justify-start gap-2 border border-gray-400 rounded-md px-5 py-3 focus-within:ring-2 focus-within:ring-green-500"
            id="passwordWrapper"
          >
            <!-- Lock Icon -->
            <svg 
              xmlns="http://www.w3.org/2000/svg" 
              width="16"
              height="16"
              fill="currentColor"
              class="bi bi-lock-fill text-gray-500 mr-3"
              viewBox="0 0 16 16"
            >
              <path d="M8 1a2 2 0 0 1 2 
                       2v4H6V3a2 2 0 0 1 
                       2-2m3 6V3a3 3 0 0 0-6 
                       0v4a2 2 0 0 0-2 
                       2v5a2 2 0 0 0 2 
                       2h6a2 2 0 0 0 2-2V9a2 
                       2 0 0 0-2-2"/>
            </svg>
            <input 
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              class="w-full focus:outline-none"
            />
          </div>
        </div>

        <br/>
        <button 
          type="submit"
          class="w-full bg-green-500 text-white py-3 rounded-md hover:bg-green-600 transition"
        >
          Log in
        </button>
      </form>
    </div>
  </div>

  <!-- ERROR/SUCCESS MODAL -->
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
          <!-- We'll dynamically switch this title between "Error" / "Success" in JS -->
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

  <!-- Bootstrap JS -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
    defer
  ></script>

  <script>
    const loginForm        = document.getElementById('loginForm');
    const modalTitle       = document.getElementById('errorModalLabel');
    const modalBody        = document.getElementById('errorMessage');
    const modalEl          = document.getElementById('errorModal');
    const usernameInput    = document.getElementById('username');
    const passwordInput    = document.getElementById('password');
    const usernameWrapper  = document.getElementById('usernameWrapper');
    const passwordWrapper  = document.getElementById('passwordWrapper');

    // LOADING overlay inside the card
    const loadingOverlay   = document.getElementById('loadingOverlay');

    let infoModal;

    document.addEventListener('DOMContentLoaded', () => {
      infoModal = new bootstrap.Modal(modalEl);
    });

    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault(); // Prevent normal form submission

      // Clear any previous error states
      clearErrorState();

      const username = usernameInput.value.trim();
      const password = passwordInput.value.trim();

      if (!username || !password) {
        setErrorState('Please enter both username and password.');
        return;
      }

      // Show loading (only inside the card)
      showLoading(true);

      try {
        // Send login request to backend
        const response = await fetch('backend/fetch_login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password })
        });
        const result = await response.json();

        if (result.status === 'success') {
          // Show success modal
          showModal('Success', `Welcome ${result.data.name || 'Chief'}! Redirecting...`);

          if (result.session_id) {
            localStorage.setItem('sessionID', result.session_id);
          }

          // 1) Automatic redirect after 1 seconds
          setTimeout(() => {
            window.location.href = 'frontend/dashboard.php';
          }, 1000);

          // 2) Also allow closing the modal manually, then redirect
          modalEl.addEventListener('hidden.bs.modal', () => {
            window.location.href = 'frontend/dashboard.php';
          }, { once: true });

        } else {
          // Error case (invalid user or password)
          setErrorState(result.message);
        }
      } catch (err) {
        console.error(err);
        setErrorState('An error occurred while trying to log in.');
      } finally {
        // Hide the loading overlay after fetch completes
        showLoading(false);
      }
    });

    // Show the modal with a given title/message
    function showModal(title, message) {
      modalTitle.textContent = title;
      if (title === 'Success') {
        modalTitle.classList.remove('text-danger');
        modalTitle.classList.add('text-success');
      } else {
        modalTitle.classList.remove('text-success');
        modalTitle.classList.add('text-danger');
      }
      modalBody.textContent = message;
      infoModal.show();
    }

    // Highlight the input boxes in red + show error modal
    function setErrorState(msg) {
      usernameWrapper.classList.add('border-red-500');
      passwordWrapper.classList.add('border-red-500');
      usernameInput.classList.add('border-red-500');
      passwordInput.classList.add('border-red-500');
      showModal('Error', msg);
    }

    // Remove any error classes
    function clearErrorState() {
      usernameWrapper.classList.remove('border-red-500');
      passwordWrapper.classList.remove('border-red-500');
      usernameInput.classList.remove('border-red-500');
      passwordInput.classList.remove('border-red-500');
    }

    // Show/hide the loading overlay (only in the card)
    function showLoading(isLoading) {
      if (isLoading) {
        loadingOverlay.classList.add('show');
      } else {
        loadingOverlay.classList.remove('show');
      }
    }
  </script>

</body>
</html>
