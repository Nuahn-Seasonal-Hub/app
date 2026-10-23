<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// fixtures/contractors.php: SAMPLE DATA ONLY. Independent contractor profiles for the marketplace UI.
// Every name, rating, count and date here is invented for the preview and must be tagged "Sample data" where shown.
// Sir Paul: GET /api/contractors/{id} (public profile fields only: never email, phone or exact address),
// GET /api/contractors/{id}/reviews?page=. badges come from the vetting record (ID check, insurance certificate on file
// and not expired, licence / trade certification where required), never from self-declaration.
// badges: verified (Nuahn review passed), insured (liability cover on file), id_checked (government ID matched), certified (trade certificate).
return [
    'c-1001' => [
        'id' => 'c-1001', 'name' => 'Jordan Mercer', 'initials' => 'JM', 'tone' => 'teal', 'trades' => ['snow-removal'],
        'headline' => 'Driveways, walkways and steps, cleared before the school run.',
        'rating' => 4.9, 'reviews' => 212, 'jobs' => 318, 'response' => '~6 min', 'since' => 'Nov 2023', 'area' => 'Edmonton SW', 'radius' => '15 km',
        'badges' => ['verified', 'insured', 'id_checked'],
        'equipment' => 'Plow truck, 2 walk-behind blowers, ice melt on board',
        'about' => 'Independent snow contractor. I run my own truck with one helper, and I quote the job I will actually do. Ice melt is always on the truck if you want it.',
        'services' => ['Driveway clearing (1 to 4 cars)', 'Walkways and steps', 'Ice melt application', 'Seasonal plans with a snowfall trigger'],
        'reviews_list' => [
            ['Alice J.', 5, 'On time, clean edges, and he cleared the steps without being asked.', 'Last Sat'],
            ['R. Patel', 5, 'Fast reply and fair price. Photos before and after, exactly as promised.', 'Dec 2025'],
            ['K. Nguyen', 4, 'Great work. Arrived a little later than the estimate because of the storm.', 'Dec 2025'],
        ],
    ],
    'c-1002' => [
        'id' => 'c-1002', 'name' => 'Priya Nair', 'initials' => 'PN', 'tone' => 'blue', 'trades' => ['snow-removal'],
        'headline' => 'Careful hand-shovel work for steps, stairs and tight driveways.',
        'rating' => 4.8, 'reviews' => 97, 'jobs' => 141, 'response' => '~11 min', 'since' => 'Jan 2024', 'area' => 'Edmonton West', 'radius' => '10 km',
        'badges' => ['verified', 'insured', 'id_checked'],
        'equipment' => 'Walk-behind blower, shovels, salt and sand',
        'about' => 'I specialise in homes with steps, narrow lanes and long walkways where a truck does not fit.',
        'services' => ['Walkways and steps', 'Driveway clearing (1 to 2 cars)', 'Ice melt application'],
        'reviews_list' => [
            ['M. Singh', 5, 'Steps were spotless and salted. Will rebook for the season.', 'Jan 2026'],
            ['L. Tremblay', 5, 'Kind and careful with the flower beds.', 'Dec 2025'],
        ],
    ],
    'c-1003' => [
        'id' => 'c-1003', 'name' => 'Dana Kowalski', 'initials' => 'DK', 'tone' => 'orange', 'trades' => ['snow-removal'],
        'headline' => 'Large driveways and small commercial lots.',
        'rating' => 4.7, 'reviews' => 58, 'jobs' => 76, 'response' => '~18 min', 'since' => 'Oct 2024', 'area' => 'Sherwood Park', 'radius' => '25 km',
        'badges' => ['verified', 'insured', 'id_checked'],
        'equipment' => 'Pickup with 8 ft plow, tow-behind salter',
        'about' => 'Plow driver with my own insurance and equipment. Happy to quote custom square footage for lots and shared drives.',
        'services' => ['Large and commercial driveways', 'Plow and salt', 'Seasonal plans'],
        'reviews_list' => [['T. Boucher', 5, 'Handled our 4-car drive in under an hour.', 'Jan 2026'], ['A. Fontaine', 4, 'Good value. Left a small windrow at the curb.', 'Dec 2025']],
    ],
    'c-1004' => [
        'id' => 'c-1004', 'name' => 'Tyler Boucher', 'initials' => 'TB', 'tone' => 'purple', 'trades' => ['snow-removal'],
        'headline' => 'New to Nuahn. Local, quick and keen to earn a first review.',
        'rating' => 5.0, 'reviews' => 3, 'jobs' => 4, 'response' => '~9 min', 'since' => 'Oct 2026', 'area' => 'Edmonton South', 'radius' => '8 km',
        'badges' => ['verified', 'insured', 'id_checked'],
        'equipment' => 'Snow blower, shovels',
        'about' => 'Joined this season. Verified and insured through Nuahn, and priced to win your first jobs.',
        'services' => ['Driveway clearing (1 to 2 cars)', 'Walkways'],
        'reviews_list' => [['G. Morin', 5, 'Showed up early and the work was great.', 'Oct 2026']],
    ],
    'c-2001' => [
        'id' => 'c-2001', 'name' => 'Marcus Tran', 'initials' => 'MT', 'tone' => 'teal', 'trades' => ['mobile-mechanics'],
        'headline' => 'Red Seal mechanic. Brakes, batteries and diagnostics at your door.',
        'rating' => 4.9, 'reviews' => 164, 'jobs' => 230, 'response' => '~9 min', 'since' => 'Feb 2024', 'area' => 'Edmonton + St. Albert', 'radius' => '30 km',
        'badges' => ['verified', 'insured', 'id_checked', 'certified'],
        'cert' => 'Red Seal Automotive Service Technician',
        'equipment' => 'Fully equipped service van, OBD-II scanner, mobile lift jacks',
        'about' => 'Fifteen years in dealerships before going independent. I itemise every quote and I pause and ask before any extra work.',
        'services' => ['Brake pads and rotors', 'Battery testing and replacement', 'Check engine diagnostics', 'Oil and filter', 'Roadside: boost and lockout'],
        'reviews_list' => [
            ['Alice J.', 5, 'Clear quote, no surprises, and he found worn rotors and asked before touching them.', 'Oct 2026'],
            ['P. Wright', 5, 'Fixed my brakes in the driveway in two hours.', 'Sep 2026'],
            ['N. Haddad', 5, 'Diagnosed the check engine light on the spot.', 'Sep 2026'],
        ],
    ],
    'c-2002' => [
        'id' => 'c-2002', 'name' => 'Sam Okafor', 'initials' => 'SO', 'tone' => 'blue', 'trades' => ['mobile-mechanics'],
        'headline' => 'Roadside specialist: flat tires, boosts, lockouts and fuel.',
        'rating' => 4.8, 'reviews' => 301, 'jobs' => 540, 'response' => '~4 min', 'since' => 'Apr 2024', 'area' => 'Edmonton, 24/7', 'radius' => '20 km',
        'badges' => ['verified', 'insured', 'id_checked', 'certified'],
        'cert' => 'Licensed roadside technician',
        'equipment' => 'Roadside rig, jump pack, tire plug kit, lockout tools, 20 L fuel cans',
        'about' => 'I take roadside calls day and night. I check photo ID and registration before any lockout.',
        'services' => ['Flat tire', 'Battery boost', 'Lockout', 'Fuel delivery', 'Tire pressure'],
        'reviews_list' => [['D. Martin', 5, 'Arrived in 15 minutes on a freezing night.', 'Jan 2026'], ['S. Lee', 5, 'Honest price and very polite.', 'Dec 2025']],
    ],
    'c-2003' => [
        'id' => 'c-2003', 'name' => 'Elena Rossi', 'initials' => 'ER', 'tone' => 'orange', 'trades' => ['mobile-mechanics'],
        'headline' => 'Tire swaps, fluids and routine maintenance on your schedule.',
        'rating' => 4.7, 'reviews' => 72, 'jobs' => 118, 'response' => '~14 min', 'since' => 'Jun 2024', 'area' => 'Edmonton North', 'radius' => '18 km',
        'badges' => ['verified', 'insured', 'id_checked', 'certified'],
        'cert' => 'Certified automotive technician',
        'equipment' => 'Service van, torque tools, tire balancer',
        'about' => 'Routine work done right, with the old parts shown to you.',
        'services' => ['Oil and filter', 'Tire rotation and swap', 'Fluid top-off', 'Battery replacement'],
        'reviews_list' => [['C. Dubois', 5, 'Swapped winter tires in my garage. Quick and tidy.', 'Oct 2026']],
    ],
];
