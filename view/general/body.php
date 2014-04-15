<?if (strstr($HTTP_USER_AGENT,"MSIE")) {?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><?}?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?echo $doc->pagetitle?> - Government 2.0 Indonesia</title>

<?include (viwpath."/general/head.php");?>

</head>
<body <?echo $onload;?>>
<div id="ajax_error" style="display:none;"></div>
<div id="infopanel"></div>
<iframe id="iframer" name="iframer" src="" frameborder="1" style="display:<?if ($debug) {echo "inline";} else {echo "none";}?>" width="300" height="200"></iframe>

<div id="maincontainer">
	<!--topsection-->
	<div class="logo">
		<?echo $doc->lnk("index.php",$doc->img("","","images/logoegov.png"));?>	
	</div>

	<div id="topsection">
			<div id="topmenu"> 
					<?include(viwpath."/general/status.php")?>
			</div>
			<div>
				<div class="atas">
					<?include(viwpath."/general/menu.php")?>		
				</div>
			</div>
			
			<div class="bawah" align="center">
				<?echo $doc->img("","","images/nyan.jpg");?>
			</div>
	</div>		
	<!--content-->
	
	<div id="contentwrapper">
		<div id="status">
					<div>
						<span class="name"><?echo $doc->pagetitle;?></span> 
						<span class="update"><?echo $doc->status;?></span>
					</div>
		</div>
		<div id="contentcolumn_<?echo $doc->bodytype;?>">
		<div class="innertube">
			<div class="navpath"><?echo $doc->navpath;?></div>
		<?
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
<?if ($doc->bodytype=="left" || $doc->bodytype=="center") {?>
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
	<!--rightcolumn-->
<?if ($doc->bodytype=="right" || $doc->bodytype=="center") {?>
	<div id="rightcolumn">
		<div class="innertube"><? 
			if (is_array($doc->rightside) && ($doc->bodytype=="right" || $doc->bodytype=="center")) {
				ksort($doc->rightside);
				while (list($key,$val)=each($doc->rightside)) {
					if ($val && file_exists($val)) {include($val);}
					else {echo $val;}
					echo "&nbsp;";
				}
			}
		?></div>
	</div>
<?}?>
	<!--singlecolumn-->
	<div class="innertube"><?if ($doc->singledoc) {include($doc->singledoc);}?></div>
	<!--footer-->
	
		<div id="footer">
			<div class="row">
			<span class="left">
				Research by <?echo $doc->lnk("http://mti.cs.ui.ac.id","University of Indonesia", "help");?>, implementation by <?echo $doc->lnk("http://www.cybergl.co.id","CyberGL");?>
			</span>
			<span class="right">
				Version: <?echo $doc->lnk(siturl."/index.php?cmd=changelog","0.3");?>
			</span>
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
