<?php
require_once "../config/db.php";
require_once "../includes/flash_helper.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'];
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $price = floatval($_POST['price']);
    $deadline = $_POST['deadline'] ?: null;
    $latitude = $_POST['latitude'] ?: null;
    $longitude = $_POST['longitude'] ?: null;

    // Handle image upload
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/jobs/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $image_path = $targetDir . time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    $stmt = $pdo->prepare("INSERT INTO jobs (user_id, title, description, price, deadline, latitude, longitude, image_path) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $description, $price, $deadline, $latitude, $longitude, $image_path]);

    setFlash("success", "Job posted successfully!", "toast");
    header("Location: ../public/jobs/index.php");
}
?>
