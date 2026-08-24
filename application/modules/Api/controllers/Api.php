<?php
defined('BASEPATH') or exit('No direct script access allowed');
date_default_timezone_set("Europe/London");
#[\AllowDynamicProperties]
class Api extends Base_Controller
{

	public function __construct()
	{
		parent::__construct();
		// $this->checkAuth();		
		$this->load->helper(
			array('common', 'user', 'whatsapp')
		);
		$this->load->library('email');
		$this->load->library('whatsapp');
		$this->load->model("user_model");
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
		header('Access-Control-Allow-Credentials: true');
	}


	public function login()
	{
		$_POST['password'] = md5($_POST['password']);
		$email = $this->common->getData('user', array('email' => $_POST['email'], 'password' => $_POST['password']), array('single'));
		if (empty($email)) {
			$this->response(false, 'Invalid email or password');
			die;
		}

		if ($email['status'] == 2) {
			$this->response(false, 'User is blocked! please contact administrative');
			die;
		}


		if ($email['recommended'] == 1) {
			$this->response(false, 'User is not approved! please contact administrative');
			die;
		}

		if ($email['exist_in_waiting'] == 1) {
			$this->response(false, 'User is available in the waiting list! please contact administrative');
			die;
		}


		// $userGroupCycle = $this->common->getData('user_group_lifecycle', array('user_id' => $email['user_id']), array('single'));

		// if (empty($userGroupCycle)) {
		// 	$this->response(false, 'Group id not found');
		// 	die;
		// }

		// added the condtion for checking group circle
		$groupCircle = $this->common->getData('user_circle', array("user_id" => $email['user_id']), array('single'));

		if (empty($groupCircle)) {
			$this->response(false, 'Group id not found');
			die;
		}

		if (!empty($email['profile_image'])) {
			$email['profile_image'] = base_url($email['profile_image']);
		} else {
			$email['profile_image'] = "";
		}


		if (!empty($email['profile_image_thumb'])) {
			$email['profile_image_thumb'] = base_url($email['profile_image_thumb']);
		} else {
			$email['profile_image_thumb'] = "";
		}

		if (!empty($email['id_proof_image'])) {
			$email['id_proof_image'] = base_url($email['id_proof_image']);
		} else {
			$email['id_proof_image'] = "";
		}

		$token = 'dfsdfsdfsdfsdf';
		$this->response(true, 'Successfully Login', array("user_id" => $email["user_id"], "email" => $email["email"], "token" => $token, "name" => $email["first_name"] . " " . $email["last_name"], "name" => $email["first_name"] . " " . $email["last_name"], "profile_image" => $email["profile_image"], "profile_image_thumb" => $email["profile_image_thumb"], "id_proof_image" => $email["id_proof_image"], "group_id" => $groupCircle["group_id"]));
	}


	public function userDetail()
	{
		$user_id = $_REQUEST['user_id'];
		$userinfo = get_user_details($user_id);

		if (!empty($userinfo)) {
			$this->response(true, "Profile Fetch Successfully.", array("userinfo" => $userinfo));
		} else {
			$this->response(false, "There Is Some Problem.Please Try Again.", array("userinfo" => array()));
		}
	}


	public function updateProfile()
	{
		$user_id = $_REQUEST['user_id'];
		unset($_REQUEST['user_id']);

		if (isset($_FILES['image'])) {
			$image = $this->common->do_upload_thumb('image', './assets/userfile/profile/');
			if (isset($image['upload_data'])) {
				$iname = 'assets/userfile/profile/' . $image['upload_data']['file_name'];
				$iname_thumb = 'assets/userfile/profile/thumb/' . $image['upload_data']['file_name'];
				$_REQUEST['profile_image'] = $iname;
				$_REQUEST['profile_image_thumb'] = $iname_thumb;
			}
		}

		$post = $this->common->getField('user', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('user', $post, array('user_id' => $user_id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Profile Updated Successfully");
		} else {
			$this->response(false, "There Is Some Problem.Please Try Again.");
		}
	}


	// function updateCreditScore($points, $calculation_type, $user_id)
	// {
	// 	$result = $this->common->getData('credit_score_user', array('user_id' => $user_id), array('single'));

	// 	if (!empty($result)) {
	// 		$totalScore = 0;
	// 		$newScore = 0;
	// 		if ($calculation_type === 'plus') {
	// 			$totalScore = $result['total_credit_score'] + $points;
	// 		}

	// 		if ($calculation_type === 'minus') {
	// 			$totalScore = $result['total_credit_score'] - $points;
	// 		}

	// 		if ($totalScore > 900) {
	// 			// $newScore = 900; // comment code 02022024
	// 			$newScore = $totalScore;
	// 		} else if ($totalScore < 0) {
	// 			$newScore = 0;
	// 		} else {
	// 			$newScore = $totalScore;
	// 		}


	// 		$this->common->updateData('credit_score_user', array("total_credit_score" => $newScore), array('user_id' => $user_id));
	// 	}
	// }

	function updateCreditScore($points, $calculation_type, $user_id)
	{
		$result = $this->common->getData(
			'credit_score_user',
			array('user_id' => $user_id),
			array('single')
		);

		if (empty($result)) {
			return;
		}

		$totalScore = (int)$result['total_credit_score'];

		if ($calculation_type === 'plus') {
			$totalScore += (int)$points;
		} elseif ($calculation_type === 'minus') {
			$totalScore -= (int)$points;
		}

		// Only prevent negative score
		if ($totalScore < 0) {
			$newScore = 0;
		} else {
			$newScore = $totalScore;
		}

		$this->common->updateData('credit_score_user', array('total_credit_score' => $newScore), array('user_id' => $user_id));
	}



	public function logout()
	{
		if (!empty($_REQUEST['user_id'])) {
			$user_id = $_REQUEST['user_id'];
			$this->common->updateData('user', array("web_token" => ""), array('user_id' => $user_id));

			$this->response(true, "Logout Successfully");
		} else {
			$this->response(false, "Missing Parameter.");
		}
	}

	private function prepareWelfarePlan()
	{
		$this->mergeJsonRequestData();

		$payoutAmount = 0;
		if (isset($_REQUEST['payout_amount'])) {
			$payoutAmount = (float) $_REQUEST['payout_amount'];
		} elseif (isset($_REQUEST['welfare_payout_amount'])) {
			$payoutAmount = (float) $_REQUEST['welfare_payout_amount'];
		} elseif (isset($_REQUEST['loan_amount'])) {
			$payoutAmount = (float) $_REQUEST['loan_amount'];
		}

		$monthlyMap = array(
			1000 => 25,
			2000 => 50,
			3000 => 75
		);

		if (!isset($monthlyMap[(int) $payoutAmount])) {
			$this->response(false, "Invalid welfare payout amount.");
			return false;
		}

		$monthlyPayment = $monthlyMap[(int) $payoutAmount];
		$term = (!empty($_REQUEST['tenure']) && (int) $_REQUEST['tenure'] > 0) ? (int) $_REQUEST['tenure'] : 24;

		$_REQUEST['loan_amount'] = $payoutAmount;
		$_REQUEST['welfare_payout_amount'] = $payoutAmount;
		$_REQUEST['tenure'] = $term;
		$_REQUEST['loan_type'] = '7';
		$_REQUEST['interest_rate'] = 0;
		$_REQUEST['interest_payable'] = 0;
		$_REQUEST['loan_emi'] = $monthlyPayment;
		$_REQUEST['welfare_admin_fee'] = $monthlyPayment;
		$_REQUEST['admin_risk'] = $monthlyPayment;
		$_REQUEST['total_payment'] = ($monthlyPayment * $term) + $monthlyPayment;
		$_REQUEST['welfare_balance'] = $payoutAmount;
		$_REQUEST['provident'] = $this->requestFloat(array('provident', 'provident_amount', 'providentAmount'), 0);

		if (empty($_REQUEST['status'])) {
			$_REQUEST['status'] = 1;
		}

		if (empty($_REQUEST['created_at'])) {
			$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		}

		if (empty($_REQUEST['start_date'])) {
			$_REQUEST['start_date'] = date('Y-m-d');
		}

		if (empty($_REQUEST['end_date'])) {
			$originalDay = (int) date('d', strtotime($_REQUEST['start_date']));
			$endDate = new DateTime($_REQUEST['start_date']);
			$endDate->modify('+' . ($term - 1) . ' month');

			$year = (int) $endDate->format('Y');
			$month = (int) $endDate->format('m');
			$lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
			$day = min($originalDay, $lastDay);

			$_REQUEST['end_date'] = sprintf('%04d-%02d-%02d', $year, $month, $day);
		}

		return true;
	}

	private function mergeJsonRequestData()
	{
		$rawInput = file_get_contents('php://input');
		if (empty($rawInput)) {
			return;
		}

		$jsonData = json_decode($rawInput, true);
		if (!is_array($jsonData)) {
			return;
		}

		foreach ($jsonData as $key => $value) {
			if (!isset($_REQUEST[$key])) {
				$_REQUEST[$key] = $value;
			}
		}
	}

	private function requestFloat($keys, $default = 0)
	{
		foreach ($keys as $key) {
			if (isset($_REQUEST[$key]) && $_REQUEST[$key] !== '') {
				return (float) $_REQUEST[$key];
			}
		}

		return $default;
	}


	public function request_loan()
	{
		// var_dump($_FILES['document_image']);
		// ini_set('display_errors', 1);
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		// emi calculate
		// $amount = $_REQUEST['loan_amount'];
		// //interest 10% => 10/100 = 0.1 => 0.1/12 => 0.0083333333333333
		// $rate = 0.0083333333333333; // Monthly interest rate
		// $term = $_REQUEST['tenure']; // Term in months
		// $loan_emi = $amount * $rate * (pow(1 + $rate, $term) / (pow(1 + $rate, $term) - 1));
		// $total_payment = $loan_emi * $_REQUEST['tenure'];
		// $interest_payable = $total_payment - $amount;
		// emi calculate


		// emi calculate

		$iname = '';
		if (isset($_FILES['document_image'])) {
			$image = $this->common->do_upload_file_document('document_image', './assets/document/');
			if (isset($image['upload_data'])) {
				$iname = 'assets/document/' . $image['upload_data']['file_name'];
				$_REQUEST['document_image'] = $iname;
			}
		}

		$amount = $_REQUEST['loan_amount'];
		$interRate = 10;

		$loanPercentDetail = $this->common->getData('loan_percent', array('id' => $_REQUEST['loan_type']), array('single'));

		if (!empty($loanPercentDetail)) {
			$interRate = $loanPercentDetail['percent'];
		}

		$interest_payable = (($amount * $interRate) / 100);
		$total_payment = $amount + $interest_payable;
		$loan_emi = $total_payment / $_REQUEST['tenure'];


		///	$_REQUEST['loan_emi'] = $loan_emi;
		//$_REQUEST['total_payment'] = $total_payment;
		$_REQUEST['interest_payable'] = $_REQUEST['provident'];
		$_REQUEST['interest_rate'] = $interRate;

		$post = $this->common->getField('user_loan', $_REQUEST);
		$result = $this->common->insertData('user_loan', $post);
		$loan_id = $this->db->insert_id();
		if ($result) {
			$message = "Loan request";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['group_id'], $message, $loan_id, "4");
			$this->send_nofificationAdmin($_REQUEST['user_id'], $_REQUEST['group_id'], $message, $loan_id, "4");

			$userDetail = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));
			$adminUsers = $this->common->getData('superAdmin', array('admin_type' => '2'));

			$loanSubject = "Loan application";
			if (!empty($_REQUEST['loan_type'])) {
				if ($_REQUEST['loan_type'] == '2') {
					$loanSubject = "Help2 Pay(Car Insurance)";
				} elseif ($_REQUEST['loan_type'] == '3') {
					$loanSubject = "Help2 Buy(Car)";
				} elseif ($_REQUEST['loan_type'] == '4') {
					$loanSubject = "Help2 Pay(credit card)";
				} elseif ($_REQUEST['loan_type'] == '5') {
					$loanSubject = "Help2 Pay(other)";
				} elseif ($_REQUEST['loan_type'] != '1') {
					$loanSubject = "Help2 Buy(property)";
				}
			}

			if (!empty($userDetail) && !empty($adminUsers)) {
				$applicantName = trim($userDetail['first_name'] . " " . $userDetail['last_name']);
				foreach ($adminUsers as $admin) {
					if (!empty($admin['email'])) {
						$data['sendername'] = !empty($admin['name']) ? $admin['name'] : "Admin";
						$data['useremail'] = "";
						$data['message'] = '<p>' . $applicantName . ' has submitted a new ' . $loanSubject . ' request.</p>
						<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-top: 10px;">
							<tr>
								<td><strong>Applicant</strong></td>
								<td>' . $applicantName . '</td>
							</tr>
							<tr>
								<td><strong>Email</strong></td>
								<td>' . $userDetail['email'] . '</td>
							</tr>
							<tr>
								<td><strong>Amount</strong></td>
								<td>£' . $_REQUEST['loan_amount'] . '</td>
							</tr>
							<tr>
								<td><strong>Term</strong></td>
								<td>' . $_REQUEST['tenure'] . ' Months</td>
							</tr>
							<tr>
								<td><strong>Type</strong></td>
								<td>' . $loanSubject . '</td>
							</tr>
						</table>';
						$mailMessage = $this->load->view('template/common-mail', $data, true);
						$this->sendMail($admin['email'], $loanSubject, $mailMessage);
					}
				}
			}


			// 			$this->common->query_normal("UPDATE credit_score_user SET each_loan_application = each_loan_application-100 WHERE `user_id` = '". $_REQUEST['user_id'] ."'");

			// 			$this->updateCreditScore(100, 'minus', $_REQUEST['user_id']);

			if (!empty($_REQUEST['gurarantor'])) {
				$this->common->query_normal("UPDATE credit_score_user SET guarantee_a_loan_application = guarantee_a_loan_application+0 WHERE `user_id` = '" . $_REQUEST['gurarantor'] . "'");

				$this->updateCreditScore(0, 'plus', $_REQUEST['gurarantor']);

				$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['gurarantor']), array('single'));

				$userDetailTo = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

				$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
				$data['useremail'] = "";
				$subject = "";
				$newmsg = '';
				if (!empty($_REQUEST['loan_type'])) {

					if ($_REQUEST['loan_type'] == '1') {
						$subject = "Loan application";
					} elseif ($_REQUEST['loan_type'] == '2') {
						$subject = "Help2 Pay(Car Insurance)";
					} elseif ($_REQUEST['loan_type'] == '3') {
						$subject = "Help2 Buy(Car)";
					} elseif ($_REQUEST['loan_type'] == '4') {
						$subject = "Help2 Pay(credit card)";
					} elseif ($_REQUEST['loan_type'] == '5') {
						$subject = "Help2 Pay(other)";
					} else {
						$subject = "Help2 Buy(property)";
					}
				}
				if ($_REQUEST['loan_type'] == '1') {
					$newmsg = '';
				} else {
					$newmsg = $subject;
				}
				$data['message'] = $userDetailTo['first_name'] . " " . $userDetailTo['last_name'] . " has applied for " . $newmsg . " of £" . $_REQUEST['loan_amount'] . " assistance and has named you as a guarantor.<p>Respond to this email and confirm if you are aware of this and are happy to act as a guarantor.</p>
				<div>
				<h4 style='margin-bottom:8px; font-size:15px; font-weight:600'>Responsibilities of a guarantor</h4>
				<ul style='list-style:none;padding-left:0px'>
				<li style='margin-bottom: 5px;'>- You are confirming that the applicant is trustworthy</li>
				<li style='margin-bottom: 5px;'>
				- You are confirming that you know the applicant well
				</li>
				<li style='margin-bottom: 5px;'>
				- You are accepting that you will be accountable for making payments on behalf of the applicant in the unlikely event that the applicant is unable to pay
				</li>
				<li style='margin-bottom: 5px;'>
				- You confirm that you will assist Interfriends to recover any amounts owed in the event that we exhaust all our options for recovering any amount owed
				</li>
				<li style='margin-bottom: 5px;'>- You are agreeing that in the event that we are unable to recover the amount owed, we can use all or part of your provident or any amount you have with interfriends for example; investments, safekeeping to pay off the outstanding amount </li>
			
