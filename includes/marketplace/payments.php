<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// includes/marketplace/payments.php: escrow payments behind one interface.
//   - PaymentProvider: authorize (hold funds) / capture / void / refund / adjust.
//   - SandboxProvider: DEFAULT. No keys, no network. Issues fake tokens. The pages show a "Test payments" label.
//   - StripeProvider: PaymentIntents with manual capture. Card details are typed ONLY into Stripe's hosted Payment
//     Element (Stripe.js) on public/pay.php. Raw card data never reaches our servers, logs or database; we store the
//     PaymentIntent id (a token) only.
// Keys: env STRIPE_SECRET_KEY / STRIPE_PUBLISHABLE_KEY / STRIPE_WEBHOOK_SECRET, or the untracked, gitignored
// config/payments.local.php (see config/payments.example.php). Keys are never committed. With no keys the sandbox is used.
// Payment state lives in payments.state and is mirrored to tasks.payment_status; it is separate from job state.

require_once __DIR__ . '/core.php';

interface PaymentProvider {
    public function name();       // 'sandbox' | 'stripe'
    public function isSandbox();
    /** Hold funds. @return array [status: authorized|requires_action|failed, ref, client_secret?, message?] */
    public function authorize(array $req);
    /** Current state of a hold. @return array [status: authorized|requires_action|captured|canceled|failed] */
    public function status($ref);
    public function capture($ref, $amountCents, $idem);  // @return array [ok, ref?, message?]
    public function void($ref, $idem);
    public function refund($ref, $amountCents, $idem);
    public function adjust($ref, $newAmountCents, $idem);
}

/** Sandbox/mock provider: always succeeds unless the test card "declined" is chosen. Fake tokens only. */
class SandboxProvider implements PaymentProvider {
    public function name() { return 'sandbox'; }
    public function isSandbox() { return true; }
    private function tok($p) { return $p . '_' . bin2hex(random_bytes(8)); }
    public function authorize(array $req) {
        if (($req['test_card'] ?? '') === 'declined') return ['status' => 'failed', 'ref' => null, 'message' => 'Test card declined. No funds were held.'];
        return ['status' => 'authorized', 'ref' => $this->tok('sbx_auth')];
    }
    public function status($ref) { return ['status' => 'authorized']; }
    public function capture($ref, $amountCents, $idem) { return ['ok' => true, 'ref' => $this->tok('sbx_cap')]; }
    public function void($ref, $idem) { return ['ok' => true, 'ref' => $this->tok('sbx_void')]; }
    public function refund($ref, $amountCents, $idem) { return ['ok' => true, 'ref' => $this->tok('sbx_ref')]; }
    public function adjust($ref, $newAmountCents, $idem) { return $newAmountCents > 0 ? ['ok' => true] : ['ok' => false, 'message' => 'Invalid amount.']; }
}

/** Stripe over HTTPS (no SDK dependency). $transport is injectable so tests can run without network. */
class StripeProvider implements PaymentProvider {
    private $secret; private $publishable; private $transport;
    public function __construct($secret, $publishable, callable $transport = null) {
        $this->secret = $secret; $this->publishable = $publishable; $this->transport = $transport;
    }
    public function name() { return 'stripe'; }
    public function isSandbox() { return false; }
    public function publishableKey() { return $this->publishable; }

