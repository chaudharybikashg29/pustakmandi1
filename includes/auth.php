<?php
/* ==========================================
   PUSTAKMANDI — includes/auth.php
   Authentication helper functions
========================================== */

/**
 * Logs the current user out by destroying their session.
 * Safe to call whether or not a session is currently active.
 */
function logout_user() {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Clear all session variables
    $_SESSION = [];

    // Delete the session cookie itself (if using cookie-based sessions)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy the session data on the server
    session_destroy();
}