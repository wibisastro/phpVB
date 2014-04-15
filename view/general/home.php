<div  class="basictab">
<ul>
<li <?if ($tab=="browse") {?>class="selected"<?}?>><a href="<?echo $PHP_SELF;?>">Tengah</a></li>
<li <?if ($tab=="left") {?>class="selected"<?}?>><a href="<?echo $PHP_SELF;?>?cmd=left">Kiri</a></li>
<li <?if ($tab=="right") {?>class="selected"<?}?>><a href="<?echo $PHP_SELF;?>?cmd=right">Kanan</a></li>
<?if ($tab=="note") {?>
<li class="selected"><a href="<?echo $PHP_SELF;?>?cmd=note&site_id=<?echo $site_id;?>">Notes</a></li>
<?}?>
<li id="more"><a href="#" title="More categories.." onclick="showbox('more','cat','<?if ($doc->bodytype=="right") {echo "kanan";} else {echo "257";}?>'); return false">&nbsp;&nbsp;&nbsp;</a>
</li>
</ul>
</div>
<div id="cat" class="hiddencats" style="display: none;">
	<div class="hiddenleft"><div class="hiddenplus" onclick="hidebox('cat')">&nbsp;</div></div>
	<div class="hiddenright">
		<ul>
			<li class="browsecat">Browse more categories</li>
			<li><a onclick="$('#bottom-right').jGrowl('Hello world!');" href="javascript:void(0);">Kanan Bawah</a></li>
			<li><a onclick="$('#bottom-left').jGrowl('Hello world!');" href="javascript:void(0);">Kiri Bawah</a></li>
			<li><a onclick="$('#top-left').jGrowl('Hello world!');" href="javascript:void(0);">Kiri Atas</a></li>
			<li><a onclick="$('#top-right').jGrowl('Hello world!');" href="javascript:void(0);">Kanan Atas</a></li>
			<li><a id="infobalik" href="javascript:void(0);">Info Balik</a></li>
		</ul>
	</div>
</div>

<div id="tabcontainer">
	<div id="errorbox" style="display:none">
Error Updated to include all methods from Robert Penners easing equations. Renamed the equations. 
			<?echo $doc->error_message;?>
		<div class="closebox">
			<a href="#" onclick="closebox('errorbox')">X</a>
		</div>
	</div>
	&nbsp;
	<div class="greybox" id="grey">
		Grey
		<div class="closebox">
			<a href="#" onclick="closebox('grey')">X</a>
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
		Info
		<div class="closebox">
			<a href="#" onclick="closebox('info')">X</a>
		</div>
	</div>
		&nbsp;
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