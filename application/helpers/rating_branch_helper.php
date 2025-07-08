<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


function rating_count($branch_id)
{
   $ci =& get_instance();
   $count_user = $ci->common->getData('branch_rating',array('branch_id'=>$branch_id),array('count'));

   if($count_user)
   {
      $query="SELECT SUM(`rating`) AS rating_count FROM branch_rating  WHERE branch_id='".$branch_id."'";
      $total_rating = $ci->common->query($query,array('single'));
      if(!empty($total_rating))
      {
         $total_rating_user = $total_rating['rating_count'];
         $avg=$total_rating_user/$count_user;
      }
      else
      {
         $avg = 0;
      }  
   }
   else
   {
      $avg = 0;
   }
   return $avg;

}



function review_count($branch_id)
{
   $ci =& get_instance();
   $review_count = $ci->common->getData('branch_rating',array('branch_id'=>$branch_id),array('count'));
   return $review_count;

}