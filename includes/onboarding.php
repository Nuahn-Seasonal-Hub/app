<?php
// includes/onboarding.php
// Onboarding, phone OTP, email verification, social/Google Reviews proof, and credential sync helpers.
// Keeps `users` (including the 40-column extended schema) and `contractor_profiles` / `contractor_documents` in sync.

require_once __DIR__ . '/marketplace/bootstrap.php';

/** Load optional local onboarding secrets (config/onboarding.local.php). */
function ob_cfg($key = null, $default = '') {
    static $cfg = null;
    if ($cfg === null) {
        $file = __DIR__ . '/../config/onboarding.local.php';
        $cfg = is_file($file) ? (array) include $file : [];
    }
    return $key === null ? $cfg : ($cfg[$key] ?? $default);
}

/** Whether live SMS OTP delivery via Twilio is configured. */
function ob_sms_live() {
    return ob_cfg('twilio_sid') !== '' && ob_cfg('twilio_token') !== '' && ob_cfg('twilio_from') !== '';
}

/** Return the set of columns currently present on the `users` table (safe even before migrations run). */
function ob_user_cols($refresh = false) {
    static $cols = null;
    if ($cols !== null && !$refresh) return $cols;
    try {
        $cols = array_flip(mk_db()->query('SHOW COLUMNS FROM `users`')->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        $cols = [];
    }
    return $cols;
}

function ob_user_has($col) {
    return isset(ob_user_cols()[$col]);
}

/**
 * Idempotently ensure all 27 extended profile/credential/verification columns and idx_status
 * exist on `users`. No-ops immediately once all columns are present. Never drops or modifies data.
 */
function ob_ensure_users_schema() {
    static $ran = false;
    if ($ran) return ['added' => 0, 'errors' => []];
    $ran = true;

    $cols = ob_user_cols();
    if (!$cols) return ['added' => 0, 'errors' => ['Could not inspect users table']];

    $defs = [
        'username'             => "varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `lastname`",
        'professional_data'    => "json DEFAULT NULL AFTER `theme_preference`",
        'phone'                => "varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `professional_data`",
        'location'             => "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `phone`",
        'profession'           => "varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `location`",
        'license_number'       => "varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `profession`",
        'insurance_number'     => "varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `license_number`",
        'certification_number' => "varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `insurance_number`",
        'issuing_authority'    => "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `certification_number`",
        'license_expiry'       => "date DEFAULT NULL AFTER `issuing_authority`",
        'years_experience'     => "int DEFAULT NULL AFTER `license_expiry`",
        'professional_bio'     => "text COLLATE utf8mb4_unicode_ci AFTER `years_experience`",
        'verification_status'  => "varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' AFTER `professional_bio`",
        'license_document'     => "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `verification_status`",
        'insurance_document'   => "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `license_document`",
        'latitude'             => "decimal(10,8) DEFAULT NULL AFTER `insurance_document`",
        'longitude'            => "decimal(11,8) DEFAULT NULL AFTER `latitude`",
        'email_verified'       => "tinyint(1) DEFAULT 0 AFTER `longitude`",
        'phone_verified'       => "tinyint(1) DEFAULT 0 AFTER `email_verified`",
        'provider_status'      => "enum('pending','active','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'pending' AFTER `phone_verified`",
        'identity_verified'    => "tinyint(1) DEFAULT 0 AFTER `provider_status`",
        'verification_token'   => "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `identity_verified`",
        'verification_sent_at' => "datetime DEFAULT NULL AFTER `verification_token`",
        'email_verified_at'    => "datetime DEFAULT NULL AFTER `verification_sent_at`",
        'license_photo'        => "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `email_verified_at`",
        'selfie_photo'         => "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `license_photo`",
        'face_verified'        => "enum('pending','verified','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending' AFTER `selfie_photo`",
    ];

    $db = mk_db();
    $added = 0;
    $errors = [];

    foreach ($defs as $col => $ddl) {
        if (isset($cols[$col])) continue;
        try {
            $db->exec("ALTER TABLE `users` ADD COLUMN `$col` $ddl");
            $cols[$col] = true;
            $added++;
        } catch (Throwable $e) {
            // Fallback without AFTER clause if a predecessor column is absent
            $plain = preg_replace('/\s+AFTER\s+`[^`]+`$/i', '', $ddl);
            try {
                $db->exec("ALTER TABLE `users` ADD COLUMN `$col` $plain");
                $cols[$col] = true;
                $added++;
            } catch (Throwable $e2) {
                $errors[] = $col . ': ' . $e2->getMessage();
                error_log('ob_ensure_users_schema ' . $col . ': ' . $e2->getMessage());
            }
        }
    }

    // Ensure idx_status index exists on users(status)
    try {
        $hasIdx = (int) $db->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_status'")->fetchColumn();
        if ($hasIdx === 0) {
            $db->exec("ALTER TABLE `users` ADD KEY `idx_status` (`status`)");
        }
    } catch (Throwable $e) {
        error_log('ob_ensure_users_schema idx_status: ' . $e->getMessage());
    }

    if ($added > 0) ob_user_cols(true);
    return ['added' => $added, 'errors' => $errors];
}

/** Run the idempotent database/migrations.sql file using the active PDO connection (staff-only). */
function ob_run_migrations_file() {
    $res = ob_ensure_users_schema();
    $path = __DIR__ . '/../database/migrations.sql';
    if (!is_file($path)) throw new MkError('database/migrations.sql not found.');
    $sql = (string) file_get_contents($path);
    $db = mk_db();
    // Split on semicolon + newline outside quotes (migrations.sql uses standard semicolons, no DELIMITER)
    $lines = preg_split('/\R/', $sql);
    $buf = '';
    $ran = 0;
    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '' || strpos($trim, '--') === 0) continue;
        $buf .= $line . "\n";
        if (substr($trim, -1) === ';') {
            $stmt = trim($buf);
            $buf = '';
            if ($stmt !== '' && $stmt !== ';') {
                $db->exec($stmt);
                $ran++;
            }
        }
    }
    ob_user_cols(true);
    return ['users_columns_added' => $res['added'], 'statements_run' => $ran, 'errors' => $res['errors']];
}

