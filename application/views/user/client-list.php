<?php
echo"<pre>";
print_r($client);
die();

?>

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
                        if(!empty($value['profile_image']))
                            {
                        ?>
                            <img src="<?php echo base_url('/assets/clientfile/profile/'.$value['profile_image']); ?>" height="150px" width="100px">
                        <?php
                        }
                        ?>
                        </td>
                        <td><?= $value['full_name']; ?></td>
                        <td><?= $value['phone_number']; ?></td>
                        
                        <td>
                            <a href="<?= base_url($link.$value['client_id']); ?>" class="btn btn-primary" ><i class="fa fa-eye"></i></a> &nbsp;
                        


                             <?php $status = $value['status']; ?>
                                <?php if ($status == 1) { ?>
                                <a href="<?php echo base_url('admin/change_status') . '/client_id/' . $value['client_id']; ?>/client/status/0/clientList" class="btn btn-danger">BLOCK</a>
                                <?php } elseif ($status == 0) { ?>
                                <a href="<?php echo base_url('admin/change_status').'/client_id/'.$value['client_id']; ?>/client/status/1/clientList" class="btn btn-success">UNBLOCK</a>
                                <?php } ?>



                        </td>      
                    </tr>
                <?php }
            } ?>
            </tbody> 
        </table>
    </div>
</div>


