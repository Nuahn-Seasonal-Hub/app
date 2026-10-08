<?php
require_once "../../config/db.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

include "../../includes/header.php";
?>

<div class="container mt-4">
  <h2 class="mb-4">Admin Notification Control Panel</h2>
  <form action="../../actions/send_notification.php" method="POST">
    <div class="mb-3">
      <label for="subject" class="form-label">Subject</label>
      <input type="text" class="form-control" id="subject" name="subject" required>
    </div>
    <div class="mb-3">
      <label for="message" class="form-label">Message</label>
      <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Recipients</label>
      <select class="form-select" name="recipient_group" required>
        <option value="all">All Users</option>
        <option value="admin">Admins</option>
        <option value="provider">Providers</option>
        <option value="client">Clients</option>
      </select>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="override" id="override">
      <label class="form-check-label" for="override">Override user preferences (critical alert)</label>
    </div>
    <button type="submit" class="btn btn-danger mt-3">Send Notification</button>
  </form>
</div>

<?php include "../../includes/footer.php"; ?>
