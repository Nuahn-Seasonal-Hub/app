<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// includes/marketplace/config.php: business constants for the booking backend (Snow Removal + Mobile Mechanics).
// Every value is a SAMPLE business rule held in one place; change it here (or override it in the untracked
// config/marketplace.local.php, loaded first) after Nuahn confirms the real numbers.
// Server-side backend: see docs/CREDITS.md.

if (is_file(__DIR__ . '/../../config/marketplace.local.php')) { require_once __DIR__ . '/../../config/marketplace.local.php'; }

defined('MK_COMMISSION_PCT')      || define('MK_COMMISSION_PCT', 12.0);   // Nuahn commission on the pre-tax job amount (sample)
defined('MK_GST_PCT')             || define('MK_GST_PCT', 5.0);           // Alberta GST (sample; province table is pending)
defined('MK_PST_PCT')             || define('MK_PST_PCT', 0.0);           // Alberta has no PST
defined('MK_AUTO_RELEASE_HOURS')  || define('MK_AUTO_RELEASE_HOURS', 48); // auto-approve if the client is silent after completion
defined('MK_SEASONAL_TRIGGER_CM') || define('MK_SEASONAL_TRIGGER_CM', 5); // seasonal-plan visit triggers at this snowfall
defined('MK_SEASONAL_DISCOUNT')   || define('MK_SEASONAL_DISCOUNT', 0.10);
defined('MK_BOOST_MAX')           || define('MK_BOOST_MAX', 25);          // max boost the client can add to a task, CAD
defined('MK_ROADSIDE_SEARCH_MIN') || define('MK_ROADSIDE_SEARCH_MIN', 15);// minutes a roadside request is broadcast before "no provider"
defined('MK_QUOTE_VALID_HOURS')   || define('MK_QUOTE_VALID_HOURS', 24);
defined('MK_DEFAULT_RADIUS_KM')   || define('MK_DEFAULT_RADIUS_KM', 25);  // used when a contractor has not set a radius
defined('MK_ROADSIDE_RADIUS_KM')  || define('MK_ROADSIDE_RADIUS_KM', 40); // roadside requests reach technicians within this distance
defined('MK_MAX_PHOTO_BYTES')     || define('MK_MAX_PHOTO_BYTES', 10 * 1024 * 1024);
defined('MK_MAX_PHOTOS_PER_KIND') || define('MK_MAX_PHOTOS_PER_KIND', 6);
defined('MK_MIN_PRICE')           || define('MK_MIN_PRICE', 10);
defined('MK_MAX_PRICE')           || define('MK_MAX_PRICE', 5000);
defined('MK_UPLOAD_DIR')          || define('MK_UPLOAD_DIR', realpath(__DIR__ . '/../..') . '/uploads/jobs');

/** Snow price guidance (CAD), the static service config in fixtures/marketplace.php ['snow'] (shared with the form). */
function mk_snow_pricing() { return nu_fixture('marketplace')['snow']; }

/** [min, max, mid] estimate for the snow form values (size, sqft, walk, steps, ice, plan). Mirrors the form's live estimator. */
function mk_snow_estimate(array $d) {
    $p = mk_snow_pricing();
    $size = isset($p['sizes'][$d['size'] ?? '']) ? $d['size'] : 'standard';
    $s = $p['sizes'][$size];
    if (!empty($s['per_sqft'])) {
        $a = max(400, min(200000, (int) ($d['sqft'] ?? 0)));
        $lo = max(60, $a * $s['per_sqft'][0]); $hi = max(90, $a * $s['per_sqft'][1]);
    } else { $lo = $s['lo']; $hi = $s['hi']; }
    $w = $p['walk'][$d['walk'] ?? 'none'] ?? ['', 0, 0];
    $lo += $w[1]; $hi += $w[2];
    $st = array_values($p['steps'])[0];
    if (!empty($d['steps'])) { $lo += $st[0]; $hi += $st[1]; }
    if (!empty($d['ice']))   { $lo += $p['ice']['lo']; $hi += $p['ice']['hi']; }
    if (($d['plan'] ?? 'demand') === 'seasonal') { $lo *= (1 - $p['seasonal_discount']); $hi *= (1 - $p['seasonal_discount']); }
    $lo = (int) (round($lo / 5) * 5); $hi = (int) (round($hi / 5) * 5);
    return [$lo, $hi, (int) round(($lo + $hi) / 2)];
}

/** Roadside price guidance by category (CAD, [min, max]). */
function mk_roadside_pricing() {
    return ['flat-tire' => [85, 120], 'dead-battery' => [70, 100], 'lockout' => [80, 110], 'fuel-delivery' => [65, 95], 'tire-pressure' => [45, 65], 'towing' => [110, 180]];
}
