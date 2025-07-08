<aside class="sidebar-left">
  <nav class="navbar navbar-inverse">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".collapse" aria-expanded="false">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      </button>
      <h1><a class="navbar-brand" href="<?= base_url('superadmin/dashboard'); ?>"><span class="fa fa-car"></span> Taxi App<span class="dashboard_text">Taxi app</span></a></h1>
    </div>
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="sidebar-menu">
        <li class="header">MAIN NAVIGATION</li>

        <li class="treeview">
          <a href="<?= base_url('superadmin/dashboard'); ?>">
          <i class="fa fa-dashboard"></i> <span>Dashboard</span>
          </a>
        </li>

        <li class="treeview">
          <a href="#">
          <i class="fa fa-users"></i>
          <span>Company</span>
          <i class="fa fa-angle-left pull-right"></i>
          </a>
          <ul class="treeview-menu">
            <li><a href="<?= base_url('superadmin/companyList'); ?>"><i class="fa fa-angle-right"></i> Company List</a></li>
            <li><a href="<?= base_url('superadmin/addCompany'); ?>"><i class="fa fa-angle-right"></i> Add Company</a></li>
          </ul>
        </li> 

        <li class="treeview">
          <a href="#">
          <i class="fa fa-car"></i>
          <span>Vehicles Class</span>
          <i class="fa fa-angle-left pull-right"></i>
          </a>
          <ul class="treeview-menu">
            <li><a href="<?= base_url('superadmin/addVehicleClass'); ?>"><i class="fa fa-angle-right"></i> Add Vehicle Class</a></li>
            <li><a href="<?= base_url('superadmin/vehicleClass'); ?>"><i class="fa fa-angle-right"></i> Vehicle Class List</a></li>            
          </ul>
        </li>        

        <li class="treeview">
          <a href="<?= base_url('superadmin/userList'); ?>">
          <i class="fa fa-users"></i> <span>User List</span>
          </a>
        </li>
               
        <li>
          <a href="#">
          <i class="fa fa-th"></i> <span>Widgets</span>
          <small class="label pull-right label-info">08</small>
          </a>
        </li>
        <li class="treeview">
          <a href="#">
          <i class="fa fa-dollar"></i> <span>Payment</span>
          <i class="fa fa-angle-left pull-right"></i>
          </a>
          <ul class="treeview-menu">
            <li><a href="#"><i class="fa fa-angle-right"></i> Payment Report</a></li>
          </ul>
        </li>
       
        <li class="header">LABELS</li>
        <li><a href="#"><i class="fa fa-angle-right text-red"></i> <span>Important</span></a></li>
        <li><a href="#"><i class="fa fa-angle-right text-yellow"></i> <span>Warning</span></a></li>
        <li><a href="#"><i class="fa fa-angle-right text-aqua"></i> <span>Information</span></a></li>
      </ul>
    </div>
      <!-- /.navbar-collapse -->
  </nav>
</aside>
  </div>

  </div>
<!--left-fixed -navigation-->

<!-- header-starts -->
<div class="sticky-header header-section ">
  <div class="header-left">
    <!--toggle button start-->
    <button id="showLeftPush"><i class="fa fa-bars"></i></button>
    <!--toggle button end-->
    <div class="profile_details_left"><!--notifications of menu start -->      
      <div class="clearfix"> </div>
    </div>
    <!--notification menu end -->
    <div class="clearfix"> </div>
  </div>
  <div class="header-right">
    
    
    <!--search-box-->
   <!--  <div class="search-box">
      <form class="input">
        <input class="sb-search-input input__field--madoka" placeholder="Search..." type="search" id="input-31" />
        <label class="input__label" for="input-31">
          <svg class="graphic" width="100%" height="100%" viewBox="0 0 404 77" preserveAspectRatio="none">
            <path d="m0,0l404,0l0,77l-404,0l0,-77z"/>
          </svg>
        </label>
      </form>
    </div> --><!--//end-search-box-->
    
    <div class="profile_details">   
      <ul>
        <li class="dropdown profile_details_drop">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
            <div class="profile_img"> 
              <span class="prfil-img"><img src="<?= base_url('assets/images/2.jpg'); ?>" alt=""> </span> 
              <div class="user-name">
                <p>Super Admin </p>
                <span>Administrator</span>
              </div>
              <i class="fa fa-angle-down lnr"></i>
              <i class="fa fa-angle-up lnr"></i>
              <div class="clearfix"></div>  
            </div>  
          </a>
          <ul class="dropdown-menu drp-mnu">
            <!-- <li> <a href="#"><i class="fa fa-cog"></i> Settings</a> </li> 
            <li> <a href="#"><i class="fa fa-user"></i> My Account</a> </li> 
            <li> <a href="#"><i class="fa fa-suitcase"></i> Profile</a> </li>  -->
            <li> <a href="<?= base_url('superadmin_login/logout'); ?>"><i class="fa fa-sign-out"></i> Logout</a> </li>
          </ul>
        </li>
      </ul>
    </div>
    <div class="clearfix"> </div>       
  </div>
  <div class="clearfix"> </div> 
</div>

<link rel="stylesheet" type="text/css" href="<?= base_url('assets/bootstap-validator/bootstrapValidator.min.js'); ?>">
<script type="text/javascript" src="<?= base_url('assets/bootstap-validator/bootstrapValidator.min.js'); ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/bootstap-validator/customValidation.js'); ?>"></script>

<div id="page-wrapper">
    <div class="main-page">