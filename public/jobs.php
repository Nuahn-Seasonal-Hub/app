<?php
// public/jobs.php - Seasonal Jobs Page
require_once("../config/init.php");
include_once("../includes/header.php");

$isClient   = isRole('client');
$isProvider = isRole('provider');

$isClient   = isRole('client');
$isProvider = isRole('provider');

$provider_id = null;
if ($isProvider && isset($_SESSION['user_id'])) {
    $provider_id = $_SESSION['user_id'];
}


// Handle job posting (Clients only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_job'])) {
    if (!$isClient) {
        header("Location: ../login.php?error=unauthorized");
        exit;
    }

    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $lat         = $_POST['location_lat'];
    $lng         = $_POST['location_lng'];
    $address     = $_POST['location_address'] ?? null;
    $client_id   = $_SESSION['user_id'];
	$payment_amount = $_POST['payment_amount'];


   
   // Handle image upload with validation
$imagePath = null;
if (!empty($_FILES['job_image']['name'])) {
    $allowedTypes = ['image/jpeg', 'image/png'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    $fileType = $_FILES['job_image']['type'];
    $fileSize = $_FILES['job_image']['size'];

    if (!in_array($fileType, $allowedTypes)) {
        flashError("Only JPG and PNG images are allowed.");
        header("Location: jobs.php?error=invalid_image");
        exit;
    }

    if ($fileSize > $maxSize) {
        flashError("Image must be smaller than 2MB.");
        header("Location: jobs.php?error=image_too_large");
        exit;
    }

    $uploadDir = "../uploads/jobs/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "_", basename($_FILES['job_image']['name']));
    $targetFile = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['job_image']['tmp_name'], $targetFile)) {
        $imagePath = $filename;
    } else {
        flashError("Failed to upload image.");
        header("Location: jobs.php?error=upload_failed");
        exit;
    }
}


