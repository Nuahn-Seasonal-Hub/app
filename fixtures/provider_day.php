<?php
// fixtures/provider_day.php: SAMPLE DATA ONLY. Provider dashboard "Good morning, Team A" (doc1 p4, doc2 p19).
// Sir Paul: GET /api/teams/{id}/day?date= (assigned jobs with approved scope, utilities, access, quote, plan mode, window),
// POST /api/jobs/{id}/events {arrived|start|pause|delay|complete}, POST /api/jobs/{id}/media (queued offline; "Pending sync"),
// POST /api/jobs/{id}/extra-work, GET /api/teams/{id}/reminders (Premium customers due for contact).
return [
    'team' => 'Team A', 'date' => 'Mon, Jan 20, 2025', 'time' => '7:12 AM',
    'stats' => [['clipboard-data', 8, 'Assigned', 'Jobs scheduled for today'], ['check-circle', 2, 'Completed', 'Jobs finished today'], ['cloud-snow', 1, 'Weather review', 'Job may be affected by weather']],
    'jobs' => [
        '1042' => ['id' => '#1042', 'service' => 'Snow Removal', 'slug' => 'snow-removal', 'window' => '8 – 11 AM', 'where' => 'Sample home · Edmonton, AB', 'status' => 'in_progress',
            'scope' => 'Driveway + front walkway', 'access' => 'Keep side gate closed', 'plan' => 'Premium · storm plan', 'quote' => 'Fixed price (approved)', 'utilities' => '—', 'property' => 'Standard driveway (1–2 cars)',
            'progress' => ['On the way', 'Arrived', 'Start work', 'Report delay'], 'at' => 2,
            'checklist' => [['Before photo uploaded', true], ['Driveway cleared', true], ['Front walkway cleared', true], ['After photo uploaded', false]]],
        '1043' => ['id' => '#1043', 'service' => 'Garbage Can Cleaning', 'slug' => 'garbage-bin-cleaning', 'window' => '11 AM – 1 PM', 'where' => 'Sample home · Edmonton, AB', 'status' => 'assigned',
            'scope' => '2 standard bins, odour treatment', 'access' => 'Bins beside driveway', 'plan' => 'Premium · monthly reminder', 'quote' => 'CAD 30.00 (approved)', 'utilities' => 'Provider water', 'property' => 'Collection day Tuesday',
            'progress' => ['On the way', 'Arrived', 'Start work', 'Report delay'], 'at' => -1,
            'checklist' => [['Before photo uploaded', false], ['Bins washed and sanitized', false], ['Bins returned', false], ['After photo uploaded', false]]],
        '1044' => ['id' => '#1044', 'service' => 'Lawn Mowing', 'slug' => 'lawn-mowing', 'window' => 'Time pending', 'where' => 'Sample home · Edmonton, AB', 'status' => 'weather_review',
            'scope' => 'Mowing, edging, clippings', 'access' => 'Gate code 4410, dog in yard', 'plan' => 'Basic · one cut', 'quote' => 'CAD 40.00 (approved)', 'utilities' => '—', 'property' => 'Medium lawn',
            'progress' => ['On the way', 'Arrived', 'Start work', 'Report delay'], 'at' => -1,
            'checklist' => [['Before photo uploaded', false], ['Mowing', false], ['Edging', false], ['After photo uploaded', false]]],
    ],
    'reminders' => [
        ['Alice C.', 'Mobile Car Wash at Home', 'Due Oct 14', 'sent'], ['R. Patel', 'Winter Snow Removal', 'Due Nov 1', 'scheduled'], ['K. Nguyen', 'Garbage Bin Cleaning', 'Replied: confirm', 'replied'], ['D. Martin', 'Lawn Mowing', 'Skipped this cycle', 'skipped'],
    ],
    'week' => [['Mon', '7 AM – 5 PM'], ['Tue', '7 AM – 5 PM'], ['Wed', 'Off'], ['Thu', '7 AM – 5 PM'], ['Fri', '7 AM – 3 PM'], ['Sat', 'Storm on-call'], ['Sun', 'Off']],
];
