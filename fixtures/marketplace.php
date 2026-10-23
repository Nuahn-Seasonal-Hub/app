<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// fixtures/marketplace.php: SAMPLE DATA ONLY. Marketplace layer: job state machine, snow pricing model, boosts,
// contractor offers, open jobs (provider board), vetting queue, disputes, payout / commission sample.
// Every number is invented for the preview and is NOT a business price or commission rate.
// Sir Paul: see docs/UI-DATA-HOOKUP.md sections 26 to 33 for the endpoints each block maps to.
return [
    // Job state machine (spec): job status and payment status are two separate columns.
    'states' => [
        'requested' => 'Requested', 'open_for_bids' => 'Open for bids', 'confirmed' => 'Confirmed · funds held', 'on_the_way' => 'On the way',
        'arrived' => 'Arrived', 'in_progress' => 'In progress', 'pending_approval' => 'Pending approval', 'approved' => 'Approved',
    ],
    'commission_pct' => 12,   // sample only; business to confirm

    // Snow task estimator (client-side, illustrative). All CAD.
    'snow' => [
        'sizes' => [
            'standard'   => ['label' => 'Standard', 'sub' => '1–2 cars', 'lo' => 35, 'hi' => 55, 'icon' => 'car-front'],
            'large'      => ['label' => 'Large', 'sub' => '3–4 cars', 'lo' => 60, 'hi' => 90, 'icon' => 'truck'],
            'commercial' => ['label' => 'Commercial / custom', 'sub' => 'Enter sq ft', 'lo' => 0, 'hi' => 0, 'icon' => 'building', 'per_sqft' => [0.018, 0.03]],
        ],
        'walk' => ['none' => ['None', 0, 0], 'front' => ['Front walkway', 8, 12], 'full' => ['Front + side walkways', 14, 22]],
        'steps' => ['Steps and stairs' => [5, 8]],
        'ice' => ['label' => 'Ice melt on walkways and steps', 'lo' => 8, 'hi' => 14],
        'seasonal_discount' => 0.10,
        'trigger_cm' => 5,
        // expected pickup time by how the budget compares with the suggested midpoint
        'pickup' => [[0.80, 'Could take 2 h or more', 'low'], [0.95, 'Around 60 min', 'mid'], [1.10, 'Around 35 min', 'good'], [1.30, 'Around 20 min', 'fast'], [99, 'Under 10 min', 'top']],
        'boost_max' => 25,
    ],

    // Offers by state for the "Review contractor offers" screen.
    'task' => ['id' => 'TASK-2051', 'service' => 'snow-removal', 'title' => 'Snow clearing · Standard driveway + front walkway', 'address' => '123 Maple Grove Drive, Edmonton, AB', 'when' => 'On demand · after the next 5 cm snowfall', 'target' => 45, 'posted' => '12 min ago', 'scope' => ['Standard driveway (1–2 cars)', 'Front walkway', 'Steps and stairs', 'Ice melt added']],
    'offers' => [
        'none' => [],
        'one' => [
            ['c' => 'c-1001', 'price' => 48, 'eta' => 'Today · about 40 min', 'note' => 'I am finishing a job two streets over. I can start right after. Ice melt is included.', 'tags' => ['Fastest']],
        ],
        'many' => [
            ['c' => 'c-1001', 'price' => 48, 'eta' => 'Today · about 40 min', 'note' => 'Two streets away now. Ice melt included.', 'tags' => ['Top rated', 'Fastest']],
            ['c' => 'c-1004', 'price' => 39, 'eta' => 'Today · about 1 h 30 min', 'note' => 'New on Nuahn so I price to win my first jobs. Blower and shovel, steps included.', 'tags' => ['Best price']],
            ['c' => 'c-1002', 'price' => 52, 'eta' => 'Tomorrow · 7:00 – 8:00 AM', 'note' => 'Hand-finished steps and walkway, salted. Before and after photos as standard.', 'tags' => []],
            ['c' => 'c-1003', 'price' => 45, 'eta' => 'Today · about 2 h', 'note' => 'Matches your target. Plow plus salter, edges finished by hand.', 'tags' => ['Matches target']],
        ],
        'boosted' => [
            ['c' => 'c-1001', 'price' => 68, 'eta' => 'Today · about 15 min', 'note' => 'Boosted job, so I am dropping the next one. On my way once you accept.', 'tags' => ['Fastest', 'Top rated']],
            ['c' => 'c-1003', 'price' => 66, 'eta' => 'Today · about 25 min', 'note' => 'Can be there within half an hour.', 'tags' => []],
            ['c' => 'c-1002', 'price' => 70, 'eta' => 'Today · about 35 min', 'note' => 'Walkway and steps by hand, salted.', 'tags' => []],
        ],
    ],

    // Provider "Open jobs near you" board.
    'open_jobs' => [
        ['id' => 'TASK-2051', 'svc' => 'snow-removal', 'title' => 'Standard driveway + front walkway', 'area' => 'Maple Grove, Edmonton SW', 'dist' => '2.4 km', 'when' => 'On demand', 'budget' => 45, 'posted' => '12 min ago', 'boost' => false, 'scope' => 'Steps and stairs · ice melt added', 'bids' => 3],
        ['id' => 'TASK-2049', 'svc' => 'snow-removal', 'title' => 'Large driveway (3–4 cars)', 'area' => 'Terwillegar, Edmonton SW', 'dist' => '5.1 km', 'when' => 'Seasonal plan · 5 cm trigger', 'budget' => 70, 'posted' => '31 min ago', 'boost' => true, 'scope' => 'Front and side walkways', 'bids' => 1],
        ['id' => 'ROAD-3310', 'svc' => 'mobile-mechanics', 'title' => 'Roadside · battery boost', 'area' => 'Whitemud Dr at 111 St', 'dist' => '3.8 km', 'when' => 'Now', 'budget' => 85, 'posted' => '3 min ago', 'boost' => false, 'scope' => '2018 Honda Civic · will not start', 'bids' => 0, 'roadside' => true],
        ['id' => 'MECH-3302', 'svc' => 'mobile-mechanics', 'title' => 'Brake pads (front) · quote needed', 'area' => 'Riverbend, Edmonton SW', 'dist' => '6.7 km', 'when' => 'Scheduled · this week', 'budget' => 0, 'posted' => '1 h ago', 'boost' => false, 'scope' => '2020 Toyota RAV4 · squeaking', 'bids' => 2, 'quote' => true],
    ],

    // Admin: vetting queue (identity, insurance, licence, certification) and disputes.
    'vetting' => [
        ['id' => 'APP-0412', 'name' => 'Luca Bianchi', 'trade' => 'Mobile Mechanics', 'submitted' => 'Today · 8:40 AM', 'checks' => ['identity' => 'ok', 'insurance' => 'review', 'licence' => 'ok', 'cert' => 'review'], 'note' => 'Certificate scan is blurry. Ask for a clearer copy.'],
        ['id' => 'APP-0411', 'name' => 'Amrit Gill', 'trade' => 'Winter Snow Removal', 'submitted' => 'Today · 7:15 AM', 'checks' => ['identity' => 'review', 'insurance' => 'ok', 'licence' => 'na', 'cert' => 'na'], 'note' => 'Photo ID does not match the selfie yet.'],
        ['id' => 'APP-0409', 'name' => 'Chloe Martin', 'trade' => 'Winter Snow Removal', 'submitted' => 'Yesterday', 'checks' => ['identity' => 'ok', 'insurance' => 'missing', 'licence' => 'na', 'cert' => 'na'], 'note' => 'Insurance certificate not uploaded.'],
        ['id' => 'APP-0405', 'name' => 'Ben Ivanov', 'trade' => 'Mobile Mechanics', 'submitted' => 'Yesterday', 'checks' => ['identity' => 'ok', 'insurance' => 'ok', 'licence' => 'ok', 'cert' => 'ok'], 'note' => 'All checks passed. Ready to approve.'],
    ],
    'disputes' => [
        ['id' => 'CASE-88', 'job' => 'SNOW-1031', 'service' => 'Winter Snow Removal', 'client' => 'Alice C.', 'contractor' => 'c-1001', 'held' => 120.00, 'reason' => 'Steps were not cleared', 'opened' => 'Today · 8:02 AM', 'status' => 'open'],
        ['id' => 'CASE-85', 'job' => 'MECH-1031', 'service' => 'Mobile Mechanics', 'client' => 'P. Wright', 'contractor' => 'c-2003', 'held' => 120.00, 'reason' => 'Work does not match the approved quote', 'opened' => 'Yesterday', 'status' => 'review'],
    ],

    // Provider payout sample. Totals are built from these in the UI.
    'payout' => ['job' => 'SNOW-1031', 'total' => 90.00, 'tip' => 0.00, 'pct' => 12, 'tax_note' => 'Contractors are responsible for their own taxes. GST/PST on the quote is collected and remitted as required.'],
    'payouts' => [['SNOW-1031', 'Today', 90.00, 'held', 'Releases when the client approves'], ['SNOW-1028', 'Mon', 55.00, 'captured', 'Paid out Tue'], ['MECH-1022', 'Fri', 212.50, 'captured', 'Paid out Mon'], ['SNOW-1019', 'Last week', 45.00, 'refunded', 'Refunded after review']],
];
