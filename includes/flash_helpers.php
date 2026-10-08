<?php
// includes/flash_helpers.php
// Make sure session_start() is called in init.php before using these helpers.

/**
 * Add a flash message to the session.
 *
 * @param string $type    Bootstrap type: 'success', 'danger', 'warning', 'info', etc.
 * @param string $message The message text to display.
 * @param string $mode    'alert' (default) or 'toast'.
 */
function addFlash($type, $message, $mode = 'alert') {
    $key = $mode === 'toast' ? 'flash_toast' : 'flash_alert';
    if (!isset($_SESSION[$key][$type])) {
        $_SESSION[$key][$type] = [];
    }
    $_SESSION[$key][$type][] = $message;
}

/**
 * Convenience wrappers for common flash types.
 */
function flashSuccess($message, $mode = 'alert') {
    addFlash('success', $message, $mode);
}

function flashError($message, $mode = 'alert') {
    addFlash('danger', $message, $mode);
}

function flashWarning($message, $mode = 'alert') {
    addFlash('warning', $message, $mode);
}

function flashInfo($message, $mode = 'alert') {
    addFlash('info', $message, $mode);
}