    private function call($method, $path, array $params = [], $idem = null) {
        if ($this->transport) return call_user_func($this->transport, $method, $path, $params, $idem);
        $ch = curl_init('https://api.stripe.com/v1' . $path);
        $headers = ['Authorization: Bearer ' . $this->secret, 'Stripe-Version: 2024-06-20'];
        if ($idem) $headers[] = 'Idempotency-Key: ' . $idem;
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers]);
        if ($method === 'POST') curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $j = is_string($raw) ? json_decode($raw, true) : null;
        return [$code, is_array($j) ? $j : []];
    }
    private function err($body) { return (string) ($body['error']['message'] ?? 'The payment processor did not accept the request.'); }

    public function authorize(array $req) {
        [$c, $b] = $this->call('POST', '/payment_intents', [
            'amount' => (int) $req['amount_cents'], 'currency' => strtolower($req['currency'] ?? 'cad'),
            'capture_method' => 'manual', 'automatic_payment_methods' => ['enabled' => 'true', 'allow_redirects' => 'never'],
            'description' => $req['description'] ?? 'Nuahn job hold', 'metadata' => ['task_id' => (string) ($req['task_id'] ?? '')],
        ], $req['idem'] ?? null);
        if ($c >= 200 && $c < 300 && !empty($b['id'])) return ['status' => 'requires_action', 'ref' => $b['id'], 'client_secret' => $b['client_secret'] ?? null];
        return ['status' => 'failed', 'ref' => null, 'message' => $this->err($b)];
    }
    public function status($ref) {
        [$c, $b] = $this->call('GET', '/payment_intents/' . rawurlencode($ref));
        if ($c !== 200) return ['status' => 'failed', 'message' => $this->err($b)];
        $map = ['requires_capture' => 'authorized', 'succeeded' => 'captured', 'canceled' => 'canceled'];
        return ['status' => $map[$b['status'] ?? ''] ?? 'requires_action', 'client_secret' => $b['client_secret'] ?? null];
    }
    public function capture($ref, $amountCents, $idem) {
        [$c, $b] = $this->call('POST', '/payment_intents/' . rawurlencode($ref) . '/capture', ['amount_to_capture' => (int) $amountCents], $idem);
        return ($c === 200) ? ['ok' => true, 'ref' => $b['latest_charge'] ?? $ref] : ['ok' => false, 'message' => $this->err($b)];
    }
    public function void($ref, $idem) {
        [$c, $b] = $this->call('POST', '/payment_intents/' . rawurlencode($ref) . '/cancel', [], $idem);
        return ($c === 200) ? ['ok' => true, 'ref' => $ref] : ['ok' => false, 'message' => $this->err($b)];
    }
    public function refund($ref, $amountCents, $idem) {
        [$c, $b] = $this->call('POST', '/refunds', ['payment_intent' => $ref, 'amount' => (int) $amountCents], $idem);
        return ($c === 200) ? ['ok' => true, 'ref' => $b['id'] ?? null] : ['ok' => false, 'message' => $this->err($b)];
    }
    public function adjust($ref, $newAmountCents, $idem) {
        [$c, $b] = $this->call('POST', '/payment_intents/' . rawurlencode($ref) . '/increment_authorization', ['amount' => (int) $newAmountCents], $idem);
        return ($c === 200) ? ['ok' => true] : ['ok' => false, 'message' => $this->err($b)];
    }

    /**
     * Verify a Stripe-Signature header ("t=...,v1=...") against the raw request body. Replays older than $tolerance
     * seconds are rejected. Constant-time compare. $now is injectable for tests.
     */
    public static function verifySignature($payload, $header, $secret, $tolerance = 300, $now = null) {
        if (!is_string($header) || $header === '' || $secret === '') return false;
        $t = null; $sigs = [];
        foreach (explode(',', $header) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) !== 2) continue;
            if ($kv[0] === 't') $t = $kv[1]; elseif ($kv[0] === 'v1') $sigs[] = $kv[1];
        }
        if ($t === null || !ctype_digit($t) || !$sigs) return false;
        if (abs(($now ?? time()) - (int) $t) > $tolerance) return false;
        $expected = hash_hmac('sha256', $t . '.' . $payload, $secret);
        foreach ($sigs as $s) { if (hash_equals($expected, $s)) return true; }
        return false;
    }
}

/** Keys from env or the untracked config/payments.local.php. Returns [secret, publishable, webhook_secret, forced]. Never logged or echoed. */
function mk_pay_config() {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    $cfg = ['secret' => (string) getenv('STRIPE_SECRET_KEY'), 'publishable' => (string) getenv('STRIPE_PUBLISHABLE_KEY'), 'webhook' => (string) getenv('STRIPE_WEBHOOK_SECRET'), 'force' => (string) getenv('NUAHN_PAYMENTS')];
    $file = __DIR__ . '/../../config/payments.local.php';
    if (($cfg['secret'] === '' || $cfg['publishable'] === '') && is_file($file)) {
        $l = include $file;
        if (is_array($l)) {
            $cfg['secret'] = $cfg['secret'] ?: (string) ($l['stripe_secret'] ?? '');
            $cfg['publishable'] = $cfg['publishable'] ?: (string) ($l['stripe_publishable'] ?? '');
            $cfg['webhook'] = $cfg['webhook'] ?: (string) ($l['stripe_webhook_secret'] ?? '');
            $cfg['force'] = $cfg['force'] ?: (string) ($l['mode'] ?? '');
        }
    }
    return $cfg;
}
function mk_pay_mode() {
    $c = mk_pay_config();
    if ($c['force'] === 'sandbox') return 'sandbox';
    return (preg_match('/^sk_(test|live)_/', $c['secret']) && preg_match('/^pk_(test|live)_/', $c['publishable'])) ? 'stripe' : 'sandbox';
}
function mk_pay_provider($name = null) {
    $name = $name ?: mk_pay_mode();
    if ($name === 'stripe') {
        $c = mk_pay_config();
        if (!preg_match('/^sk_/', $c['secret'])) throw new MkError('Card payments are not configured.', 409);
        return new StripeProvider($c['secret'], $c['publishable']);
    }
    return new SandboxProvider();
}
/** HTML label shown wherever payment is collected while the sandbox is active. */
function mk_pay_label_html() {
    if (mk_pay_mode() !== 'sandbox') return '';
    return '<span class="chip chip--warning">Test payments</span>';
}

