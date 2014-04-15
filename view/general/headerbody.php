<div id="headerbody">
<?if ($doc->headerbody && file_exists($doc->headerbody)) {include($doc->headerbody);} else {echo $doc->headerbody;}?>
</div>

<div id="tabcontainer">
	<div id="errorbox">
			<?echo $doc->error_message;?>
		<div class="closebox">
			<a href="#" onclick="closebox('errorbox')">X</a>
		</div>
	</div>
	&nbsp;
	<div class="bluebox" id="blue">
		Blue
		<div class="closebox">
			<a href="#" onclick="closebox('blue')">X</a>
		</div>
	</div>
	&nbsp;
	<div class="infobox" id="info">
		<?echo $infobox?>
		<div class="closebox">
			<a href="#" onclick="closebox('info')">X</a>
		</div>
	</div>
		&nbsp;
<?echo $breadcrumb;?>
<?	if (is_array($doc->tab))  {
		while (list($key,$val)=each($doc->tab)) {
			if ($val && file_exists($val)) {include($val);}
			echo "&nbsp;";
		}
	}?>
</div>
<script>
function closebox(elm) {
	Animation(document.getElementById(elm)).to('height', '0px').to('opacity', 0).hide().go(); return false;
}

function openbox(elm) {
Animation(document.getElementById(elm)).to('height', 'auto').from('0px').to('width', 'auto').from('0px').to('opacity', 1).from(0).blind().show().go(); return false;
}
</script>