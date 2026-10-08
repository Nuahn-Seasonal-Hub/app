<?php
// public/edit_job.php
require_once("../config/init.php");
include_once("../includes/header.php");
requireLogin('client');

$client_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: my_jobs.php?error=missing_id");
    exit;
}

$job_id = intval($_GET['id']);

// Fetch job
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND client_id = ?");
$stmt->execute([$job_id, $client_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header("Location: my_jobs.php?error=job_not_found");
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job'])) {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $lat         = $_POST['location_lat'] ?? null;
    $lng         = $_POST['location_lng'] ?? null;
    $address     = $_POST['location_address'] ?? null;

    // Validate coordinates
    if (empty($lat) || empty($lng) || !is_numeric($lat) || !is_numeric($lng)) {
        flashError("Please select a valid location on the map.");
        header("Location: edit_job.php?id=$job_id&error=invalid_location");
        exit;
    }

    // Handle optional image update
    $imagePath = $job['image'];
    if (!empty($_FILES['job_image']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        $fileType = $_FILES['job_image']['type'];
        $fileSize = $_FILES['job_image']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            flashError("Only JPG and PNG images are allowed.");
            header("Location: edit_job.php?id=$job_id&error=invalid_image");
            exit;
        }

        if ($fileSize > $maxSize) {
            flashError("Image must be smaller than 2MB.");
            header("Location: edit_job.php?id=$job_id&error=image_too_large");
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
            header("Location: edit_job.php?id=$job_id&error=upload_failed");
            exit;
        }
    }

    $update = $pdo->prepare("UPDATE jobs 
    SET title = ?, description = ?, location_lat = ?, location_lng = ?, location_address = ?, image = ?, payment_amount = ? 
    WHERE id = ? AND client_id = ?");
$update->execute([$title, $description, $lat, $lng, $address, $imagePath, $payment_amount, $job_id, $client_id]);


    // Audit log
    $log = $pdo->prepare("INSERT INTO audit_logs (action, target_user_id, job_id, created_at) 
                          VALUES (?, ?, ?, NOW())");
    $log->execute(["Job updated", $client_id, $job_id]);

    header("Location: my_jobs.php?success=job_updated");
    exit;
}
?>

<main class="container py-5">
    <h2 class="text-center mb-4">Edit Job</h2>
    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="edit_job.php?id=<?= htmlspecialchars($job_id) ?>" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Job Title</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($job['title']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($job['description']) ?></textarea>
                </div>
<div class="mb-3">
    <label class="form-label">Payment Amount (USD)</label>
    <input type="number" step="0.01" name="payment_amount" class="form-control" 
           value="<?= htmlspecialchars($job['payment_amount']) ?>" required>
</div>

                <!-- Map Selection -->
                <div class="mb-3">
                    <label class="form-label">Select Location</label>
                    <div id="editJobMap" style="height: 300px;"></div>
                    <input type="hidden" name="location_lat" id="location_lat" value="<?= htmlspecialchars($job['location_lat']) ?>" required>
                    <input type="hidden" name="location_lng" id="location_lng" value="<?= htmlspecialchars($job['location_lng']) ?>" required>
                    <input type="text" name="location_address" id="location_address" class="form-control mt-2" 
                           value="<?= htmlspecialchars($job['location_address']) ?>" placeholder="Nearest address" readonly>
                </div>

                <!-- Image Upload -->
                <div class="mb-3">
                    <label class="form-label">Job Image</label>
                    <input type="file" name="job_image" id="job_image" class="form-control" accept="image/*">
                    <?php if (!empty($job['image'])): ?>
                        <div class="mt-3">
                            <img src="../uploads/jobs/<?= htmlspecialchars($job['image']) ?>" 
                                 alt="Current Job Image" class="img-fluid" style="max-height:200px;">
                        </div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <img id="preview" src="#" alt="Image Preview" class="img-fluid d-none" style="max-height:200px;">
                    </div>
                </div>

                <button type="submit" name="update_job" class="btn btn-success">Update Job</button>
                <a href="my_jobs.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</main>

<script>
  // Initialize map with existing coordinates
  initMap('editJobMap', {
      lat: <?= $job['location_lat'] ?>,
      lng: <?= $job['location_lng'] ?>,
      zoom: 13,
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

<?php include_once("../includes/footer.php"); ?>
