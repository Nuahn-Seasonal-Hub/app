<?php
require_once "../config/db.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

// Collect filters
$group = $_GET['group'] ?? '';
$keyword = $_GET['keyword'] ?? '';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$query = "SELECT subject, recipient_group, override, sent_at FROM notifications_log WHERE 1=1";
$params = [];

if ($group) {
    $query .= " AND recipient_group = ?";
    $params[] = $group;
}
if ($keyword) {
    $query .= " AND subject LIKE ?";
    $params[] = "%$keyword%";
}
if ($start && $end) {
    $query .= " AND sent_at BETWEEN ? AND ?";
    $params[] = $start;
    $params[] = $end;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=notifications_export.csv');

$output = fopen('php://output', 'w');

// Write header row
fputcsv($output, ['Subject', 'Recipients', 'Override', 'Sent At']);

// Write data rows
foreach ($logs as $row) {
    fputcsv($output, [
        $row['subject'],
        $row['recipient_group'],
        $row['override'] ? 'Yes' : 'No',
        $row['sent_at']
    ]);
}

fclose($output);
exit;
?>
