<?php 
(defined('BASEPATH')) OR exit('No direct script access allowed');

class Admin_Controller extends Base_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->checkLogin();
	}

	public function checkLogin()
	{
		$admin = $this->session->userdata('admin');
		if(!$admin){
			redirect(base_url('admin_login'));
		}
	}
    
  	public function addVhicleCompanyName()
  	{
  		$result = $this->common->insertData('vehicle_company',$_POST);
  		if($result){
  			echo "1";
  		}else{
  			echo "0";
  		}
  	}

  	public function getVehicleCompany()
  	{
  		$vehicle_company = $this->common->getData('vehicle_company');
  		$option = '<option selected disabled>Select Vehicle Company</option>
							<option value="0">Add New Company</option>';
  		if($vehicle_company){
  			foreach ($vehicle_company as $key => $value) {
  				$option .= '<option value="'.$value['id'].'">'.$value['company'].'</option>';
  			}
  		}
  		echo $option;
  	}

  	public function getVehicleModel()
  	{
  		$model = $this->common->getDat('vehicle_model',array('vehicle_id' => $_POST['id']));
  		$option = '<option selected disabled>Select Vehicle Model</option>
								<option value="0">Add New Vehicle Model</option>';
  		if($vehicle_model){
  			foreach ($vehicle_model as $key => $value) {
  				$option .= '<option value="'.$value['id'].'">'.$value['model'].'</option>';
  			}
  		}
  		echo $option;
  	}

  	public function addVehicleModel()
  	{  		
  		$result = $this->common->insertData('vehicle_model',$_POST,array());
  		if($result){
  			$this->getVehicleModel($_POST['vehicle_id']);
  		}else{
  			echo "0";
  		}
  	}

}