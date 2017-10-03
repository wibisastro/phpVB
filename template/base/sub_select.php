<body onload="loadPage()">
<div id="subwil_<?echo $_GET["grandparent"];?>">
	<div>	
		<?//echo "gp: ".$grandparent; echo "<br/>"; echo "p: ".$parent; echo "<br/>"; echo "t: ".$tingkat;?>
		<? $api->wilayah_listbox($_GET['parent'],$tingkat); ?>
			<span><?echo $api->listbox;?> <?echo $api->loading;?></span>
	</div>
	<?echo $api->child;?>
</div>
</body>

<SCRIPT LANGUAGE="JavaScript">
<!--
function loadPage() {
  if (window == parent) return;
  else {
	  parent.document.getElementById('loading_<?echo $_GET["grandparent"];?>').style.visibility='hidden';
//	  parent.displayData('subcat_<?echo $_GET["grandparent"];?>');
	var ref_source=document.getElementById('subwil_<?echo $_GET["grandparent"];?>').innerHTML;
    parent.document.getElementById('subwil_<?echo $_GET["grandparent"];?>').innerHTML=ref_source;
  }
}
-->
</script>
