<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer via Composer is optional: without vendor/ the app still works and just logs.
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

function sendNotification($to, $subject, $body) {
    if (!class_exists(PHPMailer::class)) {
        error_log("Notification not emailed (PHPMailer not installed): $subject -> $to");
        return false;
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.yourserver.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@yourdomain.com';
        $mail->Password   = 'yourpassword';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@yourdomain.com', 'Nuahn Seasonal Hub');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    } catch (Exception $e) {
        error_log("Notification failed: {$mail->ErrorInfo}");
    }
}
?>
