<body onload="loadPage()">
<div id="response_alert_<?echo $pageID;?>">
    <div class="alert alert-danger fade in">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="fa fa-times-circle fa-fw fa-lg"></i>
        <strong><?echo $doc->error;?></strong> <?echo $doc->txt($doc->error);?>    
    </div>
</div>
<SCRIPT LANGUAGE="JavaScript">
<!--

function loadPage() {
  if (window == parent) return;
  else {
	var ref_source=document.getElementById('response_alert_<?echo $pageID;?>').innerHTML;
    parent.document.getElementById('response_alert_<?echo $pageID;?>').innerHTML=ref_source;
  }
}
//-->
</SCRIPT>
</body>