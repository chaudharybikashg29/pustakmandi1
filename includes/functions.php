<?php
/* ==========================================
   PUSTAKMANDI — includes/functions.php
   Shared helper functions
========================================== */

/**
 * Stores a one-time flash message in the session.
 * Call get_flash() on the next page load to retrieve + clear it.
 *
 * @param string $type    e.g. 'success', 'error', 'info'
 * @param string $message The message text to display
 */
function set_flash($type, $message) {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieves and clears the flash message, if one exists.
 * Use this on the page that displays the message (e.g. login.html/login.php).
 *
 * @return array|null ['type' => ..., 'message' => ...] or null if none set
 */
function get_flash() {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    return null;
}

/**
 * Redirects to a given URL and stops script execution.
 * Must be called before any HTML/output is sent.
 *
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}