$stmt = $pdo->prepare("INSERT INTO jobs 
    (client_id, title, description, location_lat, location_lng, location_address, image, payment_amount, status, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
$stmt->execute([$client_id, $title, $description, $lat, $lng, $address, $imagePath, $payment_amount]);

    $job_id = $pdo->lastInsertId();

    // Audit log
    $log = $pdo->prepare("INSERT INTO audit_logs (action, target_user_id, job_id, created_at) VALUES (?, ?, ?, NOW())");
    $log->execute(["Job posted", $client_id, $job_id]);

    header("Location: jobs.php?success=job_posted");
    exit;
}


// Available jobs (same query as before, executed before rendering)
$query = "
    SELECT jobs.*, users.name AS client_name,
           (SELECT COUNT(*) FROM applications 
            WHERE applications.job_id = jobs.id 
              AND applications.provider_id = ?) AS applied_count
    FROM jobs
    JOIN users ON jobs.client_id = users.id
    WHERE jobs.status = 'posted'
";

$params = [$provider_id]; // add provider_id to params

if (!empty($_GET['keyword'])) {
    $query .= " AND (jobs.title LIKE ? OR jobs.description LIKE ?)";
    $keyword = "%" . $_GET['keyword'] . "%";
    $params[] = $keyword;
    $params[] = $keyword;
}

if (!empty($_GET['location'])) {
    $query .= " AND users.city LIKE ?";
    $params[] = "%" . $_GET['location'] . "%";
}

$query .= " ORDER BY jobs.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$filtered = !empty($_GET['keyword']) || !empty($_GET['location']);
?>

<section class="band">
  <div class="wrap band__row">
    <div>
      <span class="eyebrow"><?= nu_icon('briefcase-fill') ?> Marketplace</span>
      <h1>Seasonal jobs</h1>
      <p><?= $isClient ? 'Post a new job or see what else is open right now.' : 'Short-term work near you. Save the ones you like and apply in a tap.' ?></p>
    </div>
    <?php if ($isProvider): ?>
      <a class="btn btn-glass" href="discover_Jobs.php"><?= nu_icon('map') ?> Map view</a>
    <?php elseif (!isset($_SESSION['user_id'])): ?>
      <a class="btn btn-light" href="register.php"><?= nu_icon('stars') ?> Join to apply</a>
    <?php endif; ?>
  </div>
</section>

<main class="wrap page-body">
<?php if (isset($_GET['error'])): ?>
  <div class="alert alert-danger">
    <?= nu_icon('x-circle') ?>
    <span>
    <?php if ($_GET['error'] === 'invalid_location'): ?>
      Please select a valid location on the map.
    <?php elseif ($_GET['error'] === 'invalid_coordinates'): ?>
      Invalid latitude/longitude values.
    <?php elseif ($_GET['error'] === 'invalid_image'): ?>
      Only JPG and PNG images are allowed.
    <?php elseif ($_GET['error'] === 'image_too_large'): ?>
      Image must be smaller than 2MB.
    <?php elseif ($_GET['error'] === 'upload_failed'): ?>
      Failed to upload image.
    <?php elseif ($_GET['error'] === 'job_not_found'): ?>
      That job is no longer available.
    <?php else: ?>
      Something went wrong. Please try again.
    <?php endif; ?>
    </span>
  </div>
<?php endif; ?>
<?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success"><?= nu_icon('check-circle-fill') ?>
    <?= $_GET['success'] === 'job_posted' ? 'Your job was submitted. It will appear here once approved.' : ($_GET['success'] === 'applied' ? 'Application sent! Track it under Applied.' : 'Done.') ?>
  </div>
<?php endif; ?>

    <!-- Filter/Search Bar (Providers only) -->
    <?php if ($isProvider): ?>
    <form method="GET" action="jobs.php" class="searchbar" role="search" data-reveal>
        <label class="input-icon"><?= nu_icon('search') ?><span class="sr-only">Keyword</span>
            <input type="text" name="keyword" class="input" placeholder="Search by title or description" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
        </label>
        <label class="input-icon"><?= nu_icon('geo-alt') ?><span class="sr-only">Location</span>
            <input type="text" name="location" class="input" placeholder="City" value="<?= htmlspecialchars($_GET['location'] ?? '') ?>">
        </label>
        <button type="submit" class="btn btn-primary btn-lg"><?= nu_icon('funnel') ?> Filter</button>
        <?php if ($filtered): ?><a href="jobs.php" class="btn btn-ghost btn-lg">Reset</a><?php endif; ?>
    </form>
    <?php endif; ?>

    <!-- Job Posting Form (Clients only) -->
    <?php if ($isClient): ?>
    <details class="card panel" id="post" data-reveal>
        <summary>
            <span class="tile__icon"><?= nu_icon('plus-lg') ?></span>
            <span><span class="tile__title" style="display:block">Post a seasonal job</span><span class="tile__text">Title, location pin, photo and pay. Takes about a minute.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </summary>
        <div class="panel__body">
<form method="POST" action="jobs.php" enctype="multipart/form-data" id="postJobForm">
  <div class="form-grid">
    <label class="field span-2"><span class="label">Job title</span>
        <input type="text" name="title" class="input" placeholder="e.g. Harvest helpers for the weekend" required>
    </label>
    <label class="field span-2"><span class="label">Description</span>
        <textarea name="description" class="input" rows="3" placeholder="What needs doing, when, and anything to bring" required></textarea>
    </label>

    <!-- Map for selecting location -->
    <div class="field span-2">
        <span class="label">Location <span class="muted" style="font-weight:500">— tap the map to drop a pin</span></span>
        <div id="jobMap" class="picker-map"></div>

        <!-- Hidden fields populated by JS -->
        <input type="hidden" name="location_lat" id="location_lat" required>
        <input type="hidden" name="location_lng" id="location_lng" required>
        <span class="input-icon mt-2" style="display:block"><?= nu_icon('geo-alt') ?>
        <input type="text" name="location_address" id="location_address" 
               class="input" placeholder="Nearest address appears here" readonly></span>
        <p class="hint is-hidden" id="locHint" style="color:var(--danger)">Please tap the map to choose the job location.</p>
    </div>

    <label class="field"><span class="label">Job image</span>
        <input type="file" name="job_image" id="job_image" class="input" accept="image/*">
        <img id="preview" src="data:," alt="Image preview" class="preview-img d-none">
    </label>
    <label class="field"><span class="label">Payment amount (CND)</span>
        <span class="input-icon"><?= nu_icon('cash-coin') ?><input type="number" step="0.01" name="payment_amount" class="input" placeholder="0.00" required></span>
    </label>
  </div>
    <button type="submit" name="post_job" class="btn btn-primary btn-lg"><?= nu_icon('send') ?> Post job</button>
</form>
        </div>
    </details>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var panel = document.getElementById('post');
    var jobMap = null;
    function ensureMap() {
      if (jobMap || typeof initMap !== 'function') { if (jobMap) jobMap.invalidateSize(); return; }
      jobMap = initMap('jobMap', {
          selectable: true,
          onSelect: function(lat, lng) {
              document.getElementById('location_lat').value = lat;
              document.getElementById('location_lng').value = lng;
              document.getElementById('locHint').classList.add('is-hidden');

              reverseGeocode(lat, lng, function(address) {
                  document.getElementById('location_address').value = address;
              });
          }
      });
      setTimeout(function () { jobMap.invalidateSize(); }, 300);
    }
    if (location.hash === '#post') panel.open = true;
    window.addEventListener('hashchange', function () { if (location.hash === '#post') { panel.open = true; ensureMap(); } });
    if (panel.open) ensureMap();
    panel.addEventListener('toggle', function () { if (panel.open) ensureMap(); });

    // Require a map pin before submitting (hidden inputs are not validated by the browser)
    document.getElementById('postJobForm').addEventListener('submit', function (e) {
      if (!document.getElementById('location_lat').value) {
        e.preventDefault();
        document.getElementById('locHint').classList.remove('is-hidden');
        document.getElementById('jobMap').scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });

    // Image preview
    document.getElementById('job_image').addEventListener('change', function(event) {
        var preview = document.getElementById('preview');
        var file = event.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) { preview.src = e.target.result; preview.classList.remove('d-none'); };
            reader.readAsDataURL(file);
        } else {
            preview.src = "data:,";
            preview.classList.add('d-none');
        }
    });
  });
