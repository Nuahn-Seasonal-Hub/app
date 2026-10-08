<?php
require_once "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$firstname = htmlspecialchars($_POST['firstname']);
$lastname = htmlspecialchars($_POST['lastname']);
$contact_info = htmlspecialchars($_POST['contact_info']);
$theme_preference = $_POST['theme_preference'] === 'dark' ? 'dark' : 'light';

// Handle photo upload
$photo = null;
if (!empty($_FILES['photo']['name'])) {
    $targetDir = "../uploads/";
    $photo = basename($_FILES["photo"]["name"]);
    $targetFile = $targetDir . $photo;
    move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile);
}

if ($photo) {
    $stmt = $pdo->prepare("UPDATE users SET firstname=?, lastname=?, contact_info=?, photo=?, theme_preference=? WHERE id=?");
    $stmt->execute([$firstname, $lastname, $contact_info, $photo, $theme_preference, $user_id]);
} else {
    $stmt = $pdo->prepare("UPDATE users SET firstname=?, lastname=?, contact_info=?, theme_preference=? WHERE id=?");
    $stmt->execute([$firstname, $lastname, $contact_info, $theme_preference, $user_id]);
}

$_SESSION['theme'] = $theme_preference; // update session immediately
header("Location: ../public/profile.php?success=updated");
?>
