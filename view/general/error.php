<script>
<!--
function loadPage() {
  if (window == parent) return;
  else {
    parent.document.getElementById('ajax_error').innerHTML = document.getElementById('ajax_error').innerHTML;
	parent.error();
  }
}
-->
</script>
<body onload="loadPage()">
<div id="ajax_error">
	<div class="fboxheader">Error: <?echo $doc->error;?></div>
	<div class="fboxbody">
		<center>
		<div class="errorbox">
			<?echo $doc->txt($doc->error);?>
		</div>
			Harap ulangi bila setelah kesalahan telah diperbaiki
		</center>
	 </div>
	 <div class="fboxfooter">
	<input type="button" value="Close"  class="formbutton" onclick="javascript:jQuery(document).trigger('close.facebox')" />
	</div>
</div>
</body>