/** Fetch full user row by id. */
function ob_user_get($uid) {
    $st = mk_db()->prepare('SELECT * FROM `users` WHERE `id` = ?');
    $st->execute([(int) $uid]);
    return $st->fetch() ?: null;
}

/** Update only columns that exist on `users`, ignoring missing columns gracefully. */
function ob_user_update($uid, array $fields) {
    $cols = ob_user_cols();
    $set = [];
    $args = [];
    foreach ($fields as $k => $v) {
        if (!isset($cols[$k])) continue;
        if ($v === '@now') {
            $set[] = "`$k` = NOW()";
        } else {
            $set[] = "`$k` = ?";
            $args[] = $v;
        }
    }
    if (!$set) return;
    $args[] = (int) $uid;
    mk_db()->prepare('UPDATE `users` SET ' . implode(', ', $set) . ' WHERE `id` = ?')->execute($args);
}

/** Decode `users.professional_data` JSON into an associative array. */
function ob_prof_data($user) {
    if (!$user || empty($user['professional_data'])) return [];
    $d = is_array($user['professional_data']) ? $user['professional_data'] : json_decode((string) $user['professional_data'], true);
    return is_array($d) ? $d : [];
}

/** Merge keys into `users.professional_data` JSON. */
function ob_prof_data_merge($uid, array $patch) {
    if (!ob_user_has('professional_data')) return;
    $u = ob_user_get($uid);
    $cur = ob_prof_data($u);
    $merged = array_merge($cur, $patch);
    ob_user_update($uid, ['professional_data' => json_encode($merged, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
}

/** Services offered by providers during onboarding. */
function ob_provider_services() {
    return [
        'snow-removal'     => ['name' => 'Winter Snow Removal', 'licensed' => false, 'live' => true],
        'mobile-mechanics' => ['name' => 'Mobile Mechanics',    'licensed' => true,  'live' => true],
        'lawn-care'        => ['name' => 'Lawn & Yard Care',    'licensed' => false, 'live' => false],
        'cleaning'         => ['name' => 'Home Cleaning',       'licensed' => false, 'live' => false],
        'handyman'         => ['name' => 'Handyman & Repairs',  'licensed' => false, 'live' => false],
        'moving'           => ['name' => 'Moving & Hauling',    'licensed' => false, 'live' => false],
    ];
}

/** Major Canadian cities for primary city autocomplete. */
function ob_canadian_cities() {
    return [
        'Edmonton, AB', 'Calgary, AB', 'St. Albert, AB', 'Sherwood Park, AB', 'Red Deer, AB', 'Lethbridge, AB',
        'Toronto, ON', 'Brampton, ON', 'Mississauga, ON', 'Ottawa, ON', 'Hamilton, ON', 'London, ON',
        'Kitchener, ON', 'Waterloo, ON', 'Markham, ON', 'Vaughan, ON', 'Oakville, ON', 'Windsor, ON',
        'Vancouver, BC', 'Surrey, BC', 'Burnaby, BC', 'Victoria, BC', 'Kelowna, BC',
        'Montreal, QC', 'Quebec City, QC', 'Laval, QC', 'Gatineau, QC',
        'Halifax, NS', 'Moncton, NB', 'Fredericton, NB', 'Saint John, NB',
        'Winnipeg, MB', 'Saskatoon, SK', 'Regina, SK', "St. John's, NL",
    ];
}

/** Normalize a country code + 10-digit North American phone number (returns E.164 like +17805551234). */
function ob_phone_normalize($code, $raw) {
    $digits = preg_replace('/\D+/', '', (string) $raw);
    $cc = preg_replace('/[^\d+]/', '', (string) $code);
    if ($cc === '' || $cc === '+') $cc = '+1';
    if ($cc[0] !== '+') $cc = '+' . $cc;
    if (strlen($digits) === 11 && $digits[0] === '1' && $cc === '+1') {
        $digits = substr($digits, 1);
    }
    if (strlen($digits) !== 10) {
        throw new MkError('Enter a valid 10-digit phone number.');
    }
    return $cc . $digits;
}

/**
 * Issue a 6-digit SMS OTP for a phone number + purpose ('signup' | 'login' | 'verify').
 * Uses Twilio when configured; otherwise runs in Beta Sandbox mode (returns code in response so testers can proceed).
 */
function ob_otp_send($phoneE164, $purpose = 'signup') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $key = 'ob_otp_' . $purpose;
    $prev = $_SESSION[$key] ?? null;
    if ($prev && ($prev['phone'] ?? '') === $phoneE164 && (time() - (int) ($prev['sent_at'] ?? 0)) < 30) {
        $wait = 30 - (time() - (int) $prev['sent_at']);
        throw new MkError("Please wait {$wait}s before requesting another code.");
    }
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION[$key] = [
        'phone'    => $phoneE164,
        'hash'     => password_hash($code, PASSWORD_DEFAULT),
        'sent_at'  => time(),
        'expires'  => time() + 600,
        'attempts' => 0,
        'verified' => false,
    ];

    if (ob_sms_live()) {
        $sid   = ob_cfg('twilio_sid');
        $tok   = ob_cfg('twilio_token');
        $from  = ob_cfg('twilio_from');
        $url   = "https://api.twilio.com/2010-04-01/Accounts/" . rawurlencode($sid) . "/Messages.json";
        $body  = http_build_query([
            'To'   => $phoneE164,
            'From' => $from,
            'Body' => "Your Nuahn Seasonal Hub verification code is {$code}. It expires in 10 minutes.",
        ]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_USERPWD        => $sid . ':' . $tok,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http < 200 || $http >= 300) {
            error_log('Twilio OTP failed (' . $http . '): ' . (string) $resp);
            throw new MkError('Could not send SMS right now. Please check the number and try again.');
        }
        return ['sent' => true, 'sandbox' => false, 'phone' => $phoneE164];
    }

    return ['sent' => true, 'sandbox' => true, 'sandbox_code' => $code, 'phone' => $phoneE164];
}

/** Verify a 6-digit OTP code for a phone number + purpose. */
function ob_otp_verify($phoneE164, $code, $purpose = 'signup') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $key = 'ob_otp_' . $purpose;
    $rec = $_SESSION[$key] ?? null;
    $clean = preg_replace('/\D+/', '', (string) $code);
    if (!$rec || ($rec['phone'] ?? '') !== $phoneE164) {
        throw new MkError('Request a verification code for this phone number first.');
    }
    if (time() > (int) ($rec['expires'] ?? 0)) {
        unset($_SESSION[$key]);
        throw new MkError('That verification code expired. Click Get Code to send a new one.');
    }
    if ((int) ($rec['attempts'] ?? 0) >= 5) {
        unset($_SESSION[$key]);
        throw new MkError('Too many wrong attempts. Request a new code.');
    }
    if (strlen($clean) !== 6 || !password_verify($clean, (string) $rec['hash'])) {
        $_SESSION[$key]['attempts'] = ((int) $rec['attempts']) + 1;
        throw new MkError('Invalid 6-digit verification code.');
    }
    unset($_SESSION[$key]);
    return true;
}

/** Build an absolute URL for links sent in verification emails. */
function ob_abs_url($path) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . nu_url($path);
}

