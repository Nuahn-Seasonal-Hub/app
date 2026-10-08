<?php
require_once "../../config/init.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

include "../../includes/header.php";
nu_band('bell', 'Messaging', 'Admin Notification Control Panel', 'Send a notice to everyone or to one group of users.',
    '<a class="btn btn-glass" href="notifications_history.php">' . nu_icon('clock') . ' History</a><a class="btn btn-light" href="notifications_analytics.php">' . nu_icon('bar-chart-line') . ' Analytics</a>');
?>

<main class="wrap-sm page-body">
  <div class="card card-pad" data-reveal>
    <form action="../../actions/send_notification.php" method="POST">
      <div class="form-grid">
        <label class="field span-2" for="subject"><span class="label">Subject</span>
          <input type="text" class="input" id="subject" name="subject" required></label>
        <label class="field span-2" for="message"><span class="label">Message</span>
          <textarea class="input" id="message" name="message" rows="4" required></textarea></label>
        <label class="field span-2"><span class="label">Recipients</span>
          <select class="input" name="recipient_group" required>
            <option value="all">All Users</option>
            <option value="manager">Managers</option>
            <option value="provider">Providers</option>
            <option value="client">Clients</option>
          </select></label>
        <label class="form-check span-2" for="override">
          <input class="form-check-input" type="checkbox" name="override" id="override">
          <span>Override user preferences (critical alert)</span>
        </label>
      </div>
      <button type="submit" class="btn btn-danger mt-6"><?= nu_icon('send') ?> Send Notification</button>
    </form>
  </div>
</main>

<?php include "../../includes/footer.php"; ?>
