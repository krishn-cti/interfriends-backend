<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


function get_restaurant_details($restaurant_id)
{
   $ci =& get_instance();
   $where = "R.restaurant_id ='".$restaurant_id."'";
   $info  = $ci->restaurant_model->restaurant_detail($where,array('single'));
   
   if(!empty($info))
   {
      if(!empty($info['profile_image']))
      {
         $info['profile_image'] = base_url($info['profile_image']);
         $info['profile_image_thumb'] = base_url($info['profile_image_thumb']);
      }
      else
      {
         $info['profile_image'] = "";
         $info['profile_image_thumb'] = "";
      }
   }
   else
   {
      $info = array();
   }
   
   return $info;
}