// ---------- ledger ----------
function mk_payment($taskId) {
    $st = mk_db()->prepare('SELECT * FROM payments WHERE task_id = ?');
    $st->execute([(int) $taskId]);
    return $st->fetch() ?: null;
}
function mk_payment_event($paymentId, $event, $amount = null, $ref = null, $idem = null, $note = '') {
    mk_db()->prepare('INSERT INTO payment_events (payment_id, event, amount, ref, idem_key, note) VALUES (?,?,?,?,?,?)')
        ->execute([(int) $paymentId, substr($event, 0, 40), $amount, $ref, $idem, substr((string) $note, 0, 255)]);
}
/** Single place that changes payments.state; mirrors it to tasks.payment_status. */
function mk_payment_set(array $pay, $state, array $set = []) {
    $cols = ['state = ?']; $args = [$state];
    foreach ($set as $c => $v) {
        if (!preg_match('/^[a-z_]+$/', $c)) continue;
        if ($v === '@now') $cols[] = "$c = NOW()"; else { $cols[] = "$c = ?"; $args[] = $v; }
    }
    $args[] = (int) $pay['id'];
    mk_db()->prepare('UPDATE payments SET ' . implode(', ', $cols) . ' WHERE id = ?')->execute($args);
    mk_db()->prepare('UPDATE tasks SET payment_status = ? WHERE id = ?')->execute([$state, (int) $pay['task_id']]);
}

/** Create or refresh the (unauthorised) payment row for a task at the given pre-tax subtotal. */
function mk_payment_prepare($taskId, $clientId, $contractorId, $subtotal, $tax) {
    $subtotal = mk_money($subtotal); $tax = mk_money($tax);
    $sp = mk_split($subtotal);
    $provider = mk_pay_mode();
    $cur = mk_payment($taskId);
    if ($cur && in_array($cur['state'], ['authorized', 'captured', 'released', 'partially_refunded', 'disputed'], true)) {
        throw new MkError('This job already has funds held.', 409);
    }
    if ($cur) {
        mk_db()->prepare("UPDATE payments SET contractor_id = ?, provider = ?, state = 'unpaid', subtotal = ?, tax = ?, total = ?, commission_pct = ?, commission = ?, payout_amount = ?, processor_ref = NULL WHERE id = ?")
            ->execute([$contractorId, $provider, $subtotal, $tax, mk_money($subtotal + $tax), $sp['pct'], $sp['commission'], $sp['payout'], $cur['id']]);
    } else {
        mk_db()->prepare("INSERT INTO payments (task_id, client_id, contractor_id, provider, state, subtotal, tax, total, commission_pct, commission, payout_amount) VALUES (?,?,?,?, 'unpaid', ?,?,?,?,?,?)")
            ->execute([(int) $taskId, (int) $clientId, $contractorId, $provider, $subtotal, $tax, mk_money($subtotal + $tax), $sp['pct'], $sp['commission'], $sp['payout']]);
    }
    return mk_payment($taskId);
}

/**
 * Ask the provider to hold the funds. Sandbox: immediate. Stripe: returns requires_action + client_secret, and the
 * client finishes on public/pay.php; mk_payment_confirm() (return URL or webhook) then marks it authorized.
 * @return array [status, client_secret?, message?]
 */
