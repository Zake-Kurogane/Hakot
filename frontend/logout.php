<?php
session_start(); // Continue or start the session

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// if (ini_get("session.use_cookies")) {
//     $params = session_get_cookie_params();
//     setcookie(session_name(), '', time() - 42000,
//         $params["path"], $params["domain"],
//         $params["secure"], $params["httponly"]
//     );
// }

// Redirect to the login page (adjust path as needed)
header("Location: ../index.php");
exit;
