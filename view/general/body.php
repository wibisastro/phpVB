<!DOCTYPE html>
<html lang="en">
<?include (viwpath."/general/head.php");?>
<body class="theme-whbl" onload="checkService()">
<iframe id="iframer" name="iframer" src="" frameborder="1" style="display:<?if ($debug) {echo "inline";} else {echo "none";}?>" width="300" height="200"></iframe>
  <div id="theme-wrapper">
  <?include (viwpath."/general/topbar.php");?>
  
  <div id="page-wrapper" class="container">
			<div class="row">
      <?include (viwpath."/general/sidebar.php");?>
      
      
          <!--content-->
          <div id="content-wrapper">
            <div class="row">
              <div class="col-lg-12">
                        <?
                        if (is_array($doc->content) && !$doc->error)  {
                            ?>
                            <header class="main-box-header clearfix">
                                <h1><?echo $doc->pagetitle;?></h1>
                            </header> 
                            <?
                            while (list($key,$val)=each($doc->content)) {
                                if ($val && file_exists($val)) {include($val);}
                                else {echo $val;}
                            }
                        } elseif (!$doc->error  && !$doc->content) {?>
                        <header class="main-box-header clearfix">
                            <h1>Under Development</h1>
                        </header> 
                        <?} elseif (!$doc->error && $doc->content) {?>
                            <h1>Var Dump Page</h1>
                            <div class="main-box clearfix">
                                <header class="main-box-header clearfix">
                                    <h1><?echo strip_tags($cmd);?></h1>
                                </header>
                                <div class="main-box-body clearfix">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <?echo $doc->content;?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?} else {?>
                            <h1>Error</h1>
                            <div class="main-box clearfix">
                                <header class="main-box-header clearfix">
                                    <h1><?echo $doc->error;?></h1>
                                </header>
                                <div class="main-box-body clearfix">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <?echo $doc->error_message;?>
                                            <p>
                                                <a href="index.php">Back to Home</a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?}?>
              </div>
            </div>		
          </div>              
  		</div>
	</div>
  
  </div>

	
	<!-- global scripts -->
	<script src="js/jquery.js"></script>
	<script src="js/bootstrap.js"></script>
	<script src="js/jquery.nanoscroller.min.js"></script>	
	
  <!-- product page specific scripts -->
	<script src="js/footable.js"></script>
	<script src="js/footable.sort.js"></script>
	<script src="js/footable.paginate.js"></script>
	<script src="js/footable.filter.js"></script>

  <!--wizard-->
  <script src="js/wizard.js"></script>
	<script src="js/jquery.maskedinput.min.js"></script>
 
  <!-- theme scripts -->
	<script src="js/scripts.js"></script>
	<script src="js/pace.min.js"></script>
  
  <!-- JS Files -->
  <script src="js/image-picker.js"></script>
  <script src="js/parsley.min.js"></script>
  <script src="js/form-validation.js"></script>
  
    
  <!-- table page specific inline scripts -->
	<script type="text/javascript">
        $(document).ready(function() {
            $('.footable').footable();
        });
        $(document).ready(function() {
            $('.footable-order').footable();
        });
	</script>
    

    <script type="text/javascript" src="http://standar.gov2.web.id/js/iframeResizer.contentWindow.min.js"></script>
</body>
</html>