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
            $mail->Password = '@Mbx9jm!2';
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
                $imapPass = '@Mbx9jm!2';

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
        $token = $this->input->get('token', true);

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

    public function sendPendingRecommendationReminders()
    {
        $token = $this->input->get('token');

        if ($token != 'INTERFRIENDS_SECRET_2026') {
            echo "Unauthorized";
            return;
        }

        $pendingApprovals = $this->common->getData(
            'recommendation_approvals',
            array('status' => '0')
        );

        if (empty($pendingApprovals)) {
            echo "No pending approvals.";
            return;
        }

        $sent = 0;

        foreach ($pendingApprovals as $approval) {

            $recommendUser = $this->common->getData(
                'recommend_user',
                array('id' => $approval['recommend_id']),
                array('single')
            );

            if (empty($recommendUser)) {
                continue;
            }

            // Approver Details
            if ($approval['approver_role'] == 'admin') {

                $approver = $this->common->getData(
                    'superAdmin',
                    array('id' => $approval['approver_id']),
                    array('single')
                );

                $approverName = !empty($approver)
                    ? $approver['name']
                    : '';
            } else {

                $approver = $this->common->getData(
                    'user',
                    array('user_id' => $approval['approver_id']),
                    array('single')
                );

                $approverName = !empty($approver)
                    ? trim($approver['first_name'] . ' ' . $approver['last_name'])
                    : '';
            }

            if (empty($approver['email'])) {
                continue;
            }

            $recommender = $this->common->getData(
                'user',
                array('user_id' => $recommendUser['user_id']),
                array('single')
            );

            $secondRecommender = $this->common->getData(
                'user',
                array('user_id' => $recommendUser['refer_user_id']),
                array('single')
            );

            $recommendedName = trim(
                $recommendUser['first_name'] . ' ' . $recommendUser['last_name']
            );

            $recommenderName = !empty($recommender)
                ? trim($recommender['first_name'] . ' ' . $recommender['last_name'])
                : '';

            $secondRecommenderName = !empty($secondRecommender)
                ? trim($secondRecommender['first_name'] . ' ' . $secondRecommender['last_name'])
                : '';

            switch ($recommendUser['employement_type']) {

                case 1:
                    $employmentStatus = "Full Time";
                    break;

                case 2:
                    $employmentStatus = "Part Time";
                    break;

                case 3:
                    $employmentStatus = "Self Employed";
                    break;

                case 4:
                    $employmentStatus = "Others";
                    break;

                default:
                    $employmentStatus = "Not Specified";
            }

            $approveLink = API_BASE_URL . "handleApproval/" . urlencode($approval['token']) . "/1";
            $declineLink = API_BASE_URL . "handleDecline/" . urlencode($approval['token']) . "/2";

            $_mobileCountryCode = !empty($recommendUser['country_code']) ? $recommendUser['country_code'] . ' ' : '';
            $_fullMobile = $_mobileCountryCode . $recommendUser['mobile_number'];
            $memberDetails = "
                <ol>
                    <li><strong>Name of proposed member:</strong> {$recommendUser['first_name']}</li>
                    <li><strong>Telephone number:</strong> {$_fullMobile}</li>
                    <li><strong>Email:</strong> {$recommendUser['email']}</li>
                    <li><strong>Employment status:</strong> {$employmentStatus}</li>
                </ol>";

            if ($approval['approver_role'] == 'second_recommender') {

                $message = "
                <p>This is a friendly reminder.</p>

                <p><strong>{$recommenderName}</strong> has recommended
                <strong>{$recommendedName}</strong> to join Interfriends.</p>

                {$memberDetails}

                <p>Please review this recommendation.</p>";
            } elseif (
                $approval['approver_role'] == 'circle_lead' ||
                $approval['approver_role'] == 'deputy_circle_lead'
            ) {

                $message = "
                <p>This is a friendly reminder.</p>

                <p><strong>{$recommenderName}</strong> and
                <strong>{$secondRecommenderName}</strong> have recommended
                <strong>{$recommendedName}</strong>.</p>

                {$memberDetails}

                <p>Please review this recommendation.</p>";
            } else {

                $message = "
                <p>This is a friendly reminder.</p>

                <p><strong>{$recommenderName}</strong> and
                <strong>{$secondRecommenderName}</strong> have recommended
                <strong>{$recommendedName}</strong>.</p>

                {$memberDetails}

                <p>Please review this recommendation.</p>";
            }

            $message .= "
            <p>
                <a href='{$approveLink}'
                style='background:#1bbe83;color:#fff;padding:10px 20px;text-decoration:none;border-radius:8px;'>
                Approve
                </a>

                &nbsp;

                <a href='{$declineLink}'
                style='background:#ff0000;color:#fff;padding:10px 20px;text-decoration:none;border-radius:8px;'>
                Decline
                </a>
            </p>

            <p><small>This reminder is automatically sent every day until action is taken.</small></p>";

            $data['sendername'] = $approverName;
            $data['useremail'] = $approver['email'];
            $data['message'] = $message;

            $mailBody = $this->load->view(
                'template/common-mail',
                $data,
                true
            );

            if ($this->sendMail(
                $approver['email'],
                'Reminder: Approval Request for Recommendation',
                $mailBody
            )) {

                $sent++;
            }
        }

        echo "{$sent} reminder email(s) sent.";
    }

    // Created by krishn on 31-07-26
    public function sendOutstandingPaymentReminder()
    {
        $token = $this->input->get('token');

        if ($token != 'INTERFRIENDS_SECRET_2026') {
            echo "Unauthorized";
            return;
        }

        $where = "
            UL.status = 4
            AND ULP.status NOT IN (1,2)
            AND ULP.emi_date < DATE_FORMAT(CURDATE(), '%Y-%m-01')
            AND U.status != 2
        ";

        $payments = $this->user_model->getOutstandingLoanPayments($where);

        if (empty($payments)) {
            echo "No outstanding payments found.";
            return;
        }

        $loanTypeMap = array(
            1 => 'Loan',
            2 => 'Help To Pay(Car Insurance)',
            3 => 'Help To Buy(Car)',
            4 => 'Help To Pay(Credit Card)',
            5 => 'Help Me Pay Something Else',
            6 => 'Help To Buy(House)',
            7 => 'Welfare'
        );

        foreach ($payments as $row) {

            $data['sendername'] = $row['first_name'] . ' ' . $row['last_name'];
            $data['useremail']  = $row['email'];

            $loanType = isset($loanTypeMap[$row['loan_type']])
                ? $loanTypeMap[$row['loan_type']]
                : 'Unknown Loan Type';

            $data['message'] = '

                <p>This is a reminder that your '. $loanType .' repayment is still outstanding.</p>

                <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;margin-top:10px;">
                    <tr>
                        <td><strong>Outstanding Amount</strong></td>
                        <td>£' . number_format($row['amount'], 2) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Due Date</strong></td>
                        <td>' . date('d M Y', strtotime($row['emi_date'])) . '</td>
                    </tr>
                </table>

                <br>

                <p>Your payment has not yet been received.</p>

                <p>Please login to your Interfriends account and make the payment as soon as possible.</p>';

            $mailMessage = $this->load->view('template/common-mail', $data, true);

            $this->sendMail(
                $row['email'],
                'Outstanding Payment Reminder',
                $mailMessage
            );
        }

        echo count($payments) . " reminder(s) sent successfully.";
    }
}
