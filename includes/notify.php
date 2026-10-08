<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // assuming PHPMailer installed via Composer

function sendNotification($to, $subject, $body) {
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
