<?php
// public/discover_jobs.php
require_once("../config/init.php");
include_once("../includes/header.php");                                                                                  

// Both clients and providers can view this page
requireLogin(); 

// Fetch all posted jobs
$stmt = $pdo->prepare("
    SELECT jobs.*, users.name AS client_name
    FROM jobs
    JOIN users ON jobs.client_id = users.id
    WHERE jobs.status = 'posted'
    ORDER BY jobs.created_at DESC
");
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container-fluid py-5">
    <h2 class="text-center mb-4">Discover Seasonal Jobs</h2>

    <div id="discoverMap" style="height: 600px;"></div>

    <script>
      // Initialize map centered on Accra 
      const map = initMap('discoverMap', {
          lat: 5.6037,
          lng: -0.1870,
          zoom: 12
      });

      // Use marker clustering for better UX
      const markers = L.markerClusterGroup();

      <?php foreach ($jobs as $job): ?>
        const marker<?= $job['id'] ?> = L.marker([<?= $job['location_lat'] ?>, <?= $job['location_lng'] ?>])
          .bindPopup(`
            <strong><?= htmlspecialchars($job['title']) ?></strong><br>
            <?= htmlspecialchars($job['description']) ?><br>
            <em>Posted by: <?= htmlspecialchars($job['client_name']) ?></em><br>
            <a href="jobs.php#job<?= $job['id'] ?>" class="btn btn-sm btn-primary mt-2">View Job</a>
          `);
        markers.addLayer(marker<?= $job['id'] ?>);
      <?php endforeach; ?>

      map.addLayer(markers);
    </script>
</main>

<?php include_once("../includes/footer.php"); ?>
