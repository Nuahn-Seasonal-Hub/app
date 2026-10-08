<?php
require_once "../../config/init.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

// Fetch all users
$stmt = $pdo->query("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$byRole = array_count_values(array_column($users, 'role'));

include "../../includes/header.php";

nu_band('people', 'People', 'Manage users', 'Everyone on Nuahn Seasonal Hub, newest first.',
    isRole('superadmin') ? '<a class="btn btn-light" href="manage_admins.php">' . nu_icon('shield-check') . ' Manage staff</a>' : '');
?>

<main class="wrap page-body">
  <div class="grid grid-stats">
    <div class="stat" data-reveal style="--i:0"><span class="stat__icon stat__icon--navy"><?= nu_icon('people-fill') ?></span><div><div class="stat__label">All users</div><div class="stat__value"><?= count($users) ?></div></div></div>
    <div class="stat" data-reveal style="--i:1"><span class="stat__icon"><?= nu_icon('briefcase') ?></span><div><div class="stat__label">Clients</div><div class="stat__value"><?= (int) ($byRole['client'] ?? 0) ?></div></div></div>
    <div class="stat" data-reveal style="--i:2"><span class="stat__icon stat__icon--success"><?= nu_icon('person-badge') ?></span><div><div class="stat__label">Providers</div><div class="stat__value"><?= (int) ($byRole['provider'] ?? 0) ?></div></div></div>
    <div class="stat" data-reveal style="--i:3"><span class="stat__icon stat__icon--warning"><?= nu_icon('shield-check') ?></span><div><div class="stat__label">Staff</div><div class="stat__value"><?= (int) (($byRole['superadmin'] ?? 0) + ($byRole['manager'] ?? 0)) ?></div></div></div>
  </div>

  <!-- Search bar -->
  <div class="searchbar mt-6" data-reveal>
    <span class="input-icon"><?= nu_icon('search') ?><input type="search" id="searchInput" class="input" placeholder="Search by name or email..." aria-label="Search users"></span>
  </div>

  <!-- Users table -->
  <div class="table-wrap mt-4" data-reveal>
    <table class="table table--stack" id="usersTable">
      <thead>
        <tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Joined</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td data-label="Name"><span class="row-flex" style="gap:10px;flex-wrap:nowrap"><span class="list-row__icon" style="width:34px;height:34px;border-radius:11px;font-size:.8rem"><?= nu_e(nu_initials($user['name'])) ?></span><strong><?= htmlspecialchars($user['name']) ?></strong></span></td>
            <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
            <td data-label="Role"><span class="chip chip--navy chip--plain"><?= htmlspecialchars($user['role']) ?></span></td>
            <td data-label="Status"><?= nu_status_chip($user['status']) ?></td>
            <td class="muted" data-label="Joined"><?= nu_e(nu_date($user['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="muted mt-4" id="noMatch" hidden>No users match that search.</p>
</main>

<!-- Client-side search -->
<script>
  (function () {
    var searchInput = document.getElementById('searchInput');
    var tableRows = document.querySelectorAll('#usersTable tbody tr');
    var none = document.getElementById('noMatch');
    searchInput.addEventListener('input', function () {
      var query = this.value.toLowerCase(), shown = 0;
      tableRows.forEach(function (row) {
        var name = row.cells[0].textContent.toLowerCase();
        var email = row.cells[1].textContent.toLowerCase();
        var hit = name.includes(query) || email.includes(query);
        row.style.display = hit ? '' : 'none';
        if (hit) shown++;
      });
      none.hidden = shown > 0;
    });
  })();
</script>

<?php include "../../includes/footer.php"; ?>
