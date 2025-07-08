

<div class="tables">
    <h2 class="title1"><?= $title; ?></h2>
    <?= $this->session->flashdata('msg'); ?>
    <div class="bs-example widget-shadow"> 
        
        <table class="table table-bordered" id="example"> 
            <thead> 
                <tr> 
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    
                    <th>Created At</th> 
                    <th>Action</th> 
                </tr> 
            </thead> 
            <tbody> 
                <?php if (!empty($dropshipper)){                                                
                foreach ($dropshipper as $key => $value) { ?>
                    <tr>
                        <th scope="row"><?= ++$key; ?></th>
                        <td><?= $value['name']; ?></td>
                        <td><?= $value['email']; ?></td>
                        
                        <td><?= date('dS M Y',strtotime($value['created_at'])); ?></td>
                        <td>
                            <a href="<?= base_url($link.$value['id']); ?>" class="btn btn-primary" ><i class="fa fa-eye"></i></a> &nbsp;
                            <a href="<?= base_url('admin/block/user/'.$value['id'].'/'.uri_string()); ?>" class="btn btn-<?php if($value['status'] == 0){ echo 'danger'; }else{ echo 'success'; } ?>" ><?php if($value['status'] == 0){ echo 'Block'; }else{ echo 'Unblock'; } ?></a> &nbsp;
                        </td>                        
                    </tr>
                <?php }
            } ?>
            </tbody> 
        </table>
    </div>
</div>


