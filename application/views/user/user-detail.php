
<style type="text/css">
.iii img {
    width: 100%; max-height:300px;
 }
</style>
<div class="forms">
    <h2 class="title1"><?= $title; ?></h2>

    <div class="alert alert-dismissible fade" role="alert"><span id="msg"></span>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>

    <div class="row">
        <div class="form-three widget-shadow">                                  
            <div class="row">
                <div class="col-md-12">
                    <h3>Basic Detail</h3><br>
                </div>
                <div class="col-md-4">
                    <div>
                        <?php
                        if(!empty($user['user_image']))
                            {
                        ?>
                            <img class="img-responsive" src="<?php echo base_url('/assets/userfile/profile/'.$user['user_image']); ?>" height="250px" width="200px">
                        <?php
                        }
                        ?>
                        <br/><br/>
                    </div >
                </div>
                <div class="col-md-8">                    
                    <dl class="dl-horizontal">
                        <dt>User ID </dt> <dd><?= $user['id']; ?></dd>
                        <dt>Username</dt> <dd><?= $user['username']; ?></dd>
                        <dt>Phone </dt> <dd><?= $user['phone']; ?></dd>
                        <dt>DOB </dt> <dd> <?= $user['dob']; ?></dd>
                        <dt>Gender </dt> <dd><?= $user['gender']; ?></dd>
                        <dt>Identity Tag</dt> <dd><?= $user['identity_tag']; ?></dd>
                        <dt>Hobby </dt> <dd><?= $user['hobby']; ?></dd>
                        <dt>User Coin </dt> <dd> <?= $user['user_coin']; ?></dd> 
                        <dt>Created at </dt> <dd> <?= $user['created_at']; ?></dd>
                        <dt>Country Name </dt> <dd> <?= $user['nicename']; ?></dd>   
                    </dl>
                </div>
                </div>
          
        </div>        
    </div>
</div>






