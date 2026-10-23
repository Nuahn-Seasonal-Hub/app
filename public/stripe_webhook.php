<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/stripe_webhook.php: Stripe webhook endpoint. Intentionally does NOT include the launch gate, init.php or
// sessions: Stripe must reach it on the public host. Safety comes from signature verification (HMAC-SHA256 with the
// webhook signing secret, 5 minute replay window) and event-id de-duplication. Without a configured secret it refuses everything.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/marketplace/handlers.php';

header('Content-Type: text/plain; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('POST only'); }
$cfg = mk_pay_config();
$payload = (string) file_get_contents('php://input');
if (strlen($payload) > 262144) { http_response_code(413); exit('Too large'); }
if ($cfg['webhook'] === '' || !StripeProvider::verifySignature($payload, $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '', $cfg['webhook'])) {
    http_response_code(400); exit('Bad signature');
}
$event = json_decode($payload, true);
if (!is_array($event) || empty($event['id']) || empty($event['type'])) { http_response_code(400); exit('Bad payload'); }

// De-duplicate: Stripe retries. INSERT IGNORE tells us whether this is the first delivery.
$ins = $pdo->prepare('INSERT IGNORE INTO webhook_events (event_id, type) VALUES (?, ?)');
$ins->execute([substr((string) $event['id'], 0, 80), substr((string) $event['type'], 0, 80)]);
if (!$ins->rowCount()) { http_response_code(200); exit('Duplicate'); }

try {
    $obj = $event['data']['object'] ?? [];
    $intent = (string) ($obj['id'] ?? '');
    $taskFor = function ($pi) use ($pdo) { $s = $pdo->prepare("SELECT task_id FROM payments WHERE provider = 'stripe' AND processor_ref = ?"); $s->execute([$pi]); return (int) $s->fetchColumn(); };
    switch ($event['type']) {
        case 'payment_intent.amount_capturable_updated':
            if ($tid = $taskFor($intent)) { if (mk_payment_confirm($tid)) mk_after_authorized($tid); }
            break;
        case 'payment_intent.payment_failed':
        case 'payment_intent.canceled':
            if ($tid = $taskFor($intent)) {
                $pay = mk_payment($tid);
                if ($pay && $pay['state'] === 'unpaid') { mk_payment_set($pay, 'failed'); mk_payment_event($pay['id'], 'webhook_' . $event['type'], null, $intent, null, 'from webhook'); }
            }
            break;
        case 'charge.dispute.created':
            $pi = (string) ($obj['payment_intent'] ?? '');
            if ($pi && ($tid = $taskFor($pi))) { mk_audit('payment.chargeback', 'task', $tid, 'Stripe dispute ' . substr((string) ($obj['id'] ?? ''), 0, 40), null, 'system'); mk_notify_staff($tid, 'chargeback', 'Card dispute received', 'A chargeback was opened on a job. Review it in Stripe.', nu_url('public/operations.php') . '?view=audit'); }
            break;
    }
    http_response_code(200); echo 'ok';
} catch (Throwable $e) {
    $pdo->prepare('DELETE FROM webhook_events WHERE event_id = ?')->execute([(string) $event['id']]); // let Stripe retry
    error_log('stripe webhook: ' . $e->getMessage());
    http_response_code(500); echo 'error';
}
