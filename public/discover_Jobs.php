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

<main class="wrap page-body" style="padding-top:var(--s6)">
    <div class="band__row mb-4">
        <div>
            <h1 style="font-size:var(--fs-2xl);margin:0">Discover seasonal jobs</h1>
            <p class="muted mb-0"><?= count($jobs) ?> open <?= count($jobs) === 1 ? 'job' : 'jobs' ?> on the map. Tap a pin for details.</p>
        </div>
        <a class="btn btn-outline" href="jobs.php"><?= nu_icon('list-check') ?> List view</a>
    </div>

    <div id="discoverMap" class="card" style="height: min(68vh, 640px); overflow:hidden"></div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        // Initialize map centered on Accra 
        const map = initMap('discoverMap', {
            lat: 5.6037,
            lng: -0.1870,
            zoom: 12
        });
        map.scrollWheelZoom.enable();

        // Use marker clustering for better UX
        const markers = L.markerClusterGroup();
        const jobs = <?= json_encode(array_map(function ($job) {
            return [
                'id' => (int) $job['id'],
                'lat' => (float) $job['location_lat'],
                'lng' => (float) $job['location_lng'],
                'title' => $job['title'],
                'description' => $job['description'],
                'client' => $job['client_name'],
            ];
        }, $jobs), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        jobs.forEach(function (job) {
          const marker = L.marker([job.lat, job.lng]).bindPopup(
            '<strong>' + esc(job.title) + '</strong><br>' + esc(job.description) +
            '<br><em>Posted by: ' + esc(job.client) + '</em><br>' +
            '<a href="jobs.php#job' + job.id + '" class="btn btn-sm btn-primary mt-2">View Job</a>'
          );
          markers.addLayer(marker);
        });

        map.addLayer(markers);
      });
    </script>
</main>

<?php include_once("../includes/footer.php"); ?>
