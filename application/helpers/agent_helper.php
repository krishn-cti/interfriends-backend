<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


function get_agent_details($agent_id)
{
   $ci =& get_instance();
   $where = "A.agent_id ='".$agent_id."'";
   $info  = $ci->agent_model->agent_detail($where,array('single'));

   $where_location = "A.agent_id ='".$agent_id."'";
   $info['location_info'] = $ci->agent_model->agentLocation($where_location,array('single'));

   if(!empty($info))
   {
      if(!empty($info['profile_image']))
      {
         $info['profile_image'] = base_url($info['profile_image']);
      }
      else
      {
         $info['profile_image'] = "";
      }
   }
   else
   {
      $info = array();
   }
   
   return $info;
}


function session_manage($agent_id,$session_id)
{
   $ci =& get_instance();
   
   // blank session set
   $ci->common->updateData('agent',array('session_id' => ""),array('session_id' => $session_id));

   //session set
   $ci->common->updateData('agent',array('session_id' => $session_id),array('agent_id' => $agent_id));
}

