<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


function get_user_details($user_id) {
   $ci =& get_instance();
   $where = "U.user_id ='".$user_id."'";
   $info  = $ci->user_model->user_detail($where,array('single'));
   

   if(!empty($info)) {
      if(!empty($info['profile_image'])) {
         $info['profile_image'] = base_url($info['profile_image']);
      } else {
         $info['profile_image'] ="assets/img/default-user-icon.jpg";
      }


      if(!empty($info['profile_image_thumb'])) {
         $info['profile_image_thumb'] = base_url($info['profile_image_thumb']);
      } else {
         $info['profile_image_thumb'] ="assets/img/default-user-icon.jpg";
      }

      if(!empty($info['id_proof_image'])) {
         $info['id_proof_image'] = base_url($info['id_proof_image']);
      } else {
         $info['id_proof_image'] ="assets/img/blank.webp";
      }
   } else {
      $info = array();
   }
   return $info;
}


function safeKeepingTotal($group_id, $user_id) {
   $ci =& get_instance();
   $wherePlus = "group_id = '". $group_id ."' AND user_id = '". $user_id ."' AND pyment_type = '2'";
   $resultPlus = $ci->common->getData('safe_keeping',$wherePlus,array("field" => 'sum(amount) as total_payment',"single"));

   if(!empty($resultPlus['total_payment'])) {
      $plusAmount = $resultPlus['total_payment'];
   } else {
      $plusAmount = 0.00;
   }

   $whereSub = "group_id = '". $group_id ."' AND user_id = '". $user_id ."' AND pyment_type = '1'";
   $resultSub = $ci->common->getData('safe_keeping',$whereSub,array("field" => 'sum(amount) as total_payment',"single"));

   if(!empty($resultSub['total_payment'])) {
      $subAmount = $resultSub['total_payment'];
   } else {
      $subAmount = 0.00;
   }

   $amount =  $plusAmount - $subAmount;
   
   return $amount;
}


function pfTotal($group_id, $user_id) {
   $ci =& get_instance();
   $wherePlus = "group_id = '". $group_id ."' AND user_id = '". $user_id ."' AND payment_type = '2'";
   $resultPlus = $ci->common->getData('pf_user',$wherePlus,array("field" => 'sum(pf_amount) as total_payment',"single"));

   if(!empty($resultPlus['total_payment'])) {
      $plusAmount = $resultPlus['total_payment'];
   } else {
      $plusAmount = 0.00;
   }

   $whereSub = "group_id = '". $group_id ."' AND user_id = '". $user_id ."' AND payment_type = '1'";
   $resultSub = $ci->common->getData('pf_user',$whereSub,array("field" => 'sum(pf_amount) as total_payment',"single"));

   if(!empty($resultSub['total_payment'])) {
      $subAmount = $resultSub['total_payment'];
   } else {
      $subAmount = 0.00;
   }

   $amount =  $plusAmount - $subAmount;
   
   return $amount;
}


function session_manage($user_id,$session_id)
{
   $ci =& get_instance();
   
   // blank session set
   $ci->common->updateData('user',array('session_id' => ""),array('session_id' => $session_id));

   //session set
   $ci->common->updateData('user',array('session_id' => $session_id),array('user_id' => $user_id));
}

