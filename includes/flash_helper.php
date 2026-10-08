<?php
session_start();

/**
 * Set a flash message
 * @param string $type    Bootstrap type: success, danger, warning, info
 * @param string $message The message text
 * @param string $mode    Mode: 'alert' or 'toast'
 */
function setFlash($type, $message, $mode = 'alert') {
    if ($mode === 'toast') {
        $_SESSION['flash_toast'][$type][] = $message;
    } else {
        $_SESSION['flash_alert'][$type][] = $message;
    }
}
?>
