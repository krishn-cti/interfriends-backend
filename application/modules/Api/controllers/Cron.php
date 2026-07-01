<?php
defined('BASEPATH') or exit('No direct script access allowed');
#[\AllowDynamicProperties]
class Cron extends Base_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper(
			array('common', 'user')
		);
		$this->load->library('email');
		$this->load->model("user_model");

        header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
		header('Access-Control-Allow-Credentials: true');

        // if sendMail() is in helper
        // $this->load->helper('common');

        // if sendMail() is in MY_Controller
        // extend MY_Controller instead of CI_Controller
    }

    function sendMail($email, $subject, $message)
	{
		require_once(APPPATH . 'third_party/phpmailer/class.phpmailer.php');
		require_once(APPPATH . 'third_party/phpmailer/class.smtp.php');

		try {
			$mail = new PHPMailer();

			$mail->IsSMTP();
			$mail->CharSet = 'UTF-8';
			$mail->Host = "smtp.hostinger.com";
			$mail->SMTPAuth = true;
			$mail->Port = 465;
			$mail->Username = 'admin@interfriends.uk';
			$mail->Password = 'Mbx9jm!2';
			$mail->SMTPSecure = "ssl";

			$mail->setFrom("admin@interfriends.uk", 'Interfriends');
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body = $message;

			$mail->addAddress($email);

			$send = $mail->send();

			if ($send) {
				$imapServer = '{imap.hostinger.com:993/imap/ssl}INBOX.Sent';
				$imapUser = 'admin@interfriends.uk';
				$imapPass = 'Mbx9jm!2';

				$imapStream = imap_open($imapServer, $imapUser, $imapPass);
				if ($imapStream) {
					$mime  = "Date: " . date('r') . "\r\n";
					$mime .= "From: Interfriends <admin@interfriends.uk>\r\n";
					$mime .= "To: <$email>\r\n";
					$mime .= "Subject: $subject\r\n";
					$mime .= "Message-ID: <" . md5(uniqid(time())) . "@interfriends.uk>\r\n";
					$mime .= "MIME-Version: 1.0\r\n";
					$mime .= "Content-Type: text/html; charset=UTF-8\r\n";
					$mime .= "\r\n";
					$mime .= $message;

					imap_append($imapStream, $imapServer, $mime, "\\Seen");
					imap_close($imapStream);
				}
				return true;
			} else {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}
	}

    public function processEmailQueue()
    {
        // Optional Security Token
        $token = $_REQUEST['token'] ?? '';

        if ($token != 'INTERFRIENDS_SECRET_2026') {
            $this->response(false, "Unauthorized.");
            return;
        }

        $limit = 50;

        // Fetch next pending emails
        $this->db->where('status', 0);
        $this->db->where('attempts <', 3);
        $this->db->order_by('id', 'ASC');
        $this->db->limit($limit);

        $emails = $this->db->get('email_queue')->result_array();

        if (empty($emails)) {
            $this->response(true, "No pending emails.");
            return;
        }

        $success = 0;
        $failed = 0;

        foreach ($emails as $mail) {

            $send = $this->sendMail(
                $mail['email'],
                $mail['subject'],
                $mail['message']
            );

            if ($send) {

                $this->common->updateData(
                    'email_queue',
                    array(
                        'status'     => 1,
                        'sent_at'    => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ),
                    array(
                        'id' => $mail['id']
                    )
                );

                $success++;
            } else {

                $attempts = $mail['attempts'] + 1;

                $update = array(
                    'attempts'  => $attempts,
                    'updated_at' => date('Y-m-d H:i:s')
                );

                if ($attempts >= 3) {
                    $update['status'] = 2;
                }

                $this->common->updateData(
                    'email_queue',
                    $update,
                    array(
                        'id' => $mail['id']
                    )
                );

                $failed++;
            }

            // Optional: 0.2 second delay to avoid SMTP flooding
            usleep(200000);
        }

        $this->response(true, "Queue processed successfully.", array(
            "processed" => count($emails),
            "sent"      => $success,
            "failed"    => $failed
        ));
    }
}
