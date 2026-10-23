<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// Run: php tests/stripe_provider_test.php
// Checks the Stripe provider against a mock transport (no network, no keys) and the webhook signature check against a locally computed HMAC.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/marketplace/payments.php';

$pass = 0; $fail = 0;
function check($name, $cond) { global $pass, $fail; if ($cond) { $pass++; echo "  PASS $name\n"; } else { $fail++; echo "  FAIL $name\n"; } }

$calls = [];
$tx = function ($method, $path, $params, $idem) use (&$calls) {
    $calls[] = [$method, $path, $params, $idem];
    if ($path === '/payment_intents' && $method === 'POST') return [200, ['id' => 'pi_test_1', 'client_secret' => 'pi_test_1_secret_x']];
    if ($path === '/payment_intents/pi_test_1' && $method === 'GET') return [200, ['status' => 'requires_capture']];
    if ($path === '/payment_intents/pi_test_1/capture') return [200, ['latest_charge' => 'ch_test_1']];
    if ($path === '/payment_intents/pi_test_1/cancel') return [200, []];
    if ($path === '/refunds') return [200, ['id' => 're_test_1']];
    return [402, ['error' => ['message' => 'Your card was declined.']]];
};
$s = new StripeProvider('sk_test_dummy', 'pk_test_dummy', $tx);

$a = $s->authorize(['amount_cents' => 8925, 'currency' => 'CAD', 'task_id' => 7, 'idem' => 'k1']);
check('authorize returns a PaymentIntent id and client secret (no card data)', $a['ref'] === 'pi_test_1' && !empty($a['client_secret']));
check('authorize uses manual capture', $calls[0][2]['capture_method'] === 'manual' && $calls[0][2]['amount'] === 8925 && $calls[0][2]['currency'] === 'cad');
check('authorize sends an idempotency key', $calls[0][3] === 'k1');
check('no card fields are ever sent by the server', !array_intersect(array_keys($calls[0][2]), ['card', 'number', 'cvc', 'exp_month', 'payment_method_data']));
check('status maps requires_capture to authorized', $s->status('pi_test_1')['status'] === 'authorized');
check('capture ok', $s->capture('pi_test_1', 8925, 'k2')['ok'] === true);
check('void ok', $s->void('pi_test_1', 'k3')['ok'] === true);
check('refund ok', $s->refund('pi_test_1', 1000, 'k4')['ok'] === true);
check('API error is surfaced as a failure', $s->capture('pi_other', 100, 'k5')['ok'] === false);

$sb = new SandboxProvider();
$sa = $sb->authorize(['amount_cents' => 1000]);
check('sandbox authorises with a fake token', $sa['status'] === 'authorized' && strpos($sa['ref'], 'sbx_') === 0);
check('sandbox declined test card fails', $sb->authorize(['amount_cents' => 1000, 'test_card' => 'declined'])['status'] === 'failed');

$secret = 'whsec_test_local'; $body = '{"id":"evt_1","type":"payment_intent.canceled"}'; $now = 1700000000;
$sig = hash_hmac('sha256', $now . '.' . $body, $secret);
check('valid signature accepted', StripeProvider::verifySignature($body, "t=$now,v1=$sig", $secret, 300, $now));
check('tampered body rejected', !StripeProvider::verifySignature($body . ' ', "t=$now,v1=$sig", $secret, 300, $now));
check('wrong secret rejected', !StripeProvider::verifySignature($body, "t=$now,v1=$sig", 'whsec_other', 300, $now));
check('old timestamp rejected (replay)', !StripeProvider::verifySignature($body, "t=$now,v1=$sig", $secret, 300, $now + 301));
check('missing header rejected', !StripeProvider::verifySignature($body, '', $secret, 300, $now));
check('empty secret rejected', !StripeProvider::verifySignature($body, "t=$now,v1=$sig", '', 300, $now));

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
