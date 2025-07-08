<div class="tables">
    <h2 class="title1"><?= $title; ?></h2>
    <?= $this->session->flashdata('msg'); ?>
    <div class="bs-example widget-shadow"> 
        
        <table class="table table-bordered" id="example"> 
            <thead> 
                <tr> 
                    <th>#</th>
                    <th>User Image</th>
                    <th>Username</th>
                    <th>phone</th>
                    
                    <th>Action</th>
                </tr> 
            </thead> 
            <tbody> 
                <?php if (!empty($user)){                                                
                foreach ($user as $key => $value) { ?>
                    <tr>
                        <th scope="row"><?= ++$key; ?></th>
                        <td>
                        <?php
                        if(!empty($value['user_image']))
                            {
                        ?>
                            <img src="<?php echo base_url('/assets/userfile/profile/'.$value['user_image']); ?>" height="150px" width="100px">
                        <?php
                        }
                        ?>
                        </td>
                        <td><?= $value['username']; ?></td>
                        <td><?= $value['phone']; ?></td>
                        
                        <td>
                            <a href="<?= base_url($link.$value['id']); ?>" class="btn btn-primary" ><i class="fa fa-eye"></i></a> &nbsp;
                        


                             <?php $status = $value['status']; ?>
                                <?php if ($status == 1) { ?>
                                <a href="<?php echo base_url('admin/change_status') . '/id/' . $value['id']; ?>/user/status/0/userList" class="btn btn-danger">BLOCK</a>
                                <?php } elseif ($status == 0) { ?>
                                <a href="<?php echo base_url('admin/change_status').'/id/'.$value['id']; ?>/user/status/1/userList" class="btn btn-success">UNBLOCK</a>
                                <?php } ?>



                        </td>      
                    </tr>
                <?php }
            } ?>
            </tbody> 
        </table>
    </div>
</div>


