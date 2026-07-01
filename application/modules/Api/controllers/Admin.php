<?php
defined('BASEPATH') or exit('No direct script access allowed');
date_default_timezone_set("Europe/London");
#[\AllowDynamicProperties]
class Admin extends Base_Controller
{
	public function __construct()
	{
		parent::__construct();
		// $this->checkAuth();		
		$this->load->helper(
			array('common', 'user')
		);
		$this->load->library('email');
		$this->load->model("user_model");

		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
		header('Access-Control-Allow-Credentials: true');
	}

	// created by @krishn on 16-05-25
	public function login()
	{
		$_POST['password'] = md5($_POST['password']);
		$adminInfo = $this->common->getData('superAdmin', [
			'email' => $_POST['email'],
			'password' => $_POST['password']
		], ['single']);

		if (empty($adminInfo)) {
			$this->response(false, 'Invalid email or password.');
			return;
		}

		if ($adminInfo['status'] == '2') {
			$this->response(false, 'Access denied: Your account has been blocked by the administrator.');
			return;
		}

		// Generate a secure token
		$token = bin2hex(random_bytes(32)); // 64-character secure random token

		$this->response(true, 'Login successful.', [
			"user_id" => $adminInfo["id"],
			"email" => $adminInfo["email"],
			"group_ids" => $adminInfo["group_ids"],
			"circle_ids" => $adminInfo["circle_ids"],
			"token" => $token,
			"name" => $adminInfo["name"],
			"admin_type" => $adminInfo["admin_type"]
		]);
	}

	// created by @krishn on 03-06-25
	public function dashboard_row_one()
	{
		ini_set('display_errors', 1);
		$userCount = $this->common->getData('user', '', array('count'));

		$activeUserCount = $this->common->getData('user', array('status' => '1'), array('count'));

		$blockedUserCount = $this->common->getData('user', array('status' => '2'), array('count'));

		$investment = $this->common->getData('investment I, user U', "I.user_id = U.user_id AND U.status != '2' AND I.group_id != 34", array("field" => 'IFNULL(sum(amount),0) as total', "single"));

		$property = $this->common->getData('investment I, user U', "I.user_id = U.user_id AND U.status != '2' AND I.group_id != 34 AND investment_type = 1", array("field" => 'IFNULL(sum(amount),0) as total', "single"));

		$payout = $this->common->getData('payout_cycle', array(), array("field" => 'IFNULL(sum(payout_amount),0) as total', "single"));

		$pf = $this->common->getData('pf_user PF, user U', "PF.user_id = U.user_id AND U.status != '2' AND PF.group_id != 34 AND PF.payment_type = 2", array("field" => 'IFNULL(sum(PF.pf_amount),0) as total, IFNULL(sum(PF.pf_interest_amount),0) as pf_interest', "single"));

		$emergency_loan_completed = $this->common->getData('user_emergency_loan', array(), array("field" => 'IFNULL(sum(loan_amount),0) as total', "single"));

		$emergency_loan_active = $this->common->getData('user_emergency_loan', array('status' => '4'), array("field" => 'IFNULL(sum(loan_amount),0) as total', "single"));

		$info = array(
			'userCount' => $userCount,
			'activeUserCount' => $activeUserCount,
			'blockedUserCount' => $blockedUserCount,
			'investment' => $investment['total'],
			'payout' => $payout['total'],
			'pf' => $pf['total'],
			'pfInterest' => $pf['pf_interest'],
			'emergency_loan_completed' => $emergency_loan_completed['total'],
			'emergency_loan_active' => $emergency_loan_active['total'],
			'paid_for_property' => $property['total'],
		);

		$this->response(true, 'Dashboard first row fetch Successfully', array('info' => $info));
	}

	// created by @krishn on 03-06-25
	public function dashboard_row_two()
	{
		ini_set('display_errors', 1);
		$safeKeeping =  $this->safekeepingTotalAmount();

		$cycle_pending = $this->savingTotalnewPending();

		$cycle = $this->savingTotalnew();

		$info = array(
			'safeKeeping' => $safeKeeping,
			'cycle_pending' => $cycle_pending,
			'cycle' => $cycle
		);

		$this->response(true, 'Dashboard second row fetch Successfully', array('info' => $info));
	}

	// created by @krishn on 03-06-25
	public function dashboard_row_three()
	{
		ini_set('display_errors', 1);
		$loan_completed = $this->common->getData('user_loan', array(), array("field" => 'IFNULL(sum(loan_amount),0) as total', "single"));

		$loan_paid_user = $this->common->getData('user_loan_payment ULP, user U', "ULP.user_id = U.user_id AND U.status != '2' AND ULP.group_id != 34", array("field" => 'IFNULL(sum(amount),0) as total', "single"));

		$group_list = $this->user_model->group_detail(array(), array(), "", "");
		$group_data = array();
		if (!empty($group_list)) {
			foreach ($group_list as $key => $value) {

				$where = "UG.group_id = '" . $value['id'] . "'";
				$userCount = $this->user_model->user_group_detail($where, array('count'));
				$value['usercount'] = $userCount;
				$group_data[] = $value;
			}
		}

		$loan_help_to_buycar_insurance = $this->common->getData(
			'user_loan UL, user U',
			"UL.loan_type = '2' AND UL.user_id = U.user_id AND U.status != '2' AND UL.group_id != 34",
			array("field" => 'IFNULL(SUM(UL.loan_amount), 0) AS total', "single")
		);

		if (!empty($loan_help_to_buycar_insurance) && isset($loan_help_to_buycar_insurance['total'])) {
			$totalLoanAmountCarInsurance = (float)$loan_help_to_buycar_insurance['total'];
		} else {
			$totalLoanAmountCarInsurance = 0;
		}

		$loan_help_to_buycar = $this->common->getData(
			'user_loan UL, user U',
			"UL.loan_type = '3' AND UL.user_id = U.user_id AND U.status != '2' AND UL.group_id != 34",
			array("field" => 'IFNULL(SUM(UL.loan_amount), 0) AS total', "single")
		);

		$loan_help_to_CC = $this->common->getData(
			'user_loan UL, user U',
			"UL.loan_type = '4' AND UL.user_id = U.user_id AND U.status != '2' AND UL.group_id != 34",
			array("field" => 'IFNULL(SUM(UL.loan_amount), 0) AS total', "single")
		);

		// Check if both results are arrays and have the 'total' key
		if (!empty($loan_help_to_buycar) && isset($loan_help_to_buycar['total']) && !empty($loan_help_to_CC) && isset($loan_help_to_CC['total'])) {
			$totalLoanAmountCc = (float)$loan_help_to_CC['total'];
			$totalLoanAmountCar = (float)$loan_help_to_buycar['total'] + $totalLoanAmountCc;
		} else {
			$totalLoanAmountCc = 0;
			$totalLoanAmountCar = 0;
		}

		$pendingRecommendedUsers = $this->pendingRecommendedUsersCount();

		$info = array(
			'loan_completed' => $loan_completed['total'],
			'loan_active' => $loan_completed['total'] - $loan_paid_user['total'],
			'loan_paid_user' => $loan_paid_user['total'],
			"groups" => count($group_data),
			'helptobuycar' => (string)$totalLoanAmountCar,
			'helptobuycarInsurance' => (string)$totalLoanAmountCarInsurance ?? '0',
			'helptobuycc' => (string)$totalLoanAmountCc,
			'pending_recommended_users' => $pendingRecommendedUsers,
		);

		$this->response(true, 'Dashboard third row fetch Successfully', array('info' => $info));
	}

	private function pendingRecommendedUsersCount()
	{
		$this->db->distinct();
		$this->db->select('RU.id');
		$this->db->from('recommend_user as RU');
		$this->db->join('recommendation_approvals as RA', 'RA.recommend_id = RU.id');
		$this->db->where('RA.status', '0');
		$this->db->where("(RU.signup_form != '1' OR RU.signup_form IS NULL)", null, false);
		$this->db->where("NOT EXISTS (SELECT 1 FROM recommendation_approvals RA_DECLINED WHERE RA_DECLINED.recommend_id = RU.id AND RA_DECLINED.status = '2')", null, false);
		$this->db->where("NOT EXISTS (SELECT 1 FROM recommendation_approvals RA_ADMIN WHERE RA_ADMIN.recommend_id = RU.id AND RA_ADMIN.approver_role = 'admin' AND RA_ADMIN.status = '1')", null, false);

		return $this->db->count_all_results();
	}

	// created by @krishn on 03-06-25
	public function dashboard_row_four()
	{
		ini_set('display_errors', 1);
		$savingJnrTotal = $this->savingJnrTotal();

		$loan_help_to_buyHSc = $this->common->getData(
			'user_loan UL, user U',
			"UL.loan_type = '6' AND UL.user_id = U.user_id AND U.status != '2' and UL.group_id != 34",
			array("field" => 'IFNULL(sum(UL.loan_amount),0) as total', "single")
		);

		if (is_array($loan_help_to_buyHSc) && isset($loan_help_to_buyHSc['total'])) {
			$totalLoanAmountBuyHSc = $loan_help_to_buyHSc['total'];
		} else {
			$totalLoanAmountBuyHSc = 0;
		}

		$cyclewelfare = $this->common->getData(
			'user_group_lifecycle UGL, user U',
			"UGL.groupLifecycle_id = '147' AND UGL.user_id = U.user_id AND U.status != '2' and UGL.group_id != 34",
			array("field" => 'IFNULL(sum(UGL.amount),0) as total', "single")
		);

		$miscellaneousTotal = $this->common->getData(
			'user_miscellaneous UM, user U',
			"UM.user_id = U.user_id AND U.status != '2' and UM.group_id != 34",
			array("field" => 'IFNULL(sum(UM.amount),0) as total', "single")
		);

		$totalMiscellaneousAmount = "";
		if (!empty($miscellaneousTotal['total'])) {
			$totalMiscellaneousAmount = $miscellaneousTotal['total'];
		} else {
			$totalMiscellaneousAmount = "0";
		}

		$dividendTotal = $this->common->getData(
			'investment',
			"group_id != 34 AND payment_status = '1'",
			array("field" => 'IFNULL(sum(amount),0) as total', "single")
		);


		$totalDividendAmount = "";
		if (!empty($dividendTotal['total'])) {
			$totalDividendAmount = $dividendTotal['total'];
		} else {
			$totalDividendAmount = "0";
		}

		$info = array(
			'savingJnrTotal' => (string)$savingJnrTotal,
			'help_to_buyHSc' => (string)$totalLoanAmountBuyHSc,
			"totalwelfare" => $cyclewelfare['total'],
			"totalMiscellaneousAmount" => $totalMiscellaneousAmount,
			"totalDividendAmount" => $totalDividendAmount
		);

		$this->response(true, 'Dashboard fourth row fetch Successfully', array('info' => $info));
	}

	public function getUserDetail()
	{
		$adminInfo = $this->adminDetailFunction($_REQUEST['user_id']);

		if (!empty($adminInfo)) {
			$this->response(true, 'Profile fetch successfully', array('userInfo' => $adminInfo));
		} else {
			$this->response(false, 'Profile not found');
		}
	}

	public function adminDetailFunction($admin_id)
	{
		$adminInfo = $this->common->getData('superAdmin', array('id' => $admin_id), array('single'));

		return $adminInfo;
	}


	public function updateProfile()
	{
		$user_id = $_REQUEST['user_id'];
		unset($_REQUEST['user_id']);
		$post = $this->common->getField('superAdmin', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('superAdmin', $post, array('id' => $user_id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Profile Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	// by group id enter all cycle 
	public function adduser_group_by_cycle()
	{
		$user_id = $_REQUEST['user_id'];
		$group_id = $_REQUEST['group_id'];
		$amount = $_REQUEST['amount'];

		$groupDetail = $this->common->getData('group_lifecycle', array('group_id' => $group_id));
		if (!empty($groupDetail)) {
			foreach ($groupDetail as $key => $value) {
				$x = 1;
				$cycleDate = $value['start_date'];
				while ($x <= $value['month_count']) {

					$cycleArr = array("group_id" => $group_id, "groupLifecycle_id" => $value['id'], "user_id" => $user_id, "amount" => $amount, "month" => $x, "created_at" => date('Y-m-d H:i:s'), "date" => $cycleDate);

					$post = $this->common->getField('user_group_lifecycle', $cycleArr);
					$result = $this->common->insertData('user_group_lifecycle', $post);
					$cycleDate = strtotime("+1 month", strtotime($cycleDate));
					$cycleDate = date("Y-m-d", $cycleDate);
					$x++;
				}
			}

			$this->response(true, "Insert Data Successfully");
		} else {
			$this->response(false, "Group Data Not Found");
		}
	}





	public function addMultipleuser_group_by_singlecycle()
	{
		$group_id = $_REQUEST['group_id'];
		$groupcycle_id = $_REQUEST['groupcycle_id'];
		$amount = $_REQUEST['amount'];

		$groupDetail = $this->common->getData('group_lifecycle', array('id' => $groupcycle_id), array('single'));

		if (!empty($groupDetail)) {

			$groupInfo = $this->common->getData('group_cycle', array('id' => $group_id), array('single'));

			if (!empty($groupInfo)) {
				$userArr = explode(',', $groupInfo['users']);
			} else {
				$userArr = array();
			}

			if (!empty($userArr)) {
				foreach ($userArr as $key => $user_id) {
					$x = 1;
					$cycleDate = $groupDetail['start_date'];
					while ($x <= $groupDetail['month_count']) {
						$cycleArr = array("group_id" => $groupDetail['group_id'], "groupLifecycle_id" => $groupDetail['id'], "user_id" => $user_id, "amount" => $amount, "month" => $x, "created_at" => date('Y-m-d H:i:s'), "date" => $cycleDate);

						$post = $this->common->getField('user_group_lifecycle', $cycleArr);
						$result = $this->common->insertData('user_group_lifecycle', $post);
						$cycleDate = strtotime("+1 month", strtotime($cycleDate));
						$cycleDate = date("Y-m-d", $cycleDate);
						$x++;
					}
				}
			}

			$this->response(true, "Insert Data Successfully");
		} else {
			$this->response(false, "Group Cycle Data Not Found");
		}
	}



	public function adduser_groupCycle()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		if ($_REQUEST['payment_method'] === '3' && ($_REQUEST['status'] == '2' || $_REQUEST['status'] == '3' || $_REQUEST['status'] == '4')) {
			$this->checkpaymentBySafekeeping($_REQUEST['amount']);
		}


		if ($_REQUEST['payment_method'] === '2' && ($_REQUEST['status'] == '2' || $_REQUEST['status'] == '3' || $_REQUEST['status'] == '4')) {
			$this->checkpaymentByPF($_REQUEST['amount']);
		}

		$post = $this->common->getField('user_group_lifecycle', $_REQUEST);
		$result = $this->common->insertData('user_group_lifecycle', $post);
		$id = $this->db->insert_id();
		if ($result) {
			if ($_REQUEST['payment_method'] === '3' && ($_REQUEST['status'] == '2' || $_REQUEST['status'] == '3' || $_REQUEST['status'] == '4')) {
				$this->paymentBySafekeeping($id, $_REQUEST['amount'], '2', '0');
			}

			if ($_REQUEST['payment_method'] === '2' && ($_REQUEST['status'] == '2' || $_REQUEST['status'] == '3' || $_REQUEST['status'] == '4')) {
				$this->paymentByPF($id, $_REQUEST['amount'], '2');
			}

			$this->common->insertData('user_cycle_status_history', array("lifecycle_id" => $id, "user_id" => $_REQUEST['user_id'], "note_title" => $_REQUEST['note'], "note_description" => $_REQUEST['note_description'], "status" => $_REQUEST['status'], "created_at" => date('Y-m-d H:i:s')));

			// if ($_REQUEST['status'] == '2') {
			// 	$data['status'] = 'Paid On Time';
			// }

			// if ($_REQUEST['status'] == '4') {
			// 	$data['status'] = 'Paid Late';
			// }

			// if ($_REQUEST['status'] == '3') {
			// 	$data['status'] = 'Declined Loan';
			// }

			// $userDetailTo = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

			// $data['username'] = $userDetailTo['first_name'] . " " . $userDetailTo['last_name'];
			// $data['amount'] = $_REQUEST['amount'];
			// $data['payment_date'] =  date('d M Y', strtotime($_REQUEST['date']));
			// $subject = "Payment Received";
			//changes 05-08
			//	$messaged = $this->load->view('template/payment-mail-received',$data,true);
			//	$mail = $this->sendMail($userDetailTo['email'],$subject,$messaged);



			$this->response(true, "Add User Cycle Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function edituser_groupCycle()
	{

		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);

		if ($_REQUEST['payment_method'] === '3' && ($_REQUEST['status'] == '2' || $_REQUEST['status'] == '3' || $_REQUEST['status'] == '4')) {
			$this->paymentBySafekeeping($id, $_REQUEST['amount'], '2', '0');
		}

		if ($_REQUEST['payment_method'] === '2' && ($_REQUEST['status'] == '2' || $_REQUEST['status'] == '3' || $_REQUEST['status'] == '4')) {
			$this->paymentByPF($id, $_REQUEST['amount'], '2');
		}
		$_REQUEST['updated_at'] = date("Y-m-d H:i:s");

		$post = $this->common->getField('user_group_lifecycle', $_REQUEST);
		if (!empty($post)) {
			$result = $this->common->updateData('user_group_lifecycle', $post, array('id' => $id));
		} else {
			$result = "";
		}
		$typename = '0';
		if ($result) {


			$this->common->insertData('user_cycle_status_history', array("lifecycle_id" => $id, "user_id" => $_REQUEST['user_id'], "note_title" => $_REQUEST['note'], "note_description" => $_REQUEST['note_description'], "status" => $_REQUEST['status'], "created_at" => date('Y-m-d H:i:s')));

			$message = "Your cycle info has been updated";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "1");


			$usergroupid = $this->common->getData('user_group_lifecycle', array('id' => $id), array('single'));

			$group_lifecycle = $this->common->getData('group_lifecycle', array('id' => $usergroupid['groupLifecycle_id']), array('single'));
			if ($group_lifecycle['group_type_id'] == '2') {
				$typename = '2';
			}

			if ($_REQUEST['status'] === '3' &&  $typename != '2') {

				$this->common->query_normal("UPDATE credit_score_user SET three_or_more_missed_savings_deadline = three_or_more_missed_savings_deadline+1 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");

				$creditScoreInfo = $this->common->getData('credit_score_user', array('user_id' => $_REQUEST['user_id']), array('single'));

				if (!empty($creditScoreInfo)) {

					if ($creditScoreInfo['three_or_more_missed_savings_deadline'] > 2) {
						$this->common->query_normal("UPDATE credit_score_user SET missed_savings_deadline = missed_savings_deadline-300 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");

						$this->updateCreditScore(300, 'minus');
					} else {
						$this->common->query_normal("UPDATE credit_score_user SET missed_savings_deadline = missed_savings_deadline-100 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
						$this->updateCreditScore(100, 'minus');
					}
				}
			}



			if ($_REQUEST['status'] === '2') {

				if ($typename != '2') {

					$this->common->query_normal("UPDATE credit_score_user SET saving_paid_on_time = saving_paid_on_time+20 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
					$this->updateCreditScore(20, 'plus');
				}

				$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

				$cycle_name = "";

				$usergroupid = $this->common->getData('user_group_lifecycle', array('id' => $id), array('single'));

				$group_lifecycle = $this->common->getData('group_lifecycle', array('id' => $usergroupid['groupLifecycle_id']), array('single'));

				$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
				$data['useremail'] = "";

				if ($group_lifecycle['group_type_id'] > '2') {
					$data['status'] = 'Paid On Time';

					if ($group_lifecycle['group_type_id'] === '3') {
						$cycle_name = 'Help to Buy';
					}
					if ($group_lifecycle['group_type_id'] === '4') {
						$cycle_name = 'WELFARE';
					}
					if ($group_lifecycle['group_type_id'] === '5') {
						$cycle_name = 'Help 2 Buy (Car)';
					}
					// $cycle_name = 'HELP2BUY payment';

					// $data['message'] = '<p>This is a confirmation that we have received and recorded your "' . $cycle_name . '" for this month. Refer to your app for confirmation</p>';

					$data['message'] = '<p>This is a confirmation that we have received and recorded your "' . $cycle_name . '" for this month. Refer to your app for confirmation</p>
						<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-top: 10px;">
							<tr>
								<td><strong>Amount paid</strong></td>
								<td>£' . $_REQUEST["amount"] . '</td>
							</tr>
							<tr>
								<td><strong>Payment date</strong></td>
								<td>' . date("d M Y", strtotime($_REQUEST["date"])) . '</td>
							</tr>
							<tr>
								<td><strong>Payment status</strong></td>
								<td>' . $data["status"] . '</td>
							</tr>
						</table>';
					$messaged = $this->load->view('template/common-mail', $data, true);
					$mail = $this->sendMail($userDetailFrom['email'], $cycle_name, $messaged);
				}
			}



			if ($_REQUEST['status'] === '4' && $group_lifecycle['group_type_id'] > '2') {

				$cycle_name = 'HELP2BUY payment';
				$data['status'] = 'Paid Late';

				$usersuper = $this->common->getData('superAdmin', array('admin_type' => '2', 'status!=' => '2'), array('single'));
				$data1['sendername'] = $usersuper['name'];
				$data1['useremail'] = "";

				$data1['message'] = '<p>This is a confirmation that we have received and recorded your "' . $cycle_name . '" for this month. Refer to your app for confirmation</p>
				<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-top: 10px;">
					<tr>
						<td><strong>Amount paid</strong></td>
						<td>£' . $_REQUEST["amount"] . '</td>
					</tr>
					<tr>
						<td><strong>Payment date</strong></td>
						<td>' . date("d M Y", strtotime($_REQUEST["date"])) . '</td>
					</tr>
					<tr>
						<td><strong>Payment status</strong></td>
						<td>' . $data["status"] . '</td>
					</tr>
				</table>';

				$messaged1 = $this->load->view('template/common-mail', $data1, true);
				$mail = $this->sendMail($usersuper['email'], $cycle_name, $messaged1);
			}


			$cycle_name = "";
			$group_lifecycle = "";
			$usergroupid = $this->common->getData('user_group_lifecycle', array('id' => $id), array('single'));

			$group_lifecycle = $this->common->getData('group_lifecycle', array('id' => $usergroupid['groupLifecycle_id']), array('single'));
			$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

			if ($group_lifecycle['group_type_id'] === '2' || $group_lifecycle['group_type_id'] === '1') {


				if ($group_lifecycle['group_type_id'] === '1') {
					$cycle_name = 'SAVINGS';
				}
				if ($group_lifecycle['group_type_id'] === '2') {
					$cycle_name = 'JNR SAVINGS';
				}

				if ($_REQUEST['status'] === '2') {
					$data['status'] = 'Paid on Time';
				}

				if ($_REQUEST['status'] === '4') {
					$data['status'] = 'Paid Late';
				}

				if ($_REQUEST['status'] === '3') {
					$data['status'] = 'Missed Payment Deadline';
				}
				$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
				$data['useremail'] = "";

				$data['message'] = '<p>This is a confirmation that we have received and recorded your "' . $cycle_name . '" for this month. Refer to your app for confirmation</p>
				<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-top: 10px;">
					<tr>
						<td><strong>Amount paid</strong></td>
						<td>£' . $_REQUEST["amount"] . '</td>
					</tr>
					<tr>
						<td><strong>Payment date</strong></td>
						<td>' . date("d M Y", strtotime($_REQUEST["date"])) . '</td>
					</tr>
					<tr>
						<td><strong>Payment status</strong></td>
						<td>' . $data["status"] . '</td>
					</tr>
				</table>';

				$messaged = $this->load->view('template/common-mail', $data, true);
				$mail = $this->sendMail($userDetailFrom['email'], $cycle_name, $messaged);
			}


			if ($_REQUEST['status'] === '4' &&  $typename != '2') {
				$this->common->query_normal("UPDATE credit_score_user SET late_savings_payment = late_savings_payment-30 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
				$this->updateCreditScore(30, 'minus');
			}
			//new-changes 12-06-2024
			if ($_REQUEST['status']) {
				$status = $_REQUEST['status'];
				$group_type_id = $group_lifecycle['group_type_id'];
				$group_id = $_REQUEST['group_id'];
				$created_at = date('Y-m-d H:i:s');
				$groupLifecycle_id = $usergroupid['groupLifecycle_id'];
				$user_id = $_REQUEST['user_id'];
				$month = $_REQUEST['month'];
				$amount = $_REQUEST['amount'];
				$result = $this->common->query_normal("INSERT INTO payment_notification(status,group_type_id,group_id,month,created_at,
               groupLifecycle_id,user_id,amount) VALUES('$status','$group_type_id','$group_id','$month','$created_at','$groupLifecycle_id','$user_id','$amount')");
			}

			// changes on 10-05-2024
			// $userDetailFrom = $this->common->getData('user',array('user_id'=>$_REQUEST['user_id']),array('single'));
			//  $checkuser = $this->common->getData('user_circle',array("user_id"=>$_REQUEST['user_id']),array('single'));
			//  if(!empty($checkuser)){
			//  $checkusercircle = $this->common->getData('user_circle',array("circle_id"=>$checkuser['circle_id']),array());
			//   if($checkusercircle){
			//       foreach($checkusercircle as $value){
			//           $chuser = $this->common->getData('user',array("user_id"=>$value['user_id']),array('single'));
			//             $data['sendername'] = $userDetailFrom['first_name']." ".$userDetailFrom['last_name'];
			// 			$data['useremail'] = "";
			// 			if($group_lifecycle['group_type_id'] > '2' ){
			//             $cycle_name = 'HELP2BUY payment';
			//             }
			// 		    if($_REQUEST['status'] ==='4'){
			//     	$data['message'] = '<p>Hello Team members Someone in your circle has paid late</p>';
			//     	$messaged = $this->load->view('template/common-mail',$data,true);
			//          $mail = $this->sendMail($chuser['email'],$cycle_name,$messaged);
			//             }
			//             if($_REQUEST['status'] ==='3'){
			//     $data['message'] = '<p>Hello Team members Someone in your circle has missed a payment</p>';
			//     $messaged = $this->load->view('template/common-mail',$data,true);
			//       $mail = $this->sendMail($chuser['email'],$cycle_name,$messaged);
			//             }


			//       }
			//   }
			//  }

			$this->response(true, "User Cycle Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	// updated by @krishn on 17-06-26
	function updateCreditScore($points, $calculation_type)
	{
		$result = $this->common->getData(
			'credit_score_user',
			array('user_id' => $_REQUEST['user_id']),
			array('single')
		);

		if (empty($result)) {
			return;
		}

		$totalScore = (int)$result['total_credit_score'];
		$credit_score = array();

		// Calculate new score
		if ($calculation_type === 'plus') {
			$totalScore += (int)$points;
		} elseif ($calculation_type === 'minus') {
			$totalScore -= (int)$points;
		}

		// Maintain score between 0 and 900
		if ($totalScore < 0) {
			$newScore = 0;
		} elseif ($totalScore > 900) {
			$newScore = 900;
		} else {
			$newScore = $totalScore;
		}

		// Get score levels
		$result1 = $this->common->getData('credit_score_list', array(), array());

		$currentLevel = '';
		$newLevel = '';

		foreach ($result1 as $value) {

			$min = (int)$value['score1'];
			$max = (int)$value['score2'];

			if ($newScore >= $min && $newScore <= $max) {
				$newLevel = $value['credit_score_name'];
			}

			if ($result['total_credit_score'] >= $min && $result['total_credit_score'] <= $max) {
				$currentLevel = $value['credit_score_name'];
			}
		}

		// Send email only if level changed
		if (!empty($newLevel) && $newLevel != $currentLevel) {

			$userDetailFrom = $this->common->getData(
				'user',
				array('user_id' => $_REQUEST['user_id']),
				array('single')
			);

			if (!empty($userDetailFrom)) {

				$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
				$data['useremail'] = "";

				if ($calculation_type === 'plus') {

					$data['message'] =
						'<p>Congratulations ' . $userDetailFrom["first_name"] . '. You have reached another milestone as a valued Interfriends member.</p>
					<p>Your Trust Score has moved up a level to ' . $newLevel . '.</p>
					<p>Well done on behalf of all of us at Interfriends.</p>
					<p>Keep it up.</p>';
				} else {

					$data['message'] =
						'<p>We regret to inform you that there has been a decrease in your Interfriends Trust Score.</p>
					<p>This change may have occurred as a result of various factors, such as a recent application or delayed or missed payments.</p>';
				}

				$messaged = $this->load->view('template/common-mail', $data, true);

				$this->sendMail(
					$userDetailFrom['email'],
					'Trust Score',
					$messaged
				);
			}
		}

		// Update score
		$this->common->updateData(
			'credit_score_user',
			array("total_credit_score" => $newScore),
			array('user_id' => $_REQUEST['user_id'])
		);
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


	public function lastTransactionsUserCycle()
	{
		$groupCycleList = $this->common->getData('user_group_lifecycle', array('group_id' => $_REQUEST['group_id'], 'groupLifecycle_id' => $_REQUEST['groupLifecycle_id'], 'user_id' => $_REQUEST['user_id']), array(
			'sort_by' => 'id',
			'sort_direction' => 'desc',
			'limit' => '5',
			''
		));

		if (!empty($groupCycleList)) {
			$this->response(true, 'group fetch successfully', array('groupCycleList' => $groupCycleList));
		} else {
			$this->response(true, 'group not found', array('groupCycleList' => array()));
		}
	}


	public function getCycleTotalAmount()
	{
		$info = [];
		$result = $this->common->getData('user_group_lifecycle', array('groupLifecycle_id' => $_REQUEST['groupLifecycle_id'], 'user_id' => $_REQUEST['user_id'], 'is_completed' => 0), array('field' => 'SUM(amount) as total_amount', 'single'));

		if (!empty($result)) {
			$groupPercentResult = $this->user_model->getLifeCyclePercent(array('GL.id' => $_POST['groupLifecycle_id']), array('single'));
			$amount_total = $result['total_amount'];

			$pfPercent = 10;
			if (!empty($groupPercentResult)) {
				$pfPercent = (int)$groupPercentResult['percent'];
			}

			if (!empty($pfPercent)) {
				$pf_amount = ($result['total_amount'] * $pfPercent) / 100;
				$totalArr['amount'] = $amount_total - $pf_amount;
				$totalArr['pf_interest_amount'] = ($pf_amount * $pfPercent) / 100;
			} else {
				$pf_amount = 0;
				$totalArr['amount'] = $amount_total;
				$totalArr['pf_interest_amount'] = 0;
			}

			$totalArr['pf_percent'] = $pfPercent . "%";
			$totalArr['amount_total'] = $amount_total;
			$totalArr['pf_amount'] = $pf_amount;
			$totalArr['pf_interest_percent'] =  $pfPercent . "%";

			$userInfo = $this->common->getData('user_group', array('user_id' => $_REQUEST['user_id'], 'group_id' => $_REQUEST['group_id']), array('single'));

			if (!empty($userInfo)) {
				$totalArr['expected_date'] =  $userInfo['expected_date'];
			}


			$this->response(true, 'group fetch successfully', array('info' => $totalArr));
		} else {
			$this->response(true, 'group not found', array('info' => array()));
		}
	}


	public function groupUser_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		// new-changes add status
		// $where = "UG.group_id = '" . $_REQUEST['group_id'] . "' and U.status != '2'";

		// if (!empty($_REQUEST['search_keyword'])) {
		// 	$where .= " AND  (U.first_name LIKE '%" . $_REQUEST['search_keyword'] . "%' OR U.last_name LIKE '%" . $_REQUEST['search_keyword'] . "%' OR U.email LIKE '%" . $_REQUEST['search_keyword'] . "%') ";
		// }

		// new-changes add status
		$where = "UG.group_id = '" . $_REQUEST['group_id'] . "' and U.status != '2'";

		if (!empty($_REQUEST['search_keyword'])) {
			$where .= " AND  (U.first_name LIKE '%" . $_REQUEST['search_keyword'] . "%' OR U.last_name LIKE '%" . $_REQUEST['search_keyword'] . "%' OR U.email LIKE '%" . $_REQUEST['search_keyword'] . "%') ";
		}

		// Add Date Range Filter Here
		if (!empty($_REQUEST['date_range'])) {
			$date_range = explode(',', $_REQUEST['date_range']);
			if (count($date_range) == 2) {
				$startDate = trim($date_range[0]);
				$endDate = trim($date_range[1]);
				$where .= " AND DATE(UG.expected_date) BETWEEN '$startDate' AND '$endDate'";
			}
		}


		$result = $this->user_model->user_group_detail($where, array(), $start, $end);
		$userCount = $this->user_model->user_group_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				if (!empty($value['profile_image'])) {
					$result[$key]['profile_image'] = base_url($value['profile_image']);
					$result[$key]['profile_image_thumb'] = base_url($value['profile_image']);
				} else {
					$result[$key]['profile_image'] = "assets/img/default-user-icon.jpg";
					$result[$key]['profile_image_thumb'] = "assets/img/default-user-icon.jpg";
				}

				$result[$key]['sno'] = $countData++;
			}
			$this->response(true, "User fetch Successfully.", array("userList" => $result, "userCount" => $userCount));
		} else {
			$this->response(true, "User fetch Successfully.", array("userList" => array(), "userCount" => $userCount));
		}
	}


	public function resetPassword()
	{

		$result = $this->common->getData('superAdmin', array('password' => md5($_POST['oldpassword']), 'id' => $_REQUEST['user_id']), array('single'));

		if (!empty($result)) {
			$a = array('password' => md5($_POST['password']));
			$result = $this->common->updateData('superAdmin', $a, array('id' => $_REQUEST['user_id']));
			if ($result) {
				$this->response(true, 'Profile update successfully');
			} else {
				$this->response(false, 'some error occured. Please try again.');
			}
		} else {
			$this->response(false, 'Current password is incorrect.');
		}
	}


	public function user_list_subadmin()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = " (U.subadmin_status = 2 OR U.subadmin_status=1) OR (U.status = '2')";

		if (!empty($_REQUEST['search_keyword'])) {
			$where .= " AND  (U.first_name LIKE '%" . $_REQUEST['search_keyword'] . "%' OR U.last_name LIKE '%" . $_REQUEST['search_keyword'] . "%' OR U.email LIKE '%" . $_REQUEST['search_keyword'] . "%') ";
		}


		if (!empty($_REQUEST['group_id_not'])) {
			$where .= " AND U.user_id not in (Select user_id from user_group )";
		}

		if (!empty($_REQUEST['group_id'])) {
			$where .= " AND U.user_id in (Select user_id from user_group where group_id=" . $_REQUEST['group_id'] . ")";
		}



		$result = $this->user_model->user_detail($where, array(), $start, $end);

		$userCount = $this->user_model->user_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				if (!empty($value['profile_image'])) {
					$result[$key]['profile_image'] = base_url($value['profile_image']);
					$result[$key]['profile_image_thumb'] = base_url($value['profile_image']);
				} else {
					$result[$key]['profile_image'] = "assets/img/default-user-icon.jpg";
					$result[$key]['profile_image_thumb'] = "assets/img/default-user-icon.jpg";
				}

				$result[$key]['sno'] = $countData++;
			}
			$this->response(true, "User fetch Successfully.", array("userList" => $result, "userCount" => $userCount));
		} else {
			$this->response(true, "User fetch Successfully.", array("userList" => array(), "userCount" => $userCount));
		}
	}

	// created by @krishn on 21-05-25
	public function default_user_list_subadmin()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = " (U.subadmin_is_default = 2 OR U.subadmin_is_default = 1) OR (U.is_default = '2')";

		if (!empty($_REQUEST['search_keyword'])) {
			$where .= " AND  (U.first_name LIKE '%" . $_REQUEST['search_keyword'] . "%' OR U.last_name LIKE '%" . $_REQUEST['search_keyword'] . "%' OR U.email LIKE '%" . $_REQUEST['search_keyword'] . "%') ";
		}


		if (!empty($_REQUEST['group_id_not'])) {
			$where .= " AND U.user_id not in (Select user_id from user_group )";
		}

		if (!empty($_REQUEST['group_id'])) {
			$where .= " AND U.user_id in (Select user_id from user_group where group_id=" . $_REQUEST['group_id'] . ")";
		}



		$result = $this->user_model->user_detail($where, array(), $start, $end);

		$userCount = $this->user_model->user_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				if (!empty($value['profile_image'])) {
					$result[$key]['profile_image'] = base_url($value['profile_image']);
					$result[$key]['profile_image_thumb'] = base_url($value['profile_image']);
				} else {
					$result[$key]['profile_image'] = "assets/img/default-user-icon.jpg";
					$result[$key]['profile_image_thumb'] = "assets/img/default-user-icon.jpg";
				}

				$result[$key]['sno'] = $countData++;
			}
			$this->response(true, "User fetch Successfully.", array("userList" => $result, "userCount" => $userCount));
		} else {
			$this->response(true, "User fetch Successfully.", array("userList" => array(), "userCount" => $userCount));
		}
	}


	public function user_list()
	{
		// limit code start
		$start = !empty($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 10;
		$end   = !empty($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
		// limit code end

		// new-changes add block status   
		$where = "(U.status != 2)";

		if (!empty($_REQUEST['search_keyword'])) {
			$keyword = $_REQUEST['search_keyword'];
			$where .= " AND (
				U.first_name LIKE '%$keyword%' OR
				U.last_name LIKE '%$keyword%' OR
				U.email LIKE '%$keyword%' OR
				(SELECT total_credit_score FROM credit_score_user WHERE user_id = U.user_id) LIKE '%$keyword%'
			)";
		}

		if (!empty($_REQUEST['group_id_not'])) {
			$where .= " AND U.user_id not in (Select user_id from user_group )";
		}

		if (!empty($_REQUEST['group_id'])) {
			$where .= " AND U.user_id in (Select user_id from user_group where group_id=" . $_REQUEST['group_id'] . ")";
		}

		$where .= " and recommended = '0' ";

		// if (!empty($_REQUEST['group_ids'])) {
		// 	$group_ids = explode(',', $_REQUEST['group_ids']);
		// 	$group_ids = array_map('trim', $group_ids);      // Remove whitespace
		// 	$group_ids = array_map('intval', $group_ids);    // Ensure integers

		// 	$where = "U.group_id IN (" . implode(',', $group_ids) . ")";
		// 	$result = $this->user_model->user_detail($where, array(), $start, $end);
		// 	$userCount = $this->user_model->user_detail($where, array('count'));
		// } else {
		// 	$result = $this->user_model->user_detail(array(), array(), $start, $end);
		// 	$userCount = $this->user_model->user_detail(array(), array('count'));
		// }

		if (!empty($_REQUEST['group_ids'])) {
			$group_ids = explode(',', $_REQUEST['group_ids']);
			$group_ids = array_map('trim', $group_ids);      // Remove whitespace
			$group_ids = array_map('intval', $group_ids);    // Sanitize to integers

			$group_condition = "GC.group_id IN (" . implode(',', $group_ids) . ")";
			$where .= " AND $group_condition";
		}

		if (!empty($_REQUEST['circle_ids'])) {
			$circle_ids = explode(',', $_REQUEST['circle_ids']);
			$circle_ids = array_map('trim', $circle_ids);      // Remove whitespace
			$circle_ids = array_map('intval', $circle_ids);    // Sanitize to integers

			$circle_condition = "UC.circle_id IN (" . implode(',', $circle_ids) . ")";
			$where .= " AND $circle_condition";
		}

		$result = $this->user_model->user_detail($where, array(), $start, $end);
		$userCount = $this->user_model->user_detail($where, array('count'));

		// $result = $this->user_model->user_detail($where, array(), $start, $end);

		// $userCount = $this->user_model->user_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				if (!empty($value['profile_image'])) {
					$result[$key]['profile_image'] = base_url($value['profile_image']);
					$result[$key]['profile_image_thumb'] = base_url($value['profile_image']);
				} else {
					$result[$key]['profile_image'] = "assets/img/default-user-icon.jpg";
					$result[$key]['profile_image_thumb'] = "assets/img/default-user-icon.jpg";
				}

				// $groupCycleList = $this->common->getData('user_group_lifecycle', array('group_id' => '22', 'user_id' =>  $value['user_id'], 'updated_at != ' => null), array('sort_by' => 'updated_at', 'sort_direction' => 'asc', 'limit' => '6', ''));
				$groupCycleList = $this->common->getData(
					'user_group_lifecycle',
					array(
						'group_id' => '22',
						'user_id' => $value['user_id'],
						'updated_at !=' => null
					),
					array(
						'sort_by' => 'updated_at',
						'sort_direction' => 'desc',
						'limit' => 6
					)
				);

				// print_r($this->db->last_query());
				// die;

				$result[$key]['lastTransactionsUserCycle'] = $groupCycleList;

				$result[$key]['sno'] = $countData++;

				$result[$key]['circle_name'] = $value['circle_name'] ?? '';
			}
			$this->response(true, "User fetch Successfully.", array("userList" => $result, "userCount" => $userCount));
		} else {
			$this->response(true, "User fetch Successfully.", array("userList" => array(), "userCount" => $userCount));
		}
	}







	public function subadmin_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "admin_type = '1'";

		if (!empty($_REQUEST['search_keyword'])) {
			$where .= " AND (SA.name LIKE '%" . $_REQUEST['search_keyword'] . "%' OR SA.email LIKE '%" . $_REQUEST['search_keyword'] . "%' OR SA.phone LIKE '%" . $_REQUEST['search_keyword'] . "%') ";
		}

		$result = $this->user_model->subadmin_detail($where, array(), $start, $end);
		$userCount = $this->user_model->subadmin_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {
			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}
			$this->response(true, "User fetch Successfully.", array("userList" => $result, "userCount" => $userCount));
		} else {
			$this->response(true, "User fetch Successfully.", array("userList" => array(), "userCount" => $userCount));
		}
	}

	// created by @krishn on 29-06-25
	public function userDetailInfo()
	{
		if (empty($_REQUEST['user_id'])) {
			$this->response(false, "User ID is required.", array("userinfo" => array()));
			return;
		}

		$where = "U.user_id = '" . $_REQUEST['user_id'] . "'";

		$result = $this->user_model->user_detail($where, array('single'));

		if (!empty($result)) {

			// Profile Image
			if (!empty($result['profile_image'])) {
				$result['profile_image'] = base_url($result['profile_image']);
			} else {
				$result['profile_image'] = base_url('assets/img/default-user-icon.jpg');
			}

			// Profile Thumbnail
			if (!empty($result['profile_image_thumb'])) {
				$result['profile_image_thumb'] = base_url($result['profile_image_thumb']);
			} else {
				$result['profile_image_thumb'] = base_url('assets/img/default-user-icon.jpg');
			}

			// ID Proof Image
			if (!empty($result['id_proof_image'])) {
				$result['id_proof_image'] = base_url($result['id_proof_image']);
			} else {
				$result['id_proof_image'] = base_url('assets/img/blank.webp');
			}

			$this->response(
				true,
				"Profile Fetch Successfully.",
				array("userinfo" => $result)
			);
		} else {

			$this->response(
				false,
				"There Is Some Problem. Please Try Again.",
				array("userinfo" => array())
			);
		}
	}


	public function category_detail()
	{
		$result = $this->common->getData('category', array('category_id' => $_REQUEST['category_id']), array('single'));

		if (!empty($result)) {
			if (!empty($result['category_image'])) {
				$result['category_image'] = base_url($result['category_image']);
				$result['category_image_thumb'] = base_url($result['category_image_thumb']);
			} else {
				$result['category_image'] = 'assets/img/default-user-icon.jpg';
				$result['category_image_thumb'] = 'assets/img/default-user-icon.jpg';
			}
			$this->response(true, "Category fetch Successfully.", array("categoryDetail" => $result));
		} else {
			$this->response(false, "Category not found", array("categoryDetail" => array()));
		}
	}


	public function plan_detail()
	{
		$result = $this->common->getData('membership_plan', array('id' => $_REQUEST['plan_id']), array('single'));
		if (!empty($result)) {
			$this->response(true, "Plan fetch Successfully.", array("planDetail" => $result));
		} else {
			$this->response(false, "Plan not found", array("planDetail" => array()));
		}
	}


	public function addCategory()
	{
		$iname = '';
		$iname_thumb = '';
		if (isset($_FILES['image'])) {
			$image = $this->common->do_upload_thumb('image', './assets/category/');
			if (isset($image['upload_data'])) {
				$iname = 'assets/category/' . $image['upload_data']['file_name'];
				$iname_thumb = 'assets/category/thumb/' . $image['upload_data']['file_name'];
			}
		}

		$_REQUEST['category_image'] = $iname;
		$_REQUEST['category_image_thumb'] = $iname_thumb;
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		$post = $this->common->getField('category', $_REQUEST);
		$result = $this->common->insertData('category', $post);

		if ($result) {
			$this->response(true, "add category successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	public function addProperty()
	{
		$iname = '';
		$iname_thumb = '';
		if (isset($_FILES['image'])) {
			$image = $this->common->do_upload_thumb('image', './assets/property/');
			if (isset($image['upload_data'])) {
				$iname = 'assets/property/' . $image['upload_data']['file_name'];
				$iname_thumb = 'assets/property/thumb/' . $image['upload_data']['file_name'];
			}
		}

		if (!empty($_FILES['background_image'])) {
			$background_image = $this->common->multi_upload('background_image', './assets/property/background/');
		}

		$_REQUEST['property_image'] = $iname;
		$_REQUEST['property_image_thumb'] = $iname_thumb;
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		$end_date = strtotime("+" . $_REQUEST['property_tenure'] . " month", strtotime($_REQUEST['start_date']));
		$_REQUEST['end_date'] = date("Y-m-d", $end_date);

		$post = $this->common->getField('property', $_REQUEST);
		$result = $this->common->insertData('property', $post);


		if ($result) {
			$property_id = $this->db->insert_id();

			$userData = $this->common->getData('user', array('status' => '1'));

			// add notification for users
			if (!empty($userData)) {
				foreach ($userData as $users) {
					$notification = "New Investment Arrived";
					$this->send_nofification($users['user_id'], $_REQUEST['admin_id'], '22', $notification, $property_id, "16");

					$this->common->updateData('user', array('is_investment_popup' => 1), array('user_id' => $users['user_id']));
				}
			}

			if (!empty($background_image)) {
				foreach ($background_image as $keyimg) {
					$data_img['background_image'] = 'assets/property/background/' . $keyimg['file_name'];
					$data_img['property_id'] = $property_id;
					$result_other = $this->common->insertData('property_image_tbl', $data_img);
				}
			}

			// send email to all users who are registered in the investment @krishn on 20-05-25
			// $investmentUsers = $this->user_model->investment_request_detail();

			// if (!empty($investmentUsers)) {
			// 	$propertyInfo = $this->common->getData('property', array('id' => $property_id), array('single'));
			// 	print_r($propertyInfo); die();
			// 	foreach ($investmentUsers as $user) {
			// 		$subject = 'Investment Request Update';
			// 		$data['sendername'] = $user['first_name'] . ' ' . $user['last_name'];

			// 		$data['message'] = '</p> <p>Thank you for showing interest in the project titled "<strong>' . $propertyInfo['title'] . '</strong>".</p>
			// 		<p>We appreciate your investment of <strong>£' . $propertyInfo['main_amount'] . '</strong>.</p>
			// 		<p><strong>Project Description:</strong> ' . $propertyInfo['short_description'] . '</p>
			// 		<p>We will keep you updated with further developments.</p>';

			// 		$messaged = $this->load->view('template/common-mail', $data, true);
			// 		$this->sendMail($user['email'], $subject, $messaged);
			// 	}
			// }

			$this->response(true, "add property successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function editProperty()
	{
		$property_id = $_REQUEST['property_id'];
		unset($_REQUEST['property_id']);

		if (isset($_FILES['image'])) {
			$image = $this->common->do_upload_thumb('image', './assets/property/');
			if (isset($image['upload_data'])) {
				$iname = 'assets/property/' . $image['upload_data']['file_name'];
				$iname_thumb = 'assets/property/thumb/' . $image['upload_data']['file_name'];
				$_REQUEST['property_image'] = $iname;
				$_REQUEST['property_image_thumb'] = $iname_thumb;
			}
		}


		if (!empty($_FILES['background_image'])) {
			$background_image = $this->common->multi_upload('background_image', './assets/property/background/');
		}

		$end_date = strtotime("+" . $_REQUEST['property_tenure'] . " month", strtotime($_REQUEST['start_date']));
		$_REQUEST['end_date'] = date("Y-m-d", $end_date);

		$post = $this->common->getField('property', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('property', $post, array('id' => $property_id));
		} else {
			$result = "";
		}

		if ($result) {
			if (!empty($background_image)) {
				foreach ($background_image as $keyimg) {
					$data_img['background_image'] = 'assets/property/background/' . $keyimg['file_name'];
					$data_img['property_id'] = $property_id;
					$result_other = $this->common->insertData('property_image_tbl', $data_img);
				}
			}
			$this->response(true, "Property Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function property_detail()
	{
		$result = $this->common->getData('property', array('id' => $_REQUEST['property_id']), array('single'));

		if (!empty($result)) {
			if (!empty($result['property_image'])) {
				$result['property_image'] = base_url($result['property_image']);
				$result['property_image_thumb'] = base_url($result['property_image_thumb']);
			} else {
				$result['property_image'] = 'assets/img/default-user-icon.jpg';
				$result['property_image_thumb'] = 'assets/img/default-user-icon.jpg';
			}

			$property_image_array = $this->common->getData('property_image_tbl', array('property_id' => $_REQUEST['property_id']));

			if (!empty($property_image_array)) {
				foreach ($property_image_array as $key => $value) {
					$property_image_array[$key]['background_image'] =  base_url($value['background_image']);
				}
			} else {
				$property_image_array =  array();
			}

			$result['background_image'] = $property_image_array;
			$this->response(true, "Property fetch Successfully.", array("propertyDetail" => $result));
		} else {
			$this->response(false, "Property not found", array("propertyDetail" => array()));
		}
	}

	public function logout()
	{
		if (!empty($_REQUEST['user_id'])) {
			$user_id = $_REQUEST['user_id'];
			$this->common->updateData('superAdmin', array("web_token" => ""), array('id' => $user_id));

			$this->response(true, "Logout Successfully");
		} else {
			$this->response(false, "Missing Parameter.");
		}
	}


	public function delete_property_image()
	{
		if (!empty($_REQUEST['property_image_id'])) {
			$property_image_id = $_REQUEST['property_image_id'];
			$where = "property_image_id	='" . $property_image_id . "'";
			$value = $this->common->deleteData('property_image_tbl', $where);
			$this->response(true, "Delete Successfully.");
		} else {
			$this->response(false, "Missing Parameter.");
		}
	}



	public function blockUnblockProperty()
	{
		$this->common->updateData('property', array('status' => $_REQUEST['status']), array('id' => $_REQUEST['id']));

		if ($_REQUEST['status'] == 1) {
			$this->response(true, 'Property Unblocked Successfully', array('status' => $_REQUEST['status']));
		} else {
			$this->response(true, 'Property Blocked Successfully', array('status' => $_REQUEST['status']));
		}
	}

	public function openCloseProperty()
	{
		$this->common->updateData('property', array('is_closed' => $_REQUEST['is_closed']), array('id' => $_REQUEST['id']));

		if ($_REQUEST['is_closed'] == 1) {
			$this->response(true, 'Property Closed Successfully', array('is_closed' => $_REQUEST['is_closed]']));
		} else {
			$this->response(true, 'Property Opened Successfully', array('is_closed' => $_REQUEST['is_closed']));
		}
	}


	public function property_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = '';
		$result = $this->user_model->property_detail($where, array(), $start, $end);
		$resultCount = $this->user_model->property_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
				if (!empty($value['property_image'])) {
					$result[$key]['property_image'] = base_url($value['property_image']);
					$result[$key]['property_image_thumb'] = base_url($value['property_image_thumb']);
				} else {
					$result[$key]['property_image'] = 'assets/img/default-user-icon.jpg';
					$result[$key]['property_image_thumb'] = 'assets/img/default-user-icon.jpg';
				}
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount));
		}
	}



	public function addPlan()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		$post = $this->common->getField('membership_plan', $_REQUEST);
		$result = $this->common->insertData('membership_plan', $post);

		if ($result) {
			$this->response(true, "Add Plan Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function getallusersCycleAmount($result_users, $result)
	{
		$amount = "";
		$amount_total = 0;
		$calculation1 = array();
		if (!empty($result)) {

			foreach ($result as $key => $value) {


				if (!empty($result_users)) {

					foreach ($result_users as $value_user) {

						$calculation = $this->common->getData('user_group_lifecycle', array('groupLifecycle_id' => $value['id'], 'user_id' => $value_user['user_id']), array('field' => 'SUM(amount) as total_amount', 'single'));

						$amount_total +=  $calculation['total_amount'];
						// $calculation1['user_id'] =  $value_user['user_id'];
						// $calculation1['amount_total'] =$calculation['total_amount'];
						//print_r($calculation1);
					}
				} else {
					$amount_total  = 0;
				}
			}
			$amount = $amount_total;
		} else {
			$amount = 0;
		}
		return $amount;
	}

	public function group_detail()
	{
		$result = $this->common->getData('group_cycle', array('id' => $_REQUEST['id']), array('single'));
		if (!empty($result)) {

			$where = "UG.group_id = '" . $_REQUEST['id'] . "'";
			$result_users = $this->user_model->user_group_detail($where, array(), '', '');

			$result1 = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['id'], "group_type_id" => '1'));
			$result2 = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['id'], "group_type_id" => '2'));
			$result3 = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['id'], "group_type_id" => '3'));

			$data1 = 0;
			$data2 = 0;
			$data3 = 0;
			// $data1 = $this->getallusersCycleAmount($result_users, $result1);
			// $data2 = $this->getallusersCycleAmount($result_users, $result2);
			// $data3 = $this->getallusersCycleAmount($result_users, $result3);

			if (!empty($result['users'])) {
				$where = "user_id IN (" . $result['users'] . ")";
				$user_list = $this->common->getData('user', $where);

				$newArray = array();
				if (!empty($user_list)) {
					$i = 0;
					foreach ($user_list as $key => $value) {
						$newArray[$i]['item_id'] = $value['user_id'];
						$newArray[$i]['item_text'] = $value['first_name'];
						$i++;
					}
				}
			} else {
				$newArray = array();
			}

			$this->response(true, "group fetch Successfully.", array("groupDetail" => $result, 'members' => $newArray, 'total_users' => sizeof($result_users), 'user_cycle' => number_format($data1, 2), 'user_cycle_jnr' => number_format($data2, 2), 'user_cycle_help_to_buy' => number_format($data3, 2)));
		} else {
			$this->response(false, "Group not found", array("groupDetail" => array()));
		}
	}


	public function addGroup()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		$post = $this->common->getField('group_cycle', $_REQUEST);
		$result = $this->common->insertData('group_cycle', $post);
		$group_id = $this->db->insert_id();

		if ($result) {
			$this->response(true, "Add Group Successfully", array("group_id" => $group_id));
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	public function addMiscellaneous()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$_REQUEST['active_date'] = date('Y-m-d H:i:s');
		$_REQUEST['total_payment'] = $_REQUEST['amount'];

		$current_date = $_REQUEST['start_date'];
		$end_date = strtotime("+" . $_REQUEST['tenure'] . " month", strtotime($current_date));
		$_REQUEST['end_date'] = date("Y-m-d", $end_date);
		$_REQUEST['start_date'] = $current_date;

		$_REQUEST['loan_emi'] = $_REQUEST['total_payment'] / $_REQUEST['tenure'];

		$post = $this->common->getField('user_miscellaneous', $_REQUEST);
		$result = $this->common->insertData('user_miscellaneous', $post);
		$miscellaneous_id = $this->db->insert_id();

		if ($result) {

			$this->common->insertData('user_miscellaneous_status_history', array("miscellaneous_id" => $miscellaneous_id, "user_id" => $_REQUEST['user_id'], "note_title" => $_REQUEST['note_title'], "note_description" => $_REQUEST['note_description'], "status" => $_REQUEST['status'], "created_at" => date('Y-m-d H:i:s')));
			$this->response(true, "Miscellaneous add submitted successfully", array("miscellaneous_id" => $miscellaneous_id));
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	// changes by chandni 07-05-2024
	public function editMiscellaneous()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);

		if ($_REQUEST['payment_method'] === '3' && ($_REQUEST['status'] == '2')) {
			$this->paymentBySafekeeping($id, $_REQUEST['amount'], '4', '0');
		}

		if ($_REQUEST['payment_method'] === '2' && ($_REQUEST['status'] == '2')) {
			$this->paymentByPF($id, $_REQUEST['amount'], '4');
		}

		if ($_REQUEST['status'] === '4') {
			$_REQUEST['active_date'] = date('Y-m-d H:i:s');
		}


		if ($_REQUEST['paid_status'] === '2') {
			$this->common->query_normal("UPDATE credit_score_user SET late_misc_payment = late_misc_payment-60 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
			$this->updateCreditScore(60, 'minus');
			$_REQUEST['active_date'] = date('Y-m-d H:i:s');
		}

		if ($_REQUEST['paid_status'] === '3') {

			$this->common->query_normal("UPDATE credit_score_user SET three_or_more_missed_misc_deadline = three_or_more_missed_misc_deadline+1 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");

			$creditScoreInfo = $this->common->getData('credit_score_user', array('user_id' => $_REQUEST['user_id']), array('single'));

			if (!empty($creditScoreInfo)) {

				if ($creditScoreInfo['three_or_more_missed_savings_deadline'] > 2) {
					$this->common->query_normal("UPDATE credit_score_user SET missed_misc_deadline = missed_misc_deadline-300 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");

					$this->updateCreditScore(300, 'minus');
				} else {
					$this->common->query_normal("UPDATE credit_score_user SET missed_misc_deadline = missed_misc_deadline-100 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
					$this->updateCreditScore(100, 'minus');
				}
			}
		}


		if ($_REQUEST['paid_status'] === '1') {

			$this->common->query_normal("UPDATE credit_score_user SET misc_paid_on_time = misc_paid_on_time+20 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
			$this->updateCreditScore(20, 'plus');
		}


		if ($_REQUEST['status'] === '2') {
			$_REQUEST['complete_date'] = date('Y-m-d H:i:s');
		}

		$post = $this->common->getField('user_miscellaneous', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('user_miscellaneous', $post, array('id' => $id));
		} else {
			$result = "";
		}

		$this->common->insertData('user_miscellaneous_status_history', array("miscellaneous_id" => $id, "user_id" => $_REQUEST['user_id'], "note_title" => $_REQUEST['note_title'], "note_description" => $_REQUEST['note_description'], "status" => $_REQUEST['status'], "created_at" => date('Y-m-d H:i:s')));
		//new-changes 12-06-2024
		if ($_REQUEST['status']) {

			$getloan = $this->common->getData('user_miscellaneous', array('id' => $id), array('single'));

			$status = $_REQUEST['status'];
			$loan_type = '9';
			$group_id = $_REQUEST['group_id'];
			$created_at = date('Y-m-d H:i:s');
			$loan_id = $id;
			$user_id = $_REQUEST['user_id'];
			$month = $getloan['start_date'];
			$amount = $_REQUEST['amount'];
			$result1 = $this->common->query_normal("INSERT INTO payment_notification(status,group_id,month,created_at,
               user_id,amount,loan_type,loan_id) VALUES('$status','$group_id','$month','$created_at','$user_id','$amount','$loan_type','$loan_id')");
		}
		if ($result) {
			$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));
			$checkuser = $this->common->getData('user_circle', array("user_id" => $_REQUEST['user_id']), array('single'));
			if (!empty($checkuser)) {
				$checkusercircle = $this->common->getData('user_circle', array("circle_id" => $checkuser['circle_id']), array());
				if ($checkusercircle) {
					foreach ($checkusercircle as $value) {
						$chuser = $this->common->getData('user', array("user_id" => $value['user_id']), array('single'));

						$data['sendername'] = $chuser['first_name'] . " " . $chuser['last_name'];
						$userFullName = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
						$data['useremail'] = "";

						if ($_REQUEST['paid_status'] === '2') {
							$data['message'] = '<p>Hello Team members ' . $userFullName . ' in your circle has paid late</p>';
							$messaged = $this->load->view('template/common-mail', $data, true);
							$mail = $this->sendMail($chuser['email'], 'Miscellaneous', $messaged);
						}
						if ($_REQUEST['paid_status'] === '3') {
							$data['message'] = '<p>Hello Team members ' . $userFullName . ' in your circle has missed a payment</p>';
							$messaged = $this->load->view('template/common-mail', $data, true);
							$mail = $this->sendMail($chuser['email'], 'Miscellaneous', $messaged);
						}
					}
				}
			}
			$this->response(true, "Miscellaneous Loan Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	public function add_loan()
	{
		$_REQUEST['created_at'] = $_REQUEST['created_at'];
		$_REQUEST['active_date'] = $_REQUEST['created_at'];

		$iname = '';
		if (isset($_FILES['document_image'])) {
			$image = $this->common->do_upload('document_image', './assets/document/');
			if (isset($image['upload_data'])) {
				$iname = 'assets/document/' . $image['upload_data']['file_name'];
			}
		}


		$_REQUEST['document_image'] = $iname;


		$amount = $_REQUEST['loan_amount'];
		$interRate = 10;


		$loanPercentDetail = $this->common->getData('loan_percent', array('id' => $_REQUEST['loan_type']), array('single'));

		if (!empty($loanPercentDetail)) {
			$interRate = $loanPercentDetail['percent'];
		}

		if (!empty($_REQUEST['interRate'])) {
			$interRate = $_REQUEST['interRate'];
		}

		$interest_payable = (($amount * $interRate) / 100);
		$total_payment = $amount + $interest_payable;
		$loan_emi = $total_payment / $_REQUEST['tenure'];


		$current_date = date('Y-m-d');
		$end_date = strtotime("+" . $_REQUEST['tenure'] . " month", strtotime($current_date));
		$_REQUEST['end_date'] = date("Y-m-d", $end_date);
		$_REQUEST['start_date'] = $current_date;


		$_REQUEST['loan_emi'] = $loan_emi;
		$_REQUEST['total_payment'] = $total_payment;
		$_REQUEST['interest_payable'] = $interest_payable;
		$_REQUEST['interest_rate'] = $interRate;
		$_REQUEST['interest_rate'] = $interRate;

		$post = $this->common->getField('user_loan', $_REQUEST);
		$result = $this->common->insertData('user_loan', $post);
		$loan_id = $this->db->insert_id();
		if ($result) {
			$this->common->query_normal("UPDATE credit_score_user SET each_loan_application = each_loan_application-200 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");

			$this->updateCreditScoreUser(200, 'minus', $_REQUEST['user_id']);

			if (!empty($_REQUEST['gurarantor'])) {
				$this->common->query_normal("UPDATE credit_score_user SET guarantee_a_loan_application = guarantee_a_loan_application+0 WHERE `user_id` = '" . $_REQUEST['gurarantor'] . "'");

				$this->updateCreditScoreUser(0, 'plus', $_REQUEST['gurarantor']);
			}



			$this->common->insertData('user_loan_status_history', array("loan_id" => $loan_id, "user_id" => $_REQUEST['user_id'], "note_title" => $_REQUEST['note_title'], "note_description" => $_REQUEST['note_description'], "status" => $_REQUEST['status'], "created_at" => date('Y-m-d H:i:s')));

			$this->response(true, "Loan added successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	function updateCreditScoreUser($points, $calculation_type, $user_id)
	{
		$result = $this->common->getData('credit_score_user', array('user_id' => $user_id), array('single'));

		if (!empty($result)) {
			$totalScore = 0;
			$newScore = 0;
			if ($calculation_type === 'plus') {
				$totalScore = $result['total_credit_score'] + $points;
			}

			if ($calculation_type === 'minus') {
				$totalScore = $result['total_credit_score'] - $points;
			}

			if ($totalScore > 900) {
				$newScore = 900;
			} else if ($totalScore < 0) {
				$newScore = 0;
			} else {
				$newScore = $totalScore;
			}

			$this->common->updateData('credit_score_user', array("total_credit_score" => $newScore), array('user_id' => $user_id));
		}
	}


	public function addPF_percent()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		$post = $this->common->getField('pf_percent', $_REQUEST);
		$result = $this->common->insertData('pf_percent', $post);
		$pf_id = $this->db->insert_id();

		if ($result) {
			$this->response(true, "Add Group Successfully", array("pf_id" => $pf_id));
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	public function addLoan_percent()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		$post = $this->common->getField('loan_percent', $_REQUEST);
		$result = $this->common->insertData('loan_percent', $post);
		$loan_id = $this->db->insert_id();

		if ($result) {
			$this->response(true, "Add Loan Successfully", array("loan_id" => $loan_id));
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function pf_percent_detail()
	{
		$result = $this->common->getData('pf_percent', array('id' => $_REQUEST['id']), array('single'));
		if (!empty($result)) {
			$this->response(true, "Pf percent fetch Successfully.", array("pf_percentDetail" => $result));
		} else {
			$this->response(false, "Pf percent not found", array("pf_percentDetail" => array()));
		}
	}



	public function loan_percent_detail()
	{
		$result = $this->common->getData('loan_percent', array('id' => $_REQUEST['id']), array('single'));
		if (!empty($result)) {
			$this->response(true, "Loan percent fetch Successfully.", array("loanDetail" => $result));
		} else {
			$this->response(false, "Loan percent not found", array("loanDetail" => array()));
		}
	}


	public function editPF_percent()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);
		$post = $this->common->getField('pf_percent', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('pf_percent', $post, array('id' => $id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "PF Percent Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	public function editLoan_percent()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);
		$post = $this->common->getField('loan_percent', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('loan_percent', $post, array('id' => $id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Loan Percent Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function editGroup()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);
		$post = $this->common->getField('group_cycle', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('group_cycle', $post, array('id' => $id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Group Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	public function addEmergencyLoan()
	{
		$_REQUEST['created_at'] = $_REQUEST['created_at'];
		$_REQUEST['active_date'] = $_REQUEST['created_at'];
		$post = $this->common->getField('user_emergency_loan', $_REQUEST);
		$result = $this->common->insertData('user_emergency_loan', $post);
		$loan_id = $this->db->insert_id();

		if ($result) {
			$this->common->query_normal("UPDATE credit_score_user SET emergency_loan_request = emergency_loan_request-100 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
			$this->updateCreditScoreUser(100, 'minus', $_REQUEST['user_id']);


			$this->common->insertData('user_emergency_loan_status_history', array("loan_id" => $loan_id, "user_id" => $_REQUEST['user_id'], "note_title" => $_REQUEST['note_title'], "note_description" => $_REQUEST['note_description'], "status" => $_REQUEST['status'], "created_at" => date('Y-m-d H:i:s')));

			$this->response(true, "Emergency loan submitted successfully", array("loan_id" => $loan_id));
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	public function editEmergencyLoan()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);

		if ($_REQUEST['payment_method'] === '3' && ($_REQUEST['status'] == '2')) {
			$this->paymentBySafekeeping($id, $_REQUEST['loan_amount'], '4', '0');
		}

		if ($_REQUEST['payment_method'] === '2' && ($_REQUEST['status'] == '2')) {
			$this->paymentByPF($id, $_REQUEST['loan_amount'], '4');
		}

		if ($_REQUEST['status'] === '4') {
			$_REQUEST['active_date'] = date('Y-m-d H:i:s');
		}

		if ($_REQUEST['status'] === '2') {
			$_REQUEST['complete_date'] = date('Y-m-d H:i:s');
		}


		$post = $this->common->getField('user_emergency_loan', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('user_emergency_loan', $post, array('id' => $id));
		} else {
			$result = "";
		}

		$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

		if ($_REQUEST['status'] === '4') {
			$message = "emergency loan approved";
			$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "2", "2");

			$message2 = "emergency loan approved";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "7");

			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
			$data['useremail'] = "";
			$data['message'] = '<p>This is a confirmation that your EMERGENCY help application has been approved and processed. Expect payment into your account within 24 hours</p><p>If you did not make this application, do let us know immediately</p>';
			$messaged = $this->load->view('template/common-mail', $data, true);
			$mail = $this->sendMail($userDetailFrom['email'], 'Emergency Loan', $messaged);
		}

		if ($_REQUEST['status'] === '3') {
			$message = "emergency loan declined";
			$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "3", "2");

			$message2 = "emergency loan declined";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "8");

			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
			$data['useremail'] = "";
			$data['message'] = '<p>We regret to inform you that your recent loan application has been declined. There are a variety of reasons that may have contributed to this decision. If you require further clarification, please feel free to reach out to the group admin.</p>
			<p>Thank you for your understanding.</p>';
			$messaged = $this->load->view('template/common-mail', $data, true);
			$mail = $this->sendMail($userDetailFrom['email'], 'Emergency Loan', $messaged);
		}

		if ($_REQUEST['status'] === '6') {
			$message = " emergency loan declined";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "6");

			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
			$data['useremail'] = "";
			$data['message'] = '<p>We regret to inform you that your recent loan application has been declined. There are a variety of reasons that may have contributed to this decision. If you require further clarification, please feel free to reach out to the group admin.</p>
			<p>Thank you for your understanding.</p>';
			$messaged = $this->load->view('template/common-mail', $data, true);
			$mail = $this->sendMail($userDetailFrom['email'], 'Emergency Loan', $messaged);
		}

		if ($_REQUEST['status'] === '5') {
			$message = "emergency loan in process";
			$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "1", "1");

			$message2 = "awaiting further approvall";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "5");
		}


		if ($_REQUEST['payment_method'] === '1' && $_REQUEST['status'] === '2') {
			$message2 = "emergency loan fully  paid";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "5");

			$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
			$data['useremail'] = "";
			$data['message'] = 'This is a confirmation that your Emergency Loan has been fully paid and marked as completed.<p>Payment due date - <b>' . date('d M Y', strtotime($_REQUEST['pay_by'])) . '</b></p>';
			$messaged = $this->load->view('template/common-mail', $data, true);
			$mail = $this->sendMail($userDetailFrom['email'], 'Emergency Loan', $messaged);
		}


		if ($_REQUEST['status'] === '2') {

			$this->common->query_normal("UPDATE credit_score_user SET loan_emergency_payment_fully_paid = loan_emergency_payment_fully_paid+0 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
			$this->updateCreditScore(0, 'plus');

			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
			$data['useremail'] = "";
			$data['message'] = '<p>Your loan repayment has been completed successfully.</p>
			<p>Thank you for your cooperation. If you need any assistance, please contact the group admin.</p>';
			$messaged = $this->load->view('template/common-mail', $data, true);
			$mail = $this->sendMail($userDetailFrom['email'], 'Emergency Loan', $messaged);
		}


		if ($_REQUEST['paid_status'] === '1') {
			$this->common->query_normal("UPDATE credit_score_user SET emergency_loan_paid_on_time = emergency_loan_paid_on_time+150 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
			$this->updateCreditScore(150, 'plus');
		}


		if ($_REQUEST['paid_status'] === '2') {
			$this->common->query_normal("UPDATE credit_score_user SET three_late_emergency_payments = three_late_emergency_payments+1 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");

			$creditScoreInfo = $this->common->getData('credit_score_user', array('user_id' => $_REQUEST['user_id']), array('single'));

			if (!empty($creditScoreInfo)) {

				if ($creditScoreInfo['three_late_emergency_payments'] > 3) {
					$this->common->query_normal("UPDATE credit_score_user SET emergency_loan_paid_late = emergency_loan_paid_late-300 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
					$this->updateCreditScore(300, 'minus');
				} else {
					$this->common->query_normal("UPDATE credit_score_user SET emergency_loan_paid_late = emergency_loan_paid_late-100 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
					$this->updateCreditScore(100, 'minus');
				}
			}
		}

		$this->common->insertData('user_emergency_loan_status_history', array("loan_id" => $id, "user_id" => $_REQUEST['user_id'], "note_title" => $_REQUEST['note_title'], "note_description" => $_REQUEST['note_description'], "status" => $_REQUEST['status'], "created_at" => date('Y-m-d H:i:s')));
		// new-changes 28-08-2025
		if (!empty($_REQUEST['status'])) {
			$status     = $_REQUEST['status'];
			$loan_type  = '8';
			$group_id   = $_REQUEST['group_id'];
			$created_at = date('Y-m-d H:i:s');
			$loan_id    = $id;
			$user_id    = $_REQUEST['user_id'];
			$month      = $_REQUEST['pay_by'];
			$amount     = $_REQUEST['loan_amount'];

			// Check if record already exists for this loan_id
			$check = $this->common->getData(
				"payment_notification",
				array("loan_id" => $loan_id)
			);

			if (!empty($check)) {
				// Update existing record
				$result = $this->common->updateData(
					"payment_notification",
					array(
						"status"     => $status,
						"group_id"   => $group_id,
						"month"      => $month,
						"created_at" => $created_at,
						"user_id"    => $user_id,
						"amount"     => $amount,
						"loan_type"  => $loan_type
					),
					array("loan_id" => $loan_id)
				);
			} else {
				// Insert new record
				$result = $this->common->insertData(
					"payment_notification",
					array(
						"status"     => $status,
						"group_id"   => $group_id,
						"month"      => $month,
						"created_at" => $created_at,
						"user_id"    => $user_id,
						"amount"     => $amount,
						"loan_type"  => $loan_type,
						"loan_id"    => $loan_id
					)
				);
			}
		}

		if ($result) {
			//changes on  10-05-2024
			//   $userDetailFrom = $this->common->getData('user',array('user_id'=>$_REQUEST['user_id']),array('single'));
			//  $checkuser = $this->common->getData('user_circle',array("user_id"=>$_REQUEST['user_id']),array('single'));
			//          if(!empty($checkuser)){
			//          $checkusercircle = $this->common->getData('user_circle',array("circle_id"=>$checkuser['circle_id']),array());
			//           if($checkusercircle){
			//               foreach($checkusercircle as $value){
			//                   $chuser = $this->common->getData('user',array("user_id"=>$value['user_id']),array('single'));
			//                     $data['sendername'] = $userDetailFrom['first_name']." ".$userDetailFrom['last_name'];
			//         			$data['useremail'] = "";
			//         			if($group_lifecycle['group_type_id'] > '2' ){
			//                       $cycle_name = 'HELP2BUY payment';
			//                     }
			//         		    if($_REQUEST['paid_status'] ==='2'){
			//  			        	$data['message'] = '<p>Hello Team members Someone in your circle has paid late</p>';
			//  			        	$messaged = $this->load->view('template/common-mail',$data,true);
			// 		    	        $mail = $this->sendMail($chuser['email'],'Emergency Loan',$messaged);
			//                     }
			//                     if($_REQUEST['paid_status'] ==='3'){
			//  				        $data['message'] = '<p>Hello Team members Someone in your circle has missed a payment</p>';
			//  				        $messaged = $this->load->view('template/common-mail',$data,true);
			// 		    	        $mail = $this->sendMail($chuser['email'],'Emergency Loan',$messaged);
			//                     }


			//               }
			//           }
			//          }

			$this->response(true, "Emergency Loan Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function miscellaneous_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "UM.user_id = '" . $_REQUEST['user_id'] . "' AND UM.group_id = '" . $_REQUEST['group_id'] . "'";

		if (!empty($_REQUEST['admin_type'] === '2')) {
			$where .= " AND UM.status != '1' AND UM.status != '6'";
		}

		$result = $this->user_model->miscellaneous_detail($where, array(), $start, $end);
		$miscellaneousCount = $this->user_model->miscellaneous_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $miscellaneousCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $miscellaneousCount));
		}
	}


	public function emergencyLoan_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "UE.user_id = '" . $_REQUEST['user_id'] . "' AND UE.group_id = '" . $_REQUEST['group_id'] . "'";

		if (!empty($_REQUEST['admin_type'] === '2')) {
			$where .= " AND UE.status != '1' AND UE.status != '6'";
		}

		$result = $this->user_model->emergencyLoan_detail($where, array(), $start, $end);
		$emergencyCount = $this->user_model->emergencyLoan_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $emergencyCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $emergencyCount));
		}
	}


	public function miscellaneous_detail()
	{
		$result = $this->common->getData('user_miscellaneous', array('id' => $_REQUEST['id']), array('single'));
		if (!empty($result)) {
			$this->response(true, "Miscellaneous fetch Successfully.", array("miscellaneousDetail" => $result));
		} else {
			$this->response(false, "Miscellaneous not found", array("miscellaneousDetail" => array()));
		}
	}





	public function emergencyLoan_detail()
	{
		$result = $this->common->getData('user_emergency_loan', array('id' => $_REQUEST['id']), array('single'));
		if (!empty($result)) {
			$this->response(true, "Emergency Loan fetch Successfully.", array("emergencyLoanDetail" => $result));
		} else {
			$this->response(false, "Emergency Loan not found", array("emergencyLoanDetail" => array()));
		}
	}


	public function blockUnblockGroup()
	{
		$this->common->updateData('group_cycle', array('status' => $_REQUEST['status']), array('id' => $_REQUEST['id']));

		if ($_REQUEST['status'] == 1) {
			$this->response(true, 'Group Unblocked Successfully', array('status' => $_REQUEST['status']));
		} else {
			$this->response(true, 'Group Blocked Successfully', array('status' => $_REQUEST['status']));
		}
	}


	public function blockUnblockGroupCycle()
	{
		$this->common->updateData('group_lifecycle', array('status' => $_REQUEST['status']), array('id' => $_REQUEST['id']));

		if ($_REQUEST['status'] == 1) {
			$this->response(true, 'Group Cycle Unblocked Successfully', array('status' => $_REQUEST['status']));
		} else {
			$this->response(true, 'Group Cycle Blocked Successfully', array('status' => $_REQUEST['status']));
		}
	}



	public function addGroupCycle()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');


		$end_date = strtotime("+" . $_REQUEST['month_count'] . " month", strtotime($_REQUEST['start_date']));
		$_REQUEST['end_date'] = date("Y-m-d", $end_date);

		$post = $this->common->getField('group_lifecycle', $_REQUEST);
		$result = $this->common->insertData('group_lifecycle', $post);
		$group_cycle_id = $this->db->insert_id();

		$userList = $this->user_model->user_group_detail(array("UG.group_id" => $_REQUEST['group_id']));

		//new-changes  add group_type_id 2
		if ($_REQUEST['group_type_id'] == '2') {
			foreach ($userList as $key => $value) {
				$this->adduser_group_by_singlecycle($group_cycle_id, $value['user_id'], $value['jnr_amount']);
			}
		} else {
			foreach ($userList as $key => $value) {
				$this->adduser_group_by_singlecycle($group_cycle_id, $value['user_id'], $value['amount']);
			}
		}


		if ($result) {
			$this->response(true, "Add Group Cycle Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	public function addGroupCycleBySingleUser()
	{
		$this->adduser_group_by_singlecycle($_REQUEST['group_cycle_id'], $_REQUEST['user_id'], $_REQUEST['amount']);
		$this->response(true, "Add Group Cycle Successfully");
	}

	public function adduser_group_by_singlecycle($groupcycle_id, $user_id, $amount)
	{
		$groupDetail = $this->common->getData('group_lifecycle', array('id' => $groupcycle_id), array('single'));

		if (!empty($groupDetail)) {
			$x = 1;
			$cycleDate = $groupDetail['start_date'];
			while ($x <= $groupDetail['month_count']) {
				$cycleArr = array("group_id" => $groupDetail['group_id'], "groupLifecycle_id" => $groupDetail['id'], "user_id" => $user_id, "amount" => $amount, "month" => $x, "created_at" => date('Y-m-d H:i:s'), "date" => $cycleDate);

				$post = $this->common->getField('user_group_lifecycle', $cycleArr);
				$result = $this->common->insertData('user_group_lifecycle', $post);
				$cycleDate = strtotime("+1 month", strtotime($cycleDate));
				$cycleDate = date("Y-m-d", $cycleDate);
				$x++;
			}
			return;
		} else {
			return;
		}
	}


	public function editGroupCycle()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);
		$end_date = strtotime("+" . $_REQUEST['month_count'] . " month", strtotime($_REQUEST['start_date']));
		$_REQUEST['end_date'] = date("Y-m-d", $end_date);
		$post = $this->common->getField('group_lifecycle', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('group_lifecycle', $post, array('id' => $id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Group Cycle Info Updated Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function groupCycle_detail()
	{
		$result = $this->common->getData('group_lifecycle', array('id' => $_REQUEST['id']), array('single'));
		if (!empty($result)) {
			$this->response(true, "group fetch Successfully.", array("groupDetail" => $result));
		} else {
			$this->response(false, "Group not found", array("groupDetail" => array()));
		}
	}


	public function userCycle_detail()
	{
		$result = $this->common->getData('user_group_lifecycle', array('id' => $_REQUEST['id']), array('single'));
		if (!empty($result)) {
			$this->response(true, "group fetch Successfully.", array("groupDetail" => $result));
		} else {
			$this->response(false, "Group not found", array("groupDetail" => array()));
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


	public function groupCycleAll_list_web()
	{

		$result = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id'], "group_type_id" => $_REQUEST['type']), array('sort_by' => 'id', 'sort_direction' => 'desc'));

		if (!empty($result)) {
			$this->response(true, "Data fetch Successfully.", array("lists" => $result));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array()));
		}
	}



	public function groupCycle_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$result = $this->user_model->groupCycle_detail(array("GL.group_id" => $_REQUEST['group_id']), array(), $start, $end);
		$groupCount = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id']), array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $groupCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $groupCount));
		}
	}




	public function editCategory()
	{
		// chmod('./assets/category/',0777);

		$category_id = $_REQUEST['category_id'];
		unset($_REQUEST['category_id']);

		if (isset($_FILES['image'])) {
			$image = $this->common->do_upload_thumb('image', './assets/category/');
			if (isset($image['upload_data'])) {
				$iname = 'assets/category/' . $image['upload_data']['file_name'];
				$iname_thumb = 'assets/category/thumb/' . $image['upload_data']['file_name'];
				$_REQUEST['category_image'] = $iname;
				$_REQUEST['category_image_thumb'] = $iname_thumb;
			}
		}

		$post = $this->common->getField('category', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('category', $post, array('category_id' => $category_id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Category Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	public function editPlan()
	{
		$plan_id = $_REQUEST['plan_id'];
		unset($_REQUEST['plan_id']);
		$post = $this->common->getField('membership_plan', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('membership_plan', $post, array('id' => $plan_id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Plan Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function category_all_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$result = $this->user_model->category_detail(array(), array(), $start, $end);
		$categoryCount = $this->common->getData('category', "", array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {
			foreach ($result as $key => $value) {
				if (!empty($value['category_image'])) {
					$result[$key]['category_image'] = base_url($value['category_image']);
					$result[$key]['category_image_thumb'] = base_url($value['category_image_thumb']);
				} else {
					$result[$key]['category_image'] = 'assets/img/default-user-icon.jpg';
					$result[$key]['category_image_thumb'] = 'assets/img/default-user-icon.jpg';
				}

				$result[$key]['sno'] = $countData++;
			}
			$this->response(true, "Category fetch Successfully.", array("categoryList" => $result, "categoryCount" => $categoryCount));
		} else {
			$this->response(true, "Category fetch Successfully.", array("categoryList" => array(), "categoryCount" => $categoryCount));
		}
	}



	public function plan_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$result = $this->user_model->plan_detail(array(), array(), $start, $end);
		$planCount = $this->common->getData('membership_plan', "", array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Plan fetch Successfully.", array("planList" => $result, "planCount" => $planCount));
		} else {
			$this->response(true, "Plan fetch Successfully.", array("planList" => array(), "planCount" => $planCount));
		}
	}



	public function getPaymenHistory()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$result = $this->user_model->payment_detail(array(), array(), $start, $end);
		$paymentCount = $this->common->getData('payment_history', "", array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Payment History fetch Successfully.", array("paymentList" => $result, "paymentCount" => $paymentCount));
		} else {
			$this->response(true, "Payment History fetch Successfully.", array("paymentList" => array(), "paymentCount" => $paymentCount));
		}
	}


	public function loanPaymentList()
	{

		$where = "user_id = '" . $_REQUEST['user_id'] . "' AND group_id = '" . $_REQUEST['group_id'] . "' AND loan_id = '" . $_REQUEST['loan_id'] . "' ";
		//changes
		$where1 = "( user_id = '" . $_REQUEST['user_id'] . "' AND group_id = '" . $_REQUEST['group_id'] . "' AND loan_id = '" . $_REQUEST['loan_id'] . "')   GROUP BY user_id ";

		$PaymentList = $this->common->getData('user_loan_payment', $where);

		//changes
		$PaymentTotal = $this->common->getData('user_loan_payment', $where1, array("field" => 'user_id,sum(amount) as total_amount', "single"));

		//changes
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
			$provident = $LoanDetail['provident']; //new-changes  
		} else {
			$loanAmount_initital = 0;
			$loanAmount = 0;
			$interest_rate = 0;
			$interest_payable = 0;
			$provident = 0; //new-changes  
		}

		//new-changes  
		if (!empty($PaymentList)) {
			$this->response(true, "Payment fetch Successfully.", array("paymentList" => $PaymentList, 'totalPaidAmount' => (int)$totalAmount, 'loanAmount' => (int)$loanAmount, 'interest_rate' => (int)$interest_rate, 'interest_payable' => (int)$interest_payable, 'loanAmount_initital' => (int)$loanAmount_initital, 'provident' => (int)$provident));
		} else {
			//new-changes  
			$this->response(true, "Payment fetch Successfully.", array("paymentList" => array(), 'totalPaidAmount' => (int)$totalAmount, 'loanAmount' => (int)$loanAmount, 'interest_rate' => (int)$interest_rate, 'interest_payable' => (int)$interest_payable, 'loanAmount_initital' => (int)$loanAmount_initital, 'provident' => (int)$provident));
		}
	}


	public function loanMiscellaneousPaymentList()
	{

		$where = "user_id = '" . $_REQUEST['user_id'] . "' AND group_id = '" . $_REQUEST['group_id'] . "' AND loan_id = '" . $_REQUEST['loan_id'] . "' ";
		$PaymentList = $this->common->getData('user_miscellaneous_payment', $where);


		$where1 = "user_id = '" . $_REQUEST['user_id'] . "' AND group_id = '" . $_REQUEST['group_id'] . "' AND loan_id = '" . $_REQUEST['loan_id'] . "' GROUP BY user_id ";

		$PaymentTotal = $this->common->getData('user_miscellaneous_payment', $where1, array("field" => 'user_id,sum(amount) as total_amount', "single"));

		if ($PaymentTotal) {
			$totalAmount = $PaymentTotal['total_amount'];
		} else {
			$totalAmount = 0.00;
		}

		$LoanDetail = $this->common->getData('user_miscellaneous', array("id" => $_REQUEST['loan_id']), array('single'));

		if (!empty($LoanDetail)) {
			$loanAmount_initital = $LoanDetail['amount'];
			$loanAmount = $LoanDetail['total_payment'];
		} else {
			$loanAmount_initital = 0;
			$loanAmount = 0;
		}


		if (!empty($PaymentList)) {
			$this->response(true, "Payment fetch Successfully.", array("paymentList" => $PaymentList, 'totalPaidAmount' => (int)$totalAmount, 'loanAmount' => (int)$loanAmount, 'loanAmount_initital' => (int)$loanAmount_initital));
		} else {
			$this->response(true, "Payment fetch Successfully.", array("paymentList" => array(), 'totalPaidAmount' => (int)$totalAmount, 'loanAmount' => (int)$loanAmount, 'loanAmount_initital' => (int)$loanAmount_initital));
		}
	}



	public function loanPaymentDetail()
	{
		$where = "id = '" . $_REQUEST['id'] . "'";
		$paymentDetail = $this->common->getData('user_loan_payment', $where, array('single'));

		if (!empty($paymentDetail)) {
			$this->response(true, "Payment fetch Successfully.", array("paymentDetail" => $paymentDetail));
		} else {
			$this->response(true, "Payment fetch Successfully.", array("paymentDetail" => array()));
		}
	}

	public function miscellaneousPaymentDetail()
	{
		$where = "id = '" . $_REQUEST['id'] . "'";
		$paymentDetail = $this->common->getData('user_miscellaneous_payment', $where, array('single'));

		if (!empty($paymentDetail)) {
			$this->response(true, "Payment fetch Successfully.", array("paymentDetail" => $paymentDetail));
		} else {
			$this->response(true, "Payment fetch Successfully.", array("paymentDetail" => array()));
		}
	}


	public function addLoanPayment()
	{

		if ($_REQUEST['payment_method'] === '3') {
			$this->checkpaymentBySafekeeping($_REQUEST['amount']);
		}

		if ($_REQUEST['payment_method'] === '2') {
			$this->checkpaymentByPF($_REQUEST['amount']);
		}

		// $_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$_REQUEST['interest_rate'] = '10%';

		$post = $this->common->getField('user_loan_payment', $_REQUEST);
		$result = $this->common->insertData('user_loan_payment', $post);
		$id = $this->db->insert_id();
		if ($result) {

			if ($_REQUEST['payment_method'] === '3') {
				$this->paymentBySafekeeping($id, $_REQUEST['amount'], '3', '0');
			}

			if ($_REQUEST['payment_method'] === '2') {
				$this->paymentByPF($id, $_REQUEST['amount'], '3');
			}

			$message = "your loan bal has been edited";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "2");

			if ($_REQUEST['status'] === '1') {
				$this->common->query_normal("UPDATE credit_score_user SET loan_paid_on_time = loan_paid_on_time+20 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
				$this->updateCreditScore(20, 'plus');
			}

			if ($_REQUEST['status'] === '3') {
				$this->common->query_normal("UPDATE credit_score_user SET missed_loan_deadline = missed_loan_deadline-120 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
				$this->updateCreditScore(120, 'minus');
			}


			if ($_REQUEST['status'] === '2') {
				$this->common->query_normal("UPDATE credit_score_user SET three_or_more_late_loan_payments = three_or_more_late_loan_payments+1 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");


				$creditScoreInfo = $this->common->getData('credit_score_user', array('user_id' => $_REQUEST['user_id']), array('single'));

				if (!empty($creditScoreInfo)) {

					if ($creditScoreInfo['three_or_more_late_loan_payments'] > 3) {
						$this->common->query_normal("UPDATE credit_score_user SET late_loan_payment = late_loan_payment-300 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
						$this->updateCreditScore(300, 'minus');
					} else {
						$this->common->query_normal("UPDATE credit_score_user SET late_loan_payment = late_loan_payment-60 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
						$this->updateCreditScore(60, 'minus');
					}
				}
			}

			$cycle_name = $_REQUEST['loan_title'] == '' ? 'Loan Payment' : $_REQUEST['loan_title'];

			$data["status"] = '#N/A';
			if ($_REQUEST['status'] == '1') {
				$data["status"] = 'Paid On Time';
			}
			if ($_REQUEST['status'] == '2') {
				$data["status"] = 'Paid Late';
			}
			if ($_REQUEST['status'] == '3') {
				$data["status"] = 'Missed Payment Deadline';
			}

			$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));
			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];

			// $data['message'] = '<p>This is a confirmation that we have received and recorded your "' . $cycle_name . '" for this month. Refer to your app for confirmation</p>';

			$data['message'] = '<p>This is a confirmation that we have received and recorded your "' . $cycle_name . '" for this month. Refer to your app for confirmation</p>
				<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-top: 10px;">
					<tr>
						<td><strong>Amount paid</strong></td>
						<td>£' . $_REQUEST["amount"] . '</td>
					</tr>
					<tr>
						<td><strong>Payment date</strong></td>
						<td>' . date("d M Y", strtotime($_REQUEST["created_at"])) . '</td>
					</tr>
					<tr>
						<td><strong>Payment status</strong></td>
						<td>' . $data["status"] . '</td>
					</tr>
				</table>';
			$messaged = $this->load->view('template/common-mail', $data, true);
			$this->sendMail($userDetailFrom['email'], $cycle_name, $messaged);

			$this->response(true, "Add loan payment Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function addMiscellaneousPayment()
	{

		if ($_REQUEST['payment_method'] === '3') {
			$this->checkpaymentBySafekeeping($_REQUEST['amount']);
		}

		if ($_REQUEST['payment_method'] === '2') {
			$this->checkpaymentByPF($_REQUEST['amount']);
		}


		$post = $this->common->getField('user_miscellaneous_payment', $_REQUEST);
		$result = $this->common->insertData('user_miscellaneous_payment', $post);
		$id = $this->db->insert_id();
		if ($result) {

			if ($_REQUEST['payment_method'] === '3') {
				$this->paymentBySafekeeping($id, $_REQUEST['amount'], '3', '0');
			}

			if ($_REQUEST['payment_method'] === '2') {
				$this->paymentByPF($id, $_REQUEST['amount'], '3');
			}

			$miscellaneousDetail = $this->common->getData('user_miscellaneous', array("id" => $_REQUEST['loan_id']), array('single'));
			$cycle_name = $miscellaneousDetail['title'] == '' ? 'Miscellaneous Payment' : $miscellaneousDetail['title'];

			$data["status"] = '#N/A';
			if ($_REQUEST['status'] == '1') {
				$data["status"] = 'Paid On Time';
				$this->updateCreditScoreUser(20, 'plus', $_REQUEST['user_id']);
			}
			if ($_REQUEST['status'] == '2') {
				$data["status"] = 'Paid Late';
				$this->updateCreditScoreUser(30, 'minus', $_REQUEST['user_id']);
			}
			if ($_REQUEST['status'] == '3') {
				$data["status"] = 'Missed Payment Deadline';
				$this->updateCreditScoreUser(60, 'minus', $_REQUEST['user_id']);
			}

			$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));
			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];

			// $data['message'] = '<p>This is a confirmation that we have received and recorded your "' . $cycle_name . '" for this month. Refer to your app for confirmation</p>';

			$data['message'] = '<p>This is a confirmation that we have received and recorded your "' . $cycle_name . '" for this month. Refer to your app for confirmation</p>
				<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-top: 10px;">
					<tr>
						<td><strong>Amount paid</strong></td>
						<td>£' . $_REQUEST["amount"] . '</td>
					</tr>
					<tr>
						<td><strong>Payment date</strong></td>
						<td>' . date("d M Y", strtotime($_REQUEST["created_at"])) . '</td>
					</tr>
					<tr>
						<td><strong>Payment status</strong></td>
						<td>' . $data["status"] . '</td>
					</tr>
				</table>';
			$messaged = $this->load->view('template/common-mail', $data, true);
			$this->sendMail($userDetailFrom['email'], $cycle_name, $messaged);

			$this->response(true, "Add Miscellaneous payment Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	// public function editLoan()
	// {
	// 	// ini_set('display_errors', 1);
	// 	$id = $_REQUEST['id'];
	// 	unset($_REQUEST['id']);

	// 	if ($_REQUEST['status'] === '4') {
	// 		$loanInfo = $this->common->getData('user_loan', array('id' => $id), array('single'));
	// 		if (!empty($loanInfo)) {
	// 			$current_date = date('Y-m-d');
	// 			$end_date = strtotime("+" . $loanInfo['tenure'] . " month", strtotime($current_date));
	// 			$_REQUEST['end_date'] = date("Y-m-d", $end_date);
	// 			$_REQUEST['start_date'] = $current_date;
	// 		}
	// 	}


	// 	$post = $this->common->getField('user_loan', $_REQUEST);

	// 	if (!empty($post)) {
	// 		$result = $this->common->updateData('user_loan', $post, array('id' => $id));
	// 	} else {
	// 		$result = "";
	// 	}

	// 	$getloan = $this->common->getData('user_loan', array('id' => $id), array('single'));


	// 	$this->common->insertData('user_loan_status_history', array("loan_id" => $id, "user_id" => $_REQUEST['user_id'], "note_title" => $_REQUEST['note_title'], "note_description" => $_REQUEST['note_description'], "status" => $_REQUEST['status'], "created_at" => date('Y-m-d H:i:s')));

	// 	if ($_REQUEST['status'] === '4') {

	// 		$message = "loan approved";
	// 		$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "5", "2");

	// 		$this->common->query_normal("UPDATE credit_score_user SET each_loan_application = each_loan_application-200 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
	// 		$this->updateCreditScore(200, 'minus');


	// 		$message2 = "loan accepted by super admin";
	// 		$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "11");

	// 		///sendmail
	// 		$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

	// 		$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
	// 		$data['useremail'] = "";

	// 		if (!empty($getloan['loan_type'])) {
	// 			$subject = "";
	// 			if ($getloan['loan_type'] == '1') {
	// 				$subject = "Assistance";
	// 			} elseif ($getloan['loan_type'] == '2') {
	// 				$subject = "Help2 Pay(Car Insurance)";
	// 			} elseif ($getloan['loan_type'] == '3') {
	// 				$subject = "Help2 Buy(Car)";
	// 			} elseif ($getloan['loan_type'] == '4') {
	// 				$subject = "Help2 Pay(credit card)";
	// 			} elseif ($getloan['loan_type'] == '5') {
	// 				$subject = "Help2 Pay(other)";
	// 			} else {
	// 				$subject = "Help2 Buy(property)";
	// 			}

	// 			$referenceNo = $getloan["reference_no"] ?? "#N/A";
	// 			$data['message'] = '<p>We are writing to inform you that your ' . $subject . '  application has been successfully processed and approved.</p><p>The payment will be deposited into your account within the next 24 hours.</p><p>If you did not initiate this loan application, please get in touch with us immediately to address the issue.</p>
	// 			<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-top: 10px;">
	// 				<tr>
	// 					<td><strong>Assistance</strong></td>
	// 					<td>£' . $_REQUEST["loan_amount"] . '</td>
	// 				</tr>
	// 				<tr>
	// 					<td><strong>Term</strong></td>
	// 					<td>' . $_REQUEST["tenure"] . ' Months</td>
	// 				</tr>
	// 				<tr>
	// 					<td><strong>Type</strong></td>
	// 					<td>' . $subject . '</td>
	// 				</tr>
	// 				<tr>
	// 					<td><strong>Payment Start Date</strong></td>
	// 					<td>' . date("d M Y", strtotime($getloan['start_date'])) . '</td>
	// 				</tr>
	// 				<tr>
	// 					<td><strong>Monthly Payment</strong></td>
	// 					<td>£' . $getloan["loan_emi"] . '</td>
	// 				</tr>
	// 				<tr>
	// 					<td><strong>Reference No.</strong></td>
	// 					<td>' . $referenceNo . '</td>
	// 				</tr>
	// 			</table>';
	// 			$messaged = $this->load->view('template/common-mail', $data, true);
	// 			$mail = $this->sendMail($userDetailFrom['email'], $subject, $messaged);
	// 		}
	// 	}

	// 	if ($_REQUEST['status'] === '3') {
	// 		$message = "loan declined";
	// 		$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "6", "2");

	// 		$message2 = "loan has been cancel by super admin";
	// 		$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "12");
	// 	}

	// 	if ($_REQUEST['status'] === '6') {
	// 		$message = "loan has been cancel by sub admin";
	// 		$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "10");
	// 	}

	// 	if ($_REQUEST['status'] === '5') {
	// 		$message = "loan application in process.";
	// 		$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "4", "1");

	// 		$message2 = "Loan awaiting approval";
	// 		$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "9");
	// 	}
	// 	//new-changes 28-08-2025
	// 	if ($_REQUEST['status']) {
	// 		$status     = $_REQUEST['status'];
	// 		$loan_type  = $_REQUEST['loan_type'];
	// 		$group_id   = $_REQUEST['group_id'];
	// 		$created_at = date('Y-m-d H:i:s');
	// 		$loan_id    = $id;
	// 		$user_id    = $_REQUEST['user_id'];
	// 		$month      = $getloan['start_date'];
	// 		$amount     = $_REQUEST['loan_amount'];

	// 		// Check if record exists for this loan_id
	// 		$check = $this->common->getData(
	// 			"payment_notification",
	// 			array("loan_id" => $loan_id)
	// 		);

	// 		if (!empty($check)) {
	// 			// Update existing record
	// 			$result = $this->common->updateData(
	// 				"payment_notification",
	// 				array(
	// 					"status"     => $status,
	// 					"group_id"   => $group_id,
	// 					"month"      => $month,
	// 					"created_at" => $created_at,
	// 					"user_id"    => $user_id,
	// 					"amount"     => $amount,
	// 					"loan_type"  => $loan_type
	// 				),
	// 				array("loan_id" => $loan_id)
	// 			);
	// 		} else {
	// 			// Insert new record
	// 			$result = $this->common->insertData(
	// 				"payment_notification",
	// 				array(
	// 					"status"     => $status,
	// 					"group_id"   => $group_id,
	// 					"month"      => $month,
	// 					"created_at" => $created_at,
	// 					"user_id"    => $user_id,
	// 					"amount"     => $amount,
	// 					"loan_type"  => $loan_type,
	// 					"loan_id"    => $loan_id
	// 				)
	// 			);
	// 		}
	// 	}

	// 	if ($_REQUEST['status'] === '2') {

	// 		$amount = pfTotal($_REQUEST['group_id'], $_REQUEST['user_id']);
	// 		// 		if($amount >= $_REQUEST['loan_amount']) {
	// 		$note_title = '';
	// 		$note_description = '';
	// 		if (!empty($_REQUEST['note_title'])) {
	// 			$note_title = $_REQUEST['note_title'];
	// 		}

	// 		$note_description = '';
	// 		if (!empty($_REQUEST['note_description'])) {
	// 			$note_description = $_REQUEST['note_description'];
	// 		}

	// 		$result = $this->common->insertData('pf_user', array(
	// 			"user_id" => $_REQUEST['user_id'],
	// 			'pf_interest_percent' => $getloan['interest_rate'],
	// 			"pf_interest_amount" => $getloan['interest_payable'],
	// 			"group_id" => $_REQUEST['group_id'],
	// 			"main_id" => $id,
	// 			"pf_amount" => $getloan['interest_payable'],
	// 			"payment_type" => '2',
	// 			"payment_by" => '3',
	// 			"note_title" => $note_title,
	// 			"note_description" => $note_description,
	// 			"created_at" => date('Y-m-d H:i:s'),
	// 			'provident' => $getloan['provident'],
	// 			'loan_type' => $getloan['loan_type']
	// 		));
	// 		//}    


	// 		$message2 = "loan completed";
	// 		$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "15");

	// 		$this->common->query_normal("UPDATE credit_score_user SET loan_payment_fully_paid = loan_payment_fully_paid+80 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
	// 		$this->updateCreditScore(80, 'plus');
	// 	}

	// 	if ($result) {
	// 		$this->response(true, "Loan Update Successfully");
	// 	} else {
	// 		$this->response(false, "There is a problem, please try again.");
	// 	}
	// }

	private function getLoanTypeTitle($loanType)
	{
		if ($loanType == '1') {
			return "Assistance";
		} elseif ($loanType == '2') {
			return "Help2 Pay(Car Insurance)";
		} elseif ($loanType == '3') {
			return "Help2 Buy(Car)";
		} elseif ($loanType == '4') {
			return "Help2 Pay(credit card)";
		} elseif ($loanType == '5') {
			return "Help2 Pay(other)";
		}

		return "Help2 Buy(property)";
	}

	// private function addLoanEmiPayments($loan)
	// {
	// 	if (empty($loan) || empty($loan['id'])) {
	// 		return false;
	// 	}

	// 	$existingPayments = $this->common->getData('user_loan_payment', array('loan_id' => $loan['id']));
	// 	if (!empty($existingPayments)) {
	// 		return true;
	// 	}

	// 	$tenure = !empty($loan['tenure']) ? (int)$loan['tenure'] : 0;
	// 	$emiAmount = !empty($loan['loan_emi']) ? $loan['loan_emi'] : 0;

	// 	if (empty($emiAmount) && !empty($loan['total_payment']) && $tenure > 0) {
	// 		$emiAmount = $loan['total_payment'] / $tenure;
	// 	}

	// 	if (empty($emiAmount) && !empty($loan['loan_amount']) && $tenure > 0) {
	// 		$emiAmount = $loan['loan_amount'] / $tenure;
	// 	}

	// 	if ($tenure <= 0 || empty($emiAmount)) {
	// 		return false;
	// 	}

	// 	$paymentDate = !empty($loan['start_date']) ? $loan['start_date'] : date('Y-m-d');

	// 	for ($month = 1; $month <= $tenure; $month++) {
	// 		$payment = array(
	// 			'loan_id' => $loan['id'],
	// 			'user_id' => $loan['user_id'],
	// 			'group_id' => $loan['group_id'],
	// 			'amount' => $emiAmount,
	// 			'payment_method' => '0',
	// 			'status' => 0,
	// 			'month' => $month,
	// 			'date' => $paymentDate,
	// 			'created_at' => date('Y-m-d H:i:s')
	// 		);

	// 		$post = $this->common->getField('user_loan_payment', $payment);
	// 		$result = $this->common->insertData('user_loan_payment', $post);

	// 		if (!$result) {
	// 			return false;
	// 		}

	// 		$paymentDate = date("Y-m-d", strtotime("+1 month", strtotime($paymentDate)));
	// 	}

	// 	return true;
	// }

	// created by @krishn on 26-06-26
	private function addLoanEmiPayments($loan)
	{
		if (empty($loan) || empty($loan['id'])) {
			return false;
		}

		$existingPayments = $this->common->getData(
			'user_loan_payment',
			array('loan_id' => $loan['id'])
		);

		if (!empty($existingPayments)) {
			return true;
		}

		$tenure = !empty($loan['tenure'])
			? (int)$loan['tenure']
			: 0;

		$emiAmount = !empty($loan['loan_emi'])
			? $loan['loan_emi']
			: 0;

		if (empty($emiAmount) && !empty($loan['total_payment']) && $tenure > 0) {
			$emiAmount = $loan['total_payment'] / $tenure;
		}

		if (empty($emiAmount) && !empty($loan['loan_amount']) && $tenure > 0) {
			$emiAmount = $loan['loan_amount'] / $tenure;
		}

		if ($tenure <= 0 || empty($emiAmount)) {
			return false;
		}


		$paymentDate = !empty($loan['start_date'])
			? $loan['start_date']
			: date('Y-m-d');

		$day = date('d', strtotime($paymentDate));


		for ($month = 1; $month <= $tenure; $month++) {

			$payment = array(
				'loan_id'         => $loan['id'],
				'user_id'         => $loan['user_id'],
				'group_id'        => $loan['group_id'],
				'amount'          => $emiAmount,
				'payment_method'  => 0,
				'status'          => 0,
				'month'           => $month,
				'emi_date'        => $paymentDate
			);

			$post = $this->common->getField(
				'user_loan_payment',
				$payment
			);

			$result = $this->common->insertData(
				'user_loan_payment',
				$post
			);

			if (!$result) {
				return false;
			}


			/* Next EMI Date */

			$nextMonth = strtotime("+1 month", strtotime($paymentDate));

			$year = date('Y', $nextMonth);
			$monthNo = date('m', $nextMonth);

			$lastDay = cal_days_in_month(
				CAL_GREGORIAN,
				$monthNo,
				$year
			);

			$emiDay = min($day, $lastDay);

			$paymentDate = date(
				'Y-m-d',
				strtotime("$year-$monthNo-$emiDay")
			);
		}

		return true;
	}

	// updated by @krishn on 17-06-26
	public function editLoan()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);

		if (isset($_REQUEST['status']) && $_REQUEST['status'] == '4') {

			$loanInfo = $this->common->getData(
				'user_loan',
				['id' => $id],
				['single']
			);

			if (!empty($loanInfo)) {

				$startDate = !empty($_REQUEST['start_date'])
					? $_REQUEST['start_date']
					: date('Y-m-d');

				$tenure = (int)$loanInfo['tenure'];

				// Start date always saved
				$_REQUEST['start_date'] = $startDate;

				// Calculate end date keeping same day as start date
				$originalDay = (int)date('d', strtotime($startDate));

				$date = new DateTime($startDate);

				// Last installment month
				$date->modify('+' . ($tenure - 1) . ' month');

				$year  = $date->format('Y');
				$month = $date->format('m');

				// Last day of that month
				$lastDay = cal_days_in_month(
					CAL_GREGORIAN,
					$month,
					$year
				);

				// Use original day if exists otherwise last day
				$day = min($originalDay, $lastDay);

				$_REQUEST['end_date'] = sprintf(
					'%04d-%02d-%02d',
					$year,
					$month,
					$day
				);
			}
		}

		$post = $this->common->getField('user_loan', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData(
				'user_loan',
				$post,
				['id' => $id]
			);
		} else {
			$result = "";
		}

		if (!empty($post)) {
			$result = $this->common->updateData('user_loan', $post, array('id' => $id));
		} else {
			$result = "";
		}

		$getloan = $this->common->getData('user_loan', array('id' => $id), array('single'));


		$this->common->insertData('user_loan_status_history', array("loan_id" => $id, "user_id" => $_REQUEST['user_id'], "note_title" => $_REQUEST['note_title'], "note_description" => $_REQUEST['note_description'], "status" => $_REQUEST['status'], "created_at" => date('Y-m-d H:i:s')));

		if ($_REQUEST['status'] === '4') {

			$emiPaymentResult = $this->addLoanEmiPayments($getloan);
			if (!$emiPaymentResult) {
				$this->response(false, "There is a problem, please try again.");
				return;
			}

			$message = "loan approved";
			$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "5", "2");

			$this->common->query_normal("UPDATE credit_score_user SET each_loan_application = each_loan_application-200 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
			$this->updateCreditScore(200, 'minus');


			$message2 = "loan accepted by super admin";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "11");

			///sendmail
			$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
			$data['useremail'] = "";

			if (!empty($getloan['loan_type'])) {
				$subject = $this->getLoanTypeTitle($getloan['loan_type']);

				$referenceNo = $getloan["reference_no"] ?? "#N/A";
				$data['message'] = '<p>We are writing to inform you that your ' . $subject . '  application has been successfully processed and approved.</p><p>The payment will be deposited into your account within the next 24 hours.</p><p>If you did not initiate this loan application, please get in touch with us immediately to address the issue.</p>
				<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-top: 10px;">
					<tr>
						<td><strong>Assistance</strong></td>
						<td>£' . $_REQUEST["loan_amount"] . '</td>
					</tr>
					<tr>
						<td><strong>Term</strong></td>
						<td>' . $_REQUEST["tenure"] . ' Months</td>
					</tr>
					<tr>
						<td><strong>Type</strong></td>
						<td>' . $subject . '</td>
					</tr>
					<tr>
						<td><strong>Payment Start Date</strong></td>
						<td>' . date("d M Y", strtotime($getloan['start_date'])) . '</td>
					</tr>
					<tr>
						<td><strong>Monthly Payment</strong></td>
						<td>£' . $getloan["loan_emi"] . '</td>
					</tr>
					<tr>
						<td><strong>Reference No.</strong></td>
						<td>' . $referenceNo . '</td>
					</tr>
				</table>';
				$messaged = $this->load->view('template/common-mail', $data, true);
				$mail = $this->sendMail($userDetailFrom['email'], $subject, $messaged);
			}
		}

		if ($_REQUEST['status'] === '3') {
			$message = "loan declined";
			$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "6", "2");

			$message2 = "loan has been cancel by super admin";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "12");
		}

		if ($_REQUEST['status'] === '6') {
			$message = "loan has been cancel by sub admin";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "10");
		}

		if ($_REQUEST['status'] === '5') {
			$message = "loan application in process.";
			$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "4", "1");

			$message2 = "Loan awaiting approval";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "9");
		}
		//new-changes 28-08-2025
		if ($_REQUEST['status']) {
			$status     = $_REQUEST['status'];
			$loan_type  = $_REQUEST['loan_type'];
			$group_id   = $_REQUEST['group_id'];
			$created_at = date('Y-m-d H:i:s');
			$loan_id    = $id;
			$user_id    = $_REQUEST['user_id'];
			$month      = $getloan['start_date'];
			$amount     = $_REQUEST['loan_amount'];

			// Check if record exists for this loan_id
			$check = $this->common->getData(
				"payment_notification",
				array("loan_id" => $loan_id)
			);

			if (!empty($check)) {
				// Update existing record
				$result = $this->common->updateData(
					"payment_notification",
					array(
						"status"     => $status,
						"group_id"   => $group_id,
						"month"      => $month,
						"created_at" => $created_at,
						"user_id"    => $user_id,
						"amount"     => $amount,
						"loan_type"  => $loan_type
					),
					array("loan_id" => $loan_id)
				);
			} else {
				// Insert new record
				$result = $this->common->insertData(
					"payment_notification",
					array(
						"status"     => $status,
						"group_id"   => $group_id,
						"month"      => $month,
						"created_at" => $created_at,
						"user_id"    => $user_id,
						"amount"     => $amount,
						"loan_type"  => $loan_type,
						"loan_id"    => $loan_id
					)
				);
			}
		}

		if ($_REQUEST['status'] === '2') {

			$amount = pfTotal($_REQUEST['group_id'], $_REQUEST['user_id']);
			// 		if($amount >= $_REQUEST['loan_amount']) {
			$note_title = '';
			$note_description = '';
			if (!empty($_REQUEST['note_title'])) {
				$note_title = $_REQUEST['note_title'];
			}

			$note_description = '';
			if (!empty($_REQUEST['note_description'])) {
				$note_description = $_REQUEST['note_description'];
			}

			$result = $this->common->insertData('pf_user', array(
				"user_id" => $_REQUEST['user_id'],
				'pf_interest_percent' => $getloan['interest_rate'],
				"pf_interest_amount" => $getloan['interest_payable'],
				"group_id" => $_REQUEST['group_id'],
				"main_id" => $id,
				"pf_amount" => $getloan['interest_payable'],
				"payment_type" => '2',
				"payment_by" => '3',
				"note_title" => $note_title,
				"note_description" => $note_description,
				"created_at" => date('Y-m-d H:i:s'),
				'provident' => $getloan['provident'],
				'loan_type' => $getloan['loan_type']
			));
			//}    


			$message2 = "loan completed";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "15");

			$this->common->query_normal("UPDATE credit_score_user SET loan_payment_fully_paid = loan_payment_fully_paid+80 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
			$this->updateCreditScore(80, 'plus');

			// Send Loan Completed Email
			$userDetailFrom = $this->common->getData(
				'user',
				array('user_id' => $_REQUEST['user_id']),
				array('single')
			);

			if (!empty($userDetailFrom)) {

				$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
				$data['useremail'] = "";

				$loanType = $this->getLoanTypeTitle($getloan['loan_type']);
				$subject = $loanType . " Loan Completed";

				$referenceNo = $getloan["reference_no"] ?? "#N/A";

				$data['message'] = '
				<p>Congratulations! Your loan has been completed successfully.</p>

				<p>This confirms that your ' . $loanType . ' has been paid in full and is now complete.</p>

				<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-top: 10px;">
					<tr>
						<td><strong>Reference No.</strong></td>
						<td>' . $referenceNo . '</td>
					</tr>
					<tr>
						<td><strong>Type</strong></td>
						<td>' . $loanType . '</td>
					</tr>
					<tr>
						<td><strong>Loan Amount</strong></td>
						<td>£' . $getloan["loan_amount"] . '</td>
					</tr>
					<tr>
						<td><strong>Completion Date</strong></td>
						<td>' . date("d M Y") . '</td>
					</tr>
				</table>

				<p>We appreciate your commitment and thank you for being a valued Interfriends member.</p>';

				$messaged = $this->load->view('template/common-mail', $data, true);

				$this->sendMail(
					$userDetailFrom['email'],
					$subject,
					$messaged
				);
			}
		}

		if ($result) {
			$this->response(true, "Loan Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	public function editLoanPayment()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);
		if ($_REQUEST['payment_method'] === '3') {
			$this->paymentBySafekeeping($id, $_REQUEST['amount'], '3', '0');
		}


		if ($_REQUEST['payment_method'] === '2') {
			$this->paymentByPF($id, $_REQUEST['amount'], '3');
		}


		$post = $this->common->getField('user_loan_payment', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('user_loan_payment', $post, array('id' => $id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Loan Payment Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function editMiscellaneousPayment()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);
		if ($_REQUEST['payment_method'] === '3') {
			$this->paymentBySafekeeping($id, $_REQUEST['amount'], '3', '0');
		}


		if ($_REQUEST['payment_method'] === '2') {
			$this->paymentByPF($id, $_REQUEST['amount'], '3');
		}


		$post = $this->common->getField('user_miscellaneous_payment', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('user_miscellaneous_payment', $post, array('id' => $id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Miscellaneous Payment Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function product_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$result = $this->user_model->product_detail(array(), array(), $start, $end);
		$productCount = $this->common->getData('product', "", array('count'));

		if (!empty($result)) {
			foreach ($result as $key => $value) {
				if (!empty($value['product_image'])) {
					$result[$key]['product_image'] = base_url($value['product_image']);
					$result[$key]['product_image_thumb'] = base_url($value['product_image_thumb']);
				} else {
					$result[$key]['product_image'] = "assets/img/default-user-icon.jpg";
					$result[$key]['product_image_thumb'] = "assets/img/default-user-icon.jpg";
				}

				if (!empty($value['category_image'])) {
					$result[$key]['category_image'] = base_url($value['category_image']);
					$result[$key]['category_image_thumb'] = base_url($value['category_image_thumb']);
				}
			}
			$this->response(true, "Product fetch Successfully.", array("productList" => $result, "productCount" => $productCount));
		} else {
			$this->response(true, "Product fetch Successfully.", array("productList" => array(), "productCount" => $productCount));
		}
	}


	public function rateing_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end



		if (!empty($_REQUEST['type'] == 1)) { // for pericular company
			$where = "R.status = 1 AND R.company_id = '" . $_REQUEST['id'] . "'";
		} else if (!empty($_REQUEST['type'] == 2)) { // for pericular product
			$where = "R.status = 1 AND R.product_id = '" . $_REQUEST['id'] . "'";
		} else if (!empty($_REQUEST['type'] == 3)) { // for my get review
			$where = "R.status = 1 AND R.to_user = '" . $_REQUEST['id'] . "'";
		} else if (!empty($_REQUEST['type'] == 4)) { // for my give review
			$where = "R.status = 1 AND R.user_id = '" . $_REQUEST['id'] . "'";
		} else if (!empty($_REQUEST['type'] == 5)) { // for pericular customer
			$where = "R.status = 1 AND R.customer_id = '" . $_REQUEST['id'] . "'";
		} else {
			$where = "R.status = 1";
		}

		$having = '';
		if (!empty($_REQUEST['search_keyword'])) {
			$having = " (company_name_search LIKE '%" . $_REQUEST['search_keyword'] . "%' OR product_name_search LIKE '%" . $_REQUEST['search_keyword'] . "%' OR username_search LIKE '%" . $_REQUEST['search_keyword'] . "%') ";
		}

		if (!empty($_REQUEST['category_id'])) {
			$where .= " AND (FIND_IN_SET('" . $_REQUEST['category_id'] . "',C.category) OR P.category = '" . $_REQUEST['category_id'] . "')";
		}

		$result = $this->user_model->rateing_detail($where, array(), $start, $end, $having);

		// echo $this->db->last_query();
		// die();

		// $ratingCount = $this->common->getData('rating',array('status' => 1),array('count'));

		if (!empty($result)) {
			foreach ($result as $key => $value) {
				if (!empty($value['rate_image'])) {
					$result[$key]['rate_image'] = base_url($value['rate_image']);
					$result[$key]['rate_image_thumb'] = base_url($value['rate_image_thumb']);
				} else {
					$result[$key]['rate_image'] = "";
					$result[$key]['rate_image_thumb'] = "";
				}


				if (!empty($value['product_image'])) {
					$result[$key]['product_image'] = base_url($value['product_image']);
					$result[$key]['product_image_thumb'] = base_url($value['product_image_thumb']);
				} else {
					$result[$key]['product_image'] = "";
					$result[$key]['product_image_thumb'] = "";
				}


				if (!empty($value['from_profile_image'])) {
					$result[$key]['from_profile_image'] = base_url($value['from_profile_image']);
				} else {
					$result[$key]['from_profile_image'] = "";
				}

				if (!empty($value['to_profile_image'])) {
					$result[$key]['to_profile_image'] = base_url($value['to_profile_image']);
				} else {
					$result[$key]['to_profile_image'] = "";
				}


				if (!empty($value['from_company_profile_image'])) {
					$result[$key]['from_company_profile_image'] = base_url($value['from_company_profile_image']);
				} else {
					$result[$key]['from_company_profile_image'] = "";
				}

				if (!empty($value['company_profile_image'])) {
					$result[$key]['company_profile_image'] = base_url($value['company_profile_image']);
				} else {
					$result[$key]['company_profile_image'] = "";
				}
			}
			$this->response(true, "User fetch Successfully.", array("ratingList" => $result));
		} else {
			$this->response(true, "User fetch Successfully.", array("ratingList" => array()));
		}
	}


	public function blockUnblocksubadminuser()
	{
		$userdetail  = get_user_details($_REQUEST['id']);
		$email = $userdetail['email'];
		$data['name'] = $userdetail['first_name'] . ' ' . $userdetail['last_name'];

		if ($_REQUEST['status'] == 1) {

			$this->common->updateData('user', array('status' => $_REQUEST['status'], 'subadmin_status' => '0'), array('user_id' => $_REQUEST['id']));

			$data['message'] = 'Your account has been unblocked by the admin!';
			$data['status'] = 'Unblocked Account';
			$message = $this->load->view('template/block-user', $data, true);
			$this->sendMail($email, $data['status'], $message);

			$this->response(true, 'Rejected & User Unblocked successfully', array('status' => $_REQUEST['status']));
		} else {

			$this->common->updateData('user', array('status' => $_REQUEST['status'], 'subadmin_status' => '1'), array('user_id' => $_REQUEST['id']));

			$this->response(true, 'Accepted & User Blocked successfully', array('status' => $_REQUEST['status']));
		}
	}



	public function blockUnblockUser()
	{
		$array = array();
		$userdetail  = get_user_details($_REQUEST['id']);
		$email = $userdetail['email'];
		$array['name'] = $userdetail['first_name'] . ' ' . $userdetail['last_name'];

		if ($_REQUEST['admintype'] == '1') {

			$this->common->updateData('user', array('subadmin_status' => $_REQUEST['status']), array('user_id' => $_REQUEST['id']));

			$this->response(true, 'Your request to block this user has been sent to the admin', array('status' => $_REQUEST['status']));
			exit();
		} else {

			$this->common->updateData('user', array('status' => $_REQUEST['status']), array('user_id' => $_REQUEST['id']));
		}

		if ($_REQUEST['status'] == 1) {

			$array['message'] = 'Your account has been unblocked by the admin!';
			$array['status'] = 'Unblocked Account';
			$message = $this->load->view('template/block-user', $array, true);
			$mail = $this->sendMail($email, $array['status'], $message);

			$this->response(true, 'User Unblocked successfully', array('status' => $_REQUEST['status']));
		} else {

			$array['message'] = 'You have been blocked by the admin! This may be due to a number of reasons including breaching our terms and conditions. If you were not expecting this, contact the group admin immediately.';
			$array['status'] = 'Blocked Account';
			$message = $this->load->view('template/block-user', $array, true);
			$mail = $this->sendMail($email, $array['status'], $message);

			$this->response(true, 'User Blocked successfully', array('status' => $_REQUEST['status']));
		}
	}

	// create by @krishn on 21-05-25
	public function isDefaultUser()
	{
		$array = array();
		$userdetail  = get_user_details($_REQUEST['id']);
		$email = $userdetail['email'];
		$array['name'] = $userdetail['first_name'] . ' ' . $userdetail['last_name'];

		if ($_REQUEST['admintype'] == '1') {

			$this->common->updateData('user', array('subadmin_is_default' => $_REQUEST['is_default']), array('user_id' => $_REQUEST['id']));

			$this->response(true, 'Your request to add in default this user has been sent to the admin', array('is_default' => $_REQUEST['is_default']));
			exit();
		} else {

			$this->common->updateData('user', array('is_default' => $_REQUEST['is_default']), array('user_id' => $_REQUEST['id']));
		}

		if ($_REQUEST['is_default'] == 1) {

			$array['message'] = 'You have been removed from DEFAULT list by the admin!';
			$array['status'] = 'Performing';
			$message = $this->load->view('template/block-user', $array, true);
			$mail = $this->sendMail($email, 'Performing', $message);

			$this->updateCreditScoreUser(300, 'plus', $_REQUEST['id']);

			$this->response(true, 'User remove from Default successfully', array('is_default' => $_REQUEST['is_default']));
		} else {

			$array['message'] = 'Your account has been put into Default. This may be due to a number of reasons including breaching our terms and conditions. If you were not expecting this, contact the group admin immediately.';
			$message = $this->load->view('template/block-user', $array, true);
			$mail = $this->sendMail($email, 'Default Account', $message);

			$this->updateCreditScoreUser(600, 'minus', $_REQUEST['id']);

			$this->response(true, 'User added in Default successfully', array('is_default' => $_REQUEST['is_default']));
		}
	}

	// create by @krishn on 21-05-25
	public function isDefaultApproval()
	{
		if ($_REQUEST['is_default'] == 1) {

			$this->common->updateData('user', array('is_default' => $_REQUEST['is_default'], 'subadmin_is_default' => '0'), array('user_id' => $_REQUEST['id']));

			$this->updateCreditScoreUser(300, 'plus', $_REQUEST['id']);

			$this->response(true, 'Rejected & User Removed from the Default list successfully', array('is_default' => $_REQUEST['is_default']));
		} else {

			$this->common->updateData('user', array('is_default' => $_REQUEST['is_default'], 'subadmin_is_default' => '1'), array('user_id' => $_REQUEST['id']));

			$this->updateCreditScoreUser(600, 'minus', $_REQUEST['id']);

			$this->response(true, 'Accepted & User Added in Default list successfully', array('is_default' => $_REQUEST['is_default']));
		}
	}


	public function activeDeactivateUser()
	{
		$this->common->updateData('user', array('business_status' => $_REQUEST['status']), array('user_id' => $_REQUEST['id']));

		if ($_REQUEST['status'] == 0) {
			$this->response(true, 'User business account deactivate successfully', array('status' => $_REQUEST['status']));
		} else {
			$this->response(true, 'User business account activate successfully', array('status' => $_REQUEST['status']));
		}
	}


	public function blockUnblockCategory()
	{
		$this->common->updateData('category', array('status' => $_REQUEST['status']), array('category_id' => $_REQUEST['id']));

		if ($_REQUEST['status'] == 1) {
			$this->response(true, 'Category blocked successfully', array('status' => $_REQUEST['status']));
		} else {
			$this->response(true, 'Category unblocked successfully', array('status' => $_REQUEST['status']));
		}
	}


	public function blockUnblockPlan()
	{
		$this->common->updateData('membership_plan', array('status' => $_REQUEST['status']), array('id' => $_REQUEST['id']));

		if ($_REQUEST['status'] == 1) {
			$this->response(true, 'Plan Unblocked Successfully', array('status' => $_REQUEST['status']));
		} else {
			$this->response(true, 'Plan Blocked Successfully', array('status' => $_REQUEST['status']));
		}
	}


	public function blockUnblockProduct()
	{
		$this->common->updateData('product', array('status' => $_REQUEST['status']), array('product_id' => $_REQUEST['id']));

		if ($_REQUEST['status'] == 1) {
			$this->response(true, 'Product blocked successfully', array('status' => $_REQUEST['status']));
		} else {
			$this->response(true, 'Product unblocked successfully', array('status' => $_REQUEST['status']));
		}
	}


	public function blockUnblockBusiness()
	{
		$this->common->updateData('user', array('status' => $_REQUEST['status']), array('id' => $_REQUEST['id']));

		if ($_REQUEST['status'] == 1) {
			$this->response(true, 'Business status unblocked successfully', array('status' => $_REQUEST['status']));
		} else {
			$this->response(true, 'Business status blocked successfully', array('status' => $_REQUEST['status']));
		}
	}


	public function blockUnblockRating()
	{
		$this->common->updateData('rating', array('status' => $_REQUEST['status']), array('id' => $_REQUEST['id']));

		if ($_REQUEST['status'] == 1) {
			$this->response(true, 'Rating blocked successfully', array('status' => $_REQUEST['status']));
		} else {
			$this->response(true, 'Rating unblocked successfully', array('status' => $_REQUEST['status']));
		}
	}



	public function forgetPassword()
	{
		$data = $this->common->getData('superAdmin', array('email' => $_POST['email']), array('single'));
		if (!empty($data)) {
			$token = $this->generateToken();
			$data['token'] = $data['id'] . $token;
			$this->common->updateData('superAdmin', array('token' => $data['token']), array('id' => $data['id']));
			$message = $this->load->view('template/reset-mail-superAdmin', $data, true);

			$mail = $this->sendMail($_REQUEST['email'], 'Forgot Password', $message);

			if ($mail) {
				$this->response(true, "Thank you, You will receive an e-mail in the next 5 minutes with instructions for resetting your password. If you Don't receive this e-mail, please check your junk mail folder or contact us for further assistance.");
			} else {
				$this->response(false, "Mail Not delivered");
			}
		} else {
			$this->response(false, 'Email not registered');
		}
	}


	public function resetForgetPassword()
	{
		$_POST['password'] = md5($_REQUEST['password']);
		$update = $this->common->updateData11('superAdmin', array('token' => "", 'password' => $_POST['password']), array('token' => $_REQUEST['token']));
		if ($update) {
			$this->response(true, 'Password Changed Successfully');
		} else {
			$this->response(false, 'Link expired. Please reset password again');
		}
	}

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



	public function ticketList()
	{
		$where = "T.status = 1 OR T.status = 0";
		$pendingList = $this->user_model->ticket_detail($where);
		$completeList = $this->user_model->ticket_detail(array('T.status' => 2));
		$this->response(true, "Ticket fetch successfully", array('pendingList' => $pendingList, 'completeList' => $completeList));
	}



	public function ticketDetail()
	{
		$ticketDetail = $this->user_model->ticket_detail(array('T.id' => $_REQUEST['id']), array('single'));

		if (!empty($ticketDetail)) {
			$result = $this->user_model->rateing_detail(array('R.id' => $ticketDetail['rate_id']), array('single'));

			$ratingDetail = array();
			if (!empty($result)) {
				if (!empty($result['rate_image'])) {
					$result['rate_image'] = base_url($result['rate_image']);
					$result['rate_image_thumb'] = base_url($result['rate_image_thumb']);
				} else {
					$result['rate_image'] = "assets/img/default-user-icon.jpg";
					$result['rate_image_thumb'] = "assets/img/default-user-icon.jpg";
				}


				if (!empty($result['product_image'])) {
					$result['product_image'] = base_url($result['product_image']);
					$result['product_image_thumb'] = base_url($result['product_image_thumb']);
				} else {
					$result['product_image'] = "assets/img/default-user-icon.jpg";
					$result['product_image_thumb'] = "assets/img/default-user-icon.jpg";
				}


				if (!empty($result['from_profile_image'])) {
					$result['from_profile_image'] = base_url($result['from_profile_image']);
				} else {
					$result['from_profile_image'] = "assets/img/default-user-icon.jpg";
				}

				if (!empty($result['to_profile_image'])) {
					$result['to_profile_image'] = base_url($result['to_profile_image']);
				} else {
					$result['to_profile_image'] = "assets/img/default-user-icon.jpg";
				}

				if (!empty($result['company_profile_image'])) {
					$result['company_profile_image'] = base_url($result['company_profile_image']);
				} else {
					$result['company_profile_image'] = "assets/img/default-user-icon.jpg";
				}

				$ratingDetail = $result;
			}

			$this->response(true, "Ticket detail fetch successfully", array('ticketDetail' => $ticketDetail, 'ratingDetail' => $ratingDetail));
		} else {
			$this->response(false, "Ticket detail not found", array('ticketDetail' => array(), 'ratingDetail' => array()));
		}
	}


	public function addTicket()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		$post = $this->common->getField('ticket', $_REQUEST);
		$result = $this->common->insertData('ticket', $post);

		if ($result) {
			$this->response(true, "add ticket successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	public function addTicketComment()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		$post = $this->common->getField('ticket_comments', $_REQUEST);
		$result = $this->common->insertData('ticket_comments', $post);

		if ($result) {
			$this->response(true, "add ticket comment successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	public function getComments()
	{
		$commentList = $this->common->getData('ticket_comments', array('ticket_id' => $_REQUEST['ticket_id']));
		if (!empty($commentList)) {
			$this->response(true, "Comments fetch successfully", array('commentList' => $commentList));
		} else {
			$this->response(true, "Data not found", array('commentList' => array()));
		}
	}


	public function updateTicket()
	{
		$ticket_id = $_REQUEST['ticket_id'];
		unset($_REQUEST['ticket_id']);
		$post = $this->common->getField('ticket', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('ticket', $post, array('id' => $ticket_id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "ticket Update Successfully");
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


	public function getPrivacyPolicyInfo()
	{
		$privacyInfo = $this->common->getData('privacy_policy', array('id' => '1'), array('single'));

		if ($privacyInfo) {
			$this->response(true, "fetch information successfully", array('privacyInfo' => $privacyInfo['info']));
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	public function addTerms()
	{
		$result = $this->common->updateData('terms', array('info' => $_REQUEST['info']), array('id' => '1'));

		if ($result) {
			$this->response(true, "Update Information Successfully");
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


	public function addSubadmin()
	{
		$email = $this->common->getData('superAdmin', array('email' => $_POST['email']), array('single', 'field' => 'email'));
		if ($email) {
			$this->response(false, 'email already exists');
			die;
		}

		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$const_password = rand();

		$_REQUEST['const_password'] = $const_password;
		$_REQUEST['password'] = md5($const_password);
		$_REQUEST['status'] = 1;
		$user = $this->common->getField('superAdmin', $_REQUEST);
		$result = $this->common->insertData('superAdmin', $user);

		if ($result) {
			$userid = $this->db->insert_id();
			$_REQUEST['hostName'] = $userid;
			$message = $this->load->view('template/info-mail-user', $_REQUEST, true);
			$mail = $this->sendMail($_REQUEST['email'], 'Sub Admin Registration', $message);

			$this->response(true, 'Sub Admin added successfully');
		} else {
			$this->response(false, 'There is a problem, please try again.');
		}
	}


	public function editSubadmin()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);

		$whereEmail = "email = '" . $_POST['email'] . "' AND id != '" . $id . "'";
		$emailExist = $this->common->getData('superAdmin', $whereEmail, array('single', 'field' => 'email'));

		if ($emailExist) {
			$this->response(false, 'Email already exists');
			die;
		}

		$post = $this->common->getField('superAdmin', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('superAdmin', $post, array('id' => $id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "super Admin Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}



	public function blockUnblockSubadmin()
	{
		$this->common->updateData('superAdmin', array('status' => $_REQUEST['status']), array('id' => $_REQUEST['id']));

		if ($_REQUEST['status'] == 1) {
			$this->response(true, 'Super Admin Unblocked successfully', array('status' => $_REQUEST['status']));
		} else {
			$this->response(true, 'Super Admin Blocked successfully', array('status' => $_REQUEST['status']));
		}
	}


	public function deleteSubAdmin()
	{
		$this->common->deleteData('superAdmin', array('id' => $_REQUEST['id']));
		$this->response(true, 'Sub Admin Delete successfully');
	}


	public function subadminDetailInfo()
	{
		$userinfo = $this->common->getData('superAdmin', array('id' => $_REQUEST['id']), array('single'));

		if (!empty($userinfo)) {
			$this->response(true, "Profile Fetch Successfully.", array("userinfo" => $userinfo));
		} else {
			$this->response(false, "There Is Some Problem.Please Try Again.", array("userinfo" => array()));
		}
	}





	public function addUser()
	{
		$email = $this->common->getData('user', array('email' => $_POST['email']), array('single', 'field' => 'email'));
		if ($email) {
			$this->response(false, 'email already exists');
			die;
		}


		$iname = '';
		$iname_thumb = '';
		$iname_idProof = '';
		if (isset($_FILES['image'])) {
			$image = $this->common->do_upload_thumb('image', './assets/userfile/profile/');
			if (isset($image['upload_data'])) {
				$iname = 'assets/userfile/profile/' . $image['upload_data']['file_name'];
				$iname_thumb = 'assets/userfile/profile/thumb/' . $image['upload_data']['file_name'];
			}
		}

		if (isset($_FILES['id_proof_image'])) {
			$idProofImage = $this->common->do_upload_file('id_proof_image', './assets/userfile/profile/idproof/');
			if (isset($idProofImage['upload_data'])) {
				$iname_idProof = 'assets/userfile/profile/idproof/' . $idProofImage['upload_data']['file_name'];
			}
		}

		$_REQUEST['profile_image'] = $iname;
		$_REQUEST['profile_image_thumb'] = $iname_thumb;
		$_REQUEST['id_proof_image'] = $iname_idProof;


		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$const_password = rand();

		$_REQUEST['const_password'] = $const_password;
		$_REQUEST['password'] = md5($const_password);
		// $_REQUEST['password'] = md5('123456');
		$_REQUEST['status'] = 1;
		$user = $this->common->getField('user', $_REQUEST);
		$result = $this->common->insertData('user', $user);

		if ($result) {
			$userid = $this->db->insert_id();
			$_REQUEST['user_id'] = $userid;
			$this->common->insertData('credit_score_user', array('user_id' => $_REQUEST['user_id']));
			$creditArray = array();
			if (!empty($_REQUEST['employement_type'])) {
				if ($_REQUEST['employement_type'] === '1') {
					$creditArray['employe_full_time'] = 0;
					$this->updateCreditScore(0, 'plus');
					$creditArray['employed'] = 0;
					$this->updateCreditScore(0, 'plus');
				}

				if ($_REQUEST['employement_type'] === '2') {
					$creditArray['employ_part_time'] = 0;
					$this->updateCreditScore(0, 'plus');
					$creditArray['employed'] = 0;
					$this->updateCreditScore(0, 'plus');
				}


				if ($_REQUEST['employement_type'] === '3') {
					$creditArray['employ_self'] = 0;
					$this->updateCreditScore(0, 'plus');
					$creditArray['employed'] = 0;
					$this->updateCreditScore(0, 'plus');
				}
			}

			$creditArray['fully_registered'] = 300;
			$this->updateCreditScore(300, 'plus');

			$recommendResult = $this->common->getData('recommend_user', array("email" => $_REQUEST['email']), array('single'));

			if (!empty($recommendResult)) {
				if (!empty($recommendResult['user_id'])) {
					$creditArray['register_one_refree'] = 0;
					$this->updateCreditScore(0, 'plus');
				}

				if (!empty($recommendResult['refer_user_id'])) {
					$creditArray['register_two_refree'] = 0;
					$this->updateCreditScore(0, 'plus');
				}
			}


			$this->addCreditScore($userid, $creditArray);

			$_REQUEST['hostName'] = $userid;
			$token = $this->generateToken();
			$data['token'] = $userid . $token;
			$data['first_name'] = $_REQUEST['first_name'];
			$data['last_name'] = $_REQUEST['last_name'];
			$this->common->updateData('user', array('verify_token' => $data['token']), array('user_id' => $userid));
			$message = $this->load->view('template/add-user-reset-mail', $data, true);
			$mail = $this->sendMail($_REQUEST['email'], 'Member Registration', $message);

			if ($mail) {
				$this->response(true, "User added successfully");
			} else {
				$this->response(false, "Mail Not Delivered");
			}
		} else {
			$this->response(false, 'There is a problem, please try again.');
		}
	}

	public function addUserByRecommended()
	{
		// echo "<pre><br>";
		// print_r($_REQUEST);
		// echo "<br>";
		// print_r($_FILES); die();
		$email = $this->common->getData('user', array('email' => $_POST['email']), array('single', 'field' => 'email'));
		if ($email) {
			$this->response(false, 'Email already exists');
			die;
		}

		$recommendResult = $this->common->getData('recommend_user', array("email" => $_REQUEST['email']), array('single'));

		if (empty($recommendResult)) {
			$this->response(false, 'Email not exists in recommend user');
			die;
		}


		$iname = '';
		$iname_thumb = '';
		$iname_idProof = '';
		if (isset($_FILES['profile_image'])) {
			$image = $this->common->do_upload_thumb('profile_image', './assets/userfile/profile/');
			if (isset($image['upload_data'])) {
				$iname = 'assets/userfile/profile/' . $image['upload_data']['file_name'];
				$iname_thumb = 'assets/userfile/profile/thumb/' . $image['upload_data']['file_name'];
			}
		}
		if (isset($_FILES['id_proof_image'])) {
			$idProofImage = $this->common->do_upload_file('id_proof_image', './assets/userfile/profile/idproof/');
			if (isset($idProofImage['upload_data'])) {
				$iname_idProof = 'assets/userfile/profile/idproof/' . $idProofImage['upload_data']['file_name'];
			}
		}


		$_REQUEST['profile_image'] = $iname;
		$_REQUEST['profile_image_thumb'] = $iname_thumb;
		$_REQUEST['id_proof_image'] = $iname_idProof;


		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$const_password = $_REQUEST['password'];

		$_REQUEST['const_password'] = $const_password;
		$_REQUEST['password'] = md5($const_password);
		// $_REQUEST['password'] = md5('123456');
		$_REQUEST['status'] = 1;


		$recommendResult = $this->common->getData('recommend_user', array("email" => $_REQUEST['email'], 'admin_status' => '1'), array('single'));

		if (!empty($recommendResult)) {
			$_REQUEST['recommended'] = 0;
		} else {
			$_REQUEST['recommended'] = 1;
		}

		$user = $this->common->getField('user', $_REQUEST);
		$result = $this->common->insertData('user', $user);

		if ($result) {
			$userid = $this->db->insert_id();
			$_REQUEST['user_id'] = $userid;
			$_REQUEST['recommended'] = '1';
			$this->common->insertData('credit_score_user', array('user_id' => $_REQUEST['user_id']));
			$creditArray = array();
			if (!empty($_REQUEST['employement_type'])) {
				if ($_REQUEST['employement_type'] === '1') {
					$creditArray['employe_full_time'] = 0;
					$this->updateCreditScore(0, 'plus');
					$creditArray['employed'] = 0;
					$this->updateCreditScore(0, 'plus');
				}

				if ($_REQUEST['employement_type'] === '2') {
					$creditArray['employ_part_time'] = 0;
					$this->updateCreditScore(0, 'plus');
					$creditArray['employed'] = 0;
					$this->updateCreditScore(0, 'plus');
				}


				if ($_REQUEST['employement_type'] === '3') {
					$creditArray['employ_self'] = 0;
					$this->updateCreditScore(0, 'plus');
					$creditArray['employed'] = 0;
					$this->updateCreditScore(0, 'plus');
				}
			}

			$creditArray['fully_registered'] = 300;
			$this->updateCreditScore(300, 'plus');

			$recommendResult = $this->common->getData('recommend_user', array("email" => $_REQUEST['email'], 'admin_status' => '1'), array('single'));

			if (!empty($recommendResult)) {
				$result = $this->common->updateData('recommend_user', array('signup_form' => '1'), array('id' => $recommendResult['id']));

				if (!empty($recommendResult['user_id'])) {
					$creditArray['register_one_refree'] = 0;
					$this->updateCreditScore(0, 'plus');
				}

				if (!empty($recommendResult['refer_user_id'])) {
					$creditArray['register_two_refree'] = 0;
					$this->updateCreditScore(0, 'plus');
				}
			}


			$this->addCreditScore($userid, $creditArray);

			$_REQUEST['hostName'] = $userid;
			$token = $this->generateToken();
			$data['token'] = $userid . $token;
			$data['first_name'] = $_REQUEST['first_name'];
			$data['last_name'] = $_REQUEST['last_name'];
			$this->common->updateData('user', array('verify_token' => $data['token']), array('user_id' => $userid));
			// $message = $this->load->view('template/add-user-reset-mail', $data, true);
			// $mail = $this->sendMail($_REQUEST['email'], 'Member Registration', $message);

			if ($result) {
				// if (!empty($_POST['token'])) {
				// 	// Delete the token after successful use

				// 	$tokenData = $this->common->getData('registration_tokens', [
				// 		'token' => $_POST['token']
				// 	], ['single']);

				// 	if (empty($tokenData)) {
				// 		$this->response(false, 'Invalid or already used token');
				// 		return;
				// 	}

				// 	if (strtotime($tokenData['expires_at']) < time()) {
				// 		// Token has expired, optionally delete it
				// 		$this->common->deleteData('registration_tokens', ['token' => $_POST['token']]);
				// 		$this->response(false, 'Token has expired');
				// 		return;
				// 	}

				// 	$this->common->deleteData('registration_tokens', ['token' => $_POST['token']]);
				// }

				$admins = $this->common->getData('superAdmin', ['admin_type' => '2'], []);
				foreach ($admins as $admin) {

					$data['sendername'] = $admin['name'];
					$adminSubject = "Recommendation | Registration Completed";
					$recommendedName = $data['first_name'] . ' ' . $data['last_name'];

					$data['message'] = "
						<p>The recommended user <strong>{$recommendedName}</strong> has successfully completed the registration process.</p>

						<p>You can review the registration and take any further action in the admin panel.</p>
					";
					$adminMessage = $this->load->view('template/common-mail', $data, true);
					$this->sendMail($admin['email'], $adminSubject, $adminMessage);
				}

				$this->response(true, "Registration completed successfully. Please wait for admin approval before logging in.");
			} else {
				$this->response(false, "Mail Not Delivered");
			}
		} else {
			$this->response(false, 'There is a problem, please try again.');
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


	function addCreditScore($userId, $dataArry)
	{
		$result = $this->common->getData('credit_score_user', array("user_id" => $userId));

		if (!empty($result)) {
			$this->common->updateData('credit_score_user', $dataArry, array('user_id' => $userId));
		} else {
			$dataArry['user_id'] = $userId;
			$post = $this->common->getField('credit_score_user', $dataArry);
			$this->common->insertData('credit_score_user', $post);
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

	// 	if (isset($_FILES['id_proof_image'])) {
	// 		$idProofImage = $this->common->do_upload_file('id_proof_image', './assets/userfile/profile/idproof/');
	// 		if (isset($idProofImage['upload_data'])) {
	// 			$iname_idProof = 'assets/userfile/profile/idproof/' . $idProofImage['upload_data']['file_name'];
	// 		}
	// 		$_REQUEST['id_proof_image'] = $iname_idProof;
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

	// updated by @krishn on 17-06-26
	public function editUser()
	{
		$user_id = $_REQUEST['user_id'];
		unset($_REQUEST['user_id']);

		// Check email already exists
		$email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';

		if (!empty($email)) {

			$whereEmail = "email = '" . $email . "' AND user_id != '" . $user_id . "'";

			$emailExist = $this->common->getData(
				'user',
				$whereEmail,
				array('single', 'field' => 'email')
			);

			if ($emailExist) {
				$this->response(false, 'Email already exists');
				die;
			}
		}

		// Profile Image Upload
		if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {

			$image = $this->common->do_upload_thumb(
				'image',
				'./assets/userfile/profile/'
			);

			if (isset($image['upload_data'])) {

				$iname = 'assets/userfile/profile/' .
					$image['upload_data']['file_name'];

				$iname_thumb = 'assets/userfile/profile/thumb/' .
					$image['upload_data']['file_name'];

				$_REQUEST['profile_image'] = $iname;
				$_REQUEST['profile_image_thumb'] = $iname_thumb;
			}
		}

		// ID Proof Upload
		if (isset($_FILES['id_proof_image']) && !empty($_FILES['id_proof_image']['name'])) {

			$idProofImage = $this->common->do_upload_file(
				'id_proof_image',
				'./assets/userfile/profile/idproof/'
			);

			if (isset($idProofImage['upload_data'])) {

				$iname_idProof = 'assets/userfile/profile/idproof/' .
					$idProofImage['upload_data']['file_name'];

				$_REQUEST['id_proof_image'] = $iname_idProof;
			}
		}

		$post = $this->common->getField('user', $_REQUEST);

		if (!empty($post)) {

			$result = $this->common->updateData(
				'user',
				$post,
				array('user_id' => $user_id)
			);
		} else {

			$result = false;
		}

		if ($result) {

			$this->response(true, "User Update Successfully");
		} else {

			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function PF_percent_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$result = $this->user_model->PF_percent_detail(array(), array(), $start, $end);
		$pfCount = $this->common->getData('pf_percent', "", array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $pfCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $pfCount));
		}
	}



	public function loan_percent_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$result = $this->user_model->loan_percent_detail(array(), array(), $start, $end);
		$loanCount = $this->common->getData('loan_percent', "", array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $loanCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $loanCount));
		}
	}

	public function group_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		if (!empty($_REQUEST['group_ids'])) {
			$group_ids = explode(',', $_REQUEST['group_ids']);
			$group_ids = array_map('trim', $group_ids); // Remove any whitespace
			$group_ids = array_map('intval', $group_ids); // Ensure all are integers

			$where = "id IN (" . implode(',', $group_ids) . ")";
			$result = $this->common->getData('group_cycle', $where, array());
			$groupCount = count($result);
		} else {
			$result = $this->user_model->group_detail(array(), array(), $start, $end);
			$groupCount = $this->common->getData('group_cycle', "", array('count'));
		}

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;

				$existUserGroup = $this->common->getData('user_group', array('group_id' => $value['id']), array('single'));

				if (!empty($existUserGroup)) {
					$result[$key]['existUserGroup_status']  = true;
				} else {
					$result[$key]['existUserGroup_status']  = false;
				}
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $groupCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $groupCount));
		}
	}

	// API created by @krishn on 21/04/25
	public function contact_us_list()
	{
		if (!empty($_REQUEST['id'])) {
			// Fetch single contact by ID
			$result = $this->common->getData('contact_us', ['id' => $_REQUEST['id']], array('single'));
		} else {
			// Fetch all contacts
			$result = $this->common->getData('contact_us', [], ['sort_by' => 'id', 'sort_direction' => 'DESC']);
		}

		if (!empty($result)) {
			$this->response(true, "Contact(s) fetched successfully.", ['contactList' => $result]);
		} else {
			$this->response(false, "No contact records found.", ['contactList' => []]);
		}
	}

	// API created by @krishn on 21/04/25
	public function interested_user_list()
	{
		if (!empty($_REQUEST['id'])) {
			// Fetch single interested user by ID
			$result = $this->common->getData('interested_user', ['id' => $_REQUEST['id']], array('single'));
		} else {
			// Fetch all interested user
			$result = $this->common->getData('interested_user', [], ['sort_by' => 'id', 'sort_direction' => 'DESC']);
		}

		if (!empty($result)) {
			$this->response(true, "Interested User(s) fetched successfully.", ['interestedUser' => $result]);
		} else {
			$this->response(false, "No Interested User records found.", ['interestedUser' => []]);
		}
	}


	// public function recommendUser_list()
	// {
	// 	// limit code start
	// 	if (empty($_REQUEST['start'])) {
	// 		$start = 10;
	// 		$end = 0;
	// 	} else {
	// 		$start = 10;
	// 		$end = $_REQUEST['start'];
	// 	}
	// 	// limit code end

	// 	$where = "";

	// 	if (!empty($_REQUEST['search_keyword'])) {
	// 		$keyword = $this->db->escape_like_str(trim($_REQUEST['search_keyword']));

	// 		$where = "(RU.first_name LIKE '%" . $keyword . "%'
	//             OR RU.last_name LIKE '%" . $keyword . "%'
	//             OR RU.email LIKE '%" . $keyword . "%')";
	// 	}

	// 	$result = $this->user_model->recommendUser_detail($where, array(), $start, $end);
	// 	$recommendUserCount = $this->user_model->recommendUser_detail($where, array('count'));

	// 	$countData = $end;
	// 	$countData++;
	// 	if (!empty($result)) {

	// 		foreach ($result as $key => $value) {

	// 			$checkemail = "email = '" . $result[$key]['email'] . "'";
	// 			$mailcheck = $this->common->getData('user', $checkemail, array("single"));
	// 			if ($mailcheck) {
	// 				$result[$key]['recommended_user_id'] = $mailcheck['user_id'];
	// 			} else {
	// 				$result[$key]['recommended_user_id'] = "0";
	// 			}
	// 			$result[$key]['sno'] = $countData++;
	// 		}

	// 		$this->response(
	// 			true,
	// 			"Data fetch Successfully.",
	// 			array(
	// 				"lists" => $result,
	// 				"listCount" => $recommendUserCount
	// 			)
	// 		);
	// 	} else {
	// 		$this->response(
	// 			true,
	// 			"Data fetch Successfully.",
	// 			array(
	// 				"lists" => array(),
	// 				"listCount" => $recommendUserCount
	// 			)
	// 		);
	// 	}
	// }

	// updated by @krishn on 17-06-26
	public function recommendUser_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "";

		/* Search */
		if (!empty($_REQUEST['search_keyword'])) {

			$keyword = $this->db->escape_like_str(trim($_REQUEST['search_keyword']));

			$where .= "(
					RU.first_name LIKE '%{$keyword}%'
					OR RU.last_name LIKE '%{$keyword}%'
					OR RU.email LIKE '%{$keyword}%'
				)";
		}

		/* Group Filter */
		if (!empty($_REQUEST['group_ids'])) {

			$group_ids = explode(',', $_REQUEST['group_ids']);
			$group_ids = array_map('trim', $group_ids);
			$group_ids = array_map('intval', $group_ids);

			$groupCondition = "
			RU.user_id IN (
				SELECT user_id
				FROM user_group
				WHERE group_id IN (" . implode(',', $group_ids) . ")
			)
		";

			if (!empty($where)) {
				$where .= " AND ";
			}

			$where .= $groupCondition;
		}


		/* Circle Filter */
		if (!empty($_REQUEST['circle_ids'])) {

			$circle_ids = explode(',', $_REQUEST['circle_ids']);
			$circle_ids = array_map('trim', $circle_ids);
			$circle_ids = array_map('intval', $circle_ids);

			$circleCondition = "
			RU.user_id IN (
				SELECT user_id
				FROM user_circle
				WHERE circle_id IN (" . implode(',', $circle_ids) . ")
			)
		";

			if (!empty($where)) {
				$where .= " AND ";
			}

			$where .= $circleCondition;
		}


		$result = $this->user_model->recommendUser_detail($where, array(), $start, $end);

		$recommendUserCount = $this->user_model->recommendUser_detail(
			$where,
			array('count')
		);

		$countData = $end + 1;

		if (!empty($result)) {

			foreach ($result as $key => $value) {

				$checkemail = "email = '" . $value['email'] . "'";

				$mailcheck = $this->common->getData(
					'user',
					$checkemail,
					array("single")
				);

				if ($mailcheck) {
					$result[$key]['recommended_user_id'] = $mailcheck['user_id'];
				} else {
					$result[$key]['recommended_user_id'] = "0";
				}

				$result[$key]['sno'] = $countData++;
			}

			$this->response(
				true,
				"Data fetch Successfully.",
				array(
					"lists" => $result,
					"listCount" => $recommendUserCount
				)
			);
		} else {

			$this->response(
				true,
				"Data fetch Successfully.",
				array(
					"lists" => array(),
					"listCount" => $recommendUserCount
				)
			);
		}
	}

	public function recommendUserApprovalTracking()
	{
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}

		$where = array();
		if (!empty($_REQUEST['id'])) {
			$where['RU.id'] = $_REQUEST['id'];
		}

		$result = $this->user_model->recommendUserApprovalTracking_detail($where, array(), $start, $end);
		// $recommendUserCount = $this->user_model->recommendUserApprovalTracking_detail($where, array('count'));

		$countData = $end;
		$countData++;

		if (!empty($result)) {
			foreach ($result as $key => $value) {
				$approvals = $this->common->getData('recommendation_approvals', array('recommend_id' => $value['id']), array('sort_by' => 'id', 'sort_direction' => 'ASC'));
				if (empty($approvals)) {
					$approvals = array();
				}
				$approvalSteps = array();
				$hasDeclined = false;
				$hasPending = false;
				$adminApproved = false;
				$currentPendingRole = "";

				foreach ($approvals as $approval) {
					$approvalStatus = "pending";
					if ($approval['status'] == '1') {
						$approvalStatus = "approved";
					} elseif ($approval['status'] == '2') {
						$approvalStatus = "declined";
					}

					if ($approval['status'] == '2') {
						$hasDeclined = true;
					}

					if ($approval['status'] == '0') {
						$hasPending = true;
						if ($currentPendingRole == "") {
							$currentPendingRole = $approval['approver_role'];
						}
					}

					if ($approval['approver_role'] == 'admin' && $approval['status'] == '1') {
						$adminApproved = true;
					}

					if ($approval['approver_role'] == 'admin') {
						$approver = $this->common->getData('superAdmin', array('id' => $approval['approver_id']), array('single'));
						$approverName = !empty($approver) ? $approver['name'] : "";
						$approverEmail = !empty($approver) ? $approver['email'] : "";
					} else {
						$approver = $this->common->getData('user', array('user_id' => $approval['approver_id']), array('single'));
						$approverName = !empty($approver) ? trim($approver['first_name'] . ' ' . $approver['last_name']) : "";
						$approverEmail = !empty($approver) ? $approver['email'] : "";
					}

					$approvalSteps[] = array(
						"id" => $approval['id'],
						"recommend_id" => $approval['recommend_id'],
						"approver_id" => $approval['approver_id'],
						"approver_role" => $approval['approver_role'],
						"approver_name" => $approverName,
						"approver_email" => $approverEmail,
						"status" => $approvalStatus,
						"status_code" => $approval['status']
					);
				}

				$overallStatus = "not_started";
				if ($hasDeclined) {
					$overallStatus = "declined";
				} elseif ($adminApproved) {
					$overallStatus = "approved";
				} elseif ($hasPending) {
					$overallStatus = "pending";
				} elseif (!empty($approvals)) {
					$overallStatus = "in_progress";
				}

				if (!empty($value['signup_form']) && $value['signup_form'] == '1') {
					$overallStatus = "registered";
				}

				$result[$key]['sno'] = $countData++;
				$result[$key]['primary_recommender_name'] = trim($value['first_name_main_refer'] . ' ' . $value['last_name_main_refer']);
				$result[$key]['second_recommender_name'] = trim($value['first_name_refer'] . ' ' . $value['last_name_refer']);
				$result[$key]['overall_status'] = $overallStatus;
				$result[$key]['current_pending_role'] = $currentPendingRole;
				$result[$key]['approval_steps'] = $approvalSteps;
			}

			$this->response(true, "Recommendation approval tracking fetched successfully.", array("lists" => $result[0], /*"listCount" => $recommendUserCount*/));
		} else {
			$this->response(true, "Recommendation approval tracking fetched successfully.", array("lists" => array(), /*"listCount" => $recommendUserCount*/));
		}
	}

	public function recommendUser_status_160525()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		$status  =  0;
		// limit code end
		if ($_REQUEST['admin_status'] == '1') {
			$status  =  '1';

			$recommenduser = $this->common->getData('recommend_user', array('id' => $_REQUEST['id']), array('single'));

			$result = $this->common->getData('user', array('email' => $recommenduser['email']), array('single'));

			if (!empty($result)) {
				$result1 =   $this->common->query_normal("UPDATE user SET recommended = '0' WHERE `user_id` = '" . $result['user_id'] . "'");
			}
		} else if ($_REQUEST['admin_status'] == '2') {
			$status  =  '2';
		}

		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);
		$post = $this->common->getField('recommend_user', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('recommend_user', $post, array('id' => $id));
		}

		if (!empty($result)) {
			if ($status == '1') {
				$subject = "Joining Instructions";
				$message = '<p style="margin-bottom:10px;">I am writing to provide you with the necessary account details for the upcoming Interfriends cycle.</p>
				<p><strong>UNITED KINGDOM USERS</strong></p>
				<p>Account Name: Interfriends</p>
				<p>Bank Name: Lloyds Bank</p>
				<p>Account Number: 32774168</p>
				<p>Sort Code: 30-98-97</p>
				<p>Reference: Your unique ID followed by SVS (we will send your unique ID separately)</p>
				<p>Please note that there are two savings cycles, one starting in January and the other in July. Payments must be made between the 1st and the last day of each month by 4:00 pm.</p>
				<p>Any payments made after the deadline may negatively impact your Interfriends Trust Score.</p>
				<p>To access your Interfriends dashboard, please follow this link and enter the email used for your application: <a href="' . USER_BASE_URL . '">Click Here</a> for Login</p>
				<p>If you have forgotten your password, you can click on \'forgotten password\' to create a new one.</p>
				<p>Thank you for your attention to this matter.</p>';

				$mail = $this->sendMail($recommenduser['email'], $subject, $message);
				if ($mail) {
					// *******************************************
					$_REQUEST['created_at'] = date('Y-m-d H:i:s');
					$groupcircle = $this->common->getData('user_circle', array("user_id" => $result['user_id']), array('single'));
					$groupuser = $this->common->getData('user', array("user_id" => $result['user_id']), array('single'));

					$recommenderGroupDetails = $this->common->getData('user_circle', array("user_id" => $recommenduser['refer_user_id']), array('single'));

					if (!empty($groupcircle)) {
						$this->response(false, "This user " . $groupuser['first_name'] . " is already in another circle");
						die();
					} else {
						$newArr = array(
							"group_id" => $recommenderGroupDetails['group_id'],
							"circle_id" => $recommenderGroupDetails['circle_id'],
							"user_id" => $result['user_id'],
							"created_at" => $_REQUEST['created_at']
						);
						$post = $this->common->getField('user_circle', $newArr);
						$addedInCricle = $this->common->insertData('user_circle', $post);

						if ($addedInCricle) {
							$this->common->deleteData('recommend_user', array('id' => $id));
						}
					}
					// *******************************************
				}
				$this->response(true, "Accepted Successfully.", array("lists" => $result));
			}

			if ($status == '2') {
				$this->response(true, "Rejected Successfully.", array("lists" => $result));
			}
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => 0));
			// $this->response(true, "No update performed.", ["lists" => [], "listCount" => 0]);
		}
	}

	// created by @krishn on 15-05-25
	public function recommendUser_status()
	{
		// Limit code start
		$start = !empty($_REQUEST['start']) ? $_REQUEST['start'] : 0;
		$limit = 10; // not used anywhere, remove if unnecessary
		// Limit code end

		if (empty($_REQUEST['id']) || empty($_REQUEST['admin_status'])) {
			$this->response(false, "Missing required parameters.");
			return;
		}

		$id = $_REQUEST['id'];
		$adminStatus = $_REQUEST['admin_status'];
		$status = ($adminStatus == '1') ? '1' : (($adminStatus == '2') ? '2' : '0');

		// Update status in recommend_user
		$postData = $this->common->getField('recommend_user', $_REQUEST);
		if (!empty($postData)) {
			$updateResult = $this->common->updateData('recommend_user', $postData, ['id' => $id]);
		}

		if (!empty($updateResult)) {
			// Get recommended user details
			$recommendUser = $this->common->getData('recommend_user', ['id' => $id], ['single']);

			if ($status === '1') {
				// If user already exists, mark as not recommended
				$existingUser = $this->common->getData('user', ['email' => $recommendUser['email']], ['single']);

				if (!empty($existingUser)) {
					$this->common->updateData('user', array('recommended' => '0'), array('user_id' => $existingUser['user_id']));
				}

				// check limit exceeded or not
				$recommenderGroupDetails = $this->common->getData('user_circle', ['user_id' => $recommendUser['refer_user_id']], ['single']);

				$groupCircleMemberCount = $this->common->getData('user_circle', array("group_id" => $recommenderGroupDetails['group_id'], "circle_id" => $recommenderGroupDetails['circle_id']), array('count'));

				if ($groupCircleMemberCount > 25) {
					$this->common->updateData('user', array('exist_in_waiting' => 1), array('user_id' => $existingUser['user_id']));

					$superAdmin = $this->common->getData('superAdmin', ['admin_type' => '2']);

					foreach ($superAdmin as $adminUser) {
						$subject = "User Added in the Waiting List";

						$data['sendername'] = $adminUser['name'];
						$data['message'] = '<p>We regret to inform you that the user (<strong>' . $existingUser['first_name'] . '</strong>) could not be added to the circle because the member limit for this circle has been exceeded.</p>
						<p><strong>' . $existingUser['first_name'] . '</strong> has been added to the waiting list.</p>
						<p>Please review the circle settings or consider increasing the member limit if necessary.</p>';

						$messaged = $this->load->view('template/common-mail', $data, true);
						$mail = $this->sendMail($adminUser['email'], $subject, $messaged);
					}

					$this->response(true, "Limit exceeded. User added in the waiting list.");
					die();
				}

				// Send Joining Instructions Email
				$subject = "Joining Instructions";
				$data['sendername'] = $recommendUser['first_name'];
				$data['message'] = '<p style="margin-bottom:10px;">I am writing to provide you with the necessary account details for the upcoming Interfriends cycle.</p>
                <h4><strong>UNITED KINGDOM USERS</strong></h4>
                <p><strong>Account Name:</strong> Interfriends</p>
                <p><strong>Bank Name:</strong> Lloyds Bank</p>
                <p><strong>Account Number:</strong> 32774168</p>
                <p><strong>Sort Code:</strong> 30-98-97</p>
                <p><strong>Reference:</strong> Your unique ID followed by SVS (we will send your unique ID separately)</p>
                <p>Please note that there are two savings cycles, one starting in January and the other in July. Payments must be made between the 1st and the last day of each month by 4:00 pm.</p>
                <p>Any payment made after the deadline may negatively impact your Interfriends Trust Score.</p>
                <p>To access your Interfriends dashboard, please follow this link and enter the email used for your application: <a href="' . USER_BASE_URL . '">Click Here</a> for Login</p>
                <p>If you have forgotten your password, you can click on \'forgotten password\' to create a new one.</p>
                <p>Thank you for your attention to this matter.</p>';

				$messaged = $this->load->view('template/common-mail', $data, true);
				$mail = $this->sendMail($recommendUser['email'], $subject, $messaged);

				if ($mail) {
					$_REQUEST['created_at'] = date('Y-m-d H:i:s');
					$groupUser = $this->common->getData('user', ['user_id' => $existingUser['user_id']], ['single']);

					// Add user to group
					$groupDetail = $this->common->getData('user_group', ['user_id' => $existingUser['user_id']], ['single']);

					if (!empty($groupDetail)) {
						$this->response(false, "This user " . $groupUser['first_name'] . " is already in another group");
						return;
					}

					// Add user to circle
					$groupCircle = $this->common->getData('user_circle', ['user_id' => $existingUser['user_id']], ['single']);

					if (!empty($groupCircle)) {
						$this->response(false, "This user " . $groupUser['first_name'] . " is already in another circle");
						return;
					}

					if (!empty($recommenderGroupDetails)) {
						// user added in group
						$insertGroupData = [
							"group_id" => $recommenderGroupDetails['group_id'],
							"user_id" => $existingUser['user_id'],
							"created_at" => $_REQUEST['created_at']
						];

						$groupPost = $this->common->getField('user_group', $insertGroupData);
						$addedInGroup = $this->common->insertData('user_group', $groupPost);

						// user added in circle
						$insertCricleData = [
							"group_id" => $recommenderGroupDetails['group_id'],
							"circle_id" => $recommenderGroupDetails['circle_id'],
							"user_id" => $existingUser['user_id'],
							"created_at" => $_REQUEST['created_at']
						];

						$circlePost = $this->common->getField('user_circle', $insertCricleData);
						$addedInCircle = $this->common->insertData('user_circle', $circlePost);

						if ($addedInCircle && $addedInGroup) {
							// Remove recommended user entry
							$this->common->deleteData('recommend_user', array('id' => $id));

							// Fetch users in same circle/group excluding the newly added user
							$circleUsers = $this->common->getData('user_circle', [
								'group_id' => $recommenderGroupDetails['group_id'],
								'circle_id' => $recommenderGroupDetails['circle_id']
							]);

							foreach ($circleUsers as $userCircle) {
								if ($userCircle['user_id'] != $existingUser['user_id']) { // Don't send to the newly added user
									$userDetails = $this->common->getData('user', ['user_id' => $userCircle['user_id']], ['single']);
									if (!empty($userDetails)) {
										$email = $userDetails['email'];
										$subject = "New Member Joined Your Circle";
										$data['sendername'] = $userDetails['first_name'];
										$data['message'] = "<p>We are pleased to inform you that <strong>" . $groupUser['first_name'] . "</strong> has successfully completed the registration process and is now a member of your circle.</p>";

										$messaged = $this->load->view('template/common-mail', $data, true);
										$this->sendMail($email, $subject, $messaged);
									}
								}
							}
						}
					}
				}

				$this->response(true, "Accepted Successfully.", ["lists" => $updateResult]);
			}
			if ($status === '2') {
				// send mail to circle lead/deputy circle lead, recommender and new member
				$recommender = $this->common->getData('user', ['user_id' => $recommendUser['user_id']], ['single']);

				// Get circle/group info of recommender
				$circleDetails = $this->common->getData('user_circle', ['user_id' => $recommender['user_id']], ['single']);

				if (!empty($circleDetails)) {
					$circle_id = $circleDetails['circle_id'];
					$group_id = $circleDetails['group_id'];

					// Circle Lead
					$lead = $this->common->getData('user_circle', array("circle_id" => $circle_id, "circle_lead" => '1', "group_id" => $group_id), array('single'));
					if (!empty($lead)) {
						$leadUser = $this->common->getData('user', ['user_id' => $lead['user_id']], ['single']);
						if (!empty($leadUser)) {
							$data['sendername'] = $leadUser['first_name'];
							$data['message'] = "<p>The application of <strong>{$recommendUser['first_name']}</strong> has been <strong>rejected</strong> by the admin.</p>";
							$messaged = $this->load->view('template/common-mail', $data, true);
							$this->sendMail($leadUser['email'], "User Recommendation Rejected", $messaged);
						}
					}

					// Deputy Circle Lead
					$deputy = $this->common->getData('user_circle', array("circle_id" => $circle_id, "deputycirclelead" => '1', "group_id" => $group_id), array('single'));
					if (!empty($deputy)) {
						$deputyUser = $this->common->getData('user', ['user_id' => $deputy['user_id']], ['single']);
						if (!empty($deputyUser)) {
							$data['sendername'] = $deputyUser['first_name'];
							$data['message'] = "<p>The application of <strong>{$recommendUser['first_name']}</strong> has been <strong>rejected</strong> by the admin.</p>";
							$messaged = $this->load->view('template/common-mail', $data, true);
							$this->sendMail($deputyUser['email'], "User Recommendation Rejected", $messaged);
						}
					}

					// Recommender
					$recommender = $this->common->getData('user', ['user_id' => $recommendUser['refer_user_id']], ['single']);
					if (!empty($recommender)) {
						$data['sendername'] = $recommender['first_name'];
						$data['message'] = "<p>The user you recommended (<strong>{$recommendUser['first_name']}</strong>) has been <strong>rejected</strong> by the admin.</p>";
						$messaged = $this->load->view('template/common-mail', $data, true);
						$this->sendMail($recommender['email'], "Your Recommendation Was Rejected", $messaged);
					}

					// Recommended Member (Applicant)
					$data['sendername'] = $recommendUser['first_name'];
					$data['message'] = "<p>We regret to inform you that your application has been <strong>rejected</strong> by the admin.</p>";
					$messaged = $this->load->view('template/common-mail', $data, true);
					$this->sendMail($recommendUser['email'], "Application Rejected", $messaged);
				}
				$this->response(true, "Rejected Successfully.", ["lists" => $updateResult]);
			}
		} else {
			$this->response(true, "No update performed.", ["lists" => [], "listCount" => 0]);
		}
	}


	// public function safe_keeping_withdral_request_list()
	// {
	// 	// limit code start
	// 	if (empty($_REQUEST['start'])) {
	// 		$start = 10;
	// 		$end = 0;
	// 	} else {
	// 		$start = 10;
	// 		$end = $_REQUEST['start'];
	// 	}
	// 	// limit code end

	// 	$where = 'U.status != 2';
	// 	if (!empty($_REQUEST['group_ids'])) {
	// 		$groupIds = array_map('intval', explode(',', $_REQUEST['group_ids']));
	// 		$groupIds = implode(',', $groupIds);
	// 		$where .= " AND SK.group_id IN ($groupIds)";
	// 	}

	// 	$result = $this->user_model->safe_keeping_withdral_detail($where, array(), $start, $end);
	// 	$resultCount = $this->user_model->safe_keeping_withdral_detail($where, array('count'));

	// 	$countData = $end;
	// 	$countData++;
	// 	if (!empty($result)) {

	// 		foreach ($result as $key => $value) {
	// 			$result[$key]['sno'] = $countData++;
	// 		}

	// 		$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount));
	// 	} else {
	// 		$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount));
	// 	}
	// }

	// created by krishn 19-06-2026
	public function safe_keeping_withdral_request_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "U.status != 2";

		// Filter by groups (only if provided)
		if (!empty($_REQUEST['group_ids'])) {
			$group_ids = explode(',', $_REQUEST['group_ids']);
			$group_ids = array_map('trim', $group_ids);
			$group_ids = array_map('intval', $group_ids);

			$where .= " AND SK.group_id IN (" . implode(',', $group_ids) . ")";
		}

		// Filter by circles (only if provided)
		if (!empty($_REQUEST['circle_ids'])) {
			$circle_ids = explode(',', $_REQUEST['circle_ids']);
			$circle_ids = array_map('trim', $circle_ids);
			$circle_ids = array_map('intval', $circle_ids);

			$where .= " AND UC.circle_id IN (" . implode(',', $circle_ids) . ")";
		}

		$result = $this->user_model->safe_keeping_withdral_detail($where, array(), $start, $end);
		$resultCount = $this->user_model->safe_keeping_withdral_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array(
				"lists" => $result,
				"listCount" => $resultCount
			));
		} else {
			$this->response(true, "Data fetch Successfully.", array(
				"lists" => array(),
				"listCount" => $resultCount
			));
		}
	}

	// created by @krishn on 23-06-25
	// public function safekeeping_request_list()
	// {
	// 	// Pagination setup
	// 	$limit = 10;
	// 	$start = !empty($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

	// 	// Group ID filtering
	// 	$searchCond = '';
	// 	if (!empty($_REQUEST['group_ids'])) {
	// 		$groupIds = array_map('intval', explode(',', $_REQUEST['group_ids']));
	// 		$groupIds = implode(',', $groupIds);
	// 		$searchCond .= " AND SK.group_id IN ($groupIds)";
	// 	}

	// 	// Search keyword filtering
	// 	$search = !empty($_REQUEST['search']) ? $this->db->escape_like_str(trim($_REQUEST['search'])) : '';
	// 	// if (!empty($search)) {
	// 	// 	$searchCond .= " AND (U.first_name LIKE '%$search%' OR U.last_name LIKE '%$search%' OR U.email LIKE '%$search%')";
	// 	// }

	// 	if (!empty($search)) {
	// 		$searchCond = " AND (
	// 			U.first_name LIKE '%{$search}%' ESCAPE '!' OR 
	// 			U.last_name LIKE '%{$search}%' ESCAPE '!' OR 
	// 			U.email LIKE '%{$search}%' ESCAPE '!'
	// 		)";
	// 	}

	// 	// SQL parts
	// 	$table = 'safe_keeping SK, user U';
	// 	$field = 'SK.id, SK.user_id, U.first_name, U.last_name, U.email, SK.amount, SK.amount_total, SK.created_at, SK.request_status';
	// 	$fullWhere = "SK.user_id = U.user_id AND U.status != '2' AND SK.group_id != 34 AND SK.requested_by = 'user' $searchCond";

	// 	// Group by (optional)
	// 	$groupBy = '';
	// 	$options = [
	// 		'field' => $field,
	// 		'limit' => $limit,
	// 		'offset' => $start,
	// 		'sort_by' => 'created_at',
	// 		'sort_direction' => 'desc',
	// 	];
	// 	if (!empty($groupBy)) {
	// 		$options['group_by'] = $groupBy;
	// 	}

	// 	// Fetch data
	// 	$resultList = $this->common->getData($table, $fullWhere, $options);

	// 	// Count total
	// 	$totalOptions = ['count'];
	// 	if (!empty($groupBy)) {
	// 		$totalOptions['group_by'] = $groupBy;
	// 	}
	// 	$resultCountData = $this->common->getData($table, $fullWhere, $totalOptions);
	// 	$resultCount = is_array($resultCountData) ? count($resultCountData) : 0;

	// 	// Add serial numbers
	// 	$countData = $start + 1;
	// 	if (!empty($resultList)) {
	// 		foreach ($resultList as &$row) {
	// 			$row['sno'] = $countData++;
	// 		}
	// 		$this->response(true, "Data fetched successfully.", [
	// 			"lists" => $resultList,
	// 			"listCount" => $resultCount
	// 		]);
	// 	} else {
	// 		$this->response(true, "No data found.", [
	// 			"lists" => [],
	// 			"listCount" => 0
	// 		]);
	// 	}
	// }

	// updated by @krishn on 19-06-26
	public function safekeeping_request_list()
	{
		// Pagination setup
		$limit = 10;
		$start = !empty($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;

		$searchCond = "";

		/* Group Filter */
		if (!empty($_REQUEST['group_ids'])) {
			$groupIds = array_map('intval', explode(',', $_REQUEST['group_ids']));
			$groupIds = implode(',', $groupIds);

			$searchCond .= " AND SK.group_id IN ($groupIds)";
		}

		/* Circle Filter */
		if (!empty($_REQUEST['circle_ids'])) {
			$circleIds = array_map('intval', explode(',', $_REQUEST['circle_ids']));
			$circleIds = implode(',', $circleIds);

			$searchCond .= " AND EXISTS (
			SELECT 1
			FROM user_circle UC
			WHERE UC.user_id = SK.user_id
			AND UC.circle_id IN ($circleIds)
		)";
		}

		/* Search Filter */
		$search = !empty($_REQUEST['search'])
			? $this->db->escape_like_str(trim($_REQUEST['search']))
			: '';

		if (!empty($search)) {
			$searchCond .= " AND (
			U.first_name LIKE '%{$search}%'
			OR U.last_name LIKE '%{$search}%'
			OR U.email LIKE '%{$search}%'
		)";
		}

		$table = 'safe_keeping SK, user U';

		$field = '
			SK.id,
			SK.user_id,
			U.first_name,
			U.last_name,
			U.email,
			SK.amount,
			SK.amount_total,
			SK.created_at,
			SK.request_status
		';

		$fullWhere = "
			SK.user_id = U.user_id
			AND U.status != '2'
			AND SK.group_id != 34
			AND SK.requested_by = 'user'
			$searchCond
		";

		/* Listing */
		$options = array(
			'field' => $field,
			'limit' => $limit,
			'offset' => $start,
			'sort_by' => 'SK.created_at',
			'sort_direction' => 'DESC'
		);

		$resultList = $this->common->getData(
			$table,
			$fullWhere,
			$options
		);

		/* Count */
		$resultCountData = $this->common->getData(
			$table,
			$fullWhere,
			array('field' => 'SK.id')
		);

		$resultCount = !empty($resultCountData)
			? count($resultCountData)
			: 0;

		/* Serial Number */
		$countData = $start + 1;

		if (!empty($resultList)) {

			foreach ($resultList as &$row) {
				$row['sno'] = $countData++;
			}

			$this->response(true, "Data fetched successfully.", array(
				"lists" => $resultList,
				"listCount" => $resultCount
			));
		} else {

			$this->response(true, "No data found.", array(
				"lists" => array(),
				"listCount" => $resultCount
			));
		}
	}

	public function removeSafekeepingRequest()
	{
		$id = $this->input->post('safekeeping_id');

		if (!empty($id)) {
			$where = ['id' => $id];
			$deleted = $this->common->deleteData('safe_keeping_withdral_request', $where);

			if ($deleted) {
				$this->response(true, "Safekeeping request deleted successfully.");
			} else {
				$this->response(false, "Failed to delete safekeeping request. Please try again.");
			}
		} else {
			$this->response(false, "ID cannot be empty.");
		}
	}



	// public function investment_request_list()
	// {
	// 	// limit code start
	// 	if (empty($_REQUEST['start'])) {
	// 		$start = 10;
	// 		$end = 0;
	// 	} else {
	// 		$start = 10;
	// 		$end = $_REQUEST['start'];
	// 	}
	// 	// limit code end

	// 	$where = 'U.status != 2';
	// 	if (!empty($_REQUEST['group_ids'])) {
	// 		$groupIds = array_map('intval', explode(',', $_REQUEST['group_ids']));
	// 		$groupIds = implode(',', $groupIds);
	// 		$where .= " AND IR.group_id IN ($groupIds)";
	// 	}

	// 	$result = $this->user_model->investment_request_detail($where, array(), $start, $end);
	// 	$resultCount = $this->user_model->investment_request_detail($where, array('count'));

	// 	$countData = $end;
	// 	$countData++;
	// 	if (!empty($result)) {

	// 		foreach ($result as $key => $value) {
	// 			$result[$key]['sno'] = $countData++;
	// 		}

	// 		$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount));
	// 	} else {
	// 		$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount));
	// 	}
	// }

	// created by @krishn on 19-06-26
	public function investment_request_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "U.status != 2";

		/* Group Filter */
		if (!empty($_REQUEST['group_ids'])) {

			$groupIds = array_map('intval', explode(',', $_REQUEST['group_ids']));
			$groupIds = implode(',', $groupIds);

			$where .= " AND IR.group_id IN ($groupIds)";
		}

		/* Circle Filter */
		if (!empty($_REQUEST['circle_ids'])) {

			$circleIds = array_map('intval', explode(',', $_REQUEST['circle_ids']));
			$circleIds = implode(',', $circleIds);

			$where .= " AND EXISTS (
			SELECT 1
			FROM user_circle UC
			WHERE UC.user_id = IR.user_id
			AND UC.circle_id IN ($circleIds)
		)";
		}

		$result = $this->user_model->investment_request_detail(
			$where,
			array(),
			$start,
			$end
		);

		$resultCount = $this->user_model->investment_request_detail(
			$where,
			array('count')
		);

		$countData = $end;
		$countData++;

		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(
				true,
				"Data fetch Successfully.",
				array(
					"lists" => $result,
					"listCount" => $resultCount
				)
			);
		} else {

			$this->response(
				true,
				"Data fetch Successfully.",
				array(
					"lists" => array(),
					"listCount" => $resultCount
				)
			);
		}
	}

	public function all_user_list()
	{
		$result = $this->common->getData('user', "", array("field" => "user.user_id as item_id,user.first_name as item_text"));
		if (!empty($result)) {
			$this->response(true, "User fetch Successfully.", array("userList" => $result));
		} else {
			$this->response(true, "User fetch Successfully.", array("userList" => array()));
		}
	}



	public function adduserGroup()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$userArr = explode(",", $_REQUEST['users']);

		foreach ($userArr as $key => $value) {
			$newArr = array("group_id" => $_REQUEST['id'], "user_id" => $value, "created_at" => $_REQUEST['created_at']);

			$post = $this->common->getField('user_group', $newArr);
			$result = $this->common->insertData('user_group', $post);
		}


		if ($result) {
			$this->response(true, "add user successfully, Please enter each user amount for cycle");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function userGroup_detail()
	{
		$result = $this->common->getData('user_group', array('id' => $_REQUEST['id']), array('single'));
		if (!empty($result)) {
			$this->response(true, "group fetch Successfully.", array("groupDetail" => $result));
		} else {
			$this->response(false, "Group not found", array("groupDetail" => array()));
		}
	}


	public function editUserGroup()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);
		$post = $this->common->getField('user_group', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('user_group', $post, array('id' => $id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Member Info Successfully Updated");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	public function chekgroupLifeCycleExist()
	{
		$result = $this->common->getData('group_lifecycle', array('group_id' => $_REQUEST['group_id']), array('sort_by' => 'id', 'sort_direction' => 'desc', 'single'));

		$current_date = date('Y-m-d H:i:s');
		$showStatus = false;
		if (!empty($result)) {
			if ($result['end_date'] < $current_date) {
				$showStatus = true;
			}

			$this->response(true, "group cycle fetch Successfully.", array("groupDetail" => $result, 'showStatus' => $showStatus, 'showMessage' => 'You can only create new cycle once current cycle will completed'));
		} else {
			$showStatus = true;
			$this->response(true, "group cycle fetch Successfully.", array("groupDetail" => array(), 'showStatus' => $showStatus, 'showMessage' => ''));
		}
	}

	// created by @krishn on 22-05-25
	public function addPayout()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$_REQUEST['requested_by'] = isset($_POST['requested_by']) ? $_POST['requested_by'] : 'admin'; // Default to admin
		$_REQUEST['request_status'] = ($_REQUEST['requested_by'] == 'user') ? 2 : 1; // Pending if user initiates

		$payOutExist = $this->common->getData('payout_cycle', array(
			'user_id' => $_POST['user_id'],
			'group_id' => $_POST['group_id'],
			'group_cycle_id' => $_POST['group_cycle_id']
		), array('single'));

		if (!empty($payOutExist)) {
			$this->response(false, "Payout already done");
			return;
		}

		// Compute payout details regardless of request type
		$groupPercentResult = $this->user_model->getLifeCyclePercent(array('GL.id' => $_POST['group_cycle_id']), array('single'));
		$pfPercent = !empty($groupPercentResult) ? (int)$groupPercentResult['percent'] : 10;

		$result = $this->common->getData('user_group_lifecycle', array(
			'groupLifecycle_id' => $_REQUEST['group_cycle_id'],
			'user_id' => $_REQUEST['user_id']
		), array('field' => 'SUM(amount) as total_amount', 'single'));

		$_REQUEST['payout_amount_total'] = $result['total_amount'];
		$_REQUEST['payout_pf_amount'] = ($_REQUEST['payout_amount_total'] * $pfPercent) / 100;
		$_REQUEST['payout_amount'] = $_REQUEST['payout_amount_total'] - $_REQUEST['payout_pf_amount'];
		$_REQUEST['pf_interest_amount'] = ($_REQUEST['payout_pf_amount'] * $pfPercent) / 100;
		$_REQUEST['payout_pf_percent'] = $pfPercent . "%";
		$_REQUEST['pf_interest_percent'] = $pfPercent . "%";

		$post = $this->common->getField('payout_cycle', $_REQUEST);
		$result = $this->common->insertData('payout_cycle', $post);
		$payout_id = $this->db->insert_id();

		if ($_REQUEST['requested_by'] == 'user') {
			if ($result) {
				$this->response(true, "Your payout request has been sent to the admin for approval.");
			} else {
				$this->response(false, "There was a problem submitting your request. Please try again.");
			}
			return;
		}

		// If admin requests, execute full payout process
		if ($result) {
			$this->processPayout($payout_id);

			$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
			$data['useremail'] = "";
			$data['message'] = '<p>This is a confirmation that your PAYOUT for this cycle has been processed and paid into your account.</p><p>If you were not expecting this payment, please do let us know.</p>';
			$messaged = $this->load->view('template/common-mail', $data, true);
			$mail = $this->sendMail($userDetailFrom['email'], 'Payout Request', $messaged);

			if ($mail) {
				$group_id = $_REQUEST['group_id'];

				$where = "FIND_IN_SET('$group_id', group_ids) > 0 OR admin_type = 2";
				$superAdmin = $this->common->getData('superAdmin', $where);

				foreach ($superAdmin as $adminUser) {
					$subject = "Payout Successfully Processed";

					$data['sendername'] = $adminUser['name'];
					$data['message'] = '<p>We’re pleased to inform you that the PAYOUT for this cycle has been successfully processed.</p>
					<p>If you did not expect this payment or believe there is an issue, please contact our support team immediately.</p>';

					$messaged = $this->load->view('template/common-mail', $data, true);
					$mail = $this->sendMail($userDetailFrom['email'], $subject, $messaged);
				}
			}
			$this->response(true, "Payout Successfully Processed");
		} else {
			$this->response(false, "There was a problem processing the payout.");
		}
	}

	// created by @krishn on 22-05-25
	public function updatePayoutRequestStatus()
	{
		$payout_id = $_POST['payout_id'];
		$request_status = $_POST['request_status']; // 1 = Accepted, 0 = Rejected
		$admin_id = $_POST['admin_id'] ?? null;

		if (!in_array($request_status, [0, 1])) {
			$this->response(false, "Invalid request status. Choose 1 (Accepted) or 0 (Rejected).");
			return;
		}

		$payoutRequest = $this->common->getData('payout_cycle', ['id' => $payout_id, 'request_status' => 2], ['single']);

		if (empty($payoutRequest)) {
			$this->response(false, "Payout request not found or already processed.");
			return;
		}

		$update = $this->common->updateData('payout_cycle', ['request_status' => $request_status], ['id' => $payout_id]);

		if ($update) {
			$userDetails = $this->common->getData('user', ['user_id' => $payoutRequest['user_id']], ['single']);
			$data['sendername'] = $userDetails['first_name'] . ' ' . $userDetails['last_name'];

			if ($request_status == 1) {
				$this->processPayout($payout_id);
				$subject = 'Payout Request Approved';
				$data['message'] = '<p>We are pleased to inform you that your payout request has been approved by the administrator.</p>
                <p>The approved amount will be processed shortly. If you have any questions, feel free to contact our support team.</p>';

				$this->send_nofification($payoutRequest['user_id'], $admin_id, $payoutRequest['group_id'], "Your payout request was approved.", $payout_id, "13");
				$this->response(true, "Payout request accepted and processed.");
			} else {
				$where = " id ='" . $payout_id . "'";
				$this->common->deleteData('payout_cycle', $where);

				$subject = 'Payout Request Rejected';
				$data['message'] = '<p>We regret to inform you that your payout request has been rejected by the administrator.</p>
                <p>If you believe this was done in error or have any questions, please contact our support team.</p>';

				$this->send_nofification($payoutRequest['user_id'], $admin_id, $payoutRequest['group_id'], "Your payout request was rejected.", $payout_id, "13");
				$this->response(true, "Payout request rejected.");
			}

			$messaged = $this->load->view('template/common-mail', $data, true);
			$this->sendMail($userDetails['email'], $subject, $messaged);
		} else {
			$this->response(false, "Failed to update payout request.");
		}
	}


	// created by @krishn on 22-05-25
	private function processPayout($payout_id)
	{
		$payoutData = $this->common->getData('payout_cycle', array('id' => $payout_id), array('single'));

		$_REQUEST = $payoutData; // Load saved request details
		$_REQUEST['request_status'] = 1; // Mark as accepted

		// Insert cycle status management
		$this->common->insertData('cycle_status_management', array(
			"group_id" => $_REQUEST['group_id'],
			"group_cycle_id" => $_REQUEST['group_cycle_id'],
			"user_id" => $_REQUEST['user_id'],
			"type" => '1',
			'created_at' => $_REQUEST['created_at']
		));

		// Insert payout details in `pf_user`
		$this->common->insertData('pf_user', array(
			"group_id" => $_REQUEST['group_id'],
			"user_id" => $_REQUEST['user_id'],
			"pf_type" => '1',
			"payment_type" => '2',
			'created_at' => $_REQUEST['created_at'],
			'pf_amount' => $_REQUEST['payout_pf_amount'],
			'pf_percent' => $_REQUEST['payout_pf_percent'],
			'pf_interest_amount' => $_REQUEST['pf_interest_amount'],
			'pf_interest_percent' => $_REQUEST['pf_interest_percent'],
			'main_id' => $_REQUEST['group_cycle_id'],
			'other_main_id' => $payout_id
		));

		// Notify user about successful payout
		$message = "Your bulk funds have been paid";
		$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $payout_id, "13");

		return true;
	}

	public function showPayout()
	{
		$current_date = date('Y-m-d H:i:s');

		$payOutExist = $this->common->getData('payout_cycle', array('user_id' => $_POST['user_id'], 'group_id' => $_POST['group_id'], 'group_cycle_id' => $_POST['group_cycle_id']), array('single'));

		if (!empty($payOutExist)) {
			$showPayoutButton = false;
		} else {
			$showPayoutButton = true;
		}

		$showAlertMessage = false;
		$userInfo = $this->common->getData('user_group', array('user_id' => $_POST['user_id'], 'group_id' => $_POST['group_id']), array('single'));

		if (!empty($userInfo)) {
			if (!empty($userInfo['expected_date'])) {
				if ($current_date >= $userInfo['expected_date']) {
					$showAlertMessage = true;
				} else {
					$showAlertMessage = false;
				}
			} else {
				$showAlertMessage = false;
			}
		} else {
			$showAlertMessage = false;
		}

		$this->response(true, "fetch status successfully", array("showPayoutButton" => $showPayoutButton, "showAlertMessage" => $showAlertMessage, "showAlredyPayoutAlertMessage" => !$showPayoutButton));
	}

	//  created by @krishn on 24-06-25
	public function addSafeKeeping()
	{
		$dataExist = $this->common->getData('cycle_status_management', [
			'user_id' => $_POST['user_id'],
			'group_id' => $_POST['group_id'],
			'group_cycle_id' => $_POST['group_cycle_id']
		], ['single']);

		if (!empty($dataExist)) {
			$this->response(false, "Cycle already transferred.");
			die();
		}

		$requester = $_REQUEST['requested_by'] ?? 'user';
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$_REQUEST['request_status'] = ($requester === 'admin') ? 1 : 2;

		// Always do calculation
		$groupPercentResult = $this->user_model->getLifeCyclePercent(['GL.id' => $_POST['group_cycle_id']], ['single']);
		$pfPercent = !empty($groupPercentResult) ? (int)$groupPercentResult['percent'] : 10;

		$result = $this->common->getData('user_group_lifecycle', [
			'groupLifecycle_id' => $_REQUEST['group_cycle_id'],
			'user_id' => $_REQUEST['user_id']
		], ['field' => 'SUM(amount) as total_amount', 'single']);

		$amount_total = $result['total_amount'];
		$pf_amount = ($amount_total * $pfPercent) / 100;
		$pf_interest_amount = ($pf_amount * $pfPercent) / 100;

		// Assign values
		$_REQUEST['amount_total'] = $amount_total;
		$_REQUEST['pf_amount'] = $pf_amount;
		$_REQUEST['amount'] = $amount_total - $pf_amount;
		$_REQUEST['pf_interest_amount'] = $pf_interest_amount;
		$_REQUEST['pf_percent'] = $pfPercent . "%";
		$_REQUEST['pf_interest_percent'] = $pfPercent . "%";

		// Insert into safekeeping
		$post = $this->common->getField('safe_keeping', $_REQUEST);
		$result = $this->common->insertData('safe_keeping', $post);
		$insert_id = $this->db->insert_id();

		if (!$result) {
			$this->response(false, "There is a problem, please try again.");
		}

		// If requested_by = user, return early (after calculations + insert)
		if ($requester === 'user') {
			$this->response(true, "Safekeeping request submitted successfully.");
			return;
		}

		// ==== Admin: run full logic ====
		$this->common->insertData('cycle_status_management', [
			"group_id" => $_REQUEST['group_id'],
			"group_cycle_id" => $_REQUEST['group_cycle_id'],
			"user_id" => $_REQUEST['user_id'],
			"type" => '2',
			'created_at' => $_REQUEST['created_at']
		]);

		$this->common->insertData('pf_user', [
			"group_id" => $_REQUEST['group_id'],
			"user_id" => $_REQUEST['user_id'],
			"pf_type" => '2',
			"payment_type" => '2',
			'created_at' => $_REQUEST['created_at'],
			'pf_amount' => $_REQUEST['pf_amount'],
			'pf_percent' => $_REQUEST['pf_percent'],
			'pf_interest_amount' => $_REQUEST['pf_interest_amount'],
			'pf_interest_percent' => $_REQUEST['pf_interest_percent'],
			'main_id' => $_REQUEST['group_cycle_id'],
			'other_main_id' => $insert_id
		]);

		$message = "Your bulk funds sent for safekeeping";
		$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $insert_id, "14");

		$userDetailFrom = $this->common->getData('user', ['user_id' => $_REQUEST['user_id']], ['single']);

		$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
		$data['useremail'] = "";
		$data['message'] = '<p>This confirms that your request has been successfully processed and deposited into your account or securely placed in safekeeping where applicable.</p><p>If you have any questions or concerns about this payment, please do not hesitate to contact us.</p>';

		$messaged = $this->load->view('template/common-mail', $data, true);
		$this->sendMail($userDetailFrom['email'], 'Safekeeping', $messaged);

		$this->common->query_normal("UPDATE credit_score_user SET safekeeping_money = safekeeping_money + 0 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");

		$this->updateCreditScore(0, 'plus');

		$this->response(true, "Safekeeping entry added successfully.");
	}

	public function acceptRejectSafekeepingRequest()
	{
		$id = $_REQUEST['safekeeping_id'] ?? null;
		$request_status = $_REQUEST['request_status'] ?? null; // 1 = accept, 0 = reject

		if ($id === null || ($request_status !== '1' && $request_status !== '0')) {
			$this->response(false, "Invalid request parameters.");
			return;
		}

		// Fetch the safekeeping entry
		$safekeeping = $this->common->getData('safe_keeping', ['id' => $id], ['single']);

		if (empty($safekeeping)) {
			$this->response(false, "Safekeeping request not found.");
			return;
		}

		// === If REJECTED ===
		if ($request_status == '0') {
			// Delete the safekeeping record
			$this->common->deleteData('safe_keeping', ['id' => $id]);
			$this->response(true, "Safekeeping request rejected and entry removed.");
			return;
		}

		// === If ACCEPTED ===
		// Update the request_status to 1
		$this->common->updateData('safe_keeping', ['request_status' => 1], ['id' => $id]);

		$user_id = $safekeeping['user_id'];
		$group_id = $safekeeping['group_id'];
		$group_cycle_id = $safekeeping['group_cycle_id'];

		// Prevent duplicate insertion
		$dataExist = $this->common->getData('cycle_status_management', [
			'user_id' => $user_id,
			'group_id' => $group_id,
			'group_cycle_id' => $group_cycle_id
		], ['single']);

		if (!empty($dataExist)) {
			$this->response(false, "Cycle already transferred.");
			return;
		}

		$created_at = $safekeeping['created_at'] ?? date('Y-m-d H:i:s');
		$pf_amount = $safekeeping['pf_amount'];
		$pf_percent = $safekeeping['pf_percent'];
		$pf_interest_amount = $safekeeping['pf_interest_amount'];
		$pf_interest_percent = $safekeeping['pf_interest_percent'];

		// Insert into cycle_status_management
		$this->common->insertData('cycle_status_management', [
			"group_id" => $group_id,
			"group_cycle_id" => $group_cycle_id,
			"user_id" => $user_id,
			"type" => '2',
			'created_at' => $created_at
		]);

		// Insert into pf_user
		$this->common->insertData('pf_user', [
			"group_id" => $group_id,
			"user_id" => $user_id,
			"pf_type" => '2',
			"payment_type" => '2',
			'created_at' => $created_at,
			'pf_amount' => $pf_amount,
			'pf_percent' => $pf_percent,
			'pf_interest_amount' => $pf_interest_amount,
			'pf_interest_percent' => $pf_interest_percent,
			'main_id' => $group_cycle_id,
			'other_main_id' => $id
		]);

		// Send notification
		$this->send_nofification($user_id, $_REQUEST['admin_id'], $group_id, "Your safekeeping request has been accepted", $id, "14");

		// Send email
		$userDetailFrom = $this->common->getData('user', ['user_id' => $user_id], ['single']);
		$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
		$data['useremail'] = "";
		$data['message'] = '<p>This confirms that your request has been successfully processed and deposited into your account or securely placed in safekeeping where applicable.</p><p>If you have any questions or concerns about this payment, please do not hesitate to contact us.</p>';
		$messaged = $this->load->view('template/common-mail', $data, true);
		$this->sendMail($userDetailFrom['email'], 'Safekeeping Accepted', $messaged);

		// Update credit score
		$this->common->query_normal("UPDATE credit_score_user SET safekeeping_money = safekeeping_money + 0 WHERE user_id = '$user_id'");
		$this->updateCreditScore(0, 'plus');

		$this->response(true, "Safekeeping request accepted successfully.");
	}



	public function creditSafeKeeping()
	{

		$safet_keeping_debit = $this->common->getData('safe_keeping', array('user_id' => $_POST['user_id'], 'group_id' => $_POST['group_id'], 'pyment_type' => '1'), array('field' => 'COALESCE(SUM(amount), 0) as total_amount', 'single'));


		$safet_keeping_credit = $this->common->getData('safe_keeping', array('user_id' => $_POST['user_id'], 'group_id' => $_POST['group_id'], 'pyment_type' => '2'), array('field' => 'COALESCE(SUM(amount), 0) as total_amount', 'single'));

		$amount_limit = $safet_keeping_debit['total_amount'] - $safet_keeping_credit['total_amount'];

		if ($amount_limit < $_REQUEST['amount']) {
			$this->response(false, "Amount limit is excede, you have add less then " . $amount_limit);
			die();
		}


		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$_REQUEST['pyment_type'] = '2';
		$post = $this->common->getField('safe_keeping', $_REQUEST);
		$result = $this->common->insertData('safe_keeping', $post);

		if ($result) {
			$this->response(true, "Add Safe Keeping Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function safeKeeping_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$where = "SK.user_id = '" . $_REQUEST['user_id'] . "' AND SK.group_id = '" . $_REQUEST['group_id'] . "'";
		$result = $this->user_model->safeKeeping_detail($where, array(), $start, $end);
		$resultCount = $this->user_model->safeKeeping_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}


			$totalCreditAmount = $this->common->getData('safe_keeping', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'pyment_type' => 2), array("field" => 'sum(amount) as safe_keeping_total_amount', "single"));


			$totalDebitAmount = $this->common->getData('safe_keeping', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'pyment_type' => 1), array("field" => 'sum(amount) as safe_keeping_total_amount', "single"));


			$safeKeepingAmount = $totalCreditAmount['safe_keeping_total_amount'] - $totalDebitAmount['safe_keeping_total_amount'];

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount, 'safeKeepingAmount' => $safeKeepingAmount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount, 'safeKeepingAmount' => 0));
		}
	}


	public function payout_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$where = "P.user_id = '" . $_REQUEST['user_id'] . "' AND P.group_id = '" . $_REQUEST['group_id'] . "'";
		$result = $this->user_model->payout_detail($where, array(), $start, $end);
		$resultCount = $this->user_model->payout_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount));
		}
	}

	public function getAllPayoutList()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "P.requested_by = 'user'";

		if (!empty($_REQUEST['group_ids'])) {
			$group_ids = explode(',', $_REQUEST['group_ids']);
			$group_ids = array_map('trim', $group_ids);
			$group_ids = array_map('intval', $group_ids);

			$where .= " AND P.group_id IN (" . implode(',', $group_ids) . ")";
		}

		if (!empty($_REQUEST['circle_ids'])) {
			$circle_ids = explode(',', $_REQUEST['circle_ids']);
			$circle_ids = array_map('trim', $circle_ids);
			$circle_ids = array_map('intval', $circle_ids);

			$where .= " AND UC.circle_id IN (" . implode(',', $circle_ids) . ")";
		}

		$payout = $this->user_model->payout_detail($where, array(), $start, $end);
		$payoutCount = $this->user_model->payout_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($payout)) {

			foreach ($payout as $key => $value) {
				$payout[$key]['sno'] = $countData++;
			}

			foreach ($payout as $key => $value) {
				$payout[$key]['user_info'] = get_user_details($value['user_id']);
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $payout, "listCount" => $payoutCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $payoutCount));
		}
	}



	public function showStatus()
	{
		$current_date = date('Y-m-d H:i:s');

		$result = $this->common->getData('cycle_status_management', array('user_id' => $_POST['user_id'], 'group_id' => $_POST['group_id'], 'group_cycle_id' => $_POST['group_cycle_id']), array('single'));

		$message  = '';
		if (!empty($result)) {
			$showButton = false;
			if ($result['type'] == '1') {
				$message = 'Bulk funds paid this cycle';
			}

			if ($result['type'] == '2') {
				$message = 'Member’s bulk payment diverted to safekeeping';
			}
		} else {
			$showButton = true;
		}


		// show  expected date alert message
		$showAlertMessage = false;
		$userInfo = $this->common->getData('user_group', array('user_id' => $_POST['user_id'], 'group_id' => $_POST['group_id']), array('single'));

		if (!empty($userInfo)) {
			if (!empty($userInfo['expected_date'])) {
				if ($current_date >= $userInfo['expected_date']) {
					$showAlertMessage = true;
				} else {
					$showAlertMessage = false;
				}
			} else {
				$showAlertMessage = false;
			}
		} else {
			$showAlertMessage = false;
		}

		$this->response(true, "fetch status successfully", array("showButton" => $showButton, "showAlertMessage" => $showAlertMessage, "showAlreadyAlertMessage" => !$showButton, "message" => $message));
	}

	public function loanList()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end


		$where = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND  L.loan_type = '1'";

		if (!empty($_REQUEST['admin_type'] === '2')) {
			$where .= " AND L.status != '6'"; // L.status != '1' AND 
		}

		$result = $this->user_model->loan_detail($where, array(), $start, $end);
		$resultCount = $this->user_model->loan_detail($where, array('count'), $start, $end);
		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount));
		}
	}

	// created by @krishn on 31-07-25
	public function loanList_Help2pay()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND  L.loan_type = '" . $_REQUEST['loan_type'] . "'";

		if (!empty($_REQUEST['admin_type'] === '2')) {
			$where .= " AND L.status != '6'";
		}

		$result = $this->user_model->loan_detail($where, array(), $start, $end);
		$resultCount = $this->user_model->loan_detail($where, array('count'), $start, $end);
		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;

				if (!empty($value['document_image'])) {
					$docUrl = base_url($value['document_image']);
					$result[$key]['document_image'] = $docUrl;

					// Check for PDF extension (case-insensitive)
					$ext = pathinfo($value['document_image'], PATHINFO_EXTENSION);
					$result[$key]['is_pdf'] = (strtolower($ext) === 'pdf') ? true : false;
				} else {
					$result[$key]['document_image'] = "assets/img/default-user-icon.jpg";
					$result[$key]['is_pdf'] = false;
				}
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount));
		}
	}

	public function loanList_emergencyHelp()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "UE.user_id = '" . $_REQUEST['user_id'] . "' AND UE.group_id = '" . $_REQUEST['group_id'] . "'";

		if (!empty($_REQUEST['admin_type'] === '2')) {
			$where .= " AND  UE.status != '6'";
		}

		$result = $this->user_model->emergencyLoan_detail($where, array(), $start, $end);
		$emergencyCount = $this->user_model->emergencyLoan_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $emergencyCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $emergencyCount));
		}
	}


	public function payoutDetail()
	{
		$payoutCycle = $this->common->getData('payout_cycle', array('group_id' => $_REQUEST['group_id'], 'group_cycle_id' => $_REQUEST['group_cycle_id'], 'user_id' => $_REQUEST['user_id']));
		if (!empty($payoutCycle)) {
			$this->response(true, 'group fetch successfully', array('payoutCycle' => $payoutCycle));
		} else {
			$this->response(true, 'group not found', array('payoutCycle' => array()));
		}
	}

	// created by @krishn on 25-06-25
	public function safekeepingDetail()
	{
		$safekeepingCycle = $this->common->getData('safe_keeping', array('group_id' => $_REQUEST['group_id'], 'group_cycle_id' => $_REQUEST['group_cycle_id'], 'user_id' => $_REQUEST['user_id']));
		if (!empty($safekeepingCycle)) {
			$this->response(true, 'data fetch successfully', array('safekeepingCycle' => $safekeepingCycle));
		} else {
			$this->response(true, 'data not found', array('safekeepingCycle' => array()));
		}
	}


	public function loanDetail()
	{
		$loanDetail = $this->common->getData('user_loan', array('id' => $_REQUEST['id']), array('single'));
		if (!empty($loanDetail)) {
			$this->response(true, 'loan fetch successfully', array('loanDetail' => $loanDetail));
		} else {
			$this->response(true, 'loan not found', array('loanDetail' => array()));
		}
	}



	function send_nofification($user_id, $admin_id, $group_id, $message, $id, $type)
	{
		$userDetailTo = $this->common->getData('user', array('user_id' => $user_id), array('single'));
		$userDetailFrom = $this->common->getData('superAdmin', array('id' => $admin_id), array('single'));
		// print_r($admin_id);
		//     die;
		$this->common->query_normal("UPDATE user SET notification_count = notification_count+1 WHERE `user_id` = '" . $user_id . "'");

		$userName = '';
		$userName = $userDetailFrom['name'];
		$title = $userName;

		$this->common->insertData('notification_tbl', array("message" => $message, "user_send_to" => $user_id, "group_id" => $group_id, "user_send_from" => $admin_id, "main_id" => $id, "created_at" => date('Y-m-d H:i:s'), "type" => $type));



		$msg_notification = array(
			"body" => $message,
			"title" => $title,
			"icon" => ASSET_BASE_URL . 'icons/i_white.jfif'
		);

		// for web		
		if (!empty($userDetailTo['web_token'])) {
			$data = array(
				"to_user_id" => $user_id,
				"from_user_id" => $admin_id,
				'type' => $type
			);
			$res = $this->send_notification_web($userDetailTo['web_token'], $msg_notification, $data);
		}
	}


	function send_nofification_admin($user_id, $admin_id, $group_id, $message, $id, $type, $user_type)
	{

		if ($user_type === '1') {
			$userDetailTo = $this->common->getData('superAdmin', array('admin_type' => '2'));
		} else {
			$userDetailTo = $this->common->getData('superAdmin', array('admin_type' => '1'));
		}

		$token_arr = array();
		if (!empty($userDetailTo)) {
			foreach ($userDetailTo as $key => $value) {
				if (!empty($value['web_token'])) {
					$token_arr[] = $value['web_token'];
				}
			}
		}


		$userDetailFrom = $this->common->getData('superAdmin', array('id' => $admin_id), array('single'));

		$this->common->query_normal("UPDATE superAdmin SET notification_count = notification_count+1 WHERE `admin_type` = '2'");


		$userName = '';
		$userName = $userDetailFrom['name'];
		$title = $userName;

		$this->common->insertData('notification_admin_tbl', array("message" => $message, "group_id" => $group_id, "user_id" => $user_id, "user_send_from" => $admin_id, "main_id" => $id, "created_at" => date('Y-m-d H:i:s'), "type" => $type, "user_type" => $user_type));

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
			$where = "N.user_type = '" . $_REQUEST['user_type'] . "'";
			$notificationInfo = $this->user_model->notificationAdmin_detail($where, array(), $start, $end);

			if (!empty($notificationInfo)) {
				// foreach ($notificationInfo as $key => $value) {

				//    }


				$result = $this->common->updateData('superAdmin', array('notification_count' => '0'), array('id' => $user_id));

				$this->response(true, "Notification List.", array("lists" => $notificationInfo));
			} else {
				$this->response(false, "Notification Not Found.", array("lists" => array()));
			}
		} else {
			$this->response(false, "Missing Parameter.");
		}
	}


	public function get_notification_count()
	{
		$userInfo = $this->common->getData('superAdmin', array('id' => $_REQUEST['user_id']), array('single'));

		$this->response(false, "Notification Count Found Successfully.", array("count" => $userInfo['notification_count']));
	}



	public function loanStatusHistoryDetail()
	{
		$loanDetail = $this->common->getData('user_loan_status_history', array('loan_id' => $_REQUEST['loan_id']));
		if (!empty($loanDetail)) {
			$this->response(true, 'loan fetch successfully', array('loanDetail' => $loanDetail));
		} else {
			$this->response(true, 'loan not found', array('loanDetail' => array()));
		}
	}


	public function emergencyLoanStatusHistoryDetail()
	{
		$loanDetail = $this->common->getData('user_emergency_loan_status_history', array('loan_id' => $_REQUEST['loan_id']));
		if (!empty($loanDetail)) {
			$this->response(true, 'loan fetch successfully', array('loanDetail' => $loanDetail));
		} else {
			$this->response(true, 'loan not found', array('loanDetail' => array()));
		}
	}



	public function miscellaneousLoanStatusHistoryDetail()
	{
		$miscellaneousDetail = $this->common->getData('user_miscellaneous_status_history', array('miscellaneous_id' => $_REQUEST['miscellaneous_id']));
		if (!empty($miscellaneousDetail)) {
			$this->response(true, 'Data fetch successfully', array('miscellaneousDetail' => $miscellaneousDetail));
		} else {
			$this->response(true, 'loan not found', array('miscellaneousDetail' => array()));
		}
	}



	public function loanStatusHistory_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$where = "ULSH.loan_id = '" . $_REQUEST['loan_id'] . "'";
		$result = $this->user_model->loanStatusHistory_detail($where, array(), $start, $end);
		$resultCount = $this->user_model->loanStatusHistory_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount));
		}
	}

	public function miscellaneousStatusHistory_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$where = "UMSH.miscellaneous_id = '" . $_REQUEST['miscellaneous_id'] . "'";
		$result = $this->user_model->miscellaneousStatusHistory_detail($where, array(), $start, $end);
		$resultCount = $this->user_model->miscellaneousStatusHistory_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount));
		}
	}


	public function userCycleStatusHistoryDetail()
	{
		$result = $this->common->getData('user_cycle_status_history', array('lifecycle_id' => $_REQUEST['lifecycle_id']));
		if (!empty($result)) {
			$this->response(true, 'cycle fetch successfully', array('cycleDetail' => $result));
		} else {
			$this->response(true, 'cycle not found', array('cycleDetail' => array()));
		}
	}



	public function userGroupList()
	{
		$where = "UG.user_id = '" . $_REQUEST['user_id'] . "'";
		$result = $this->user_model->userGroup_detail($where);
		if (!empty($result)) {
			$this->response(true, 'cycle fetch successfully', array('lists' => $result));
		} else {
			$this->response(true, 'cycle not found', array('lists' => array()));
		}
	}


	public function userCycleStatusHistory_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$where = "UCSH.lifecycle_id = '" . $_REQUEST['lifecycle_id'] . "'";
		$result = $this->user_model->userCycleStatusHistory_detail($where, array(), $start, $end);
		$resultCount = $this->user_model->userCycleStatusHistory_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount));
		}
	}



	public function pf_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$where = "PF.user_id = '" . $_REQUEST['user_id'] . "' AND PF.group_id = '" . $_REQUEST['group_id'] . "'";
		$result = $this->user_model->pf_detail($where, array(), $start, $end);
		$resultCount = $this->user_model->pf_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$totalCreditpfAmount = $this->common->getData('pf_user', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'payment_type' => '2'), array("field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest', "single"));

			$totalDebitpfAmount = $this->common->getData('pf_user', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'payment_type' => '1'), array("field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest', "single"));

			$creditPfAmount = (float)($totalCreditpfAmount['pf_total_amount'] ?? 0);
			$debitPfAmount  = (float)($totalDebitpfAmount['pf_total_amount'] ?? 0);

			$creditPfInterest = (float)($totalCreditpfAmount['pf_interest'] ?? 0);
			$debitPfInterest  = (float)($totalDebitpfAmount['pf_interest'] ?? 0);

			// Same logic as user panel
			$pfAmount = $creditPfAmount - $debitPfAmount;

			// Keep interest separate
			$pfInterest = $creditPfInterest - $debitPfInterest;

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount, 'pfAmount' => $pfAmount, 'pfInterest' => $pfInterest));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount, 'pfAmount' => 0));
		}
	}




	public function addInvestment()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		if ($_REQUEST['property_id'] === 'others') {
			$_REQUEST['investment_type'] = '2';
			$_REQUEST['property_id'] = '';
		} else {
			$_REQUEST['investment_type'] = '1';
		}


		if ($_REQUEST['payment_method'] === '3') {
			$this->checkpaymentBySafekeeping($_REQUEST['amount']);
		}

		if ($_REQUEST['payment_method'] === '2') {
			$this->checkpaymentByPF($_REQUEST['amount']);
		}

		$post = $this->common->getField('investment', $_REQUEST);
		$result = $this->common->insertData('investment', $post);
		$investment_id = $this->db->insert_id();

		if ($result) {

			if ($_REQUEST['payment_method'] === '3') {
				$this->paymentBySafekeeping($investment_id, $_REQUEST['amount'], '5', '0');
			}


			if ($_REQUEST['payment_method'] === '2') {
				$this->paymentByPF($investment_id, $_REQUEST['amount'], '5');
			}

			$message = "Admin updated your intestment info";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $investment_id, "4");

			$this->common->query_normal("UPDATE credit_score_user SET investment_money = investment_money+0 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
			$this->updateCreditScore(0, 'plus');

			$this->response(true, "Add Investment Successfully", array("investment_id" => $investment_id));
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function editInvestment()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);

		if ($_REQUEST['payment_method'] === '3') {
			$this->paymentBySafekeeping($id, $_REQUEST['amount'], '5', '0');
		}

		if ($_REQUEST['payment_method'] === '2') {
			$this->paymentByPF($id, $_REQUEST['amount'], '5');
		}

		$post = $this->common->getField('investment', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('investment', $post, array('id' => $id));
		} else {
			$result = "";
		}
		$method = "";
		if ($_REQUEST['payment_method'] == '1') {

			$method = 'Direct Transfer';
		} else if ($_REQUEST['payment_method'] == '2') {
			$method = 'From Provident';
		} else {
			$method =  'From Safe Keeping';
		}

		$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

		$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
		$data['useremail'] = "";
		$data['message'] = 'Admin updated your intestment info <p>Payment Method:' . $method . '</p> 
		<p>Amount:' . $_REQUEST['amount'] . '</p>';

		$messaged = $this->load->view('template/common-mail', $data, true);
		$mail = $this->sendMail($userDetailFrom['email'], 'Investment', $messaged);



		if ($result) {
			$this->response(true, "Investment Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function investment_list()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "I.user_id = '" . $_REQUEST['user_id'] . "' AND I.group_id = '" . $_REQUEST['group_id'] . "'";

		if (!empty($_REQUEST['payment_status'])) {
			$where .= " AND I.payment_status = '" . $_REQUEST['payment_status'] . "'";
		}


		$result = $this->user_model->investment_detail($where, array(), $start, $end);
		$resultCount = $this->user_model->investment_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "Data fetch Successfully.", array("lists" => $result, "listCount" => $resultCount));
		} else {
			$this->response(true, "Data fetch Successfully.", array("lists" => array(), "listCount" => $resultCount));
		}
	}



	public function investment_detail()
	{
		$result = $this->common->getData('investment', array('id' => $_REQUEST['id']), array('single'));

		if (!empty($result)) {
			if ($result['investment_type'] == '2') {
				$result['property_id'] = 'others';
				$result['property_info'] = array();
			} else {
				$result['property_info'] = $this->common->getData('property', array('id' => $result['property_id']), array('single'));

				if (!empty($result['property_info'])) {
					if (!empty($result['property_info']['property_image'])) {
						$result['property_info']['property_image'] = base_url($result['property_info']['property_image']);
						$result['property_info']['property_image_thumb'] = base_url($result['property_info']['property_image_thumb']);
					} else {
						$result['property_info']['property_image'] = 'assets/img/default-user-icon.jpg';
						$result['property_info']['property_image_thumb'] = 'assets/img/default-user-icon.jpg';
					}
				}
			}



			$this->response(true, "Investment fetch Successfully.", array("investmentDetail" => $result));
		} else {
			$this->response(false, "Investment not found", array("investmentDetail" => array()));
		}
	}

	function paymentBySafekeeping($id, $amount_paid, $payment_by, $request_type)
	{
		//ini_set('display_errors', 1);
		$amount = safeKeepingTotal($_REQUEST['group_id'], $_REQUEST['user_id']);
		$note_title = '';
		$note_description = '';
		$paidAvgAmount = 0.00;
		if (!empty($_REQUEST['note_title'])) {
			$note_title = $_REQUEST['note_title'];
		}

		$note_description = '';
		if (!empty($_REQUEST['note_description'])) {
			$note_description = $_REQUEST['note_description'];
		}

		$avgAmountSafeKeeping = "";
		if ($request_type === '1') {

			$grouplifecycle = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id'], "group_type_id" => '1'), array('sort_by' => 'id', 'sort_direction' => 'desc'));
			//	$_REQUEST['group_cycle_id'] =$grouplifecycle[0]['id'];
			//$paidAvgAmount = 0.00;
			$cycleTransfer = $this->common->getData('cycle_status_management', array('user_id' => $_REQUEST['user_id'], 'group_id' => $_REQUEST['group_id'], 'group_cycle_id' => $grouplifecycle[0]['id']), array('single'));
			if (empty($cycleTransfer)) {
				$where = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $grouplifecycle[0]['id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
				$result = $this->common->getData('user_group_lifecycle', $where, array("field" => 'sum(amount) as total_payment', "single"));

				if (!empty($result['total_payment'])) {
					$avgAmountSafeKeeping = $result['total_payment'];
				} else {
					$avgAmountSafeKeeping = 0.00;
				}
			} else {
				$paidWhere = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
				$paidResult = $this->common->getData('user_group_lifecycle', $paidWhere, array("field" => 'sum(amount) as total_payment', "single"));

				if (!empty($paidResult['total_payment'])) {
					$paidAvgAmount = $paidResult['total_payment'];
				} else {
					$paidAvgAmount = 0.00;
				}


				$result = $this->common->getData('user_group_lifecycle', array('groupLifecycle_id' => $_REQUEST['group_cycle_id'], 'user_id' => $_REQUEST['user_id']), array('field' => 'SUM(amount) as total_amount', 'single'));

				$payout_amount_total = $result['total_amount'];


				$avgAmountSafeKeeping = $paidAvgAmount - $payout_amount_total;
			}


			if ($avgAmountSafeKeeping >= $amount_paid) {

				$getresult = $this->common->getData('safe_keeping', array('user_id' => $_REQUEST['user_id'], "group_id" => $_REQUEST['group_id'], "main_id" => $id, "amount" => $amount_paid, "pyment_type" => '1', "payment_by" => $payment_by, "DATE(created_at)" => date('Y-m-d')), array('single'));

				if (empty($getresult)) {
					$result = $this->common->insertData('safe_keeping', array(
						"user_id" => $_REQUEST['user_id'],
						"group_id" => $_REQUEST['group_id'],
						"main_id" => $_REQUEST['safe_keeping_id'],
						"amount" => $amount_paid,
						"pyment_type" => '1',
						"payment_by" => $payment_by,
						"note_title" => $note_title,
						"note_description" => $note_description,
						"created_at" => date('Y-m-d H:i:s'),
						'payment_method' => '4'
					));

					if ($_REQUEST['safe_keeping_id']) {

						$result = $this->common->updateData('safe_keeping_withdral_request', array('request_status' => '1'), array('id' => $_REQUEST['safe_keeping_id']));
					}

					return;
				} else {

					if ($_REQUEST['safe_keeping_id']) {

						$result = $this->common->updateData('safe_keeping_withdral_request', array('request_status' => '0'), array('id' => $_REQUEST['safe_keeping_id']));
					}

					$this->response(false, "Payment Failed", array('request_status' => 0));
					die();
				}
			}
		} else {

			if ($amount >= $amount_paid) {

				$getresult = $this->common->getData('safe_keeping', array('user_id' => $_REQUEST['user_id'], "group_id" => $_REQUEST['group_id'], "main_id" => $id, "amount" => $amount_paid, "pyment_type" => '1', "payment_by" => $payment_by, "DATE(created_at)" => date('Y-m-d')), array('single'));

				if (empty($getresult)) {
					$result = $this->common->insertData('safe_keeping', array("user_id" => $_REQUEST['user_id'], "group_id" => $_REQUEST['group_id'], "main_id" => $id, "amount" => $amount_paid, "pyment_type" => '1', "payment_by" => $payment_by, "note_title" => $note_title, "note_description" => $note_description, "created_at" => date('Y-m-d H:i:s')));

					if ($_REQUEST['safe_keeping_id']) {

						$result = $this->common->updateData('safe_keeping_withdral_request', array('request_status' => '1'), array('id' => $_REQUEST['safe_keeping_id']));
					}

					return;
				} else {


					if ($_REQUEST['safe_keeping_id']) {

						$result = $this->common->updateData('safe_keeping_withdral_request', array('request_status' => '0'), array('id' => $_REQUEST['safe_keeping_id']));
					}

					$this->response(false, "Payment Failed", array('request_status' => 0));
					die();
				}
			} else {
				$this->response(false, "Payment Failed, your Safe Keeping have " . $amount . " Amount", array('request_status' => 2));
				die();
			}
		}
	}


	public function debit_pf()
	{
		if ($_REQUEST['payment_method'] === '6') {
			$this->paymentByPF('', $_REQUEST['amount'], $_REQUEST['payment_method']);
		} else {
			$this->paymentByPF('', $_REQUEST['amount'], $_REQUEST['payment_method']);

			$result = $this->common->insertData('safe_keeping', array("user_id" => $_REQUEST['user_id'], "group_id" => $_REQUEST['group_id'], "main_id" => '', "amount" => $_REQUEST['amount'], "pyment_type" => '2', "payment_by" => 6, "created_at" => date('Y-m-d H:i:s')));
		}




		$this->response(true, "Payment debited successfully");
	}



	public function debit_Safekeeping()
	{
		//  ini_set('display_errors', 1);
		//$_REQUEST['request_type'] = '1';
		$this->paymentBySafekeeping('', $_REQUEST['amount'], $_REQUEST['payment_method'], $_REQUEST['request_type']);

		$this->response(true, "Payment debited successfully", array('request_status' => 1));
	}


	function paymentByPF($id, $amount_paid, $payment_by)
	{

		$amount = pfTotal($_REQUEST['group_id'], $_REQUEST['user_id']);
		if ($amount >= $amount_paid) {
			$note_title = '';
			$note_description = '';
			if (!empty($_REQUEST['note_title'])) {
				$note_title = $_REQUEST['note_title'];
			}

			$note_description = '';
			if (!empty($_REQUEST['note_description'])) {
				$note_description = $_REQUEST['note_description'];
			}

			$result = $this->common->insertData('pf_user', array("user_id" => $_REQUEST['user_id'], "group_id" => $_REQUEST['group_id'], "main_id" => $id, "pf_amount" => $amount_paid, "payment_type" => '1', "payment_by" => $payment_by, "note_title" => $note_title, "note_description" => $note_description, "created_at" => date('Y-m-d H:i:s')));
			return;
		} else {
			$this->response(false, "Payment Failed, your PF have " . $amount . " Amount");
			die();
		}
	}

	function checkpaymentByPF($amount_paid)
	{
		$amount = pfTotal($_REQUEST['group_id'], $_REQUEST['user_id']);

		if ($amount >= $amount_paid) {
			return true;
		} else {
			$this->response(false, "Payment Failed, your PF have " . $amount . " Amount");
			die();
		}
	}


	function checkpaymentBySafekeeping($amount_paid)
	{
		$amount = safeKeepingTotal($_REQUEST['group_id'], $_REQUEST['user_id']);

		if ($amount >= $amount_paid) {
			return true;
		} else {
			$this->response(false, "Payment Failed, your Safe Keeping have " . $amount . " Amount");
			die();
		}
	}



	public function all_property_list()
	{
		$result = $this->common->getData('property', "", array("field" => "property.id as item_id,property.title as item_text"));
		if (!empty($result)) {
			$this->response(true, "property fetch Successfully.", array("propertyList" => $result));
		} else {
			$this->response(true, "property fetch Successfully.", array("propertyList" => array()));
		}
	}


	public function all_pf_percent_list()
	{
		$result = $this->common->getData('pf_percent');
		if (!empty($result)) {
			$this->response(true, "pf percent fetch Successfully.", array("pfpercentList" => $result));
		} else {
			$this->response(true, "pf percent fetch Successfully.", array("pfpercentList" => array()));
		}
	}


	public function all_loan_list()
	{
		$result = $this->common->getData('loan_percent');
		if (!empty($result)) {
			$this->response(true, "loan fetch Successfully.", array("loanList" => $result));
		} else {
			$this->response(true, "loan fetch Successfully.", array("loanList" => array()));
		}
	}

	// created by @krishn on 11-08-25
	public function addBanner()
	{
		if (empty($_REQUEST['title'])) {
			$_REQUEST['title'] = null;
		}

		$_REQUEST['image'] = '';

		if (isset($_FILES['image']) && !empty($_FILES['image']['name'][0])) {
			$images = $this->common->multi_upload('image', './assets/banners/');
			if (!empty($images)) {
				foreach ($images as $image) {
					$_REQUEST['image'] = 'assets/banners/' . $image['file_name'];
					$post = $this->common->getField('tbl_banners', $_REQUEST);
					$result = $this->common->insertData('tbl_banners', $post);
				}
			}
		} else {
			$post = $this->common->getField('tbl_banners', $_REQUEST);
			$result = $this->common->insertData('tbl_banners', $post);
		}

		$result_banner = $this->common->getData('tbl_banners');
		if (!empty($result_banner)) {
			$this->response(true, "Banners fetched successfully.", ["banners" => $result_banner]);
		} else {
			$this->response(true, "Banners fetched successfully.", ["banners" => []]);
		}
	}

	// created by @krishn on 11-08-25
	public function delete_banner()
	{
		if (!empty($_REQUEST['id'])) {
			$where = "id = '" . $this->db->escape_str($_REQUEST['id']) . "'";
			$value = $this->common->deleteData('tbl_banners', $where);
			$this->response(true, "Deleted successfully.");
		} else {
			$this->response(false, "Id can't be empty.");
		}
	}

	// created by @krishn on 11-08-25
	public function allBanners()
	{
		$result_banner = $this->common->getData('tbl_banners');
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

	// created by @krishn on 11-08-25
	public function getBannerDetail()
	{
		if (empty($_REQUEST['id'])) {
			return $this->response(false, "Id can't be empty.");
		}

		$id = $this->db->escape_str($_REQUEST['id']);

		$banner = $this->common->getData('tbl_banners', ["id" => $id]);

		if (!empty($banner)) {
			$banner = $banner[0];

			if (!empty($banner['image'])) {
				$banner['image'] = base_url() . $banner['image'];
			} else {
				$banner['image'] = "";
			}

			$this->response(true, "Banner details fetched successfully.", ["banner" => $banner]);
		} else {
			$this->response(false, "Banner not found.");
		}
	}

	public function addCreditScoreList()
	{

		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$post = $this->common->getField('credit_score_list', $_REQUEST);
		$result = $this->common->insertData('credit_score_list', $post);
		$banners = $this->db->insert_id();

		$credit_score_list = $this->common->getData('credit_score_list');
		if (!empty($credit_score_list)) {
			$this->response(true, "credit_score fetch Successfully.", array("credit_score_list" => $credit_score_list));
		} else {
			$this->response(true, "credit_score fetch Successfully.", array("credit_score_list" => array()));
		}
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

	public function deleteCreditScoreList()
	{
		if (!empty($_REQUEST['id'])) {
			$where = " id ='" . $_REQUEST['id'] . "'";
			$value = $this->common->deleteData('credit_score_list', $where);
			$this->response(true, "Delete Successfully.");
		} else {
			$this->response(false, "Id Can't be empty.");
		}
	}

	public function editCreditScoreList()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);
		$post = $this->common->getField('credit_score_list', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('credit_score_list', $post, array('id' => $id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Credit_score Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	// created by @krishn on 11-08-25
	public function editBanner()
	{
		$id = $this->input->post('id', true);

		if (!$id) {
			return $this->response(false, "Banner ID is required.");
		}

		// Get existing banner
		$banner = $this->common->getData('tbl_banners', ['id' => $id], ['single']);
		if (!$banner) {
			return $this->response(false, "Banner not found.");
		}

		$updateData = [];

		$title = $this->input->post('title', true);
		$updateData['title'] = !empty($title) ? $title : "";

		// Image upload
		if (isset($_FILES['image']) && $_FILES['image']['name'] != '') {
			$upload = $this->common->do_upload('image', './assets/banners/');
			if (!empty($upload['upload_data'])) {
				$updateData['image'] = 'assets/banners/' . $upload['upload_data']['file_name'];
				// Delete old image if exists
				if (!empty($banner['image']) && file_exists(FCPATH . $banner['image'])) {
					unlink(FCPATH . $banner['image']);
				}
			} else {
				return $this->response(false, "Image upload failed.");
			}
		} else {
			$updateData['image'] = $banner['image'];
		}

		$result = $this->common->updateData('tbl_banners', $updateData, ['id' => $id]);

		if ($result) {
			return $this->response(true, "Banner updated successfully.");
		} else {
			return $this->response(false, "No changes made or update failed.");
		}
	}

	function savingAvgCal($group_cycle_id)
	{
		$cycleTransfer = $this->common->getData('cycle_status_management', array('user_id' => $_REQUEST['user_id'], 'group_id' => $_REQUEST['group_id'], 'group_cycle_id' => $group_cycle_id), array('single'));
		// echo "<pre>"; print_r($cycleTransfer); echo "</pre>";
		if (empty($cycleTransfer)) {
			$where = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $group_cycle_id . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
			$result = $this->common->getData('user_group_lifecycle', $where, array("field" => 'sum(amount) as total_payment', "single"));

			if (!empty($result['total_payment'])) {
				$avgAmount = $result['total_payment'];
			} else {
				$avgAmount = 0;
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


	function cylcleAvgPayout($group_cycle_id)
	{
		$amount = 0;
		$where = "group_id = '" . $_REQUEST['group_id'] . "' AND group_cycle_id = '" . $group_cycle_id . "' AND user_id = '" . $_REQUEST['user_id'] . "'";

		$resultpayout = $this->common->getData('payout_cycle', $where, array("field" => 'sum(payout_amount) as total_payment', "single"));

		if (!empty($resultpayout['total_payment'])) {
			$amount = $resultpayout['total_payment'];
		} else {
			$amount = 0;
		}
		return $amount;
	}

	public function userTabsTotal()
	{
		//ini_set('display_errors', 1);
		$usercycle = 0;
		$usercyclejnr = 0;
		$usercyclehelp2buy = 0;
		$payoutCycle = 0;
		$payoutCyclejnr = 0;
		$avgAmountSafeKeeping = 0;
		$result1 = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id'], "group_type_id" => '1'));
		$result2 = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id'], "group_type_id" => '2'));
		$result3 = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id'], "group_type_id" => '3'));

		if (!empty($result1)) {
			foreach ($result1 as $key => $value) {
				$usercycle += $this->savingAvgCal($value['id']);
				$payoutCycle += $this->cylcleAvgPayout($value['id']);
			}
		} else {
			$payoutCycle = 0;
			$usercycle = 0;
		}

		if (!empty($result2)) {
			foreach ($result2 as $key => $value) {
				$usercyclejnr += $this->savingAvgCal($value['id']);
				$payoutCyclejnr += $this->cylcleAvgPayout($value['id']);
			}
		} else {
			$usercyclejnr = 0;
			$payoutCyclejnr = 0;
		}


		//  if(!empty($result3)){
		// 	foreach ($result3 as $key => $value) {
		// 		$usercyclehelp2buy+= $this->savingAvgCal($value['id']);
		// 	}
		// }else{
		// 	$usercyclehelp2buy = 0;
		// }

		$usercyclehelp2buy  = $this->helptobuyTotal();

		$totalSafeCreditAmount = $this->common->getData('safe_keeping', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'pyment_type' => 2), array("field" => 'sum(amount) as safe_keeping_total_amount', "single"));

		$totalSafeDebitAmount = $this->common->getData('safe_keeping', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'pyment_type' => 1), array("field" => 'sum(amount) as safe_keeping_total_amount', "single"));

		$avgAmountSafeKeeping = $totalSafeCreditAmount['safe_keeping_total_amount'] - $totalSafeDebitAmount['safe_keeping_total_amount'];




		$whereActive1 = "L.user_id = '" . $_REQUEST['user_id'] . "' AND L.group_id = '" . $_REQUEST['group_id'] . "' AND L.status = 4 AND L.loan_type= '1'";
		$resultActiveloan = $this->user_model->loan_detail($whereActive1, array());
		$totalPaidAmountloan = 0;
		$totalActivePaymentloan = 0;
		$avgAmountLoan = 0;
		if (!empty($resultActiveloan)) {
			foreach ($resultActiveloan as $key => $value) {
				$resultActiveloan[$key]['paid_amount'] = (float) $value['paid_amount'];
				$resultActiveloan[$key]['total_payment'] = (float) $value['total_payment'];
				$totalPaidAmountloan += $resultActiveloan[$key]['paid_amount'];
				$totalActivePaymentloan += $resultActiveloan[$key]['total_payment'];
			}

			$avgAmountLoan =  $totalPaidAmountloan - $totalActivePaymentloan;
		}





		$whereDivided = "I.user_id = '" . $_REQUEST['user_id'] . "' AND I.group_id = '" . $_REQUEST['group_id'] . "' AND I.payment_status = '1'";
		$dividedTotal = $this->common->getData('investment as I', $whereDivided, array("field" => 'sum(I.amount) as total_payment', "single"));

		if (!empty($dividedTotal['total_payment'])) {
			$totalAmountDivided = $dividedTotal['total_payment'];
		} else {
			$totalAmountDivided = "0";
		}



		$where = "I.user_id = '" . $_REQUEST['user_id'] . "' AND I.group_id = '" . $_REQUEST['group_id'] . "' AND I.payment_status = '2'";
		$investmentTotal = $this->common->getData('investment as I', $where, array("field" => 'sum(I.amount) as total_payment', "single"));
		if (!empty($investmentTotal['total_payment'])) {
			$totalAmountInvestment = $investmentTotal['total_payment'];
		} else {
			$totalAmountInvestment = "0";
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


		$emergencyloanAvgPayment = $this->common->getData('user_emergency_loan', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'status' => '4'), array("field" => 'sum(loan_amount) as total_payment', "single"));


		if ($emergencyloanAvgPayment['total_payment']) {
			$avgAmountEmergencyLoan = $emergencyloanAvgPayment['total_payment'];
		} else {
			$avgAmountEmergencyLoan = 0;
		}


		//       $wherePendingwel = "user_id = '". $_REQUEST['user_id'] ."' AND group_id = '". $_REQUEST['group_id'] ."'AND groupLifecycle_id = '147' AND status = '2'
		// 		    GROUP BY user_id,grand_total_amount,group_id,groupLifecycle_id
		// 		";

		// 		$resultwelTotal = $this->common->getData('user_group_lifecycle',$wherePendingwel,array("field" => 'user_id,group_id,groupLifecycle_id,grand_total_amount',""));



		// 		$wherewelamount = "group_id = '". $_REQUEST['group_id'] ."' AND user_id = '". $_REQUEST['user_id'] ."' AND loan_amount_status = 1";

		// 		$resultAmount = $this->common->getData('user_group_lifecycle',$wherewelamount,array("field" => 'sum(amount) as amount',""));



		$avgwelfareAmount = 0;

		$grouplifecycle = $this->common->getData('group_lifecycle', array("group_id" => $_REQUEST['group_id'], "group_type_id" => '4'), array('sort_by' => 'id', 'sort_direction' => 'desc'));

		$avgAmount = 0;

		$_REQUEST['group_cycle_id'] = $grouplifecycle[0]['id'];

		$cycleTransfer = $this->common->getData('cycle_status_management', array('user_id' => $_REQUEST['user_id'], 'group_id' => $_REQUEST['group_id'], 'group_cycle_id' => $_REQUEST['group_cycle_id']), array('single'));

		if (empty($cycleTransfer)) {

			$where = "group_id = '" . $_REQUEST['group_id'] . "' AND groupLifecycle_id = '" . $_REQUEST['group_cycle_id'] . "' AND user_id = '" . $_REQUEST['user_id'] . "' AND status !='1'";
			$result = $this->common->getData('user_group_lifecycle', $where, array("field" => 'sum(amount) as total_payment', "single"));

			if (!empty($result['total_payment'])) {
				$avgwelfareAmount = $result['total_payment'];
			} else {
				$avgwelfareAmount = 0.00;
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
			$avgwelfareAmount =  $paidAvgAmount -  $payout_amount_total;
		}



		//      $totalPaidAmount = 0;
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
		//         		$avgwelfareAmount = '-'.$totalActivePayment;

		//         }



		$totalCreditpfAmount = $this->common->getData('pf_user', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'payment_type' => '2'), array("field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest', "single"));

		$totalDebitpfAmount = $this->common->getData('pf_user', array('group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id'], 'payment_type' => '1'), array("field" => 'sum(pf_amount) as pf_total_amount, sum(pf_interest_amount) as pf_interest', "single"));

		$creditPfAmount = (float)($totalCreditpfAmount['pf_total_amount'] ?? 0);
		$creditPfInterest = (float)($totalCreditpfAmount['pf_interest'] ?? 0);

		$debitPfAmount = (float)($totalDebitpfAmount['pf_total_amount'] ?? 0);
		$debitPfInterest = (float)($totalDebitpfAmount['pf_interest'] ?? 0);

		// PF Amount (excluding interest)
		$avgAmountPf = $creditPfAmount - $debitPfAmount;

		// PF Interest (kept separate if needed)
		$pfInterest = $creditPfInterest - $debitPfInterest;



		$this->response(true, "Profile Fetch Successfully.", array(
			'Saving' => (string)$usercycle,
			'SavingJNR' => (string)$usercyclejnr,
			'Help2buy' => (string)$usercyclehelp2buy,
			'payoutCycle' => number_format($payoutCycle, 2),
			'payoutCyclejnr' => (string)$payoutCyclejnr,
			'SafeKeeping' => (string)$avgAmountSafeKeeping,
			'Loans' => (string)$avgAmountLoan,
			'Dividend' => $totalAmountDivided,
			'Investment' => $totalAmountInvestment,
			'Miscellaneous' => (string)$avgMiscellaneous,
			'Emergencyloan' => (string)'-' . $avgAmountEmergencyLoan,
			'avgwelfare' => (string)$avgwelfareAmount,
			'avgAmountPf' => (string)$avgAmountPf,
			'pfInterest' => (string)$pfInterest
		));
	}

	//created by @krishn on 03-06-25
	public function savingJnrTotal()
	{
		$where = "UG.group_id != 34 AND UG.group_id != 0 AND U.status != 2";
		$users = $this->user_model->user_group_detail($where, array());

		$totalJnr = 0;

		foreach ($users as $user) {
			$_REQUEST['user_id'] =  $user['user_id'];
			$_REQUEST['group_id'] = $user['group_id'];

			$lifecycleRecords = $this->common->getData('group_lifecycle', [
				"group_id" => $_REQUEST['group_id'],
				"group_type_id" => '2'
			]);

			if (!empty($lifecycleRecords)) {
				foreach ($lifecycleRecords as $lifecycle) {
					$totalJnr += $this->savingAvgCal($lifecycle['id']);
				}
			}
		}

		return $totalJnr;
	}

	//created by @krishn on 03-06-25
	public function savingTotalnew()
	{
		$this->db->select("
			U.user_id,
			SUM(UGL.amount) AS total_amount,
			SUM(CASE WHEN UGL.status = 2 THEN UGL.amount ELSE 0 END) AS paid_amount
		");
		$this->db->from('user_group UGP');
		$this->db->join('user U', 'U.user_id = UGP.user_id');
		$this->db->join('group_lifecycle GL', 'GL.group_id = UGP.group_id AND GL.group_type_id = 1');
		$this->db->join('user_group_lifecycle UGL', 'UGL.groupLifecycle_id = GL.id AND UGL.user_id = U.user_id');
		$this->db->where('UGP.group_id !=', 0);
		$this->db->where('U.status !=', 2);
		$this->db->group_by('U.user_id');

		$result = $this->db->get()->result_array();

		$total_completed = 0;
		foreach ($result as $row) {
			$total_completed += $row['total_amount'] - $row['paid_amount'];
		}

		return $total_completed;
	}



	// created by @krishn on 03-06-25
	public function savingTotalnewPending()
	{
		$this->db->select("
			U.user_id,
			U.first_name,
			SUM(UGL.amount) AS total_amount,
			SUM(CASE WHEN UGL.status != 2 THEN UGL.amount ELSE 0 END) AS unpaid_amount
		");
		$this->db->from('user_group UGP');
		$this->db->join('user U', 'U.user_id = UGP.user_id');
		$this->db->join('group_lifecycle GL', 'GL.group_id = UGP.group_id AND GL.group_type_id = 1');
		$this->db->join('user_group_lifecycle UGL', 'UGL.groupLifecycle_id = GL.id AND UGL.user_id = U.user_id');
		$this->db->where('UGP.group_id !=', 0);
		$this->db->where('U.status !=', 2);
		$this->db->group_by('U.user_id');

		$result = $this->db->get()->result_array();

		$total_pending = 0;
		foreach ($result as $row) {
			$pending = $row['total_amount'] - $row['unpaid_amount'];
			$total_pending += $pending;
		}

		return $total_pending;
	}


	public function safekeepingTotalAmount()
	{
		$where = "UG.group_id != 0 and U.status != 2";
		$users = $this->user_model->user_group_detail($where, array(''));
		$usercyclejnr = 0;
		foreach ($users as $key => $value) {
			$usercyclejnr += safekeepingTotal($value['group_id'], $value['user_id']);
		}
		return  $usercyclejnr;
	}

	public function savingTotal_all()
	{
		$where = "UG.group_id != 0";
		$users = $this->user_model->user_group_detail($where, array(''));
		$usercyclejnr = 0;
		foreach ($users as $key => $value) {
			$_REQUEST['user_id'] =  $value['user_id'];
			$_REQUEST['group_id'] =  $value['group_id'];
			$result3 = $this->common->getData('group_lifecycle', array("group_id" => $value['group_id'], "group_type_id" => '1'));
			if (!empty($result3)) {
				foreach ($result3 as $valuejnr) {
					$usercyclejnr += $this->savingAvgCal($valuejnr['id']);
				}
			}
		}
		return  $usercyclejnr;
	}

	public function HelptobuyCarTotal()
	{
		$where = "UG.group_id != 0";
		$users = $this->user_model->user_group_detail($where, array(''));
		$usercyclejnr = 0;
		foreach ($users as $key => $value) {
			$_REQUEST['user_id'] =  $value['user_id'];
			$_REQUEST['group_id'] =  $value['group_id'];
			$result3 = $this->common->getData('group_lifecycle', array("group_id" => $value['group_id'], "group_type_id" => '3'));
			if (!empty($result3)) {
				foreach ($result3 as $valuejnr) {

					$usercyclejnr += $this->savingAvgCal($valuejnr['id']);
				}
			}
		}
		return  $usercyclejnr;
	}

	public function sendgrentorMail()
	{

		$user_id = $_REQUEST['user_id'];
		$gurarantor_id =  $_REQUEST['gurarantor_id'];
		$amount = $_REQUEST['amount'];

		$user = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));
		if (!empty($gurarantor_id)) {
			$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['gurarantor_id']), array('single'));
			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
			$username = $user['first_name'] . " " . $user['last_name'];
			$data['useremail'] = "";
			$data['message'] = 'This mail is to remaind you that the ' . $username . ' has missed Payment of Amount ' . $amount;

			$messaged = $this->load->view('template/common-mail', $data, true);
			$mail = $this->sendMail($userDetailFrom['email'], 'Remainder', $messaged);
			$this->response(true, "Mail Sent Successfully!");
		} else {

			$this->response(false, "No gaurantor found for this user!");
		}
	}


	public function request_welfare()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		$amount = $_REQUEST['loan_amount'];
		$interRate = 10;

		$interest_payable = (($amount * $interRate) / 100);
		$total_payment = $amount + $interest_payable;
		$loan_emi = $total_payment / $_REQUEST['tenure'];


		$_REQUEST['interest_payable'] = $_REQUEST['provident'];
		$_REQUEST['interest_rate'] = $interRate;
		$_REQUEST['loan_type'] = '7';

		$post = $this->common->getField('user_loan', $_REQUEST);
		$result = $this->common->insertData('user_loan', $post);
		$loan_id = $this->db->insert_id();
		if ($result) {

			$array = array(
				'loan_id' => $loan_id,
				'user_id' => $_REQUEST['user_id'],
				'group_id' => $_REQUEST['group_id'],
				'amount' => $_REQUEST['loan_amount'],
				'payment_method' => '1',
				'status' => 0,
				'created_at' => $_REQUEST['created_at']
			);


			$post1 = $this->common->getField('user_loan_payment', $array);
			$result1 = $this->common->insertData('user_loan_payment', $post1);


			// 			$message = "request welfare";
			// 			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['group_id'], $message, $loan_id, "4");


			$this->common->query_normal("UPDATE credit_score_user SET each_loan_application = each_loan_application-100 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");

			$this->updateCreditScore(100, 'minus', $_REQUEST['user_id']);


			$this->response(true, "Request for Welfare successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	// created by @krishn on 18-07-25
	public function welfareList()
	{
		$groupCycleList = $this->common->getData(
			'user_group_lifecycle',
			array(
				'group_id' => $_REQUEST['group_id'],
				'groupLifecycle_id' => $_REQUEST['groupLifecycle_id'],
				'user_id' => $_REQUEST['user_id'],
				'is_completed' => 0
			)
		);

		if (!empty($groupCycleList)) {

			// Find all records where welfare_uuid is NULL or empty
			$emptyUuidItems = array_filter($groupCycleList, function ($row) {
				return empty($row['welfare_uuid']);
			});

			if (!empty($emptyUuidItems)) {
				// Sort items by ID (to keep consistent order)
				usort($emptyUuidItems, function ($a, $b) {
					return $a['id'] <=> $b['id'];
				});

				$count = 0;
				$uuid = null;

				foreach ($emptyUuidItems as $item) {
					if ($count % 40 === 0 || !$uuid) {
						// Generate a new UUID for each batch of 40
						$uuid = strtoupper(substr(bin2hex(random_bytes(8)), 0, 10));
					}

					// Update each row with the batch UUID
					$this->common->updateData(
						'user_group_lifecycle',
						array('welfare_uuid' => $uuid),
						array('id' => $item['id'])
					);

					$count++;
				}

				// Fetch the updated list (with UUIDs)
				$groupCycleList = $this->common->getData(
					'user_group_lifecycle',
					array(
						'group_id' => $_REQUEST['group_id'],
						'groupLifecycle_id' => $_REQUEST['groupLifecycle_id'],
						'user_id' => $_REQUEST['user_id'],
						'is_completed' => 0
					)
				);
			}

			$this->response(true, 'group fetch successfully', array('lists' => $groupCycleList));
		} else {
			$this->response(true, 'group not found', array('lists' => array()));
		}
	}



	public function welfareStatusHistoryDetail()
	{
		$result = $this->common->getData('user_cycle_status_history', array('lifecycle_id' => $_REQUEST['lifecycle_id']));
		if (!empty($result)) {
			$this->response(true, 'cycle fetch successfully', array('loanDetail' => $result));
		} else {
			$this->response(true, 'cycle not found', array('loanDetail' => array()));
		}
	}


	public function editwelfare()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);
		$_REQUEST['loan_type'] = '7';
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


		$this->common->insertData('user_loan_status_history', array("loan_id" => $id, "user_id" => $_REQUEST['user_id'], "note_title" => $_REQUEST['note_title'], "note_description" => $_REQUEST['note_description'], "status" => $_REQUEST['status'], "created_at" => date('Y-m-d H:i:s')));

		if ($_REQUEST['status'] === '4') {
			$message = "welfare approved";
			$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "5", "2");

			$message2 = "welfare accepted by super admin";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "11");
		}

		if ($_REQUEST['status'] === '3') {
			$message = "welfare declined";
			$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "6", "2");

			$message2 = "welfare has been cancel by super admin";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "12");
		}

		if ($_REQUEST['status'] === '6') {
			$message = "welfare has been cancel by sub admin";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "10");
		}

		if ($_REQUEST['status'] === '5') {
			$message = "welfare application in process.";
			$this->send_nofification_admin($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "4", "1");

			$message2 = "welfare awaiting approval";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "9");
		}


		if ($_REQUEST['status'] === '2') {
			$message2 = "welfare approved";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message2, $id, "15");

			$this->common->query_normal("UPDATE credit_score_user SET loan_payment_fully_paid = loan_payment_fully_paid+80 WHERE `user_id` = '" . $_REQUEST['user_id'] . "'");
			$this->updateCreditScore(80, 'plus');
		}

		if ($result) {
			$this->response(true, "welfare Update Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function welfareDetailByid()
	{
		$loanDetail = $this->common->getData('user_loan', array('id' => $_REQUEST['id']), array('single'));
		if (!empty($loanDetail)) {
			$this->response(true, 'welfare fetch successfully', array('loanDetail' => $loanDetail));
		} else {
			$this->response(true, 'welfare not found', array('loanDetail' => array()));
		}
	}

	// created by @krishn on 18-07-25
	public function editwelfareCycle()
	{
		$id = $_REQUEST['id'] ?? null;
		if (!$id) {
			$this->response(false, "Missing lifecycle ID");
			return;
		}

		unset($_REQUEST['id']); // Remove id so it doesn't overwrite the record

		// Handle payments
		if ($_REQUEST['payment_method'] == '3') {
			$this->paymentBySafekeeping($id, $_REQUEST['amount'], '2', "0");
		}
		if ($_REQUEST['payment_method'] == '2') {
			$this->paymentByPF($id, $_REQUEST['amount'], '2');
		}

		// Get last cycle data
		$getcycle = $this->common->getData(
			'user_group_lifecycle',
			[
				"group_id" => $_REQUEST['group_id'],
				'status!=' => '1',
				'loan_amount_status' => 1,
				"user_id" => $_REQUEST['user_id']
			],
			['sort_by' => 'id', 'sort_direction' => 'desc', 'limit' => 1]
		);

		$_REQUEST['loan_amount_status'] = 1;
		if (!empty($_REQUEST['created_at'])) {
			$_REQUEST['date'] = $_REQUEST['created_at'];
		}

		// Ensure welfare_uuid for grouping (40 per batch)
		if (empty($_REQUEST['welfare_uuid'])) {
			$lastEntry = $this->common->getData(
				'user_group_lifecycle',
				['user_id' => $_REQUEST['user_id'], 'group_id' => $_REQUEST['group_id']],
				['sort_by' => 'id', 'sort_direction' => 'desc', 'limit' => 1]
			);

			$batchUuid = !empty($lastEntry) ? $lastEntry[0]['welfare_uuid'] : '';

			if ($batchUuid) {
				$batchCount = $this->common->getData(
					'user_group_lifecycle',
					['welfare_uuid' => $batchUuid],
					['count' => true] // Should return only count
				);

				if ($batchCount < 40) {
					$_REQUEST['welfare_uuid'] = $batchUuid;
				} else {
					$_REQUEST['welfare_uuid'] = strtoupper(substr(bin2hex(random_bytes(8)), 0, 10));
				}
			} else {
				$_REQUEST['welfare_uuid'] = strtoupper(substr(bin2hex(random_bytes(8)), 0, 10));
			}
		}

		// Prepare data for update
		$post = $this->common->getField('user_group_lifecycle', $_REQUEST);

		if (!empty($post)) {
			if (!empty($getcycle) && $getcycle[0]['total_payment'] > 0) {
				$amount = $getcycle[0]['total_payment'] - $_REQUEST['amount'];
				$this->common->updateData('user_group_lifecycle', ['total_payment' => $amount], ['id' => $id]);
			}

			$post['total_payment'] = $post['amount'] * $post['month'];

			// Mark as completed if requested
			if (!empty($_REQUEST['is_completed']) && $_REQUEST['is_completed'] == '1') {

				$welfareUuid = $_REQUEST['welfare_uuid'] ?? '';

				if (empty($welfareUuid)) {
					// Get last entry to determine batch UUID if not provided
					$lastEntry = $this->common->getData(
						'user_group_lifecycle',
						['user_id' => $_REQUEST['user_id'], 'group_id' => $_REQUEST['group_id']],
						['sort_by' => 'id', 'sort_direction' => 'desc', 'limit' => 1]
					);
					if (!empty($lastEntry)) {
						$welfareUuid = $lastEntry[0]['welfare_uuid'];
					}
				}

				if (!empty($welfareUuid)) {
					// Fetch all 40 entries for this cycle batch
					$cycleEntries = $this->common->getData(
						'user_group_lifecycle',
						['welfare_uuid' => $welfareUuid, 'group_id' => $_REQUEST['group_id'], 'user_id' => $_REQUEST['user_id']],
						['sort_by' => 'id', 'sort_direction' => 'asc']
					);

					foreach ($cycleEntries as $entry) {
						$updateData = [
							'is_completed' => 1
						];

						// Update fields conditionally
						if ($entry['status'] == 1) {
							$updateData['status'] = 2;
						}
						if ($entry['payment_method'] == 0) {
							$updateData['payment_method'] = 1;
						}
						if ($entry['loan_amount_status'] == 0) {
							$updateData['loan_amount_status'] = 1;
						}

						// Update total_payment if needed
						if ($entry['total_payment'] == 0) {
							$updateData['total_payment'] = $entry['month'] * $entry['amount'];
						}

						// Update this row
						$this->common->updateData('user_group_lifecycle', $updateData, ['id' => $entry['id']]);
					}
				}
			}


			$result = $this->common->updateData('user_group_lifecycle', $post, ['id' => $id]);
		} else {
			$result = false;
		}

		if (!$result) {
			$this->response(false, "There is a problem, please try again.");
			return;
		}

		// Insert status history
		$this->common->insertData('user_cycle_status_history', [
			"lifecycle_id" => $id,
			"user_id" => $_REQUEST['user_id'],
			"note_title" => $_REQUEST['note_title'],
			"note_description" => $_REQUEST['note_description'],
			"status" => $_REQUEST['status'],
			"created_at" => date('Y-m-d H:i:s')
		]);

		// Log loan payment
		$wherePendingwel = "user_id = '{$_REQUEST['user_id']}' AND group_id = '{$_REQUEST['group_id']}' AND groupLifecycle_id = '147' GROUP BY user_id,grand_total_amount,group_id,groupLifecycle_id,id";
		$resultwelTotal = $this->common->getData('user_group_lifecycle', $wherePendingwel, [
			"field" => 'user_id,group_id,groupLifecycle_id,grand_total_amount,id'
		]);

		if (!empty($resultwelTotal)) {
			$this->common->insertData('user_loan_payment', [
				"loan_id" => $resultwelTotal[0]['id'],
				"group_id" => $_REQUEST['group_id'],
				"amount" => $_REQUEST['amount'],
				"payment_method" => $_REQUEST['payment_method'],
				"user_id" => $_REQUEST['user_id'],
				"note_title" => $_REQUEST['note_title'],
				"note_description" => $_REQUEST['note_description'],
				"status" => $_REQUEST['status'],
				"created_at" => date('Y-m-d H:i:s')
			]);
		}

		// Send notification
		$message = "Your cycle info has been updated";
		$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $id, "1");

		// Credit score adjustments
		if ($_REQUEST['status'] === '3') { // Missed payment
			$this->common->query_normal("UPDATE credit_score_user SET three_or_more_missed_savings_deadline = three_or_more_missed_savings_deadline+1 WHERE user_id = '{$_REQUEST['user_id']}'");
			$creditScoreInfo = $this->common->getData('credit_score_user', ['user_id' => $_REQUEST['user_id']], ['single']);
			if (!empty($creditScoreInfo)) {
				if ($creditScoreInfo['three_or_more_missed_savings_deadline'] > 2) {
					$this->common->query_normal("UPDATE credit_score_user SET missed_savings_deadline = missed_savings_deadline-300 WHERE user_id = '{$_REQUEST['user_id']}'");
					$this->updateCreditScore(300, 'minus');
				} else {
					$this->common->query_normal("UPDATE credit_score_user SET missed_savings_deadline = missed_savings_deadline-120 WHERE user_id = '{$_REQUEST['user_id']}'");
					$this->updateCreditScore(120, 'minus');
				}
			}
		}

		if ($_REQUEST['status'] === '2') { // On-time payment
			$data['status'] = "Paid on Time";
			$this->common->query_normal("UPDATE credit_score_user SET saving_paid_on_time = saving_paid_on_time+20 WHERE user_id = '{$_REQUEST['user_id']}'");
			$this->updateCreditScore(20, 'plus');

			$userDetailFrom = $this->common->getData('user', ['user_id' => $_REQUEST['user_id']], ['single']);
			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
			$data['useremail'] = "";
			$data['message'] = '<p>This is a confirmation that your WELFARE payment for this month has been received and recorded. Check your app for confirmation.</p>
			<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-top: 10px;">
				<tr><td><strong>Amount paid</strong></td><td>£' . $_REQUEST["amount"] . '</td></tr>
				<tr><td><strong>Payment date</strong></td><td>' . date("d M Y", strtotime($_REQUEST['created_at'])) . '</td></tr>
				<tr><td><strong>Payment status</strong></td><td>' . $data['status'] . '</td></tr>
			</table>';
			$messaged = $this->load->view('template/common-mail', $data, true);
			$this->sendMail($userDetailFrom['email'], 'Welfare', $messaged);
		}

		if ($_REQUEST['status'] === '4') { // Missed deadline
			$data['status'] = "Missed Payment Deadline";
			$usersuper = $this->common->getData('superAdmin', ['admin_type' => '2', 'status!=' => '2'], ['single']);
			$data1['sendername'] = $usersuper['name'];
			$data1['message'] = '<p>This is a confirmation that we have received and recorded Welfare for this month. Refer to your app for confirmation.</p>
			<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-top: 10px;">
				<tr><td><strong>Amount paid</strong></td><td>£' . $_REQUEST["amount"] . '</td></tr>
				<tr><td><strong>Payment date</strong></td><td>' . date("d M Y", strtotime($_REQUEST['created_at'])) . '</td></tr>
				<tr><td><strong>Payment status</strong></td><td>' . $data['status'] . '</td></tr>
			</table>';
			$messaged1 = $this->load->view('template/common-mail', $data1, true);
			$this->sendMail($usersuper['email'], 'Welfare', $messaged1);

			$this->common->query_normal("UPDATE credit_score_user SET late_savings_payment = late_savings_payment-60 WHERE user_id = '{$_REQUEST['user_id']}'");
			$this->updateCreditScore(60, 'minus');
		}

		// Log notification
		if (!empty($_REQUEST['status'])) {
			$status = $_REQUEST['status'];
			$this->common->query_normal("
            INSERT INTO payment_notification (status, group_type_id, group_id, month, created_at, groupLifecycle_id, user_id, amount)
            VALUES ('{$status}', '4', '{$_REQUEST['group_id']}', '{$_REQUEST['month']}', NOW(), '147', '{$_REQUEST['user_id']}', '{$_REQUEST['amount']}')
        ");
		}

		$this->response(true, "User Welfare Cycle Update Successfully");
	}

	// created by @krishn on 27-05-25
	public function addWelfareCycle()
	{
		$user_id  = intval($_REQUEST['user_id'] ?? 0);
		$group_id = intval($_REQUEST['group_id'] ?? 0);
		$groupLifecycleId = intval($_REQUEST['groupLifecycle_id'] ?? 0);
		$amount   = floatval($_REQUEST['amount'] ?? 0);
		$startDate = $_REQUEST['date'] ?? date('Y-m-d');

		if (!$user_id || !$group_id || !$groupLifecycleId || !$amount) {
			$this->response(false, "Missing required parameters.");
			return;
		}

		// Step 1: Check if group lifecycle exists and is of type welfare (4)
		$groupDetail = $this->common->getData('group_lifecycle', [
			'group_id'       => $group_id,
			'id'             => $groupLifecycleId,
			'group_type_id'  => 4
		]);

		if (empty($groupDetail)) {
			$this->response(false, "Group lifecycle not found.");
			return;
		}

		// Step 2: Check if user already has an incomplete welfare cycle
		$checkExistingWelfareCycle = $this->common->getData('user_group_lifecycle', [
			'group_id'       => $group_id,
			'groupLifecycle_id' => $groupLifecycleId,
			'user_id'        => $user_id,
			'is_completed'   => '0'
		]);

		if (!empty($checkExistingWelfareCycle)) {
			$this->response(false, "You must complete the current welfare cycle before starting a new one.");
			return;
		}

		// Step 3: Proceed to insert welfare cycle months
		$welfareUuid = strtoupper(substr(bin2hex(random_bytes(8)), 0, 10));
		$createdAt = date('Y-m-d H:i:s');
		$loanEmi = floatval($_REQUEST['loan_emi'] ?? 0);
		$adminRisk = floatval($_REQUEST['admin_risk'] ?? 0);
		$provident = floatval($_REQUEST['provident'] ?? 0);
		$grandTotal = floatval($_REQUEST['total_payment'] ?? 0);

		foreach ($groupDetail as $value) {
			$monthCount = intval($value['month_count'] ?? 0);
			$cycleDate = $startDate;

			for ($x = 1; $x <= $monthCount; $x++) {
				$cycleArr = [
					"group_id"           => $group_id,
					"welfare_uuid"       => $welfareUuid,
					"groupLifecycle_id"  => $groupLifecycleId,
					"user_id"            => $user_id,
					"amount"             => $amount,
					"month"              => $x,
					"created_at"         => $createdAt,
					"date"               => $cycleDate,
					"loan_emi"           => $loanEmi,
					"admin_risk"         => $adminRisk,
					"provident"          => $provident,
					"total_payment"      => 0,
					"grand_total_amount" => $grandTotal
				];

				$post = $this->common->getField('user_group_lifecycle', $cycleArr);
				$this->common->insertData('user_group_lifecycle', $post);

				// Next month
				$cycleDate = date("Y-m-d", strtotime("+1 month", strtotime($cycleDate)));
			}
		}

		$this->response(true, "Welfare cycle created successfully.");
	}

	public function addPayoutwelfareCycle()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');

		$payOutExist = $this->common->getData('payout_cycle', array('user_id' => $_POST['user_id'], 'group_id' => $_POST['group_id'], 'group_cycle_id' => $_POST['group_cycle_id']), array('single'));

		if (!empty($payOutExist)) {
			$this->response(false, "Payout already done");
			die();
		}


		$groupPercentResult = $this->user_model->getLifeCyclePercent(array('GL.id' => $_POST['group_cycle_id']), array('single'));

		$pfPercent = 0.025;
		// if(!empty($groupPercentResult)) {
		// 	$pfPercent = (int)$groupPercentResult['percent'];
		// }

		$result = $this->common->getData('user_group_lifecycle', array('groupLifecycle_id' => $_REQUEST['group_cycle_id'], 'user_id' => $_REQUEST['user_id']), array('field' => 'SUM(amount) as total_amount', 'single'));

		$payout_amount_total = $result['total_amount'];
		if (!empty($pfPercent)) {
			$payout_pf_amount = ($result['total_amount'] * $pfPercent) / 100;
			$_REQUEST['payout_amount'] = $payout_amount_total - $payout_pf_amount;
			$_REQUEST['pf_interest_amount'] = ($payout_pf_amount * $pfPercent) / 100;
		} else {
			$payout_pf_amount = 0;
			$_REQUEST['payout_amount'] = $payout_amount_total;
			$_REQUEST['pf_interest_amount'] = 0;
		}

		$_REQUEST['payout_pf_percent'] = $pfPercent . "%";
		$_REQUEST['pf_interest_percent'] = $pfPercent . "%";
		$_REQUEST['payout_amount_total'] = $payout_amount_total;
		$_REQUEST['payout_pf_amount'] = $payout_pf_amount;


		$post = $this->common->getField('payout_cycle', $_REQUEST);
		$result = $this->common->insertData('payout_cycle', $post);
		$payout_id = $this->db->insert_id();

		if ($result) {
			$this->common->insertData('cycle_status_management', array("group_id" => $_REQUEST['group_id'], "group_cycle_id" => $_REQUEST['group_cycle_id'], "user_id" => $_REQUEST['user_id'], "type" => '1', 'created_at' => $_REQUEST['created_at']));

			$this->common->insertData('pf_user', array("group_id" => $_REQUEST['group_id'], "user_id" => $_REQUEST['user_id'], "pf_type" => '1', "payment_type" => '2', 'created_at' => $_REQUEST['created_at'], 'pf_amount' => $payout_pf_amount, 'pf_percent' => $_REQUEST['payout_pf_percent'], 'pf_interest_amount' => $_REQUEST['pf_interest_amount'], 'pf_interest_percent' => $_REQUEST['pf_interest_percent'], 'main_id' => $_REQUEST['group_cycle_id'], 'other_main_id' => $payout_id));

			$message = "your bulk funds is now paid";
			$this->send_nofification($_REQUEST['user_id'], $_REQUEST['admin_id'], $_REQUEST['group_id'], $message, $payout_id, "13");


			$userDetailFrom = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));

			$data['sendername'] = $userDetailFrom['first_name'] . " " . $userDetailFrom['last_name'];
			$data['useremail'] = "";
			// $data['message'] = '<p>This is a confirmation that your PAYOUT for this cycle has been processed and paid into your SAFEKEEPING account.</p><p>If you were not expecting this payment, please do let us know.</p>';
			$data['message'] = '<p>This confirms that your Welfare PAYOUT has been successfully processed and deposited into your account or securely placed in safekeeping where applicable.</p><p>If you have any questions or concerns about this payment, please do not hesitate to contact us.</p>';

			$messaged = $this->load->view('template/common-mail', $data, true);
			$mail = $this->sendMail($userDetailFrom['email'], 'Payout', $messaged);




			$this->response(true, "Payout Successfully");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}


	public function getCycleTotalAmountPayout()
	{
		$info = [];
		$result = $this->common->getData('user_group_lifecycle', array('groupLifecycle_id' => $_REQUEST['groupLifecycle_id'], 'user_id' => $_REQUEST['user_id'], 'is_completed' => 0), array('field' => 'SUM(amount) as total_amount', 'single'));

		if (!empty($result)) {
			$groupPercentResult = $this->user_model->getLifeCyclePercent(array('GL.id' => $_POST['groupLifecycle_id']), array('single'));
			$amount_total = $result['total_amount'];

			$pfPercent = 0.025;
			// if(!empty($groupPercentResult)) {
			// 	$pfPercent = (int)$groupPercentResult['percent'];
			// }

			if (!empty($pfPercent)) {
				$pf_amount = ($result['total_amount'] * $pfPercent) / 100;
				$totalArr['amount'] = $amount_total - $pf_amount;
				$totalArr['pf_interest_amount'] = 0; //($pf_amount*$pfPercent)/100;
			} else {
				$pf_amount = 0;
				$totalArr['amount'] = $amount_total;
				$totalArr['pf_interest_amount'] = 0;
			}
			$totalArr['amount'] = $amount_total;
			$totalArr['pf_percent'] = "0%";
			$totalArr['amount_total'] = $amount_total;
			$totalArr['pf_amount'] = 0;
			$totalArr['pf_interest_percent'] =  $pfPercent . "%";

			$this->response(true, 'group fetch successfully', array('info' => $totalArr));
		} else {
			$this->response(true, 'group not found', array('info' => array()));
		}
	}



	public  function helptobuyTotal()
	{

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


		$totalAvgAmount = $avgAmount + $avgAmount1 + $avgAmount2 + $avgAmount3  + $avgAmount4;
		if ($totalAvgAmount != 0) {

			$totalAvgAmount = '-' . $totalAvgAmount;
		}

		return $totalAvgAmount;
	}
	/////////////////////////////////////////////Circle Creation//////////////////////////////////////////////////////
	public function addgroupCircle()
	{
		if (!empty($_REQUEST['circle_name'])) {
			$_REQUEST['created_at'] = date('Y-m-d H:i:s');
			$post = $this->common->getField('group_circle', $_REQUEST);
			$result = $this->common->insertData('group_circle', $post);
			$id = $this->db->insert_id();
			if ($result) {
				$this->response(true, "Circle added successfully!");
			} else {
				$this->response(false, "There is a problem, please try again.");
			}
		} else {
			$this->response(false, "Please enter your circle name");
		}
	}

	// created by @krishn on 23-06-26
	public function getCircleBygroupid()
	{
		if (empty($_POST['group_id'])) {
			$this->response(false, "Please enter your group id");
			die();
		}

		$circleIds = explode(",", $_POST['circle_ids']);

		if (!empty($_POST['circle_ids'])) {

			$groupcircle = $this->common->getData(
				'group_circle',
				array('group_id' => $_POST['group_id']),
				array(
					'colname'  => 'id',
					'where_in' => $circleIds
				)
			);
		} else {

			$groupcircle = $this->common->getData(
				'group_circle',
				array('group_id' => $_POST['group_id']),
				array()
			);
		}

		$array = [];

		if ($groupcircle) {

			foreach ($groupcircle as $value) {

				$i = 0;
				$trustscore = 0;

				$usercircle = $this->common->getData(
					'user_circle',
					array(
						'circle_id' => $value['id'],
						'circle_lead' => '1'
					),
					array('single')
				);

				$usercircle_dep = $this->common->getData(
					'user_circle',
					array(
						'circle_id' => $value['id'],
						'deputycirclelead' => '1'
					),
					array('single')
				);

				// Total members in the circle excluding recommended users and inactive users
				$whereForMember = "UG.circle_id = '" . $value['id']	 . "'" . " AND UG.group_id = '" . $_POST['group_id'] . "'" . " AND U.status != '2'" . "AND U.recommended = '0'";
				$usercircledata = $this->user_model->user_circle_detail($whereForMember, array());

				$value['total_members'] = !empty($usercircledata) ? count($usercircledata) : 0;

				if (!empty($usercircledata)) {

					foreach ($usercircledata as $value1) {

						$i++;

						$userinfo = get_user_details($value1['user_id']);

						if ($userinfo) {
							$trustscore += $userinfo['total_credit_score'];
						}
					}
				}

				if ($i != 0) {
					$value['trust_score'] = $trustscore / $i;
				} else {
					$value['trust_score'] = 0;
				}


				if ($usercircle_dep) {

					$userdeputy = $this->common->getData(
						'user',
						array('user_id' => $usercircle_dep['user_id']),
						array('single')
					);

					$deputyname = $userdeputy
						? $userdeputy['first_name'] . " " . $userdeputy['last_name']
						: '';
				} else {

					$deputyname = '';
				}


				if ($usercircle) {

					$user = $this->common->getData(
						'user',
						array('user_id' => $usercircle['user_id']),
						array('single')
					);

					$name = $user
						? $user['first_name'] . " " . $user['last_name']
						: '';
				} else {

					$name = '';
				}


				$value['deputy_lead_name'] = $deputyname;
				$value['circle_lead_name'] = $name;

				$array[] = $value;
			}

			$this->response(true, "Succesfully fetched circle!", array(
				"data" => $array
			));
		} else {

			$this->response(false, "Circle not found. Please check your details.");
		}
	}

	public function updateCircle()
	{
		$id = $_REQUEST['id'];
		unset($_REQUEST['id']);
		$post = $this->common->getField('group_circle', $_REQUEST);
		if (!empty($post)) {
			$result = $this->common->updateData('group_circle', $post, array('id' => $id));
			if ($result) {
				$this->response(true, "Circle Updated Successfully");
			} else {
				$this->response(false, "There is a problem, please try again.");
			}
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	public function deleteCircle()
	{
		if (empty($_REQUEST['id'])) {
			$this->response(false, "Please enter your id");
			die();
		}

		$this->common->deleteData('user_circle', array('circle_id' => $_REQUEST['id']));

		$this->common->deleteData('group_circle', array('id' => $_REQUEST['id']));
		$this->response(true, 'Circle Deleted Successfully');
	}


	// recommendUser_status
	public function adduserGroupCircle()
	{
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		if (empty($_REQUEST['users'])) {
			$this->response(false, "Please enter users");
			die();
		}

		$userArr = explode(",", $_REQUEST['users']);
		foreach ($userArr as $key => $userId) {
			$groupCircleMemberCount = $this->common->getData('user_circle', array("group_id" => $_REQUEST['group_id'], "circle_id" => $_REQUEST['circle_id']), array('count'));

			if ($groupCircleMemberCount > 25) {
				$this->common->updateData('user', array('exist_in_waiting' => 1), array('user_id' => $userId));
				$this->response(true, "Approved by admin but cicle limit exceeded. You can add only 25 members to this circle.");
				die();
			}

			$groupcircle = $this->common->getData('user_circle', array("user_id" => $userId), array('single'));
			$groupuser = $this->common->getData('user', array("user_id" => $userId), array('single'));
			if (!empty($groupcircle)) {
				$this->response(false, "This user " . $groupuser['first_name'] . " is already in another circle");
				die();
			} else {

				$this->common->updateData('user', array('exist_in_waiting' => 0), array('user_id' => $userId));

				$newArr = array(
					"group_id" => $_REQUEST['group_id'],
					"circle_id" => $_REQUEST['circle_id'],
					"user_id" => $userId,
					"created_at" => $_REQUEST['created_at']
				);
				$post = $this->common->getField('user_circle', $newArr);
				$result = $this->common->insertData('user_circle', $post);
			}
			// send mail to admin
			if ($_REQUEST['isWaiting'] === '1') {
				$subject = "Joining Instructions";
				$data['sendername'] = $groupuser['first_name'];
				$data['message'] = '<p style="margin-bottom:10px;">I am writing to provide you with the necessary account details for the upcoming Interfriends cycle.</p>
                <h4><strong>UNITED KINGDOM USERS</strong></h4>
                <p><strong>Account Name:</strong> Interfriends</p>
                <p><strong>Bank Name:</strong> Lloyds Bank</p>
                <p><strong>Account Number:</strong> 32774168</p>
                <p><strong>Sort Code:</strong> 30-98-97</p>
                <p><strong>Reference:</strong> Your unique ID followed by SVS (we will send your unique ID separately)</p>
                <p>Please note that there are two savings cycles, one starting in January and the other in July. Payments must be made between the 1st and the last day of each month by 4:00 pm.</p>
                <p>Any payment made after the deadline may negatively impact your Interfriends Trust Score.</p>
                <p>To access your Interfriends dashboard, please follow this link and enter the email used for your application: <a href="' . USER_BASE_URL . '">Click Here</a> for Login</p>
                <p>If you have forgotten your password, you can click on \'forgotten password\' to create a new one.</p>
                <p>Thank you for your attention to this matter.</p>';

				$messaged = $this->load->view('template/common-mail', $data, true);
				$mail = $this->sendMail($groupuser['email'], $subject, $messaged);
			}
		}
		if ($result) {
			$this->response(true, "User added successfully in circle");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	public function assignLeadcircle()
	{
		$checklead = $this->common->getData('user_circle', array("circle_id" => $_REQUEST['circle_id'], "group_id" => $_REQUEST['group_id'], "circle_lead" => '1'), array('single'));
		if (!empty($checklead)) {
			$result1 = $this->common->query_normal("UPDATE user_circle SET circle_lead='0' WHERE `id` = '" . $checklead['id'] . "'");
			// $this->response(false,"Admin had already assigned lead in this circle");
			// die();
		}
		$groupuser = $this->common->getData('user_circle', array(
			"user_id" => $_REQUEST['user_id'],
			"circle_id" => $_REQUEST['circle_id'],
			"group_id" => $_REQUEST['group_id']
		), array('single'));
		if (!empty($groupuser)) {
			$id = $groupuser['id'];
			$_REQUEST['circle_lead'] = "1";
			$result = $this->common->query_normal("UPDATE user_circle SET circle_lead='" . $_REQUEST['circle_lead'] . "' WHERE `id` = '" . $id . "'");
			if ($result) {
				$this->response(true, "Circle Assign Successfully");
			} else {
				$this->response(false, "There is a problem, please try again.");
			}
		} else {
			$this->response(false, "Users not found. Please check your details.");
		}
	}

	// created by @krishn on 23-06-25
	public function removeLeadCircle()
	{
		// Check if a lead exists for the given user, circle, and group
		$leadUser = $this->common->getData('user_circle', array(
			// "user_id"    => $_REQUEST['user_id'],
			"circle_id"  => $_REQUEST['circle_id'],
			"group_id"   => $_REQUEST['group_id'],
			"circle_lead" => '1'
		), array('single'));

		if (!empty($leadUser)) {
			$id = $leadUser['id'];

			// Remove lead role by setting circle_lead to 0
			$result = $this->common->query_normal("UPDATE user_circle SET circle_lead = '0' WHERE `id` = '" . $id . "'");

			if ($result) {
				$this->response(true, "Circle lead removed successfully.");
			} else {
				$this->response(false, "There was a problem removing the circle lead. Please try again.");
			}
		} else {
			$this->response(false, "No lead found for this user in the specified circle and group.");
		}
	}

	public function circleUser_list()
	{
		$limit = 10;
		$offset = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;

		// Validate required parameters
		if (empty($_REQUEST['group_id']) || empty($_REQUEST['circle_id'])) {
			$this->response(false, "Group ID and Circle ID are required.", []);
			return;
		}

		// Prepare WHERE clause
		$where = "UG.group_id = '" . $_REQUEST['group_id'] . "' 
              AND UG.circle_id = '" . $_REQUEST['circle_id'] . "' 
              AND U.status != '2' 
              AND U.recommended = 0";

		if (!empty($_REQUEST['search_keyword'])) {
			$keyword = $this->db->escape_like_str($_REQUEST['search_keyword']);
			$where .= " AND (U.first_name LIKE '%$keyword%' 
                      OR U.last_name LIKE '%$keyword%' 
                      OR U.email LIKE '%$keyword%')";
		}

		// Get paginated data
		$result = $this->user_model->user_circle_detail($where, [], $limit, $offset);

		// Get total count (without limit)
		$userCount = $this->user_model->user_circle_detail($where, ['count']);

		// Format results
		$countData = $offset + 1;
		foreach ($result as $key => $value) {
			$profileImage = !empty($value['profile_image']) ? base_url($value['profile_image']) : "assets/img/default-user-icon.jpg";

			$result[$key]['profile_image'] = $profileImage;
			$result[$key]['profile_image_thumb'] = $profileImage;
			$result[$key]['sno'] = $countData++;
		}

		$groupcircle = $this->common->getData('group_circle', ['id' => $_REQUEST['circle_id']], ['single']);
		$circleName = $groupcircle['circle_name'] ?? '';

		$this->response(true, "Users fetched successfully.", [
			"userList" => $result,
			"userCount" => $userCount,
			"circle_name" => $circleName,
			"limit" => $limit,
			"start" => $offset
		]);
	}

	public function moveCircle()
	{

		$data = "";
		$id = $_REQUEST['id'];
		$circle_id = $_REQUEST['circle_id'];
		$group_id = $_REQUEST['group_id'];

		$movegroup_id = $_REQUEST['movegroup_id'];
		$movecircle_id = $_REQUEST['movecircle_id'];


		$moveuser_id = $_REQUEST['moveuser_id'];

		if ($_REQUEST['id']) {
			$data =    $this->common->deleteData('user_circle', array('id' => $_REQUEST['id']));
		}

		if ($data) {
			$created_at = date('Y-m-d H:i:s');
			$result = $this->common->query_normal("INSERT INTO user_circle(circle_id,group_id,user_id,created_at) VALUES ('$movecircle_id','$movegroup_id','$moveuser_id','$created_at')");
			$id = $this->db->insert_id();
			if ($result) {
				$this->response(true, "Circle moved successfully!");
			} else {
				$this->response(false, "There is a problem, please try again.");
			}
		} else {
			$this->response(false, "Please enter your circle name");
		}
	}

	public function sendEmailtoAllmembersinCircle()
	{
		if (!empty($_REQUEST['subject']) && !empty($_REQUEST['message'])) {
			$userDetailFrom = $this->common->getData('user_circle', array('circle_id' => $_REQUEST['circle_id']), array(''));
			if (!empty($userDetailFrom)) {

				foreach ($userDetailFrom as $value) {
					$userDetailFrom1 = $this->common->getData('user', array('user_id' => $value['user_id']), array('single'));
					$data['sendername'] = $userDetailFrom1['first_name'] . " " . $userDetailFrom1['last_name'];
					$data['useremail'] = "";
					$data['message'] = '<p>' . $_REQUEST['message'] . '</p>';
					$messaged = $this->load->view('template/common-mail', $data, true);
					$mail = $this->sendMail($userDetailFrom1['email'], $_REQUEST['subject'], $messaged);
				}
				$this->response(true, "Mail sent successfully!");
			} else {
				$this->response(false, "User not found.Please add users and try again!");
			}
		} else {
			$this->response(false, "Please add message and subject");
		}
	}



	public function sendEmailtoAllCirclelead()
	{
		if (!empty($_REQUEST['subject']) && !empty($_REQUEST['message'])) {
			$userDetailFrom = $this->common->getData('user_circle', array("circle_lead" => '1'), array());
			if ($userDetailFrom) {

				foreach ($userDetailFrom as $value) {
					$userDetailFrom1 = $this->common->getData('user', array('user_id' => $value['user_id']), array('single'));
					$data['sendername'] = $userDetailFrom1['first_name'] . " " . $userDetailFrom1['last_name'];
					$data['useremail'] = "";
					$data['message'] = '<p>' . $_REQUEST['message'] . '</p>';
					$messaged = $this->load->view('template/common-mail', $data, true);
					$mail = $this->sendMail($userDetailFrom1['email'], $_REQUEST['subject'], $messaged);
				}
				$this->response(true, "Mail sent successfully!");
			} else {
				$this->response(false, "Circle lead not found.Please add Circle lead and try again!");
			}
		} else {
			$this->response(false, "Please add message and subject");
		}
	}

	public function userListNotinCircle()
	{

		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
		$array = [];
		$where = " U.status != '2' AND U.recommended = 0 AND UG.group_id = '" . $_REQUEST['group_id'] . "'";

		if (!empty($_REQUEST['search_keyword'])) {
			$where .= " AND (U.first_name LIKE '%" . $_REQUEST['search_keyword'] . "%' OR U.last_name LIKE '%" . $_REQUEST['search_keyword'] . "%' OR U.email LIKE '%" . $_REQUEST['search_keyword'] . "%') ";
		}

		$userDetailFrom = $this->common->getData('user_circle', array(), array());
		if (!empty($userDetailFrom)) {
			foreach ($userDetailFrom as $key => $value) {
				$array[] = $value['user_id'];
			}
			$impload = implode(',', $array);
			$where .= " AND U.user_id NOT IN (" . $impload . ") ";
		}

		$result = $this->user_model->user_group_detail($where, array(), $start, $end);

		$userCount = $this->user_model->user_group_detail($where, array('count'));

		$countData = $end;
		$countData++;
		if (!empty($result)) {

			foreach ($result as $key => $value) {
				if (!empty($value['profile_image'])) {
					$result[$key]['profile_image'] = base_url($value['profile_image']);
					$result[$key]['profile_image_thumb'] = base_url($value['profile_image']);
				} else {
					$result[$key]['profile_image'] = "assets/img/default-user-icon.jpg";
					$result[$key]['profile_image_thumb'] = "assets/img/default-user-icon.jpg";
				}

				$result[$key]['sno'] = $countData++;
			}
			$this->response(true, "User fetch Successfully.", array("userList" => $result, "userCount" => $userCount));
		} else {
			$this->response(true, "User fetch Successfully.", array("userList" => array(), "userCount" => $userCount));
		}
	}


	public function sendEmailtoUserinCircle()
	{

		if (!empty($_REQUEST['subject']) && !empty($_REQUEST['message'])) {
			$userDetailFrom1 = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));
			$data['sendername'] = $userDetailFrom1['first_name'] . " " . $userDetailFrom1['last_name'];
			$data['useremail'] = "";
			$data['message'] = '<p>' . $_REQUEST['message'] . '</p>';
			$messaged = $this->load->view('template/common-mail', $data, true);
			$mail = $this->sendMail($userDetailFrom1['email'], $_REQUEST['subject'], $messaged);

			if ($mail) {
				$this->response(true, "Mail sent successfully!");
			} else {
				$this->response(false, "There is a problem, please try again.");
			}
		} else {
			$this->response(false, "Please add message and subject");
		}
	}


	public function recommendUserlistcircle()
	{

		$user_id = $_REQUEST['user_id'];

		$userDetailFrom = $this->common->getData('user_circle', array('user_id' => $user_id), array('single'));

		$where = " U.status != '2' AND U.recommended = 0 AND UG.circle_id = '" . $userDetailFrom['circle_id'] . "'";

		$result = $this->user_model->user_circle_detail($where, array(), "", "");
		$userCount = $this->user_model->user_circle_detail($where, array('count'));

		$countData = 0;
		$countData++;
		if (!empty($result)) {
			foreach ($result as $key => $value) {
				if (!empty($value['profile_image'])) {
					$result[$key]['profile_image'] = base_url($value['profile_image']);
					$result[$key]['profile_image_thumb'] = base_url($value['profile_image']);
				} else {
					$result[$key]['profile_image'] = "assets/img/default-user-icon.jpg";
					$result[$key]['profile_image_thumb'] = "assets/img/default-user-icon.jpg";
				}

				$result[$key]['sno'] = $countData++;
			}
			$this->response(true, "User fetch Successfully.", array("userList" => $result, "userCount" => $userCount));
		} else {
			$this->response(true, "User fetch Successfully.", array("userList" => array(), "userCount" => $userCount));
		}
	}

	public function assigndeputyleadcircle()
	{
		$checklead = $this->common->getData('user_circle', array("circle_id" => $_REQUEST['circle_id'], "group_id" => $_REQUEST['group_id'], "deputycirclelead" => '1'), array('single'));
		if (!empty($checklead)) {
			$result1 = $this->common->query_normal("UPDATE user_circle SET deputycirclelead='0' WHERE `id` = '" . $checklead['id'] . "'");
			//$this->response(false,"Admin had already assigned deputy circle lead in this circle");

		}
		$groupuser = $this->common->getData('user_circle', array(
			"user_id" => $_REQUEST['user_id'],
			"circle_id" => $_REQUEST['circle_id'],
			"group_id" => $_REQUEST['group_id']
		), array('single'));
		if (!empty($groupuser)) {
			$id = $groupuser['id'];
			$_REQUEST['deputycirclelead'] = "1";
			$result = $this->common->query_normal("UPDATE user_circle SET deputycirclelead='" . $_REQUEST['deputycirclelead'] . "' WHERE `id` = '" . $id . "'");
			if ($result) {
				$this->response(true, "Circle assign deputy circle lead Successfully");
			} else {
				$this->response(false, "There is a problem, please try again.");
			}
		} else {
			$this->response(false, "Users not found. Please check your details.");
		}
	}

	// created by @krishn on 23-06-25
	public function removeDeputyLeadCircle()
	{
		// Check if this user is currently a deputy circle lead for the given circle and group
		$deputyUser = $this->common->getData('user_circle', array(
			// "user_id"         => $_REQUEST['user_id'],
			"circle_id"       => $_REQUEST['circle_id'],
			"group_id"        => $_REQUEST['group_id'],
			"deputycirclelead" => '1'
		), array('single'));

		if (!empty($deputyUser)) {
			$id = $deputyUser['id'];

			// Remove the deputy lead role
			$result = $this->common->query_normal("UPDATE user_circle SET deputycirclelead = '0' WHERE `id` = '" . $id . "'");

			if ($result) {
				$this->response(true, "Deputy circle lead removed successfully.");
			} else {
				$this->response(false, "There was a problem removing the deputy circle lead. Please try again.");
			}
		} else {
			$this->response(false, "No deputy circle lead found for this user in the specified circle and group.");
		}
	}

	public function deleteUserCircle()
	{
		if (empty($_REQUEST['id'])) {
			$this->response(false, "Please enter your id");
			die();
		}
		$this->common->deleteData('user_circle', array('id' => $_REQUEST['id']));
		$this->response(true, 'Circle User Deleted Successfully');
	}
	//////////////////////////////////////new changes on mail///////////////////////////////////////////////////
	public function sendEmailtoUserGroup()
	{
		if ($_REQUEST['user_id']) {
			$id  = $_REQUEST['id'];
			$ecode = base64_encode($id);
			$userDetailFrom1 = $this->common->getData('user', array('user_id' => $_REQUEST['user_id']), array('single'));
			$data['sendername'] = $userDetailFrom1['first_name'] . " " . $userDetailFrom1['last_name'];
			$data['useremail'] = "";

			$baseUrl = ADMIN_BASE_URL . 'user/UpdateUserPayment/';
			$data["link"] = $baseUrl . $ecode;

			$data['message'] = '<p>As we prepare for the upcoming Interfriends cycle, we kindly request that you confirm your monthly savings and anticipated payout date.</p><p>To provide this important information, please click on the link provided below.</p><a href="' . $data["link"] . '">click here</a>
                <p>Thank you for your prompt attention to this matter.</p>';

			$messaged = $this->load->view('template/common-mail', $data, true);
			$mail = $this->sendMail($userDetailFrom1['email'], "New Savings Cycle Decision", $messaged);

			if ($mail) {
				$this->response(true, "Mail sent successfully!");
			} else {
				$this->response(false, "There is a problem, please try again.");
			}
		} else {
			$this->response(false, "User id is required, please try again.");
		}
	}

	// created by @krishn on 06-06-25
	// public function sendEmailtoUserGroupAll()
	// {
	// 	$created_at = date('Y-m-d');
	// 	$month = date("m", strtotime($created_at));

	// 	$group_id = $_REQUEST['group_id'] ?? null;

	// 	if (!$group_id) {
	// 		$this->response(false, "Group ID is required.");
	// 		return;
	// 	}

	// 	// Check if mail already sent this month for this group
	// 	$check = $this->common->getData('mail_records', [
	// 		"Month(created_at)" => $month,
	// 		"group_id" => $group_id
	// 	], ['single']);

	// 	if (!empty($check)) {
	// 		$this->response(false, "Mail already sent to all users for this month.");
	// 		return;
	// 	}

	// 	// Fetch users of the group
	// 	$where = "UG.group_id = '" . $group_id . "' AND U.status != '2'";
	// 	$this->db->select('U.*');
	// 	$this->db->from('user_group as UG');
	// 	$this->db->join('user as U', 'U.user_id = UG.user_id');
	// 	$this->db->where($where);
	// 	$this->db->order_by("UG.id", 'DESC');
	// 	$users = $this->db->get()->result_array();

	// 	if (empty($users)) {
	// 		$this->response(false, "No users found in this group.");
	// 		return;
	// 	}

	// 	$successCount = 0;

	// 	foreach ($users as $user) {
	// 		$userId = $user['user_id'];
	// 		$encodedId = base64_encode($userId);

	// 		$data['sendername'] = $user['first_name'] . " " . $user['last_name'];
	// 		$data['useremail'] = "";
	// 		$data["link"] = ADMIN_BASE_URL . 'user/UpdateUserPayment/' . $encodedId;
	// 		$data['message'] = '
	//         <p>As we prepare for the upcoming Interfriends cycle, we kindly request that you confirm your monthly savings and anticipated payout date.</p>
	//         <p>To provide this important information, please click on the link provided below.</p>
	//         <a href="' . $data["link"] . '">Click here</a>
	//         <p>Thank you for your prompt attention to this matter.</p>';

	// 		$emailBody = $this->load->view('template/common-mail', $data, true);

	// 		if ($this->sendMail($user['email'], "New Savings Cycle Decision", $emailBody)) {
	// 			$successCount++;
	// 		}
	// 	}

	// 	// Insert mail record only if at least one mail was sent successfully
	// 	if ($successCount > 0) {
	// 		$this->common->insertData('mail_records', [
	// 			'mail_sent' => 1,
	// 			'created_at' => $created_at,
	// 			'group_id' => $group_id
	// 		]);
	// 		$this->response(true, "Mail sent successfully to {$successCount} user(s).");
	// 	} else {
	// 		$this->response(false, "Unable to send emails. Please try again.");
	// 	}
	// }

	// created by @krishn on 01-07-26
	public function sendEmailtoUserGroupAll()
	{
		$created_at = date('Y-m-d');
		$month      = date("m", strtotime($created_at));

		$group_id = $_REQUEST['group_id'] ?? null;

		if (!$group_id) {
			$this->response(false, "Group ID is required.");
			return;
		}

		// Check if mail already queued/sent this month
		$check = $this->common->getData(
			'mail_records',
			array(
				"Month(created_at)" => $month,
				"group_id" => $group_id
			),
			array('single')
		);

		if (!empty($check)) {
			$this->response(false, "Mail already sent to all users for this month.");
			return;
		}

		// Fetch users
		$where = "UG.group_id = '" . $group_id . "' AND U.status != '2'";

		$this->db->select('U.*');
		$this->db->from('user_group UG');
		$this->db->join('user U', 'U.user_id = UG.user_id');
		$this->db->where($where);
		$this->db->order_by('UG.id', 'DESC');

		$users = $this->db->get()->result_array();

		if (empty($users)) {
			$this->response(false, "No users found in this group.");
			return;
		}

		$queueCount = 0;

		foreach ($users as $user) {

			$encodedId = base64_encode($user['user_id']);

			$data = array();
			$data['sendername'] = $user['first_name'] . " " . $user['last_name'];
			$data['useremail'] = "";
			$data['link'] = ADMIN_BASE_URL . 'user/UpdateUserPayment/' . $encodedId;

			$data['message'] = '
			<p>As we prepare for the upcoming Interfriends cycle, we kindly request that you confirm your monthly savings and anticipated payout date.</p>

			<p>To provide this important information, please click on the link provided below.</p>

			<a href="' . $data['link'] . '">Click here</a>

			<p>Thank you for your prompt attention to this matter.</p>';

			$emailBody = $this->load->view('template/common-mail', $data, true);

			$queueData = array(
				'user_id'      => $user['user_id'],
				'group_id'     => $group_id,
				'email'        => $user['email'],
				'subject'      => 'New Savings Cycle Decision',
				'message'      => $emailBody,
				'status'       => 0, // Pending
				'attempts'     => 0,
				'created_at'   => date('Y-m-d H:i:s'),
				'updated_at'   => date('Y-m-d H:i:s')
			);

			if ($this->common->insertData('email_queue', $queueData)) {
				$queueCount++;
			}
		}

		if ($queueCount > 0) {

			$this->common->insertData(
				'mail_records',
				array(
					'mail_sent'  => 1,
					'group_id'   => $group_id,
					'created_at' => $created_at
				)
			);

			$this->response(
				true,
				$queueCount . " email(s) added to queue successfully.",
				array(
					"queued_emails" => $queueCount
				)
			);
		} else {

			$this->response(false, "Unable to add emails to queue.");
		}
	}

	public function editUserGroupJnr()
	{
		$data = "";
		if ($_REQUEST['id']) {
			$data =   base64_decode($_REQUEST['id']);
		}
		$id = $data;
		unset($_REQUEST['id']);
		$post = $this->common->getField('user_group', $_REQUEST);

		if (!empty($post)) {
			$result = $this->common->updateData('user_group', $post, array('id' => $id));
		} else {
			$result = "";
		}

		if ($result) {
			$this->response(true, "Member Info Successfully Updated");
		} else {
			$this->response(false, "There is a problem, please try again.");
		}
	}

	// created by @krishn on 20-05-25
	public function paymentallNotification()
	{
		if (empty($_REQUEST['start'])) {
			$start = 200;
			$end = 0;
		} else {
			$start = 200;
			$end = $_REQUEST['start'];
		}

		$where = " U.status != '2' AND U.recommended = 0";

		if (!empty($_REQUEST['search_keyword'])) {
			$keyword = $_REQUEST['search_keyword'];
			$where .= " AND (
				U.first_name LIKE '%$keyword%' OR
				U.last_name LIKE '%$keyword%' OR
				U.email LIKE '%$keyword%' OR
				UG.amount LIKE '%$keyword%'
			)";
		}

		// Filter by loan_type
		if (!empty($_REQUEST['loan_type'])) {
			$loan_type = $_REQUEST['loan_type'];
			$where .= " AND UG.loan_type = '$loan_type'";
		}

		// Filter by status
		if (!empty($_REQUEST['status'])) {
			$status = $_REQUEST['status'];
			$where .= " AND UG.status = '$status'";
		}

		// Filter by group_type
		if (!empty($_REQUEST['group_type_id'])) {
			$group_type = $_REQUEST['group_type_id'];
			$where .= " AND UG.group_type_id = '$group_type'";
		}

		// Filter by date_range (format: "2024-01-01,2024-01-31")
		if (!empty($_REQUEST['date_range'])) {
			$date_range = explode(',', $_REQUEST['date_range']);
			if (count($date_range) === 2) {
				$startDate = trim($date_range[0]);
				$endDate = trim($date_range[1]);
				$where .= " AND DATE(UG.created_at) BETWEEN '$startDate' AND '$endDate'";
			}
		}


		$result = $this->user_model->paymentallNotification_detail($where, array(), $start, $end);
		$userCount = $this->user_model->paymentallNotification_detail($where, array('count'));

		$countData = $end + 1;

		if (!empty($result)) {
			foreach ($result as $key => $value) {
				if (!empty($value['profile_image'])) {
					$result[$key]['profile_image'] = base_url($value['profile_image']);
					$result[$key]['profile_image_thumb'] = base_url($value['profile_image']);
				} else {
					$result[$key]['profile_image'] = "assets/img/default-user-icon.jpg";
					$result[$key]['profile_image_thumb'] = "assets/img/default-user-icon.jpg";
				}

				$result[$key]['sno'] = $countData++;
			}

			$this->response(true, "User fetch Successfully.", array("userList" => $result, "userCount" => $userCount));
		} else {
			$this->response(true, "User data not dound.", array("userList" => array(), "userCount" => $userCount));
		}
	}

	// changed by @krishn on 25-05-25
	public function getDownloadableData()
	{
		// ini_set('display_errors', 1);
		// // limit code start
		// if (empty($_REQUEST['start'])) {
		// 	$start = 0;
		// } else {
		// 	$start = $_REQUEST['start'];
		// }
		// $limit = 200;
		// // limit code end

		// limit code start
		$limit = !empty($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 200;
		$start = !empty($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
		// limit code end

		$search = !empty($_REQUEST['search']) ? $this->db->escape_like_str(trim($_REQUEST['search'])) : '';
		$type   = isset($_REQUEST['product_category']) ? $_REQUEST['product_category'] : '';

		$resultList = [];
		$where = "U.status != '2'";
		$searchCond = '';

		// if (!empty($search)) {
		// 	$searchCond = " AND (U.first_name LIKE '%$search%' OR U.last_name LIKE '%$search%' OR U.email LIKE '%$search%')";
		// }

		if (!empty($search)) {
			$searchCond = " AND (
				U.first_name LIKE '%{$search}%' ESCAPE '!' OR 
				U.last_name LIKE '%{$search}%' ESCAPE '!' OR 
				U.email LIKE '%{$search}%' ESCAPE '!'
			)";
		}

		$field = '';
		$table = '';
		$fullWhere = '';

		$createdAtAliasMap = [
			'investment' => 'I',
			'property' => 'I',
			'dividend' => 'I',
			'payout' => 'PU',
			'pf_user' => 'PF',
			'emergency_loan_completed' => 'UEL',
			'emergency_loan_active' => 'UEL',
			'loan_approved' => 'UL',
			'loan_completed' => 'UL',
			'loan_paid' => 'ULP',
			'active_loan' => 'UL',
			'help_to_pay_car_insurance' => 'UL',
			'help_to_buy_car' => 'UL',
			'help_to_pay_cc' => 'UL',
			'help_to_buy_house' => 'UL',
			'miscellaneous' => 'UM',
			'welfare_cycle' => 'UGL',
			'jnr_saving' => 'UGL',
			'saving_pending' => 'UGL',
			'saving' => 'UGL',
			'safekeeping_add' => 'SK',
			'safekeeping_remove' => 'SK',
		];

		$createdAtAlias = isset($createdAtAliasMap[$type]) ? $createdAtAliasMap[$type] : 'U';

		if (!empty($_REQUEST['date_range'])) {
			$date_range = explode(',', $_REQUEST['date_range']);
			if (count($date_range) == 2) {
				$startDate = trim($date_range[0]);
				$endDate   = trim($date_range[1]);
				if ($type == 'saving' || $type == 'saving_pending') {
					$searchCond .= " AND DATE(UGL.date) BETWEEN '$startDate' AND '$endDate'";
				} else {
					$searchCond .= " AND DATE($createdAtAlias.created_at) BETWEEN '$startDate' AND '$endDate'";
				}
			}
		}

		switch ($type) {
			case 'investment':
				$table = 'investment I, user U';
				$fullWhere = "$where AND I.user_id = U.user_id AND I.group_id != 34 AND payment_status = '2' $searchCond";
				$field = 'I.user_id, U.first_name, U.last_name, U.email, I.amount, I.created_at';
				break;

			case 'property':
				$table = 'investment I, user U';
				$fullWhere = "$where AND I.user_id = U.user_id AND I.group_id != 34 AND I.investment_type = 1 $searchCond";
				$field = 'I.user_id, U.first_name, U.last_name, U.email, I.amount, I.created_at';
				break;

			case 'dividend':
				$table = 'investment I, user U';
				$fullWhere = "$where AND I.user_id = U.user_id AND I.group_id != 34 AND payment_status = '1' $searchCond";
				$field = 'I.user_id, U.first_name, U.last_name, U.email, I.amount, I.created_at';
				break;

			case 'payout':
				$table = 'payout_cycle PU, user U';
				$fullWhere = "PU.user_id = U.user_id AND U.status != '2' $searchCond";
				$field = 'PU.user_id, U.first_name, U.last_name, U.email, PU.payout_amount AS amount, PU.created_at';
				break;

			case 'pf_user':
				$table = 'pf_user PF, user U';
				$fullWhere = "PF.user_id = U.user_id AND U.status != '2' AND PF.group_id != 34 AND PF.payment_type = '2' $searchCond";
				$field = 'PF.user_id, U.first_name, U.last_name, U.email, PF.pf_amount AS amount, PF.created_at';
				break;

			case 'emergency_loan_completed':
				$table = 'user_emergency_loan UEL, user U';
				$fullWhere = "UEL.user_id = U.user_id AND U.status != '2' $searchCond";
				$field = 'UEL.user_id, U.first_name, U.last_name, U.email, UEL.loan_amount AS amount, UEL.created_at';
				break;

			case 'emergency_loan_active':
				$table = 'user_emergency_loan UEL, user U';
				$fullWhere = "UEL.user_id = U.user_id AND U.status != '2' AND UEL.status = '4' $searchCond";
				$field = 'UEL.user_id, U.first_name, U.last_name, U.email, UEL.loan_amount AS amount, UEL.created_at';
				break;

			// case 'loan_completed':
			// 	$table = 'user_loan UL, user U';
			// 	$fullWhere = "UL.user_id = U.user_id AND U.status != '2' AND UL.group_id != 34 $searchCond";
			// 	$field = 'UL.user_id, U.first_name, U.last_name, U.email, UL.loan_amount AS amount, UL.created_at';
			// 	break;

			// case 'loan_paid':
			// 	$table = 'user_loan_payment ULP, user U';
			// 	$fullWhere = "ULP.user_id = U.user_id AND U.status != '2' AND ULP.group_id != 34 $searchCond";
			// 	$field = 'ULP.user_id, U.first_name, U.last_name, U.email, ULP.amount, ULP.created_at';
			// 	break;

			// case 'active_loan':
			// 	$table = 'user_loan UL 
			// 		LEFT JOIN (
			// 			SELECT user_id, group_id, SUM(amount) AS total_paid 
			// 			FROM user_loan_payment 
			// 			GROUP BY user_id, group_id
			// 		) AS ULP ON UL.user_id = ULP.user_id AND UL.group_id = ULP.group_id,
			// 		user U';

			// 	$fullWhere = "UL.user_id = U.user_id AND U.status != '2' AND UL.group_id != 34 $searchCond";

			// 	$field = 'UL.user_id, U.first_name, U.last_name, U.email, 
			// 		SUM(UL.loan_amount) AS total_completed,
			// 		IFNULL(ULP.total_paid, 0) AS total_paid,
			// 		(SUM(UL.loan_amount) - IFNULL(ULP.total_paid, 0)) AS amount,
			// 		MAX(UL.created_at) AS created_at';

			// 	$groupBy = 'UL.user_id';
			// 	break;

			case 'loan_approved':
				$table = 'user_loan UL, user U';
				$fullWhere = "UL.user_id = U.user_id AND U.status != '2' AND UL.group_id != 34 AND UL.loan_type = 1 AND UL.status = '4' $searchCond";
				$field = 'UL.user_id, UL.group_id, U.first_name, U.last_name, U.email, UL.loan_amount AS amount, UL.created_at';
				break;

			case 'loan_completed':
				$table = 'user_loan UL
				LEFT JOIN (
					SELECT loan_id, SUM(amount) AS total_paid
					FROM user_loan_payment
					GROUP BY loan_id
				) AS ULP ON ULP.loan_id = UL.id,
				user U';
				$fullWhere = "UL.user_id = U.user_id
					AND U.status != '2'
					AND UL.group_id != 34
					AND UL.loan_type = 1
					AND UL.status = '2'
					AND EXISTS (
						SELECT 1
						FROM user_loan_status_history ULSH
						WHERE ULSH.loan_id = UL.id
						AND ULSH.status = '2'
					) $searchCond";
				$field = 'UL.user_id, UL.group_id, U.first_name, U.last_name, U.email, UL.loan_amount AS amount, IFNULL(ULP.total_paid, 0) AS total_paid, UL.created_at';
				break;

			// case 'loan_paid':
			// 	$table = 'user_loan_payment ULP, user U';
			// 	$fullWhere = "ULP.user_id = U.user_id AND U.status != '2' AND ULP.group_id != 34 $searchCond";
			// 	$field = 'ULP.user_id, ULP.group_id, U.first_name, U.last_name, U.email, ULP.amount, ULP.created_at';
			// 	break;

			case 'loan_paid':
				$table = 'user_loan_payment ULP, user U, user_loan UL';
				$fullWhere = "ULP.user_id = U.user_id
					AND U.status != '2'
					AND ULP.group_id != 34
					AND ULP.loan_id = UL.id
					AND UL.loan_type = 1 $searchCond";
				$field = 'ULP.user_id, ULP.group_id, U.first_name, U.last_name, U.email, ULP.amount, ULP.created_at';
				break;

			// case 'active_loan':
			// 	$table = 'user_loan UL 
			// 	LEFT JOIN (
			// 		SELECT user_id, group_id, SUM(amount) AS total_paid 
			// 		FROM user_loan_payment 
			// 		GROUP BY user_id, group_id
			// 	) AS ULP ON UL.user_id = ULP.user_id AND UL.group_id = ULP.group_id,
			// 	user U';

			// 	$fullWhere = "UL.user_id = U.user_id AND U.status != '2' AND UL.group_id != 34 AND UL.loan_type = 1 $searchCond";

			// 	$field = 'UL.user_id, UL.group_id, U.first_name, U.last_name, U.email, 
			// 	SUM(UL.loan_amount) AS total_completed,
			// 	IFNULL(ULP.total_paid, 0) AS total_paid,
			// 	(SUM(UL.loan_amount) - IFNULL(ULP.total_paid, 0)) AS amount,
			// 	MAX(UL.created_at) AS created_at';

			// 	$groupBy = 'UL.user_id, UL.group_id';
			// 	break;

			case 'active_loan':

				$table = "
				user_loan UL
				LEFT JOIN (
					SELECT
						loan_id,
						SUM(amount) AS total_paid
					FROM user_loan_payment
					GROUP BY loan_id
				) ULP ON ULP.loan_id = UL.id
				INNER JOIN user U ON U.user_id = UL.user_id
			";

				$fullWhere = "
				U.status != '2'
				AND UL.group_id != 34
				AND UL.loan_type = 1
				AND UL.status = 4
				$searchCond
			";

				$field = "
				UL.user_id,
				UL.group_id,
				U.first_name,
				U.last_name,
				U.email,

				SUM(UL.loan_amount) AS total_completed,

				SUM(IFNULL(ULP.total_paid,0)) AS total_paid,

				SUM(UL.loan_amount - IFNULL(ULP.total_paid,0)) AS amount,

				MAX(UL.created_at) AS created_at
			";

				$groupBy = "UL.user_id, UL.group_id";

				break;

			case 'help_to_pay_car_insurance':
				$table = 'user_loan UL, user U';
				$fullWhere = "UL.loan_type = '2' AND UL.user_id = U.user_id AND U.status != '2' AND UL.group_id != 34 $searchCond";
				$field = 'UL.user_id, U.first_name, U.last_name, U.email, UL.loan_amount as amount, UL.created_at';
				break;

			case 'help_to_buy_car':
				$table = 'user_loan UL, user U';
				$fullWhere = "UL.loan_type = '3' AND UL.user_id = U.user_id AND U.status != '2' AND UL.group_id != 34 $searchCond";
				$field = 'UL.user_id, U.first_name, U.last_name, U.email, UL.loan_amount as amount, UL.created_at';
				break;

			case 'help_to_pay_cc':
				$table = 'user_loan UL, user U';
				$fullWhere = "UL.loan_type = '4' AND UL.user_id = U.user_id AND U.status != '2' AND UL.group_id != 34 $searchCond";
				$field = 'UL.user_id, U.first_name, U.last_name, U.email, UL.loan_amount as amount, UL.created_at';
				break;

			case 'help_to_buy_house':
				$table = 'user_loan UL, user U';
				$fullWhere = "UL.loan_type = '6' AND UL.user_id = U.user_id AND U.status != '2' AND UL.group_id != 34 $searchCond";
				$field = 'UL.user_id, U.first_name, U.last_name, U.email, UL.loan_amount as amount, UL.created_at';
				break;

			case 'miscellaneous':
				$table = 'user_miscellaneous UM, user U';
				$fullWhere = "UM.user_id = U.user_id AND U.status != '2' and UM.group_id != 34 $searchCond";
				$field = 'UM.user_id, UM.title, U.first_name, U.last_name, U.email, UM.amount, UM.created_at';
				break;

			case 'welfare_cycle':
				$table = 'user_group_lifecycle UGL, user U';
				$fullWhere = "UGL.user_id = U.user_id AND U.status != '2' and UGL.group_id != 34 $searchCond";
				$field = 'UGL.user_id, U.first_name, U.last_name, U.email, UGL.amount, UGL.created_at';
				break;

			case 'jnr_saving':
				$table = 'user_group UG, user U, group_lifecycle GL, user_group_lifecycle UGL';
				$fullWhere = "UG.user_id = U.user_id AND UG.group_id = GL.group_id AND GL.id = UGL.groupLifecycle_id AND UGL.user_id = U.user_id AND UGL.group_id != 0 AND U.status != '2' $searchCond";
				$field = 'UG.user_id, U.first_name, U.last_name, U.email, UGL.amount, UGL.created_at';
				break;

			// case 'jnr_saving':
			// 	$table = 'user_group UG, user U';
			// 	$fullWhere = "UG.user_id = U.user_id AND U.status != '2' and UG.group_id != 34 $searchCond";
			// 	$field = 'UG.user_id, U.first_name, U.last_name, U.email, UG.jnr_amount as amount, UG.created_at';
			// 	break;

			case 'saving_pending':
				$table = 'user_group UG, user U, group_lifecycle GL, user_group_lifecycle UGL';
				$fullWhere = "UG.user_id = U.user_id AND UG.group_id = GL.group_id AND GL.id = UGL.groupLifecycle_id AND UGL.user_id = U.user_id AND UGL.group_id != 0 AND U.status != '2' AND UGL.status = 1 $searchCond";
				$field = "U.user_id, U.first_name, U.last_name, U.email, UGL.amount, UGL.created_at";
				break;

			case 'saving':
				$table = 'user_group UG, user U, group_lifecycle GL, user_group_lifecycle UGL';
				$fullWhere = "UG.user_id = U.user_id AND UG.group_id = GL.group_id AND GL.id =UGL.groupLifecycle_id AND UGL.user_id = U.user_id AND UGL.group_id != 0 AND U.status != '2' AND UGL.status = 2 $searchCond";
				$field = "U.user_id, U.first_name, U.last_name, U.email, UGL.amount, UGL.created_at";
				break;

			// case 'saving':
			// 	$table = 'user_group UG, user U';
			// 	$fullWhere = "UG.user_id = U.user_id AND U.status != '2' and UG.group_id != 34 $searchCond";
			// 	$field = "U.user_id, U.first_name, U.last_name, U.email, UG.amount, UG.created_at";
			// 	break;

			case 'safekeeping_add':
				$table = 'safe_keeping SK, user U';
				$fullWhere = "SK.user_id = U.user_id AND SK.group_id != 0 AND U.status != '2' AND SK.pyment_type = 2 $searchCond";
				$field = "U.user_id, U.first_name, U.last_name, U.email, SK.amount_total AS amount, SK.created_at";
				break;

			case 'safekeeping_remove':
				$table = 'safe_keeping SK, user U';
				$fullWhere = "SK.user_id = U.user_id AND SK.group_id != 0 AND U.status != '2' AND SK.pyment_type = 1 $searchCond";
				$field = "U.user_id, U.first_name, U.last_name, U.email, SK.amount, SK.created_at";
				break;

			default:
				$this->response(true, "Invalid type.", ['resultList' => [], 'resultCount' => 0]);
				return;
		}

		// Fetch paginated result
		$options = [
			'field' => $field,
			'limit' => $limit,
			'offset' => $start,
			'sort_by' => 'created_at',
			'sort_direction' => 'desc',
		];

		if (isset($groupBy) && !empty($groupBy)) {
			$options['group_by'] = $groupBy;
		}

		$resultList = $this->common->getData($table, $fullWhere, $options);
		// $totalList = $this->common->getData($table, $fullWhere, ['count', 'group_by' => $groupBy]);
		$totalOptions = ['count'];
		if (!empty($groupBy)) {
			$totalOptions['group_by'] = $groupBy;
		}
		$totalList = $this->common->getData($table, $fullWhere, $totalOptions);


		$countData = $start + 1;
		foreach ($resultList as &$row) {
			$row['type'] = $type == 'miscellaneous' ? $row['title'] : $type;
			$row['sno'] = $countData++;
		}

		$this->response(true, !empty($resultList) ? "Data fetched successfully." : "Record not found.", [
			'resultList' => $resultList,
			'resultCount' => $totalList
		]);
	}



	// created by @krishn on 22-05-25
	public function getAllMissedPayments()
	{
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}

		$where1 = "L.status = '1'";
		$where2 = "UGL.status = '1'";
		$where3 = "UM.user_id = '1'";

		// Fetch all data first (no limit)
		$allLoan = $this->user_model->loan_detail($where1, array());
		$allSaving = $this->user_model->saving_detail($where2, array());
		$allMiscellaneous = $this->user_model->miscellaneous_detail($where3, array());

		// Add type
		foreach ($allLoan as &$item) {
			$item['type'] = 'loan';
		}
		foreach ($allSaving as &$item) {
			$item['type'] = 'saving';
		}
		foreach ($allMiscellaneous as &$item) {
			$item['type'] = 'miscellaneous';
		}

		// Merge all data
		$mergedData = array_merge($allLoan, $allSaving, $allMiscellaneous);

		/* Group Filter */
		if (!empty($_REQUEST['group_ids'])) {

			$groupIds = array_map('intval', explode(',', $_REQUEST['group_ids']));

			$mergedData = array_filter($mergedData, function ($item) use ($groupIds) {

				return (
					isset($item['group_id']) &&
					in_array((int)$item['group_id'], $groupIds)
				);
			});
		}


		/* Circle Filter */
		if (!empty($_REQUEST['circle_ids'])) {

			$circleIds = array_map('intval', explode(',', $_REQUEST['circle_ids']));

			$mergedData = array_filter($mergedData, function ($item) use ($circleIds) {

				if (empty($item['user_id'])) {
					return false;
				}

				$userCircles = $this->common->getData(
					'user_circle',
					array('user_id' => $item['user_id'])
				);

				if (empty($userCircles)) {
					return false;
				}

				foreach ($userCircles as $circle) {

					if (in_array((int)$circle['circle_id'], $circleIds)) {
						return true;
					}
				}

				return false;
			});
		}

		// Strict Type Filter: only allow loan/saving/miscellaneous
		// if (isset($_REQUEST['type'])) {
		// 	$type = trim(strtolower(trim($_REQUEST['type'])));
		// 	$allowedTypes = ['loan', 'saving', 'miscellaneous'];

		// 	if (in_array($type, $allowedTypes, true)) {
		// 		$mergedData = array_filter($mergedData, function ($item) use ($type) {
		// 			return isset($item['type']) && strtolower($item['type']) === $type;
		// 		});
		// 	}
		// 	// if type not in whitelist, no filter is applied
		// }

		/* Type Filter */
		if (isset($_REQUEST['type'])) {

			$type = trim(strtolower($_REQUEST['type']));
			$allowedTypes = ['loan', 'saving', 'miscellaneous'];

			// If type key comes but value is blank
			if ($type == '') {

				$mergedData = [];
			} elseif (in_array($type, $allowedTypes, true)) {

				$mergedData = array_filter($mergedData, function ($item) use ($type) {

					return (
						isset($item['type']) &&
						strtolower($item['type']) == $type
					);
				});
			} else {

				// Invalid type
				$mergedData = [];
			}
		}

		// Date Range Filter
		if (!empty($_REQUEST['date_range'])) {

			$dateRange = explode(',', $_REQUEST['date_range']);

			if (count($dateRange) == 2) {

				$fromDate = trim($dateRange[0]);
				$toDate   = trim($dateRange[1]);

				// Handle reversed dates
				if (strtotime($fromDate) > strtotime($toDate)) {
					$temp = $fromDate;
					$fromDate = $toDate;
					$toDate = $temp;
				}

				$mergedData = array_filter($mergedData, function ($item) use ($fromDate, $toDate) {

					$itemDate = '';

					// Saving records use 'date' column
					if (
						isset($item['type']) &&
						strtolower($item['type']) == 'saving' &&
						!empty($item['date'])
					) {

						$itemDate = date('Y-m-d', strtotime($item['date']));
					}
					// Loan and Miscellaneous use created_at
					elseif (!empty($item['created_at'])) {

						$itemDate = date('Y-m-d', strtotime($item['created_at']));
					}

					if (empty($itemDate)) {
						return false;
					}

					return (
						$itemDate >= $fromDate &&
						$itemDate <= $toDate
					);
				});
			}
		}

		// Search Filter
		if (!empty($_REQUEST['search_keyword'])) {
			$keyword = strtolower($_REQUEST['search_keyword']);
			$mergedData = array_filter($mergedData, function ($item) use ($keyword) {
				$user = get_user_details($item['user_id']);
				return (
					strpos(strtolower($user['first_name']), $keyword) !== false ||
					strpos(strtolower($user['last_name']), $keyword) !== false ||
					strpos(strtolower($user['email']), $keyword) !== false ||
					(isset($item['loan_total']) && strpos(strtolower($item['loan_total']), $keyword) !== false) ||
					(isset($item['amount']) && strpos(strtolower($item['amount']), $keyword) !== false) ||
					(isset($item['type']) && strpos(strtolower($item['type']), $keyword) !== false)
				);
			});
		}

		// // Reset indexes after filtering
		// $mergedData = array_values($mergedData);
		// $mergedCount = count($mergedData);

		// // Pagination
		// $paginatedData = array_slice($mergedData, $end, $start);

		// Sort data by date DESC
		// Sort by latest date DESC
		usort($mergedData, function ($a, $b) {

			// Saving records should use `date`
			if (
				isset($a['type']) &&
				$a['type'] == 'saving' &&
				!empty($a['date'])
			) {
				$dateA = strtotime($a['date']);
			} else {
				$dateA = !empty($a['created_at'])
					? strtotime($a['created_at'])
					: 0;
			}


			// Saving records should use `date`
			if (
				isset($b['type']) &&
				$b['type'] == 'saving' &&
				!empty($b['date'])
			) {
				$dateB = strtotime($b['date']);
			} else {
				$dateB = !empty($b['created_at'])
					? strtotime($b['created_at'])
					: 0;
			}


			// Descending order
			if ($dateA == $dateB) {
				return 0;
			}

			return ($dateA < $dateB) ? 1 : -1;
		});


		// Reset indexes
		$mergedData = array_values($mergedData);

		$mergedCount = count($mergedData);


		// Pagination
		$paginatedData = array_slice(
			$mergedData,
			$end,
			$start
		);
		// Append user_info and SNO
		$sno = $end + 1;
		// foreach ($paginatedData as $key => &$value) {
		// 	$value['sno'] = $sno++;
		// 	$value['user_info'] = get_user_details($value['user_id']);
		// }

		foreach ($paginatedData as $key => &$value) {

			// Saving records should expose date in created_at
			if (
				isset($value['type']) &&
				$value['type'] == 'saving' &&
				!empty($value['date'])
			) {

				$value['created_at'] = $value['date'];
			}

			$value['sno'] = $sno++;

			$value['user_info'] = get_user_details(
				$value['user_id']
			);
		}

		$this->response(true, "Missed Payment Data fetched successfully.", array(
			"lists" => $paginatedData,
			"listCount" => $mergedCount
		));
	}

	// created by @krishn on 27-05-25
	public function getAllOutstandingPayments()
	{
		// limit code start
		if (empty($_REQUEST['start'])) {
			$start = 10;
			$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end


		$where = "L.status = '1'";

		$loanResult = $this->user_model->loan_detail($where, array(), $start, $end);
		$loanCount = $this->user_model->loan_detail($where, array('count'), $start, $end);

		$where = "UM.user_id = '1'";

		$miscellaneousResult = $this->user_model->miscellaneous_detail($where, array(), $start, $end);
		$miscellaneousCount = $this->user_model->miscellaneous_detail($where, array('count'));

		$this->response(true, "Outstanding Payment Data fetched successfully.", array(
			"lists" => $miscellaneousResult,
			"listCount" => $miscellaneousCount
		));
	}

	// API created by @krishn on 26/05/25
	public function getUserWaitingList()
	{
		if (empty($_REQUEST['start'])) {
			$start = 0;
		} else {
			$start = $_REQUEST['start'];
		}
		$limit = 10;

		$search = '';
		if (!empty($_REQUEST['search_keyword'])) {
			$keyword = $this->db->escape_like_str($_REQUEST['search_keyword']);
			$search = " AND (first_name LIKE '%$keyword%' OR last_name LIKE '%$keyword%' OR email LIKE '%$keyword%')";
		}

		$where = "exist_in_waiting = 1" . $search;

		$waitingUserData = $this->common->getData('user', $where, array(), $limit, $start);
		$totalCount = $this->common->getData('user', $where, array('count'));

		$sno = $start + 1;

		if (!empty($waitingUserData)) {
			foreach ($waitingUserData as $key => $value) {
				$waitingUserData[$key]['sno'] = $sno++;

				if (!empty($value['profile_image'])) {
					$waitingUserData[$key]['profile_image'] = base_url($value['profile_image']);
					$waitingUserData[$key]['profile_image_thumb'] = base_url($value['profile_image']);
				} else {
					$waitingUserData[$key]['profile_image'] = base_url("assets/img/default-user-icon.jpg");
					$waitingUserData[$key]['profile_image_thumb'] = base_url("assets/img/default-user-icon.jpg");
				}
			}

			$this->response(true, "Data fetched successfully.", array(
				"users" => $waitingUserData,
				"totalCount" => $totalCount
			));
		} else {
			$this->response(false, "Data not found.");
		}
	}

	// API created by @krishn on 10/06/26
	public function getUserNotes($user_id)
	{
		// Pagination
		if (empty($_REQUEST['start'])) {
			$limit = 10;
			$offset = 0;
		} else {
			$limit = 10;
			$offset = (int)$_REQUEST['start'];
		}

		// Fetch notes from all tables
		$cycleNotes = $this->common->getData(
			'user_cycle_status_history',
			['user_id' => $user_id],
			['all']
		);

		$emergencyLoanNotes = $this->common->getData(
			'user_emergency_loan_status_history',
			['user_id' => $user_id],
			['all']
		);

		$loanNotes = $this->common->getData(
			'user_loan_status_history',
			['user_id' => $user_id],
			['all']
		);

		$miscellaneousNotes = $this->common->getData(
			'user_miscellaneous_status_history', // change if actual table name differs
			['user_id' => $user_id],
			['all']
		);

		// Prevent 500 errors
		$cycleNotes = is_array($cycleNotes) ? $cycleNotes : [];
		$emergencyLoanNotes = is_array($emergencyLoanNotes) ? $emergencyLoanNotes : [];
		$loanNotes = is_array($loanNotes) ? $loanNotes : [];
		$miscellaneousNotes = is_array($miscellaneousNotes) ? $miscellaneousNotes : [];

		// Add type
		foreach ($cycleNotes as &$note) {
			$note['type'] = 'cycle';
		}

		foreach ($emergencyLoanNotes as &$note) {
			$note['type'] = 'emergency_loan';
		}

		foreach ($loanNotes as &$note) {
			$note['type'] = 'loan';
		}

		foreach ($miscellaneousNotes as &$note) {
			$note['type'] = 'miscellaneous';
		}

		unset($note);

		// Merge all notes
		$notes = array_merge(
			$cycleNotes,
			$emergencyLoanNotes,
			$loanNotes,
			$miscellaneousNotes
		);

		// If no records found
		if (empty($notes)) {
			$this->response(
				false,
				"No notes found for this user.",
				[
					'notes' => [],
					'totalCount' => 0
				]
			);
			return;
		}

		// Type Filter
		if (!empty($_REQUEST['type'])) {

			$type = strtolower(trim($_REQUEST['type']));

			$allowedTypes = [
				'cycle',
				'emergency_loan',
				'loan',
				'miscellaneous'
			];

			if (in_array($type, $allowedTypes)) {

				$notes = array_filter($notes, function ($item) use ($type) {

					return isset($item['type']) &&
						strtolower($item['type']) === $type;
				});
			}
		}

		// Search Filter
		if (!empty($_REQUEST['search_keyword'])) {

			$keyword = strtolower(trim($_REQUEST['search_keyword']));

			$notes = array_filter($notes, function ($item) use ($keyword) {

				return (
					(isset($item['note_title']) &&
						strpos(strtolower($item['note_title']), $keyword) !== false)

					||

					(isset($item['note_description']) &&
						strpos(strtolower($item['note_description']), $keyword) !== false)
				);
			});
		}

		// Date Range Filter
		if (!empty($_REQUEST['date_range'])) {

			$dateRange = explode(',', $_REQUEST['date_range']);

			if (count($dateRange) == 2) {

				$fromDate = trim($dateRange[0]);
				$toDate   = trim($dateRange[1]);

				if (strtotime($fromDate) > strtotime($toDate)) {

					$temp = $fromDate;
					$fromDate = $toDate;
					$toDate = $temp;
				}

				$notes = array_filter($notes, function ($item) use ($fromDate, $toDate) {

					if (empty($item['created_at'])) {
						return false;
					}

					$itemDate = date('Y-m-d', strtotime($item['created_at']));

					return (
						$itemDate >= $fromDate &&
						$itemDate <= $toDate
					);
				});
			}
		}

		// Reset indexes
		$notes = array_values($notes);

		// Sort latest first
		usort($notes, function ($a, $b) {

			return strtotime($b['created_at']) - strtotime($a['created_at']);
		});

		$totalCount = count($notes);

		// If filters removed all records
		if ($totalCount == 0) {

			$this->response(
				false,
				"No notes found.",
				[
					'notes' => [],
					'totalCount' => 0
				]
			);
			return;
		}

		// Pagination
		$notes = array_slice($notes, $offset, $limit);

		// SNO
		$sno = $offset + 1;

		foreach ($notes as &$note) {

			$note['sno'] = $sno++;
		}

		unset($note);

		$this->response(
			true,
			"Notes fetched successfully.",
			[
				'notes' => $notes,
				'totalCount' => $totalCount
			]
		);
	}

	// updated by @krishn on 23/06/26
	public function updateInvestmentRequestStatus()
	{
		if (empty($_REQUEST['request_id'])) {
			$this->response(false, "Request ID is required.");
			return;
		}

		if (!isset($_REQUEST['status'])) {
			$this->response(false, "Status is required.");
			return;
		}

		$requestId = (int)$_REQUEST['request_id'];
		$status    = (int)$_REQUEST['status'];

		// Only Approve or Reject
		if (!in_array($status, [1, 2])) {
			$this->response(false, "Invalid status.");
			return;
		}

		// Get request details
		$request = $this->common->getData(
			'investment_request',
			['id' => $requestId],
			['single']
		);

		if (empty($request)) {
			$this->response(false, "Investment request not found.");
			return;
		}

		// Update status
		$result = $this->common->updateData(
			'investment_request',
			['status' => $status],
			['id' => $requestId]
		);

		if ($result) {

			// Insert into investment table when approved
			if ($status == 1) {

				$investmentData = array(
					'user_id'          => $request['user_id'],
					'group_id'         => $request['group_id'],
					'property_id'      => $request['property_id'],
					'amount'           => $request['amount'],

					'investment_type'  => 1,
					'payment_status'   => 2,
					'status'           => 1,
					'payment_method'   => 1,

					'description'      => $_REQUEST['description'] ?? 'Approved by Admin',
					'note_title'       => $_REQUEST['note_title'] ?? '',
					'note_description' => $_REQUEST['note_description'] ?? '',

					'created_at'       => date('Y-m-d H:i:s')
				);

				$this->common->insertData('investment', $investmentData);
			}


			/*---------------------------------------
			| User Information
			---------------------------------------*/
			$user = $this->common->getData('user', array('user_id' => $request['user_id']), array('single'));


			/*---------------------------------------
			| Notification Message
			---------------------------------------*/
			if ($status == 1) {

				$message = "Your investment request has been approved.";

				$emailMessage = "
                <p>We are pleased to inform you that your investment request has been approved successfully.</p>

                <p><strong>Investment Amount:</strong> £{$request['amount']}</p>

                <p>Thank you for investing with Interfriends.</p>
            ";
			} else {

				$message = "Your investment request has been rejected.";

				$emailMessage = "
                <p>We regret to inform you that your investment request has been rejected.</p>

                <p><strong>Investment Amount:</strong> £{$request['amount']}</p>

                <p>If you have any questions, please contact the administrator.</p>
            ";
			}


			/*---------------------------------------
			| Push Notification
			---------------------------------------*/
			$this->send_nofification(
				$request['user_id'],
				$_REQUEST['admin_id'],
				$request['group_id'],
				$message,
				$requestId,
				"11"
			);


			/*---------------------------------------
			| Send Email
			---------------------------------------*/
			$mailData = [];
			$mailData['sendername'] = $user['first_name'] . ' ' . $user['last_name'];
			$mailData['message'] = $emailMessage;

			$body = $this->load->view(
				'template/common-mail',
				$mailData,
				true
			);

			$subject = ($status == 1)
				? 'Investment Request Approved'
				: 'Investment Request Rejected';

			$this->sendMail(
				$user['email'],
				$subject,
				$body
			);


			$this->response(
				true,
				($status == 1)
					? "Investment request approved successfully."
					: "Investment request rejected successfully."
			);
		} else {

			$this->response(
				false,
				"There is a problem, please try again."
			);
		}
	}

	// API created by @krishn on 16/06/26
	public function sendRecommendUserApprovalReminder()
	{
		if (empty($_REQUEST['approval_id'])) {
			$this->response(false, "Approval ID is required.");
			return;
		}

		if (empty($_REQUEST['admin_id'])) {
			$this->response(false, "Admin ID is required.");
			return;
		}

		$approval = $this->common->getData('recommendation_approvals', array('id' => $_REQUEST['approval_id']), array('single'));
		if (empty($approval)) {
			$this->response(false, "Approval step not found.");
			return;
		}

		if ($approval['status'] != '0') {
			$this->response(false, "Reminder can be sent only for pending approval.");
			return;
		}

		$recommendUser = $this->common->getData('recommend_user', array('id' => $approval['recommend_id']), array('single'));
		if (empty($recommendUser)) {
			$this->response(false, "Recommended user not found.");
			return;
		}

		$admin = $this->common->getData('superAdmin', array('id' => $_REQUEST['admin_id']), array('single'));
		if (empty($admin)) {
			$this->response(false, "Admin not found.");
			return;
		}

		if ($approval['approver_role'] == 'admin') {
			$approver = $this->common->getData('superAdmin', array('id' => $approval['approver_id']), array('single'));
			$approverName = !empty($approver) ? $approver['name'] : "";
		} else {
			$approver = $this->common->getData('user', array('user_id' => $approval['approver_id']), array('single'));
			$approverName = !empty($approver) ? trim($approver['first_name'] . ' ' . $approver['last_name']) : "";
		}

		if (empty($approver) || empty($approver['email'])) {
			$this->response(false, "Approver email not found.");
			return;
		}

		$recommender = $this->common->getData('user', array('user_id' => $recommendUser['user_id']), array('single'));
		$secondRecommender = $this->common->getData('user', array('user_id' => $recommendUser['refer_user_id']), array('single'));

		$recommendedName = trim($recommendUser['first_name'] . ' ' . $recommendUser['last_name']);
		$recommenderName = !empty($recommender) ? trim($recommender['first_name'] . ' ' . $recommender['last_name']) : "";
		$secondRecommenderName = !empty($secondRecommender) ? trim($secondRecommender['first_name'] . ' ' . $secondRecommender['last_name']) : "";

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
				break;
		}

		$token = urlencode($approval['token']);
		$linkApprove = API_BASE_URL . "handleApproval/{$token}/1";
		$linkDecline = API_BASE_URL . "handleDecline/{$token}/2";
		$reminderSentAt = date('d M Y h:i A');

		$memberDetails = "
			<ol>
				<li><strong>Name of proposed member:</strong> {$recommendUser['first_name']}</li>
				<li><strong>Telephone number:</strong> {$recommendUser['mobile_number']}</li>
				<li><strong>Email:</strong> {$recommendUser['email']}</li>
				<li><strong>Employment status:</strong> {$employmentStatus}</li>
			</ol>
		";

		if ($approval['approver_role'] == 'second_recommender') {
			$message = "
				<p><strong>{$recommenderName}</strong> has recommended <strong>{$recommendedName}</strong> to join Interfriends. Please find below the pertinent details of the recommended member:</p>
				{$memberDetails}
				<p>Please review this recommendation and choose an action below.</p>
			";
		} elseif ($approval['approver_role'] == 'circle_lead' || $approval['approver_role'] == 'deputy_circle_lead') {
			$message = "
				<p><strong>{$recommenderName}</strong> and <strong>{$secondRecommenderName}</strong> has recommended <strong>{$recommendedName}</strong> to join Interfriends.</p>
				<p>Please find below the pertinent details of the recommended member:</p>
				{$memberDetails}
				<p>Please review this recommendation and choose an action below.</p>
			";
		} else {
			$message = "
				<p><strong>{$recommenderName}</strong> and <strong>{$secondRecommenderName}</strong> have recommended <strong>{$recommendedName}</strong> for consideration.</p>
				<p>Below is a summary of their details:</p>
				{$memberDetails}
				<p>Please review this recommendation and choose an action below.</p>
			";
		}

		$message .= "
			<p>
				<a style='background-color:#1bbe83; text-decoration:none; color:#fff;padding:10px 20px;border-radius:10px' href='{$linkApprove}'>Approve</a>
				<a style='background-color:#ff0000; text-decoration:none; color:#fff;padding:10px 20px;border-radius:10px' href='{$linkDecline}'>Decline</a>
			</p>
			<p><small>Reminder sent on {$reminderSentAt}</small></p>
		";

		$data['sendername'] = $approverName;
		$data['useremail'] = $approver['email'];
		$data['message'] = $message;

		$subject = "Reminder: Approval Request for Recommendation";
		$mailMessage = $this->load->view('template/common-mail', $data, true);
		$mail = $this->sendMail($approver['email'], $subject, $mailMessage);

		if (!$mail) {
			$this->response(false, "Unable to send reminder email. Please try again.");
			return;
		}

		$group_id = 0;
		if (!empty($recommendUser['refer_user_id'])) {
			$userCircle = $this->common->getData('user_circle', array('user_id' => $recommendUser['refer_user_id']), array('single'));
			if (!empty($userCircle['group_id'])) {
				$group_id = $userCircle['group_id'];
			}
		}

		$notificationMessage = "Reminder: approval is pending for recommended user {$recommendedName}.";

		if ($approval['approver_role'] == 'admin') {
			$this->common->query_normal("UPDATE superAdmin SET notification_count = notification_count+1 WHERE `id` = '" . $approval['approver_id'] . "'");
			$this->common->insertData('notification_admin_tbl', array(
				"message" => $notificationMessage,
				"user_id" => $approval['approver_id'],
				"group_id" => $group_id,
				"user_send_to" => $approval['approver_id'],
				"user_send_from" => $_REQUEST['admin_id'],
				"main_id" => $approval['recommend_id'],
				"created_at" => date('Y-m-d H:i:s'),
				"type" => "8",
				"user_type" => "1"
			));
		} else {
			$this->send_nofification($approval['approver_id'], $_REQUEST['admin_id'], $group_id, $notificationMessage, $approval['recommend_id'], "8");
		}

		$this->response(true, "Reminder sent successfully.");
	}

	// API created by @krishn on 18/06/26
	public function sendOutstandingPaymentReminder()
	{
		if (
			empty($_REQUEST['user_id']) ||
			empty($_REQUEST['type']) ||
			empty($_REQUEST['amount']) ||
			empty($_REQUEST['date'])
		) {

			$this->response(false, "Required fields are missing.");
			return;
		}

		$user_id = $_REQUEST['user_id'];
		$type = ucfirst(strtolower(trim($_REQUEST['type'])));
		$amount = $_REQUEST['amount'];
		$date = $_REQUEST['date'];

		$user = $this->common->getData(
			'user',
			['user_id' => $user_id],
			['single']
		);

		if (empty($user)) {
			$this->response(false, "User not found.");
			return;
		}

		$month = date('F Y', strtotime($date));

		$data['sendername'] = $user['first_name'] . ' ' . $user['last_name'];
		$data['useremail'] = '';

		$data['message'] = '

		<p>This is a reminder that you have an outstanding <strong>' . $type . '</strong> payment.</p>

		<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;margin-top:10px;">
			<tr>
				<td><strong>Payment Type</strong></td>
				<td>' . $type . '</td>
			</tr>
			<tr>
				<td><strong>Outstanding Amount</strong></td>
				<td>£' . number_format($amount, 2) . '</td>
			</tr>
			<tr>
				<td><strong>Overdue Month</strong></td>
				<td>' . $month . '</td>
			</tr>
		</table>

		<p>Please make the payment at your earliest convenience to avoid any disruption to your Interfriends benefits.</p>

		<p>If you have already made this payment, kindly ignore this email.</p>';

		$subject = "Outstanding Payment Reminder";
		$messaged = $this->load->view('template/common-mail', $data, true);

		$mail = $this->sendMail($user['email'], $subject, $messaged);

		if ($mail) {

			$message = "Outstanding payment reminder sent.";

			$this->send_nofification($user_id, 1, 0, $message, 0, "11");

			$this->response(true, "Reminder email sent successfully.");
		} else {

			$this->response(false, "Unable to send reminder email.");
		}
	}
}
