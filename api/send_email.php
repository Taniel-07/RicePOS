

<?php
// I-include ang PHPMailer files. I-adjust ang path kung lahi ang imong setup.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// KINI ANG GIUSAB: Gamiton ang Composer autoloader para husto ang pag-load sa PHPMailer.
require_once __DIR__ . '/../vendor/autoload.php';


function send_notification_email($recipientEmail, $recipientName, $subject, $body) {
    // --- Google SMTP Configuration (Kinahanglan nimo ilisan ni) ---
    $smtp_host = 'smtp.gmail.com';
    $smtp_username = 'nathanielpiraman@gmail.com'; // Imong Gmail Address
    $smtp_password = 'hptf btyv vetj rktl'; // Imong Gmail App Password
    $smtp_port = 587; // O 465 kung gamiton ang SMTPSecure = 'ssl'
    $from_email = 'nathanielpiraman@gmail.com'; 
    $from_name = 'The Equilibrium Syntax';
    // -------------------------------------------------------------

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Gamita ni para sa port 587
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Gamita ni para sa port 465
        $mail->Port       = $smtp_port;
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($recipientEmail, $recipientName); // Add a recipient

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Plain text version for non-HTML mail clients

        $mail->send();
        // Return true if sending is successful, to avoid error log in the calling function
        return true; 
    } catch (Exception $e) {
        // I-log lang ang error, dili i-stop ang tibuok transaction
        error_log("Email could not be sent to {$recipientEmail}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>