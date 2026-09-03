<?php
defined('BASEPATH') or exit('No direct script access allowed');


class User_model extends CI_Model
{

	function __construct()
	{
		parent::__construct();
	}

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

		$this->db->select('UG.id,UG.group_id,UG.user_id,UG.amount,UG.expected_date,UG.jnr_amount,U.first_name,U.last_name,U.email,U.dob,U.country_code,U.mobile_number,U.home_country_code,U.home_number,U.emergency_country_code,U.emergency_number,U.kin_name,U.kin_country_code,U.kin_number,U.address_line_1,U.address_line_2,U.post_code,U.city,U.profile_image,U.profile_image_thumb');
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
		$this->db->select('UG.id,UG.group_id,UG.circle_lead,UG.circle_id,UG.user_id,U.first_name,U.last_name,U.email,U.dob,U.country_code,U.mobile_number,U.home_country_code,U.home_number,U.emergency_country_code,U.emergency_number,U.kin_name,U.kin_country_code,U.kin_number,U.address_line_1,U.address_line_2,U.post_code,U.city,U.profile_image,U.profile_image_thumb');
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


	// public function payout_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	// {
	// 	$this->db->select('P.*');
	// 	$this->db->from('payout_cycle as P');
	// 	if ($where != "") {
	// 		$this->db->where($where);
	// 	}

	// 	if ($having != "") {
	// 		$this->db->having($having);
	// 	}

	// 	// $this->db->join('tag_tbl as T','T.tag_id = R.tags');
	// 	$this->db->order_by("P.id", 'DESC');

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


	// created by krishn 19-06-2026
	public function payout_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('P.*');
		$this->db->from('payout_cycle as P');

		// Added joins
		$this->db->join('user as U', 'U.user_id = P.user_id', 'left');
		$this->db->join('user_circle as UC', 'UC.user_id = U.user_id', 'left');

		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

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
		// $this->db->select("L.*, IFNULL((SELECT SUM(amount) FROM user_loan_payment where user_id='" . $_REQUEST['user_id'] . "' AND group_id='" . $_REQUEST['group_id'] . "' AND loan_id=L.id), 0) as paid_amount,U.first_name as gurarantor_first_name,U.last_name as gurarantor_last_name,U.email as gurarantor_email");

