<?php
defined('BASEPATH') OR exit('No direct script access allowed');
date_default_timezone_set("Asia/Kolkata");
class Paidapi extends Base_Controller {
	
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



	public function getRatingList() {
		if(!empty($_REQUEST['token'])) {
			$userDetail = $this->common->getData('user',array('paid_api_token'=>$_REQUEST['token']),array('single'));
			if(!empty($userDetail)) {
					// limit code start
					if(empty($_REQUEST['start'])) {
							$start = 10;
							$end = 0;
					} else {
						$start = 10;
						$end = $_REQUEST['start'];
					}
					// limit code end

					$_REQUEST['type'] = 3;
					$_REQUEST['id'] = $userDetail['user_id'];

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
						$this->response(true,"User fetch Successfully.",array("ratingList" => $result));			
					}else{
						$this->response(true,"User fetch Successfully.",array("ratingList" => array()));
					}
			} else {
				$this->response(false,"Token not found");
			}
		} else {
			$this->response(false,"Missing Parameter.");
		}
		
	}	
	
	
	
	}
?>