<?php 
(defined('BASEPATH')) OR exit('No direct script access allowed');

class Superadmin_Controller extends Base_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->checkLogin();
	}

	public function checkLogin()
	{
		$admin = $this->session->userdata('super');
		if(!$admin){
			redirect(base_url('superadmin_login'));
		}
	}
    
  
}