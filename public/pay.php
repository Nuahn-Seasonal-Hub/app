<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/pay.php: card step for the escrow hold. Only used when real card payments are configured (Stripe keys present).
// Card details are typed into Stripe's hosted Payment Element and go straight to Stripe; this page and our database only
// ever see the PaymentIntent id. On return (or by webhook) the hold is confirmed and the job moves on.
// With no keys the sandbox provider holds funds instantly and this page just forwards to the job.
require_once __DIR__ . '/../config/init.php';
requireLogin('client');
require_once __DIR__ . '/../includes/marketplace/bootstrap.php';

$taskId = (int) ($_GET['task'] ?? 0);
$a = mk_task_access($taskId);
if (!$a || $a[1] !== 'client') { http_response_code(404); echo 'Not found'; exit; }
$t = $a[0];
$pay = mk_payment($taskId);
$track = nu_url('public/track.php') . '?id=' . $taskId;
if (!$pay) { header('Location: ' . $track); exit; }

if (!empty($_GET['return']) || $pay['state'] === 'authorized') {
    if ($pay['state'] !== 'authorized') { try { mk_payment_confirm($taskId); } catch (Throwable $e) { error_log('pay return: ' . $e->getMessage()); } }
    $pay = mk_payment($taskId);
    if ($pay['state'] === 'authorized') {
        try { mk_after_authorized($taskId); } catch (Throwable $e) { error_log('after authorized: ' . $e->getMessage()); }
        flashSuccess('Payment held securely. Funds are released only when you approve the finished work.');
        header('Location: ' . $track); exit;
    }
    flashError('We could not confirm your card yet. Please try again.');
}
if ($pay['state'] !== 'unpaid' && $pay['state'] !== 'failed') { header('Location: ' . $track); exit; }

$secret = ''; $pk = '';
if ($pay['provider'] === 'stripe' && $pay['processor_ref']) {
    try {
        $prov = mk_pay_provider('stripe');
        $s = $prov->status($pay['processor_ref']);
        $secret = (string) ($s['client_secret'] ?? ''); $pk = $prov->publishableKey();
    } catch (Throwable $e) { error_log('pay page: ' . $e->getMessage()); }
}
include_once __DIR__ . '/../includes/header.php';
?>
<main class="screen" id="pay">
  <p class="crumbs"><a href="<?= nu_e(nu_url('public/bookings.php')) ?>">My bookings</a> / Secure payment</p>
  <div class="screen__head"><div><h1>Hold payment</h1><p><?= nu_e(mk_ref($t)) ?> · <?= nu_e($t['title']) ?></p></div><?= mk_pay_label_html() ?></div>
  <section class="sv-card" style="max-width:520px">
    <dl class="ap-sum"><div><dt>Job price</dt><dd>$<?= number_format((float) $pay['subtotal'], 2) ?></dd></div><div><dt>Tax</dt><dd>$<?= number_format((float) $pay['tax'], 2) ?></dd></div><div class="is-total"><dt>Held now</dt><dd>$<?= number_format((float) $pay['total'], 2) ?></dd></div></dl>
    <?= nu_trust_note('held') ?>
<?php if ($secret !== '' && $pk !== ''): ?>
    <div id="pay-root" data-pk="<?= nu_e($pk) ?>" data-secret="<?= nu_e($secret) ?>" data-ret="<?= nu_e(nu_url('public/pay.php') . '?task=' . $taskId . '&return=1') ?>">
      <form id="pay-form"><div id="pay-element"></div><p class="ap-err" id="pay-msg" role="alert" hidden></p>
        <button class="btn btn-primary btn-block" id="pay-btn" type="submit"><?= nu_icon('lock') ?> Hold $<?= number_format((float) $pay['total'], 2) ?></button></form>
    </div>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="<?= nu_e(nu_asset('public/assets/pay.js')) ?>" defer></script>
<?php else: ?>
    <p class="ap-fine">Card payments are not available right now. Nothing was charged. <a href="<?= nu_e($track) ?>">Back to your job</a></p>
<?php endif; ?>
  </section>
</main>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
