<?php
/**
 * メール送信処理
 */

// debug用、一旦コメントアウト
// ini_set( 'display_errors', 1 );

require 'PHPMailer/PHPMailerAutoload.php';

/**
 * $subject メールタイトル
 * $body 送信内容
 * $to 送信先EMAILアドレス
 */
function gmail($subject, $body, $to) {
    $from = 'china_souvenir_shop@gmail.com';
    $pass = 'xxx';
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->CharSet = 'utf-8';
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth = true;
    $mail->Username = $from;
    $mail->Password = $pass;
    $mail->setFrom($from);
    $mail->addReplyTo($from);
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $body;
    return $mail->send();
}

// 送信処理実行
if (!gmail("test mail", "this is a test mail!", "xx@gmail.com")) {
    echo "Mailer Error: " . $mail->ErrorInfo;
} else {
    echo "Message sent!";
}
?>