function mk_payment_authorize($taskId, array $opts = []) {
    $pay = mk_payment($taskId);
    if (!$pay) throw new MkError('No payment is prepared for this job.', 409);
    $prov = mk_pay_provider($pay['provider']);
    $idem = 'auth-' . $pay['id'] . '-' . bin2hex(random_bytes(4));
    $r = $prov->authorize(['amount_cents' => mk_cents($pay['total']), 'currency' => $pay['currency'], 'task_id' => $taskId, 'idem' => $idem, 'test_card' => $opts['test_card'] ?? '', 'description' => 'Nuahn job ' . $taskId]);
    if ($r['status'] === 'authorized') {
        mk_payment_set($pay, 'authorized', ['processor_ref' => $r['ref'], 'authorized_at' => '@now']);
        mk_payment_event($pay['id'], 'authorized', $pay['total'], $r['ref'], $idem, $prov->name() . ' hold placed');
        mk_audit('payment.authorized', 'payment', $pay['id'], $prov->name() . ' ' . number_format((float) $pay['total'], 2));
    } elseif ($r['status'] === 'requires_action') {
        mk_db()->prepare('UPDATE payments SET processor_ref = ? WHERE id = ?')->execute([$r['ref'], $pay['id']]);
        mk_payment_event($pay['id'], 'intent_created', $pay['total'], $r['ref'], $idem, 'awaiting card entry');
    } else {
        mk_payment_set($pay, 'failed');
        mk_payment_event($pay['id'], 'authorize_failed', $pay['total'], null, $idem, $r['message'] ?? 'failed');
        mk_audit('payment.failed', 'payment', $pay['id'], $r['message'] ?? 'failed');
    }
    return $r;
}

/** Stripe only: after the client confirms the card (return URL) or the webhook fires, check the PaymentIntent and mark the hold. */
function mk_payment_confirm($taskId) {
    $pay = mk_payment($taskId);
    if (!$pay || !$pay['processor_ref']) return false;
    if ($pay['state'] === 'authorized') return true;
    if ($pay['state'] !== 'unpaid' && $pay['state'] !== 'failed') return false;
    $s = mk_pay_provider($pay['provider'])->status($pay['processor_ref']);
    if (($s['status'] ?? '') === 'authorized') {
        mk_payment_set($pay, 'authorized', ['authorized_at' => '@now']);
        mk_payment_event($pay['id'], 'authorized', $pay['total'], $pay['processor_ref'], null, 'confirmed');
        mk_audit('payment.authorized', 'payment', $pay['id'], 'stripe hold confirmed', null, 'system');
        return true;
    }
    return false;
}

/**
 * Capture the held funds. $shareSubtotal = the pre-tax amount the contractor is paid for (default: everything).
 * Commission is 12% of the captured pre-tax share; tax is captured pro rata; anything not captured is released back.
 * Sandbox: payout is simulated as paid at once (state 'released'). Stripe: payout stays 'pending' until Ops marks it paid
 * (Connect transfers are pending work).
 */
function mk_payment_capture($taskId, $shareSubtotal = null, $why = 'approved') {
    $pay = mk_payment($taskId);
    if (!$pay) throw new MkError('No payment exists for this job.', 409);
    if (!in_array($pay['state'], ['authorized', 'disputed'], true)) throw new MkError('Funds are not held for this job.', 409);
    $sub = (float) $pay['subtotal'];
    $share = $shareSubtotal === null ? $sub : max(0.0, min($sub, (float) $shareSubtotal));
    $tax = $sub > 0 ? mk_money((float) $pay['tax'] * $share / $sub) : 0.0;
    $capture = mk_money($share + $tax);
    $prov = mk_pay_provider($pay['provider']);
    $idem = 'cap-' . $pay['id'];
    if ($capture <= 0) { return mk_payment_void($taskId, $why); }
    $r = $prov->capture($pay['processor_ref'], mk_cents($capture), $idem);
    if (empty($r['ok'])) throw new MkError('The payment could not be captured: ' . ($r['message'] ?? 'processor error') . ' Ops has been notified.', 409);
    $sp = mk_split($share, (float) $pay['commission_pct']);
    $refunded = mk_money((float) $pay['total'] - $capture);
    $partial = $refunded > 0.004;
    $sandbox = $prov->isSandbox();
    mk_payment_set($pay, $sandbox ? ($partial ? 'partially_refunded' : 'released') : ($partial ? 'partially_refunded' : 'captured'), [
        'captured_amount' => $capture, 'refunded_amount' => $refunded, 'commission' => $sp['commission'], 'payout_amount' => $sp['payout'],
        'captured_at' => '@now', 'payout_status' => $sandbox ? 'paid' : 'pending', 'payout_ref' => $sandbox ? 'sbx_po_' . bin2hex(random_bytes(6)) : null,
    ] + ($sandbox ? ['released_at' => '@now'] : []) + ($partial ? ['refunded_at' => '@now'] : []));
    mk_payment_event($pay['id'], 'captured', $capture, $r['ref'] ?? null, $idem, $why . ': commission ' . number_format($sp['commission'], 2) . ', payout ' . number_format($sp['payout'], 2));
    if ($partial) mk_payment_event($pay['id'], 'released_back', $refunded, null, $idem . '-rem', 'uncaptured remainder returned to the client');
    mk_audit('payment.captured', 'payment', $pay['id'], $why . ' captured ' . number_format($capture, 2) . ', commission ' . number_format($sp['commission'], 2));
    return mk_payment($taskId);
}