/** Issue a fresh email verification token and attempt to send the verification email. */
function ob_email_send_verification($uid) {
    $u = ob_user_get($uid);
    if (!$u || empty($u['email'])) return null;
    $token = bin2hex(random_bytes(24));
    ob_user_update($uid, [
        'verification_token'   => $token,
        'verification_sent_at' => '@now',
    ]);
    $link = ob_abs_url('public/verify_email.php?token=' . rawurlencode($token));
    $from = ob_cfg('mail_from', 'no-reply@nuahnseasonalhub.com');
    $subject = 'Verify your email · Nuahn Seasonal Hub';
    $body = "Hi " . ($u['name'] ?: 'there') . ",\n\n"
          . "Please verify your email address for Nuahn Seasonal Hub by opening this link:\n\n"
          . $link . "\n\n"
          . "If you did not create this account, you can ignore this email.\n";
    $headers = "From: Nuahn Seasonal Hub <{$from}>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    @mail($u['email'], $subject, $body, $headers);
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['ob_last_verify_link'] = $link;
    return $link;
}

/** Verify a user's email address from a token. Returns the user row or null. */
function ob_email_verify_token($token) {
    $token = trim((string) $token);
    if ($token === '' || !preg_match('/^[a-f0-9]{32,64}$/', $token) || !ob_user_has('verification_token')) {
        return null;
    }
    $st = mk_db()->prepare('SELECT * FROM `users` WHERE `verification_token` = ? LIMIT 1');
    $st->execute([$token]);
    $u = $st->fetch();
    if (!$u) return null;
    ob_user_update($u['id'], [
        'email_verified'     => 1,
        'email_verified_at'  => '@now',
        'verification_token' => null,
    ]);
    mk_audit('email.verified', 'user', (int) $u['id'], $u['email'], (int) $u['id'], (string) ($u['role'] ?? 'client'));
    return $u;
}

