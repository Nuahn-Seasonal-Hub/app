<?php
// includes/footer.php
?>
<footer class="bg-dark text-white mt-5">
  <div class="container py-4">
    <div class="row">
      <div class="col-md-4">
        <h5>Nuahn Seasonal Hub</h5>
        <p class="small">Building scalable, regulator‑ready seasonal job solutions.</p>
      </div>
      <div class="col-md-4">
        <h5>Quick Links</h5>
        <ul class="list-unstyled">
          <li><a href="../public/contact.php" class="text-white text-decoration-none">Contact</a></li>
          <li><a href="../public/privacy.php" class="text-white text-decoration-none">Privacy Policy</a></li>
          <li><a href="../public/help.php" class="text-white text-decoration-none">Help</a></li>
        </ul>
      </div>
      <div class="col-md-4 text-md-end">
        <h5>Stay Connected</h5>
        <p class="small">© <?= date("Y") ?> Nuahn Seasonal Hub. All rights reserved.</p>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const passwordField = document.getElementById("password");
  const strengthBar = document.createElement("div");
  strengthBar.className = "progress mt-2";
  strengthBar.innerHTML = '<div id="strength-meter" class="progress-bar" role="progressbar"></div>';
  passwordField.parentNode.appendChild(strengthBar);

  const meter = document.getElementById("strength-meter");

  passwordField.addEventListener("input", function() {
    const val = passwordField.value;
    let score = 0;

    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[\W]/.test(val)) score++;

    meter.style.width = (score * 20) + "%";

    switch(score) {
      case 0:
      case 1:
        meter.className = "progress-bar bg-danger";
        meter.textContent = "Weak";
        break;
      case 2:
      case 3:
        meter.className = "progress-bar bg-warning";
        meter.textContent = "Medium";
        break;
      case 4:
      case 5:
        meter.className = "progress-bar bg-success";
        meter.textContent = "Strong";
        break;
    }
  });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.classList.remove('show');
      alert.classList.add('fade');
    }, 4000); // auto-dismiss after 4 seconds
  });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  const toastElList = [].slice.call(document.querySelectorAll('.toast'));
  const toastList = toastElList.map(function(toastEl) {
    return new bootstrap.Toast(toastEl, { delay: 4000 }); // auto-dismiss after 4s
  });
  toastList.forEach(toast => toast.show());
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  // Auto-dismiss alerts after 4s
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      alert.classList.remove('show');
      alert.classList.add('fade');
    }, 4000);
  });

  // Initialize toasts
  const toastElList = [].slice.call(document.querySelectorAll('.toast'));
  const toastList = toastElList.map(toastEl => new bootstrap.Toast(toastEl, { delay: 4000 }));
  toastList.forEach(toast => toast.show());
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
  // Auto-dismiss alerts after 4s
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      alert.classList.remove('show');
      alert.classList.add('fade');
    }, 4000);
  });

  // Initialize toasts
  const toastElList = [].slice.call(document.querySelectorAll('.toast'));
  const toastList = toastElList.map(toastEl => new bootstrap.Toast(toastEl, { delay: 4000 }));
  toastList.forEach(toast => toast.show());
});
</script>
<script>
const toggle = document.getElementById('theme-toggle');
toggle.addEventListener('click', () => {
  document.body.classList.toggle('bg-dark');
  document.body.classList.toggle('text-white');
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Theme Toggle Script -->
<script>
  const toggleBtn = document.getElementById('theme-toggle');
  const body = document.body;

  // Apply saved theme on load
  if (localStorage.getItem('theme') === 'dark') {
    body.classList.add('dark-mode');
    toggleBtn.textContent = '☀️';
  }

  toggleBtn.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    if (body.classList.contains('dark-mode')) {
      localStorage.setItem('theme', 'dark');
      toggleBtn.textContent = '☀️';
    } else {
      localStorage.setItem('theme', 'light');
      toggleBtn.textContent = '🌙';
    }
  });
</script>
<script>
  var map = L.map('map').setView([5.6037, -0.1870], 12); // Accra default
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  var marker;

  map.on('click', function(e) {
      var lat = e.latlng.lat.toFixed(6);
      var lng = e.latlng.lng.toFixed(6);

      if (marker) map.removeLayer(marker);
      marker = L.marker([lat, lng]).addTo(map);

      document.getElementById('location_lat').value = lat;
      document.getElementById('location_lng').value = lng;

      // Reverse geocoding using Nominatim
      fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
        .then(res => res.json())
        .then(data => {
          document.getElementById('location_address').value = data.display_name || '';
        });
  });
</script>
</body>
</html>