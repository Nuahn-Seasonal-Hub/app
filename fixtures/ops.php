<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// fixtures/ops.php: SAMPLE DATA ONLY. Admin / operations overview for the independent-contractor marketplace.
// The open jobs, vetting and disputes lists live in fixtures/marketplace.php (keys open_jobs, vetting, disputes).
// Sir Paul: GET /api/ops/summary?area=&date=, GET /api/ops/open-jobs, GET /api/ops/vetting, POST /api/ops/vetting/{id}/decision,
// GET /api/ops/disputes, POST /api/ops/disputes/{id}/resolve, GET /api/ops/payments. See docs/UI-DATA-HOOKUP.md sections 26 to 33.
return [
    'areas' => ['Edmonton', 'St. Albert', 'Sherwood Park'],
    'kpis' => [
        ['megaphone', 4, 'Open jobs', 'teal'], ['person-vcard', 4, 'In vetting', 'orange'], ['flag-fill', 2, 'Open disputes', 'red'], ['lock-fill', 6, 'Jobs with funds held', 'blue'],
    ],
    'weather' => ['title' => 'Snow expected · Edmonton', 'text' => 'Snow tasks may see a spike in new requests. Nearby contractors are notified automatically.'],
    'payments' => [['authorized', 6], ['paid', 17], ['failed', 1], ['partially_refunded', 1], ['disputed', 2]],
    'contractors_online' => [['Jordan Mercer', 'busy'], ['Priya Nair', 'available'], ['Marcus Tran', 'available']],
];
