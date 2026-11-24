<?php
// Launch gate: public domain shows only the Coming Soon page until launch (includes/gate.php).
require_once __DIR__ . '/../includes/gate.php';
require_once "../config/init.php";
require_once "../includes/onboarding.php";
ob_ensure_users_schema();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}
if (!mk_csrf_ok()) {
    flashError('Your session expired. Please refresh and try again.');
    header("Location: ../public/profile.php?error=csrf");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$firstname = mk_clean($_POST['firstname'] ?? '', 100);
$lastname = mk_clean($_POST['lastname'] ?? '', 100);
$contact_info = mk_clean($_POST['contact_info'] ?? '', 255);
$theme_preference = ($_POST['theme_preference'] ?? 'light') === 'dark' ? 'dark' : 'light';
$city = mk_clean($_POST['city'] ?? '', 120);

try {
    $postal = ob_postal_normalize($_POST['postal_code'] ?? '');
} catch (MkError $e) {
    flashError($e->getMessage());
    header("Location: ../public/profile.php?error=postal");
    exit;
}

$fields = [
    'firstname'        => $firstname,
    'lastname'         => $lastname,
    'contact_info'     => $contact_info,
    'theme_preference' => $theme_preference,
];
$fullName = trim($firstname . ' ' . $lastname);
if ($fullName !== '') {
    $fields['name'] = $fullName;
    $_SESSION['user_name'] = $fullName;
}
if ($city !== '' || $postal !== '') {
    $fields['location'] = trim($city . ($city !== '' && $postal !== '' ? ' · ' : '') . $postal);
}

// Handle photo upload safely
if (!empty($_FILES['photo']['name']) && (int) ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    try {
        ob_avatar_store($user_id, 'photo');
    } catch (Throwable $e) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);
        $photo = basename((string) $_FILES["photo"]["name"]);
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetDir . $photo)) {
            $fields['photo'] = $photo;
        }
    }
}

ob_user_update($user_id, $fields);
ob_prof_data_merge($user_id, [
    'city'        => $city,
    'postal_code' => $postal,
]);

$_SESSION['theme'] = $theme_preference;
header("Location: ../public/profile.php?success=updated");
exit;
