<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// includes/marketplace/view.php: small rendering helpers shared by the live booking pages (forms that post to the
// action router, status wording, photo grids, job scope text). Pure output helpers: no state changes here.
require_once __DIR__ . '/bootstrap.php';

/** Opening tag of a POST form to the action router with the CSRF token and the `do` action. $back = same-site page to return to. */
function mk_form($do, array $hidden = [], $attrs = '', $back = '') {
    $o = '<form method="post" action="' . nu_e(nu_url('actions/marketplace.php')) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>' . mk_csrf_field()
       . '<input type="hidden" name="do" value="' . nu_e($do) . '">';
    if ($back !== '') $o .= '<input type="hidden" name="back" value="' . nu_e($back) . '">';
    foreach ($hidden as $k => $v) $o .= '<input type="hidden" name="' . nu_e($k) . '" value="' . nu_e($v) . '">';
    return $o;
}


/** Short plain-language scope line for a task, used on cards. */
function mk_scope($t) {
    $d = mk_details($t);
    if ($t['service'] === 'snow-removal') {
        $m = nu_fixture('marketplace')['snow'];
        $size = $d['size'] ?? 'standard';
        $parts = [($m['sizes'][$size]['label'] ?? ucfirst((string) $size)) . ' driveway' . (!empty($d['sqft']) ? ' (' . (int) $d['sqft'] . ' sq ft)' : '')];
        if (!empty($d['walk']) && $d['walk'] !== 'none') $parts[] = 'walkway';
        if (!empty($d['steps'])) $parts[] = 'steps';
        if (!empty($d['ice'])) $parts[] = 'ice melt';
        $plan = ($t['mode'] === 'seasonal') ? ' · seasonal plan' : '';
        return ucfirst(implode(', ', $parts)) . $plan;
    }
    $v = $d['vehicle'] ?? [];
    $car = trim(($v['year'] ?? '') . ' ' . ($v['make'] ?? '') . ' ' . ($v['model'] ?? ''));
    return $car !== '' ? $car . (!empty($t['notes']) ? ' · ' . mb_substr($t['notes'], 0, 80) : '') : (string) ($t['notes'] ?? '');
}

/** Job key for nu_job_machine() from a task row. */
function mk_machine_key($t) {
    if ($t['status'] === 'disputed') return 'disputed';
    if ($t['status'] === 'quote_pending' || $t['status'] === 'searching') return 'open_for_bids';
    if ($t['status'] === 'paused') return 'in_progress';
    return $t['status'];
}

function mk_when($t) {
    if (!empty($t['window_label'])) return $t['window_label'];
    return $t['mode'] === 'roadside' ? 'As soon as possible' : 'Flexible';
}

function mk_dt($d, $fmt = 'M j, g:i A') { return $d ? date($fmt, strtotime($d)) : ''; }

/** Photos grid for a task, filtered by kind. Images load through public/media.php (access-checked). */
function mk_photo_grid($taskId, $kind, $emptyText = '') {
    $rows = mk_photos($taskId, $kind);
    if (!$rows) return $emptyText !== '' ? '<p class="tk-rep__wait">' . nu_icon('image') . '<span>' . nu_e($emptyText) . '</span></p>' : '';
    $o = '<div class="ba ba--grid">';
    foreach ($rows as $p) {
        $o .= '<figure><a href="' . nu_e(mk_photo_url($p['id'])) . '" target="_blank" rel="noopener"><img src="' . nu_e(mk_photo_url($p['id'])) . '" alt="' . nu_e(ucfirst($kind)) . ' photo, ' . nu_e(mk_dt($p['created_at'])) . '" loading="lazy" width="200" height="150"></a>'
            . '<figcaption>' . nu_e(ucfirst($kind)) . ' <small>' . nu_e(mk_dt($p['created_at'])) . '</small></figcaption></figure>';
    }
    return $o . '</div>';
}