/** Store a provider profile picture into uploads/ using GD re-encoding (strips EXIF). */
function ob_avatar_store($uid, $field = 'avatar') {
    $files = mk_files($field);
    if (!$files) return null;
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new MkError('Profile photo uploads are unavailable right now.');
    }
    $r = mk_image_reencode($files[0], $dir);
    $fname = 'profile_' . $r['name'];
    $dest = $dir . '/' . $fname;
    if (!@rename($r['path'], $dest)) {
        $fname = $r['name'];
    }
    ob_user_update($uid, ['photo' => $fname]);
    mk_audit('profile.photo', 'user', (int) $uid, $fname, (int) $uid, 'provider');
    return $fname;
}

/** Store a private verification selfie under uploads/jobs/_docs/{uid}/ and update users.selfie_photo. */
function ob_selfie_store($uid, $field = 'selfie') {
    $files = mk_files($field);
    if (!$files) throw new MkError('Choose a clear selfie photo to upload.');
    $dir = mk_upload_dir('_docs/' . (int) $uid);
    $r = mk_image_reencode($files[0], $dir);
    $rel = '_docs/' . (int) $uid . '/' . $r['name'];
    ob_user_update($uid, [
        'selfie_photo'  => $rel,
        'face_verified' => 'pending',
    ]);
    mk_audit('selfie.uploaded', 'user', (int) $uid, $rel, (int) $uid, 'provider');
    return $rel;
}

