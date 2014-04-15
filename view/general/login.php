<div class="cssbox">  
<div class="cssbox_head">  
<h2>Login Form</h2>  
</div>  
<div class="cssbox_body">  
<?echo $frm->form(siturl."/login.php", $hiddenlogin, "ValidateLogin(this)", "Login");?>
		<div class="form">
		<span class="help">Email:<br />
		<?echo $frm->textbox("text","email","20","32");?>
		</span>
		</div>
		<div class="spacer"></div>
		<div class="form">
		<span class="help">Password:<br />
		<?echo $frm->textbox("password","password","10","16");?>
		</span>
		</div>
		<div class="spacer"></div>
		<div class="form">
		<?echo $frm->button($doc->txt("bLogin"));?>
		 | 
		<?echo $doc->lnk(siturl."/registration.php", "Register", "menu");?>
		 | 
		<?echo $doc->lnk(siturl."/lostpassword.php","Lost Password?","menu");?>
		</div>

<script type="text/javascript">
<!--
function ValidateLogin(theForm) {
 if (!theForm.email.value)
  {
    alert("Field Email is still empty");
    theForm.email.focus();
    return (false);
  }
  if (!theForm.password.value)
  {
    alert("Field Password is still empty");
    theForm.password.focus();
    return (false);
  }
  return (true);
}
//-->
</script>
</form>
</div>
</div>