/** "Test payments" notice when running on the sandbox provider; empty string with live keys. */
function mk_test_note() {
    return mk_pay_label_html();
}

/** Commission shown to a contractor for a pre-tax price. */
function mk_you_get($price) { return round((float) $price * (100 - MK_COMMISSION_PCT) / 100, 2); }

/** One open-job card for the contractor board. $j = a row from mk_board(). Real forms; no fake accept. */
function nu_open_job_card($j) {
    $roadside = $j['mode'] === 'roadside'; $quote = $j['status'] === 'quote_pending' || $j['mode'] === 'scheduled';
    $svc = $j['service'] === 'snow-removal' ? 'snow' : 'wrench-adjustable';
    $price = (float) $j['price']; $id = (int) $j['id'];
    $km = $j['km'] !== null ? number_format((float) $j['km'], 1) . ' km' : 'distance n/a';
    $when = mk_when($j); $mine = $j['my_offer'] ?? null;
    $back = 'open_jobs.php';
    $o = '<article class="oj" data-oj data-id="' . $id . '" data-budget="' . (int) $price . '" data-pct="' . (int) MK_COMMISSION_PCT . '">';
    $o .= '<div class="oj__top"><span class="oj__ic">' . nu_icon($svc) . '</span><div class="oj__t"><h3>' . nu_e($j['title']) . '</h3><p>' . nu_icon('geo-alt') . ' ' . nu_e($j['area']) . ' · ' . nu_e($km) . '</p></div>';
    $o .= '<div class="oj__chips">' . ($roadside ? '<span class="chip chip--warning">' . nu_icon('broadcast') . 'Roadside · now</span>' : '')
        . ((float) $j['boost'] > 0 ? '<span class="chip chip--blue">' . nu_icon('rocket-takeoff-fill') . 'Boosted</span>' : '')
        . ($j['mode'] === 'seasonal' ? '<span class="chip">' . nu_icon('snow') . 'Seasonal plan</span>' : '') . '</div></div>';
    $o .= '<p class="oj__m">' . nu_icon('clock') . ' ' . nu_e($when) . ' <span>· posted ' . nu_e(mk_ago($j['age_s'])) . '</span>'
        . ($roadside && $j['search_left_min'] !== null ? '<span>· ' . max(0, (int) $j['search_left_min']) . ' min left to respond</span>' : '') . '</p>';
    $o .= '<p class="oj__s">' . nu_e(mk_scope($j)) . '</p>';
    if ($quote) {
        $o .= '<div class="oj__bud"><span><small>Client budget</small><b>Send a quote</b></span><span class="oj__pay">Itemise parts, labour and call-out fee</span></div>';
        $o .= '<div class="oj__acts"><a class="btn btn-primary btn-sm" href="' . nu_e(nu_url('public/quote.php') . '?task=' . $id) . '">' . nu_icon('receipt') . ($mine ? ' Update quote' : ' Send quote') . '</a></div>';
    } elseif ($roadside) {
        $o .= '<div class="oj__bud"><span><small>Offered price</small><b>$' . number_format($price, 2) . '</b></span><span class="oj__pay">You receive about <b>$' . number_format(mk_you_get($price), 2) . '</b> before tax, after ' . (int) MK_COMMISSION_PCT . '% commission</span></div>';
        $o .= mk_form('roadside_accept', ['task_id' => $id], 'class="oj__acts"', $back) . '<label class="sr-only" for="eta-' . $id . '">Arrival time</label><select class="input input--sm" id="eta-' . $id . '" name="eta"><option value="15">ETA 15 min</option><option value="25" selected>ETA 25 min</option><option value="40">ETA 40 min</option><option value="60">ETA 60 min</option></select>'
            . '<button class="btn btn-primary btn-sm" type="submit">' . nu_icon('check2') . ' Accept and go</button><button class="btn btn-ghost btn-sm" type="button" data-oj-skip>Not for me</button></form>';
    } else {
        $o .= '<div class="oj__bud"><span><small>Client target</small><b>$' . number_format($price, 2) . '</b></span><span class="oj__pay">You receive about <b data-oj-pay>$' . number_format(mk_you_get($price), 2) . '</b> before tax, after ' . (int) MK_COMMISSION_PCT . '% commission</span></div>';
        if ($mine === 'sent') {
            $o .= mk_form('offer_withdraw', ['task_id' => $id], 'class="oj__acts"', $back) . '<span class="chip chip--success">' . nu_icon('check-circle-fill') . 'Offer sent</span><button class="btn btn-ghost btn-sm" type="submit">Withdraw offer</button></form>';
        } else {
            $o .= '<div class="oj__acts">' . mk_form('offer_send', ['task_id' => $id, 'kind' => 'accept', 'amount' => number_format($price, 2, '.', '')], 'class="oj__inl"', $back)
                . '<button class="btn btn-primary btn-sm" type="submit">' . nu_icon('check2') . ' Accept at $' . (int) $price . '</button></form>'
                . '<button class="btn btn-outline btn-sm" type="button" data-oj-counter aria-expanded="false">' . nu_icon('arrow-left-right') . ' Counter-bid</button><button class="btn btn-ghost btn-sm" type="button" data-oj-skip>Not for me</button></div>';
            $o .= mk_form('offer_send', ['task_id' => $id, 'kind' => 'counter'], 'class="oj__cb" data-oj-form hidden', $back)
                . '<div class="oj__cbrow"><label class="label" for="cb-' . $id . '">Your price</label><div class="oj__step"><button type="button" data-oj-step="-5" aria-label="Lower by $5">−</button><span><i>$</i><input class="input" id="cb-' . $id . '" name="amount" type="number" inputmode="numeric" min="10" max="999" data-oj-price value="' . ((int) $price + 10) . '" required></span><button type="button" data-oj-step="5" aria-label="Raise by $5">+</button></div></div>'
                . '<div class="oj__cbrow"><label class="label" for="cn-' . $id . '">Arrival</label><select class="input" id="cn-' . $id . '" name="eta"><option value="60">Within 1 hour</option><option value="120">Within 2 hours</option><option value="720">Tomorrow morning</option></select></div>'
                . '<div class="oj__cbrow"><label class="label" for="cm-' . $id . '">Note to the client</label><input class="input" id="cm-' . $id . '" name="message" maxlength="120" placeholder="e.g. Ice melt included, equipment on site"></div>'
                . '<p class="oj__pay">You receive about <b data-oj-cpay>$0.00</b> after ' . (int) MK_COMMISSION_PCT . '% commission · <span data-oj-vs></span></p>'
                . '<div class="oj__acts"><button class="btn btn-primary btn-sm" type="submit">Send offer</button><button class="btn btn-ghost btn-sm" type="button" data-oj-cancel>Cancel</button></div></form>';
        }
    }
    return $o . '</article>';
}

/** Sandbox-only "test card" chooser for the accept / approve / pay steps. Empty with live Stripe keys (card entry is Stripe's hosted field then). */
function mk_test_card_field() {
    if (mk_pay_mode() !== 'sandbox') return '';
    return '<label class="fld mk-testcard"><span class="label">' . nu_icon('credit-card') . ' Test card <span class="chip chip--warning">Test payments</span></span>'
         . '<select class="input" name="test_card"><option value="">Test card ending 4242 (approved)</option><option value="declined">Test card ending 0002 (declined)</option></select>'
         . '<small>Test payments only. No real money moves and no card number is collected.</small></label>';
}

function mk_eta_text($min) {
    $m = (int) $min;
    if ($m <= 0) return 'Arrival time to be agreed';
    if ($m < 90) return 'Arrives in about ' . $m . ' min';
    if ($m < 1440) return 'Arrives in about ' . (int) round($m / 60) . ' h';
    return 'Arrives tomorrow';
}
