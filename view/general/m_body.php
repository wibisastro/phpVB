<?if (strstr($HTTP_USER_AGENT,"MSIE")) {?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><?}?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />

<title><?echo $doc->pagetitle?> - Government 2.0 Indonesia</title>

<?include (viwpath."/general/m_head.php");?>

</head>
<body <?echo $onload;?>>
<div id="ajax_error" style="display:none;"></div>
<div id="infopanel"></div>
<!--script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php" type="text/javascript"></script-->
<iframe id="iframer" name="iframer" src="" frameborder="1" style="display:<?if ($debug) {echo "inline";} else {echo "none";}?>" width="300" height="200"></iframe>
<div id="maincontainer">
	<!--topsection-->
	<div id="topsection">

			<div class="atas">
				<div class="logo">
				<?echo $doc->lnk("index.php",$doc->img("","","images/logo.png"));?>	
				</div>
			</div>

			<div class="bawah">
				<div id="topmenu"> 
					<div><?include(viwpath."/general/status.php")?></div>
				</div>
				<div><?
					echo $api->m_study_menu(2);
					//$mainmenu=$doc->mainmenu(85);
					//include(viwpath."/home/m_mainmenu.php");
					//echo substr($mainmenu,20,strlen($mainmenu));
					?>
				</div>
			</div>

			<div id="status">
				<div>
					<span class="name"><?echo $doc->pagetitle;?></span> 
					<span class="update"><?echo $doc->status;?></span>
				</div>
			</div>

	</div>	
	
	<!--content-->
	<div id="contentwrapper">
		<div id="contentcolumn_<?echo $doc->bodytype;?>">
		<div class="innertube">
			<div class="navpath"><?echo $doc->navpath;?></div><?
		if (is_array($doc->content))  {
			while (list($key,$val)=each($doc->content)) {
				if ($val && file_exists($val)) {include($val);}
				else {echo $val;}
			}
		} elseif (!$doc->error) {?>
			<div class="infobox" style="margin-left:20px;text-align:center;">Under Construction</div>
		<?}?>
		</div>
		</div>
	</div>
	<!--leftcolumn-->
<?if ($doc->bodytype=="left") {?>
	<div id="leftcolumn">
		<div class="innertube"><?
			if (is_array($doc->leftside) && ($doc->bodytype=="left" || $doc->bodytype=="center")) {
				while (list($key,$val)=each($doc->leftside)) {
					if ($val && file_exists($val)) {include($val);}
					else {echo $val;}
					echo "&nbsp;";
				}
			}
		?></div>
	</div>
<?}?>
	<!--footer-->
		<div id="footer">
			<div class="row">
			<span class="left">
				Research by <?echo $doc->lnk("http://mti.cs.ui.ac.id","University of Indonesia", "help");?>, implementation by <?echo $doc->lnk("http://www.cybergl.co.id","CyberGL");?>
			</span>
			<!--<span class="right">
				Version: <?echo $doc->lnk(siturl."/index.php?cmd=changelog","0.3");?>
			</span>-->
			</div>
		</div>
	
</div>
<!--fixed bottom bar-->
<div id="bottom-right" class="bottom-right"></div>
<div id="bottom-left" class="bottom-left"></div>
<div id="top-left" class="top-left"></div>
<div id="top-right" class="top-right"></div>
<script>
$(document).ready(function(){
	<?if ($doc->error)	{?>openbox('errorbox');<?}?>
	<?if ($infobox)	{?>openbox('info');<?}?>
	<?if ($bluebox)	{?>openbox('blue');<?}?>
});

function error() {
	$('#trigger_error').click();
}	
</script>
<a href="#ajax_error" rel="facebox" id="trigger_error" style="display:none">error</a>
</body>
</html>
