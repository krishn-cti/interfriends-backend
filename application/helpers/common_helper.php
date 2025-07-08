<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


	function send_otp($phone_number)
	{
		//Parse
		//Sinch API Details
		$key = "11b56022-f833-4f80-96ea-9cdb5100628c";    
		 $secret = "oQItpJBAjEKspSfeK+sPGA=="; 
	
		//Query
		$user = "application\\" . $key . ":" . $secret;    
		$message = array('identity'=>array('type'=>'number','endpoint'=>$phone_number),'metadata'=>array('os'=>'rest','platform'=>'N/A'),'method'=>'sms');    
		$data = json_encode($message);    
        //https://verificationapi-v1.sinch.com/verification/v1/verifications
		$ch = curl_init('https://verificationapi-v1.sinch.com/verification/v1/verifications');    
		curl_setopt($ch, CURLOPT_POST, true);    
		curl_setopt($ch, CURLOPT_USERPWD,$user);    
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);    
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));    
		//Results
		$result = curl_exec($ch);    
		if(curl_errno($ch)) {    
		     // echo 'Curl error: ' . curl_error($ch);    
		} else {    
		     // echo $result;    
		}   
		curl_close($ch);
	}


	function verify_otp($mobile_otp, $phone_number) {
        $key = "11b56022-f833-4f80-96ea-9cdb5100628c";    
        $secret = "oQItpJBAjEKspSfeK+sPGA=="; 
    
        //Query
        $user = "application\\" . $key . ":" . $secret;
        $message = array('method'=>'sms','sms'=>array('code'=>$mobile_otp));    
        $data = json_encode($message);    
        $ch = curl_init('https://verificationapi-v1.sinch.com/verification/v1/verifications/number/'.$phone_number);    
        //curl_setopt($ch, CURLOPT_POST, true); 
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");   
        curl_setopt($ch, CURLOPT_USERPWD,$user);    
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));    
        //Results
        $result = curl_exec($ch);    
        if(curl_errno($ch)) {    
              //echo 'Curl error: ' . curl_error($ch);    
        } else {    
             // echo $result;    
        }   
        curl_close($ch);

        $res=json_decode($result,true);
        
        return $res;
    }




