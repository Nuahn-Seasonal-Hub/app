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

?>

<main class="container py-5">
    <h2 class="text-center mb-4">Seasonal Jobs</h2>
<?php if (isset($_GET['error'])): ?>
  <div class="alert alert-danger text-center">
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
    <?php endif; ?>
  </div>
<?php endif; ?>

    <!-- Filter/Search Bar (Providers only) -->
    <?php if ($isProvider): ?>
    <form method="GET" action="jobs.php" class="row mb-4">
        <div class="col-md-4">
            <input type="text" name="keyword" class="form-control" placeholder="Search by title or description" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="location" class="form-control" placeholder="Filter by location (city)" value="<?= htmlspecialchars($_GET['location'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <a href="jobs.php" class="btn btn-secondary w-100">Reset</a>
        </div>
    </form>
    <?php endif; ?>

    <!-- Job Posting Form (Clients only) -->
    <?php if ($isClient): ?>
    <div class="card mb-5 shadow">
        <div class="card-header bg-primary text-white">Post a Seasonal Job</div>
        <div class="card-body">
<form method="POST" action="jobs.php" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label">Job Title</label>
        <input type="text" name="title" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3" required></textarea>
    </div>

    <!-- Map for selecting location -->
    <div class="mb-3">
        <label class="form-label">Select Location</label>
        <div id="jobMap" style="height: 400px;"></div>

        <!-- Hidden fields populated by JS -->
        <input type="hidden" name="location_lat" id="location_lat" required>
        <input type="hidden" name="location_lng" id="location_lng" required>
        <input type="text" name="location_address" id="location_address" 
               class="form-control mt-2" placeholder="Nearest address" readonly>
    </div>

    <div class="mb-3">
        <label class="form-label">Job Image</label>
        <input type="file" name="job_image" id="job_image" class="form-control" accept="image/*">
        <div class="mt-3">
            <img id="preview" src="#" alt="Image Preview" class="img-fluid d-none" style="max-height: 200px;">
        </div>
    </div>
<div class="mb-3">
    <label class="form-label">Payment Amount (CND)</label>
    <input type="number" step="0.01" name="payment_amount" class="form-control" required>
</div>

    <button type="submit" name="post_job" class="btn btn-success">Post Job</button>
</form>


        </div>
    </div>
<script>
  initMap('jobMap', {
      selectable: true,
      onSelect: function(lat, lng) {
          document.getElementById('location_lat').value = lat;
          document.getElementById('location_lng').value = lng;

          reverseGeocode(lat, lng, function(address) {
              document.getElementById('location_address').value = address;
          });
      }
  });

  // Image preview
  document.getElementById('job_image').addEventListener('change', function(event) {
      const preview = document.getElementById('preview');
      const file = event.target.files[0];

      if (file) {
          const reader = new FileReader();
          reader.onload = function(e) {
              preview.src = e.target.result;
              preview.classList.remove('d-none');
          };
          reader.readAsDataURL(file);
      } else {
          preview.src = "#";
          preview.classList.add('d-none');
      }
  });
</script>

    <?php endif; ?>

    <!-- Available Jobs List -->
    <h3 class="mb-3">Available Seasonal Jobs</h3>
    <div class="row">
        <?php
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

        while ($job = $stmt->fetch(PDO::FETCH_ASSOC)) {
        ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
				<div class="card-body">
    <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
    <p class="card-text"><?= htmlspecialchars($job['description']) ?></p>

    <?php if (!empty($job['image'])): ?>
       <img src="../uploads/jobs/<?= htmlspecialchars($job['image']) ?>" 
     class="card-img-top" alt="Job Image"
     style="max-height:150px;object-fit:cover;">

    <?php endif; ?>

    <p><strong>Posted by:</strong> <?= htmlspecialchars($job['client_name']) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($job['status']) ?></p>
	<p><strong>Payment:</strong> $<?= number_format($job['payment_amount'], 2) ?></p>

    <!-- Map rendering remains unchanged -->
    <div id="map<?= $job['id'] ?>" style="height:140px;" class="mb-2"></div>


                        <script>
                          const map<?= $job['id'] ?> = initMap('map<?= $job['id'] ?>', {
                              lat: <?= $job['location_lat'] ?>,
                              lng: <?= $job['location_lng'] ?>,
                              zoom: 13
                          });
                          addMarker(map<?= $job['id'] ?>, <?= $job['location_lat'] ?>, <?= $job['location_lng'] ?>, "<?= htmlspecialchars($job['title']) ?>");
                        </script>

                  <?php if ($isProvider): ?>
    <?php if ($job['applied_count'] > 0): ?>
        <span class="badge bg-success">Applied</span>
    <?php else: ?>
        <form method="POST" action="../actions/save_job.php" class="d-inline">
            <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
            <button type="submit" class="btn btn-outline-secondary btn-sm">Save Job</button>
        </form>
        <form method="POST" action="../actions/accept_job.php" class="d-inline">
            <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        </form>
    <?php endif; ?>
<?php endif; ?>

                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</main>

<?php include_once("../includes/footer.php"); ?>