/** Validate social & website URLs (matching UrbanTasker domain rules). */
function ob_validate_social_urls(array $in) {
    $out = [];
    $website = trim((string) ($in['website_url'] ?? ''));
    if ($website !== '' && $website !== 'https://' && $website !== 'http://') {
        if (!preg_match('#^https?://#i', $website)) $website = 'https://' . $website;
        $p = @parse_url($website);
        $host = strtolower((string) ($p['host'] ?? ''));
        if (!$p || !in_array(strtolower((string) ($p['scheme'] ?? '')), ['http', 'https'], true) || strpos($host, '.') === false) {
            throw new MkError('Please enter a valid Website URL.');
        }
        foreach (['facebook.com', 'instagram.com', 'homestars.com', 'tiktok.com'] as $soc) {
            if (strpos($host, $soc) !== false) {
                throw new MkError('Enter Facebook, Instagram, HomeStars, or TikTok links in their own fields — not Website.');
            }
        }
        $out['website_url'] = $website;
    } else {
        $out['website_url'] = '';
    }

    $ig = trim((string) ($in['instagram_url'] ?? ''));
    $ig = preg_replace('#^https?://(www\.)?instagram\.com/#i', '', $ig);
    $ig = ltrim($ig, '@/');
    $ig = rtrim($ig, '/');
    if ($ig !== '' && !preg_match('/^[A-Za-z0-9._]{1,40}$/', $ig)) {
        throw new MkError('Enter a valid Instagram username (letters, numbers, dots, underscores).');
    }
    $out['instagram_url'] = $ig !== '' ? 'https://instagram.com/' . $ig : '';

    $domains = [
        'facebook_url'  => ['facebook.com', 'Please enter a valid Facebook page URL.'],
        'homestars_url' => ['homestars.com', 'Please enter a valid HomeStars profile URL.'],
        'tiktok_url'    => ['tiktok.com', 'Please enter a valid TikTok profile URL.'],
    ];
    foreach ($domains as $key => [$needle, $err]) {
        $val = trim((string) ($in[$key] ?? ''));
        if ($val === '') { $out[$key] = ''; continue; }
        if (!preg_match('#^https?://#i', $val)) $val = 'https://' . $val;
        $p = @parse_url($val);
        $host = strtolower((string) ($p['host'] ?? ''));
        if (!$p || strpos($host, $needle) === false) throw new MkError($err);
        $out[$key] = $val;
    }
    return $out;
}

/** Save or resubmit a Google Business / Google Reviews link (stored in users.professional_data). */
function ob_google_reviews_save($uid, $businessName, $mapsUrl, $placeId = '') {
    $u = ob_user_get($uid);
    $pd = ob_prof_data($u);
    $curStatus = (string) ($pd['google_reviews_status'] ?? '');
    if (in_array($curStatus, ['approved', 'in_review'], true) && !empty($pd['google_business_name'])) {
        throw new MkError('Your Google Business is already linked and cannot be changed once verified.');
    }
    $businessName = mk_clean($businessName, 160);
    $mapsUrl = trim((string) $mapsUrl);
    $placeId = mk_clean($placeId, 160);
    if ($businessName === '') {
        throw new MkError('Enter your Google Business name to import reviews.');
    }
    if ($mapsUrl !== '') {
        if (!preg_match('#^https?://#i', $mapsUrl)) $mapsUrl = 'https://' . $mapsUrl;
        $host = strtolower((string) parse_url($mapsUrl, PHP_URL_HOST));
        if (!preg_match('/(google\.|goo\.gl|g\.page)/', $host)) {
            throw new MkError('Enter a valid Google Maps / Google Business URL (google.com, maps.app.goo.gl, or g.page).');
        }
    }
    ob_prof_data_merge($uid, [
        'google_business_name'         => $businessName,
        'google_maps_url'              => $mapsUrl,
        'google_place_id'              => $placeId,
        'google_reviews_status'        => 'pending',
        'google_reviews_submitted_at'  => date('Y-m-d H:i:s'),
    ]);
    mk_audit('google_reviews.submitted', 'user', (int) $uid, $businessName, (int) $uid, 'provider');
}

/** Keep users.provider_status / verification_status / identity_verified in sync with contractor_profiles. */
function ob_sync_provider_status($uid, $vettingStatus, array $checks = []) {
    $provMap = [
        'draft'             => 'pending',
        'pending'           => 'pending',
        'in_review'         => 'pending',
        'changes_requested' => 'pending',
        'approved'          => 'active',
        'rejected'          => 'suspended',
    ];
    $fields = [
        'verification_status' => $vettingStatus,
        'provider_status'     => $provMap[$vettingStatus] ?? 'pending',
    ];
    if (isset($checks['chk_identity'])) {
        $fields['identity_verified'] = $checks['chk_identity'] === 'ok' ? 1 : 0;
    }
    ob_user_update($uid, $fields);
}

