<div class="fboxheader">Delete Confirmation</div>
	<?echo $frm->form($PHP_SELF, $hidden);?>
<div class="fboxbody">
	<center>
	<?echo $del_confirm;?>
	</center>
 </div>
 <div class="fboxfooter">
 <input type="submit" name="cmd" value="Sure" class="formbutton"/> 
<input type="button" value="Cancel"  class="formbutton" onclick="javascript:jQuery(document).trigger('close.facebox')" />
</div>