/** Cancel an unused hold; the client is not charged. */
function mk_payment_void($taskId, $why = 'cancelled') {
    $pay = mk_payment($taskId);
    if (!$pay) return null;
    if (!in_array($pay['state'], ['authorized', 'disputed', 'unpaid', 'failed'], true)) return $pay;
    if (in_array($pay['state'], ['authorized', 'disputed'], true) && $pay['processor_ref']) {
        $r = mk_pay_provider($pay['provider'])->void($pay['processor_ref'], 'void-' . $pay['id']);
        if (empty($r['ok'])) throw new MkError('The hold could not be released: ' . ($r['message'] ?? 'processor error'), 409);
    } elseif ($pay['processor_ref'] && $pay['provider'] === 'stripe') {
        // intent was created but the card step never finished: cancel it quietly
        try { mk_pay_provider('stripe')->void($pay['processor_ref'], 'void-' . $pay['id']); } catch (Throwable $e) {}
    }
    mk_payment_set($pay, 'refunded', ['refunded_amount' => $pay['state'] === 'authorized' || $pay['state'] === 'disputed' ? $pay['total'] : 0, 'refunded_at' => '@now', 'payout_status' => 'none', 'payout_amount' => 0, 'commission' => 0]);
    mk_payment_event($pay['id'], 'voided', $pay['total'], null, 'void-' . $pay['id'], $why);
    mk_audit('payment.voided', 'payment', $pay['id'], $why);
    return mk_payment($taskId);
}

/** Re-size the hold when approved extra work is added (revised quote). $deltaSubtotal is pre-tax. */
function mk_payment_adjust($taskId, $deltaSubtotal) {
    $pay = mk_payment($taskId);
    if (!$pay || $pay['state'] !== 'authorized') throw new MkError('There is no active hold to adjust.', 409);
    $sub = mk_money((float) $pay['subtotal'] + $deltaSubtotal);
    [$gst, $pst] = mk_tax($sub); $tax = mk_money($gst + $pst);
    $total = mk_money($sub + $tax);
    $r = mk_pay_provider($pay['provider'])->adjust($pay['processor_ref'], mk_cents($total), 'adj-' . $pay['id'] . '-' . mk_cents($total));
    if (empty($r['ok'])) throw new MkError('The held amount could not be increased: ' . ($r['message'] ?? 'processor error') . ' Ask the client to pay the extra separately, or decline the extra work.', 409);
    $sp = mk_split($sub, (float) $pay['commission_pct']);
    mk_db()->prepare('UPDATE payments SET subtotal = ?, tax = ?, total = ?, commission = ?, payout_amount = ? WHERE id = ?')->execute([$sub, $tax, $total, $sp['commission'], $sp['payout'], $pay['id']]);
    mk_db()->prepare('UPDATE tasks SET agreed_amount = ?, tax_amount = ? WHERE id = ?')->execute([$sub, $tax, (int) $taskId]);
    mk_payment_event($pay['id'], 'adjusted', $total, null, 'adj-' . $pay['id'], 'hold re-sized for approved extra work');
    mk_audit('payment.adjusted', 'payment', $pay['id'], 'new total ' . number_format($total, 2));
}

function mk_payout_mark_paid($taskId, $ref = '') {
    $pay = mk_payment($taskId);
    if (!$pay || $pay['payout_status'] !== 'pending') throw new MkError('There is no pending payout for this job.', 409);
    $newState = $pay['state'] === 'captured' ? 'released' : $pay['state'];
    mk_payment_set($pay, $newState, ['payout_status' => 'paid', 'payout_ref' => mk_clean($ref, 100) ?: 'manual', 'released_at' => '@now']);
    mk_payment_event($pay['id'], 'payout_paid', $pay['payout_amount'], mk_clean($ref, 100), null, 'marked paid by ops');
    mk_audit('payout.paid', 'payment', $pay['id'], 'job ' . $taskId);
}