/** Find a user by normalized phone number (supports +1XXXXXXXXXX or raw 10-digit formats). */
function ob_find_user_by_phone($phoneE164) {
    if (!ob_user_has('phone')) return null;
    $digits10 = preg_replace('/\D+/', '', $phoneE164);
    if (strlen($digits10) === 11 && $digits10[0] === '1') $digits10 = substr($digits10, 1);
    $st = mk_db()->prepare("SELECT * FROM `users` WHERE `phone` = ? OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`phone`, '+1', ''), '-', ''), ' ', ''), '(', ''), ')', '') = ? LIMIT 1");
    $st->execute([$phoneE164, $digits10]);
    return $st->fetch() ?: null;
}

/** Compute provider verification checklist summary (5 pillars matching UrbanTasker's hub). */
function ob_verification_summary($user, $profile) {
    $pd = ob_prof_data($user);
    $trades = $profile ? mk_trades($profile) : [];
    $needsLicence = in_array('mobile-mechanics', $trades, true);
    $emailOk = !empty($user['email_verified']);
    $phoneOk = !empty($user['phone_verified']);
    $contactOk = $emailOk && $phoneOk;
    $idStatus = (string) ($profile['chk_identity'] ?? 'missing');
    $insStatus = (string) ($profile['chk_insurance'] ?? 'missing');
    $licStatus = (string) ($profile['chk_licence'] ?? ($needsLicence ? 'missing' : 'na'));
    $grStatus = (string) ($pd['google_reviews_status'] ?? '');

    $requiredDone = 0;
    $requiredTotal = 2 + ($needsLicence ? 1 : 0); // ID + Insurance (+ Licence for mechanics)
    if (in_array($idStatus, ['review', 'ok'], true)) $requiredDone++;
    if (in_array($insStatus, ['review', 'ok'], true)) $requiredDone++;
    if ($needsLicence && in_array($licStatus, ['review', 'ok'], true)) $requiredDone++;

    return [
        'email_ok'       => $emailOk,
        'phone_ok'       => $phoneOk,
        'contact_ok'     => $contactOk,
        'id_status'      => $idStatus,
        'ins_status'     => $insStatus,
        'lic_status'     => $licStatus,
        'needs_licence'  => $needsLicence,
        'gr_status'      => $grStatus,
        'required_done'  => $requiredDone,
        'required_total' => $requiredTotal,
        'pct'            => $requiredTotal > 0 ? (int) round(($requiredDone / $requiredTotal) * 100) : 100,
    ];
}

/** Validate and normalize a 6-character Canadian postal code (returns e.g. "T5J 0N3" or '' if empty). */
function ob_postal_normalize($raw) {
    $clean = strtoupper(preg_replace('/\s+/', '', trim((string) $raw)));
    if ($clean === '') return '';
    if (!preg_match('/^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z]\d[ABCEGHJ-NPRSTV-Z]\d$/', $clean)) {
        throw new MkError('Enter a valid 6-character Canadian postal code (e.g. T5J 0N3).');
    }
    return substr($clean, 0, 3) . ' ' . substr($clean, 3, 3);
}

/** True when a client signed up via SMS OTP without providing a real email address yet. */
function ob_is_placeholder_email($email) {
    return (bool) preg_match('/^phone_\d+@phone\.nuahn\.local$/i', trim((string) $email));
}

/**
 * Compute progressive non-blocking verification state for a client/customer account.
 * Mirrors UrbanTasker's non-blocking pending status labels while keeping task posting frictionless.
 */
function ob_client_verification_status($user) {
    $email = trim((string) ($user['email'] ?? ''));
    $hasRealEmail = $email !== '' && !ob_is_placeholder_email($email);
    $emailOk = $hasRealEmail && !empty($user['email_verified']);
    $phoneOk = !empty($user['phone_verified']) && trim((string) ($user['phone'] ?? '')) !== '';
    $pendingCount = ($emailOk ? 0 : 1) + ($phoneOk ? 0 : 1);

    if (!$emailOk && !$phoneOk) {
        $label = $hasRealEmail ? 'Email and phone verification pending' : 'Add your email & verify phone (optional)';
    } elseif (!$emailOk) {
        $label = $hasRealEmail ? 'Email verification pending' : 'Add an email address for receipts';
    } elseif (!$phoneOk) {
        $label = 'Phone verification pending (optional)';
    } else {
        $label = '';
    }

    return [
        'has_real_email' => $hasRealEmail,
        'email_ok'       => $emailOk,
        'phone_ok'       => $phoneOk,
        'pending_count'  => $pendingCount,
        'label'          => $label,
    ];
}

