<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


function dishesDetailFunction($dishes_id)
{
   $ci =& get_instance();
   $where = "dishes_id = '". $dishes_id ."'";
   $result = $ci->common->getData('dishes',$where, array("single"));

   if(!empty($result["image"]))
   {
      $result['image'] = base_url($result['image']);
      $result['image_thumb'] = base_url($result['image_thumb']);
   }

      
   $where_add_on = "dishes_id = '". $dishes_id ."'";
   $result_add_on = $ci->common->getData('add_on',$where_add_on);

   foreach ($result_add_on as $key => $value) 
   {
      $where_add_on = "add_on_id = '". $value['add_on_id'] ."'";
      $result_add_on[$key]["add_on_attribute"] = $ci->common->getData('add_on_attribute',$where_add_on);
   }

   $result["add_on"] = $result_add_on;

   return $result;
}