		$this->db->select("
			L.*,
			IFNULL(
				(
					SELECT SUM(amount)
					FROM user_loan_payment
					WHERE user_id = L.user_id
					AND group_id = L.group_id
					AND loan_id = L.id
					AND status = 1
				),
				0
			) AS paid_amount,
			U.first_name AS gurarantor_first_name,
			U.last_name AS gurarantor_last_name,
			U.email AS gurarantor_email
		");

		$this->db->from('user_loan as L');

		if ($where != "") {
			$this->db->where($where);
		}

		$this->db->join('user as UU', 'UU.user_id = L.user_id');
		$this->db->where('UU.status !=', 2);

		$this->db->join('user as U', 'U.user_id = L.gurarantor', 'left');

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->order_by("L.id", "DESC");

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

		$this->db->join('user U', 'U.user_id = UM.user_id');
		$this->db->where('U.status !=', 2);

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

	public function recommendUserApprovalTracking_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select("RU.*,U.first_name as first_name_main_refer,U.last_name as last_name_main_refer,U.email as email_main_refer,IFNULL(UR.first_name,'') as first_name_refer,IFNULL(UR.last_name,'') as last_name_refer,IFNULL(UR.email,'') as email_refer,IFNULL(REG.user_id,'0') as recommended_user_id,IFNULL(REG.recommended,'') as recommended_status");
		$this->db->from('recommend_user as RU');
		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		$this->db->join('user as U', 'U.user_id = RU.user_id');
		$this->db->join('user as UR', 'UR.user_id = RU.refer_user_id', 'left');
		$this->db->join('user as REG', 'REG.email = RU.email', 'left');
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




	// public function safe_keeping_withdral_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	// {
	// 	$this->db->select("SK.*,U.first_name,U.last_name,U.email");
	// 	$this->db->from('safe_keeping_withdral_request as SK');
	// 	if ($where != "") {
	// 		$this->db->where($where);
	// 	}

	// 	if ($having != "") {
	// 		$this->db->having($having);
	// 	}

	// 	$this->db->join('user as U', 'U.user_id = SK.user_id');
	// 	$this->db->order_by("SK.id", 'DESC');

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
	// 		return array();
	// 	}
	// }

	// created by krishn 19-06-2026
	public function safe_keeping_withdral_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select("SK.*,U.first_name,U.last_name,U.email");
		$this->db->from('safe_keeping_withdral_request as SK');

		$this->db->join('user as U', 'U.user_id = SK.user_id');
		$this->db->join('user_circle as UC', 'UC.user_id = U.user_id', 'left');

		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

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

	public function saving_detail($where = "", $options = array(), $limit = '', $start = '', $having = '')
	{
		$this->db->select('UGL.*');
		$this->db->from('user_group_lifecycle as UGL');

		if ($where != "") {
			$this->db->where($where);
		}

		if ($having != "") {
			$this->db->having($having);
		}

		// Exclude blocked users
		$this->db->join('user as U', 'U.user_id = UGL.user_id');
		$this->db->where('U.status !=', 2);

		$this->db->order_by('UGL.id', 'DESC');

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

	// created by krishn 13-07-2026
	public function serviceCategory_detail($where = array(), $options = array(), $limit = '', $start = '')
	{
		$this->db->select('SC.*');
		$this->db->from('service_categories SC');

		if (!empty($where['SC.created_by'])) {
			$this->db->where('SC.created_by', $where['SC.created_by']);
		}

		if (!empty($where['SC.created_by_type'])) {
			$this->db->where('SC.created_by_type', $where['SC.created_by_type']);
		}

		if (!empty($where['status'])) {
			$this->db->where('SC.status', $where['status']);
		}

		if (!empty($where['category_name LIKE'])) {
			$this->db->like('SC.category_name', str_replace('%', '', $where['category_name LIKE']));
		}

		$this->db->order_by('SC.id', 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (!empty($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}

	// created by krishn 13-07-2026
	public function serviceSubCategory_detail($where = array(), $options = array(), $limit = '', $start = '')
	{
		$this->db->select('
			SSC.*,
			SC.category_name
		');

		$this->db->from('service_subcategories SSC');

		$this->db->join(
			'service_categories SC',
			'SC.id = SSC.category_id',
			'left'
		);

		if (!empty($where['SSC.created_by'])) {
			$this->db->where('SSC.created_by', $where['SSC.created_by']);
		}

		if (!empty($where['SSC.created_by_type'])) {
			$this->db->where('SSC.created_by_type', $where['SSC.created_by_type']);
		}

		if (!empty($where['SSC.category_id'])) {
			$this->db->where('SSC.category_id', $where['SSC.category_id']);
		}

		if (isset($where['SSC.status']) && $where['SSC.status'] !== '') {
			$this->db->where('SSC.status', $where['SSC.status']);
		}

		if (!empty($where['search'])) {
			$this->db->group_start();
			$this->db->like('SSC.subcategory_name', $where['search']);
			$this->db->or_like('SC.category_name', $where['search']);
			$this->db->group_end();
		}

		$this->db->order_by('SSC.id', 'DESC');

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {
			if (!empty($options) && in_array('single', $options)) {
				return $res[0];
			} else {
				return $res;
			}
		} else {
			return array();
		}
	}

	// created by krishn 14-07-2026
	public function service_detail($where = array(), $options = array(), $limit = '', $start = '')
	{
		$this->db->select("
			S.*,
			SC.category_name,
			SSC.subcategory_name
		");

		$this->db->from('services S');

		$this->db->join(
			'service_categories SC',
			'SC.id = S.category_id',
			'left'
		);

		$this->db->join(
			'service_subcategories SSC',
			'SSC.id = S.subcategory_id',
			'left'
		);

		if (!empty($where['S.created_by'])) {
			$this->db->where('S.created_by', $where['S.created_by']);
		}

		if (!empty($where['S.created_by_type'])) {
			$this->db->where('S.created_by_type', $where['S.created_by_type']);
		}

		if (!empty($where['S.category_id'])) {
			$this->db->where('S.category_id', $where['S.category_id']);
		}

		if (!empty($where['S.subcategory_id'])) {
			$this->db->where('S.subcategory_id', $where['S.subcategory_id']);
		}

		if (isset($where['S.status'])) {
			$this->db->where('S.status', $where['S.status']);
		}

		if (!empty($where['S.id'])) {
			$this->db->where('S.id', $where['S.id']);
		}

		if (!empty($where['search'])) {
			$this->db->group_start();
			$this->db->like('S.service_name', $where['search']);
			$this->db->or_like('SC.category_name', $where['search']);
			$this->db->or_like('SSC.subcategory_name', $where['search']);
			$this->db->group_end();
		}

		$this->db->order_by('S.id', 'DESC');

		if ($limit !== '' && $limit !== null) {
			if ($start !== '' && $start !== null) {
				$this->db->limit((int)$limit, (int)$start);
			} else {
				$this->db->limit((int)$limit);
			}
		}

		$res = $this->db->get()->result_array();

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		if ($res) {

			if (!empty($options) && in_array('single', $options)) {
				return $res[0];
			}

			return $res;
		}

		return array();
	}

	// created by krishn 14-07-2026
	public function serviceAssignedUsers($service_id)
	{
		$this->db->select("
			US.id,
			US.user_id,
			U.first_name,
			U.last_name,
			U.email,
			U.mobile_number,
			US.price,
			US.description,
			US.location,
			US.latitude,
			US.longitude,
			US.country_code,
			US.approval_status,
			US.status
		");

		$this->db->from('user_services US');

		$this->db->join('user U', 'U.user_id = US.user_id');

		$this->db->where('US.service_id', $service_id);

		$this->db->where('US.status', 1);

		$this->db->order_by('U.first_name', 'ASC');

		return $this->db->get()->result_array();
	}

	// created by krishn 14-07-2026
	public function serviceAvailableUser_detail($search = "", $service_id = 0)
	{
		$this->db->select("
			U.user_id,
			U.first_name,
			U.last_name,
			U.email
		");

		$this->db->from('user U');

		$this->db->where('U.status !=', 2);

		if (!empty($search)) {

			$this->db->group_start();
			$this->db->like('U.first_name', $search);
			$this->db->or_like('U.last_name', $search);
			$this->db->or_like('U.email', $search);
			$this->db->group_end();
		}

		// User can have maximum 5 active services
		$this->db->where("
			(
				SELECT COUNT(*)
				FROM user_services US
				WHERE US.user_id = U.user_id
				AND US.approval_status IN (0,1)
			) < 5
		", NULL, FALSE);

		// Exclude users already assigned to this service
		if (!empty($service_id)) {

			$this->db->where("
				NOT EXISTS (
					SELECT 1
					FROM user_services US2
					WHERE US2.user_id = U.user_id
					AND US2.status = 1
					AND US2.service_id = " . $this->db->escape($service_id) . "
				)
			", NULL, FALSE);
		}

		$this->db->order_by('U.created_at', 'DESC');

		return $this->db->get()->result_array();
	}

	// created by krishn 15-07-2026
	// public function user_assigned_services($where = "", $options = array(), $limit = '', $start = '')
	// {
	// 	$this->db->select("
	// 		US.id AS user_service_id,
	// 		US.service_id,
	// 		US.company_name,
	// 		US.company_logo,
	// 		US.price,
	// 		US.description as provider_description,
	// 		US.country_code,
	// 		US.mobile,
	// 		US.email,
	// 		US.website,
	// 		US.location,
	// 		US.service_start_date,
	// 		US.service_end_date,
	// 		US.latitude,
	// 		US.longitude,
	// 		US.status as user_service_status,
	// 		US.approval_status,
	// 		US.created_at,

	// 		S.service_name,
	// 		S.description as service_description,
	// 		S.status as service_status,

	// 		U.user_id,
	// 		U.first_name,
	// 		U.last_name,
	// 		U.email as user_email,
	// 		U.mobile_number,
	// 		U.profile_image,
	// 		U.profile_image_thumb,

	// 		SC.category_name,
	// 		SSC.subcategory_name
	// 	");

	// 	$this->db->from('user_services US');

	// 	$this->db->join('services S', 'S.id = US.service_id');
	// 	$this->db->join('service_categories SC', 'SC.id = S.category_id');
	// 	$this->db->join('service_subcategories SSC', 'SSC.id = S.subcategory_id');

	// 	$this->db->join('user U', 'U.user_id = US.user_id');
	// 	$this->db->join('user_circle UC', 'UC.user_id = U.user_id', 'left');

	// 	if (!empty($where) && is_array($where)) {
	// 		if (!empty($where['US.id'])) {
	// 			$this->db->where('US.id', $where['US.id']);
	// 		}

	// 		if (!empty($where['US.user_id'])) {
	// 			$this->db->where('US.user_id', $where['US.user_id']);
	// 		}

	// 		if (!empty($where['US.service_id'])) {
	// 			$this->db->where('US.service_id', $where['US.service_id']);
	// 		}

	// 		if (!empty($where['S.category_id'])) {
	// 			$this->db->where('S.category_id', $where['S.category_id']);
	// 		}

	// 		if (!empty($where['S.subcategory_id'])) {
	// 			$this->db->where('S.subcategory_id', $where['S.subcategory_id']);
	// 		}

	// 		if (isset($where['US.status'])) {
	// 			$this->db->where('US.status', $where['US.status']);
	// 		}

	// 		if (isset($where['US.approval_status'])) {
	// 			$this->db->where('US.approval_status', $where['US.approval_status']);
	// 		}

	// 		if (isset($where['S.status'])) {
	// 			$this->db->where('S.status', $where['S.status']);
	// 		}

	// 		if (!empty($where['group_ids'])) {
	// 			$this->db->where_in('UC.group_id', $where['group_ids']);
	// 		}

	// 		if (!empty($where['circle_ids'])) {
	// 			$this->db->where_in('UC.circle_id', $where['circle_ids']);
	// 		}

	// 		if (!empty($where['search'])) {
	// 			$this->db->group_start();
	// 			$this->db->like('S.service_name', $where['search']);
	// 			$this->db->or_like('S.description', $where['search']);
	// 			$this->db->or_like('US.company_name', $where['search']);
	// 			$this->db->or_like('US.description', $where['search']);
	// 			$this->db->or_like('US.email', $where['search']);
	// 			$this->db->or_like('US.country_code', $where['search']);
	// 			$this->db->or_like('US.mobile', $where['search']);
	// 			$this->db->or_like('US.location', $where['search']);
	// 			$this->db->or_like('U.first_name', $where['search']);
	// 			$this->db->or_like('U.last_name', $where['search']);
	// 			$this->db->or_like('U.email', $where['search']);
	// 			$this->db->or_like('U.mobile_number', $where['search']);
	// 			$this->db->or_like('SC.category_name', $where['search']);
	// 			$this->db->or_like('SSC.subcategory_name', $where['search']);
	// 			$this->db->group_end();
	// 		}
	// 	} elseif (!empty($where)) {
	// 		$this->db->where("1=1 {$where}", NULL, FALSE);
	// 	}

	// 	if (!empty($options) && in_array('order_by_pending', $options)) {
	// 		$this->db->order_by("CASE WHEN US.approval_status = 0 THEN 0 ELSE 1 END", "ASC", FALSE);
	// 		$this->db->order_by('US.id', 'DESC');
	// 	} else {
	// 		$this->db->order_by('US.id', 'DESC');
	// 	}

	// 	if ($limit != '') {
	// 		$this->db->limit($limit, $start);
	// 	}

	// 	$res = $this->db->get()->result_array();

	// 	if (!empty($options) && in_array('count', $options)) {
	// 		return count($res);
	// 	}

	// 	return $res;
	// }

	public function user_assigned_services($where = "", $options = array(), $limit = '', $start = '')
	{
		$this->db->select("
			US.id AS user_service_id,
			US.service_id,
			US.company_name,
			US.company_logo,
			US.price,
			US.description as provider_description,
			US.country_code,
			US.mobile,
			US.email,
			US.website,
			US.location,
			US.service_start_date,
			US.service_end_date,
			US.latitude,
			US.longitude,
			US.status as user_service_status,
			US.approval_status,
			US.created_at,

			S.service_name,
			S.description as service_description,
			S.status as service_status,

			U.user_id,
			U.first_name,
			U.last_name,
			U.email as user_email,
			U.mobile_number,
			U.profile_image,
			U.profile_image_thumb,

			SC.category_name,
			SSC.subcategory_name
		");

		$this->db->from('user_services US');

		$this->db->join('services S', 'S.id = US.service_id');
		$this->db->join('service_categories SC', 'SC.id = S.category_id');
		$this->db->join('service_subcategories SSC', 'SSC.id = S.subcategory_id');

		$this->db->join('user U', 'U.user_id = US.user_id');
		$this->db->join('user_circle UC', 'UC.user_id = U.user_id', 'left');

		if (!empty($where) && is_array($where)) {
			if (!empty($where['US.id'])) {
				$this->db->where('US.id', $where['US.id']);
			}

			if (!empty($where['US.user_id'])) {
				$this->db->where('US.user_id', $where['US.user_id']);
			}

			if (!empty($where['US.service_id'])) {
				$this->db->where('US.service_id', $where['US.service_id']);
			}

			if (!empty($where['expired_services'])) {
				$this->db->where('US.service_end_date <', date('Y-m-d'));
			}

			if (!empty($where['S.category_id'])) {
				$this->db->where('S.category_id', $where['S.category_id']);
			}

			if (!empty($where['S.subcategory_id'])) {
				$this->db->where('S.subcategory_id', $where['S.subcategory_id']);
			}

			if (isset($where['US.status'])) {
				$this->db->where('US.status', $where['US.status']);
			}

			if (isset($where['US.approval_status'])) {
				$this->db->where('US.approval_status', $where['US.approval_status']);
			}

			if (isset($where['S.status'])) {
				$this->db->where('S.status', $where['S.status']);
			}

			if (!empty($where['group_ids'])) {
				$this->db->where_in('UC.group_id', $where['group_ids']);
			}

			if (!empty($where['circle_ids'])) {
				$this->db->where_in('UC.circle_id', $where['circle_ids']);
			}

			if (!empty($where['search'])) {
				$this->db->group_start();
				$this->db->like('S.service_name', $where['search']);
				$this->db->or_like('S.description', $where['search']);
				$this->db->or_like('US.company_name', $where['search']);
				$this->db->or_like('US.description', $where['search']);
				$this->db->or_like('US.email', $where['search']);
				$this->db->or_like('US.country_code', $where['search']);
				$this->db->or_like('US.mobile', $where['search']);
				$this->db->or_like('US.location', $where['search']);
				$this->db->or_like('U.first_name', $where['search']);
				$this->db->or_like('U.last_name', $where['search']);
				$this->db->or_like('U.email', $where['search']);
				$this->db->or_like('U.mobile_number', $where['search']);
				$this->db->or_like('SC.category_name', $where['search']);
				$this->db->or_like('SSC.subcategory_name', $where['search']);
				$this->db->group_end();
			}
		} elseif (!empty($where)) {
			$this->db->where("1=1 {$where}", NULL, FALSE);
		}

		if (!empty($options) && in_array('order_by_pending', $options)) {
			$this->db->order_by("CASE WHEN US.approval_status = 0 THEN 0 ELSE 1 END", "ASC", FALSE);
			$this->db->order_by('US.id', 'DESC');
		} else {
			$this->db->order_by('US.id', 'DESC');
		}

		if ($limit != '') {
			$this->db->limit($limit, $start);
		}

		$res = $this->db->get()->result_array();

		/*
		|--------------------------------------------------------------------------
		| Check Service Expiry
		|--------------------------------------------------------------------------
		|
		| If service_end_date has passed today's date:
		| is_expired = true
		|
		| If service_end_date is today or in the future:
		| is_expired = false
		|
		| If service_end_date is empty:
		| is_expired = false
		|
		*/

		$today = date('Y-m-d');

		foreach ($res as &$row) {

			$row['is_expired'] = false;

			if (
				!empty($row['service_end_date']) &&
				$row['service_end_date'] < $today
			) {
				$row['is_expired'] = true;
			}
		}

		unset($row);

		if (!empty($options) && in_array('count', $options)) {
			return count($res);
		}

		return $res;
	}

	public function attachServiceImages($services)
	{
		if (empty($services)) {
			return array();
		}

		$userServiceIds = array_column($services, 'user_service_id');

		$this->db->where_in('user_service_id', $userServiceIds);
		$this->db->order_by('id', 'ASC');
		$serviceImages = $this->db->get('service_images')->result_array();

		$imageMap = array();

		foreach ($serviceImages as $serviceImage) {
			$imageMap[$serviceImage['user_service_id']][] = array(
				'id'              => $serviceImage['id'],
				'user_service_id' => $serviceImage['user_service_id'],
				'image'           => !empty($serviceImage['image']) ? base_url($serviceImage['image']) : '',
				'created_at'      => $serviceImage['created_at'],
				'updated_at'      => $serviceImage['updated_at']
			);
		}

		foreach ($services as $key => $service) {
			$userServiceId = $service['user_service_id'];
			$services[$key]['profile_image'] = !empty($service['profile_image']) ? base_url($service['profile_image']) : 'assets/img/default-user-icon.jpg';
			$services[$key]['profile_image_thumb'] = !empty($service['profile_image_thumb']) ? base_url($service['profile_image_thumb']) : 'assets/img/default-user-icon.jpg';
			if (!empty($service['company_logo'])) {
				$services[$key]['company_logo'] = (filter_var($service['company_logo'], FILTER_VALIDATE_URL) !== false) ? $service['company_logo'] : base_url($service['company_logo']);
			} else {
				$services[$key]['company_logo'] = '';
			}
			$services[$key]['service_images'] = isset($imageMap[$userServiceId]) ? $imageMap[$userServiceId] : array();
		}

		return $services;
	}

	// created by krishn on 31-07-26
	public function getOutstandingLoanPayments($where = "")
	{
		$this->db->select("
			ULP.id,
			ULP.loan_id,
			ULP.user_id,
			ULP.amount,
			ULP.emi_date,
			ULP.status,

			UL.loan_amount,
			UL.loan_emi,
			UL.reference_no,
			UL.loan_type,

			U.first_name,
			U.last_name,
			U.email
		");

		$this->db->from('user_loan_payment ULP');

		$this->db->join(
			'user_loan UL',
			'UL.id = ULP.loan_id'
		);

		$this->db->join(
			'user U',
			'U.user_id = UL.user_id'
		);

		if (!empty($where)) {
			$this->db->where($where, NULL, FALSE);
		}

		return $this->db->get()->result_array();
	}
	
	// created by @krishn on 25/08/26
	public function getDividendPreview($dividendYear, $percentage, $type = 1)
	{
		$typeName = ($type == 2) ? 'Provident' : 'Investment';

		// Check if dividend already applied for this year + type
		$duplicate = $this->db
			->where('dividend_year', $dividendYear)
			->where('type', $type)
			->where('property_id IS NULL', null, false)
			->get('dividend')
			->row_array();

		$alreadyApplied = !empty($duplicate);

		$eligibleUsers = $this->getDividendEligibleUsers();

		$usersCount           = 0;
		$totalProvidentBal    = 0;
		$totalInvestmentBal   = 0;
		$totalProvidentDiv    = 0;
		$totalInvestmentDiv   = 0;
		$totalDividend        = 0;

		foreach ($eligibleUsers as $user) {
			$providentBalance   = max(0, (float) $user['provident_balance']);
			$investmentBalance  = max(0, (float) $user['investment_balance']);

			if ($type == 1) {
				// Investment dividend only
				$providentDividend  = 0.00;
				$investmentDividend = round(($investmentBalance * $percentage) / 100, 2);
			} elseif ($type == 2) {
				// Provident dividend only
				$providentDividend  = round(($providentBalance * $percentage) / 100, 2);
				$investmentDividend = 0.00;
			} else {
				$providentDividend  = round(($providentBalance * $percentage) / 100, 2);
				$investmentDividend = round(($investmentBalance * $percentage) / 100, 2);
			}

			$rowTotal = round($providentDividend + $investmentDividend, 2);

			if ($rowTotal <= 0) {
				continue;
			}

			$usersCount++;
			$totalProvidentBal  += $providentBalance;
			$totalInvestmentBal += $investmentBalance;
			$totalProvidentDiv  += $providentDividend;
			$totalInvestmentDiv += $investmentDividend;
			$totalDividend      += $rowTotal;
		}

		return array(
			'status'  => true,
			'message' => 'Dividend preview calculated successfully.',
			'data'    => array(
				'dividend_year'              => $dividendYear,
				'percentage'                 => number_format($percentage, 2, '.', ''),
				'type'                       => $type,
				'type_name'                  => $typeName,
				'already_applied'            => $alreadyApplied,
				'eligible_users_count'       => $usersCount,
				'total_provident_balance'    => number_format($totalProvidentBal, 2, '.', ''),
				'total_investment_balance'   => number_format($totalInvestmentBal, 2, '.', ''),
				'total_provident_dividend'   => number_format($totalProvidentDiv, 2, '.', ''),
				'total_investment_dividend'  => number_format($totalInvestmentDiv, 2, '.', ''),
				'total_dividend'             => number_format($totalDividend, 2, '.', '')
			)
		);
	}

	public function createDividendForAllUsers($dividendYear, $percentage, $description, $createdBy, $type = 1)
	{
		$duplicate = $this->db
			->where('dividend_year', $dividendYear)
			->where('type', $type)
			->where('property_id IS NULL', null, false)
			->get('dividend')
			->row_array();

		if (!empty($duplicate)) {
			$typeName = ($type == 2) ? 'Provident' : 'Investment';
			return array(
				'status' => false,
				'message' => 'Dividend for ' . $typeName . ' has already been applied for this year.'
			);
		}

		$eligibleUsers = $this->getDividendEligibleUsers();

		if (empty($eligibleUsers)) {
			return array(
				'status' => false,
				'message' => 'No eligible users found for dividend.'
			);
		}

		$now = date('Y-m-d H:i:s');

		$this->db->trans_begin();

		$this->db->insert('dividend', array(
			'dividend_year' => $dividendYear,
			'percentage' => number_format($percentage, 2, '.', ''),
			'type' => $type,
			'property_id' => null,
			'description' => $description,
			'status' => 1,
			'created_by' => $createdBy,
			'created_at' => $now
		));

		$dividendId = $this->db->insert_id();

		if (empty($dividendId)) {
			$this->db->trans_rollback();

			return array(
				'status' => false,
				'message' => 'There is a problem creating dividend.'
			);
		}

		$rows = array();

		$usersProcessed = 0;
		$totalProvidentDividend = 0;
		$totalInvestmentDividend = 0;
		$totalDividend = 0;

		foreach ($eligibleUsers as $user) {
			$providentBalance = max(0, (float) $user['provident_balance']);
			$investmentBalance = max(0, (float) $user['investment_balance']);

			if ($type == 1) {
				// Investment dividend only
				$providentDividend = 0.00;
				$investmentDividend = round(($investmentBalance * $percentage) / 100, 2);
			} else if ($type == 2) {
				// Provident dividend only
				$providentDividend = round(($providentBalance * $percentage) / 100, 2);
				$investmentDividend = 0.00;
			} else {
				$providentDividend = round(($providentBalance * $percentage) / 100, 2);
				$investmentDividend = round(($investmentBalance * $percentage) / 100, 2);
			}

			$rowTotalDividend = round($providentDividend + $investmentDividend, 2);

			if ($rowTotalDividend <= 0) {
				continue;
			}

			$rows[] = array(
				'dividend_id' => $dividendId,
				'user_id' => $user['user_id'],
				'group_id' => $user['group_id'],
				'provident_balance' => $providentBalance,
				'investment_balance' => $investmentBalance,
				'percentage' => number_format($percentage, 2, '.', ''),
				'provident_dividend' => $providentDividend,
				'investment_dividend' => $investmentDividend,
				'total_dividend' => $rowTotalDividend,
				'paid_amount' => 0,
				'balance_amount' => $rowTotalDividend,
				'status' => 3,
				'created_at' => $now
			);

			$usersProcessed++;
			$totalProvidentDividend += $providentDividend;
			$totalInvestmentDividend += $investmentDividend;
			$totalDividend += $rowTotalDividend;
		}

		if (empty($rows)) {
			$this->db->trans_rollback();

			return array(
				'status' => false,
				'message' => 'No eligible users found with a positive dividend amount.'
			);
		}

		$this->db->insert_batch('dividend_user', $rows);

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();

			return array(
				'status' => false,
				'message' => 'There is a problem applying dividend, please try again.'
			);
		}

		$this->db->trans_commit();

		return array(
			'status' => true,
			'message' => 'Dividend applied successfully. All user dividends initiated.',
			'data' => array(
				'dividend_id' => $dividendId,
				'dividend_year' => $dividendYear,
				'percentage' => number_format($percentage, 2, '.', ''),
				'type' => $type,
				'type_name' => ($type == 2 ? 'Provident' : 'Investment'),
				'usersProcessed' => $usersProcessed,
				'totalProvidentDividend' => number_format($totalProvidentDividend, 2, '.', ''),
				'totalInvestmentDividend' => number_format($totalInvestmentDividend, 2, '.', ''),
				'totalDividend' => number_format($totalDividend, 2, '.', '')
			)
		);
	}

	public function dividendList($where = array(), $limit = 10, $start = 0)
	{
		$this->db->select("
			D.*,
			COUNT(DU.id) as users_processed,
			IFNULL(SUM(DU.provident_dividend), 0) as total_provident_dividend,
			IFNULL(SUM(DU.investment_dividend), 0) as total_investment_dividend,
			IFNULL(SUM(DU.total_dividend), 0) as total_dividend,
			IFNULL(SUM(DU.paid_amount), 0) as total_paid_amount,
			IFNULL(SUM(DU.balance_amount), 0) as total_balance_amount
		", false);

		$this->db->from('dividend D');
		$this->db->join('dividend_user DU', 'DU.dividend_id = D.id', 'left');

		if (!empty($where)) {
			$this->db->where($where);
		}

		$this->db->group_by('D.id');
		$this->db->order_by('D.id', 'DESC');
		$this->db->limit($limit, $start);

		$result = $this->db->get()->result_array();

		$this->db->select('D.id');
		$this->db->from('dividend D');

		if (!empty($where)) {
			$this->db->where($where);
		}

		$resultCount = $this->db->count_all_results();
		$countData = $start + 1;

		if (!empty($result)) {
			foreach ($result as $key => $value) {
				$result[$key]['sno'] = $countData++;
				$result[$key]['type_name'] = isset($value['type']) && $value['type'] == 2 ? 'Provident' : 'Investment';
			}
		}

		return array(
			'lists' => !empty($result) ? $result : array(),
			'listCount' => $resultCount
		);
	}

	public function dividendDetail($dividendId, $filters = array())
	{
		$dividend = $this->db
			->where('id', $dividendId)
			->get('dividend')
			->row_array();

		if (empty($dividend)) {
			return false;
		}

		$dividend['type_name'] = isset($dividend['type']) && $dividend['type'] == 2 ? 'Provident' : 'Investment';

		$this->db->select('DU.*, U.first_name, U.last_name, U.email');
		$this->db->from('dividend_user DU');
		$this->db->join('user U', 'U.user_id = DU.user_id');
		$this->db->where('DU.dividend_id', $dividendId);

		if (!empty($filters['user_id'])) {
			$this->db->where('DU.user_id', $filters['user_id']);
		}

		if (!empty($filters['group_id'])) {
			$this->db->where('DU.group_id', $filters['group_id']);
		}

		if (isset($filters['status']) && $filters['status'] !== '') {
			$this->db->where('DU.status', $filters['status']);
		}

		$this->db->order_by('DU.id', 'DESC');
		$users = $this->db->get()->result_array();

		return array(
			'dividendDetail' => $dividend,
			'users' => !empty($users) ? $users : array()
		);
	}

	public function dividendPayoutRequestList($status = '', $limit = 10, $start = 0)
	{
		$this->db->select('
			DU.*,
			D.dividend_year,
			D.percentage,
			D.type,
			U.first_name,
			U.last_name,
			U.email
		');

		$this->db->from('dividend_user DU');
		$this->db->join(
			'dividend D',
			'D.id = DU.dividend_id',
			'left'
		);

		$this->db->join(
			'user U',
			'U.user_id = DU.user_id',
			'left'
		);

		// If specific status is requested
		if ($status !== '') {
			$this->db->where('DU.status', (int) $status);
		} else {
			// Default: only 0, 1, 2
			$this->db->where_in('DU.status', array(0, 1, 2));
		}

		$countQuery = clone $this->db;

		$totalCount = $countQuery
			->select('COUNT(DU.id) as total', false)
			->get()
			->row_array();

		$totalCount = !empty($totalCount['total'])
			? (int) $totalCount['total']
			: 0;

		$this->db->order_by('DU.updated_at', 'DESC');
		$this->db->limit($limit, $start);

		$result = $this->db->get()->result_array();

		$countData = $start + 1;

		if (!empty($result)) {

			foreach ($result as &$row) {

				$row['sno'] = $countData++;

				$row['type_name'] = (
					isset($row['type']) && $row['type'] == 2
				)
					? 'Provident'
					: 'Investment';
			}

			unset($row);
		}

		return array(
			'lists' => !empty($result) ? $result : array(),
			'listCount' => $totalCount,
			'limit' => $limit,
			'start' => $start
		);
	}

	public function updateDividendPayoutRequestStatus($dividendUserId, $requestStatus)
	{
		$request = $this->getPendingDividendPayoutRequest($dividendUserId);

		if (empty($request)) {
			return array(
				'status' => false,
				'message' => 'Dividend payout request not found or already processed.'
			);
		}

		$this->db->trans_begin();

		if ($requestStatus === '1') {
			$balanceAmount = (float) $request['balance_amount'];
			$paidAmount = (float) $request['paid_amount'];

			$update = array(
				'paid_amount' => $paidAmount + $balanceAmount,
				'balance_amount' => 0,
				'status' => 1,
				'updated_at' => date('Y-m-d H:i:s')
			);
		} else {
			$update = array(
				'status' => 0,
				'updated_at' => date('Y-m-d H:i:s')
			);
		}

		$this->db->where('id', $dividendUserId);
		$this->db->update('dividend_user', $update);

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();

			return array(
				'status' => false,
				'message' => 'Failed to update dividend payout request status.'
			);
		}

		$this->db->trans_commit();

		return array(
			'status' => true,
			'request' => $request
		);
	}

	private function getPendingDividendPayoutRequest($dividendUserId)
	{
		$this->db->select('
			DU.*,
			D.dividend_year,
			D.percentage,
			D.type,
			U.first_name,
			U.last_name,
			U.email
		');

		$this->db->from('dividend_user DU');
		$this->db->join('dividend D', 'D.id = DU.dividend_id', 'left');
		$this->db->join('user U', 'U.user_id = DU.user_id', 'left');
		$this->db->where('DU.id', $dividendUserId);
		$this->db->where('DU.status', 2);

		return $this->db->get()->row_array();
	}

	// created by @krishn on 07/08/26
	public function getMyDividendList($userId, $filters = array(), $limit = 10, $start = 0)
	{
		$this->db->select('
			DU.*,
			D.dividend_year,
			D.percentage AS dividend_percentage,
			D.type AS dividend_type,
			CASE
				WHEN D.type = 2 THEN "Provident"
				WHEN D.type = 1 THEN "Investment"
				ELSE "Investment"
			END AS dividend_type_name,
			D.description,

			CASE
				WHEN DU.status = 0 THEN "Rejected"
				WHEN DU.status = 1 THEN "Accepted"
				WHEN DU.status = 2 THEN "Pending"
				WHEN DU.status = 3 THEN "Initiated"
				ELSE "Unknown"
			END AS status_name
		', false);

		$this->db->from('dividend_user DU');
		$this->db->join('dividend D', 'D.id = DU.dividend_id', 'left');
		$this->db->where('DU.user_id', $userId);
		$this->db->where('D.status', 1);

		if (!empty($filters['group_id'])) {
			$this->db->where('DU.group_id', (int) $filters['group_id']);
		}

		if (!empty($filters['dividend_year'])) {
			$this->db->where('D.dividend_year', trim($filters['dividend_year']));
		}

		if (isset($filters['status']) && $filters['status'] !== '') {
			$this->db->where('DU.status', (int) $filters['status']);
		}

		$countQuery = clone $this->db;
		$totalCountData = $countQuery
			->select('COUNT(DU.id) AS total', false)
			->get()
			->row_array();

		$totalCount = !empty($totalCountData['total']) ? (int) $totalCountData['total'] : 0;

		$this->db->order_by('D.dividend_year', 'DESC');
		$this->db->order_by('DU.id', 'DESC');
		$this->db->limit($limit, $start);

		$lists = $this->db->get()->result_array();

		$countData = $start + 1;
		if (!empty($lists)) {
			foreach ($lists as &$row) {
				$row['sno'] = $countData++;
			}
			unset($row);
		}

		// Summary Query
		$this->db->select('
			IFNULL(SUM(DU.total_dividend), 0) AS total_dividend,
			IFNULL(SUM(DU.paid_amount), 0) AS paid_amount,
			IFNULL(SUM(DU.balance_amount), 0) AS balance_amount
		', false);

		$this->db->from('dividend_user DU');
		$this->db->join('dividend D', 'D.id = DU.dividend_id', 'left');
		$this->db->where('DU.user_id', $userId);
		$this->db->where('D.status', 1);

		if (!empty($filters['group_id'])) {
			$this->db->where('DU.group_id', (int) $filters['group_id']);
		}

		if (!empty($filters['dividend_year'])) {
			$this->db->where('D.dividend_year', trim($filters['dividend_year']));
		}

		$summary = $this->db->get()->row_array();

		return array(
			'lists' => !empty($lists) ? $lists : array(),
			'listCount' => $totalCount,
			'limit' => $limit,
			'start' => $start,
			'summary' => !empty($summary) ? $summary : array(
				'total_dividend' => 0,
				'paid_amount' => 0,
				'balance_amount' => 0
			)
		);
	}

	// created by @krishn on 07/08/26
	public function getUserDividendForPayout($dividendUserId, $userId)
	{
		$this->db->select('
			DU.*,
			D.dividend_year,
			D.percentage,
			D.type,
			D.description,
			U.first_name,
			U.last_name,
			U.email
		');

		$this->db->from('dividend_user DU');
		$this->db->join('dividend D', 'D.id = DU.dividend_id', 'left');
		$this->db->join('user U', 'U.user_id = DU.user_id', 'left');
		$this->db->where('DU.id', $dividendUserId);
		$this->db->where('DU.user_id', $userId);
		$this->db->where('D.status', 1);
		$this->db->where('U.status !=', 2);

		return $this->db->get()->row_array();
	}

	// created by @krishn on 07/08/26
	public function applyDividendPayoutRequest($dividendUserId, $userId)
	{
		return $this->common->updateData(
			'dividend_user',
			array(
				'status' => 2,
				'updated_at' => date('Y-m-d H:i:s')
			),
			array(
				'id' => $dividendUserId,
				'user_id' => $userId
			)
		);
	}

	private function getDividendEligibleUsers()
	{
		$sql = "
			SELECT
				U.user_id,
				G.group_id,
				GREATEST(IFNULL(PF.provident_balance, 0), 0) as provident_balance,
				GREATEST(IFNULL(INV.investment_balance, 0), 0) as investment_balance
			FROM user U
			INNER JOIN (
				SELECT user_id, group_id FROM pf_user
				UNION
				SELECT user_id, group_id FROM investment WHERE payment_status = 2 AND status = 1
			) G ON G.user_id = U.user_id
			LEFT JOIN (
				SELECT
					user_id,
					group_id,
					SUM(CASE
						WHEN payment_type = 2 THEN pf_amount
						WHEN payment_type = 1 THEN -pf_amount
						ELSE 0
					END) as provident_balance
				FROM pf_user
				GROUP BY user_id, group_id
			) PF ON PF.user_id = G.user_id AND PF.group_id = G.group_id
			LEFT JOIN (
				SELECT
					user_id,
					group_id,
					SUM(amount) as investment_balance
				FROM investment
				WHERE payment_status = 2
				AND status = 1
				GROUP BY user_id, group_id
			) INV ON INV.user_id = G.user_id AND INV.group_id = G.group_id
			WHERE U.status != 2
			HAVING provident_balance > 0 OR investment_balance > 0
		";

		return $this->db->query($sql)->result_array();
	}

	// // created by @krishn on 13/08/26
	// public function loan_payment_detail($where = "", $options = array(), $limit = '', $start = '')
	// {
	// 	$this->db->select("
	// 		U.first_name,
	// 		U.last_name,
	// 		U.email,

	// 		UL.loan_amount,
	// 		UL.loan_emi,
	// 		UL.loan_type,
	// 		UL.reference_no,

	// 		ULP.id AS payment_id,
	// 		ULP.amount AS payment_amount,
	// 		ULP.payment_method,
	// 		ULP.status AS payment_status,
	// 		ULP.emi_date AS payment_emi_date,
	// 		ULP.created_at AS payment_created_at

	// 	", false);

	// 	$this->db->from('user_loan_payment AS ULP');

	// 	$this->db->join('user AS U', 'U.user_id = ULP.user_id');
	// 	$this->db->where('U.status !=', 2);

	// 	$this->db->join('user_loan AS UL', 'UL.id = ULP.loan_id', 'left');

	// 	if ($where != '') {
	// 		$this->db->where($where);
	// 	}

	// 	$this->db->group_start();

	// 	$this->db->where('ULP.status', 3);

	// 	$this->db->or_group_start();
	// 	$this->db->where('ULP.emi_date <', date('Y-m-d'));
	// 	$this->db->where('ULP.status !=', 1);
	// 	$this->db->group_end();

	// 	$this->db->group_end();

	// 	if (!empty($options) && in_array('count', $options)) {
	// 		$result = $this->db->get()->result_array();
	// 		return count($result);
	// 	}

	// 	$this->db->order_by('ULP.emi_date', 'ASC');
	// 	$this->db->order_by('UL.id', 'DESC');

	// 	if ($limit != '') {
	// 		$this->db->limit($limit, $start);
	// 	}

	// 	$result = $this->db->get()->result_array();

	// 	return !empty($result) ? $result : array();
	// }

	// created by @krishn on 13/08/26
	public function outstanding_loan_payment_detail($where = "", $limit = '', $start = '')
	{
		$this->db->select("
			U.first_name,
			U.last_name,
			U.email,

			UL.loan_amount,
			UL.loan_emi,
			UL.loan_type,
			UL.reference_no,

			ULP.user_id,
			ULP.id AS payment_id,
			ULP.amount AS payment_amount,
			ULP.payment_method,
			ULP.status AS payment_status,
			ULP.emi_date AS payment_emi_date,
			ULP.created_at AS payment_created_at

		", false);

		$this->db->from('user_loan_payment AS ULP');

		// User
		$this->db->join(
			'user AS U',
			'U.user_id = ULP.user_id'
		);

		$this->db->where('U.status !=', 2);
		$this->db->where('ULP.amount >', 0);

		// Loan
		$this->db->join(
			'user_loan AS UL',
			'UL.id = ULP.loan_id',
			'left'
		);

		// Additional filters
		if ($where != '') {
			$this->db->where($where);
		}

		/*
		* Outstanding Loan Payment Logic
		*
		* Status:
		* 0 = Pending
		* 1 = Paid On Time
		* 2 = Paid Late
		* 3 = Missed Payment Deadline
		*
		* Include:
		* 1. Status 3 (Missed Payment Deadline)
		* 2. Status 0 (Pending) AND EMI date has passed
		*
		* Exclude:
		* 1. Status 1 (Paid On Time)
		* 2. Status 2 (Paid Late)
		*/

		$this->db->group_start();

		// Explicitly missed payment
		$this->db->where('ULP.status', 3);

		// OR pending payment whose due date has passed
		$this->db->or_group_start();

		$this->db->where('ULP.status', 0);
		$this->db->where(
			'ULP.emi_date <',
			date('Y-m-d')
		);

		$this->db->group_end();

		$this->db->group_end();

		// Oldest outstanding payment first
		$this->db->order_by('ULP.emi_date', 'ASC');
		$this->db->order_by('ULP.id', 'DESC');

		// Pagination
		if ($limit !== '') {
			$this->db->limit($limit, $start);
		}

		$result = $this->db->get()->result_array();

		return !empty($result) ? $result : array();
	}

	// created by @krishn on 13/08/26
	public function outstanding_emergency_loan_payment_detail($where = "", $limit = '', $start = '')
	{
		$this->db->select("
			U.first_name,
			U.last_name,
			U.email,

			UEL.user_id,
			UEL.id AS payment_id,
			UEL.loan_amount AS payment_amount,
			UEL.pay_by AS payment_date,
			UEL.status AS loan_status,
			UEL.created_at AS payment_created_at,
			UEL.payment_method,
			UEL.paid_status AS payment_status

		", false);

		$this->db->from('user_emergency_loan AS UEL');

		$this->db->join('user AS U', 'U.user_id = UEL.user_id');

		$this->db->where('U.status !=', 2);
		$this->db->where('UEL.loan_amount >', 0);

		// Only consider approved/active emergency loans
		$this->db->where('UEL.status', 4);

		if ($where != '') {
			$this->db->where($where);
		}

		/*
		* Outstanding Emergency Loan Payment:
		*
		* 1. paid_status = 3
		*    -> Missed Payment Deadline
		*
		* 2. paid_status = 0 AND pay_by < today
		*    -> Pending payment whose due date has passed
		*
		* Excluded:
		* 1 = Paid On Time
		* 2 = Paid Late
		*/
		$this->db->group_start();

		// Explicitly missed payment
		$this->db->where('UEL.paid_status', 3);

		$this->db->or_group_start();

		// Pending payment whose due date has passed
		$this->db->where('UEL.paid_status', 0);
		$this->db->where('UEL.pay_by <', date('Y-m-d'));

		$this->db->group_end();

		$this->db->group_end();

		// Oldest outstanding payments first
		$this->db->order_by('UEL.pay_by', 'ASC');
		$this->db->order_by('UEL.id', 'DESC');

		if ($limit !== '') {
			$this->db->limit($limit, $start);
		}

		$result = $this->db->get()->result_array();

		return !empty($result) ? $result : array();
	}

	// created by @krishn on 13/08/26
	public function outstanding_saving_payment_detail($where = "", $limit = '', $start = '', $savingType = '') {
		$this->db->select("
			U.first_name,
			U.last_name,
			U.email,

			GL.group_type_id,
			GL.start_date,
			GL.end_date,

			UGL.user_id,
			UGL.id AS payment_id,
			UGL.amount AS payment_amount,
			UGL.date AS monthly_payment_date,
			UGL.status AS payment_status,
			UGL.created_at AS payment_created_at,
			UGL.payment_method

		", false);

		$this->db->from('user_group_lifecycle AS UGL');

		$this->db->join(
			'user AS U',
			'U.user_id = UGL.user_id',
			'inner'
		);

		$this->db->join(
			'group_lifecycle AS GL',
			'GL.id = UGL.groupLifecycle_id',
			'left'
		);

		/*
		* Only active users
		*/
		$this->db->where('U.status !=', 2);
		$this->db->where('UGL.amount >', 0);

		/*
		* Only active group lifecycle
		*/
		$this->db->where('GL.status', 1);

		/*
		* Saving Product Type
		*
		* 1 = Simple Saving
		* 2 = Saving JNR
		* 4 = Welfare
		* etc.
		*/
		if ($savingType !== '' && $savingType !== null) {
			$this->db->where(
				'GL.group_type_id',
				(int) $savingType
			);
		}

		/*
		* Additional filters
		*/
		if (!empty($where)) {
			$this->db->where($where);
		}

		/*
		* =====================================================
		* OUTSTANDING PAYMENT LOGIC
		* =====================================================
		*
		* Status:
		*
		* 1 = Pending
		* 2 = Paid On Time
		* 3 = Missed Payment Deadline
		* 4 = Paid Late
		*
		* Outstanding means:
		*
		* 1. Pending payment whose due date has passed
		* 2. Missed payment (status = 3)
		*
		* Exclude:
		*
		* 2 = Paid On Time
		* 4 = Paid Late
		*/

		$this->db->group_start();

		/*
         * Pending payment
         * Include only when payment date has passed.
         */
		$this->db->group_start();

		$this->db->where('UGL.status', 1);
		$this->db->where('UGL.date <', date('Y-m-d'));

		$this->db->group_end();

		/*
         * Missed payment deadline
         */
		$this->db->or_where('UGL.status', 3);

		$this->db->group_end();

		/*
		* Sort oldest outstanding payment first
		*/
		$this->db->order_by('UGL.date', 'ASC');
		$this->db->order_by('UGL.id', 'DESC');

		/*
		* Pagination
		*/
		if ($limit !== '' && $limit !== null) {
			$this->db->limit(
				(int) $limit,
				(int) $start
			);
		}

		$result = $this->db->get()->result_array();

		return !empty($result) ? $result : array();
	}

	public function outstanding_miscellaneous_payment_detail($where = "", $limit = '', $start = '')
	{
		$this->db->select("
			U.first_name,
			U.last_name,
			U.email,

			UM.title,
			UM.description,
			UM.tenure,

			UMP.user_id,
			UMP.group_id,
			UMP.id AS payment_id,
			UMP.loan_id AS miscellaneous_id,
			UMP.amount AS payment_amount,
			UMP.payment_method,
			UMP.status AS payment_status,
			UMP.emi_date AS payment_emi_date,
			UMP.created_at AS payment_created_at

		", false);

		$this->db->from('user_miscellaneous_payment AS UMP');

		// User
		$this->db->join(
			'user AS U',
			'U.user_id = UMP.user_id'
		);

		$this->db->where('U.status !=', 2);
		$this->db->where('UMP.amount >', 0);

		// Miscellaneous
		$this->db->join(
			'user_miscellaneous AS UM',
			'UM.id = UMP.loan_id',
			'left'
		);

		// Additional filters
		if ($where != '') {
			$this->db->where($where);
		}

		/*
		* Outstanding Miscellaneous Payment Logic
		*
		* Status:
		* 0 = Pending
		* 1 = Paid On Time
		* 2 = Paid Late
		* 3 = Missed Payment Deadline
		*
		* Include:
		* 1. Status 3 (Missed Payment Deadline)
		* 2. Status 0 (Pending) AND EMI date has passed
		*
		* Exclude:
		* 1. Status 1 (Paid On Time)
		* 2. Status 2 (Paid Late)
		*/

		$this->db->group_start();

		// Explicitly missed payment
		$this->db->where('UMP.status', 3);

		// OR pending payment whose due date has passed
		$this->db->or_group_start();

		$this->db->where('UMP.status', 0);
		$this->db->where(
			'UMP.emi_date <',
			date('Y-m-d')
		);

		$this->db->group_end();

		$this->db->group_end();

		// Oldest outstanding payment first
		$this->db->order_by('UMP.emi_date', 'ASC');
		$this->db->order_by('UMP.id', 'DESC');

		// Pagination
		if ($limit !== '' && $limit !== null) {
			$this->db->limit(
				(int) $limit,
				(int) $start
			);
		}

		$result = $this->db->get()->result_array();

		return !empty($result) ? $result : array();
	}
}
