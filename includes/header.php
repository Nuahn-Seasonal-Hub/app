<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Meta -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nuah Seasonal Hub</title>

  <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="/Nuahn/assets/js/map.js"></script>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Roboto+Slab:wght@600&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- MarkerCluster -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
  <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

  <!-- Custom Theme -->
  <link rel="stylesheet" href="../css/custom.css">

  <!-- Favicon -->
  <link rel="icon" href="../images/favicon.ico" type="image/x-icon">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">
      <img src="../images/logo.png" alt="Logo"> Nuah Seasonal Hub
    </a> 

    <!-- Toggler -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
            data-bs-target="#navbarNav" aria-controls="navbarNav" 
            aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Collapsible Menu -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="jobs.php">Seasonal Jobs</a></li>
        
        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="../public/logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="../public/auth.php">Login</a></li>
        <?php endif; ?>

        <?php if (function_exists('isRole') && isRole('provider')): ?>
          <li class="nav-item"><a class="nav-link" href="../public/saved_jobs.php">Saved Jobs</a></li>
          <li class="nav-item"><a class="nav-link" href="../public/my_activity.php">My Activity</a></li>
        <?php endif; ?>

        <?php if (function_exists('isRole') && isRole('client')): ?>
          <li class="nav-item"><a class="nav-link" href="../public/my_jobs.php">My Jobs</a></li>
        <?php endif; ?>

        <?php if (function_exists('isRole') && (isRole('manager') || isRole('admin') || isRole('superadmin'))): ?>
          <li class="nav-item"><a class="nav-link" href="../public/dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="../public/manage_applications.php">Manage Applications</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Theme Toggle -->
    <div class="d-flex">
      <button id="theme-toggle" class="btn btn-outline-light ms-2">🌙</button>
    </div>
  </div>
</nav>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Theme Toggle Script -->
<script>
  const toggleBtn = document.getElementById('theme-toggle');
  toggleBtn.addEventListener('click', () => {
    document.body.classList.toggle('light-theme');
    if (document.body.classList.contains('light-theme')) {
      localStorage.setItem('theme', 'light');
      toggleBtn.textContent = '☀️';
    } else {
      localStorage.setItem('theme', 'dark');
      toggleBtn.textContent = '🌙';
    }
  });

  if (localStorage.getItem('theme') === 'light') {
    document.body.classList.add('light-theme');
    toggleBtn.textContent = '☀️';
  }
</script>
</body>
</html>
