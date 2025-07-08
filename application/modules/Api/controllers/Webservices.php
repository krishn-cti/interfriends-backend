<?php
defined('BASEPATH') OR exit('No direct script access allowed');
date_default_timezone_set("Asia/Kolkata");
class Webservices extends Base_Controller {
	
	public function __construct()
	{
		parent:: __construct();
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
	
	public function signup() {	
		$exist = $this->common->getData('user',array('email' => $_REQUEST['email']),array('single'));

		$existPhone = $this->common->getData('user',array('phone_number' => $_REQUEST['phone_number']),array('single'));
	
		if(!empty($exist)) {
			$response = $this->response(false,"This Email Already Exists.",array("userinfo" => (object) array()));				
		} else if(!empty($existPhone)) {
			$response = $this->response(false,"This Phone Number Already Exists.",array("agentinfo" => (object) array()));
		} else {
			$iname = '';
			if(isset($_FILES['profile_image'])) {
				$image = $this->common->do_upload('profile_image','./assets/userfile/profile/');
				if(isset($image['upload_data'])) {
					$iname = 'assets/userfile/profile/'.$image['upload_data']['file_name'];
				}
			}

			

			$_REQUEST['profile_image'] = $iname;
			$_REQUEST['password'] = md5($_REQUEST['password']);
			$_REQUEST['created_at'] = date('Y-m-d H:i:s');
			$_REQUEST['act_token'] = $this->generateToken();

			if(!empty($_POST['categorys'])) {
				$categorys = json_decode($_REQUEST['categorys']);

				$categoryArray = array();
				foreach ($categorys as $key => $value) {
					$categoryArray[] = $value->item_id;
				}

				$_REQUEST['category'] = implode(",",$categoryArray);
				$_REQUEST['business_status'] = '1';
			} 
							
			
			$post = $this->common->getField('user',$_REQUEST); 
			$result = $this->common->insertData('user',$post);
			
			if($result) {
				$user_id = $this->db->insert_id();
				$info = get_user_details($user_id);	

				$message = $this->load->view('template/confirm-mail',$_REQUEST,true);
				$mail = $this->sendMail($_REQUEST['email'],'Activate Account',$message);

				if($mail) {
					$this->response(true,'Your Account Has Been Successfully Created. An Email Has Been Sent To You With Detailed Instructions On How To Activate It.');
				} else {
					$this->response(false,'Mail Not Delivered');
				}

			} else {
					$this->response(false,"There Is Some Problem.Please Try Again.",array("userinfo" => (object) array()));
			}
		}
	}


	public function otp_verification()
	{
		$where_exist = "country_code = '".$_REQUEST['country_code']."' AND phone_number = '". $_REQUEST['phone_number'] ."'";
		$result_exist = $this->common->getData('user',$where_exist);

		if(empty($result_exist))
		{
			$phone_number = $_REQUEST['country_code'].$_REQUEST['phone_number'];
			send_otp($phone_number); 
		
			$this->response(true,"Otp Sent Successfully");
		}
		else
		{
			$this->response(false,"Mobile Number Already Exist");
		}
		
	}

	public function check_verification()
	{
		$phone_number = $_REQUEST['country_code'].$_REQUEST['phone_number'];
		$res = verify_otp($_REQUEST['mobile_otp'],$phone_number);

		if($res['status']=='SUCCESSFUL') {
		        $this->response(true,'Successfully Verified');                    
		} else {
        	$this->response(false,'OTP Does Not Match');
        }
	}


	public function activate() {
		$user = $this->common->getData('user',array('act_token'=>$_GET['token']),array('single'));		
		if($user){
			$data = $this->common->updateData('user',array('act_token'=>""),array('act_token'=>$_GET['token']));
			
			redirect(ANG_URL.'?status=success');
		}else{ 			
			redirect(ANG_URL.'?status=info');		
		}
	}



	public function login() {				
		$_REQUEST['password'] = md5($_REQUEST['password']);
		$result = $this->common->getData('user',array('phone_number' => $_REQUEST['phone_number'],'country_code' => $_REQUEST['country_code'], 'password' => $_REQUEST['password']),array('single'));
	
			if($result){
				if($result['status'] == 2) {
					$this->response(false,'Your Account Blocked By Admin. Please Contact To support@irate.com');
		    		die;
				} else {

					$user_info = get_user_details($result['user_id']);
					
					$this->response(true,'Successfully Login',array("user_id" => $user_info["user_id"],"email" => $user_info["email"],"phone_number" => $user_info["phone_number"],"username" => $user_info["username"],"image" => $user_info['profile_image']));
				}					
		}else{
			$message = "Wrong Email Or Password";			
			$this->response(false,$message,array("userinfo" => (object) array()));
		}
	}



	public function logout() {
		if(!empty($_REQUEST['user_id'])) {	
			$user_id = $_REQUEST['user_id'];
			$this->common->updateData('user',array("web_token" => ""),array('user_id' => $user_id));
			$this->response(true,"Logout Successfully");
		} else {
			$this->response(false,"Missing Parameter.");
		}	
	}


	public function betaLogin() {				
		$result = $this->common->getData('user',array('user_id'=>$_POST['user_id']),array('single'));
	
		if($result) {
			$user_info = get_user_details($result['user_id']);
			
			$this->response(true,'Successfully Login',array("user_id" => $user_info["user_id"],"email" => $user_info["email"],"phone_number" => $user_info["phone_number"],"username" => $user_info["username"],"image" => $user_info['profile_image']));	
		} else {
			$message = "User Not Found";			
			$this->response(false,$message,array("userinfo" => (object) array()));
		}
	}


	public function updateProfile()
	{
		chmod('./assets/userfile/profile/',0777);
		
		$user_id = $_REQUEST['user_id']; unset($_REQUEST['user_id']);


		if(!empty($_POST['categorys'])) {
			$categorys = json_decode($_REQUEST['categorys']);

			$categoryArray = array();
			foreach ($categorys as $key => $value) {
				$categoryArray[] = $value->item_id;
			}

			$_REQUEST['category'] = implode(",",$categoryArray);
			$_REQUEST['business_status'] = '1';
		} 
		
		if(!empty($_FILES['image']))
		{
			$image = $this->common->do_upload('image','./assets/userfile/profile/');
			$_REQUEST['profile_image'] = 'assets/userfile/profile/'.$image['upload_data']['file_name'];
		}



		if(!empty($_FILES['company_image']))
		{
			$image = $this->common->do_upload('company_image','./assets/userfile/profile/');
			$_REQUEST['company_profile_image'] = 'assets/userfile/profile/'.$image['upload_data']['file_name'];
		}



		$post = $this->common->getField('user',$_REQUEST);
		
		if(!empty($post))
		{		
			$result = $this->common->updateData('user',$post,array('user_id' => $user_id));
		}
		else
		{
			$result = "";
		}
		
		if($result)
		{
			$info = get_user_details($user_id);
			$this->response(true,"Profile Update Successfully",array("userinfo" => $info));
		}
		else
		{
			$this->response(false,"There Is Some Problem.Please Try Again.",array("userinfo" =>(object) array()));
		}
	}



	public function getProfile()
	{
		$user_id = $_REQUEST['user_id'];
		$userinfo = get_user_details($user_id);

		if(!empty($userinfo))
		{	
			if(!empty($userinfo['category'])) {
				$category = $this->category_data($userinfo['category']);
			} else {
				$category = array();
			}

			$this->response(true,"Profile Fetch Successfully.",array("userinfo" => $userinfo,"categoryList" => $category));
		}
		else
		{
			$this->response(false,"There Is Some Problem.Please Try Again.",array("userinfo" => array()));
		}			
	}


	function category_data($category) {
		$arr_category=(explode(",",$category));
		$category_string = implode("','", $arr_category);
		$where_category = "`category_id` IN ('".$category_string."') AND status = 1";
		$result_category = $this->common->getData('category',$where_category,array('field'=>'category_id as item_id,category_name as item_text'));
        
        if(!empty($result_category))
        { 	
			return $result_category;			
		}else{
			return array();	
		}
	}


	public function change_password()
	{
		if(!empty($_REQUEST['user_id']) && !empty($_REQUEST['old_password']) && !empty($_REQUEST['new_password']))
		{
			$user_id = $_REQUEST['user_id'];
			$old_password = $_REQUEST['old_password'];
			$new_password = $_REQUEST['new_password'];
			$user_info = $this->common->getData('user',array('user_id' => $user_id),array('single'));
			$old_user_password = $user_info['password'];
			$old_password = md5($old_password);
			if ($old_password == $old_user_password) 
			{
				$data['password'] = md5($new_password);
				$result = $this->common->updateData('user',$data,array('user_id' => $user_id));
				$this->response(true,'Password Changed Successfully');
			} 
			else 
			{
				$this->response(false,'Invalid Old Password');
				exit();
			}
		}
		else
		{
			$this->response(false,'Missing Parameter');
		}
	}
	



	public function generateToken() {
		$seed = str_split('abcdefghijklmnopqrstuvwxyz'
                 .'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                 .'0123456789'); // and any other characters
		shuffle($seed); // probably optional since array_is randomized;
		$rand = '';
		foreach (array_rand($seed, 8) as $k){
			$rand .= $seed[$k];	
		} 
		return md5(microtime().$rand);
	}


	public function forgetPassword()
	{
 			$data = $this->common->getData('user',array('email'=>$_POST['email']),array('single'));
 			if(!empty($data)){
				$token = $this->generateToken();
				$data['token'] = $data['user_id'].$token;
				$this->common->updateData('user',array('token' => $data['token']),array('user_id'=> $data['user_id']));
				$message = $this->load->view('template/reset-mail',$data,true);
				
				////////////////////////////////////////

				$mail = $this->sendMail($_POST['email'],'Forgot Password',$message);

				if($mail) {
					$this->response(true,"Thank You, You Will Receive An E-mail In The Next 5 Minutes With Instructions For Resetting Your Password. If You Don't Receive This E-mail, Please Check Your Junk Mail Folder Or Contact Us For Further Assistance.");
				} else {
					$this->response(false,"Mail Not Delivered");
				}
 				 
			}else{
				$this->response(false,'Email Not Registered');
			}		 				
	}


	function sendMail($email, $subject, $message) {
		require_once(APPPATH .'third_party/phpmailer/class.phpmailer.php');
		require_once(APPPATH .'third_party/phpmailer/class.smtp.php');
						
		$mail = new PHPMailer();
	
		$mail->IsSMTP();
		$mail->CharSet = 'UTF-8';
		$mail->Host = "smtp.gmail.com";

		$mail->SMTPAuth= true;
		$mail->Port = 587; // Or 587
		$mail->Username= 'akashbaidya442@gmail.com';
		$mail->Password= 'gvosukywgcnxxizc';
		$mail->SMTPSecure = "tls";
		//$mail->SMTPDebug  = 1;
		$mail->setFrom("akashbaidya442@gmail.com", 'IRate Pro App');
		$mail->Body = $message;

		$mail->isHTML(true);
		$mail->Subject = $subject;

	    $mail->addAddress($email);
	    $send =  $mail->send();


		if ($send != '1') {
			return false;
		} else {
			return true;
		}
	}


	public function resetForgetPassword() {	
		$_POST['password'] = md5($_REQUEST['password']);
		$update = $this->common->updateData11('user',array('token'=> "",'password'=>$_POST['password']),array('token'=>$_REQUEST['token']));
		if($update){
			$this->response(true,'Password Changed Successfully');
		}else{
			$this->response(false,'Link expired. Please Reset Password Again');
		}	
	}

	public function reset_passowrd() {
		if(!empty($_REQUEST['phone_number']) && !empty($_REQUEST['country_code']) && !empty($_REQUEST['password'])) {
			$data['password'] = md5($_REQUEST['password']);
				$result = $this->common->updateData('user',$data,array('phone_number' => $_REQUEST['phone_number'],'country_code' => $_REQUEST['country_code']));
				$this->response(true,'Password Changed Successfully');
		} else {
			$this->response(false,'Missing Parameter');
		}
	}




	public function category_list() {
		$where = "status = 1";
        $result = $this->common->getData('category',$where);
        
        if(!empty($result))
        { 
        	foreach ($result as $key => $value) {
        		if(!empty($value['category_image'])) {
        			 $result[$key]['category_image'] = base_url($value['category_image']);
        			 $result[$key]['category_image_thumb'] = base_url($value['category_image_thumb']);
        		}
        	}
			$this->response(true,"Category Fetch Successfully.",array("category_list" => $result));			
		}else{
			$this->response(false,"Category Not Found",array("category_list" => array()));
		}

	}


	public function category_list_dropdowm() {
		$where = "status = 1";
        $result = $this->common->getData('category',$where,array('field'=>'category_id as item_id,category_name as item_text'));
        
        if(!empty($result))
        { 
        	foreach ($result as $key => $value) {
        		if(!empty($value['category_image'])) {
        			 $result[$key]['category_image'] = base_url($value['category_image']);
        			 $result[$key]['category_image_thumb'] = base_url($value['category_image_thumb']);
        		}
        	}
			$this->response(true,"Category Fetch Successfully.",array("category_list" => $result));			
		}else{
			$this->response(false,"Category Not Found",array("category_list" => array()));
		}

	}


	public function category_detail() {
        $result = $this->common->getData('category',array('category_id' => $_REQUEST['category_id']), array('single'));
        
        if(!empty($result)) {
        	if(!empty($result['category_image'])) {
        			 $result['category_image'] = base_url($result['category_image']);
        			 $result['category_image_thumb'] = base_url($result['category_image_thumb']);
        	}
			$this->response(true,"Category Fetch Successfully.",array("category_list" => $result));	
		} else {
			$this->response(false,"Category Not Found",array("category_list" => array()));
		}

	}


	public function addCategory()
	{	
		$iname = '';
		$iname_thumb = '';
		if(isset($_FILES['category_image'])) {
			$image = $this->common->do_upload_thumb('category_image','./assets/category/');
			if(isset($image['upload_data'])) {
				$iname = 'assets/category/'.$image['upload_data']['file_name'];
				$iname_thumb = 'assets/category/thumb/'.$image['upload_data']['file_name'];
			}
		}

		$_REQUEST['category_image'] = $iname;
		$_REQUEST['category_image_thumb'] = $iname_thumb;
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
						
		$post = $this->common->getField('category',$_REQUEST); 
		$result = $this->common->insertData('category',$post);
		
		if($result) {
			$this->response(true,"Add Category Successfully");					
		} else {
			$this->response(false,"There Is Some Problem.Please Try Again.");
		}
			
	}

	public function editCategory() {
		chmod('./assets/category/',0777);
		
		$category_id = $_REQUEST['category_id']; unset($_REQUEST['category_id']);
		
		if(isset($_FILES['category_image'])) {
			$image = $this->common->do_upload_thumb('category_image','./assets/category/');
			if(isset($image['upload_data'])) {
				$iname = 'assets/category/'.$image['upload_data']['file_name'];
				$iname_thumb = 'assets/category/thumb/'.$image['upload_data']['file_name'];
				$_REQUEST['category_image'] = $iname;
				$_REQUEST['category_image_thumb'] = $iname_thumb;
			}
		}

		$post = $this->common->getField('category',$_REQUEST);
		
		if(!empty($post)) {		
			$result = $this->common->updateData('category',$post,array('category_id' => $category_id));
		} else {
			$result = "";
		}
		
		if($result){
			$this->response(true,"Category Update Successfully");
		} else {
			$this->response(false,"There Is Some Problem.Please Try Again.");
		}
	}



	public function purchasePaidApi() {
		$userInfo = $this->common->getData('user',array('user_id'=>$_REQUEST['user_id']),array('single'));

		// plan info
		$planResult = $this->common->getData('paid_api_plan',array('id'=>$_REQUEST['plan_id']),array('single'));

		$this->load->helper('stripe');
		AddStripetest();
		$finalAmount = $planResult['price'];
		$token = $_POST['stripeToken'];
		$charge = \Stripe\Charge::create([
		    'amount' => $finalAmount*100,
		    'currency' => 'usd',
		    'description' => $userInfo['username'].'BY User:'.$userInfo['email'],
		    'source' => $token, 
		    'receipt_email'=>$userInfo['email'],
		]);

		if($charge->status==='succeeded') {

			$chrList = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$randomValue = substr(str_shuffle(str_repeat($chrList, mt_rand(1,20))), 1, 20);
			$token = 'sk_test_'.$_REQUEST['user_id'].'_'.$randomValue;
		
			$time_diff = "+".$planResult['days']." day";
			$expiry_date = date("Y-m-d", strtotime($time_diff));
			$data_update['paid_api_expiry_date'] =  $expiry_date;
			$data_update['paid_api_token'] =  	$token;
			$result = $this->common->updateData('user',$data_update,array('user_id' => $_REQUEST['user_id'])); 

			// data insert in payment history table	
			// $data_pay['user_id'] = 	$_REQUEST['user_id'];
			// $data_pay['plan_id'] = $_REQUEST['plan_id'];
			// $data_pay['title'] = $planResult['title'];
			// $data_pay['description'] = $planResult['description'];
			// $data_pay['price'] = $planResult['price'];
			// $data_pay['days'] = $planResult['days'];
			// $data_pay['type'] = $planResult['plan_type'];
			// $data_pay['status'] = 'paid';
			// $data_pay['created_at'] = Date('Y-m-d H:i:s');
			// $result = $this->common->insertData('payment_history',$data_pay);
			
			$this->response(true,"Subscription Added Successfully",array('finalAmount'=> $finalAmount));
		} else {
			$this->response(false,'Plan Purchase Failed'); 
		}
	}









	public function product_list() {
		// limit code start
		if(empty($_REQUEST['start'])) {
				$start = 10;
				$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end
        $result = $this->user_model->product_detail(array('P.status' => '1','P.user_id' => $_REQUEST['user_id']),array(),$start,$end);
        $productCount = $this->common->getData('product',"",array('count'));

        if(!empty($result))
        { 
        	foreach ($result as $key => $value) {
        		if(!empty($value['product_image'])) {
        			 $result[$key]['product_image'] = base_url($value['product_image']);
        			 $result[$key]['product_image_thumb'] = base_url($value['product_image_thumb']);
        		} else {
        			$result[$key]['product_image'] = "assets/img/default-user-icon.jpg";
        			$result[$key]['product_image_thumb'] = "assets/img/default-user-icon.jpg";
        		}

        		if(!empty($value['category_image'])) {
        			  $result[$key]['category_image'] = base_url($value['category_image']);
        			  $result[$key]['category_image_thumb'] = base_url($value['category_image_thumb']);
        		}
        	}
			$this->response(true,"Product Fetch Successfully.",array("productList" => $result,"productCount" => $productCount));			
		}else{
			$this->response(true,"Product Fetch Successfully.",array("productList" => array(),"productCount" => $productCount));
		}

	}




	public function user_list() {
		// limit code start
		if(empty($_REQUEST['start'])) {
				$start = 10;
				$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "U.status = 1 AND U.user_id != '". $_REQUEST['user_id'] ."' AND business_status=1";

		if(!empty($_REQUEST['category_id'])) {
        	$where.= 	" AND FIND_IN_SET('". $_REQUEST['category_id'] ."',U.category)";
        }

         if(!empty($_REQUEST['search_keyword'])) {
			$where.= " AND  U.company_name LIKE '%". $_REQUEST['search_keyword'] ."%'";
		}


        $result = $this->user_model->user_detail($where,array(),$start,$end);

        $userCount = $this->common->getData('user',"",array('count'));

        if(!empty($result))
        { 
        	foreach ($result as $key => $value) {
        		if(!empty($value['profile_image'])) {
					$result[$key]['profile_image'] = base_url($value['profile_image']);
				} else {
					$result[$key]['profile_image'] = "assets/img/default-user-icon.jpg";
				}


				if(!empty($value['company_profile_image'])) {
					$result[$key]['company_profile_image'] = base_url($value['company_profile_image']);
				} else {
					$result[$key]['company_profile_image'] = "assets/img/default-user-icon.jpg";
				}
        	}
			$this->response(true,"User Fetch Successfully.",array("userList" => $result,"userCount" => $userCount));			
		}else{
			$this->response(true,"User Fetch Successfully.",array("userList" => array(),"userCount" => $userCount));
		}

	}


	public function customer_list() {
		// limit code start
		if(empty($_REQUEST['start'])) {
				$start = 10;
				$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "U.status = 1 AND U.user_id != '". $_REQUEST['user_id'] ."'";

		if(!empty($_REQUEST['category_id'])) {
        	$where.= 	" AND FIND_IN_SET('". $_REQUEST['category_id'] ."',U.category)";
        }

         if(!empty($_REQUEST['search_keyword'])) {
			$where.= " AND  U.username LIKE '%". $_REQUEST['search_keyword'] ."%'";
		}


        $result = $this->user_model->user_detail($where,array(),$start,$end);

        $userCount = $this->common->getData('user',"",array('count'));

        if(!empty($result))
        { 
        	foreach ($result as $key => $value) {
        		if(!empty($value['profile_image'])) {
					$result[$key]['profile_image'] = base_url($value['profile_image']);
				} else {
					$result[$key]['profile_image'] = "assets/img/default-user-icon.jpg";
				}
        	}
			$this->response(true,"User Fetch Successfully.",array("userList" => $result,"userCount" => $userCount));			
		}else{
			$this->response(true,"User Fetch Successfully.",array("userList" => array(),"userCount" => $userCount));
		}

	}



	public function product_all_list() {
		// limit code start
		if(empty($_REQUEST['start'])) {
				$start = 10;
				$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		$where = "P.status = 1 AND P.user_id != '". $_REQUEST['user_id'] ."'";

		if(!empty($_REQUEST['category_id'])) {
        	$where.= " AND P.category = '". $_REQUEST['category_id'] ."'";
        }

         if(!empty($_REQUEST['search_keyword'])) {
			$where.= " AND  P.product_name LIKE '%". $_REQUEST['search_keyword'] ."%'";
		}

        $result = $this->user_model->product_detail($where,array(),$start,$end);

        if(!empty($result))
        { 
        	foreach ($result as $key => $value) {
        		if(!empty($value['product_image'])) {
        			 $result[$key]['product_image'] = base_url($value['product_image']);
        			 $result[$key]['product_image_thumb'] = base_url($value['product_image_thumb']);
        		} else {
        			$result[$key]['product_image'] = "assets/img/default-user-icon.jpg";
        			$result[$key]['product_image_thumb'] = "assets/img/default-user-icon.jpg";
        		}
        	}
			$this->response(true,"User Fetch Successfully.",array("productList" => $result));			
		}else{
			$this->response(true,"User Fetch Successfully.",array("productList" => array()));
		}

	}


	public function userDetail()
	{
		$user_id = $_REQUEST['user_id'];
		$userinfo = get_user_details($user_id);

		if(!empty($userinfo)) {	
			$this->response(true,"Profile Fetch Successfully.",array("userinfo" => $userinfo));
		} else {
			$this->response(false,"There Is Some Problem.Please Try Again.",array("userinfo" => array()));
		}			
	}



	public function customerDetail()
	{
		$user_id = $_REQUEST['user_id'];
		$userinfo = get_user_details($user_id);

		if(!empty($userinfo)) {	
			$this->response(true,"Profile Fetch Successfully.",array("userinfo" => $userinfo));
		} else {
			$this->response(false,"There Is Some Problem.Please Try Again.",array("userinfo" => array()));
		}			
	}


	public function product_detail() {
        $result = $this->user_model->product_detail(array('P.product_id' => $_REQUEST['product_id']), array('single'));
        
        if(!empty($result)) {
        	if(!empty($result['product_image'])) {
        			 $result['product_image'] = base_url($result['product_image']);
        			 $result['product_image_thumb'] = base_url($result['product_image_thumb']);
        	}

        	if(!empty($result['category_image'])) {
        			 $result['category_image'] = base_url($result['category_image']);
        			 $result['category_image_thumb'] = base_url($result['category_image_thumb']);
        	}
			$this->response(true,"Product Fetch Successfully.",array("info" => $result));	
		} else {
			$this->response(false,"Product Not Found",array("info" => array()));
		}

	}



	public function paid_api_detail() {
        $result = $this->common->getData('paid_api_plan',array('id'=>'1'),array('single'));
        
        if(!empty($result)) {
			$this->response(true,"Plan Fetch Successfully.",array("info" => $result));	
		} else {
			$this->response(false,"Plan Not Found",array("info" => array()));
		}

	}


	public function addProduct()
	{	
		$iname = '';
		$iname_thumb = '';
		if(isset($_FILES['image'])) {
			$image = $this->common->do_upload_thumb('image','./assets/product/');
			if(isset($image['upload_data'])) {
				$iname = 'assets/product/'.$image['upload_data']['file_name'];
				$iname_thumb = 'assets/product/thumb/'.$image['upload_data']['file_name'];
			}
		}

		$_REQUEST['product_image'] = $iname;
		$_REQUEST['product_image_thumb'] = $iname_thumb;
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
						
		$post = $this->common->getField('product',$_REQUEST); 
		$result = $this->common->insertData('product',$post);
		
		if($result) {
			$this->response(true,"Add Product Successfully");					
		} else {
			$this->response(false,"There Is Some Problem.Please Try Again.");
		}
			
	}



	public function ticketList()
	{	
		$pendingList = $this->user_model->ticket_detail(array('T.user_id' => $_REQUEST['user_id'], 'T.status' => 0));

		$completeList = $this->user_model->ticket_detail(array('T.user_id' => $_REQUEST['user_id'], 'T.status' => 1));

		$this->response(true,"Ticket Fetch Successfully", array('pendingList' => $pendingList, 'completeList' => $completeList));	
			
	}



	public function ticketDetail() {	
		$ticketDetail = $this->user_model->ticket_detail(array('T.id' => $_REQUEST['id']), array('single'));

		if(!empty($ticketDetail)) {
			$result = $this->user_model->rateing_detail(array('R.id' => $ticketDetail['rate_id']),array('single'));

			$ratingDetail = array();
	 		if(!empty($result)) {
	 			if(!empty($result['rate_image'])) {
					$result['rate_image'] = base_url($result['rate_image']);
					$result['rate_image_thumb'] = base_url($result['rate_image_thumb']);
				} else {
					$result['rate_image'] = "assets/img/default-user-icon.jpg";
					$result['rate_image_thumb'] = "assets/img/default-user-icon.jpg";
				}


				if(!empty($result['product_image'])) {
					$result['product_image'] = base_url($result['product_image']);
					$result['product_image_thumb'] = base_url($result['product_image_thumb']);
		    	} else {
		    		$result['product_image'] = "assets/img/default-user-icon.jpg";
					$result['product_image_thumb'] = "assets/img/default-user-icon.jpg";
		    	}


		    	if(!empty($result['from_profile_image'])) {
					$result['from_profile_image'] = base_url($result['from_profile_image']);
				} else {
					$result['from_profile_image'] = "assets/img/default-user-icon.jpg";
				}

				if(!empty($result['to_profile_image'])) {
					$result['to_profile_image'] = base_url($result['to_profile_image']);
				} else {
					$result['to_profile_image'] = "assets/img/default-user-icon.jpg";
				}

		    	if(!empty($result['company_profile_image'])) {
					$result['company_profile_image'] = base_url($result['company_profile_image']);
				} else {
					$result['company_profile_image'] = "assets/img/default-user-icon.jpg";
				}

				$ratingDetail = $result;
	 		}

	 		$this->response(true,"Ticket Detail Fetch Successfully", array('ticketDetail' => $ticketDetail,'ratingDetail' => $ratingDetail));

	 	} else {
	 		$this->response(false,"Ticket Detail Not Found", array('ticketDetail' => array(),'ratingDetail' => array()));
	 	}	
	}


	public function addTicket() {	
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
						
		$post = $this->common->getField('ticket',$_REQUEST); 
		$result = $this->common->insertData('ticket',$post);
		
		if($result) {
			$this->response(true,"Add Ticket successfully");					
		} else {
			$this->response(false,"There Is Some Problem.Please Try Again.");
		}
	}



	public function addTicketComment() {	
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
						
		$post = $this->common->getField('ticket_comments',$_REQUEST); 
		$result = $this->common->insertData('ticket_comments',$post);
		
		if($result) {
			$this->response(true,"Add Ticket Comment Successfully");					
		} else {
			$this->response(false,"There Is Some Problem.Please Try Again.");
		}
	}

	public function getComments() {
		$commentList = $this->common->getData('ticket_comments',array('ticket_id'=>$_REQUEST['ticket_id']));
		if(!empty($commentList)) {
			$this->response(true,"Comments Fetch Successfully", array('commentList' => $commentList));
		} else {
			$this->response(true,"Data Not Found", array('commentList' => array()));
		}	
	}




	public function editProduct() {
		chmod('./assets/product/',0777);
		
		$product_id = $_REQUEST['product_id']; unset($_REQUEST['product_id']);
		
		if(isset($_FILES['image'])) {
			$image = $this->common->do_upload_thumb('image','./assets/product/');
			if(isset($image['upload_data'])) {
				$iname = 'assets/product/'.$image['upload_data']['file_name'];
				$iname_thumb = 'assets/product/thumb/'.$image['upload_data']['file_name'];
				$_REQUEST['product_image'] = $iname;
				$_REQUEST['product_image_thumb'] = $iname_thumb;
			}
		}

		$post = $this->common->getField('product',$_REQUEST);
		
		if(!empty($post)) {		
			$result = $this->common->updateData('product',$post,array('product_id' => $product_id));
		} else {
			$result = "";
		}
		
		if($result){
			$this->response(true,"Product Update Successfully");
		} else {
			$this->response(false,"There Is Some Problem.Please Try Again.");
		}
	}



	public function addCompanyRating() {	
		$iname = '';
		$iname_thumb = '';
		if(isset($_FILES['image'])) {
			$image = $this->common->do_upload_thumb('image','./assets/rating/');
			if(isset($image['upload_data'])) {
				$iname = 'assets/rating/'.$image['upload_data']['file_name'];
				$iname_thumb = 'assets/rating/thumb/'.$image['upload_data']['file_name'];
			}
		}

		$_REQUEST['rate_image'] = $iname;
		$_REQUEST['rate_image_thumb'] = $iname_thumb;
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$_REQUEST['type'] = '1';
						
		$post = $this->common->getField('rating',$_REQUEST); 
		$result = $this->common->insertData('rating',$post);
		$rating_id = $this->db->insert_id();


		// notification start
		$result = $this->common->insertData('notification_tbl',array('user_send_from' => $_REQUEST['user_id'], 'user_send_to' => $_REQUEST['to_user'], 'company_id' => $_REQUEST['company_id'],'created_at'=> $_REQUEST['created_at'],'message'=> 'Give Review Your Company','type'=> '1','user_type'=> $_REQUEST['user_type'],'rating_id'=> $rating_id));

		$this->notification_count($_REQUEST['to_user']);
		$this->send_nofification_company($_REQUEST['to_user'], $_REQUEST['user_id'], $_REQUEST['user_type'], $rating_id);

		// notification end
		
		$rating_res = $this->rating_count($_REQUEST['company_id']);
		$this->common->updateData('user',array('avg_rating' => $rating_res['avg'], 'rating_count' => $rating_res['count']),array('user_id' =>$_REQUEST['company_id']));
		if($result) {
			$this->response(true,"Add Company Rating Successfully");					
		} else {
			$this->response(false,"There Is Some Problem.Please Try Again.");
		}		
	}




	function send_nofification_company($to_user_id, $from_user_id, $type, $rating_id) {
		$userDetailTo = $this->common->getData('user',array('user_id'=>$to_user_id),array('single'));

		$userDetailFrom = $this->common->getData('user',array('user_id'=>$from_user_id),array('single'));


		
		$userName = '';
		if($type == '1') {
			$userName = $userDetailFrom['username'];
		} else {
			$userName = $userDetailFrom['company_name'];
		}


		$msg_notification = array(
						        "body" => $userName.' Give Review Your Company',
						        "title"=>'Company Review',
						        "icon"=>'https://ctinfotech.com/iratepro/assets/img/fav.png'
					        );

		// for web		
		if(!empty($userDetailTo['web_token'])) {
			$data = array(
			        "to_user_id"=> $to_user_id,
					"from_user_id"=>$from_user_id,
					"rating_id"=> (string)$rating_id,
					'type' => '1'
				    );
	        $res = $this->send_notification_web($userDetailTo['web_token'], $msg_notification, $data);
        }


        // for ios
        if($userDetailTo['device_type'] == "2") {
        	if(!empty($userDetailTo['fcm_token'])) {
	        	$data = array(
				        "to_user_id"=> $to_user_id,
						"from_user_id"=>$from_user_id,
						"rating_id"=> (string)$rating_id,
						'type' => '1'
					    );

	       		$registatoin_id = array($userDetailTo['fcm_token']);
	        	$res = $this->send_notification_ios($registatoin_id,$msg_notification,$data); 
	        }
        }

		// for android
		// if($userDetailFrom['device_type'] == "1") {
		// 	if(!empty($userDetailFrom['fcm_token'])) {
		// 		$messages_push =  array(
		//         	"notification" => $msg_notification,
		//         	"notification_type" => $type,
		//         	"to_user_id"=>$to_user_id,
		//         	"from_user_id"=>$from_user_id
		//         );
		//         $registatoin_id = array($userDetailFrom['fcm_token']);
		//         $res = $this->send_notification($registatoin_id, $messages_push); 
		// 	}
  //       }
        
       
	}






	public function addCustomerRating() {	
		$iname = '';
		$iname_thumb = '';
		if(isset($_FILES['image'])) {
			$image = $this->common->do_upload_thumb('image','./assets/rating/');
			if(isset($image['upload_data'])) {
				$iname = 'assets/rating/'.$image['upload_data']['file_name'];
				$iname_thumb = 'assets/rating/thumb/'.$image['upload_data']['file_name'];
			}
		}

		$_REQUEST['rate_image'] = $iname;
		$_REQUEST['rate_image_thumb'] = $iname_thumb;
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$_REQUEST['type'] = '3';
						
		$post = $this->common->getField('rating',$_REQUEST); 
		$result = $this->common->insertData('rating',$post);
		$rating_id = $this->db->insert_id();


		// notification start
		$result = $this->common->insertData('notification_tbl',array('user_send_from' => $_REQUEST['user_id'], 'user_send_to' => $_REQUEST['to_user'], 'customer_id' => $_REQUEST['customer_id'],'created_at'=> $_REQUEST['created_at'],'message'=> 'Give Review Your Customer','type'=> '3','user_type'=> $_REQUEST['user_type'],'rating_id'=> $rating_id));

		$this->notification_count($_REQUEST['to_user']);
		$this->send_nofification_customer($_REQUEST['to_user'], $_REQUEST['user_id'], $_REQUEST['user_type'], $rating_id);

		// notification end
		
		$rating_res = $this->rating_customer_count($_REQUEST['customer_id']);
		$this->common->updateData('user',array('user_avg_rating' => $rating_res['avg'], 'user_rating_count' => $rating_res['count']),array('user_id' =>$_REQUEST['customer_id']));
		if($result) {
			$this->response(true,"Add Customer Rating Successfully");					
		} else {
			$this->response(false,"There Is Some Problem.Please Try Again.");
		}		
	}



	function send_nofification_customer($to_user_id, $from_user_id, $type, $rating_id) {
		$userDetailTo = $this->common->getData('user',array('user_id'=>$to_user_id),array('single'));
		$userDetailFrom = $this->common->getData('user',array('user_id'=>$from_user_id),array('single'));
		

		$userName = '';
		if($type == '1') {
			$userName = $userDetailFrom['username'];
		} else {
			$userName = $userDetailFrom['company_name'];
		}


		$msg_notification = array(
						        "body" => $userName.' Give Review Your Customer',
						        "title"=>'Customer Review',
						        "icon"=>'https://ctinfotech.com/iratepro/assets/img/fav.png'
					        );

		// for web		
		if(!empty($userDetailTo['web_token'])) {
			$data = array(
			        "to_user_id"=> $to_user_id,
					"from_user_id"=>$from_user_id,
					"rating_id"=> (string)$rating_id,
					'type' => '3'
				    );
	        $res = $this->send_notification_web($userDetailTo['web_token'], $msg_notification, $data);
        }

        // for ios
        if($userDetailTo['device_type'] == "2") {
        	if(!empty($userDetailTo['fcm_token'])) {
	        	$data = array(
				        "to_user_id"=> $to_user_id,
						"from_user_id"=>$from_user_id,
						"rating_id"=> (string)$rating_id,
						'type' => '3'
					    );

	       		$registatoin_id = array($userDetailTo['fcm_token']);
	        	$res = $this->send_notification_ios($registatoin_id,$msg_notification,$data); 
	        }
        }

		// for android
		// if($userDetailFrom['device_type'] == "1") {
		// 	if(!empty($userDetailFrom['fcm_token'])) {
		// 		$messages_push =  array(
		//         	"notification" => $msg_notification,
		//         	"notification_type" => $type,
		//         	"to_user_id"=>$to_user_id,
		//         	"from_user_id"=>$from_user_id
		//         );
		//         $registatoin_id = array($userDetailFrom['fcm_token']);
		//         $res = $this->send_notification($registatoin_id, $messages_push); 
		// 	}
  //       }
        
 
	}



	function rating_customer_count($customer_id) {
		$count_customer = $this->common->getData('rating',array('customer_id'=>$customer_id),array('count'));
		if($count_customer) {
			$query="SELECT SUM(`rate`) AS rating_count FROM rating  WHERE customer_id='".$customer_id."'";
			$total_rating = $this->common->query($query,array('single'));
			if(!empty($total_rating)) {
				$total_rating_customer = $total_rating['rating_count'];
				$avg=$total_rating_customer/$count_customer;
			} else {
				$avg = 0;
			}	
		} else {
			$avg = 0;
		}
		
		return  array('avg' => $avg, 'count' => $count_customer);
	}


	function notification_count($user_id) {
		$user_batch_count = $this->common->getData('user',array('user_id'=>$user_id),array('single'));
		$notification_count_no = $user_batch_count['notification_count']+1;
		$data_batch['notification_count']=$notification_count_no;
		$result = $this->common->updateData('user',$data_batch,array('user_id' => $user_id));
	}


	function rating_count($company_id) {
		$where = "company_id = '". $company_id ."' AND 	type != 3";
		$count_company = $this->common->getData('rating',$where,array('count'));
		if($count_company) {
			$query="SELECT SUM(`rate`) AS rating_count FROM rating  WHERE company_id='".$company_id."' AND 	type != 3";
			$total_rating = $this->common->query($query,array('single'));
			if(!empty($total_rating)) {
				$total_rating_company = $total_rating['rating_count'];
				$avg=$total_rating_company/$count_company;
			} else {
				$avg = 0;
			}	
		} else {
			$avg = 0;
		}
		
		return  array('avg' => $avg, 'count' => $count_company);
	}	


	function rating_product_count($product_id) {
		$count_product = $this->common->getData('rating',array('product_id'=>$product_id),array('count'));
		if($count_product) {
			$query="SELECT SUM(`rate`) AS rating_count FROM rating  WHERE product_id='".$product_id."'";
			$total_rating = $this->common->query($query,array('single'));
			if(!empty($total_rating)) {
				$total_rating_product = $total_rating['rating_count'];
				$avg=$total_rating_product/$count_product;
			} else {
				$avg = 0;
			}	
		} else {
			$avg = 0;
		}
		
		return  array('avg' => $avg, 'count' => $count_product);
	}



	public function addProductRating() {	
		$iname = '';
		$iname_thumb = '';
		if(isset($_FILES['image'])) {
			$image = $this->common->do_upload_thumb('image','./assets/rating/');
			if(isset($image['upload_data'])) {
				$iname = 'assets/rating/'.$image['upload_data']['file_name'];
				$iname_thumb = 'assets/rating/thumb/'.$image['upload_data']['file_name'];
			}
		}

		$_REQUEST['rate_image'] = $iname;
		$_REQUEST['rate_image_thumb'] = $iname_thumb;
		$_REQUEST['created_at'] = date('Y-m-d H:i:s');
		$_REQUEST['type'] = '2';
						
		$post = $this->common->getField('rating',$_REQUEST); 
		$result = $this->common->insertData('rating',$post);
		$rating_id = $this->db->insert_id();

		// notification start
		$result = $this->common->insertData('notification_tbl',array('user_send_from' => $_REQUEST['user_id'], 'user_send_to' => $_REQUEST['to_user'], 'product_id' => $_REQUEST['product_id'],'created_at'=> $_REQUEST['created_at'],'message'=> 'Give Review Your Product','type'=> '2','user_type'=> $_REQUEST['user_type'],'rating_id'=> $rating_id));

		$this->notification_count($_REQUEST['to_user']);
		$this->send_nofification_product($_REQUEST['to_user'], $_REQUEST['user_id'], $_REQUEST['user_type'], $rating_id);

		// notification end

		$rating_res = $this->rating_product_count($_REQUEST['product_id']);
		$this->common->updateData('product',array('avg_rating' => $rating_res['avg'], 'rating_count' => $rating_res['count']),array('product_id' =>$_REQUEST['product_id']));

		if($result) {
			$this->response(true,"Add Product Rating Successfully");					
		} else {
			$this->response(false,"There Is Some Problem.Please Try Again.");
		}		
	}




	function send_nofification_product($to_user_id, $from_user_id, $type, $rating_id) {
		$userDetailTo = $this->common->getData('user',array('user_id'=>$to_user_id),array('single'));
		$userDetailFrom = $this->common->getData('user',array('user_id'=>$from_user_id),array('single'));
		
		$userName = '';
		if($type == '1') {
			$userName = $userDetailFrom['username'];
		} else {
			$userName = $userDetailFrom['company_name'];
		}


		$msg_notification = array(
						        "body" => $userName.' Give Review Your Product',
						        "title"=>'Product review',
						        "icon"=>'https://ctinfotech.com/iratepro/assets/img/fav.png'
					        );

		// for web		
		if(!empty($userDetailTo['web_token'])) {
			$data = array(
			        "to_user_id"=> $to_user_id,
					"from_user_id"=>$from_user_id,
					"rating_id"=> (string)$rating_id,
					'type' => '2'
				    );
	        $res = $this->send_notification_web($userDetailTo['web_token'], $msg_notification, $data);
        }


        // for ios
        if($userDetailTo['device_type'] == "2") {
        	if(!empty($userDetailTo['fcm_token'])) {
	        	$data = array(
				        "to_user_id"=> $to_user_id,
						"from_user_id"=>$from_user_id,
						"rating_id"=> (string)$rating_id,
						'type' => '2'
					    );

	       		$registatoin_id = array($userDetailTo['fcm_token']);
	        	$res = $this->send_notification_ios($registatoin_id,$msg_notification,$data); 
	        }
        }

		// for android
		// if($userDetailFrom['device_type'] == "1") {
		// 	if(!empty($userDetailFrom['fcm_token'])) {
		// 		$messages_push =  array(
		//         	"notification" => $msg_notification,
		//         	"notification_type" => $type,
		//         	"to_user_id"=>$to_user_id,
		//         	"from_user_id"=>$from_user_id
		//         );
		//         $registatoin_id = array($userDetailFrom['fcm_token']);
		//         $res = $this->send_notification($registatoin_id, $messages_push); 
		// 	}
  //       }
        
	}


	


	public function rateing_list() {
		// limit code start
		if(empty($_REQUEST['start'])) {
				$start = 10;
				$end = 0;
		} else {
			$start = 10;
			$end = $_REQUEST['start'];
		}
		// limit code end

		

		if(!empty($_REQUEST['type'] == 1)) { // for pericular company
			$where = "R.status = 1 AND R.company_id = '". $_REQUEST['id'] ."'";
		} else if(!empty($_REQUEST['type'] == 2)) { // for pericular product
			$where = "R.status = 1 AND R.product_id = '". $_REQUEST['id'] ."'";
		} else if(!empty($_REQUEST['type'] == 3)) { // for my get review
			$where = "R.status = 1 AND R.to_user = '". $_REQUEST['id'] ."'";
		} else if(!empty($_REQUEST['type'] == 4)) { // for my give review
			$where = "R.status = 1 AND R.user_id = '". $_REQUEST['id'] ."'";
		} else if(!empty($_REQUEST['type'] == 5)) { // for pericular customer
			$where = "R.status = 1 AND R.customer_id = '". $_REQUEST['id'] ."'";
		} else {
			$where = "R.status = 1";
		}


		if($_REQUEST['sort_filter'] == 1) {
        	$where.= " AND (R.type = 1 OR R.type = 2)";
        }

		$having = '';
		if(!empty($_REQUEST['search_keyword'])) {
			$having = " (company_name_search LIKE '%". $_REQUEST['search_keyword'] ."%' OR product_name_search LIKE '%". $_REQUEST['search_keyword'] ."%' OR username_search LIKE '%". $_REQUEST['search_keyword'] ."%') ";
		}

		if(!empty($_REQUEST['category_id'])) {
        	$where.= " AND (FIND_IN_SET('". $_REQUEST['category_id'] ."',C.category) OR P.category = '". $_REQUEST['category_id'] ."')";
        }




        $result = $this->user_model->rateing_detail($where,array(),$start,$end, $having);
        // echo $this->db->last_query();
        // echo"<pre>";
        // print_r($result);
        // die();
        // $ratingCount = $this->common->getData('rating',array('status' => 1),array('count'));

        if(!empty($result))
        { 
        	foreach ($result as $key => $value) {
        		if(!empty($value['rate_image'])) {
					$result[$key]['rate_image'] = base_url($value['rate_image']);
					$result[$key]['rate_image_thumb'] = base_url($value['rate_image_thumb']);
				} else {
					$result[$key]['rate_image'] = "assets/img/default-user-icon.jpg";
					$result[$key]['rate_image_thumb'] = "assets/img/default-user-icon.jpg";
				}


				if(!empty($value['product_image'])) {
        			$result[$key]['product_image'] = base_url($value['product_image']);
        			$result[$key]['product_image_thumb'] = base_url($value['product_image_thumb']);
	        	} else {
	        		$result[$key]['product_image'] = "assets/img/default-user-icon.jpg";
					$result[$key]['product_image_thumb'] = "assets/img/default-user-icon.jpg";
	        	}


	        	if(!empty($value['from_profile_image'])) {
					$result[$key]['from_profile_image'] = base_url($value['from_profile_image']);
				} else {
					$result[$key]['from_profile_image'] = "assets/img/default-user-icon.jpg";
				}

				if(!empty($value['to_profile_image'])) {
					$result[$key]['to_profile_image'] = base_url($value['to_profile_image']);
				} else {
					$result[$key]['to_profile_image'] = "assets/img/default-user-icon.jpg";
				}


				if(!empty($value['from_company_profile_image'])) {
					$result[$key]['from_company_profile_image'] = base_url($value['from_company_profile_image']);
				} else {
					$result[$key]['from_company_profile_image'] = "assets/img/default-user-icon.jpg";
				}

	        	if(!empty($value['company_profile_image'])) {
					$result[$key]['company_profile_image'] = base_url($value['company_profile_image']);
				} else {
					$result[$key]['company_profile_image'] = "assets/img/default-user-icon.jpg";
				}
        	}
			$this->response(true,"User Fetch Successfully.",array("ratingList" => $result));			
		}else{
			$this->response(true,"User Fetch Successfully.",array("ratingList" => array()));
		}

	}


	public function otp_verification_forgot() {
		$where_exist = "country_code = '".$_REQUEST['country_code']."' AND phone_number = '". $_REQUEST['phone_number'] ."'";
		$result_exist = $this->common->getData('user',$where_exist);

		if(!empty($result_exist)) {
			$phone_number = $_REQUEST['country_code'].$_REQUEST['phone_number'];
			send_otp($phone_number); 
		
			$this->response(true,"Otp Send Successfully");
		}
		else
		{
			$this->response(false,"Mobile Number Not Found Please Signup First");
		}
		
	}


	public function getMembershipPlan() {
		$monthlyPlanList = $this->common->getData('membership_plan', array('status' => 1, 'plan_type' => 1));
		$yearlyPlanList = $this->common->getData('membership_plan', array('status' => 1, 'plan_type' => 2));

		if(empty($monthlyPlanList)) {
			$monthlyPlanList = array();
		}

		if(empty($yearlyPlanList)) {
			$yearlyPlanList = array();
		}
		
		$this->response(true,"Membership Plan Fetch Successfully", array('monthlyPlanList' => $monthlyPlanList, 'yearlyPlanList' => $yearlyPlanList));
	}


	public function add_subscription() {
		if(!empty($_REQUEST['user_id']) && !empty($_REQUEST['plan_id']) && !empty($_REQUEST['stripeToken'])) {
			
			
			$userInfo = $this->common->getData('user',array('user_id'=>$_REQUEST['user_id']),array('single'));

			// plan info
			$planResult = $this->common->getData('membership_plan',array('id'=>$_REQUEST['plan_id']),array('single'));

			$this->load->helper('stripe');
			AddStripetest();
			$finalAmount = $planResult['price'];
			$token = $_POST['stripeToken'];
			$charge = \Stripe\Charge::create([
			    'amount' => $finalAmount*100,
			    'currency' => 'usd',
			    'description' => $userInfo['username'].'BY User:'.$userInfo['email'],
			    'source' => $token, 
			    'receipt_email'=>$userInfo['email'],
			]);

			if($charge->status==='succeeded') {
				$time_diff = "+".$planResult['days']." day";
				$expiry_date = date("Y-m-d", strtotime($time_diff));
				$data_update['expiry_date'] =  	$expiry_date;
				$data_update['plan_id'] =  	$_REQUEST['plan_id'];
				$result = $this->common->updateData('user',$data_update,array('user_id' => $_REQUEST['user_id'])); 

				// data insert in payment history table	
				$data_pay['user_id'] = 	$_REQUEST['user_id'];
				$data_pay['plan_id'] = $_REQUEST['plan_id'];
				$data_pay['title'] = $planResult['title'];
				$data_pay['description'] = $planResult['description'];
				$data_pay['price'] = $planResult['price'];
				$data_pay['days'] = $planResult['days'];
				$data_pay['type'] = $planResult['plan_type'];
				$data_pay['status'] = 'paid';
				$data_pay['created_at'] = Date('Y-m-d H:i:s');
				$result = $this->common->insertData('payment_history',$data_pay);
				
				$this->response(true,"Subscription Added Successfully",array('finalAmount'=> $finalAmount));
			} else {
				$this->response(false,'Plan Purchase Failed'); 
			}
		} else {
			$this->response(false,"Missing Parameter.");
		}
	}



	public function getPaymenHistory() {
		$payment_history = $this->common->getData('payment_history', array('user_id' => $_REQUEST['user_id']),array('sort_by'=>'id','sort_direction'=>'DESC'));
		
		if(!empty($payment_history)) {
			$this->response(true,"History Fetch Successfully", array('payment_history' => $payment_history));
		} else {
			$this->response(true,"History Fetch Successfully", array('payment_history' => array()));
		}
	}


	public function notification_list() {
		if(!empty($_REQUEST['user_id'])) {
        	$user_id = $_REQUEST['user_id'];
        	$where = "N.user_send_to = '".$user_id."'";
        	$notificationInfo = $this->user_model->notification_detail($where);

        	if(!empty($notificationInfo)) {
	        	foreach ($notificationInfo as $key => $value) {
	                if(!empty($value['profile_image_to'])) {
						$notificationInfo[$key]['profile_image_to'] = base_url($value['profile_image_to']);
					} else {
						$notificationInfo[$key]['profile_image_to'] = "assets/img/default-user-icon.jpg";
					}

					 if(!empty($value['profile_image_from'])) {
						$notificationInfo[$key]['profile_image_from'] = base_url($value['profile_image_from']);
					} else {
						$notificationInfo[$key]['profile_image_from'] = "assets/img/default-user-icon.jpg";
					}

					if(!empty($value['company_profile_image_from'])) {
						$notificationInfo[$key]['company_profile_image_from'] = base_url($value['company_profile_image_from']);
					} else {
						$notificationInfo[$key]['company_profile_image_from'] = "assets/img/default-user-icon.jpg";
					}
	            }



				$result = $this->common->updateData('user',array('notification_count' => '0'),array('user_id' => $user_id));

				$this->response(true,"Notification List.",array("notification_info" =>$notificationInfo));
			} else {
				$this->response(false,"Notification Not Found.",array("notification_info" =>array()));
			}
        } else {
        	$this->response(false,"Missing Parameter.");
        }
	}


	public function get_notification_count() {	
		$userInfo = $this->common->getData('user',array('user_id' => $_REQUEST['user_id']),array('single'));

		$this->response(false,"Notification Count Found Successfully.",array("count" => $userInfo['notification_count']));
	}

	public function delete_notification() {	
		$this->common->deleteData('notification_tbl',array('id' => $_REQUEST['id']),array('single'));

		$this->response(true,"Notification Deleted Successfully.");
	}
	
	}
?>