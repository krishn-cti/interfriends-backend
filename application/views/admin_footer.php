</div>
    </div>
<!--footer-->
    <div class="footer">
       <p>&copy; <?php echo Date('Y');?> Pavone App. All Rights Reserved </p>       
    </div>
    <!--//footer-->
    </div>
        
    <!-- new added graphs chart js-->
    
    <script src="<?= base_url('assets/js/Chart.bundle.js'); ?>"></script>
    <script src="<?= base_url('assets/js/utils.js'); ?>"></script>
   
    <!-- new added graphs chart js-->
    
    <!-- Classie --><!-- for toggle left push menu script -->
        <script src="<?= base_url('assets/js/classie.js'); ?>"></script>
        <script>
            var menuLeft = document.getElementById( 'cbp-spmenu-s1' ),
                showLeftPush = document.getElementById( 'showLeftPush' ),
                body = document.body;


                
            showLeftPush.onclick = function() {
                classie.toggle( this, 'active' );
                classie.toggle( body, 'cbp-spmenu-push-toright' );
                classie.toggle( menuLeft, 'cbp-spmenu-open' );
                disableOther( 'showLeftPush' );
            };
            

            function disableOther( button ) {
                if( button !== 'showLeftPush' ) {
                    classie.toggle( showLeftPush, 'disabled' );
                }
            }
        </script>
    <!-- //Classie --><!-- //for toggle left push menu script -->
        
  
    <!-- side nav js -->
    <script src='<?= base_url('assets/js/SidebarNav.min.js'); ?>' type='text/javascript'></script>
    <script>
      $('.sidebar-menu').SidebarNav()
    </script>
    <!-- //side nav js -->
    
    <!-- for index page weekly sales java script -->
    <script src="<?= base_url('assets/js/SimpleChart.js'); ?>"></script>
    
    <!-- //for index page weekly sales java script -->
    
    
    <!-- Bootstrap Core JavaScript -->
   <script src="<?= base_url('assets/js/bootstrap.js'); ?>"> </script>
    <!-- //Bootstrap Core JavaScript -->

    <!-- datatable -->
    <script type="text/javascript" src="<?= base_url('assets/vendor/datatable/datatables.min.js'); ?>"></script>

    <!-- time picker start -->
     <script type="text/javascript" src="<?= base_url('assets/timepicker/jquery-timepicker.js'); ?>"></script>
    <!-- time picker end -->
    
    <!-- ck editor start -->
    <script type="text/javascript" src="<?= base_url('assets/ckeditor/ckeditor.js'); ?>"></script>
    <!-- ck editor end -->

</body>
</html>