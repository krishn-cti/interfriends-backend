<?php
defined('BASEPATH') or exit('No direct script access allowed');


class User_model extends CI_Model
{

	function __construct()
	{
		parent::__construct();
	}


	// public function user_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	// {
	// 	$this->db->select('U.*,IFNULL((SELECT total_credit_score FROM credit_score_user where user_id=U.user_id), 0) as total_credit_score');

	// 	//	$this->db->select('U.*,IFNULL(700, 0) as total_credit_score');
	// 	$this->db->from('user as U');
	// 	if ($where != "") {
	// 		$this->db->where($where);
	// 	}

	// 	if ($having != "") {
	// 		$this->db->having($having);
	// 	}

	// 	// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
	// 	$this->db->order_by("U.user_id", 'DESC');

	// 	if ($limit != '') {
	// 		$this->db->limit($limit, $start);
	// 	}

	// 	$res = $this->db->get()->result_array();

	// 	if (!empty($options) && in_array('count', $options)) {
	// 		return count($res);
	// 	}

	// 	if ($res) {
	// 		if (isset($options) && in_array('single', $options)) {
	// 			return $res[0];
	// 		} else {
	// 			return $res;
	// 		}
	// 	} else {
	// 		return false;
	// 	}
	// }

	public function user_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('
			U.*, 
			IFNULL((SELECT total_credit_score FROM credit_score_user WHERE user_id = U.user_id), 0) as total_credit_score,
			GC.circle_name
		');

		$this->db->from('user as U');

		// Join with user_circle and group_circle
		$this->db->join('user_circle as UC', 'UC.user_id = U.user_id', 'left');
		$this->db->join('group_circle as GC', 'GC.id = UC.circle_id', 'left');

		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->order_by("U.user_id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}

	public function user_detail_recommend($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('U.*,IFNULL((SELECT total_credit_score FROM credit_score_user where user_id=U.user_id), 0) as total_credit_score');
		$this->db->from('user as U');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("U.first_name", 'ASC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}

	public function subadmin_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('SA.*');
		$this->db->from('superAdmin as SA');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("SA.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}



	public function category_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('C.category_id,C.category_name,C.category_image,C.category_image_thumb,C.status');
		$this->db->from('category as C');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("C.category_id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}



	public function plan_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('P.*');
		$this->db->from('membership_plan as P');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("P.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}



	public function PF_percent_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('P.*');
		$this->db->from('pf_percent as P');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("P.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}



	public function loan_percent_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('L.*');
		$this->db->from('loan_percent as L');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("L.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}




	public function group_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('G.*');
		$this->db->from('group_cycle as G');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("G.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}




	public function groupCycle_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('GL.*');
		$this->db->from('group_lifecycle as GL');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("GL.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}



	public function getLifeCyclePercent($where = "", $options = array())
	{
		$this->db->select('PP.percent');
		$this->db->from('group_lifecycle as GL');
		if ($where != "") {
			$this->db->where($where);
		}

		$this->db->join('pf_percent as PP', 'PP.id = GL.group_type_id');
		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return (object) array();
		}
	}


	public function userLocation($where = "", $options = array())
	{
		$this->db->select('C.country_id,C.country_code,C.country_name,S.state_id,S.state_code,S.state_name,CT.city_id,CT.city_name');
		$this->db->from('user as U');
		if ($where != "") {
			$this->db->where($where);
		}

		$this->db->join('country as C', 'C.country_id = U.country_id');
		$this->db->join('states as S', 'S.state_id = U.state_id');
		$this->db->join('city as CT', 'CT.city_id = U.city_id');


		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return (object) array();
		}
	}


	public function product_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('P.product_id,P.user_id,P.category,P.product_name,P.product_image,P.product_image_thumb,P.product_detail,P.product_description,P.status,C.category_name,C.category_image,C.category_image_thumb,P.avg_rating,P.rating_count');
		$this->db->from('product as P');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('category as C', 'C.category_id = P.category');
		$this->db->order_by("P.product_id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}



	public function route_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('R.*');
		$this->db->from('route as R');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('category as C','C.category_id = P.category');
		$this->db->order_by("R.route_id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}




	public function stop_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('S.*');
		$this->db->from('stop as S');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('category as C','C.category_id = P.category');
		$this->db->order_by("S.stop_id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}



	public function get_stop_by_lat_long($where, $latitude, $longitude, $limit = '', $start = '', $having = '', $order_by = '', $order_by2 = '')
	{

		$this->db->select('S.*, (
     3959 * acos (
      cos ( radians("' . $latitude . '") )
      * cos( radians( S.latitude ) )
      * cos( radians( S.longitude ) - radians("' . $longitude . '") )
      + sin ( radians("' . $latitude . '") )
      * sin( radians( S.latitude ) )
    )
  ) * 1.609344 AS distance');

		$this->db->from('stop as S');
		if ($where != "") {
			$this->db->where($where);
		}

		// $this->db->join('subscription as SUB','SUB.salon_id = S.salon_id');

		if ($order_by != '') {
			$this->db->order_by($order_by);
		}

		if ($order_by2 != '') {
			echo "oreder 2";
			$this->db->order_by($order_by2);
		}



		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		if ($having != '') {
			$this->db->having($having);
		}

		$res = $this->db->get()->result_array();



		if ($res) {
			return $res;
		} else {
			return false;
		}
	}


	public function rateing_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select("R.id,R.user_id,R.to_user,R.company_id,R.customer_id,R.product_id,R.rate,R.review_title,R.review_description,R.rate_image,R.rate_image_thumb,R.status,R.type,R.user_type,R.created_at,UF.username as from_username,UF.company_name as from_company_name,UF.email as from_email,UF.phone_number as from_phone_number,UF.fcm_token as from_fcm_token,UF.profile_image as from_profile_image,UF.company_profile_image as from_company_profile_image,UT.username as to_username,UT.email as to_email,UT.phone_number as to_phone_number,UT.fcm_token as to_fcm_token,UT.profile_image as to_profile_image,IFNULL(C.company_name,'') as company_name,IFNULL(C.company_description,'') as company_description,IFNULL(C.category,'') as company_category,IFNULL(C.ein,'') as company_ein,IFNULL(C.website,'') as company_website,IFNULL(C.address,'') as company_address,C.company_profile_image as company_profile_image,IFNULL(P.product_name,'') as product_name,IFNULL(P.category,'') as product_category,P.product_image,P.product_image,P.product_image_thumb,IFNULL(P.product_detail,'') as product_detail,IFNULL(P.product_description,'') as product_description,IFNULL(C.avg_rating ,'')as user_avg_rating,IFNULL(P.avg_rating,'') as product_avg_rating,IF(P.avg_rating > '' , P.avg_rating , C.avg_rating) as all_avg_rating,IFNULL(C.rating_count,'') as user_rating_count,IFNULL(P.rating_count,'') as product_rating_count,IF(P.rating_count > '' , P.rating_count , C.rating_count) as all_rating_count,IF(R.type != 1 , '' , IFNULL(C.company_name,'')) as company_name_search,IFNULL(P.product_name,'') as product_name_search,IF(R.type != 3 , '' , IFNULL(UT.username,'')) as username_search");
		$this->db->from('rating as R');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('user as UF', 'UF.user_id = R.user_id');
		$this->db->join('user as UT', 'UT.user_id = R.to_user');
		$this->db->join('user as C', 'C.user_id = R.company_id', 'left');
		$this->db->join('product as P', 'P.product_id = R.product_id', 'left');

		if (!empty($_REQUEST['sort_filter'])) {
			if ($_REQUEST['sort_filter'] == 1) { // trending desc
				$this->db->group_by(array("R.company_id", "R.product_id"));
				$this->db->order_by("all_rating_count", 'DESC');
			} else if ($_REQUEST['sort_filter'] == 2) { // popular desc
				$this->db->order_by("all_avg_rating", 'DESC');
			} else if ($_REQUEST['sort_filter'] == 3) { // rate wise desc
				$this->db->order_by("R.rate", 'DESC');
			}
		} else {
			$this->db->order_by("R.id", 'DESC');
		}



		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}



	public function ticket_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('T.id,T.user_id,T.rate_id,T.title,T.detail,T.description,T.status,T.created_at,R.review_title,R.review_description,R.rate');
		$this->db->from('ticket as T');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('rating as R', 'R.id = T.rate_id');
		$this->db->order_by("T.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}


	public function notification_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('N.*,SA.name as user_send_from_name');
		$this->db->from('notification_tbl as N');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('superAdmin as SA', 'SA.id = N.user_send_from');
		$this->db->order_by("N.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}



	public function notificationAdmin_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('N.*,IFNULL(SA.name,CONCAT(U.first_name," ",U.last_name)) as user_send_from_name');
		$this->db->from('notification_admin_tbl as N');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('superAdmin as SA', 'SA.id = N.user_send_from', 'left');
		$this->db->join('user as U', 'U.user_id = N.user_send_from', 'left');
		$this->db->order_by("N.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}


	public function payment_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('H.id,H.user_id,H.plan_id,H.title,H.description,H.price,H.days,H.status,H.type,H.created_at,U.username,U.email,U.country_code,U.phone_number,U.company_name,U.	company_description,U.profile_image,U.company_profile_image,P.title as plan_title,P.description as plan_description');
		$this->db->from('payment_history as H');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('user as U', 'U.user_id = H.user_id');
		$this->db->join('membership_plan as P', 'P.id = H.plan_id');
		$this->db->order_by("H.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}



	public function user_group_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		// new-changes  ,UG.jnr_amount,

		$this->db->select('UG.id,UG.group_id,UG.user_id,UG.amount,UG.expected_date,UG.jnr_amount,U.first_name,U.last_name,U.email,U.dob,U.mobile_number,U.home_number,U.emergency_number,U.kin_name,U.kin_number,U.address_line_1,U.address_line_2,U.post_code,U.city,U.profile_image,U.profile_image_thumb');
		$this->db->from('user_group as UG');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('user as U', 'U.user_id = UG.user_id');
		$this->db->order_by("UG.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}


	public function user_circle_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('UG.id,UG.group_id,UG.circle_lead,UG.circle_id,UG.user_id,U.first_name,U.last_name,U.email,U.dob,U.mobile_number,U.home_number,U.emergency_number,U.kin_name,U.kin_number,U.address_line_1,U.address_line_2,U.post_code,U.city,U.	profile_image,U.profile_image_thumb');
		$this->db->from('user_circle as UG');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('user as U', 'U.user_id = UG.user_id');
		$this->db->order_by("UG.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}

	//new changes ///

	public function paymentallNotification_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('UG.*,U.first_name,U.last_name,U.email,U.profile_image');
		$this->db->from('payment_notification as UG');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('user as U', 'U.user_id = UG.user_id');
		$this->db->order_by("UG.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}


	public function safeKeeping_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('SK.*');
		$this->db->from('safe_keeping as SK');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("SK.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}


	public function pf_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select("PF.*,IFNULL(GL.start_date,'') as cycle_start_date,IFNULL(GL.end_date,'') as cycle_end_date,GL.group_type_id");
		$this->db->from('pf_user as PF');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('group_lifecycle as GL', 'GL.id = PF.main_id', 'left');
		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("PF.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}


	public function loanStatusHistory_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('ULSH.*');
		$this->db->from('user_loan_status_history as UCSH');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("ULSH.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}


	public function miscellaneousStatusHistory_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('UMSH.*');
		$this->db->from('user_miscellaneous_status_history as UMSH');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("UMSH.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}


	public function userCycleStatusHistory_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('UCSH.*');
		$this->db->from('user_cycle_status_history as UCSH');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("UCSH.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}


	public function payout_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('P.*');
		$this->db->from('payout_cycle as P');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("P.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}



	public function loan_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select("L.*, IFNULL((SELECT SUM(amount) FROM user_loan_payment where user_id='" . $_REQUEST['user_id'] . "' AND group_id='" . $_REQUEST['group_id'] . "' AND loan_id=L.id), 0) as paid_amount,U.first_name as gurarantor_first_name,U.last_name as gurarantor_last_name,U.email as gurarantor_email");

		$this->db->from('user_loan as L');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('user as U', 'U.user_id = L.gurarantor', 'left');
		$this->db->order_by("L.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}



	public function userGroup_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('UG.*, G.group_cycle_name, G.group_cycle_descp');
		$this->db->from('user_group as UG');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('group_cycle as G', 'G.id = UG.group_id');
		$this->db->order_by("UG.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}



	public function emergencyLoan_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('UE.*,U.first_name as gurarantor_first_name,U.last_name as gurarantor_last_name,U.email as gurarantor_email');
		$this->db->from('user_emergency_loan as UE');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('user as U', 'U.user_id = UE.gurarantor', 'left');
		$this->db->order_by("UE.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}



	public function miscellaneous_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('UM.*');
		$this->db->from('user_miscellaneous as UM');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('user as U','U.user_id = UE.gurarantor', 'left');
		$this->db->order_by("UM.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}




	public function miscellaneous_detail_new($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select("L.*, IFNULL((SELECT SUM(amount) FROM user_miscellaneous_payment where user_id='" . $_REQUEST['user_id'] . "' AND group_id='" . $_REQUEST['group_id'] . "' AND loan_id=L.id), 0) as paid_amount");
		$this->db->from('user_miscellaneous as L');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->order_by("L.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}


	public function recommendUser_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select("RU.*,U.first_name as first_name_main_refer,U.last_name as last_name_main_refer,U.email as email_main_refer,IFNULL(UR.first_name,'') as first_name_refer,IFNULL(UR.last_name,'') as last_name_refer,IFNULL(UR.email,'') as email_refer");
		$this->db->from('recommend_user as RU');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('user as U', 'U.user_id = RU.user_id');
		$this->db->join('user as UR', 'UR.user_id = RU.refer_user_id', 'left');
		$this->db->order_by("RU.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}




	public function safe_keeping_withdral_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select("SK.*,U.first_name,U.last_name,U.email");
		$this->db->from('safe_keeping_withdral_request as SK');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('user as U', 'U.user_id = SK.user_id');
		$this->db->order_by("SK.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}


	public function investment_request_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select("IR.*,U.first_name,U.last_name,U.email,P.title as property_title,P.short_description as property_short_description");
		$this->db->from('investment_request as IR');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('user as U', 'U.user_id = IR.user_id');
		$this->db->join('property as P', 'P.id = IR.property_id');
		$this->db->order_by("IR.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}


	public function property_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('P.*');
		$this->db->from('property as P');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('group_cycle as G','G.id = UG.group_id');
		$this->db->order_by("P.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}





	public function investment_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('I.*');
		$this->db->from('investment as I');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
		$this->db->order_by("I.id", 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (isset($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return false;
		}
	}
}
