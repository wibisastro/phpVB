<?if (!$_SESSION['active_client'] || ($_SESSION['active_client'] && basename($_SERVER['SCRIPT_NAME'])=='cloud.php')) {$fullpage=true;}?>
<!DOCTYPE html>
<html lang="en">
<?include (VIWPATH."/general/header.php");?>
<body class="theme-turquoise <?if ($fullpage) {?>fixed-footer fixed-header<?}?>">
  <div id="theme-wrapper">
  <?if ($fullpage) include (VIWPATH."/general/topbar.php");?>
    <div id="page-wrapper" class="container">
      <div class="row">
      <?if ($fullpage) include (VIWPATH."/general/sidebar.php");?>
          <!--content-->
          <div id="content-wrapper">
            <iframe id="iframer" name="iframer" src="" frameborder="1" style="display:<?if ($_GET['debug']) {echo "inline";} else {echo "none";}?>" width="300" height="200"></iframe>
            <div class="row">
              <div class="col-lg-12">
                <?
                if (is_array($doc->content) && !$doc->error)  {
                    //if (!$_SESSION['active_client'])
                    include(VIWPATH."/general/titlebar.php");
                    while (list($key,$val)=each($doc->content)) {
                        if ($val && file_exists($val)) {include($val);}
                        else {echo $val;}
                    }
                } elseif (!$doc->error  && !$doc->content) {?>
                    <header class="main-box-header clearfix">
                        <h1>Under Development</h1>
                    </header>
                <?} elseif (!$doc->error && $doc->content) {?>
                    <header class="main-box-header clearfix">
                        <h1>Var Dump Page</h1>
                    </header>
                    <div class="main-box clearfix">
                        <div class="alert alert-success fade in">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="fa fa-check-circle fa-fw fa-lg"></i>
                            <strong>Case:</strong> <?if (in_array($_GET["cmd"],$cases)) {echo strip_tags($_GET["cmd"].$_POST["cmd"]);}
                            else {echo "Unregistered Case";}?>
                        </div>
                        <div class="main-box-body clearfix">
                            <div class="row">
                                <div class="col-lg-12">
                                    <?if (is_object($doc->content)) {print_r($doc->content);
									} 
									else {echo $doc->content;}?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?} else {?>
                    <header class="main-box-header clearfix">
                        <h1><?echo $doc->error;?></h1>
                    </header>
                    <div class="main-box clearfix">
                        <div class="alert alert-danger fade in clearfix">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="fa fa-times-circle fa-fw fa-lg"></i>
                            <strong>Perhatian</strong> <?echo $doc->error_message;?>
                        </div>
                        <div class="main-box-body clearfix" style="top-margin:20px">
                            <div class="row">
                                <div class="col-lg-12">
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
        <?if ($fullpage) include (VIWPATH."/general/bottombar.php");?>
  		</div>
	</div>
  </div>
    <?if ($fullpage) //include (VIWPATH."/general/sidepanel.php");?>
    <?include (VIWPATH."/general/footer.php");?>
</body>
</html>