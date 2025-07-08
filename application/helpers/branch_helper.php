<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function get_branch_detail($branch_id)
{
   $ci =& get_instance();
   $result = $ci->branch_model->get_branch_by_lat_long(array("branch_id" => $branch_id),array("single"));

   if(!empty($result))
   {
      $time_table_result = getTimeTable($branch_id);
      $result["time_schedule"] =  $time_table_result["time_schedule"];
      $result["store_status"] =  $time_table_result["store_status"];

      if(!empty($result["profile_image"]))
      {
         $result['profile_image'] = base_url($result['profile_image']);
         $result['profile_image_thumb'] = base_url($result['profile_image_thumb']);
      }
   }
   else
   {
      $result = array();
   }

   return $result;
}



function getTimeTable($branch_id)
{
   $ci =& get_instance();
   $branch_time_schedule = $ci->common->getData('time_schedule_tbl',array('branch_id' => $branch_id));

   // main array create
   $time_table_array = array();
   $store_status ="";


   if(!empty($branch_time_schedule))
   {
      $current_day = date('l');
      $current_time = date('H:i:s');
      
      // check day 
      foreach($branch_time_schedule as $key => $value) 
      {
         if($current_day == $value['day'])
         {
            // check start time and end time 
            if($current_time >= $value['start_time'] &&  $current_time <= $value['end_time'])
            {
               $store_status = "Open";
            }
         }
      }

      if($store_status == "")
      {
         $store_status = "Closed";
      }

      $time_table_array['store_status'] = $store_status;
      $time_table_array['time_schedule'] = $branch_time_schedule;
   }
   else
   {
      $time_table_array['store_status'] = "";
      $time_table_array['time_schedule'] = array();
   }

   return $time_table_array;
}



function categoryListFunction()
{
   $ci =& get_instance();
   
   $where = "status = 1";
   $result = $ci->common->getData('category',$where);

   if(!empty($result))
   {
      foreach ($result as $key => $value) 
      {
         if(!empty($result[$key]['category_image']))
         {
            $result[$key]['category_image'] = base_url($value['category_image']);
            $result[$key]['category_image_thumb'] = base_url($value['category_image_thumb']);
         }
         
      }

      return $result;
   }
   else
   {
      return array();
   }
}

