<?php
// fixtures/queue.php — SAMPLE DATA ONLY. Sir Paul: replace with GET /api/providers/{id}/route?date= (ordered stops)
// and POST /api/stops/{id}/status {on_my_way|arrived|start|complete} + photo upload.
return [
    'team' => 'Nuahn Team A', 'date' => 'Snow day · Fri', 'snow' => '8 cm overnight',
    'stops' => [
        ['n' => 1, 'client' => 'R. Patel', 'address' => '42 Aspen Glen Pl SW', 'service' => 'Snow Removal', 'detail' => 'Double driveway + walkway', 'window' => '6:00 – 6:30 AM', 'status' => 'completed', 'plan' => 'Premium'],
        ['n' => 2, 'client' => 'K. Nguyen', 'address' => '17 Aspen Hills Ter SW', 'service' => 'Snow Removal', 'detail' => 'Single driveway, salt', 'window' => '6:30 – 7:00 AM', 'status' => 'in_progress', 'plan' => 'Premium'],
        ['n' => 3, 'client' => 'Alice Client', 'address' => '123 Maple Grove Dr SW', 'service' => 'Snow Removal', 'detail' => 'Double driveway + steps', 'window' => '6:40 – 7:10 AM', 'status' => 'next', 'plan' => 'Premium'],
        ['n' => 4, 'client' => 'D. Martin', 'address' => '9 Aspen Stone Rd SW', 'service' => 'Snow Removal', 'detail' => 'Long driveway', 'window' => '7:15 – 7:50 AM', 'status' => 'queued', 'plan' => 'Basic', 'note' => 'Moved later by client'],
        ['n' => 5, 'client' => 'S. Okafor', 'address' => '233 Aspen Summit Hts SW', 'service' => 'Garbage Can Cleaning', 'detail' => '3 bins', 'window' => '9:00 – 11:00 AM', 'status' => 'queued', 'plan' => 'Premium'],
    ],
];
