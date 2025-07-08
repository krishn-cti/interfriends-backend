<?php
$config = [
	'add_doctor'	=>	[	
							[
								'field'	=>	'name',
								'label'	=>	'Name',
								'rules'	=>	'trim|required',
							],								
							[
								'field'	=>	'latitude',
								'label'	=>	'Latitude',
								'rules'	=>	'trim|required',
							],
							[
								'field'	=>	'longitude',
								'label'	=>	'Longitude',
								'rules'	=>	'trim|required|is_unique[doctor.email]',
							],
							[
								'field'	=>	'phone',
								'label'	=>	'Phone Number',
								'rules'	=>	'required|numeric',
							],
						],

	'add_resource'	=>	[	
							[
								'field'	=>	'name',
								'label'	=>	'Name',
								'rules'	=>	'trim|required',
							],								
							// [
							// 	'field'	=>	'email',
							// 	'label'	=>	'Email',
							// 	'rules'	=>	'trim|required|is_unique[resource.email]',
							// ],
							[
								'field'	=>	'phone',
								'label'	=>	'Phone Number',
								'rules'	=>	'required|numeric',
							],
						],
	'admin_login'	=>	
						[
							[
								'field'	=>	'username',
								'label'	=>	'User Name',
								'rules'	=>	'trim|required|is_unique[user.uname]|min_length[3]'
							],
							[
								'field'	=>	'password',
								'label'	=>	'Password',
								'rules'	=>	'required',
							]
						],



];