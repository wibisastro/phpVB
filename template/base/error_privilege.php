<body onload="loadPage()">
<div id="response_alert_<?echo $pageID;?>">
<?if ($this->error) {?>
    <div class="alert alert-danger fade in">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="fa fa-times-circle fa-fw fa-lg"></i>
        <strong><?echo $this->error;?></strong> <?echo $this->txt($this->error);?>    
    </div>
<?} else {?>
    <div class="alert alert-success fade in">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="fa fa-check-circle fa-fw fa-lg"></i>
        <strong>Berhasil</strong> Informasi telah berhasil diperbaharui.  
    </div>
<?}?>
</div>
<?if ($_POST['cmd']=="add" || $_POST['cmd']=="update") {?>
    <table id="append_<?echo $pageID;?>">
    <tr id="row_<?echo $pageID;?>_<?echo $response->{$pageID."_id"};?>">
        <?
        $val=$response;
        include(VIWPATH."/$pageID/row.php");
        ?>
    </tr>
    </table>
<?}?>
<SCRIPT LANGUAGE="JavaScript">
<!--

function loadPage() {
  if (window == parent) return;
  else {
	var ref_source=document.getElementById('response_alert_<?echo $pageID;?>').innerHTML;
    parent.document.getElementById('response_alert_<?echo $pageID;?>').innerHTML=ref_source;
    <?if ($_POST['cmd']=="add") {?>
	  parent.closeForm_<?echo $pageID;?>();
    <?} elseif ($_POST['cmd']=="remove") {?>
	  parent.closeRemove_<?echo $pageID;?>();
    <?} elseif ($_POST['cmd']=="update") {?>
	  parent.closeForm_<?echo $pageID;?>();
    <?}?>    
  }
}
//-->
</SCRIPT>
</body>