</script>
    <?php endif; ?>

    <!-- Available Jobs List -->
    <div class="result-meta">
        <h2><?= count($jobs) ?> open <?= count($jobs) === 1 ? 'job' : 'jobs' ?><?= $filtered ? ' found' : '' ?></h2>
        <span class="chip chip--blue chip--plain"><?= nu_icon('lightning-charge-fill') ?> Newest first</span>
    </div>

    <?php if (empty($jobs)): ?>
        <?php nu_empty('inbox', $filtered ? 'No jobs match your search' : 'No open jobs right now', $filtered ? 'Try a different keyword or clear the filters.' : 'New seasonal jobs are posted all the time. Check back soon.', $filtered ? '<a class="btn btn-soft" href="jobs.php">Clear filters</a>' : ''); ?>
    <?php else: ?>
    <div class="grid grid-2 grid-jobs">
        <?php foreach ($jobs as $n => $job):
            $foot = '';
            if ($isProvider) {
                if ($job['applied_count'] > 0) {
                    $foot = '<span class="chip chip--success chip--plain">' . nu_icon('check2') . ' Applied</span>';
                } else {
                    $foot = '<form method="POST" action="../actions/save_job.php" class="inline-form">'
                          . '<input type="hidden" name="job_id" value="' . htmlspecialchars($job['id']) . '">'
                          . '<button type="submit" class="btn btn-outline btn-sm">' . nu_icon('bookmark') . ' Save</button></form>'
                          . '<form method="POST" action="../actions/accept_job.php" class="inline-form">'
                          . '<input type="hidden" name="job_id" value="' . htmlspecialchars($job['id']) . '">'
                          . '<button type="submit" class="btn btn-primary btn-sm">Apply ' . nu_icon('arrow-right') . '</button></form>';
                }
            } elseif (!isset($_SESSION['user_id'])) {
                $foot = '<a class="btn btn-primary btn-sm" href="login.php">Sign in to apply</a>';
            }
            nu_job_card($job, ['map' => true, 'footer' => $foot, 'i' => $n]);
        endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<?php include_once("../includes/footer.php"); ?>