				</ul>
				</div>
				";
				$messaged = $this->load->view('template/common-mail', $data, true);
				$mail = $this->sendMail($userDetailFrom['email'], $subject, $messaged);
			}

			$this->response(true, "Request for loan sent successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	public function request_welfare()
	{
		$this->mergeJsonRequestData();

		$existingWelfare = $this->common->getData('user_loan', "user_id = '" . (int)$_REQUEST['user_id'] . "' AND loan_type = '7' AND status IN (1, 4, 5)", array('single'));
		if (!empty($existingWelfare)) {
			$this->response(false, "You already have an active or pending welfare account.");
			return;
		}

		if (!$this->prepareWelfarePlan()) {
			return;
		}

		$post = $this->common->getField('user_loan', $_REQUEST);
		$result = $this->common->insertData('user_loan', $post);
		$loan_id = $this->db->insert_id();

		if ($result) {
			$message = "Welfare request";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['group_id'], $message, $loan_id, "11");
			$this->send_nofificationAdmin($_REQUEST['user_id'], $_REQUEST['group_id'], $message, $loan_id, "11");

			// $this->common->query_normal("UPDATE credit_score_user SET each_loan_application = each_loan_application-100 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
			// $this->updateCreditScore(100, 'minus', $_REQUEST['user_id']);

			// Send Email to Applicant & Super Admins
			$user = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));
			if (!empty($user)) {
				// 1. Confirmation email to the applying user
				if (!empty($user['email'])) {
					$applicantName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
					if (empty($applicantName)) {
						$applicantName = 'Member';
					}
					$userMailData['sendername'] = $applicantName;
					$userMailData['useremail']  = $user['email'];
					$userMailData['message']    = "
						<p>Thank you for applying to join the Interfriends Welfare Scheme.</p>
						<p>We are reviewing your application against our eligibility criteria and will notify you once a decision has been made.</p>
						<p>If you did not submit this application, please contact us immediately.</p>
						<br>
						<table border=\"1\" cellpadding=\"8\" cellspacing=\"0\" style=\"border-collapse: collapse; margin-top: 10px; margin-bottom: 10px;\">
							<tr>
								<td colspan=\"2\"><strong>Product Details</strong></td>
							</tr>
							<tr>
								<td>Claim Payout Amount</td>
								<td>£" . number_format($_REQUEST['welfare_payout_amount'], 2) . "</td>
							</tr>
							<tr>
								<td>Term</td>
								<td>" . (!empty($_REQUEST['tenure']) ? $_REQUEST['tenure'] : 24) . " months</td>
							</tr>
							<tr>
								<td>Monthly Payment</td>
								<td>£" . number_format($_REQUEST['loan_emi'], 2) . " (NB: Your monthly payment doubles as soon as a successful claim is made)</td>
							</tr>
						</table>
						<br>
						<p>If you do not make a claim, all your payments will be returned to you after " . (!empty($_REQUEST['tenure']) ? $_REQUEST['tenure'] : 24) . " months or when you leave the scheme.</p>
						<p><strong>Next step:</strong> We have contacted your nominated witness for confirmation. Once we receive their response, we will process your application and make a final decision.</p>
						<br>
						<p><strong>Witness responsibilities:</strong></p>
						<ul>
							<li>Confirm that you are aware of this claim.</li>
							<li>Confirm that the claim is accurate and honest.</li>
							<li>Confirm that the claimant will use the funds only for the stated reason.</li>
							<li>Acknowledge that, if the claim is dishonest, both you and the claimant may lose future Interfriends benefits.</li>
							<li>Acknowledge that supporting a dishonest claim gives Interfriends the right to cancel any active benefits you hold.</li>
							<li>Understand that making or supporting a dishonest claim may seriously affect your Trust Score.</li>
						</ul>
					";
					$userMailMessage = $this->load->view('template/common-mail', $userMailData, true);
					$this->sendMail($user['email'], 'Interfriends Welfare Scheme Application', $userMailMessage);
				}

				// 2. Email to Super Admins
				$admins = $this->common->getData('superAdmin', array('admin_type' => '2'));
				if (!empty($admins)) {
					foreach ($admins as $admin) {
						$mailData['sendername'] = $admin['name'];
						$mailData['useremail'] = $admin['email'];
						$mailData['message'] = "
							<p>A new Welfare application requires your approval.</p>
							<p><strong>User:</strong> {$user['first_name']} {$user['last_name']}</p>
							<p><strong>Email:</strong> {$user['email']}</p>
							<p><strong>Payout Amount:</strong> £" . number_format($_REQUEST['welfare_payout_amount'], 2) . "</p>
							<p><strong>Term:</strong> {$_REQUEST['tenure']} Months</p>
							<p><strong>Monthly Contribution:</strong> £" . number_format($_REQUEST['loan_emi'], 2) . "</p>
						";
						$adminMailMessage = $this->load->view('template/common-mail', $mailData, true);
						$this->sendMail($admin['email'], 'New Welfare Application Submitted', $adminMailMessage);
					}
				}
			}

			$this->response(true, "Welfare account created successfully", array("welfare_id" => $loan_id));
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	public function submitWelfareClaim()
	{
		$this->mergeJsonRequestData();

		if (empty($_REQUEST['user_id']) || empty($_REQUEST['welfare_loan_id']) || empty($_REQUEST['payout_amount'])) {
			$this->response(false, "Missing required parameters.");
			return;
		}

		$userId = $_REQUEST['user_id'];
		$welfareLoanId = $_REQUEST['welfare_loan_id'];
		$payoutAmount = (float) $_REQUEST['payout_amount'];
		$claimReason = !empty($_REQUEST['claim_reason']) ? trim($_REQUEST['claim_reason']) : '';
		$claimReasonOther = !empty($_REQUEST['claim_reason_other']) ? trim($_REQUEST['claim_reason_other']) : '';
		$beneficiary = !empty($_REQUEST['beneficiary']) ? trim($_REQUEST['beneficiary']) : '';
		$beneficiaryOther = !empty($_REQUEST['beneficiary_other']) ? trim($_REQUEST['beneficiary_other']) : '';
		$seconder1UserId = !empty($_REQUEST['seconder1_user_id']) ? $_REQUEST['seconder1_user_id'] : (!empty($_REQUEST['seconder1_id']) ? $_REQUEST['seconder1_id'] : '');
		$seconder2UserId = !empty($_REQUEST['seconder2_user_id']) ? $_REQUEST['seconder2_user_id'] : (!empty($_REQUEST['seconder2_id']) ? $_REQUEST['seconder2_id'] : '');

		// Validate Claim Reason
		$validReasons = array('Bereavement', 'Medical Bills', 'Other');
		if (!in_array($claimReason, $validReasons)) {
			$this->response(false, "Invalid claim reason. Must be Bereavement or Medical Bills or Other.");
			return;
		}

		if ($claimReason === 'Other' && empty($claimReasonOther)) {
			$this->response(false, "Please specify the custom claim reason.");
			return;
		}

		// Validate Beneficiary
		$validBeneficiaries = array('Mother', 'Father', 'Sibling', 'Child', 'Close Relation', 'Other');
		if (!in_array($beneficiary, $validBeneficiaries)) {
			$this->response(false, "Invalid beneficiary. Must be Mother, Father, Sibling, Child, Close Relation or Other.");
			return;
		}

		if ($beneficiary === 'Other' && empty($beneficiaryOther)) {
			$this->response(false, "Please specify the custom beneficiary.");
			return;
		}

		// Validate Seconders
		if (empty($seconder1UserId) || empty($seconder2UserId)) {
			$this->response(false, "Two seconders (seconder1_user_id and seconder2_user_id) are required.");
			return;
		}

		if ($seconder1UserId == $userId || $seconder2UserId == $userId || $seconder1UserId == $seconder2UserId) {
			$this->response(false, "Seconders must be two distinct users and cannot be the applicant.");
			return;
		}

		// Check Welfare Loan Account
		$welfareAccount = $this->common->getData(
			'user_loan',
			array('id' => $welfareLoanId, 'user_id' => $userId, 'loan_type' => 7),
			array('single')
		);

		if (empty($welfareAccount) || !is_array($welfareAccount)) {
			$this->response(false, "Welfare account not found.");
			return;
		}

		if (empty($welfareAccount['status']) || $welfareAccount['status'] != 4) {
			$this->response(false, "Welfare account is not active or approved.");
			return;
		}

		// Check for existing pending claim
		$existingPendingClaim = $this->common->getData(
			'welfare_claim',
			array('user_id' => $userId, 'welfare_loan_id' => $welfareLoanId, 'status' => 0),
			array('single')
		);

		if (!empty($existingPendingClaim) && is_array($existingPendingClaim)) {
			$this->response(false, "You already have a pending welfare claim awaiting admin approval.");
			return;
		}

		$currentBalance = (is_array($welfareAccount) && isset($welfareAccount['welfare_balance'])) ? (float) $welfareAccount['welfare_balance'] : 0;
		if ($payoutAmount <= 0 || $payoutAmount > $currentBalance) {
			$this->response(false, "Claim payout amount cannot exceed available welfare balance (£" . number_format($currentBalance, 2) . ").");
			return;
		}

		// Check Net Welfare Balance (avgComplete)
		$paidAmountData = $this->common->getData('user_loan', "user_id = '" . (int)$userId . "' AND loan_type = '7' AND status IN (2, 4)", array('field' => 'IFNULL(SUM(total_payment), 0) as total_paid', 'single'));
		$totalPaidAmount = (is_array($paidAmountData) && isset($paidAmountData['total_paid'])) ? (float) $paidAmountData['total_paid'] : 0;

		$claimWhere = array('user_id' => (int)$userId, 'status' => 1);
		if (!empty($welfareAccount['group_id'])) {
			$claimWhere['group_id'] = (int)$welfareAccount['group_id'];
		}
		$approvedClaimsData = $this->common->getData('welfare_claim', $claimWhere, array('field' => 'IFNULL(SUM(payout_amount), 0) as total_claimed', 'single'));
		$totalApprovedClaims = (is_array($approvedClaimsData) && isset($approvedClaimsData['total_claimed'])) ? (float) $approvedClaimsData['total_claimed'] : 0;

		$netWelfareBalance = $totalPaidAmount - $totalApprovedClaims;
		if ($totalApprovedClaims > 0 && $netWelfareBalance <= 0) {
			$this->response(false, "You cannot submit a claim because your net welfare balance is not greater than 0.");
			return;
		}

		// Prepopulate User Info
		$userInfo = $this->common->getData('user', array('user_id' => $userId), array('single'));
		$userName = !empty($_REQUEST['name']) ? $_REQUEST['name'] : ((is_array($userInfo) && !empty($userInfo['first_name'])) ? trim($userInfo['first_name'] . ' ' . ($userInfo['last_name'] ?? '')) : '');
		$userEmail = !empty($_REQUEST['email']) ? $_REQUEST['email'] : ((is_array($userInfo) && !empty($userInfo['email'])) ? $userInfo['email'] : '');
		$groupId = !empty($_REQUEST['group_id']) ? $_REQUEST['group_id'] : ((is_array($welfareAccount) && !empty($welfareAccount['group_id'])) ? $welfareAccount['group_id'] : 0);

		$claimData = array(
			'welfare_loan_id'    => $welfareLoanId,
			'user_id'            => $userId,
			'group_id'           => $groupId,
			'name'               => $userName,
			'email'              => $userEmail,
			'claim_reason'       => $claimReason,
			'claim_reason_other' => ($claimReason === 'Other') ? $claimReasonOther : null,
			'beneficiary'        => $beneficiary,
			'beneficiary_other'  => ($beneficiary === 'Other') ? $beneficiaryOther : null,
			'seconder1_user_id'  => $seconder1UserId,
			'seconder2_user_id'  => $seconder2UserId,
			'payout_amount'      => $payoutAmount,
			'status'             => 0, // Pending
			'created_at'         => date('Y-m-d H:i:s')
		);

		$claimPost = $this->common->getField('welfare_claim', $claimData);
		$result = $this->common->insertData('welfare_claim', $claimPost);
		if (!$result) {
			$this->response(false, "Unable to submit welfare claim. Please try again.");
			return;
		}

		$claimId = $this->db->insert_id();

		// Insert Status History
		$this->common->insertData('welfare_claim_status_history', array(
			'claim_id'         => $claimId,
			'user_id'          => $userId,
			'note_title'       => 'Welfare Claim Submitted',
			'note_description' => 'Claim submitted for ' . $claimReason . ' (£' . number_format($payoutAmount, 2) . ')',
			'status'           => 0,
			'created_at'       => date('Y-m-d H:i:s')
		));

		// Notifications
		$message = "Welfare claim request";
		$this->send_nofification($userId, $groupId, $message, $claimId, "12");
		$this->send_nofificationAdmin($userId, $groupId, $message, $claimId, "12");

		// Send Email to Super Admin
		$admins = $this->common->getData('superAdmin', array('admin_type' => '2'));
		if (!empty($admins) && is_array($admins)) {
			foreach ($admins as $admin) {
				if (!empty($admin['email'])) {
					$mailData = array();
					$mailData['sendername'] = $admin['name'] ?? 'Admin';
					$mailData['useremail']  = $admin['email'];
					$mailData['message']    = "
						<p>A new Welfare Claim has been submitted and is pending review.</p>
						<p><strong>User:</strong> {$userName} ({$userEmail})</p>
						<p><strong>Reason:</strong> {$claimReason}</p>
						<p><strong>Beneficiary:</strong> {$beneficiary}</p>
						<p><strong>Payout Amount:</strong> £" . number_format($payoutAmount, 2) . "</p>
					";
					$mailMessage = $this->load->view('template/common-mail', $mailData, true);
					$this->sendMail($admin['email'], 'New Welfare Claim Submitted', $mailMessage);
				}
			}
		}

		// Send Email to Witness 1 and Witness 2 (Seconders) if available
		$witnessUserIds = array_filter(array($seconder1UserId, $seconder2UserId));
		if (!empty($witnessUserIds)) {
			foreach ($witnessUserIds as $wUserId) {
				$witness = $this->common->getData('user', array('user_id' => $wUserId), array('single'));
				if (!empty($witness) && is_array($witness) && !empty($witness['email'])) {
					$witnessName = trim(($witness['first_name'] ?? '') . ' ' . ($witness['last_name'] ?? ''));
					if (empty($witnessName)) {
						$witnessName = 'Member';
					}
					$witnessMailData = array();
					$witnessMailData['sendername'] = $witnessName;
					$witnessMailData['useremail']  = $witness['email'];
					$witnessMailData['message']    = "
						<p>Member <strong>{$userName}</strong> has submitted an Interfriends Welfare claim.</p>
						<p>The claimant has nominated you to provide a witness statement confirming that the claim is accurate and honest.</p>
						<br>
						<p><strong>See the details</strong></p>
						<table border=\"1\" cellpadding=\"8\" cellspacing=\"0\" style=\"border-collapse: collapse; margin-top: 10px; margin-bottom: 10px;\">
							<tr>
								<td><strong>Reason for claim</strong></td>
								<td>" . htmlspecialchars($claimReason) . "</td>
							</tr>
							<tr>
								<td><strong>Relationship to the claimant</strong></td>
								<td>" . htmlspecialchars($beneficiary) . "</td>
							</tr>
						</table>
						<br>
						<p><strong>Witness responsibilities:</strong></p>
						<ul>
							<li>Confirm that you are aware of this claim.</li>
							<li>Confirm that the claim is accurate and honest.</li>
							<li>Confirm that the claimant will use the funds only for the stated reason.</li>
							<li>Acknowledge that, if the claim is dishonest, both you and the claimant may lose future Interfriends benefits.</li>
							<li>Acknowledge that supporting a dishonest claim gives Interfriends the right to cancel any active benefits you hold.</li>
							<li>Understand that making or supporting a dishonest claim may seriously affect your Trust Score.</li>
						</ul>
					";
					$witnessMailMessage = $this->load->view('template/common-mail', $witnessMailData, true);
					$this->sendMail($witness['email'], 'Interfriends Welfare Claim Witness Request', $witnessMailMessage);
				}
			}
		}

		$this->response(true, "Welfare claim submitted successfully.", array("claim_id" => $claimId));
	}

	public function getUserWelfareClaims()
	{
		$this->mergeJsonRequestData();

		if (empty($_REQUEST['user_id'])) {
			$this->response(false, "User ID is required.");
			return;
		}

		$userId = $_REQUEST['user_id'];
		$where = "WC.user_id = '" . (int)$userId . "'";

		if (isset($_REQUEST['status']) && $_REQUEST['status'] !== '') {
			$where .= " AND WC.status = '" . (int)$_REQUEST['status'] . "'";
		}

		if (!empty($_REQUEST['welfare_loan_id'])) {
			$where .= " AND WC.welfare_loan_id = '" . (int)$_REQUEST['welfare_loan_id'] . "'";
		}

		$this->db->select("
			WC.*,
			CONCAT(IFNULL(S1.first_name,''), ' ', IFNULL(S1.last_name,'')) AS seconder1_name, S1.email AS seconder1_email,
			CONCAT(IFNULL(S2.first_name,''), ' ', IFNULL(S2.last_name,'')) AS seconder2_name, S2.email AS seconder2_email,
			UL.welfare_payout_amount, UL.welfare_balance, UL.loan_emi
		");
		$this->db->from("welfare_claim WC");
		$this->db->join("user S1", "S1.user_id = WC.seconder1_user_id", "left");
		$this->db->join("user S2", "S2.user_id = WC.seconder2_user_id", "left");
		$this->db->join("user_loan UL", "UL.id = WC.welfare_loan_id", "left");
		$this->db->where($where, NULL, FALSE);
		$this->db->order_by("WC.id", "DESC");
		$claims = $this->db->get()->result_array();

		$countData = 1;
		if (!empty($claims)) {
			foreach ($claims as $key => $claim) {
				$claims[$key]['sno'] = $countData++;
				$history = $this->common->getData('welfare_claim_status_history', array('claim_id' => $claim['id']), array('sort_by' => 'id', 'sort_direction' => 'asc'));
				$claims[$key]['history'] = !empty($history) ? $history : array();
			}
		}

		$this->response(true, "Welfare claims fetched successfully.", array("claims" => $claims, "totalCount" => count($claims)));
	}


	public function resetForgetPassword()
	{
		$_POST['password'] = md5($_REQUEST['password']);
		$update = $this->common->updateData11('user', array('token' => "", 'password' => $_POST['password']), array('token' => $_REQUEST['token']));
		if ($update) {
			$this->response(true, 'Password Changed Successfully');
		} else {
			$this->response(false, 'Link expired. Please Reset Password Again');
		}
	}

	public function verifyUser()
	{
		$_POST['password'] = md5($_REQUEST['password']);
		$update = $this->common->updateData11('user', array('verify_token' => "", 'password' => $_POST['password']), array('verify_token' => $_REQUEST['token']));
		if ($update) {
			$this->response(true, 'Password Changed Successfully');
		} else {
			$this->response(false, 'Link expired. Please Reset Password Again');
		}
	}


	public function resetPassword()
	{
		if ($_POST['password'] == $_POST['confpassword']) {
			$a = array('password' => md5($_POST['password']));
			$result = $this->common->updateData('user', $a, array('user_id' => $_REQUEST['user_id']));
			if ($result) {
				$this->response(true, 'Profile update successfully');
			} else {
				$this->response(false, 'some error occured. Please try again.');
			}
		} else {
			$this->response(false, 'Password not match.');
		}
	}

	public function editLoan()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);

		if ($_REQUEST['status'] === '4') {
			$loanInfo = $this->common->getData('user_loan', array('id' => $id), array('single'));
			if (!empty($loanInfo)) {
				$current_date = date('Y-m-d');
				$end_date = strtotime("+" . $loanInfo['tenure'] . " month", strtotime($current_date));
				$_REQUEST['end_date'] = date("Y-m-d", $end_date);
				$_REQUEST['start_date'] = $current_date;
			}
		}

		$post = $this->common->getField('user_loan', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('user_loan', $post, array('id' => $id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Loan Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	// public function editUser()
	// {
	// 	$user_id = $_REQUEST['user_id'];
	// 	unset($_REQUEST['user_id']);

	// 	$whereEmail = "email = '" . $_POST['email'] . "' AND user_id != '" . $user_id . "'";
	// 	$emailExist = $this->common->getData('user', $whereEmail, array('single', 'field' => 'email'));

	// 	if ($emailExist) {
	// 		$this->response(false, 'Email already exists');
	// 		die;
	// 	}



	// 	if (isset($_FILES['image'])) {
	// 		$image = $this->common->do_upload_thumb('image', './assets/userfile/profile/');
	// 		if (isset($image['upload_data'])) {
	// 			$iname = 'assets/userfile/profile/' . $image['upload_data']['file_name'];
	// 			$iname_thumb = 'assets/userfile/profile/thumb/' . $image['upload_data']['file_name'];
	// 			$_REQUEST['profile_image'] = $iname;
	// 			$_REQUEST['profile_image_thumb'] = $iname_thumb;
	// 		}
	// 	}

	// 	$post = $this->common->getField('user', $_REQUEST);

	// 	if (!empty($post)) {
	// 		$result = $this->common->updateData('user', $post, array('user_id' => $user_id));
	// 	} else {
	// 		$result = "";
	// 	}

	// 	if ($result) {
	// 		$this->response(true, "User Update Successfully");
	// 	} else {
	// 		$this->response(false, "There is a problem, please try again.");
	// 	}
	// }

	public function editUser()
	{
		$user_id = $_REQUEST['user_id'];
		unset($_REQUEST['user_id']);

		// Check email only if email is provided
		if (!empty($_POST['email'])) {

			$whereEmail = "email = '" . $_POST['email'] . "' AND user_id != '" . $user_id . "'";

			$emailExist = $this->common->getData(
				'user',
				$whereEmail,
				array(
					'single',
					'field' => 'email'
				)
			);

			if ($emailExist) {
				$this->response(false, 'Email already exists');
				return;
			}
		}

		/*
     * Profile Image Upload
     */
		if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {

			$image = $this->common->do_upload_thumb(
				'image',
				'./assets/userfile/profile/'
			);

			if (!empty($image['upload_data'])) {

				$fileName = $image['upload_data']['file_name'];

				$_REQUEST['profile_image'] =
					'assets/userfile/profile/' . $fileName;

				$_REQUEST['profile_image_thumb'] =
					'assets/userfile/profile/thumb/' . $fileName;
			} else {

				// Log actual upload/processing error
				log_message(
					'error',
					'editUser profile image upload failed for user_id ' .
						$user_id . ': ' .
						print_r($image, true)
				);

				$errorMessage = !empty($image['error'])
					? $image['error']
					: 'Unable to process profile image.';

				$this->response(false, $errorMessage);
				return;
			}
		}

		/*
     * Prepare user update
     */
		$post = $this->common->getField(
			'user',
			$_REQUEST
		);

		if (!empty($post)) {

			$result = $this->common->updateData(
				'user',
				$post,
				array(
					'user_id' => $user_id
				)
			);
		} else {

			$result = false;
		}

		/*
     * Response
     */
		if ($result) {

			$this->response(
				true,
				"User Update Successfully"
			);
		} else {

			$this->response(
				false,
				"There is a problem, please try again."
			);
		}
	}

	public function addLoanPayment()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$_REQUEST['interest_rate'] = '10%';

		$post = $this->common->getField('user_loan_payment', $_REQUEST);
		$result = $this->common->insertData('user_loan_payment', $post);

		if ($result) {
			$this->response(true, "Add loan payment Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function requestSafeKeepingWithdral()
	{
		// ini_set('display_errors', 1);
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$avgAmountSafeKeeping = 0;

		//new-changes 17-06-2024
		if ($_REQUEST['amount'] < '0') {
			$this->response(false, "Please enter amount greater then 0 ");
			die();
		}
		if ($_REQUEST['type'] != "Safe Keeping") {

			$grouplifecycle = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id'], "group_type_id" => '1'), array('sort_by' => 'id', 'sort_direction' => 'desc'));
			$_REQUEST['group_cycle_id'] = $grouplifecycle[0]['id'];

			$cycleTransfer = $this->common->getData('cycle_status_management', array('user_id' => $_REQUEST['user_id'], 'group_id' => $_REQUEST['group_id'], 'group_cycle_id' => $_REQUEST['group_cycle_id']), array('single'));
			if (empty($cycleTransfer)) {
				$where = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
				$result = $this->common->getData('user_group_lifecycle', $where, array("field" => 'sum(amount) as total_payment', "single"));

				if (!empty($result['total_payment'])) {
					$avgAmount = $result['total_payment'];
				} else {
					$avgAmount = 0.00;
				}
			} else {
				$paidWhere = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
				$paidResult = $this->common->getData('user_group_lifecycle', $paidWhere, array("field" => 'sum(amount) as total_payment', "single"));

				if (!empty($paidResult['total_payment'])) {
					$paidAvgAmount = $paidResult['total_payment'];
				} else {
					$paidAvgAmount = 0;
				}


				$result = $this->common->getData('user_group_lifecycle', array('groupLifecycle_id' => $_REQUEST['group_cycle_id'], 'user_id' => $_REQUEST['user_id']), array('field' => 'SUM(amount) as total_amount', 'single'));

				$payout_amount_total = $result['total_amount'];

				//print_r($payout_amount_total);
				$avgAmountSafeKeeping = $paidAvgAmount - $payout_amount_total;
			}
		} else {
			$safeKeepingAmount = $this->common->getData('safe_keeping', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id']), array("field" => 'sum(amount) as safe_keeping_total_amount', "single"));


			if (!empty($safeKeepingAmount['safe_keeping_total_amount'])) {
				$avgAmountSafeKeeping = $safeKeepingAmount['safe_keeping_total_amount'];
			} else {
				$avgAmountSafeKeeping = 0;
			}
		}


		if ($_REQUEST['type'] != "Safe Keeping") {
			$_REQUEST['request_type']  = 1;
		} else {

			if ($_REQUEST['amount'] > $avgAmountSafeKeeping) {
				$this->response(false, "Please enter amount less then " . $avgAmountSafeKeeping);
				die();
			}
			$_REQUEST['request_type']  = 0;
		}


		$post = $this->common->getField('safe_keeping_withdral_request', $_REQUEST);
		$result = $this->common->insertData('safe_keeping_withdral_request', $post);


		if ($result) {
			$id = $this->db->insert_id();
			$message = "Safekeeping withdrawal request";
			$this->send_nofificationAdmin($_REQUEST['user_id'], $_REQUEST['group_id'], $message, $id, "7");

			$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));
			$userDetailTo = $this->common->getData('superAdmin', array('admin_type' => '2'));


			$allemail = array();
			if (!empty($userDetailTo)) {
				foreach ($userDetailTo as $key => $value) {
					if (!empty($value['email'])) {
						$username = trim($userDetailFrom['first_name'] . " " . $userDetailFrom['last_name']);
						$value['sendername'] = !empty($value['name']) ? $value['name'] : "Admin";
						$value['useremail'] = $userDetailFrom['email'];
						$value['message'] = $username . ' has requested a safekeeping withdrawal.';
						$message = $this->load->view('template/common-mail', $value, true);
						$mail = $this->sendMail($value['email'], 'Safekeeping Withdrawal', $message);
					}
				}
			}

			$this->response(true, "Request Submitted");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function requestInvestment()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$post = $this->common->getField('investment_request', $_REQUEST);
		$result = $this->common->insertData('investment_request', $post);

		if ($result) {
			$id = $this->db->insert_id();
			$message = "Investment request";
			$this->send_nofificationAdmin($_REQUEST['user_id'], $_REQUEST['group_id'], $message, $id, "9");
			$this->response(true, "Request Submitted");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	// API created by @krishn on 18/04/25
	public function checkRecommendedUser()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		// Check if the email already exists in the 'user' table
		$email = $this->common->getData('user', array('email' => $_REQUEST['email']), array('single'));
		if (!empty($email)) {
			$this->response(false, 'email_exists');
			return;
		}

		// Check if the email and name exist in the 'recommend_user' table
		$recommendResult = $this->common->getData('recommend_user', [
			'email' => $_POST['email']
		], [
			'single'
		]);

		if (empty($recommendResult)) {
			$this->response(false, 'not_found');
		} else {
			if ($recommendResult['admin_status'] == 1) {
				$subject = "Complete the Registration Process";

				$data1["sendername"] = $_POST['name'];
				$data1["useremail"] = "";
				// Generate secure token and expiry time
				$token = bin2hex(random_bytes(16));
				$expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

				// Save token in database
				$this->common->insertData('registration_tokens', [
					'email' => $_REQUEST['email'],
					'token' => $token,
					'expires_at' => $expires_at,
					'created_at' => date('Y-m-d H:i:s')
				]);

				// Build registration link with token
				$data1["link"] = USER_BASE_URL . 'register?token=' . $token;

				$data1["message"] = '
					<p>
						Your recommendation has been approved by the admin. To complete your registration, please click the button below:
					</p>
					<a href="' . $data1["link"] . '" 
					style="display: inline-block; padding: 10px 20px; font-size: 16px; 
							color: #ffffff; background-color: #28a745; text-decoration: none; 
							border-radius: 5px; margin-top: 10px;">
					Complete Registration
					</a>
				';

				$messaged1 = $this->load->view('template/common-mail', $data1, true);
				$this->sendMail($_REQUEST['email'], $subject, $messaged1);

				$this->response(true, 'approved');
			} else {
				$this->response(false, 'pending_or_reject');
			}
		}
	}

	// API created by @krishn on 18/04/25
	public function verifyToken()
	{
		$token = $_GET['token'] ?? null;

		if (!$token) {
			$this->response(false, 'Token is required');
			return;
		}

		// Fetch token record
		$tokenData = $this->common->getData('registration_tokens', ['token' => $token], ['single']);

		if (!$tokenData) {
			$this->response(false, 'Invalid token');
			return;
		}

		// Check if token is expired
		if (strtotime($tokenData['expires_at']) < time()) {
			$this->response(false, 'Token has expired');
			return;
		}

		// Token is valid
		$this->response(true, 'Token is valid', ['email' => $tokenData['email']]);
	}

	// API created by @krishn on 21/04/25
	// public function saveContactUsDetails()
	// {
	// 	$contactData = $this->common->getField('contact_us', $_REQUEST);

	// 	$inserted = $this->common->insertData('contact_us', $contactData);

	// 	if ($inserted) {
	// 		echo json_encode(['status' => true, 'message' => 'Contact message submitted successfully.']);
	// 	} else {
	// 		echo json_encode(['status' => false, 'message' => 'Something went wrong.']);
	// 	}
	// }

	// API created by @krishn on 30/04/25
	public function saveContactUsDetails()
	{
		$contactData = $this->common->getField('contact_us', $_REQUEST);

		$inserted = $this->common->insertData('contact_us', $contactData);

		if ($inserted) {
			$fullName = trim(($contactData['first_name'] ?? '') . ' ' . ($contactData['last_name'] ?? ''));

			$adminUsers = $this->common->getData('superAdmin', ['admin_type' => '2']);

			if (!empty($adminUsers)) {
				foreach ($adminUsers as $admin) {
					if (!empty($admin['email'])) {
						$subject = "New Contact Us Form Submission";


						$data['sendername'] = $admin['name'] ?? 'Admin';
						$data['message'] = '
							<p>We have received a new inquiry via the <strong>Contact Us</strong> form. Below are the details submitted by the user:</p>

							<hr>
							<p><strong>Name:</strong> ' . htmlspecialchars($fullName ?: 'Not Provided') . '</p>
							<p><strong>Email:</strong> ' . htmlspecialchars($contactData['email'] ?? 'Not Provided') . '</p>
							<p><strong>Phone:</strong> ' . htmlspecialchars($contactData['phone_number'] ?? 'Not Provided') . '</p>
							<p><strong>Message:</strong><br>' . nl2br(htmlspecialchars($contactData['message'] ?? '')) . '</p>
							<hr>

							<p>Please follow up accordingly if needed.</p>
						';

						$emailBody = $this->load->view('template/common-mail', $data, true);
						$this->sendMail($admin['email'], $subject, $emailBody);
					}
				}
			}

			echo json_encode(['status' => true, 'message' => 'Contact message submitted successfully.']);
		} else {
			echo json_encode(['status' => false, 'message' => 'Something went wrong.']);
		}
	}



	// API created by @krishn on 21/04/25
	// public function saveInterestedUser()
	// {
	// 	$email = $this->common->getData('interested_user', array('email' => $_REQUEST['email']), array('single'));
	// 	if (!empty($email)) {
	// 		$this->response(false, "Email already exists");
	// 		die();
	// 	}

	// 	$data = $this->common->getField('interested_user', $_REQUEST);

	// 	$inserted = $this->common->insertData('interested_user', $data);

	// 	if ($inserted) {
	// 		echo json_encode(['status' => true, 'message' => 'Data submitted successfully.']);
	// 	} else {
	// 		echo json_encode(['status' => false, 'message' => 'Something went wrong.']);
	// 	}
	// }

	public function saveInterestedUser()
	{
		$email = $this->common->getData(
			'interested_user',
			array('email' => $_REQUEST['email']),
			array('single')
		);

		if (!empty($email)) {
			$this->response(false, "Email already exists");
			die();
		}

		$data = $this->common->getField('interested_user', $_REQUEST);

		$inserted = $this->common->insertData('interested_user', $data);

		if ($inserted) {

			$insert_id = $this->db->insert_id();
			// ==========================
			// Send Email to Admin
			// ==========================
			$superAdmin = $this->common->getData('superAdmin', ['admin_type' => '2'], ['single']);

			$subject = "New User Interested in Joining Interfriends";

			$countryCode = !empty($_REQUEST['country_code']) ? trim($_REQUEST['country_code']) : '';
			$phoneNumber = $_REQUEST['phone_number'] ?? '';
			$fullPhone   = (!empty($countryCode) ? $countryCode . ' ' : '') . $phoneNumber;

			$messaged1 = "
			Hello " . $superAdmin['name'] . ",<br><br>

			A new user has expressed interest in joining Interfriends.<br><br>

			<b>Name:</b> " . ($_REQUEST['first_name'] ?? '') . " " . ($_REQUEST['last_name'] ?? '') . "<br>
			<b>Email:</b> " . ($_REQUEST['email'] ?? '') . "<br>
			<b>Phone:</b> " . $fullPhone . "<br><br>

			Please review the details and take the necessary action.<br><br>

			Thank you.
		";

			$this->sendMail($superAdmin['email'], $subject, $messaged1);

			// ==========================
			// Send Admin Notification
			// ==========================

			$notificationMessage = "A new user (" . ($_REQUEST['email'] ?? '') . ") is interested in joining Interfriends.";

			$this->send_nofificationAdmin(
				0, // No user_id available for interested users
				0, // No group_id available
				$notificationMessage,
				$insert_id, // inserted record id
				"10" // notification type for interested user
			);

			echo json_encode([
				'status'  => true,
				'message' => 'Data submitted successfully.'
			]);
		} else {

			echo json_encode([
				'status'  => false,
				'message' => 'Something went wrong.'
			]);
		}
	}

	public function recommendUser()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$email = $this->common->getData('recommend_user', array('email' => $_REQUEST['email']), array('single'));
		if (!empty($email)) {
			$this->response(false, "Email already exists");
			die();
		}

		$post = $this->common->getField('recommend_user', $_REQUEST);

		$result = $this->common->insertData('recommend_user', $post);
		if ($result) {
			$insert_id = $this->db->insert_id();
			$this->common->query_normal("UPDATE credit_score_user SET recommended_a_friend = recommended_a_friend+0 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");

			$this->updateCreditScore(0, 'plus', $_REQUEST['user_id']);

			if (!empty($_REQUEST['refer_user_id'])) {
				$this->common->query_normal("UPDATE credit_score_user SET acted_as_second_referee = acted_as_second_referee+0 WHERE `user_id` = '" . $_REQUEST['refer_user_id'] . "'");
				$this->updateCreditScore(0, 'plus', $_REQUEST['refer_user_id']);
			}


			$message = "Recommend a user";
			$this->send_nofificationAdmin($_REQUEST['user_id'], $_REQUEST['group_id'], $message, $insert_id, "8");



			$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['refer_user_id']), array('single'));

			$userDetailTo = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

			$data['recommend_member'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];

			$data['recommend_by'] = $_REQUEST['first_name'] . " " . $_REQUEST['last_name'];

			$data['recommend_first_member'] = $userDetailTo['first_name'] . " " . $userDetailTo['last_name'];
			$subject = "Recommend a Friend";

			$messaged = $this->load->view('template/recommend-mail', $data, true);

			$mail = $this->sendMail($userDetailFrom['email'], $subject, $messaged);

			$checkuser = $this->common->getData('user_circle', array("user_id" => $_REQUEST['refer_user_id']), array('single'));

			if (!empty($checkuser)) {
				$circle_id = 	$checkuser['circle_id'];
				if ($circle_id) {
					$checklead = $this->common->getData('user_circle', array("circle_id" => $circle_id, "circle_lead" => '1'), array('single'));
					$userDetailcircleLead = $this->common->getData('user', array('user_id' => $checklead['user_id']), array('single'));

					$checkdeputylead = $this->common->getData('user_circle', array("circle_id" => $circle_id, "deputycirclelead" => '1'), array('single'));
					$userDetailcircledeputylead = $this->common->getData('user', array('user_id' => $checkdeputylead['user_id']), array('single'));

					if (!empty($userDetailcircleLead)) {
						$data['recommend_member'] = $userDetailcircleLead['first_name'] . " " . $userDetailcircleLead['last_name'];
						$data['recommend_by'] = $_REQUEST['first_name'] . " " . $_REQUEST['last_name'];
						$data['recommend_first_member'] = $userDetailTo['first_name'] . " " . $userDetailTo['last_name'];
						$subject = "Recommend a Friend";
						$messaged = $this->load->view('template/recommend-mail', $data, true);
						$mail = $this->sendMail($userDetailcircleLead['email'], $subject, $messaged);
					}

					if (!empty($userDetailcircledeputylead)) {

						$data['recommend_member'] = $userDetailcircledeputylead['first_name'] . " " . $userDetailcircledeputylead['last_name'];
						$data['recommend_by'] = $_REQUEST['first_name'] . " " . $_REQUEST['last_name'];
						$data['recommend_first_member'] = $userDetailTo['first_name'] . " " . $userDetailTo['last_name'];
						$subject = "Recommend a Friend";
						$messaged = $this->load->view('template/recommend-mail', $data, true);
						$mail = $this->sendMail($userDetailcircledeputylead['email'], $subject, $messaged);
					}
				}
			}

			$data1['link'] = USER_BASE_URL . 'register';
			$data1['sendername'] = $_REQUEST['first_name'] . " " . $_REQUEST['last_name'];
			$data1['useremail'] = "";
			$data1['message'] = '<p style="margin-bottom:10px;">Your Friend ' . $data['recommend_first_member'] . ' and ' . $data['recommend_member'] . ' have recommended you to interfriends to join the group.</p><p>To accept and continue your registration,click on link below to complete your registration</p><a href="' . $data1["link"] . '">click here</a>';
			$messaged1 = $this->load->view('template/common-mail', $data1, true);
			$mail1 = $this->sendMail($_REQUEST['email'], $subject, $messaged1);


			$this->response(true, "Request Submitted");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function loanList()
	{
		// limit code start
		// if(empty($_REQUEST['start'])) {
		// 		$start = 10;
		// 		$end = 0;
		// } else {
		// 	$start = 10;
		// 	$end = $_REQUEST['start'];
		// }
		// limit code end

		//  

		$wherePending = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.loan_type= '" . $_REQUEST['loan_type'] . "'";
		$resultPending = $this->user_model->loan_detail($wherePending, array());

		if (!empty($resultPending)) {
			foreach ($resultPending as $key => $value) {
				$resultPending[$key]['payment_list'] = array();
				$resultPending[$key]['payment_list_status'] = true;
				$resultPending[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultPending[$key]['total_payment'] = (float) $value['total_payment'];
			}
		}

		$whereComplete = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 2 AND L.loan_type= '" . $_REQUEST['loan_type'] . "'";
		$resultComplete = $this->user_model->loan_detail($whereComplete, array());



		if (!empty($resultComplete)) {
			foreach ($resultComplete as $key => $value) {
				$resultComplete[$key]['payment_list'] = array();
				$resultComplete[$key]['payment_list_status'] = true;
				$resultComplete[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultComplete[$key]['total_payment'] = (float) $value['total_payment'];
			}
		}


		$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '" . $_REQUEST['loan_type'] . "'";
		$resultActive = $this->user_model->loan_detail($whereActive, array());
		$totalPaidAmount = 0;
		$totalActivePayment = 0;
		$avgAmount = 0;
		if (!empty($resultActive)) {
			foreach ($resultActive as $key => $value) {
				$resultActive[$key]['payment_list'] = array();
				$resultActive[$key]['payment_list_status'] = true;
				$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
				$totalPaidAmount += $resultActive[$key]['paid_amount'];
				$totalActivePayment += $resultActive[$key]['total_payment'];
			}

			$avgAmount = $totalActivePayment - $totalPaidAmount;
		}


		// $loanAvgPayment = $this->common->getData('user_loan',array('group_id' => $_REQUEST['group_id'],'user_id' => $_REQUEST['user_id'],'status' => '4'),array("field" => 'sum(total_payment) as total_payment',"single"));


		// if($loanAvgPayment['total_payment']) {
		// 	$avgAmount = $loanAvgPayment['total_payment'];
		// } else {
		// 	$avgAmount = 0.00;
		// }

		$loanAvgComplete = $this->common->getData('user_loan', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'status' => '2', "loan_type" => $_REQUEST['loan_type']), array("field" => 'sum(total_payment) as total_payment', "single"));


		if ($loanAvgComplete['total_payment']) {
			$avgComplete = $loanAvgComplete['total_payment'];
		} else {
			$avgComplete = 0.00;
		}

		$this->response(true, "Loan fetch Successfully.", array("laonPending" => $resultPending, "laonComplete" => $resultComplete, "laonActive" => $resultActive, "avgAmount" => $avgAmount, "avgComplete" => $avgComplete));
	}



	public function miscellaneousList()
	{
		// limit code start
		// if(empty($_REQUEST['start'])) {
		// 		$start = 10;
		// 		$end = 0;
		// } else {
		// 	$start = 10;
		// 	$end = $_REQUEST['start'];
		// }
		// limit code end


		$wherePending = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "'";
		$resultPending = $this->user_model->miscellaneous_detail_new($wherePending, array());

		if (!empty($resultPending)) {
			foreach ($resultPending as $key => $value) {
				$resultPending[$key]['payment_list'] = array();
				$resultPending[$key]['payment_list_status'] = true;
				$resultPending[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultPending[$key]['total_payment'] = (float) $value['total_payment'];
			}
		}

		$whereComplete = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 2";
		$resultComplete = $this->user_model->miscellaneous_detail_new($whereComplete, array());



		if (!empty($resultComplete)) {
			foreach ($resultComplete as $key => $value) {
				$resultComplete[$key]['payment_list'] = array();
				$resultComplete[$key]['payment_list_status'] = true;
				$resultComplete[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultComplete[$key]['total_payment'] = (float) $value['total_payment'];
			}
		}


		$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4";
		$resultActive = $this->user_model->miscellaneous_detail_new($whereActive, array());
		$totalPaidAmount = 0;
		$totalActivePayment = 0;
		$avgAmount = 0;
		if (!empty($resultActive)) {
			foreach ($resultActive as $key => $value) {
				$resultActive[$key]['payment_list'] = array();
				$resultActive[$key]['payment_list_status'] = true;
				$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
				$totalPaidAmount += $resultActive[$key]['paid_amount'];
				$totalActivePayment += $resultActive[$key]['total_payment'];
			}

			$avgAmount =   $totalPaidAmount - $totalActivePayment;
		}


		// $loanAvgPayment = $this->common->getData('user_loan',array('group_id' => $_REQUEST['group_id'],'user_id' => $_REQUEST['user_id'],'status' => '4'),array("field" => 'sum(total_payment) as total_payment',"single"));


		// if($loanAvgPayment['total_payment']) {
		// 	$avgAmount = $loanAvgPayment['total_payment'];
		// } else {
		// 	$avgAmount = 0.00;
		// }

		$loanAvgComplete = $this->common->getData('user_miscellaneous', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'status' => '2'), array("field" => 'sum(total_payment) as total_payment', "single"));


		if ($loanAvgComplete['total_payment']) {
			$avgComplete = $loanAvgComplete['total_payment'];
		} else {
			$avgComplete = 0.00;
		}

		$this->response(true, "Loan fetch Successfully.", array("laonPending" => $resultPending, "laonComplete" => $resultComplete, "laonActive" => $resultActive, "avgAmount" => $avgAmount, "avgComplete" => $avgComplete));
	}

	public function addEmergencyLoan()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		$post = $this->common->getField('user_emergency_loan', $_REQUEST);
		$result = $this->common->insertData('user_emergency_loan', $post);
		$loan_id = $this->db->insert_id();

		if ($result) {
			$message = "Emergency Fund Request";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['group_id'], $message, $loan_id, "1");


			if (!empty($_REQUEST['gurarantor'])) {
				$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['gurarantor']), array('single'));

				$userDetailTo = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

				$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
				$data['useremail'] = "";
				$subject = "Emergency Help";

				$data['message'] = $userDetailTo['first_name'] . " " . $userDetailTo['last_name'] . " has applied for " . $subject . " of £" . $_REQUEST['loan_amount'] . " assistance and has named you as a guarantor.<p>Respond to this email and confirm if you are aware of this and are happy to act as a guarantor.</p>
				<div>
				<h4 style='margin-bottom:8px; font-size:15px; font-weight:600'>Responsibilities of a guarantor</h4>
				<ul style='list-style:none;padding-left:0px'>
				<li style='margin-bottom: 5px;'>- You are confirming that the applicant is trustworthy</li>
				<li style='margin-bottom: 5px;'>
				- You are confirming that you know the applicant well
				</li>
				<li style='margin-bottom: 5px;'>
				- You are accepting that you will be accountable for making payments on behalf of the applicant in the unlikely event that the applicant is unable to pay
				</li>
				<li style='margin-bottom: 5px;'>
				- You confirm that you will assist Interfriends to recover any amounts owed in the event that we exhaust all our options for recovering any amount owed
				</li>
				<li style='margin-bottom: 5px;'>- You are agreeing that in the event that we are unable to recover the amount owed, we can use all or part of your provident or any amount you have with interfriends for example; investments, safekeeping to pay off the outstanding amount </li>
			
				</ul>
				</div>
				";

				$messaged = $this->load->view('template/common-mail', $data, true);
				$mail = $this->sendMail($userDetailFrom['email'], $subject, $messaged);
			}


			$this->common->query_normal("UPDATE credit_score_user SET emergency_loan_request = emergency_loan_request-100 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
			$this->updateCreditScore(100, 'minus', $_REQUEST['user_id']);

			$this->response(true, "Emergency loan submitted successfully", array("loan_id" => $loan_id));
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	function send_nofification($user_id, $group_id, $message, $id, $type)
	{
		$userDetailFrom = $this->common->getData('user', array('user_id' => $user_id), array('single'));
		$userDetailTo = $this->common->getData('superAdmin', array('admin_type' => '1'));

		$token_arr = array();
		if (!empty($userDetailTo)) {
			foreach ($userDetailTo as $key => $value) {
				if (!empty($value['web_token'])) {
					$token_arr[] = $value['web_token'];
				}
			}
		}



		$this->common->query_normal("UPDATE superAdmin SET notification_count = notification_count+1 WHERE `admin_type` = '1'");


		$userName = '';
		$userName = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];

		$title = $userName;

		$this->common->insertData('notification_admin_tbl', array("message" => $message, "user_send_from" => $user_id, "user_id" => $user_id, "group_id" => $group_id, "main_id" => $id, "created_at" => date('Y-m-d H:i:s'), "type" => $type, "user_type" => '2'));

		$msg_notification = array(
			"body" => $message,
			"title" => $title,
			"icon" => ASSET_BASE_URL . 'icons/i_white.jfif'
		);

		// for web		
		if (!empty($token_arr)) {
			$data = array(
				"user_id" => $user_id,
				'type' => '3'
			);
			$res = $this->send_notification_web_multiple($token_arr, $msg_notification, $data);
		}
	}


	function send_nofificationAdmin($user_id, $group_id, $message, $id, $type)
	{
		$userDetailFrom = $this->common->getData('user', array('user_id' => $user_id), array('single'));
		$userDetailTo = $this->common->getData('superAdmin', array('admin_type' => '2'));

		$token_arr = array();
		if (!empty($userDetailTo)) {
			foreach ($userDetailTo as $key => $value) {
				if (!empty($value['web_token'])) {
					$token_arr[] = $value['web_token'];
				}
			}
		}



		$this->common->query_normal("UPDATE superAdmin SET notification_count = notification_count+1 WHERE `admin_type` = '2'");


		$userName = '';
		$userName = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];

		$title = $userName;

		$this->common->insertData('notification_admin_tbl', array("message" => $message, "user_send_from" => $user_id, "user_id" => $user_id, "group_id" => $group_id, "main_id" => $id, "created_at" => date('Y-m-d H:i:s'), "type" => $type, "user_type" => '1'));

		$msg_notification = array(
			"body" => $message,
			"title" => $title,
			"icon" => ASSET_BASE_URL . 'icons/i_white.jfif'
		);

		// for web		
		if (!empty($token_arr)) {
			$data = array(
				"user_id" => $user_id,
				'type' => '3'
			);
			$res = $this->send_notification_web_multiple($token_arr, $msg_notification, $data);
		}
	}


	public function notification_list()
	{
		if (!empty($_REQUEST['user_id'])) {

			// limit code start
			if (empty($_REQUEST['start'])) {
				$start = 10;
				$end = 0;
			} else {
				$start = 10;
				$end = $_REQUEST['start'];
			}
			// limit code end

			$user_id = $_REQUEST['user_id'];
			$where = "N.user_send_to = '" . $user_id . "'";
			$notificationInfo = $this->user_model->notification_detail($where, array(), $start, $end);

			if (!empty($notificationInfo)) {
				// foreach ($notificationInfo as $key => $value) {

				//    }


				$result = $this->common->updateData('user', array('notification_count' => '0', 'is_investment_popup' => '0'), array('user_id' => $user_id));

				$this->response(true, "Notification List.", array("lists" => $notificationInfo));
			} else {
				$this->response(false, "Notification Not Found.", array("lists" => array()));
			}
		} else {
			$this->response(false, "Missing Parameter.");
		}
	}


	public function emergencyLoanList()
	{
		// limit code start
		// if(empty($_REQUEST['start'])) {
		// 		$start = 10;
		// 		$end = 0;
		// } else {
		// 	$start = 10;
		// 	$end = $_REQUEST['start'];
		// }
		// limit code end


		$wherePending = "UE.user_id = '" . $_REQUEST['user_id'] . "' AND UE.group_id = '" . $_REQUEST['group_id'] . "'";
		$resultPending = $this->user_model->emergencyLoan_detail($wherePending, array());

		if (!empty($resultPending)) {
			foreach ($resultPending as $key => $value) {
				$resultPending[$key]['payment_list'] = array();
				$resultPending[$key]['payment_list_status'] = true;
			}
		}

		$whereComplete = "UE.user_id = '" . $_REQUEST['user_id'] . "' AND UE.group_id = '" . $_REQUEST['group_id'] . "' AND UE.status = 2";
		$resultComplete = $this->user_model->emergencyLoan_detail($whereComplete, array());



		if (!empty($resultComplete)) {
			foreach ($resultComplete as $key => $value) {
				$resultComplete[$key]['payment_list'] = array();
				$resultComplete[$key]['payment_list_status'] = true;
			}
		}


		$whereActive = "UE.user_id = '" . $_REQUEST['user_id'] . "' AND UE.group_id = '" . $_REQUEST['group_id'] . "' AND UE.status = 4";
		$resultActive = $this->user_model->emergencyLoan_detail($whereActive, array());

		if (!empty($resultActive)) {
			foreach ($resultActive as $key => $value) {
				$resultActive[$key]['payment_list'] = array();
				$resultActive[$key]['payment_list_status'] = true;
			}
		}


		$loanAvgPayment = $this->common->getData('user_emergency_loan', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'status' => '4'), array("field" => 'sum(loan_amount) as total_payment', "single"));


		if ($loanAvgPayment['total_payment']) {
			$avgAmount = $loanAvgPayment['total_payment'];
		} else {
			$avgAmount = 0.00;
		}


		$loanAvgComplete = $this->common->getData('user_emergency_loan', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'status' => '2'), array("field" => 'sum(loan_amount) as total_payment', "single"));


		if ($loanAvgComplete['total_payment']) {
			$avgComplete = $loanAvgComplete['total_payment'];
		} else {
			$avgComplete = 0.00;
		}

		$this->response(true, "Loan fetch Successfully.", array("laonPending" => $resultPending, "laonComplete" => $resultComplete, "laonActive" => $resultActive, "avgAmount" => '-' . $avgAmount, "avgComplete" => $avgComplete));
	}


	public function loanPaymentList()
	{
		$this->mergeJsonRequestData();

		$where = "user_id = '" . $_REQUEST['user_id'] . "' AND group_id = '" . $_REQUEST['group_id'] . "' AND loan_id = '" . $_REQUEST['loan_id'] . "' ";
		$where1 = "( user_id = '" . $_REQUEST['user_id'] . "' AND group_id = '" . $_REQUEST['group_id'] . "' AND loan_id = '" . $_REQUEST['loan_id'] . "') GROUP BY user_id ";

		$PaymentList = $this->common->getData('user_loan_payment', $where, array(
			'sort_by' => 'id',
			'sort_direction' => 'asc'
		));

		$PaymentTotal = $this->common->getData('user_loan_payment', $where1, array("field" => 'user_id,sum(amount) as total_amount', "single"));

		if ($PaymentTotal) {
			$totalAmount = $PaymentTotal['total_amount'];
		} else {
			$totalAmount = 0.00;
		}

		$LoanDetail = $this->common->getData('user_loan', array("id" => $_REQUEST['loan_id']), array('single'));

		if (!empty($LoanDetail)) {
			$loanAmount_initital = $LoanDetail['loan_amount'];
			$loanAmount = $LoanDetail['total_payment'];
			$interest_rate = $LoanDetail['interest_rate'];
			$interest_payable = $LoanDetail['interest_payable'];
			$provident = $LoanDetail['provident'];
		} else {
			$loanAmount_initital = 0;
			$loanAmount = 0;
			$interest_rate = 0;
			$interest_payable = 0;
			$provident = 0;
		}

		if (!empty($PaymentList)) {
			$this->response(true, "Payment fetch Successfully.", array(
				"paymentList" => $PaymentList,
				'totalPaidAmount' => (float)$totalAmount,
				'loanAmount' => (float)$loanAmount,
				'interest_rate' => (float)$interest_rate,
				'interest_payable' => (float)$interest_payable,
				'loanAmount_initital' => (float)$loanAmount_initital,
				'provident' => (float)$provident
			));
		} else {
			$this->response(true, "Payment fetch Successfully.", array(
				"paymentList" => array(),
				'totalPaidAmount' => (float)$totalAmount,
				'loanAmount' => (float)$loanAmount,
				'interest_rate' => (float)$interest_rate,
				'interest_payable' => (float)$interest_payable,
				'loanAmount_initital' => (float)$loanAmount_initital,
				'provident' => (float)$provident
			));
		}
	}


	public function miscellaneousPaymentList()
	{

		$where = "user_id = '" . $_REQUEST['user_id'] . "' AND group_id = '" . $_REQUEST['group_id'] . "' AND loan_id = '" . $_REQUEST['loan_id'] . "' ";
		$PaymentList = $this->common->getData('user_miscellaneous_payment', $where);

		if (!empty($PaymentList)) {
			$this->response(true, "Payment fetch Successfully.", array("paymentList" => $PaymentList));
		} else {
			$this->response(true, "Payment fetch Successfully.", array("paymentList" => array()));
		}
	}


	public function groupCycleAll_list()
	{
		$result = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id']));
		if (!empty($result)) {
			$this->response(true, "Data fetch Successfully.", array("lists" => $result));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array()));
		}
	}


	public function getuser_group_by_singlecycle()
	{
		$groupCycleList = $this->common->getData('user_group_lifecycle', array('group_id' => $_REQUEST['group_id'], 'groupLifecycle_id' => $_REQUEST['groupLifecycle_id'], 'user_id' => $_REQUEST['user_id']));
		if (!empty($groupCycleList)) {
			$this->response(true, 'group fetch successfully', array('groupCycleList' => $groupCycleList));
		} else {
			$this->response(true, 'group not found', array('groupCycleList' => array()));
		}
	}




	public function pfList()
	{
		date_default_timezone_set("Europe/London");
		$main_id = "";

		if (!empty($_REQUEST['main_id'])) {
			$main_id = " AND PF.main_id = '" . $_REQUEST['main_id'] . "'";
		} else {
			$main_id = "";
		}

		$wherePf = "PF.user_id = '" . $_REQUEST['user_id'] . "' AND PF.group_id = '" . $_REQUEST['group_id'] . "' $main_id";
		$pfList = $this->user_model->pf_detail($wherePf);

		if (!empty($_REQUEST['main_id'])) {
			$totalCreditpfAmount = $this->common->getData('pf_user', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'payment_type' => '2', "main_id" => $_REQUEST['main_id']), array("field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest', "single"));
			$totalDebitpfAmount = $this->common->getData('pf_user', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'payment_type' => '1', "main_id" => $_REQUEST['main_id']), array("field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest', "single"));
		} else {
			$totalCreditpfAmount = $this->common->getData('pf_user', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'payment_type' => '2'), array("field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest', "single"));
			$totalDebitpfAmount = $this->common->getData('pf_user', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'payment_type' => '1'), array("field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest', "single"));
		}


		$pfInterest = $totalCreditpfAmount['pf_interest'] - $totalDebitpfAmount['pf_interest'];
		$pfAmount = $totalCreditpfAmount['pf_total_amount'] - $totalDebitpfAmount['pf_total_amount'];

		if (!empty($pfList)) {
			$this->response(true, 'pf fetch successfully', array('pfList' => $pfList, 'pfAmount' => $pfAmount, 'pf_interest' => $pfInterest));
		} else {
			$this->response(true, 'pf not found', array('pfList' => array(), 'pfAmount' => 0, 'pf_interest' => 0));
		}
	}


	public function all_user_list()
	{
		$where = "(U.status = 1 OR U.status=2)";
		if (!empty($_REQUEST['group_id'])) {
			$where .= " AND U.user_id in (Select user_id from user_group where group_id=" . $_REQUEST['group_id'] . " AND user_id !=" . $_REQUEST['user_id'] . ")";
		}

		$result = $this->user_model->user_detail_recommend($where, array());
		if (!empty($result)) {
			$this->response(true, "User fetch Successfully.", array("userList" => $result));
		} else {
			$this->response(true, "User fetch Successfully.", array("userList" => array()));
		}
	}

	public function lastTransactionsUserCycle()
	{
		// limit code start
		$groupCycleList = $this->common->getData('user_group_lifecycle', array('group_id' => '22', 'user_id' =>  $_REQUEST['user_id'], 'updated_at != ' => null), array('sort_by' => 'created_at', 'sort_direction' => 'asc', 'limit' => '6', ''));
		$user_id = $_REQUEST['user_id'];

		$usercircle = $this->common->getData('user_circle', array('user_id' =>  $user_id), array('single'));
		$avg = "";
		if ($usercircle) {
			$users = $this->common->getData('user_circle', array('circle_id' =>  $usercircle['circle_id']), array(''));
			$i = 0;
			$trustscore = 0;
			foreach ($users as $key => $value) {
				$userinfo = get_user_details($value['user_id']);
				$trustscore += $userinfo['total_credit_score'];
				$i++;
			}

			if ($trustscore) {
				$avg = $trustscore / $i;
			}
		}
		if (!empty($groupCycleList)) {
			$this->response(true, "User fetch Successfully.", array("userList" => $groupCycleList, 'Avg_circle_score' => $avg, "circle_id" => $usercircle['circle_id']));
		} else {
			$this->response(true, "User fetch Successfully.", array("userList" => array()));
		}
	}


	// public function safeKeepingList()
	// {
	// 	$safeKeepingList = $this->common->getData('safe_keeping', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id']));

	// 	// removed this conditions ('request_status' => '1') in both amount
	// 	$totalCreditAmount = $this->common->getData('safe_keeping', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'pyment_type' => 2, 'request_status' => '1'), array("field" => 'sum(amount) as safe_keeping_total_amount', "single"));

	// 	$totalDebitAmount = $this->common->getData('safe_keeping', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'pyment_type' => 1, 'request_status' => '1'), array("field" => 'sum(amount) as safe_keeping_total_amount', "single"));


	// 	$safeKeepingAmount = $totalCreditAmount['safe_keeping_total_amount'] - $totalDebitAmount['safe_keeping_total_amount'];

	// 	if (!empty($safeKeepingList)) {
	// 		$this->response(true, 'safe Keeping fetch successfully', array('safeKeepingList' => $safeKeepingList, 'safeKeepingAmount' => $safeKeepingAmount));
	// 	} else {
	// 		$this->response(true, 'safe Keeping not found', array('safeKeepingList' => array(), 'safeKeepingAmount' => 0));
	// 	}
	// }

	// public function safeKeepingList()
	// {
	// 	$group_id = $_REQUEST['group_id'];
	// 	$user_id  = $_REQUEST['user_id'];

	// 	// condition for list
	// 	$condition = "group_id = '$group_id' AND user_id = '$user_id' 
	//               AND (
	//                     (requested_by = 'admin' AND request_status = '2') 
	//                     OR 
	//                     (requested_by = 'user' AND request_status = '1')
	//                   )";

	// 	// fetch list
	// 	$safeKeepingList = $this->common->getData('safe_keeping', $condition);

	// 	// total credit
	// 	$totalCreditAmount = $this->common->getData(
	// 		'safe_keeping',
	// 		$condition . " AND pyment_type = 2",
	// 		array("field" => 'SUM(amount) as safe_keeping_total_amount', "single")
	// 	);

	// 	// total debit
	// 	$totalDebitAmount = $this->common->getData(
	// 		'safe_keeping',
	// 		$condition . " AND pyment_type = 1",
	// 		array("field" => 'SUM(amount) as safe_keeping_total_amount', "single")
	// 	);

	// 	$safeKeepingAmount = $totalCreditAmount['safe_keeping_total_amount'] - $totalDebitAmount['safe_keeping_total_amount'];

	// 	if (!empty($safeKeepingList)) {
	// 		$this->response(true, 'Safe keeping fetched successfully', array(
	// 			'safeKeepingList'   => $safeKeepingList,
	// 			'safeKeepingAmount' => $safeKeepingAmount
	// 		));
	// 	} else {
	// 		$this->response(true, 'Safe keeping not found', array(
	// 			'safeKeepingList'   => array(),
	// 			'safeKeepingAmount' => 0
	// 		));
	// 	}
	// }

	// updated by @krishn on 18-08-25
	public function safeKeepingList()
	{
		$group_id = $_REQUEST['group_id'];
		$user_id  = $_REQUEST['user_id'];

		// Condition for displaying list records
		$condition = "group_id = '$group_id'
                  AND user_id = '$user_id'
                  AND (
                        requested_by = 'admin'
                        OR
                        (requested_by = 'user' AND request_status = '1')
                      )";

		// Fetch list
		$safeKeepingList = $this->common->getData(
			'safe_keeping',
			$condition
		);

		/*
		|--------------------------------------------------------------------------
		| Amount Calculation
		| Same logic as Admin Side
		|--------------------------------------------------------------------------
		*/
		$amountCondition = "group_id = '$group_id'
                        AND user_id = '$user_id'";

		// Total Credit
		$totalCreditAmount = $this->common->getData(
			'safe_keeping',
			$amountCondition . " AND pyment_type = 2",
			array(
				"field" => 'SUM(amount) as safe_keeping_total_amount',
				"single"
			)
		);

		// Total Debit
		$totalDebitAmount = $this->common->getData(
			'safe_keeping',
			$amountCondition . " AND pyment_type = 1",
			array(
				"field" => 'SUM(amount) as safe_keeping_total_amount',
				"single"
			)
		);

		$creditAmount = (float)($totalCreditAmount['safe_keeping_total_amount'] ?? 0);
		$debitAmount  = (float)($totalDebitAmount['safe_keeping_total_amount'] ?? 0);

		$safeKeepingAmount = $creditAmount - $debitAmount;

		if (!empty($safeKeepingList)) {

			$this->response(
				true,
				'Safe keeping fetched successfully',
				array(
					'safeKeepingList'   => $safeKeepingList,
					'safeKeepingAmount' => $safeKeepingAmount
				)
			);
		} else {

			$this->response(
				true,
				'Safe keeping not found',
				array(
					'safeKeepingList'   => array(),
					'safeKeepingAmount' => $safeKeepingAmount
				)
			);
		}
	}

	// // created by @krishn on 18-08-25
	// public function safeKeepingList()
	// {
	// 	$group_id = $_REQUEST['group_id'];
	// 	$user_id  = $_REQUEST['user_id'];

	// 	// Add condition for requested_by and request_status
	// 	$condition = "group_id = '$group_id' AND user_id = '$user_id' 
	//               AND (
	//                     (requested_by = 'admin' AND request_status = '2') 
	//                     OR 
	//                     (requested_by = 'user' AND request_status = '1')
	//                   )";

	// 	$safeKeepingList = $this->common->getData('safe_keeping', $condition);

	// 	// total credit
	// 	$totalCreditAmount = $this->common->getData('safe_keeping', $condition . " AND pyment_type = 2", [
	// 		"field"  => 'SUM(amount) as safe_keeping_total_amount',
	// 		"single" => true
	// 	]);

	// 	// total debit
	// 	$totalDebitAmount = $this->common->getData('safe_keeping', $condition . " AND pyment_type = 1", [
	// 		"field"  => 'SUM(amount) as safe_keeping_total_amount',
	// 		"single" => true
	// 	]);

	// 	$safeKeepingAmount = $totalCreditAmount['safe_keeping_total_amount'] - $totalDebitAmount['safe_keeping_total_amount'];

	// 	if (!empty($safeKeepingList)) {
	// 		$this->response(true, 'Safe keeping fetched successfully', [
	// 			'safeKeepingList'   => $safeKeepingList,
	// 			'safeKeepingAmount' => $safeKeepingAmount
	// 		]);
	// 	} else {
	// 		$this->response(true, 'Safe keeping not found', [
	// 			'safeKeepingList'   => [],
	// 			'safeKeepingAmount' => 0
	// 		]);
	// 	}
	// }


	public function cylcleAvg()
	{

		$cycleTransfer = $this->common->getData('cycle_status_management', array('user_id' => $_REQUEST['user_id'], 'group_id' => $_REQUEST['group_id'], 'group_cycle_id' => $_REQUEST['group_cycle_id']), array('single'));

		if (empty($cycleTransfer)) {
			$where = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
			$result = $this->common->getData('user_group_lifecycle', $where, array("field" => 'sum(amount) as total_payment', "single"));

			if (!empty($result['total_payment'])) {
				$avgAmount = $result['total_payment'];
			} else {
				$avgAmount = 0.00;
			}
		} else {
			$paidWhere = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
			$paidResult = $this->common->getData('user_group_lifecycle', $paidWhere, array("field" => 'sum(amount) as total_payment', "single"));

			if (!empty($paidResult['total_payment'])) {
				$paidAvgAmount = $paidResult['total_payment'];
			} else {
				$paidAvgAmount = 0;
			}


			$result = $this->common->getData('user_group_lifecycle', array('groupLifecycle_id' => $_REQUEST['group_cycle_id'], 'user_id' => $_REQUEST['user_id']), array('field' => 'SUM(amount) as total_amount', 'single'));

			$payout_amount_total = $result['total_amount'];

			//print_r($payout_amount_total);
			$avgAmount = $paidAvgAmount - $payout_amount_total;
		}

		// 		$groupCycleInfo = $this->common->getData('group_lifecycle',array('group_id'=>$_REQUEST['group_id'], 'group_type_id'=>$_REQUEST['type']));
		// 		$avgAmount = 0;
		// 		if(!empty($groupCycleInfo)) {
		// 			foreach ($groupCycleInfo as $key => $value) {

		//     			$paidWhere = "group_id = '".$_REQUEST['group_id']."' AND groupLifecycle_id = '".$value['id']."' AND user_id = '". $_REQUEST['user_id'] ."' AND status !='1'";
		//     			$paidResult = $this->common->getData('user_group_lifecycle',$paidWhere,array("field" => 'sum(amount) as total_payment',"single"));

		//     			if(!empty($paidResult['total_payment'])) {
		//     				$avgAmount += $paidResult['total_payment'];
		//     			}
		// 			}
		// 		} 


		$whereCycle = "group_id = '" . $_REQUEST['group_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status != 1";
		$resultCycle = $this->common->getData('user_group_lifecycle', $whereCycle, array("field" => 'sum(amount) as total_payment', "single"));

		if (!empty($resultCycle['total_payment'])) {
			$totalAvgAmount = $resultCycle['total_payment'];
		} else {
			$totalAvgAmount = 0;
		}

		$this->response(true, 'amount fetch successfully', array('avgAmount' => $avgAmount, 'totalAvgAmount' => $totalAvgAmount));
	}

	public function cylcleAvgPayout()
	{

		$where = "group_id = '" . $_REQUEST['group_id'] . "' AND group_cycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "'";
		$result = $this->common->getData('payout_cycle', $where, array("field" => 'sum(payout_amount) as total_payment', "single"));

		if (!empty($result['total_payment'])) {
			$avgAmount = $result['total_payment'];
		} else {
			$avgAmount = 0.00;
		}




		$where = "group_id = '" . $_REQUEST['group_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "'";
		$result = $this->common->getData('payout_cycle', $where, array("field" => 'sum(payout_amount) as total_payment', "single"));

		if (!empty($result['total_payment'])) {
			$totalAvgAmount = $result['total_payment'];
		} else {
			$totalAvgAmount = 0.00;
		}

		$this->response(true, 'amount fetch successfully', array('avgAmount' => $avgAmount, 'totalAvgAmount' => $totalAvgAmount));
	}


	public function investment_list()
	{
		$where = "I.user_id = '" . $_REQUEST['user_id'] . "' AND I.group_id = '" . $_REQUEST['group_id'] . "'";

		if (!empty($_REQUEST['payment_status'])) {
			$where .= " AND I.payment_status = '" . $_REQUEST['payment_status'] . "'";
		}

		$result = $this->user_model->investment_detail($where, array());


		$resultTotal = $this->common->getData('investment as I', $where, array("field" => 'sum(I.amount) as total_payment', "single"));

		if (!empty($resultTotal['total_payment'])) {
			$totalAmount = $resultTotal['total_payment'];
		} else {
			$totalAmount = 0.00;
		}

		if (!empty($result)) {
			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "totalAmount" => $totalAmount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "totalAmount" => 0));
		}
	}

	public function property_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 8;
			$end = 0;
		} else {
			$start = 8;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = 'P.status=1';
		$result = $this->user_model->property_detail($where, array(), $start, $end);

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
				if (!empty($value['property_image'])) {
					$result[$key]['property_image'] = base_url($value['property_image']);
					$result[$key]['show_property_image'] = base_url($value['property_image']);
					$result[$key]['property_image_thumb'] = base_url($value['property_image_thumb']);
				} else {
					$result[$key]['property_image'] = 'assets/img/default-user-icon.jpg';
					$result[$key]['property_image_thumb'] = 'assets/img/default-user-icon.jpg';

					$result[$key]['show_property_image'] = 'assets/img/default-user-icon.jpg';
				}


				$result[$key]['more_image_status'] = false;
				$result[$key]['background_image'] = array();

				$resultInvestment = $this->common->getData('investment', array("property_id" => $value['id']), array("field" => 'sum(amount) as total_payment', "single"));



				if (!empty((int)$resultInvestment['total_payment']) && !empty((int)$value['main_amount'])) {

					$result[$key]['invest_amount'] = $resultInvestment['total_payment'];
					$result[$key]['invest_percentage'] = round(($resultInvestment['total_payment'] * 100) / $value['main_amount']);
				} else {
					$result[$key]['invest_amount'] = 0.00;
					$result[$key]['invest_percentage'] = 0;
				}
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array()));
		}
	}


	public function get_notification_count()
	{
		$userInfo = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

		$this->response(false, "Notification Count Found Successfully.", array("count" => $userInfo['notification_count']));
	}

	// created by @krishn on 05-06-25
	public function get_investment_notification_count()
	{
		$user_id = $_REQUEST['user_id'] ?? null;

		if (!$user_id) {
			$this->response(true, "User ID is required.", []);
			return;
		}

		$userInfo = $this->common->getData('user', ['user_id' => $user_id], ['single']);

		if (empty($userInfo)) {
			$this->response(true, "User not found.", []);
			return;
		}

		$investment_notification_count = 0;

		if (!empty($userInfo['is_investment_popup']) && $userInfo['is_investment_popup'] > 0) {

			$investment_notification_count = $userInfo['is_investment_popup'];
		}

		$this->response(
			false,
			"Notification count retrieved successfully.",
			[
				"is_investment_arrived" => $investment_notification_count
			]
		);
	}


	public function propertyImage()
	{
		$property_image_array = $this->common->getData('property_image_tbl', array('property_id' => $_REQUEST['property_id']));

		if (!empty($property_image_array)) {
			foreach ($property_image_array as $key => $value) {
				$property_image_array[$key]['background_image'] =  base_url($value['background_image']);
			}
		} else {
			$property_image_array =  array();
		}

		$this->response(true, "Property Image fetch Successfully.", array("property_image" => $property_image_array));
	}


	public function forgetPassword()
	{
		$data = $this->common->getData('user', array('email' => $_POST['email']), array('single'));
		if (!empty($data)) {
			$token = $this->generateToken();
			$data['token'] = $data['user_id'] . $token;
			$this->common->updateData('user', array('token' => $data['token']), array('user_id' => $data['user_id']));
			$message = $this->load->view('template/reset-mail', $data, true);

			////////////////////////////////////////

			$mail = $this->sendMail($_POST['email'], 'Forgot Password', $message);

			if ($mail) {
				$this->response(true, "Thank You, You Will Receive An E-mail In The Next 5 Minutes With Instructions For Resetting Your Password. If You Don't Receive This E-mail, Please Check Your Junk Mail Folder Or Contact Us For Further Assistance.");
			} else {
				$this->response(false, "Mail Not Delivered");
			}
		} else {
			$this->response(false, 'Email Not Registered');
		}
	}


	public function generateToken()
	{
		$seed = str_split('abcdefghijklmnopqrstuvwxyz'
			. 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
			. '0123456789'); // and any other characters
		shuffle($seed); // probably optional since array_is randomized;
		$rand = '';
		foreach (array_rand($seed, 8) as $k) {
			$rand .= $seed[$k];
		}
		return md5(microtime() . $rand);
	}



	// function sendMail($email, $subject, $message)
	// {

	// 	require_once(APPPATH . 'third_party/phpmailer/class.phpmailer.php');
	// 	require_once(APPPATH . 'third_party/phpmailer/class.smtp.php');

	// 	try {

	// 		$mail = new PHPMailer();

	// 		$mail->IsSMTP();
	// 		$mail->CharSet = 'UTF-8';
	// 		// $mail->Host = "smtp.gmail.com";
	// 		$mail->Host = "smtp.hostinger.com";

	// 		$mail->SMTPAuth = true;
	// 		// 		$mail->SMTPOptions = array(
	// 		//             'ssl' => array(
	// 		//                 'verify_peer' => false,
	// 		//                 'verify_peer_name' => false,
	// 		//                 'allow_self_signed' => true
	// 		//             )
	// 		//         );

	// 		$mail->Port = 587; // Or 587
	// 		// $mail->Username = 'interfriendscu@gmail.com';
	// 		$mail->Username = 'admin@interfriends.uk';
	// 		// $mail->Password = 'zbkydsoaizmbqnhm';
	// 		$mail->Password = '@Mbx9jm!2';
	// 		$mail->SMTPSecure = "tls";
	// 		//$mail->SMTPDebug = 2;
	// 		// $mail->setFrom("interfriendscu@gmail.com", 'Interfriends');
	// 		$mail->setFrom("admin@interfriends.uk", 'Interfriends');
	// 		$mail->Body = $message;

	// 		$mail->isHTML(true);
	// 		$mail->Subject = $subject;

	// 		$mail->addAddress($email);
	// 		$send =  $mail->send();

	// 		if ($send != '1') {
	// 			return false;
	// 		} else {
	// 			return true;
	// 		}
	// 	} catch (Exception $e) {
	// 		echo "An error occurred while sending the email: " . $e->getMessage();
	// 	}
	// }

	// created by @krishn on 12-08-25
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
			$mail->Port = 587;
			$mail->Username = 'admin@interfriends.uk';
			$mail->Password = '@Mbx9jm!2';
			$mail->SMTPSecure = "tls";

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

				$imapStream = @imap_open($imapServer, $imapUser, $imapPass);
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



	public function userDetailInfo()
	{
		$user_id = $_REQUEST['user_id'];
		$userinfo = get_user_details($user_id);

		if (!empty($userinfo)) {
			$this->response(true, "Profile Fetch Successfully.", array("userinfo" => $userinfo));
		} else {
			$this->response(false, "There Is Some Problem.Please Try Again.", array("userinfo" => array()));
		}
	}



	public function contactUs()
	{
		$data = array('name' => $_REQUEST['name'], 'email' => $_REQUEST['email'], 'mobile' => $_REQUEST['mobile_number'], 'message' => $_REQUEST['message'], 'type' => $_REQUEST['type']);
		$message = $this->load->view('template/contact-mail', $data, true);

		$superAdmin = $this->common->getData('superAdmin', ['admin_type' => '2'], ['single']);
		$toMail = $superAdmin['email'];
		$mail = $this->sendMail($toMail, 'Contact Us', $message);
		if (!$mail) {
			$this->response(false, "Mail Not delivered");
		} else {
			$this->response(true, "Mail sent successfully.");
		}
	}


	public function help()
	{
		$data = array('name' => $_REQUEST['name'], 'type' => $_REQUEST['type'], 'message' => $_REQUEST['message']);
		$message = $this->load->view('template/help-mail', $data, true);

		$superAdmin = $this->common->getData('superAdmin', ['admin_type' => '2'], ['single']);
		$toMail = $superAdmin['email'];
		$mail = $this->sendMail($toMail, 'Help', $message);
		if (!$mail) {
			$this->response(false, "Mail Not delivered");
		} else {
			$this->response(true, "Mail sent successfully.");
		}
	}


	function savingAvgCal($group_cycle_id)
	{
		$cycleTransfer = $this->common->getData('cycle_status_management', array('user_id' => $_POST['user_id'], 'group_id' => $_POST['group_id'], 'group_cycle_id' => $group_cycle_id), array('single'));

		if (empty($cycleTransfer)) {
			$where = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $group_cycle_id . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
			$result = $this->common->getData('user_group_lifecycle', $where, array("field" => 'sum(amount) as total_payment', "single"));

			if (!empty($result['total_payment'])) {
				$avgAmount = $result['total_payment'];
			} else {
				$avgAmount = 0.00;
			}

			return $avgAmount;
		} else {
			$paidWhere = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $group_cycle_id . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";

			$paidResult = $this->common->getData('user_group_lifecycle', $paidWhere, array("field" => 'sum(amount) as total_payment', "single"));

			if (!empty($paidResult['total_payment'])) {
				$paidAvgAmount = $paidResult['total_payment'];
			} else {
				$paidAvgAmount = 0;
			}



			$result = $this->common->getData('user_group_lifecycle', array('groupLifecycle_id' => $group_cycle_id, 'user_id' => $_REQUEST['user_id']), array('field' => 'SUM(amount) as total_amount', 'single'));

			$payout_amount_total = $result['total_amount'];


			return $avgAmount =  $paidAvgAmount - $payout_amount_total;
		}
	}


	// public function avgSaving()
	// {
	// 	$grouplifecycle = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id'], "group_type_id" => '1'), array('sort_by' => 'id', 'sort_direction' => 'desc'));



	// 	// 		$totalAvgAmount = 0;

	// 	$avgAmount = 0;

	// 	// 		$_REQUEST['group_cycle_id'] = 144; //comment code 02022024
	// 	$_REQUEST['group_cycle_id'] = $grouplifecycle[0]['id'];

	// 	$cycleTransfer = $this->common->getData('cycle_status_management', array('user_id' => $_REQUEST['user_id'], 'group_id' => $_REQUEST['group_id'], 'group_cycle_id' => $_REQUEST['group_cycle_id']), array('single'));

	// 	if (empty($cycleTransfer)) {
	// 		$where = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
	// 		$result = $this->common->getData('user_group_lifecycle', $where, array("field" => 'sum(amount) as total_payment', "single"));

	// 		if (!empty($result['total_payment'])) {
	// 			$avgAmount = $result['total_payment'];
	// 		} else {
	// 			$avgAmount = 0.00;
	// 		}
	// 	} else {
	// 		$paidWhere = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
	// 		$paidResult = $this->common->getData('user_group_lifecycle', $paidWhere, array("field" => 'sum(amount) as total_payment', "single"));



	// 		if (!empty($paidResult['total_payment'])) {
	// 			$paidAvgAmount = $paidResult['total_payment'];
	// 		} else {
	// 			$paidAvgAmount = 0;
	// 		}


	// 		$result = $this->common->getData('user_group_lifecycle', array('groupLifecycle_id' => $_REQUEST['group_cycle_id'], 'user_id' => $_REQUEST['user_id']), array('field' => 'SUM(amount) as total_amount', 'single'));

	// 		$payout_amount_total = $result['total_amount'];

	// 		//print_r($payout_amount_total);
	// 		$avgAmount = $paidAvgAmount - $payout_amount_total;
	// 	}

	// 	$this->response(true, 'amount fetch successfully', array('avgAmountCycle' => $avgAmount));

	// 	// 		if(!empty($avgAmount)) {
	// 	// 			foreach ($groupCycleInfo as $key => $value) {
	// 	// 				$totalAvgAmount+= $this->savingAvgCal($value['id']);
	// 	// 				//print_r($totalAvgAmount);
	// 	// 			}

	// 	// 			$this->response(true,'amount fetch successfully', array('avgAmountCycle'=> $totalAvgAmount));

	// 	// 		} else {
	// 	// 			$this->response(true,'amount fetch successfully', array('avgAmountCycle'=> $totalAvgAmount));
	// 	// 		}




	// }

	// changes by @krishn on 23-04-25
	// public function avgSaving()
	// {
	// 	$grouplifecycle = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id'], "group_type_id" => '1'), array('sort_by' => 'id', 'sort_direction' => 'desc'));

	// 	$avgAmount = 0;

	// 	$_REQUEST['group_cycle_id'] = $grouplifecycle[0]['id'];

	// 	$cycleTransfer = $this->common->getData('cycle_status_management', array(
	// 		'user_id' => $_REQUEST['user_id'],
	// 		'group_id' => $_REQUEST['group_id'],
	// 		'group_cycle_id' => $_REQUEST['group_cycle_id']
	// 	), array('single'));

	// 	if (empty($cycleTransfer)) {
	// 		$where = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
	// 		$result = $this->common->getData('user_group_lifecycle', $where, array("field" => 'sum(amount) as total_payment', "single"));

	// 		$avgAmount = !empty($result['total_payment']) ? $result['total_payment'] : 0.00;
	// 	} else {
	// 		$paidWhere = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
	// 		$paidResult = $this->common->getData('user_group_lifecycle', $paidWhere, array("field" => 'sum(amount) as total_payment', "single"));

	// 		$paidAvgAmount = !empty($paidResult['total_payment']) ? $paidResult['total_payment'] : 0;

	// 		$result = $this->common->getData('user_group_lifecycle', array(
	// 			'groupLifecycle_id' => $_REQUEST['group_cycle_id'],
	// 			'user_id' => $_REQUEST['user_id']
	// 		), array('field' => 'SUM(amount) as total_amount', 'single'));

	// 		$payout_amount_total = $result['total_amount'];
	// 		$avgAmount = $paidAvgAmount - $payout_amount_total;
	// 	}

	// 	// Fetch last 6 transactions for the user in this group lifecycle
	// 	$lastTransactionsDesc = $this->common->getData('user_group_lifecycle', array(
	// 		'group_id' => $_REQUEST['group_id'],
	// 		'groupLifecycle_id' => $_REQUEST['group_cycle_id'],
	// 		'user_id' => $_REQUEST['user_id']
	// 	), array(
	// 		'sort_by' => 'updated_at',
	// 		'sort_direction' => 'desc',
	// 		'limit' => 6
	// 	));

	// 	// Optional: Reverse to maintain chronological order
	// 	$lastTransactions = array_reverse($lastTransactionsDesc);

	// 	$this->response(true, 'Amount fetched successfully', array(
	// 		'avgAmountCycle' => $avgAmount,
	// 		'lastSixTransactions' => $lastTransactions
	// 	));
	// }

	// changes by @krishn on 10-07-26
	public function avgSaving()
	{
		$result2 = $this->common->getData('group_lifecycle', array(
			"group_id" => $_REQUEST['group_id'],
			"group_type_id" => '1'
		), array(
			'sort_by' => 'id',
			'sort_direction' => 'desc'
		));

		$avgJnrAmount = 0;
		$lastSixTransactions = [];

		if (!empty($result2)) {
			foreach ($result2 as $key => $value) {
				$avgJnrAmount += $this->savingAvgCal($value['id']);
			}

			// Use the latest groupLifecycle ID to fetch recent transactions
			$latestGroupLifecycleId = $result2[0]['id'];

			$transactionsDesc = $this->common->getData('user_group_lifecycle', array(
				'group_id' => $_REQUEST['group_id'],
				'groupLifecycle_id' => $latestGroupLifecycleId,
				'user_id' => $_REQUEST['user_id']
			), array(
				'sort_by' => 'updated_at',
				'sort_direction' => 'desc',
				'limit' => 6
			));

			// Reverse to show in chronological (ascending) order
			$lastSixTransactions = array_reverse($transactionsDesc);
		}

		$this->response(true, 'Amount fetched successfully', array(
			'avgAmountCycle' => $avgJnrAmount,
			'lastSixTransactions' => $lastSixTransactions
		));
	}

	// public function avgSavingJNR()
	// {

	// 	$result2 = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id'], "group_type_id" => '2'), array('sort_by' => 'id', 'sort_direction' => 'desc'));

	// 	$avgAmount = 0;
	// 	if (!empty($result2)) {
	// 		foreach ($result2 as $key => $value) {
	// 			$avgAmount += $this->savingAvgCal($value['id']);
	// 		}
	// 	} else {
	// 		$avgAmount = 0;
	// 	}


	// 	// $_REQUEST['group_cycle_id'] = $grouplifecycle[0]['id'];
	// 	// $_POST['group_cycle_id'] = $grouplifecycle[0]['id'];
	// 	// $cycleTransfer = $this->common->getData('cycle_status_management', array('user_id' => $_POST['user_id'], 'group_id' => $_POST['group_id'], 'group_cycle_id' => $_POST['group_cycle_id']), array('single'));

	// 	// if (empty($cycleTransfer)) {
	// 	// 	$where = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
	// 	// 	$result = $this->common->getData('user_group_lifecycle', $where, array("field" => 'sum(amount) as total_payment', "single"));

	// 	// 	if (!empty($result['total_payment'])) {
	// 	// 		$avgAmount = $result['total_payment'];
	// 	// 	} else {
	// 	// 		$avgAmount = 0.00;
	// 	// 	}
	// 	// } else {
	// 	// 	$paidWhere = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
	// 	// 	$paidResult = $this->common->getData('user_group_lifecycle', $paidWhere, array("field" => 'sum(amount) as total_payment', "single"));

	// 	// 	if (!empty($paidResult['total_payment'])) {
	// 	// 		$paidAvgAmount = $paidResult['total_payment'];
	// 	// 	} else {
	// 	// 		$paidAvgAmount = 0;
	// 	// 	}


	// 	// 	$result = $this->common->getData('user_group_lifecycle', array('groupLifecycle_id' => $_REQUEST['group_cycle_id'], 'user_id' => $_REQUEST['user_id']), array('field' => 'SUM(amount) as total_amount', 'single'));

	// 	// 	$payout_amount_total = $result['total_amount'];
	// 	// 	$avgAmount =  $payout_amount_total -  $paidAvgAmount;
	// 	// }


	// 	// 	$groupCycleInfo = $this->common->getData('group_lifecycle',array('group_id'=>$_REQUEST['group_id'], 'group_type_id'=>'2'));
	// 	// 		$avgAmount = 0;
	// 	// 		if(!empty($groupCycleInfo)) {
	// 	// 			foreach ($groupCycleInfo as $key => $value) {

	// 	//     			$paidWhere = "group_id = '".$_REQUEST['group_id']."' AND groupLifecycle_id = '".$value['id']."' AND user_id = '". $_REQUEST['user_id'] ."' AND status !='1'";
	// 	//     			$paidResult = $this->common->getData('user_group_lifecycle',$paidWhere,array("field" => 'sum(amount) as total_payment',"single"));

	// 	//     			if(!empty($paidResult['total_payment'])) {
	// 	//     				$avgAmount += $paidResult['total_payment'];
	// 	//     			}
	// 	// 			}
	// 	// 		} 

	// 	$this->response(true, 'amount fetch successfully', array('avgAmountCycle' => $avgAmount));
	// }

	// changed by @krishn on 23-04-25
	public function avgSavingJNR()
	{
		$result2 = $this->common->getData('group_lifecycle', array(
			"group_id" => $_REQUEST['group_id'],
			"group_type_id" => '2'
		), array(
			'sort_by' => 'id',
			'sort_direction' => 'desc'
		));

		$avgAmount = 0;
		$lastSixTransactions = [];

		if (!empty($result2)) {
			foreach ($result2 as $key => $value) {
				$avgAmount += $this->savingAvgCal($value['id']);
			}

			// Use the latest groupLifecycle ID to fetch recent transactions
			$latestGroupLifecycleId = $result2[0]['id'];

			$transactionsDesc = $this->common->getData('user_group_lifecycle', array(
				'group_id' => $_REQUEST['group_id'],
				'groupLifecycle_id' => $latestGroupLifecycleId,
				'user_id' => $_REQUEST['user_id']
			), array(
				'sort_by' => 'updated_at',
				'sort_direction' => 'desc',
				'limit' => 6
			));

			// Reverse to show in chronological (ascending) order
			$lastSixTransactions = array_reverse($transactionsDesc);
		}

		$this->response(true, 'Amount fetched successfully', array(
			'avgAmountCycle' => $avgAmount,
			'lastSixTransactions' => $lastSixTransactions
		));
	}

	// changed by @krishn on 24-04-25(current method)
	public function avgSavingHelpToBuy()
	{
		$loanTypes = [2, 3, 4, 5, 6];
		$totalAvgAmount = 0;

		foreach ($loanTypes as $loanType) {
			$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type = '{$loanType}'";
			$resultActive = $this->user_model->loan_detail($whereActive, array());

			$totalPaidAmount = 0;
			$totalActivePayment = 0;

			if (!empty($resultActive)) {
				foreach ($resultActive as $key => $value) {
					$paid = (float) $value['paid_amount'];
					$total = (float) $value['total_payment'];
					$totalPaidAmount += $paid;
					$totalActivePayment += $total;

					// Optional enhancements (you can remove if unused)
					$resultActive[$key]['payment_list'] = [];
					$resultActive[$key]['payment_list_status'] = true;
					$resultActive[$key]['paid_amount'] = $paid;
					$resultActive[$key]['total_payment'] = $total;
				}
			}

			$avg = $totalActivePayment - $totalPaidAmount;
			$totalAvgAmount += $avg;
		}

		// Last 6 transactions where status = 4 and loan_type in (2,3,4,5,6)
		$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type IN (2,3,4,5,6)";
		$lastSixDesc = $this->user_model->loan_detail($whereActive, array('sort_by' => 'L.id', 'sort_direction' => 'desc'), 6);

		// Reverse to chronological order (oldest first among last 6)
		$lastSixTransactions = array_reverse($lastSixDesc);

		$this->response(true, 'Amount fetched successfully', array(
			'avgAmountCycle' => '-' . $totalAvgAmount,
			'lastSixTransactions' => $lastSixTransactions
		));
	}

	// changed by @krishn on 24-04-25(old method)
	// public function avgSavingHelpToBuy()
	// {
	// 	$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '3'";
	// 	$resultActive = $this->user_model->loan_detail($whereActive, array());
	// 	$totalPaidAmount = 0;
	// 	$totalActivePayment = 0;
	// 	$avgAmount = 0;
	// 	if (!empty($resultActive)) {
	// 		foreach ($resultActive as $key => $value) {
	// 			$resultActive[$key]['payment_list'] = array();
	// 			$resultActive[$key]['payment_list_status'] = true;
	// 			$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
	// 			$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
	// 			$totalPaidAmount += $resultActive[$key]['paid_amount'];
	// 			$totalActivePayment += $resultActive[$key]['total_payment'];
	// 		}
	// 		$avgAmount = $totalActivePayment - $totalPaidAmount;
	// 	}


	// 	// help_to_buy_carinsurance
	// 	$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '2'";
	// 	$resultActive = $this->user_model->loan_detail($whereActive, array());
	// 	$totalPaidAmount = 0;
	// 	$totalActivePayment = 0;
	// 	$avgAmount1 = 0;
	// 	if (!empty($resultActive)) {
	// 		foreach ($resultActive as $key => $value) {
	// 			$resultActive[$key]['payment_list'] = array();
	// 			$resultActive[$key]['payment_list_status'] = true;
	// 			$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
	// 			$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
	// 			$totalPaidAmount += $resultActive[$key]['paid_amount'];
	// 			$totalActivePayment += $resultActive[$key]['total_payment'];
	// 		}
	// 		$avgAmount1 = $totalActivePayment - $totalPaidAmount;
	// 	}



	// 	$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '4'";
	// 	$resultActive = $this->user_model->loan_detail($whereActive, array());
	// 	$totalPaidAmount = 0;
	// 	$totalActivePayment = 0;
	// 	$avgAmount2 = 0;
	// 	if (!empty($resultActive)) {
	// 		foreach ($resultActive as $key => $value) {
	// 			$resultActive[$key]['payment_list'] = array();
	// 			$resultActive[$key]['payment_list_status'] = true;
	// 			$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
	// 			$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
	// 			$totalPaidAmount += $resultActive[$key]['paid_amount'];
	// 			$totalActivePayment += $resultActive[$key]['total_payment'];
	// 		}
	// 		$avgAmount2 = $totalActivePayment - $totalPaidAmount;
	// 	}



	// 	$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '6'";
	// 	$resultActive = $this->user_model->loan_detail($whereActive, array());
	// 	$totalPaidAmount = 0;
	// 	$totalActivePayment = 0;
	// 	$avgAmount3 = 0;
	// 	if (!empty($resultActive)) {
	// 		foreach ($resultActive as $key => $value) {
	// 			$resultActive[$key]['payment_list'] = array();
	// 			$resultActive[$key]['payment_list_status'] = true;
	// 			$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
	// 			$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
	// 			$totalPaidAmount += $resultActive[$key]['paid_amount'];
	// 			$totalActivePayment += $resultActive[$key]['total_payment'];
	// 		}
	// 		$avgAmount3 = $totalActivePayment - $totalPaidAmount;
	// 	}



	// 	$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '5'";
	// 	$resultActive = $this->user_model->loan_detail($whereActive, array());
	// 	$totalPaidAmount = 0;
	// 	$totalActivePayment = 0;
	// 	$avgAmount4 = 0;
	// 	if (!empty($resultActive)) {
	// 		foreach ($resultActive as $key => $value) {
	// 			$resultActive[$key]['payment_list'] = array();
	// 			$resultActive[$key]['payment_list_status'] = true;
	// 			$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
	// 			$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
	// 			$totalPaidAmount += $resultActive[$key]['paid_amount'];
	// 			$totalActivePayment += $resultActive[$key]['total_payment'];
	// 		}
	// 		$avgAmount4 = $totalActivePayment - $totalPaidAmount;
	// 	}


	// 	$totalAvgAmount = $avgAmount + $avgAmount1 + $avgAmount2 + $avgAmount3  + $avgAmount4;

	// 	// Last 6 transactions where status = 4 and loan_type in 2,3,4,5,6
	// 	$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type IN (2,3,4,5,6)";
	// 	$lastSixTransactions = $this->user_model->loan_detail($whereActive, array(), 6);

	// 	$this->response(true, 'Amount fetched successfully', array(
	// 		'avgAmountCycle' => '-' . $totalAvgAmount,
	// 		'lastSixTransactions' => $lastSixTransactions
	// 	));

	// 	// $this->response(true, 'amount fetch successfully', array('avgAmountCycle' => '-' . $totalAvgAmount));
	// }

	// created by @krishn on 24-04-25
	public function avgSafeKeeping()
	{
		// Total credit amount (payment_type = 2)
		$totalSafeCreditAmount = $this->common->getData('safe_keeping', array(
			'group_id' => $_REQUEST['group_id'],
			'user_id' => $_REQUEST['user_id'],
			'pyment_type' => 2
		), array("field" => 'sum(amount) as safe_keeping_total_amount', "single"));

		// Total debit amount (payment_type = 1)
		$totalSafeDebitAmount = $this->common->getData('safe_keeping', array(
			'group_id' => $_REQUEST['group_id'],
			'user_id' => $_REQUEST['user_id'],
			'pyment_type' => 1
		), array("field" => 'sum(amount) as safe_keeping_total_amount', "single"));

		// Safe keeping balance
		$creditAmount = $totalSafeCreditAmount['safe_keeping_total_amount'] ?? 0;
		$debitAmount = $totalSafeDebitAmount['safe_keeping_total_amount'] ?? 0;
		$avgAmountSafeKeeping = $creditAmount - $debitAmount;

		// Last 6 transactions (credit or debit), newest first
		$transactionsDesc = $this->common->getData('safe_keeping', array(
			'group_id' => $_REQUEST['group_id'],
			'user_id' => $_REQUEST['user_id']
		), array(
			'sort_by' => 'id',
			'sort_direction' => 'desc',
			'limit' => 6
		));

		// Reverse to show in chronological order
		$lastSixTransactions = array_reverse($transactionsDesc);

		$this->response(true, 'Amount fetched successfully', array(
			'avgAmountSafeKeeping' => $avgAmountSafeKeeping,
			'lastSixTransactions' => $lastSixTransactions
		));
	}

	// created by @krishn on 24-04-25
	public function avgAmountPf()
	{
		// Total credit amount (payment_type = 2)
		$totalCreditpfAmount = $this->common->getData('pf_user', array(
			'group_id' => $_REQUEST['group_id'],
			'user_id' => $_REQUEST['user_id'],
			'payment_type' => '2'
		), array(
			"field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest',
			"single"
		));

		// Total debit amount (payment_type = 1)
		$totalDebitpfAmount = $this->common->getData('pf_user', array(
			'group_id' => $_REQUEST['group_id'],
			'user_id' => $_REQUEST['user_id'],
			'payment_type' => '1'
		), array(
			"field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest',
			"single"
		));

		// Calculate PF balance
		$avgAmountPf = $totalCreditpfAmount['pf_total_amount'] - $totalDebitpfAmount['pf_total_amount'];

		// Get last 6 transactions for this user and group
		$lastSixTransactions = $this->common->getData('pf_user', array(
			'group_id' => $_REQUEST['group_id'],
			'user_id' => $_REQUEST['user_id']
		), array(
			'sort_by' => 'id',
			'sort_direction' => 'desc',
			'limit' => 6
		));

		// Return response
		$this->response(true, 'Amount fetched successfully', array(
			'avgAmountPf' => $avgAmountPf,
			'lastSixTransactions' => $lastSixTransactions
		));
	}

	// created by @krishn on 24-04-25
	public function totalAmountInvestment()
	{
		$where = "I.user_id = '" . $_REQUEST['user_id'] . "' AND I.group_id = '" . $_REQUEST['group_id'] . "' AND I.payment_status = '2'";
		$investmentTotal = $this->common->getData('investment as I', $where, array("field" => 'sum(I.amount) as total_payment', "single"));

		if (!empty($investmentTotal['total_payment'])) {
			$totalAmountInvestment = $investmentTotal['total_payment'];
		} else {
			$totalAmountInvestment = 0;
		}

		// Get last 6 transactions for this user and group
		$lastSixTransactions = $this->common->getData('investment', array(
			'group_id' => $_REQUEST['group_id'],
			'user_id' => $_REQUEST['user_id'],
			'payment_status' => '2',
		), array(
			'sort_by' => 'id',
			'sort_direction' => 'desc',
			'limit' => 6
		));

		// Return response
		$this->response(true, 'Amount fetched successfully', array(
			'totalAmountInvestment' => $totalAmountInvestment,
			'lastSixTransactions' => $lastSixTransactions
		));
	}

	// created by @krishn on 24-04-25
	public function totalAmountDivided()
	{
		$whereDivided = "I.user_id = '" . $_REQUEST['user_id'] . "' AND I.group_id = '" . $_REQUEST['group_id'] . "' AND I.payment_status = '1'";
		$dividedTotal = $this->common->getData('investment as I', $whereDivided, array("field" => 'sum(I.amount) as total_payment', "single"));

		if (!empty($dividedTotal['total_payment'])) {
			$totalAmountDivided = $dividedTotal['total_payment'];
		} else {
			$totalAmountDivided = 0;
		}

		// Get last 6 transactions for this user and group
		$lastSixTransactions = $this->common->getData('investment', array(
			'group_id' => $_REQUEST['group_id'],
			'user_id' => $_REQUEST['user_id'],
			'payment_status' => '1',
		), array(
			'sort_by' => 'id',
			'sort_direction' => 'desc',
			'limit' => 6
		));

		// Return response
		$this->response(true, 'Amount fetched successfully', array(
			'totalAmountDivided' => $totalAmountDivided,
			'lastSixTransactions' => $lastSixTransactions
		));
	}

	// created by @krishn on 24-04-25
	public function avgAmountLoan()
	{
		$userId = $_REQUEST['user_id'];
		$groupId = $_REQUEST['group_id'];

		$whereActive = "L.user_id = '{$userId}' AND L.group_id = '{$groupId}' AND L.status = 4 AND L.loan_type = '1'";
		$resultActive = $this->user_model->loan_detail($whereActive, array());

		$totalPaidAmount = 0;
		$totalActivePayment = 0;

		if (!empty($resultActive)) {
			foreach ($resultActive as $value) {
				$paidAmount = (float) $value['paid_amount'];
				$totalPayment = (float) $value['total_payment'];

				$totalPaidAmount += $paidAmount;
				$totalActivePayment += $totalPayment;
			}
		}

		$avgAmountLoan = $totalActivePayment - $totalPaidAmount;

		// Fetch last 6 transactions sorted by latest first
		$lastSixDesc = $this->user_model->loan_detail(
			$whereActive,
			['sort_by' => 'L.id', 'sort_direction' => 'desc'],
			6
		);

		// Reverse for chronological order (optional)
		$lastSixTransactions = array_reverse($lastSixDesc);

		// Return response
		$this->response(true, 'Amount fetched successfully', [
			'avgAmountLoan' => '-' . $avgAmountLoan,
			'lastSixTransactions' => $lastSixTransactions
		]);
	}


	// created by @krishn on 24-04-25
	public function avgAmountEmergencyLoan()
	{
		if (empty($_REQUEST['user_id']) || empty($_REQUEST['group_id'])) {
			return $this->response(false, 'Missing Parameter.', [
				'avgAmountEmergencyLoan' => '0',
				'lastSixTransactions' => []
			]);
		}

		$userId = $_REQUEST['user_id'];
		$groupId = $_REQUEST['group_id'];

		// Get total emergency loan amount with status = 4
		$emergencyLoanSummary = $this->common->getData('user_emergency_loan', [
			'group_id' => $groupId,
			'user_id' => $userId,
			'status' => '4'
		], [
			'field' => 'SUM(loan_amount) as total_payment',
			'single'
		]);

		$avgAmountEmergencyLoan = (float) ($emergencyLoanSummary['total_payment'] ?? 0);

		// Get last 6 emergency loan transactions with status = 4
		$where = "group_id = '{$groupId}' AND user_id = '{$userId}' AND status = 4";
		$lastSixDesc = $this->common->getData('user_emergency_loan', $where, [
			'sort_by' => 'id',
			'sort_direction' => 'desc',
			'limit' => 6
		]);

		// Optional: reverse to chronological order
		$lastSixTransactions = !empty($lastSixDesc) ? array_reverse($lastSixDesc) : [];

		// Return response
		$this->response(true, 'Amount fetched successfully', [
			'avgAmountEmergencyLoan' => '-' . $avgAmountEmergencyLoan,
			'lastSixTransactions' => $lastSixTransactions
		]);
	}

	// created by @krishn on 24-04-25
	public function avgWelfareAmount()
	{
		$groupId = $_REQUEST['group_id'];
		$userId = $_REQUEST['user_id'];
		$avgWelfareAmount = 0;

		// Fetch the latest group lifecycle entry for group_type_id = 4
		$groupLifecycle = $this->common->getData('group_lifecycle', [
			'group_id' => $groupId,
			'group_type_id' => '4'
		], [
			'sort_by' => 'id',
			'sort_direction' => 'desc'
		]);

		if (empty($groupLifecycle)) {
			return $this->response(false, 'No group lifecycle found.');
		}

		$groupCycleId = $groupLifecycle[0]['id'];

		// Check if there's a cycle transfer
		$cycleTransfer = $this->common->getData('cycle_status_management', [
			'user_id' => $userId,
			'group_id' => $groupId,
			'group_cycle_id' => $groupCycleId
		], ['single' => true]);

		if (empty($cycleTransfer)) {
			// If no cycle transfer, calculate direct total payment (excluding status 1)
			$result = $this->common->getData(
				'user_group_lifecycle',
				"group_id = '{$groupId}' AND groupLifecycle_id = '{$groupCycleId}' AND user_id = '{$userId}' AND status != '1'",
				['field' => 'SUM(amount) as total_payment', 'single' => true]
			);

			$avgWelfareAmount = (float) ($result['total_payment'] ?? 0.00);
		} else {
			// If cycle transfer exists, calculate the adjusted welfare amount
			$paidResult = $this->common->getData(
				'user_group_lifecycle',
				"group_id = '{$groupId}' AND groupLifecycle_id = '{$groupCycleId}' AND user_id = '{$userId}' AND status != '1'",
				['field' => 'SUM(amount) as total_payment', 'single' => true]
			);
			$paidAmount = (float) ($paidResult['total_payment'] ?? 0);

			$totalAmountResult = $this->common->getData('user_group_lifecycle', [
				'groupLifecycle_id' => $groupCycleId,
				'user_id' => $userId
			], ['field' => 'SUM(amount) as total_amount', 'single' => true]);
			$payoutAmount = (float) ($totalAmountResult['total_amount'] ?? 0);

			$avgWelfareAmount = $paidAmount - $payoutAmount;
		}

		// Fetch last 6 welfare transactions excluding status = 1
		$transactionWhere = "group_id = '{$groupId}' AND groupLifecycle_id = '{$groupCycleId}' AND user_id = '{$userId}' AND status != '1'";
		$lastSixDesc = $this->common->getData('user_group_lifecycle', $transactionWhere, [
			'sort_by' => 'updated_at',
			'sort_direction' => 'desc',
			'limit' => 6
		]);

		// Optional: reverse for chronological order
		$lastSixTransactions = array_reverse($lastSixDesc);

		// Return response
		$this->response(true, 'Amount fetched successfully', [
			'avgwelfareAmount' => '-' . $avgWelfareAmount,
			'lastSixTransactions' => $lastSixTransactions
		]);
	}

	// created by @krishn on 24-04-25
	public function avgMiscellaneous()
	{
		$userId = $_REQUEST['user_id'];
		$groupId = $_REQUEST['group_id'];
		$avgMiscellaneous = 0;

		// Build the where clause
		// $where = "L.user_id = '{$userId}' AND L.group_id = '{$groupId}' AND L.status = 4";
		$where = "L.user_id = '{$userId}' AND L.group_id = '{$groupId}'";

		// Fetch all active miscellaneous loans with status = 4
		$resultActive = $this->user_model->miscellaneous_detail_new($where, []);

		$totalPaidAmount = 0;
		$totalActivePayment = 0;

		if (!empty($resultActive)) {
			foreach ($resultActive as &$value) {
				$paidAmount = (float) ($value['paid_amount'] ?? 0);
				$totalPayment = (float) ($value['total_payment'] ?? 0);

				$value['payment_list'] = []; // Possibly to be populated later
				$value['payment_list_status'] = true;
				$value['paid_amount'] = $paidAmount;
				$value['total_payment'] = $totalPayment;

				$totalPaidAmount += $paidAmount;
				$totalActivePayment += $totalPayment;
			}

			// Difference between paid and expected total
			$avgMiscellaneous = $totalPaidAmount - $totalActivePayment;
		}

		// Get last 6 miscellaneous transactions with status = 4
		$lastSixTransactions = $this->user_model->miscellaneous_detail_new($where, [], 6, 0);

		// Return response
		$this->response(true, 'Amount fetched successfully', [
			'avgMiscellaneous' => '-' . $avgMiscellaneous,
			'lastSixTransactions' => $lastSixTransactions
		]);
	}

	public function avgAmount()
	{
		$totalSafeCreditAmount = $this->common->getData('safe_keeping', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'pyment_type' => 2), array("field" => 'sum(amount) as safe_keeping_total_amount', "single"));


		$totalSafeDebitAmount = $this->common->getData('safe_keeping', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'pyment_type' => 1), array("field" => 'sum(amount) as safe_keeping_total_amount', "single"));


		$avgAmountSafeKeeping = $totalSafeCreditAmount['safe_keeping_total_amount'] - $totalSafeDebitAmount['safe_keeping_total_amount'];



		$whereCycle = "group_id = '" . $_REQUEST['group_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status != 1";
		$resultCycle = $this->common->getData('user_group_lifecycle', $whereCycle, array("field" => 'sum(amount) as total_payment', "single"));

		if (!empty($resultCycle['total_payment'])) {
			$avgAmountCycle = $resultCycle['total_payment'];
		} else {
			$avgAmountCycle = 0;
		}


		// new-changes 04-06-2024

		// $loanAvgPayment = $this->common->getData('user_loan',array('group_id' => $_REQUEST['group_id'],'user_id' => $_REQUEST['user_id'],'status' => '4'),array("field" => 'sum(total_payment) as total_payment',"single"));


		//      if($loanAvgPayment['total_payment']) {
		//      	$avgAmountLoan = $loanAvgPayment['total_payment'];
		//      } else {
		//      	$avgAmountLoan = 0;
		//      }




		// 		$wherePendingwel = "user_id = '". $_REQUEST['user_id'] ."' AND group_id = '". $_REQUEST['group_id'] ."'AND groupLifecycle_id = '147' AND status = '2'
		// 		    GROUP BY user_id,grand_total_amount,group_id,groupLifecycle_id
		// 		";

		// 		$resultwelTotal = $this->common->getData('user_group_lifecycle',$wherePendingwel,array("field" => 'user_id,group_id,groupLifecycle_id,grand_total_amount',""));



		// 		$wherewelamount = "group_id = '". $_REQUEST['group_id'] ."' AND user_id = '". $_REQUEST['user_id'] ."' AND loan_amount_status = 1";

		// 		$resultAmount = $this->common->getData('user_group_lifecycle',$wherewelamount,array("field" => 'sum(amount) as amount',""));



		//         $totalPaidAmount = 0;
		// 		$totalActivePayment = 0;
		// 		$avgwelfareAmount = 0;

		//         if(!empty($resultwelTotal)) {
		//         	foreach ($resultwelTotal as $key => $value) {

		//         		$resultwelTotal[$key]['payment_list'] = array();
		//         		$resultwelTotal[$key]['payment_list_status'] = true;

		//         		$resultwelTotal[$key]['grand_total_amount'] = (float) $value['grand_total_amount'];

		//         		$resultwelTotal[$key]['amount'] = (float) $resultAmount[$key]['amount'];

		//         		//$totalPaidAmount+=$resultwelTotal[$key]['grand_total_amount'];
		//         		$totalActivePayment+=$resultwelTotal[$key]['amount'];

		//         	}
		//         		$avgwelfareAmount = $totalActivePayment;

		//         }

		//  new-changes 04-06-2024

		$avgwelfareAmount = 0;
		$totalWelfarePaidAmount = 0;
		$welfareUserId = (int) $_REQUEST['user_id'];
		$welfareGroupWhere = !empty($_REQUEST['group_id']) ? " AND L.group_id = '" . (int) $_REQUEST['group_id'] . "'" : "";

		$whereComplete = "L.user_id = '" . $welfareUserId . "' {$welfareGroupWhere} AND L.loan_type = '7' AND L.status = 2";
		$resultComplete = $this->user_model->loan_detail($whereComplete, array());

		if (!empty($resultComplete)) {
			foreach ($resultComplete as $key => $value) {
				$totalWelfarePaidAmount += (float) ($value['paid_amount'] ?? 0);
			}
		}

		$whereActiveWelfare = "L.user_id = '" . $welfareUserId . "' {$welfareGroupWhere} AND L.loan_type = '7' AND L.status = 4";
		$resultActiveWelfare = $this->user_model->loan_detail($whereActiveWelfare, array());

		if (!empty($resultActiveWelfare)) {
			foreach ($resultActiveWelfare as $key => $value) {
				$totalWelfarePaidAmount += (float) ($value['paid_amount'] ?? 0);
			}
		}

		$claimWhere = array('user_id' => $welfareUserId, 'status' => 1);
		if (!empty($_REQUEST['group_id'])) {
			$claimWhere['group_id'] = (int) $_REQUEST['group_id'];
		}

		$approvedClaimsData = $this->common->getData(
			'welfare_claim',
			$claimWhere,
			array('field' => 'IFNULL(SUM(payout_amount), 0) as total_claimed', 'single')
		);

		$totalApprovedClaims = (float) ($approvedClaimsData['total_claimed'] ?? 0);
		$avgwelfareAmount = $totalWelfarePaidAmount - $totalApprovedClaims;


		$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '1'";
		$resultActive = $this->user_model->loan_detail($whereActive, array());
		$totalPaidAmount = 0;
		$totalActivePayment = 0;
		$avgAmountLoan = 0;
		if (!empty($resultActive)) {
			foreach ($resultActive as $key => $value) {
				$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
				$totalPaidAmount += $resultActive[$key]['paid_amount'];
				$totalActivePayment += $resultActive[$key]['total_payment'];
			}

			$avgAmountLoan = $totalActivePayment - $totalPaidAmount;
		}

		$totalCreditpfAmount = $this->common->getData('pf_user', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'payment_type' => '2'), array("field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest', "single"));



		$totalDebitpfAmount = $this->common->getData('pf_user', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'payment_type' => '1'), array("field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest', "single"));

		$avgAmountPf = $totalCreditpfAmount['pf_total_amount'] - $totalDebitpfAmount['pf_total_amount'];


		$emergencyloanAvgPayment = $this->common->getData('user_emergency_loan', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'status' => '4'), array("field" => 'sum(loan_amount) as total_payment', "single"));


		if ($emergencyloanAvgPayment['total_payment']) {
			$avgAmountEmergencyLoan = $emergencyloanAvgPayment['total_payment'];
		} else {
			$avgAmountEmergencyLoan = 0;
		}


		// $whereDivided = "I.user_id = '" . $_REQUEST['user_id'] . "' AND I.group_id = '" . $_REQUEST['group_id'] . "' AND I.payment_status = '1'";
		// $dividedTotal = $this->common->getData('investment as I', $whereDivided, array("field" => 'sum(I.amount) as total_payment', "single"));

		// if (!empty($dividedTotal['total_payment'])) {
		// 	$totalAmountDivided = $dividedTotal['total_payment'];
		// } else {
		// 	$totalAmountDivided = 0;
		// }

		$this->db->select('IFNULL(SUM(DU.balance_amount), 0) AS balance_amount', false);
		$this->db->from('dividend_user DU');
		$this->db->join('dividend D', 'D.id = DU.dividend_id', 'left');
		$this->db->where('DU.user_id', (int) $_REQUEST['user_id']);
		$this->db->where('DU.group_id', (int) $_REQUEST['group_id']);
		$this->db->where('D.status', 1);
		$dividedTotal = $this->db->get()->row_array();

		if (!empty($dividedTotal['balance_amount'])) {
			$totalAmountDivided = $dividedTotal['balance_amount'];
		} else {
			$totalAmountDivided = "0";
		}


		$where = "I.user_id = '" . $_REQUEST['user_id'] . "' AND I.group_id = '" . $_REQUEST['group_id'] . "' AND I.payment_status = '2'";
		$investmentTotal = $this->common->getData('investment as I', $where, array("field" => 'sum(I.amount) as total_payment', "single"));


		$creditResult = $this->common->getData('credit_score_user', array("user_id" => $_REQUEST['user_id']), array("single"));
		$total_credit_score = 0;
		if (!empty($creditResult)) {
			$total_credit_score = $creditResult['total_credit_score'];
		} else {
			$total_credit_score = 0;
		}

		if (!empty($investmentTotal['total_payment'])) {
			$totalAmountInvestment = $investmentTotal['total_payment'];
		} else {
			$totalAmountInvestment = 0;
		}

		$avgMiscellaneous = 0;
		$whereActive1 = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4";
		$resultActive1 = $this->user_model->miscellaneous_detail_new($whereActive1, array());
		$totalPaidAmount1 = 0;
		$totalActivePayment1 = 0;
		$avgAmount1 = 0;
		if (!empty($resultActive1)) {
			foreach ($resultActive1 as $key => $value) {
				$resultActive1[$key]['payment_list'] = array();
				$resultActive1[$key]['payment_list_status'] = true;
				$resultActive1[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultActive1[$key]['total_payment'] = (float) $value['total_payment'];
				$totalPaidAmount1 += $resultActive1[$key]['paid_amount'];
				$totalActivePayment1 += $resultActive1[$key]['total_payment'];
			}

			$avgMiscellaneous =   $totalPaidAmount1 - $totalActivePayment1;
		}

		$this->response(true, 'amount fetch successfully', array(
			'avgAmountSafeKeeping' => $avgAmountSafeKeeping,
			'avgAmountCycle' => $avgAmountCycle,
			'avgAmountLoan' => - ($avgAmountLoan),
			'avgAmountPf' => $avgAmountPf,
			'avgAmountEmergencyLoan' => '-' . $avgAmountEmergencyLoan,
			'totalAmountInvestment' => $totalAmountInvestment,
			'totalAmountDivided' => $totalAmountDivided,
			'total_credit_score' => $total_credit_score,
			'avgAmountMiscellaneous' => $avgMiscellaneous,
			'avgwelfare' => '' . $avgwelfareAmount
		));
	}

	///////////////////////////////////////////////
	// public function allBanners()
	// {

	// 	$result_banner = $this->common->getData('tbl_banners');
	// 	$data = array();
	// 	if (!empty($result_banner)) {
	// 		foreach ($result_banner as $key => $value) {
	// 			if (!empty($value['image'])) {
	// 				$value['image'] = base_url() . $value['image'];
	// 			} else {
	// 				$value['image'] = "";
	// 			}

	// 			$data[] = $value;
	// 		}
	// 		$this->response(true, "banners fetch Successfully.", array("banners" => $data));
	// 	} else {
	// 		$this->response(true, "banners fetch Successfully.", array("banners" => array()));
	// 	}
	// }

	// created by @krishn on 11-08-25
	public function allBanners()
	{
		$result_banner = $this->common->getData('tbl_banners', array('status' => 1));
		$data = [];

		if (!empty($result_banner)) {
			foreach ($result_banner as $banner) {
				if (!empty($banner['image'])) {
					$banner['image'] = base_url() . $banner['image'];
				} else {
					$banner['image'] = "";
				}
				$data[] = $banner;
			}
		}

		$this->response(true, "Banners fetched successfully.", ["banners" => $data]);
	}

	public function allCreditScoreList()
	{
		$credit_score_list = $this->common->getData('credit_score_list');
		if (!empty($credit_score_list)) {

			$this->response(true, "credit_score fetch Successfully.", array("credit_score_list" => $credit_score_list));
		} else {
			$this->response(true, "credit_score fetch Successfully.", array("credit_score_list" => array()));
		}
	}

	public function lastFiveTransactionsUserCycle()
	{

		$groupCycleList = $this->common->getData('user_group_lifecycle', array('group_id' => $_REQUEST['group_id'], 'user_id' =>  $_REQUEST['user_id']), array('sort_by' => 'id', 'sort_direction' => 'desc', 'limit' => '6', ''));

		if (!empty($groupCycleList)) {

			$this->response(true, "lastFiveTransactionsUserCycle fetch Successfully.", array("lastFiveTransactions" => $groupCycleList));
		} else {
			$this->response(true, "lastFiveTransactionsUserCycle fetch Successfully.", array("lastFiveTransactions" => array()));
		}
	}


	public function getPrivacyPolicyInfo()
	{
		$privacyInfo = $this->common->getData('privacy_policy', array('id' => '1'), array('single'));

		if ($privacyInfo) {
			$this->response(true, "fetch information successfully", array('privacyInfo' => $privacyInfo['info']));
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function getTermsInfo()
	{
		$termsInfo = $this->common->getData('terms', array('id' => '1'), array('single'));

		if ($termsInfo) {
			$this->response(true, "add information successfully", array('termsInfo' => $termsInfo['info']));
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function addPrivacyPolicy()
	{
		$result = $this->common->updateData('privacy_policy', array('info' => $_REQUEST['info']), array('id' => '1'));

		if ($result) {
			$this->response(true, "Update Information Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	public function welfareList()
	{
		$this->mergeJsonRequestData();

		if (empty($_REQUEST['user_id'])) {
			$this->response(false, "User ID is required.");
			return;
		}

		$userId = $_REQUEST['user_id'];
		$groupWhere = !empty($_REQUEST['group_id']) ? " AND L.group_id = '" . (int)$_REQUEST['group_id'] . "'" : "";

		// 1. Pending Welfare Requests (Status 1, 3,5)
		$wherePending = "L.user_id = '" . (int)$userId . "' {$groupWhere} AND L.loan_type = '7' AND L.status IN (1, 3, 5)";
		$resultPending = $this->user_model->loan_detail($wherePending, array());
		if (!empty($resultPending)) {
			foreach ($resultPending as $key => $value) {
				$payments = $this->common->getData('user_loan_payment', array('loan_id' => $value['id']), array('sort_by' => 'id', 'sort_direction' => 'asc'));
				$resultPending[$key]['payment_list'] = !empty($payments) ? $payments : array();
				$resultPending[$key]['payment_list_status'] = !empty($payments);
				$resultPending[$key]['paid_amount'] = (float) ($value['paid_amount'] ?? 0);
				$resultPending[$key]['total_payment'] = (float) ($value['total_payment'] ?? 0);
			}
		} else {
			$resultPending = array();
		}

		// 2. Completed Welfare Accounts (Status 2)
		$whereComplete = "L.user_id = '" . (int)$userId . "' {$groupWhere} AND L.loan_type = '7' AND L.status = 2";
		$resultComplete = $this->user_model->loan_detail($whereComplete, array());
		$totalPaidAmount = 0;
		if (!empty($resultComplete)) {
			foreach ($resultComplete as $key => $value) {
				$payments = $this->common->getData('user_loan_payment', array('loan_id' => $value['id']), array('sort_by' => 'id', 'sort_direction' => 'asc'));
				$resultComplete[$key]['payment_list'] = !empty($payments) ? $payments : array();
				$resultComplete[$key]['payment_list_status'] = !empty($payments);
				$resultComplete[$key]['paid_amount'] = (float) ($value['paid_amount'] ?? 0);
				$resultComplete[$key]['total_payment'] = (float) ($value['total_payment'] ?? 0);
				$totalPaidAmount += $resultComplete[$key]['paid_amount'];
			}
		} else {
			$resultComplete = array();
		}

		// 3. Active Welfare Accounts (Status 4)
		$whereActive = "L.user_id = '" . (int)$userId . "' {$groupWhere} AND L.loan_type = '7' AND L.status = 4";
		$resultActive = $this->user_model->loan_detail($whereActive, array());
		$totalActivePayment = 0;
		$avgAmount = 0;
		if (!empty($resultActive)) {
			foreach ($resultActive as $key => $value) {
				$payments = $this->common->getData('user_loan_payment', array('loan_id' => $value['id']), array('sort_by' => 'id', 'sort_direction' => 'asc'));
				$resultActive[$key]['payment_list'] = !empty($payments) ? $payments : array();
				$resultActive[$key]['payment_list_status'] = !empty($payments);
				$resultActive[$key]['paid_amount'] = (float) ($value['paid_amount'] ?? 0);
				$resultActive[$key]['total_payment'] = (float) ($value['total_payment'] ?? 0);
				$totalPaidAmount += $resultActive[$key]['paid_amount'];
				$totalActivePayment += $resultActive[$key]['total_payment'];
			}
			$avgAmount = $totalActivePayment - $totalPaidAmount;
		} else {
			$resultActive = array();
		}

		// Calculate total approved claims payout for the user
		$claimWhere = array('user_id' => (int)$userId, 'status' => 1);
		if (!empty($_REQUEST['group_id'])) {
			$claimWhere['group_id'] = (int)$_REQUEST['group_id'];
		}
		$approvedClaimsData = $this->common->getData('welfare_claim', $claimWhere, array('field' => 'IFNULL(SUM(payout_amount), 0) as total_claimed', 'single'));
		$totalApprovedClaims = (float) ($approvedClaimsData['total_claimed'] ?? 0);

		$avgComplete = $totalPaidAmount - $totalApprovedClaims;

		$this->response(true, "Welfare list fetched successfully.", array(
			"listPending"     => $resultPending,
			"listComplete"    => $resultComplete,
			"listActive"      => $resultActive,
			// "welfarePending"  => $resultPending,
			// "welfareComplete" => $resultComplete,
			// "welfareActive"   => $resultActive,
			"avgAmount"       => $avgAmount,
			"avgComplete"     => $avgComplete
		));
	}

	public function help2buylist()
	{

		// help_to_buycar
		$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '3'";
		$resultActive = $this->user_model->loan_detail($whereActive, array());
		$totalPaidAmount = 0;
		$totalActivePayment = 0;
		$avgAmount = 0;
		if (!empty($resultActive)) {
			foreach ($resultActive as $key => $value) {
				$resultActive[$key]['payment_list'] = array();
				$resultActive[$key]['payment_list_status'] = true;
				$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
				$totalPaidAmount += $resultActive[$key]['paid_amount'];
				$totalActivePayment += $resultActive[$key]['total_payment'];
			}
			$avgAmount = $totalActivePayment - $totalPaidAmount;
		}


		// help_to_buy_carinsurance
		$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '2'";
		$resultActive = $this->user_model->loan_detail($whereActive, array());
		$totalPaidAmount = 0;
		$totalActivePayment = 0;
		$avgAmount1 = 0;
		if (!empty($resultActive)) {
			foreach ($resultActive as $key => $value) {
				$resultActive[$key]['payment_list'] = array();
				$resultActive[$key]['payment_list_status'] = true;
				$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
				$totalPaidAmount += $resultActive[$key]['paid_amount'];
				$totalActivePayment += $resultActive[$key]['total_payment'];
			}
			$avgAmount1 = $totalActivePayment - $totalPaidAmount;
		}



		$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '4'";
		$resultActive = $this->user_model->loan_detail($whereActive, array());
		$totalPaidAmount = 0;
		$totalActivePayment = 0;
		$avgAmount2 = 0;
		if (!empty($resultActive)) {
			foreach ($resultActive as $key => $value) {
				$resultActive[$key]['payment_list'] = array();
				$resultActive[$key]['payment_list_status'] = true;
				$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
				$totalPaidAmount += $resultActive[$key]['paid_amount'];
				$totalActivePayment += $resultActive[$key]['total_payment'];
			}
			$avgAmount2 = $totalActivePayment - $totalPaidAmount;
		}



		$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '6'";
		$resultActive = $this->user_model->loan_detail($whereActive, array());
		$totalPaidAmount = 0;
		$totalActivePayment = 0;
		$avgAmount3 = 0;
		if (!empty($resultActive)) {
			foreach ($resultActive as $key => $value) {
				$resultActive[$key]['payment_list'] = array();
				$resultActive[$key]['payment_list_status'] = true;
				$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
				$totalPaidAmount += $resultActive[$key]['paid_amount'];
				$totalActivePayment += $resultActive[$key]['total_payment'];
			}
			$avgAmount3 = $totalActivePayment - $totalPaidAmount;
		}



		$whereActive = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '5'";
		$resultActive = $this->user_model->loan_detail($whereActive, array());
		$totalPaidAmount = 0;
		$totalActivePayment = 0;
		$avgAmount4 = 0;
		if (!empty($resultActive)) {
			foreach ($resultActive as $key => $value) {
				$resultActive[$key]['payment_list'] = array();
				$resultActive[$key]['payment_list_status'] = true;
				$resultActive[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultActive[$key]['total_payment'] = (float) $value['total_payment'];
				$totalPaidAmount += $resultActive[$key]['paid_amount'];
				$totalActivePayment += $resultActive[$key]['total_payment'];
			}
			$avgAmount4 = $totalActivePayment - $totalPaidAmount;
		}



		//$avgAmount
		$this->response(true, "list fetch Successfully.", array(
			"help_to_buycar" => $avgAmount,
			"help_to_buy_carinsurance" => $avgAmount1,
			"help_to_buy_other" => $avgAmount4,
			"help_to_buy_property" => $avgAmount3,
			"help_to_buy_credit_card" => $avgAmount2
		));
	}

	// created by @krishn on 01-May-25
	public function recommendUserProcess()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		// Check for duplicate email
		$email = $this->common->getData('recommend_user', array('email' => $_REQUEST['email']), array('single'));
		if (!empty($email)) {
			$this->response(false, "Email already exists");
			return;
		}

		// Insert recommendation
		$post = $this->common->getField('recommend_user', $_REQUEST);
		$result = $this->common->insertData('recommend_user', $post);

		if ($result) {
			$insert_id = $this->db->insert_id();

			$notificationMessage = "Recommend a user";
			$this->send_nofificationAdmin($_REQUEST['user_id'], $_REQUEST['group_id'], $notificationMessage, $insert_id, "8");

			// Trigger first level approval: second recommender
			$this->createApproval($insert_id, $_REQUEST['refer_user_id'], 'second_recommender');

			$this->response(true, "Recommendation submitted, awaiting second recommender approval");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	// created by @krishn on 01-May-25
	private function createApproval($recommend_id, $approver_id, $role)
	{
		$this->load->helper('string');
		$token = random_string('alnum', 32);

		// Insert new approval entry
		$approvalData = [
			'recommend_id'    => $recommend_id,
			'approver_id'     => $approver_id,
			'approver_role'   => $role,
			'status'          => 0,
			'token'           => $token,
		];
		$this->common->insertData('recommendation_approvals', $approvalData);

		// Fetch necessary user data
		$recommendedUser = $this->common->getData('recommend_user', ['id' => $recommend_id], ['single']);
		$recommender     = $this->common->getData('user', ['user_id' => $recommendedUser['user_id']], ['single']);
		$secondRecommender = $this->common->getData('user', ['user_id' => $recommendedUser['refer_user_id']], ['single']);

		$approver = ($role === 'admin')
			? $this->common->getData('superAdmin', ['id' => $approver_id], ['single'])
			: $this->common->getData('user', ['user_id' => $approver_id], ['single']);

		if (!$approver) return;

		$token = urlencode($token);
		$linkApprove = API_BASE_URL . "handleApproval/{$token}/1";
		$linkDecline = API_BASE_URL . "handleDecline/{$token}/2";

		$subject = "Approval Request for Recommendation";

		// Name for sender
		$data['sendername'] = ($role === 'admin')
			? ($approver['name'] ?? '')
			: trim(($approver['first_name'] ?? '') . ' ' . ($approver['last_name'] ?? ''));

		$recommendedName     = trim($recommendedUser['first_name'] . ' ' . $recommendedUser['last_name']);
		$recommenderName     = trim($recommender['first_name'] . ' ' . $recommender['last_name']);
		$secondRecommenderName = trim($secondRecommender['first_name'] . ' ' . $secondRecommender['last_name']);

		switch ($recommendedUser['employement_type']) {
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
				break;
		}

		// Common details list
		$_mobileCountryCode = !empty($recommendedUser['country_code']) ? $recommendedUser['country_code'] . ' ' : '';
		$_fullMobile = $_mobileCountryCode . $recommendedUser['mobile_number'];
		$memberDetails = "
			<ol>
				<li><strong>Name of proposed member:</strong> {$recommendedUser['first_name']}</li>
				<li><strong>Telephone number:</strong> {$_fullMobile}</li>
				<li><strong>Email:</strong> {$recommendedUser['email']}</li>
				<li><strong>Employment status:</strong> {$employmentStatus}</li>
			</ol>
		";

		// Define role-specific message
		switch ($role) {
			case 'second_recommender':
				$data['message'] = "
					<p><strong>{$recommenderName}</strong> recommends <strong>{$recommendedName}</strong>. Please find below the pertinent details of the recommended member:</p>
					{$memberDetails}
					<p>Thank you for considering this recommendation</p>
					<p>
						<a style='background-color:#1bbe83; text-decoration:none; color:#fff;padding:10px 20px;border-radius:10px' href='{$linkApprove}'>Approve</a>
						<a style='background-color:#ff0000; text-decoration:none; color:#fff;padding:10px 20px;border-radius:10px' href='{$linkDecline}'>Decline</a>
					</p>
				";
				break;

			case 'circle_lead':
			case 'deputy_circle_lead':
				$data['message'] = "
					<p><strong>{$recommenderName}</strong> and <strong>{$secondRecommenderName}</strong> are recommending <strong>{$recommendedName}</strong> to join Interfriends.</p>
					<p>Please find below the pertinent details of the recommended member:</p>
					{$memberDetails}
					<p>Thank you for considering this recommendation.</p>
					<p>
						<a style='background-color:#1bbe83; text-decoration:none; color:#fff;padding:10px 20px;border-radius:10px' href='{$linkApprove}'>Approve</a>
						<a style='background-color:#ff0000; text-decoration:none; color:#fff;padding:10px 20px;border-radius:10px' href='{$linkDecline}'>Decline</a>
					</p>
				";
				break;

			case 'admin':
				$data['message'] = "
					<p><strong>{$recommenderName}</strong> and <strong>{$secondRecommenderName}</strong> have recommended <strong>{$recommendedName}</strong> for consideration.</p>
					<p>Below is a summary of their details:</p>
					{$memberDetails}
					<p>Thank you for your time and consideration.</p>
					<p>
						<a style='background-color:#1bbe83; text-decoration:none; color:#fff;padding:10px 20px;border-radius:10px' href='{$linkApprove}'>Approve</a>
						<a style='background-color:#ff0000; text-decoration:none; color:#fff;padding:10px 20px;border-radius:10px' href='{$linkDecline}'>Decline</a>
					</p>
				";
				break;
		}

		// Send mail to current approver
		$messageContent = $this->load->view('template/common-mail', $data, true);
		$this->sendMail($approver['email'], $subject, $messageContent);

		if ($role === 'second_recommender') {
			$this->sendRecommendedMemberNotification($recommendedUser, $recommenderName, $secondRecommenderName);
		}

		// Notify super admins
		$admins = $this->common->getData('superAdmin', ['admin_type' => '2'], []);
		foreach ($admins as $admin) {
			$approverName = $data['sendername'];
			$adminData['sendername'] = $admin['name'];
			$adminSubject = "[Notification] {$role} approval requested";
			$adminData['message'] = "
				<p>An approval step (<strong>{$role}</strong>) has been created for recommendation #{$recommend_id}.</p>
				<p>Current approver: {$approverName} ({$approver['email']})</p>
				<p>Recommendation Details:</p>
				<ul>
					<li>Recommender: {$recommenderName}</li>
					<li>Recommended: {$recommendedName}</li>
					<li>Approval Step: {$role}</li>
				</ul>
			";
			$adminMessage = $this->load->view('template/common-mail', $adminData, true);
			$this->sendMail($admin['email'], $adminSubject, $adminMessage);
		}
	}

	private function sendRecommendedMemberNotification($recommendedUser, $recommenderName, $secondRecommenderName)
	{
		if (empty($recommendedUser['email'])) {
			return;
		}

		$recommendedName = trim(($recommendedUser['first_name'] ?? '') . ' ' . ($recommendedUser['last_name'] ?? ''));

		$data['sendername'] = $recommendedName;
		$data['useremail'] = $recommendedUser['email'];
		$data['message'] = "
			<p><strong>{$recommenderName}</strong> and <strong>{$secondRecommenderName}</strong> have recently recommended you to become a member of the Interfriends Savings Club/Co-operative by joining their Circle.</p>
			<p>We're letting you know that your recommendation is currently undergoing approval. You may receive a call or an email from us (Interfriends) inviting you to participate in our Welcome Presentation to learn more about Interfriends.</p>
			<p><strong>Next Steps;</strong></p>
			<ol>
				<li>Wait for your approval process to complete</li>
				<li>Attend our Welcome presentation</li>
				<li>Complete your application after the presentation and provide relevant identification</li>
				<li>Wait for approval.</li>
			</ol>
			
			<p>In the meantime, you can learn more about Interfriends by visiting <a href='https://www.interfriends.uk'>www.interfriends.uk</a></p>
		";

		$messageContent = $this->load->view('template/common-mail', $data, true);
		$this->sendMail($recommendedUser['email'], 'You Have Been Recommended to Join Interfriends', $messageContent);
	}

	// created by @krishn on 01-May-25
	public function handleApproval($token, $status)
	{
		$approval = $this->common->getData('recommendation_approvals', array('token' => $token), array('single'));
		if (empty($approval) || $approval['status'] != 0) {
			return $this->response(false, "Invalid or already processed approval.");
		}

		$this->common->updateData('recommendation_approvals', array('status' => $status), array('token' => $token));

		// if ($status == 2) {
		// 	$this->sendDeclineNotification($approval);
		// 	header("Location: " . USER_BASE_URL . "handleApproval/decline/2");
		// 	exit;
		// }
		// Process next level based on role
		switch ($approval['approver_role']) {
			case 'second_recommender':
				$this->processCircleApproval($approval['recommend_id']);
				header("Location: " . USER_BASE_URL . "handleApproval/second_recommender/1");
				exit;
				break;

			case 'circle_lead':
			case 'deputy_circle_lead':
				$this->processAdminApproval($approval['recommend_id']);
				header("Location: " . USER_BASE_URL . "handleApproval/circle_lead/1");
				exit;
				break;

			case 'admin':
				$this->sendFinalRegistrationMail($approval['recommend_id']);
				header("Location: " . USER_BASE_URL . "handleApproval/admin/1");
				exit;
				break;

			default:
				$this->response(false, "Unknown approval role.");
				exit;
				break;
		}
	}

	// created by @krishn on 01-May-25
	public function handleDecline($token, $status)
	{
		$approval = $this->common->getData('recommendation_approvals', array('token' => $token), array('single'));
		if (empty($approval) || $approval['status'] != 0) {
			return $this->response(false, "Invalid or already processed approval.");
		}

		$this->common->updateData('recommendation_approvals', array('status' => $status), array('token' => $token));

		if ($status == 2) {
			$this->sendDeclineNotification($approval);
			header("Location: " . USER_BASE_URL . "handleApproval/decline/2");
			exit;
		}
	}

	// created by @krishn on 01-May-25
	private function processCircleApproval($recommend_id)
	{
		$recommend = $this->common->getData('recommend_user', array('id' => $recommend_id), array('single'));
		$circleInfo = $this->common->getData('user_circle', array("user_id" => $recommend['refer_user_id']), array('single'));

		if (!empty($circleInfo)) {
			$circle_id = $circleInfo['circle_id'];

			// Circle lead
			$lead = $this->common->getData('user_circle', array("circle_id" => $circle_id, "circle_lead" => '1'), array('single'));
			if (!empty($lead)) {
				$this->createApproval($recommend_id, $lead['user_id'], 'circle_lead');
			}

			// Deputy lead
			$deputy = $this->common->getData('user_circle', array("circle_id" => $circle_id, "deputycirclelead" => '1'), array('single'));
			if (!empty($deputy)) {
				$this->createApproval($recommend_id, $deputy['user_id'], 'deputy_circle_lead');
			}
		}
	}

	// created by @krishn on 01-May-25
	private function processAdminApproval($recommend_id)
	{
		// Admin from superAdmin table
		$admin = $this->common->getData('superAdmin', array("admin_type" => '2'), array('single'));
		if (!empty($admin)) {
			$this->createApproval($recommend_id, $admin['id'], 'admin');
		}
	}

	// created by @krishn on 01-May-25
	private function sendFinalRegistrationMail($recommend_id)
	{
		$recommend = $this->common->getData('recommend_user', array('id' => $recommend_id), array('single'));

		$link = USER_BASE_URL . "register?recommend_id=$recommend_id";

		$message = "
			<p>
				I am writing to inform you that your recommendation to join Interfriends has been carefully reviewed and approved.
				Please note that we have already created a registration form for this purpose.
				Please click the button below to proceed with your registration.
			</p>

			<p>
				<a href='$link' 
				style='display: inline-block; padding: 10px 20px; font-size: 16px; color: #ffffff; 
						background-color: #007bff; text-decoration: none; border-radius: 5px;'>
				Complete Registration
				</a>
			</p>
		";

		$data['sendername'] = $recommend['first_name'] . ' ' . $recommend['last_name'];
		$data['useremail'] = $recommend['email'];
		$data['message'] = $message;
		$subject = "Complete Your Registration - Interfriends";

		$messaged = $this->load->view('template/common-mail', $data, true);
		$this->sendMail($recommend['email'], $subject, $messaged);
	}

	// created by @krishn on 01-May-25
	private function sendDeclineNotification($approval)
	{
		$recommend = $this->common->getData('recommend_user', ['id' => $approval['recommend_id']], ['single']);
		$decliner = ($approval['approver_role'] === 'admin')
			? $this->common->getData('superAdmin', ['id' => $approval['approver_id']], ['single'])
			: $this->common->getData('user', ['user_id' => $approval['approver_id']], ['single']);

		if (!$recommend || !$decliner) return;

		$recommender = $this->common->getData('user', ['user_id' => $recommend['user_id']], ['single']);
		$secondRecommender = $this->common->getData('user', ['user_id' => $recommend['refer_user_id']], ['single']);

		$recommendedName = trim($recommend['first_name'] . ' ' . $recommend['last_name']);
		$declinerName = isset($decliner['name']) ? $decliner['name'] : trim($decliner['first_name'] . ' ' . $decliner['last_name']);
		$recommenderName = trim($recommender['first_name'] . ' ' . $recommender['last_name']);
		$secondRecommenderName = trim($secondRecommender['first_name'] . ' ' . $secondRecommender['last_name']);

		$subject = "Recommendation Declined: {$recommendedName}";

		// Mail body content shared for all
		$data['message'] = "
			<p>Unfortunately, the recommendation process for <strong>{$recommendedName}</strong> has been <span style='color: red;'>declined</span> by <strong>{$declinerName}</strong> ({$approval['approver_role']}).</p>
			<p>Recommenders:</p>
			<ul>
				<li>Primary Recommender: {$recommenderName}</li>
				<li>Second Recommender: {$secondRecommenderName}</li>
			</ul>
			<p>Please reach out if you have any questions or want to revise the recommendation.</p>
		";

		$admins = $this->common->getData('superAdmin', ['admin_type' => '2'], []);

		if ($approval['approver_role'] === 'second_recommender') {
			// Notify first recommender
			$data['sendername'] = $recommenderName;
			$data['useremail'] = $recommender['email'];
			$messageContent = $this->load->view('template/common-mail', $data, true);
			$this->sendMail($recommender['email'], $subject, $messageContent);

			// Notify admins
			foreach ($admins as $admin) {
				$data['sendername'] = isset($admin['name']) ? $admin['name'] : 'Admin';
				$data['useremail'] = $admin['email'];
				$messageContent = $this->load->view('template/common-mail', $data, true);
				$this->sendMail($admin['email'], $subject, $messageContent);
			}
		} elseif (in_array($approval['approver_role'], ['circle_lead', 'deputy_circle_lead'])) {
			// Notify both recommenders
			$data['sendername'] = $recommenderName;
			$data['useremail'] = $recommender['email'];
			$messageContent = $this->load->view('template/common-mail', $data, true);
			$this->sendMail($recommender['email'], $subject, $messageContent);

			$data['sendername'] = $secondRecommenderName;
			$data['useremail'] = $secondRecommender['email'];
			$messageContent = $this->load->view('template/common-mail', $data, true);
			$this->sendMail($secondRecommender['email'], $subject, $messageContent);

			// Notify admins
			foreach ($admins as $admin) {
				$data['sendername'] = isset($admin['name']) ? $admin['name'] : 'Admin';
				$data['useremail'] = $admin['email'];
				$messageContent = $this->load->view('template/common-mail', $data, true);
				$this->sendMail($admin['email'], $subject, $messageContent);
			}
		} elseif ($approval['approver_role'] === 'admin') {
			// Notify both recommenders
			$data['sendername'] = $recommenderName;
			$data['useremail'] = $recommender['email'];
			$messageContent = $this->load->view('template/common-mail', $data, true);
			$this->sendMail($recommender['email'], $subject, $messageContent);

			$data['sendername'] = $secondRecommenderName;
			$data['useremail'] = $secondRecommender['email'];
			$messageContent = $this->load->view('template/common-mail', $data, true);
			$this->sendMail($secondRecommender['email'], $subject, $messageContent);

			// Also notify circle lead & deputy lead if available (optional enhancement)
			$circleLeads = $this->common->getData('user_group_lifecycle', [
				'group_id' => $recommend['group_id'],
				'user_role' => 'circle_lead'
			]);

			foreach ($circleLeads as $lead) {
				$leadData = $this->common->getData('user', ['user_id' => $lead['user_id']], ['single']);
				if ($leadData) {
					$leadName = trim($leadData['first_name'] . ' ' . $leadData['last_name']);
					$data['sendername'] = $leadName;
					$data['useremail'] = $leadData['email'];
					$messageContent = $this->load->view('template/common-mail', $data, true);
					$this->sendMail($leadData['email'], $subject, $messageContent);
				}
			}
		}
	}

	// API created by @krishn on 14/05/25
	public function getRecommendedUserDetails($id)
	{
		$recommendUserId = $id;
		$recommendData = $this->common->getData('recommend_user', array('id' => $recommendUserId), array('single'));

		if ($recommendData) {

			$registeredUser = $this->common->getData('user', array('email' => $recommendData['email']), array('single'));

			if (!empty($registeredUser)) {

				$preserveRecommendUserId = $recommendData['user_id'];
				$preserveRecommendId = $recommendData['id'];

				$hiddenUserFields = array('user_id', 'id', 'password', 'token', 'verify_token', 'web_token');

				foreach ($registeredUser as $key => $value) {
					if (in_array($key, $hiddenUserFields)) {
						continue;
					}

					$recommendData[$key] = $value;
				}

				$recommendData['id'] = $preserveRecommendId;
				$recommendData['user_id'] = $preserveRecommendUserId;
				$recommendData['recommended_user_id'] = $registeredUser['user_id'];
			}

			// Profile Image
			if (!empty($recommendData['profile_image'])) {
				$recommendData['profile_image'] = base_url($recommendData['profile_image']);
			} else {
				$recommendData['profile_image'] = base_url('assets/img/default-user-icon.jpg');
			}

			// Profile Thumbnail
			if (!empty($recommendData['profile_image_thumb'])) {
				$recommendData['profile_image_thumb'] = base_url($recommendData['profile_image_thumb']);
			} else {
				$recommendData['profile_image_thumb'] = base_url('assets/img/default-user-icon.jpg');
			}

			// ID Proof Image
			if (!empty($recommendData['id_proof_image'])) {
				$recommendData['id_proof_image'] = base_url($recommendData['id_proof_image']);
			} else {
				$recommendData['id_proof_image'] = base_url('assets/img/blank.webp');
			}

			$this->response(true, "Data fetch Successfully.", array("users" => $recommendData));
		} else {
			$this->response(false, "Data not found.");
		}
	}

	// API created by @krishn on 10/06/26
	public function getMyRecommendedUsers($id)
	{
		$myId = $id;
		$recommendData = $this->common->getData('recommend_user', array('user_id' => $myId), array('all'));

		if ($recommendData) {
			$this->response(true, "Data fetch Successfully.", array("users" => $recommendData));
		} else {
			$this->response(false, "Data not found.");
		}
	}

	// created by @krishn on 27/05/25
	// public function getAllCircleUsers()
	// {
	// 	$userId = $_REQUEST['user_id'] ?? null;

	// 	if (!$userId) {
	// 		$this->response(false, "User ID is required.");
	// 		return;
	// 	}

	// 	// Get existing user
	// 	$existingUser = $this->common->getData('user', ['user_id' => $userId], ['single']);
	// 	if (empty($existingUser)) {
	// 		$this->response(false, "User not found");
	// 		return;
	// 	}

	// 	// Get user's circle info
	// 	$existingUserDetails = $this->common->getData('user_circle', ['user_id' => $userId], ['single']);
	// 	if (empty($existingUserDetails)) {
	// 		$this->response(false, "User circle details not found");
	// 		return;
	// 	}

	// 	$groupId = $existingUserDetails['group_id'];
	// 	$circleId = $existingUserDetails['circle_id'];

	// 	// Pagination
	// 	$start = !empty($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
	// 	$limit = 10;

	// 	// Get all users in the same circle except the current user
	// 	$circleUsers = $this->common->getData('user_circle', [
	// 		'group_id' => $groupId,
	// 		'circle_id' => $circleId,
	// 	]);

	// 	if (empty($circleUsers)) {
	// 		$this->response(false, "No users found in the circle");
	// 		return;
	// 	}

	// 	$userIds = [];
	// 	foreach ($circleUsers as $cu) {
	// 		if ($cu['user_id'] != $userId) {
	// 			$userIds[] = (int) $cu['user_id'];
	// 		}
	// 	}

	// 	if (empty($userIds)) {
	// 		$this->response(false, "No other users found in the circle");
	// 		return;
	// 	}

	// 	// Build WHERE clause for the user table
	// 	$userIdStr = implode(',', $userIds);
	// 	$where = "user_id IN ($userIdStr) AND status != 2 AND recommended = 0";

	// 	if (!empty($_REQUEST['search_keyword'])) {
	// 		$keyword = $this->db->escape_like_str($_REQUEST['search_keyword']);
	// 		$where .= " AND (first_name LIKE '%$keyword%' OR last_name LIKE '%$keyword%' OR email LIKE '%$keyword%')";
	// 	}

	// 	// Fetch users with pagination
	// 	$circleUserData = $this->common->getData('user', $where, [], $limit, $start);
	// 	$totalCount = $this->common->getData('user', $where, ['count']);

	// 	$circleInfo = $this->common->getData('group_circle', ['id' => $circleId], ['single']);

	// 	$sno = $start + 1;
	// 	foreach ($circleUserData as $key => $value) {
	// 		$circleUserData[$key]['sno'] = $sno++;

	// 		if (!empty($value['profile_image'])) {
	// 			$circleUserData[$key]['profile_image'] = base_url($value['profile_image']);
	// 			$circleUserData[$key]['profile_image_thumb'] = base_url($value['profile_image_thumb']);
	// 		} else {
	// 			$circleUserData[$key]['profile_image'] = "assets/img/default-user-icon.jpg";
	// 			$circleUserData[$key]['profile_image_thumb'] = "assets/img/default-user-icon.jpg";
	// 		}
	// 	}

	// 	$this->response(true, "Circle users fetched successfully", [
	// 		"circleName" => $circleInfo['circle_name'] ?? '',
	// 		"users" => $circleUserData,
	// 		"totalCount" => $totalCount
	// 	]);
	// }

	public function getAllCircleUsers()
	{
		$userId = $_REQUEST['user_id'] ?? null;

		if (!$userId) {
			$this->response(false, "User ID is required.");
			return;
		}

		// Get existing user
		$existingUser = $this->common->getData('user', ['user_id' => $userId], ['single']);
		if (empty($existingUser)) {
			$this->response(false, "User not found");
			return;
		}

		// Get user's circle info
		$existingUserDetails = $this->common->getData('user_circle', ['user_id' => $userId], ['single']);
		if (empty($existingUserDetails)) {
			$this->response(false, "User circle details not found");
			return;
		}

		$groupId = $existingUserDetails['group_id'];
		$circleId = $existingUserDetails['circle_id'];

		// Pagination
		$start = !empty($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
		$limit = 10;

		// Get all users in the same circle
		$circleUsers = $this->common->getData('user_circle', [
			'group_id' => $groupId,
			'circle_id' => $circleId,
		]);

		if (empty($circleUsers)) {
			$this->response(false, "No users found in the circle");
			return;
		}

		$userIds = [];
		$roles = [];

		foreach ($circleUsers as $cu) {
			$userIds[] = (int)$cu['user_id'];

			if ($cu['circle_lead'] == 1 && $cu['deputycirclelead'] == 1) {
				$roles[$cu['user_id']] = 'Circle Lead & Deputy Circle Lead';
			} elseif ($cu['circle_lead'] == 1) {
				$roles[$cu['user_id']] = 'Circle Lead';
			} elseif ($cu['deputycirclelead'] == 1) {
				$roles[$cu['user_id']] = 'Deputy Circle Lead';
			} else {
				$roles[$cu['user_id']] = 'Member';
			}
		}

		if (empty($userIds)) {
			$this->response(false, "No users found in the circle");
			return;
		}

		// Build WHERE clause for the user table
		$userIdStr = implode(',', $userIds);
		$where = "user_id IN ($userIdStr) AND status != 2 AND recommended = 0";

		if (!empty($_REQUEST['search_keyword'])) {
			$keyword = $this->db->escape_like_str($_REQUEST['search_keyword']);
			$where .= " AND (
			first_name LIKE '%$keyword%' 
			OR last_name LIKE '%$keyword%' 
			OR email LIKE '%$keyword%'
		)";
		}

		// Fetch users with pagination
		$circleUserData = $this->common->getData('user', $where, [], $limit, $start);
		$totalCount = $this->common->getData('user', $where, ['count']);

		$circleInfo = $this->common->getData('group_circle', ['id' => $circleId], ['single']);

		$sno = $start + 1;
		foreach ($circleUserData as $key => $value) {

			$circleUserData[$key]['sno'] = $sno++;

			// Add role
			$circleUserData[$key]['role'] = $roles[$value['user_id']] ?? 'Member';

			if (!empty($value['profile_image'])) {
				$circleUserData[$key]['profile_image'] = base_url($value['profile_image']);
				$circleUserData[$key]['profile_image_thumb'] = base_url($value['profile_image_thumb']);
			} else {
				$circleUserData[$key]['profile_image'] = "assets/img/default-user-icon.jpg";
				$circleUserData[$key]['profile_image_thumb'] = "assets/img/default-user-icon.jpg";
			}
		}

		$this->response(true, "Circle users fetched successfully", [
			"circleName" => $circleInfo['circle_name'] ?? '',
			"users" => $circleUserData,
			"totalCount" => $totalCount
		]);
	}

	// created by @krishn on 15/07/26
	public function getMyAllServices()
	{
		if (empty($_REQUEST['user_id'])) {
			$this->response(false, "User ID is required.");
			return;
		}

		$where = " AND US.user_id = " . (int)$_REQUEST['user_id'] . " AND US.status = 1 ";

		$services = $this->user_model->user_assigned_services($where);
		$services = $this->user_model->attachServiceImages($services);

		$this->response(true, "Services fetched successfully.", array(
			"services"   => $services,
			"totalCount" => count($services)
		));
	}

	// created by @krishn on 16/07/26
	public function createService()
	{
		if (empty($_REQUEST['user_id'])) {
			$this->response(false, "User is required.");
			return;
		}

		if (empty($_REQUEST['service_id'])) {
			$this->response(false, "Service is required.");
			return;
		}

		// if (empty($_REQUEST['price']) || $_REQUEST['price'] <= 0) {
		// 	$this->response(false, "Price is required.");
		// 	return;
		// }

		// User Check
		$user = $this->common->getData(
			'user',
			array('user_id' => $_REQUEST['user_id']),
			array('single')
		);

		if (empty($user)) {
			$this->response(false, "User not found.");
			return;
		}

		// Service Check
		$service = $this->common->getData(
			'services',
			array(
				'id' => $_REQUEST['service_id'],
				'status' => 1
			),
			array('single')
		);

		if (empty($service)) {
			$this->response(false, "Service not found.");
			return;
		}

		// Already Exists?
		$exist = $this->common->getData(
			'user_services',
			array(
				'user_id' => $_REQUEST['user_id'],
				'service_id' => $_REQUEST['service_id'],
				'status' => 1
			),
			array('single')
		);

		if (!empty($exist)) {
			$this->response(false, "You have already requested this service.");
			return;
		}

		// Maximum 5 Services Check
		$totalServices = $this->common->getData(
			'user_services',
			"user_id='" . $_REQUEST['user_id'] . "' AND status = 1 AND approval_status IN(0,1)",
			array('count')
		);

		if ($totalServices >= 5) {
			$this->response(false, "You can create/request maximum 5 services.");
			return;
		}

		if ($this->getServiceImageUploadCount() > 5) {
			$this->response(false, "You can upload maximum 5 images for one service.");
			return;
		}

		$companyLogo = '';
		if (!empty($_FILES['company_logo']['name'])) {
			$uploadPath = './assets/user_services/';
			if (!is_dir($uploadPath) && !mkdir($uploadPath, 0777, true)) {
				$this->response(false, "Unable to create service image folder.");
				return;
			}
			$logoImage = $this->common->do_upload_file('company_logo', $uploadPath);
			if (!empty($logoImage['upload_data'])) {
				$companyLogo = 'assets/user_services/' . $logoImage['upload_data']['file_name'];
			} else {
				$this->response(false, $this->serviceImageUploadError(isset($logoImage['error']) ? $logoImage['error'] : ''));
				return;
			}
		} elseif (!empty($_REQUEST['company_logo'])) {
			$companyLogo = $_REQUEST['company_logo'];
		}

		$data = array(
			'user_id'           => $_REQUEST['user_id'],
			'service_id'        => $_REQUEST['service_id'],
			'company_name'      => !empty($_REQUEST['company_name']) ? $_REQUEST['company_name'] : '',
			'company_logo'      => $companyLogo,
			'description'       => !empty($_REQUEST['description']) ? $_REQUEST['description'] : '',
			'country_code'      => !empty($_REQUEST['country_code']) ? $_REQUEST['country_code'] : '',
			'mobile'            => !empty($_REQUEST['mobile']) ? $_REQUEST['mobile'] : '',
			'email'             => !empty($_REQUEST['email']) ? $_REQUEST['email'] : '',
			'website'           => !empty($_REQUEST['website']) ? $_REQUEST['website'] : '',
			'location'          => !empty($_REQUEST['location']) ? $_REQUEST['location'] : '',
			'approval_status'   => 0,
			'status'            => 1,
			'created_by'        => $_REQUEST['user_id'],
			'created_by_type'   => 'user',
			'created_at'        => date('Y-m-d H:i:s')
		);

		$post = $this->common->getField('user_services', $data);

		$result = $this->common->insertData('user_services', $post);

		if (!$result) {
			$this->response(false, "Unable to create service request.");
			return;
		}

		$userServiceId = $this->db->insert_id();

		if (!$this->saveServiceImages($userServiceId)) {
			return;
		}

		/***********************
		 * Notify Admin
		 ************************/

		$admins = $this->common->getData(
			'superAdmin',
			array('admin_type' => '2')
		);

		if (!empty($admins)) {

			foreach ($admins as $admin) {

				$mailData['sendername'] = $admin['name'];
				$mailData['useremail'] = $admin['email'];

				$mailData['message'] = "
				<p>A new service request requires your approval.</p>

				<p><strong>User :</strong> {$user['first_name']} {$user['last_name']}</p>

				<p><strong>Email :</strong> {$user['email']}</p>

				<p><strong>Service :</strong> {$service['service_name']}</p>
				";

				$mailMessage = $this->load->view('template/common-mail', $mailData, true);

				$this->sendMail(
					$admin['email'],
					'New Service Request',
					$mailMessage
				);
			}
		}

		$this->response(true, "Service request submitted successfully. Waiting for admin approval.");
	}

	// created by @krishn on 20/07/26
	private function getServiceImageUploadCount($field = 'images')
	{
		if (empty($_FILES[$field]['name'])) {
			return 0;
		}

		if (is_array($_FILES[$field]['name'])) {
			return count(array_filter($_FILES[$field]['name']));
		}

		return 1;
	}

	// created by @krishn on 20/07/26
	private function getDeleteServiceImageIds()
	{
		if (empty($_REQUEST['delete_image_ids'])) {
			return array();
		}

		$imageIds = $_REQUEST['delete_image_ids'];

		if (!is_array($imageIds)) {
			$imageIds = explode(',', $imageIds);
		}

		return array_values(array_filter(array_map('intval', $imageIds)));
	}

	// created by @krishn on 20/07/26
	private function serviceImageUploadError($error)
	{
		if (is_array($error)) {
			$error = implode(' ', $error);
		}

		if (empty($error)) {
			$error = "Unable to upload service image.";
		}

		return trim(strip_tags($error));
	}

	// created by @krishn on 20/07/26
	private function saveServiceImages($userServiceId, $maxImages = 5)
	{
		$uploadCount = $this->getServiceImageUploadCount();

		if ($uploadCount == 0) {
			return true;
		}

		$existingCount = $this->common->getData(
			'service_images',
			array('user_service_id' => $userServiceId),
			array('count')
		);

		if (($existingCount + $uploadCount) > $maxImages) {
			$this->response(false, "You can upload maximum {$maxImages} images for one service.");
			return false;
		}

		$uploadPath = './assets/user_services/';

		if (!is_dir($uploadPath) && !mkdir($uploadPath, 0777, true)) {
			$this->response(false, "Unable to create service image folder.");
			return false;
		}

		if (is_array($_FILES['images']['name'])) {
			$images = $this->common->multi_upload('images', $uploadPath);

			if (empty($images) || !is_array($images)) {
				$this->response(false, $this->serviceImageUploadError($images));
				return false;
			}
		} else {
			$image = $this->common->do_upload_file('images', $uploadPath);

			if (empty($image['upload_data'])) {
				$this->response(false, $this->serviceImageUploadError(isset($image['error']) ? $image['error'] : ''));
				return false;
			}

			$images = array($image['upload_data']);
		}

		foreach ($images as $serviceImage) {
			$imageData = array(
				'user_service_id' => $userServiceId,
				'image'           => 'assets/user_services/' . $serviceImage['file_name'],
				'created_at'      => date('Y-m-d H:i:s'),
				'updated_at'      => date('Y-m-d H:i:s')
			);

			$this->common->insertData('service_images', $this->common->getField('service_images', $imageData));
		}

		return true;
	}

	// created by @krishn on 20/07/26
	private function deleteServiceImages($userServiceId, $imageIds)
	{
		if (empty($imageIds)) {
			return true;
		}

		$this->db->where('user_service_id', $userServiceId);
		$this->db->where_in('id', $imageIds);
		$images = $this->db->get('service_images')->result_array();

		if (count($images) != count($imageIds)) {
			$this->response(false, "Invalid service image selected for delete.");
			return false;
		}

		foreach ($images as $image) {
			if (!empty($image['image']) && file_exists(FCPATH . $image['image'])) {
				unlink(FCPATH . $image['image']);
			}
		}

		$this->db->where('user_service_id', $userServiceId);
		$this->db->where_in('id', $imageIds);
		$this->db->delete('service_images');

		return true;
	}

	// created by @krishn on 16/07/26
	public function updateUserService()
	{
		if (empty($_REQUEST['user_service_id'])) {
			$this->response(false, "User Service ID is required.");
			return;
		}

		$service = $this->common->getData(
			'user_services',
			array(
				'id' => $_REQUEST['user_service_id'],
				'status' => 1
			),
			array('single')
		);

		if (empty($service)) {
			$this->response(false, "Service not found.");
			return;
		}

		$deleteImageIds = $this->getDeleteServiceImageIds();
		$newImageCount = $this->getServiceImageUploadCount();

		if (!empty($deleteImageIds)) {
			$this->db->where('user_service_id', $_REQUEST['user_service_id']);
			$this->db->where_in('id', $deleteImageIds);
			$deleteImageCount = $this->db->count_all_results('service_images');

			if ($deleteImageCount != count($deleteImageIds)) {
				$this->response(false, "Invalid service image selected for delete.");
				return;
			}
		} else {
			$deleteImageCount = 0;
		}

		$existingImageCount = $this->common->getData(
			'service_images',
			array('user_service_id' => $_REQUEST['user_service_id']),
			array('count')
		);

		if (($existingImageCount - $deleteImageCount + $newImageCount) > 5) {
			$this->response(false, "You can upload maximum 5 images for one service.");
			return;
		}

		// Only update allowed fields
		$updateArr = array();

		if (isset($_REQUEST['company_name'])) {
			$updateArr['company_name'] = $_REQUEST['company_name'];
		}

		if (!empty($_FILES['company_logo']['name'])) {
			$uploadPath = './assets/user_services/';
			if (!is_dir($uploadPath) && !mkdir($uploadPath, 0777, true)) {
				$this->response(false, "Unable to create service image folder.");
				return;
			}
			$logoImage = $this->common->do_upload_file('company_logo', $uploadPath);
			if (!empty($logoImage['upload_data'])) {
				$updateArr['company_logo'] = 'assets/user_services/' . $logoImage['upload_data']['file_name'];
			} else {
				$this->response(false, $this->serviceImageUploadError(isset($logoImage['error']) ? $logoImage['error'] : ''));
				return;
			}
		} elseif (isset($_REQUEST['company_logo'])) {
			$updateArr['company_logo'] = $_REQUEST['company_logo'];
		}

		if (isset($_REQUEST['price'])) {
			$updateArr['price'] = $_REQUEST['price'];
		}

		if (isset($_REQUEST['description'])) {
			$updateArr['description'] = $_REQUEST['description'];
		}

		if (isset($_REQUEST['mobile'])) {
			$updateArr['mobile'] = $_REQUEST['mobile'];
		}

		if (isset($_REQUEST['country_code'])) {
			$updateArr['country_code'] = $_REQUEST['country_code'];
		}

		if (isset($_REQUEST['email'])) {
			$updateArr['email'] = $_REQUEST['email'];
		}

		if (isset($_REQUEST['website'])) {
			$updateArr['website'] = $_REQUEST['website'];
		}

		if (isset($_REQUEST['location'])) {
			$updateArr['location'] = $_REQUEST['location'];
		}

		if (isset($_REQUEST['latitude'])) {
			$updateArr['latitude'] = $_REQUEST['latitude'];
		}

		if (isset($_REQUEST['longitude'])) {
			$updateArr['longitude'] = $_REQUEST['longitude'];
		}

		// Again send for approval
		$updateArr['approval_status'] = 0;
		$updateArr['approved_by'] = NULL;
		$updateArr['approved_at'] = NULL;
		$updateArr['updated_at'] = date('Y-m-d H:i:s');

		$post = $this->common->getField('user_services', $updateArr);

		$result = $this->common->updateData(
			'user_services',
			$post,
			array('id' => $_REQUEST['user_service_id'])
		);

		if (!$result) {
			$this->response(false, "Unable to update service.");
			return;
		}

		if (!$this->deleteServiceImages($_REQUEST['user_service_id'], $deleteImageIds)) {
			return;
		}

		if (!$this->saveServiceImages($_REQUEST['user_service_id'])) {
			return;
		}

		// User Details
		$user = $this->common->getData(
			'user',
			array('user_id' => $service['user_id']),
			array('single')
		);

		// Service Details
		$serviceDetail = $this->common->getData(
			'services',
			array('id' => $service['service_id']),
			array('single')
		);

		// Mail to Admins
		$admins = $this->common->getData(
			'superAdmin',
			array('admin_type' => '2')
		);

		if (!empty($admins)) {

			foreach ($admins as $admin) {

				$data['sendername'] = $admin['name'];
				$data['useremail'] = $admin['email'];

				$data['message'] = "
					<p>A user has updated their service details.</p>

					<p><strong>User:</strong> {$user['first_name']} {$user['last_name']}</p>

					<p><strong>Service:</strong> {$serviceDetail['service_name']}</p>

					<p>The service is waiting for your approval.</p>
				";

				$mailMessage = $this->load->view('template/common-mail', $data, true);

				$this->sendMail(
					$admin['email'],
					'Service Update Approval Required',
					$mailMessage
				);
			}
		}

		$this->response(true, "Service updated successfully and sent for approval.");
	}

	// created by @krishn on 16/07/26
	public function getAllServices()
	{
		$limit = '';
		$start = '';
		$where = array(
			'US.status' => 1
		);

		if (isset($_REQUEST['start']) && $_REQUEST['start'] !== '') {
			$limit = 10;
			$start = (int)$_REQUEST['start'];
		}

		if (!empty($_REQUEST['user_service_id'])) {
			$where['US.id'] = $_REQUEST['user_service_id'];
		}

		if (!empty($_REQUEST['category_id'])) {
			$where['S.category_id'] = $_REQUEST['category_id'];
		}

		if (!empty($_REQUEST['subcategory_id'])) {
			$where['S.subcategory_id'] = $_REQUEST['subcategory_id'];
		}

		if (isset($_REQUEST['approval_status']) && $_REQUEST['approval_status'] !== '') {
			$where['US.approval_status'] = $_REQUEST['approval_status'];
		}

		if (isset($_REQUEST['service_status']) && $_REQUEST['service_status'] !== '') {
			$where['S.status'] = $_REQUEST['service_status'];
		}

		if (!empty($_REQUEST['service_id'])) {
			$where['US.service_id'] = $_REQUEST['service_id'];
		}

		if (!empty($_REQUEST['search']) || !empty($_REQUEST['search_keyword'])) {
			$where['search'] = !empty($_REQUEST['search']) ? trim($_REQUEST['search']) : trim($_REQUEST['search_keyword']);
		}

		$services = $this->user_model->user_assigned_services($where, array(), $limit, $start);
		$services = $this->user_model->attachServiceImages($services);
		$totalCount = $this->user_model->user_assigned_services($where, array('count'));

		$this->response(true, "Services fetched successfully.", array(
			"services"   => $services,
			"totalCount" => $totalCount
		));
	}

	/**
	 * Test API for sending WhatsApp messages
	 * Endpoint: Api/test_whatsapp
	 * Method: GET / POST
	 * Parameters:
	 *   - recipient: (required) Phone number with country code (e.g. 919876543210) OR user email
	 *   - message: Message text body (optional, for text messages)
	 *   - template_name: Meta template name (optional, for template messages)
	 *   - parameters: Parameters array or JSON for template placeholders (optional)
	 *   - language_code: Language code e.g. 'en' (optional, default 'en')
	 */
	public function test_whatsapp()
	{
		$recipient     = isset($_REQUEST['recipient']) ? trim($_REQUEST['recipient']) : '';
		$message       = isset($_REQUEST['message']) ? trim($_REQUEST['message']) : 'Test WhatsApp Message from Interfriends API';
		$template_name = isset($_REQUEST['template_name']) ? trim($_REQUEST['template_name']) : '';
		$params_raw    = isset($_REQUEST['parameters']) ? $_REQUEST['parameters'] : '';
		$language_code = isset($_REQUEST['language_code']) ? trim($_REQUEST['language_code']) : 'en';

		if (empty($recipient)) {
			$this->response(false, 'Recipient (phone number or email) is required.');
			return;
		}

		$parameters = array();
		if (!empty($params_raw)) {
			if (is_array($params_raw)) {
				$parameters = $params_raw;
			} else {
				$decoded = json_decode($params_raw, true);
				$parameters = is_array($decoded) ? $decoded : explode(',', $params_raw);
			}
		}

		$result = send_whatsapp_notification($recipient, $message, $template_name, $parameters, $language_code);

		if (!empty($result['success'])) {
			$this->response(true, 'WhatsApp message sent successfully', $result);
		} else {
			$this->response(false, isset($result['message']) ? $result['message'] : 'Failed to send WhatsApp message', isset($result['data']) ? $result['data'] : $result);
		}
	}

	// webhook for whatsapp message
	public function whatsapp_webhook()
	{
		// 1. Meta Verification Challenge (GET Request)
		if ($this->input->method() === 'get') {
			$mode      = $this->input->get('hub_mode');
			$token     = $this->input->get('hub_verify_token');
			$challenge = $this->input->get('hub_challenge');

			$config_token = $this->config->item('whatsapp_verify_token', 'whatsapp');
			if (empty($config_token)) {
				$config_token = 'INTERFRIENDS_WA_WEBHOOK_2026';
			}

			if ($mode === 'subscribe' && $token === $config_token) {
				http_response_code(200);
				echo $challenge;
				exit;
			} else {
				http_response_code(403);
				echo 'Forbidden';
				exit;
			}
		}

		// 2. Incoming Notification / Webhook Payload (POST Request)
		if ($this->input->method() === 'post') {
			$input = file_get_contents('php://input');
			$data  = json_decode($input, true);

			if (!empty($data)) {
				log_message('info', 'WhatsApp Webhook Notification: ' . $input);
			}

			http_response_code(200);
			echo 'EVENT_RECEIVED';
			exit;
		}
	}

	// created by @krishn on 07/08/26
	public function myDividendList()
	{
		/*
		* Validate User
		*/
		if (empty($_REQUEST['user_id'])) {
			$this->response(false, "User ID is required.");
			return;
		}

		$userId = (int) $_REQUEST['user_id'];

		/*
		* Pagination
		*/
		$limit = !empty($_REQUEST['limit'])
			? (int) $_REQUEST['limit']
			: 10;

		$start = isset($_REQUEST['start'])
			? (int) $_REQUEST['start']
			: 0;

		if ($limit < 1) {
			$limit = 10;
		}

		if ($start < 0) {
			$start = 0;
		}

		/*
		* Status:
		* 0 = Rejected
		* 1 = Accepted
		* 2 = Pending
		* 3 = Initiated
		*/
		$status = isset($_REQUEST['status'])
			? trim($_REQUEST['status'])
			: '';

		if ($status !== '') {

			$status = (int) $status;

			if (!in_array($status, array(0, 1, 2, 3), true)) {
				$this->response(false, "Invalid status.");
				return;
			}
		}

		$filters = array(
			'group_id' => isset($_REQUEST['group_id']) ? $_REQUEST['group_id'] : '',
			'dividend_year' => isset($_REQUEST['dividend_year']) ? $_REQUEST['dividend_year'] : '',
			'status' => $status
		);

		$result = $this->user_model->getMyDividendList($userId, $filters, $limit, $start);

		$this->response(
			true,
			"Dividend list fetched successfully.",
			$result
		);
	}

	// created by @krishn on 07/08/26
	public function requestDividendPayout()
	{
		$dividendUserId = isset($_REQUEST['dividend_user_id'])
			? (int) $_REQUEST['dividend_user_id']
			: 0;

		$userId = isset($_REQUEST['user_id'])
			? (int) $_REQUEST['user_id']
			: 0;

		/*
		* Validate parameters
		*/
		if (empty($dividendUserId) || empty($userId)) {
			$this->response(
				false,
				"Dividend ID and User ID are required."
			);
			return;
		}

		/*
		* Get user's dividend record from model
		*/
		$dividend = $this->user_model->getUserDividendForPayout(
			$dividendUserId,
			$userId
		);

		if (empty($dividend)) {
			$this->response(false, "Dividend not found.");
			return;
		}

		/*
		* Status:
		* 0 = Rejected
		* 1 = Accepted
		* 2 = Pending
		* 3 = Initiated
		*/

		$currentStatus = (int) $dividend['status'];

		/*
		* Already pending
		*/
		if ($currentStatus === 2) {
			$this->response(
				false,
				"Dividend payout request is already pending."
			);
			return;
		}

		/*
		* Rejected request
		*
		* Allow user to submit again if the
		* previous request was rejected.
		*/
		if ($currentStatus === 0) {
			// Allow resubmission
		}

		/*
		* No balance available
		*/
		$balanceAmount = (float) $dividend['balance_amount'];

		if ($balanceAmount <= 0) {
			$this->response(
				false,
				"Dividend payout has already been completed."
			);
			return;
		}

		/*
		* Dividend Type:
		* 1 = Investment
		* 2 = Provident
		*/
		$dividendType = (int) $dividend['type'];

		if ($dividendType === 1) {
			$dividendTypeName = 'Investment';
		} elseif ($dividendType === 2) {
			$dividendTypeName = 'Provident';
		} else {
			$dividendTypeName = 'Unknown';
		}

		/*
		* User can request payout when
		* dividend is Initiated (3), Accepted (1)
		* OR previously Rejected (0).
		*
		* Status will become Pending (2).
		*/
		if (
			$currentStatus !== 3 &&
			$currentStatus !== 1 &&
			$currentStatus !== 0
		) {
			$this->response(
				false,
				"Dividend payout cannot be requested at this time."
			);
			return;
		}

		/*
		* Apply payout request status in model
		*/
		$result = $this->user_model->applyDividendPayoutRequest($dividendUserId, $userId);

		if (!$result) {
			$this->response(
				false,
				"There is a problem submitting the payout request."
			);
			return;
		}

		/*
		* Notify Admin
		*/
		$message = "Dividend payout request";

		$this->send_nofificationAdmin(
			$userId,
			$dividend['group_id'],
			$message,
			$dividendUserId,
			"17"
		);

		/*
		* Send email to admins
		*/
		$admins = $this->common->getData(
			'superAdmin',
			array(
				'admin_type' => '2'
			)
		);

		if (!empty($admins)) {

			foreach ($admins as $admin) {

				if (empty($admin['email'])) {
					continue;
				}

				$userName = trim(
					$dividend['first_name'] . ' ' . $dividend['last_name']
				);

				$data['sendername'] = !empty($admin['name'])
					? $admin['name']
					: 'Admin';

				$data['useremail'] = $admin['email'];

				$data['message'] = '
				<p>
					<strong>' . htmlspecialchars($userName) . '</strong>
					has requested a dividend payout.
				</p>

				<p>
					<strong>Dividend Year:</strong>
					' . htmlspecialchars($dividend['dividend_year']) . '
				</p>

				<p>
					<strong>Dividend Type:</strong>
					' . htmlspecialchars($dividendTypeName) . '
				</p>

				<p>
					<strong>Amount:</strong>
					£ ' . number_format($balanceAmount, 2) . '
				</p>

				<p>
					Please review and process the payout request
					from the admin panel.
				</p>
			';

				$mailMessage = $this->load->view(
					'template/common-mail',
					$data,
					true
				);

				$this->sendMail(
					$admin['email'],
					'Dividend Payout Request - ' . $dividendTypeName,
					$mailMessage
				);
			}
		}

		/*
		* Response
		*/
		$this->response(
			true,
			"Dividend payout request submitted successfully."
		);
	}
}
