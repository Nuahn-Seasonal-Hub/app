<?php
// actions/onboarding.php
// Handles SMS OTP, passwordless OTP login, 3-step provider onboarding wizard,
// email/phone verification, credentials save, selfie upload, and Google Reviews import.
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../includes/onboarding.php';

function ob_json(array $payload, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function ob_redirect($path) {
    header('Location: ' . nu_url($path), true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

$do = (string) ($_POST['do'] ?? '');
$wantsJson = ($do === 'otp_send') || (stripos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false);

if (!mk_csrf_ok()) {
    if ($wantsJson) ob_json(['ok' => false, 'error' => 'Session expired. Please refresh and try again.'], 403);
    flashError('Your session expired. Please refresh the page and try again.');
    ob_redirect('public/provider_onboarding.php');
}

try {
    switch ($do) {
        // ---------- 1. Send SMS OTP (JSON) ----------
        case 'otp_send': {
            $purpose = in_array($_POST['purpose'] ?? 'signup', ['signup', 'login', 'verify'], true)
                ? (string) $_POST['purpose'] : 'signup';
            $phone = ob_phone_normalize($_POST['phone_code'] ?? '+1', $_POST['phone'] ?? '');
            if ($purpose === 'login') {
                $existing = ob_find_user_by_phone($phone);
                if (!$existing) {
                    throw new MkError('No account is registered with that phone number yet.');
                }
            }
            $res = ob_otp_send($phone, $purpose);
            ob_json(['ok' => true] + $res);
        }

        // ---------- 2. Passwordless SMS OTP Login ----------
        case 'otp_login': {
            $phone = ob_phone_normalize($_POST['phone_code'] ?? '+1', $_POST['phone'] ?? '');
            $code  = (string) ($_POST['otp'] ?? '');
            ob_otp_verify($phone, $code, 'login');
            $user = ob_find_user_by_phone($phone);
            if (!$user) {
                throw new MkError('No account was found for that phone number.');
            }
            if (($user['status'] ?? 'active') === 'suspended') {
                throw new MkError('Your account has been suspended. Please contact support.');
            }
            ob_user_update($user['id'], ['phone_verified' => 1]);
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['theme']     = $user['theme_preference'] ?? 'light';
            $_SESSION['user_name'] = $user['name'];
            $next = mk_safe_next($_POST['next'] ?? '');
            if ($next !== '') {
                ob_redirect(strpos($next, 'public/') === 0 ? $next : 'public/' . $next);
            }
            redirectByRole($user['role']);
            exit;
        }

        // ---------- 3. Provider Onboarding Step 1 (Account + Trade + City + Phone OTP) ----------
        case 'provider_step1': {
            $name     = trim((string) ($_POST['name'] ?? ''));
            $email    = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $password = (string) ($_POST['password'] ?? '');
            $city     = mk_clean($_POST['primary_city'] ?? '', 120);
            $areas    = mk_clean($_POST['service_areas'] ?? '', 255);
            $services = array_values(array_intersect(
                array_map('trim', (array) ($_POST['services'] ?? [])),
                array_keys(ob_provider_services())
            ));
            $terms    = !empty($_POST['agree_terms']);

            if (mb_strlen($name) < 2) throw new MkError('Enter your full legal name (it must match your Government ID).');
            if (!$services) throw new MkError('Select at least one service you provide.');
            if (mb_strlen($city) < 2) throw new MkError('Select the primary city you operate in.');
            if (!$email) throw new MkError('Enter a valid email address.');
            if (!$terms) throw new MkError('You must agree to the Terms and Conditions to continue.');

            $pwErrs = [];
            if (strlen($password) < 8)              $pwErrs[] = 'at least 8 characters';
            if (!preg_match('/[A-Z]/', $password))  $pwErrs[] = 'an uppercase letter';
            if (!preg_match('/[a-z]/', $password))  $pwErrs[] = 'a lowercase letter';
            if (!preg_match('/[0-9]/', $password))  $pwErrs[] = 'a number';
            if (!preg_match('/[\W]/', $password))   $pwErrs[] = 'a symbol';
            if ($pwErrs) throw new MkError('Password must include ' . implode(', ', $pwErrs) . '.');

            $phone = ob_phone_normalize($_POST['phone_code'] ?? '+1', $_POST['phone'] ?? '');
            ob_otp_verify($phone, (string) ($_POST['otp'] ?? ''), 'signup');

            // Ensure email is unique
            $chk = mk_db()->prepare('SELECT id FROM `users` WHERE `email` = ? LIMIT 1');
            $chk->execute([$email]);
            if ($chk->fetch()) throw new MkError('That email address is already registered. Try signing in instead.');

            $parts = preg_split('/\s+/', $name, 2);
            $first = $parts[0] ?? $name;
            $last  = $parts[1] ?? null;
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mk_db()->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `firstname`, `lastname`) VALUES (?, ?, ?, 'provider', ?, ?)");
            $stmt->execute([$name, $email, $hashed, $first, $last]);
            $uid = (int) mk_db()->lastInsertId();

            $svcCatalog = ob_provider_services();
            $primaryProfession = $svcCatalog[$services[0]]['name'] ?? 'Service Provider';
            $location = $city . ($areas !== '' ? ' · ' . $areas : '');
            ob_user_update($uid, [
                'phone'               => $phone,
                'phone_verified'      => 1,
                'location'            => $location,
                'profession'          => $primaryProfession,
                'verification_status' => 'pending',
                'provider_status'     => 'pending',
                'contact_info'        => $phone,
                'professional_data'   => json_encode([
                    'services'      => $services,
                    'primary_city'  => $city,
                    'service_areas' => $areas,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);

            // Create contractor_profiles draft synced with live marketplace trades
            if (mk_ready()) {
                mk_profile_ensure($uid);
                $liveTrades = array_values(array_intersect($services, ['snow-removal', 'mobile-mechanics']));
                mk_db()->prepare('UPDATE contractor_profiles SET trades = ?, base_address = ? WHERE user_id = ?')
                    ->execute([implode(',', $liveTrades), $city, $uid]);
                mk_audit('provider.registered', 'user', $uid, implode(',', $services) . ' @ ' . $city, $uid, 'provider');
            }

            // Issue email verification token
            ob_email_send_verification($uid);

            // Log provider straight in so Steps 2 & 3 continue seamlessly
            session_regenerate_id(true);
            $_SESSION['user_id']   = $uid;
            $_SESSION['role']      = 'provider';
            $_SESSION['theme']     = 'light';
            $_SESSION['user_name'] = $name;

            flashSuccess('Account created and phone verified! Next, add your profile photo and links.');
            ob_redirect('public/provider_onboarding.php?step=2');
        }

        // ---------- 4. Provider Onboarding Step 2 (Profile Picture + Bio + Social Links) ----------
        case 'provider_step2': {
            if (!isset($_SESSION['user_id']) || nu_role() !== 'provider') {
                flashError('Please sign in as a provider first.');
                ob_redirect('public/login.php');
            }
            $uid = mk_uid();
            if (!empty($_POST['skip_step'])) {
                ob_redirect('public/provider_onboarding.php?step=3');
            }

            if (!empty($_FILES['avatar']) && (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                ob_avatar_store($uid, 'avatar');
            }

            $social = ob_validate_social_urls($_POST);
            $years  = ($_POST['years_experience'] ?? '') !== '' ? max(0, min(60, (int) $_POST['years_experience'])) : null;
            $bio    = mk_clean($_POST['professional_bio'] ?? '', 1500);
            $head   = mk_clean($_POST['headline'] ?? '', 160);
            $equip  = mk_clean($_POST['equipment'] ?? '', 255);

            ob_user_update($uid, [
                'years_experience' => $years,
                'professional_bio' => $bio,
            ]);
            ob_prof_data_merge($uid, $social + [
                'years_experience' => $years,
                'professional_bio' => $bio,
            ]);

            if (mk_ready()) {
                mk_profile_ensure($uid);
                mk_db()->prepare('UPDATE contractor_profiles SET headline = ?, about = ?, equipment = ? WHERE user_id = ?')
                    ->execute([$head, $bio, $equip, $uid]);
            }

            flashSuccess('Profile photo and links saved. One last optional step: Google Reviews.');
            ob_redirect('public/provider_onboarding.php?step=3');
        }

        // ---------- 5. Provider Onboarding Step 3 (Google Reviews Import) ----------
        case 'provider_step3': {
            if (!isset($_SESSION['user_id']) || nu_role() !== 'provider') {
                flashError('Please sign in as a provider first.');
                ob_redirect('public/login.php');
            }
            $uid = mk_uid();
            if (!empty($_POST['skip_step'])) {
                ob_prof_data_merge($uid, ['google_reviews_status' => 'skipped']);
                flashSuccess('Now upload your verification documents to activate your provider account.');
                ob_redirect('public/contractor_setup.php?tab=identity');
            }
            ob_google_reviews_save(
                $uid,
                $_POST['google_business_name'] ?? '',
                $_POST['google_maps_url'] ?? '',
                $_POST['google_place_id'] ?? ''
            );
            flashSuccess('Google Business submitted for verification (usually within 24 hours). Complete your ID & insurance below.');
            ob_redirect('public/contractor_setup.php?tab=identity');
        }

        // ---------- 6. Verification Hub / Client Profile: Verify Phone OTP ----------
        case 'verify_phone': {
            if (!isset($_SESSION['user_id'])) ob_redirect('public/login.php');
            ob_ensure_users_schema();
            $uid = mk_uid();
            $phone = ob_phone_normalize($_POST['phone_code'] ?? '+1', $_POST['phone'] ?? '');
            ob_otp_verify($phone, (string) ($_POST['otp'] ?? ''), 'verify');
            ob_user_update($uid, ['phone' => $phone, 'phone_verified' => 1, 'contact_info' => $phone]);
            mk_audit('phone.verified', 'user', $uid, $phone, $uid, mk_role());
            flashSuccess('Phone number verified.');
            $back = mk_safe_next($_POST['back'] ?? '');
            if ($back !== '') ob_redirect(strpos($back, 'public/') === 0 ? $back : 'public/' . $back);
            ob_redirect(nu_role() === 'provider' ? 'public/contractor_setup.php?tab=contact' : 'public/profile.php#verification');
        }

        // ---------- 7. Verification Hub / Client Profile: Resend Verification Email ----------
        case 'resend_email': {
            if (!isset($_SESSION['user_id'])) ob_redirect('public/login.php');
            ob_ensure_users_schema();
            $uid = mk_uid();
            $u = ob_user_get($uid);
            if (!$u || ob_is_placeholder_email($u['email'] ?? '')) {
                throw new MkError('Add an email address in Profile Settings first.');
            }
            ob_email_send_verification($uid);
            flashSuccess('Verification email sent. Check your inbox (or use the beta link shown below).');
            $back = mk_safe_next($_POST['back'] ?? '');
            if ($back !== '') ob_redirect(strpos($back, 'public/') === 0 ? $back : 'public/' . $back);
            ob_redirect(nu_role() === 'provider' ? 'public/contractor_setup.php?tab=contact' : 'public/profile.php#verification');
        }

        // ---------- 7b. Client 3-Step Signup (Email + Password OR SMS OTP, Optional Phone Verification) ----------
        case 'client_signup': {
            ob_ensure_users_schema();
            $mode   = ($_POST['signup_mode'] ?? 'email') === 'otp' ? 'otp' : 'email';
            $name   = trim((string) ($_POST['name'] ?? ''));
            $city   = mk_clean($_POST['city'] ?? '', 120);
            $postal = ob_postal_normalize($_POST['postal_code'] ?? '');
            $terms  = !empty($_POST['agree_terms']);
            $next   = mk_safe_next($_POST['next'] ?? '');

            if (mb_strlen($name) < 2) throw new MkError('Enter your full name to create your account.');
            if (!$terms) throw new MkError('You must agree to Nuahn’s Terms & Conditions and Privacy Policy.');

            $phone = '';
            $phoneVerified = 0;
            $email = '';
            $hashed = '';
            $hasRealEmail = false;

            if ($mode === 'otp') {
                $phone = ob_phone_normalize($_POST['phone_code'] ?? '+1', $_POST['phone'] ?? '');
                ob_otp_verify($phone, (string) ($_POST['otp'] ?? ''), 'signup');
                $phoneVerified = 1;

                // Unified OTP UX (matching UrbanTasker): if a client already exists for this phone, sign them right in
                $existing = ob_find_user_by_phone($phone);
                if ($existing) {
                    if (($existing['status'] ?? 'active') === 'suspended') {
                        throw new MkError('Your account has been suspended. Please contact support.');
                    }
                    if (($existing['role'] ?? '') !== 'client') {
                        throw new MkError('That phone number belongs to an existing provider or team account. Sign in with SMS OTP on the Sign in page.');
                    }
                    ob_user_update($existing['id'], ['phone_verified' => 1]);
                    session_regenerate_id(true);
                    $_SESSION['user_id']   = $existing['id'];
                    $_SESSION['role']      = $existing['role'];
                    $_SESSION['theme']     = $existing['theme_preference'] ?? 'light';
                    $_SESSION['user_name'] = $existing['name'];
                    flashSuccess('Welcome back! Signed in with your verified phone.');
                    if ($next !== '') ob_redirect(strpos($next, 'public/') === 0 ? $next : 'public/' . $next);
                    ob_redirect('public/client_home.php');
                }

                $rawEmail = trim((string) ($_POST['email'] ?? ''));
                if ($rawEmail !== '') {
                    $validEmail = filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
                    if (!$validEmail) throw new MkError('Enter a valid email address, or leave it blank for now.');
                    $chk = mk_db()->prepare('SELECT id FROM `users` WHERE `email` = ? LIMIT 1');
                    $chk->execute([$validEmail]);
                    if ($chk->fetch()) throw new MkError('That email address is already registered. Try signing in instead.');
                    $email = $validEmail;
                    $hasRealEmail = true;
                } else {
                    $digits = preg_replace('/\D+/', '', $phone);
                    $email = 'phone_' . $digits . '@phone.nuahn.local';
                }
                $hashed = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            } else {
                $validEmail = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
                if (!$validEmail) throw new MkError('Enter a valid email address.');
                $password = (string) ($_POST['password'] ?? '');
                $confirm  = (string) ($_POST['confirm_password'] ?? '');
                if ($password !== $confirm) throw new MkError('Passwords do not match.');
                $pwErrs = [];
                if (strlen($password) < 8)              $pwErrs[] = 'at least 8 characters';
                if (!preg_match('/[A-Z]/', $password))  $pwErrs[] = 'an uppercase letter';
                if (!preg_match('/[a-z]/', $password))  $pwErrs[] = 'a lowercase letter';
                if (!preg_match('/[0-9]/', $password))  $pwErrs[] = 'a number';
                if (!preg_match('/[\W]/', $password))   $pwErrs[] = 'a symbol';
                if ($pwErrs) throw new MkError('Password must include ' . implode(', ', $pwErrs) . '.');

                $chk = mk_db()->prepare('SELECT id FROM `users` WHERE `email` = ? LIMIT 1');
                $chk->execute([$validEmail]);
                if ($chk->fetch()) throw new MkError('That email address is already registered. Try signing in instead.');

                $email = $validEmail;
                $hasRealEmail = true;
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // Phone is OPTIONAL on email signup; if provided AND OTP entered (and not skipped), verify it now
                $rawPhone = trim((string) ($_POST['phone'] ?? ''));
                $otpCode  = trim((string) ($_POST['otp'] ?? ''));
                $skipPhone = !empty($_POST['skip_phone']);
                if ($rawPhone !== '') {
                    $phone = ob_phone_normalize($_POST['phone_code'] ?? '+1', $rawPhone);
                    if (!$skipPhone && $otpCode !== '') {
                        ob_otp_verify($phone, $otpCode, 'signup');
                        $phoneVerified = 1;
                    }
                }
            }

            $parts = preg_split('/\s+/', $name, 2);
            $first = $parts[0] ?? $name;
            $last  = $parts[1] ?? null;

            $stmt = mk_db()->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `firstname`, `lastname`) VALUES (?, ?, ?, 'client', ?, ?)");
            $stmt->execute([$name, $email, $hashed, $first, $last]);
            $uid = (int) mk_db()->lastInsertId();

            $location = trim($city . ($city !== '' && $postal !== '' ? ' · ' : '') . $postal);
            $meta = [];
            if ($city !== '')   $meta['city'] = $city;
            if ($postal !== '') $meta['postal_code'] = $postal;

            ob_user_update($uid, [
                'phone'             => $phone !== '' ? $phone : null,
                'phone_verified'    => $phoneVerified,
                'location'          => $location !== '' ? $location : null,
                'contact_info'      => $phone !== '' ? $phone : ($hasRealEmail ? $email : null),
                'professional_data' => $meta ? json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            ]);

            if ($hasRealEmail) {
                ob_email_send_verification($uid);
            }
            if (mk_ready()) {
                mk_audit('client.registered', 'user', $uid, $mode . ($location !== '' ? ' @ ' . $location : ''), $uid, 'client');
            }

            // Auto-sign the client in immediately (eliminates forced second login!)
            session_regenerate_id(true);
            $_SESSION['user_id']   = $uid;
            $_SESSION['role']      = 'client';
            $_SESSION['theme']     = 'light';
            $_SESSION['user_name'] = $name;

            if ($next !== '') {
                flashSuccess('Account created! Your saved request is ready to send.');
                ob_redirect(strpos($next, 'public/') === 0 ? $next : 'public/' . $next);
            }
            flashSuccess($phoneVerified ? 'Welcome to Nuahn! Your account is ready and phone is verified.' : 'Welcome to Nuahn! Your account is ready.');
            ob_redirect('public/client_home.php');
        }

        // ---------- 7c. Client Profile: Add or Update Email & Send Verification ----------
        case 'update_client_email': {
            if (!isset($_SESSION['user_id'])) ob_redirect('public/login.php');
            ob_ensure_users_schema();
            $uid = mk_uid();
            $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            if (!$email) throw new MkError('Enter a valid email address.');
            $chk = mk_db()->prepare('SELECT id FROM `users` WHERE `email` = ? AND `id` != ? LIMIT 1');
            $chk->execute([$email, $uid]);
            if ($chk->fetch()) throw new MkError('That email address is already linked to another account.');
            ob_user_update($uid, [
                'email'             => $email,
                'email_verified'    => 0,
                'email_verified_at' => null,
            ]);
            ob_email_send_verification($uid);
            flashSuccess('Email updated! Open the verification link to confirm your address.');
            ob_redirect('public/profile.php#verification');
        }

        // ---------- 8. Verification Hub: Save Licence / Insurance / Certification Metadata ----------
        case 'save_credentials': {
            if (!isset($_SESSION['user_id']) || nu_role() !== 'provider') ob_redirect('public/login.php');
            $uid = mk_uid();
            $tab = in_array($_POST['tab'] ?? 'licence', ['licence', 'insurance'], true) ? (string) $_POST['tab'] : 'licence';
            $fields = [];
            $pdPatch = [];
            if ($tab === 'licence') {
                $licNum = mk_clean($_POST['license_number'] ?? '', 100);
                $auth   = mk_clean($_POST['issuing_authority'] ?? '', 255);
                $cert   = mk_clean($_POST['certification_number'] ?? '', 100);
                $exp    = trim((string) ($_POST['license_expiry'] ?? ''));
                $expVal = preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp) ? $exp : null;
                $fields = [
                    'license_number'       => $licNum,
                    'issuing_authority'    => $auth,
                    'certification_number' => $cert,
                    'license_expiry'       => $expVal,
                ];
                $pdPatch = $fields;
            } else {
                $insNum = mk_clean($_POST['insurance_number'] ?? '', 100);
                $fields = ['insurance_number' => $insNum];
                $pdPatch = $fields;
            }
            ob_user_update($uid, $fields);
            ob_prof_data_merge($uid, $pdPatch);
            flashSuccess(ucfirst($tab) . ' details saved.');
            ob_redirect('public/contractor_setup.php?tab=' . $tab);
        }

        // ---------- 9. Verification Hub: Upload Selfie Photo ----------
        case 'upload_selfie': {
            if (!isset($_SESSION['user_id']) || nu_role() !== 'provider') ob_redirect('public/login.php');
            $uid = mk_uid();
            ob_selfie_store($uid, 'selfie');
            flashSuccess('Selfie uploaded for identity verification.');
            ob_redirect('public/contractor_setup.php?tab=identity');
        }

        // ---------- 10. Verification Hub: Update Avatar & Social Links ----------
        case 'save_social': {
            if (!isset($_SESSION['user_id']) || nu_role() !== 'provider') ob_redirect('public/login.php');
            $uid = mk_uid();
            if (!empty($_FILES['avatar']) && (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                ob_avatar_store($uid, 'avatar');
            }
            $social = ob_validate_social_urls($_POST);
            ob_prof_data_merge($uid, $social);
            flashSuccess('Profile photo and social links updated.');
            ob_redirect('public/contractor_setup.php?tab=contact');
        }

        // ---------- 11. Verification Hub: Link / Resubmit Google Reviews ----------
        case 'save_google_reviews': {
            if (!isset($_SESSION['user_id']) || nu_role() !== 'provider') ob_redirect('public/login.php');
            $uid = mk_uid();
            ob_google_reviews_save(
                $uid,
                $_POST['google_business_name'] ?? '',
                $_POST['google_maps_url'] ?? '',
                $_POST['google_place_id'] ?? ''
            );
            flashSuccess('Google Business submitted for verification (usually within 24 hours).');
            ob_redirect('public/contractor_setup.php?tab=reviews');
        }

        // ---------- 12. Operations Staff: Decide Google Reviews Status ----------
        case 'google_reviews_decide': {
            if (!isset($_SESSION['user_id']) || !mk_is_staff()) {
                http_response_code(403);
                exit('Forbidden');
            }
            $targetUid = (int) ($_POST['user_id'] ?? 0);
            $decision  = in_array($_POST['gr_decision'] ?? '', ['approved', 'rejected', 'in_review'], true)
                ? (string) $_POST['gr_decision'] : 'in_review';
            if ($targetUid > 0) {
                ob_prof_data_merge($targetUid, [
                    'google_reviews_status'     => $decision,
                    'google_reviews_checked_at' => date('Y-m-d H:i:s'),
                ]);
                mk_audit('google_reviews.' . $decision, 'user', $targetUid, 'staff decision');
                flashSuccess('Google Reviews status updated to ' . str_replace('_', ' ', $decision) . '.');
            }
            ob_redirect('public/operations.php?view=vetting');
        }

        // ---------- 13. Operations Staff: Run idempotent database/migrations.sql ----------
        case 'run_migrations': {
            if (!isset($_SESSION['user_id']) || !mk_is_staff()) {
                http_response_code(403);
                exit('Forbidden');
            }
            $r = ob_run_migrations_file();
            flashSuccess('Database migrations applied (' . (int) $r['users_columns_added'] . ' new user columns added, ' . (int) $r['statements_run'] . ' statements verified).');
            ob_redirect('public/operations.php');
        }

        default:
            throw new MkError('Unknown onboarding action.', 404);
    }
} catch (MkError $e) {
    if ($wantsJson) ob_json(['ok' => false, 'error' => $e->getMessage()], 422);
    flashError($e->getMessage());
    $back = mk_safe_next($_POST['back'] ?? '');
    if ($back !== '') ob_redirect(strpos($back, 'public/') === 0 ? $back : 'public/' . $back);
    if ($do === 'otp_login') ob_redirect('public/login.php?mode=otp');
    if ($do === 'client_signup') ob_redirect('public/register.php');
    if (strpos($do, 'provider_step') === 0) ob_redirect('public/provider_onboarding.php?step=' . substr($do, -1));
    ob_redirect(nu_role() === 'provider' ? 'public/contractor_setup.php' : 'public/profile.php#verification');
} catch (Throwable $e) {
    error_log('onboarding ' . $do . ': ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine());
    if ($wantsJson) ob_json(['ok' => false, 'error' => 'Something went wrong. Please try again.'], 500);
    flashError('Something went wrong on our side. Please try again.');
    ob_redirect('public/provider_onboarding.php');
}
