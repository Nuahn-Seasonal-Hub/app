<?php
// fixtures/weather.php — SAMPLE DATA ONLY. Sir Paul: replace with a forecast feed (e.g. Environment Canada / Open-Meteo) keyed by the client's address.
return [
    'city' => 'Edmonton, AB',
    'now' => ['temp' => -5, 'icon' => 'cloud-snow', 'text' => 'Snow expected', 'detail' => '5–10 cm overnight · Wind 15 km/h'],
    'days' => [
        ['d' => 'Today', 'icon' => 'cloud-snow', 'hi' => -3, 'lo' => -9, 'snow' => true],
        ['d' => 'Fri', 'icon' => 'snow', 'hi' => -6, 'lo' => -12, 'snow' => true],
        ['d' => 'Sat', 'icon' => 'cloud-sun', 'hi' => -2, 'lo' => -10, 'snow' => false],
        ['d' => 'Sun', 'icon' => 'sun-fill', 'hi' => 1, 'lo' => -7, 'snow' => false],
        ['d' => 'Mon', 'icon' => 'cloud', 'hi' => 0, 'lo' => -6, 'snow' => false],
        ['d' => 'Tue', 'icon' => 'cloud-snow', 'hi' => -4, 'lo' => -11, 'snow' => true],
        ['d' => 'Wed', 'icon' => 'cloud-sun', 'hi' => -1, 'lo' => -8, 'snow' => false],
    ],
];
