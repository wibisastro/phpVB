<link rel="icon" href="<?echo imgurl?>/favicon.png" type="image/png" />
<link rel="StyleSheet" href="<?echo css_url;?>/style.css" type="text/css" />
<link rel="stylesheet" href="/gov2/v2/fasilkom/controller/css/m_layout.css" type="text/css" />
<link rel="stylesheet" href="/gov2/v2/fasilkom/controller/css/m_nav.css" type="text/css" />
<link rel="stylesheet" href="/gov2/v2/fasilkom/controller/css/m_accordion.css" type="text/css" />
<link rel="stylesheet" href="<?echo css_url;?>/form.css" type="text/css" />
<link rel="stylesheet" href="/gov2/v2/fasilkom/controller/css/m_ja.moomenu.css" type="text/css" />
<link rel="stylesheet" href="<?echo css_url;?>/fixedbar.css" type="text/css" />
<link rel="StyleSheet" href="<?echo css_url;?>/accordion.css" type="text/css" />
<link rel="StyleSheet" href="<?echo css_url;?>/jquery.jgrowl.css" type="text/css" />
<link rel="StyleSheet" href="<?echo css_url;?>/elastic.css" type="text/css" />
<link rel="StyleSheet" href="<?echo css_url;?>/facebox.css" type="text/css" />
<link rel="StyleSheet" href="<?echo css_url;?>/wall.css" type="text/css" />
<link rel="StyleSheet" href="<?echo css_url;?>/profile.css" type="text/css" />
<link rel="StyleSheet" href="<?echo css_url;?>/publisher.css" type="text/css" />
<link rel="StyleSheet" href="<?echo css_url;?>/mention.css" type="text/css" />
<link rel="StyleSheet" href="<?echo css_url;?>/publisher_placeholder.css" type="text/css" />
<link rel="StyleSheet" href="<?echo css_url;?>/link.css" type="text/css" />

<script type="text/javascript" src="<?echo js_url?>/jquery.js"></script>
<script type="text/javascript" src="<?echo js_url?>/m_accordion.js"></script>
<script type="text/javascript" src="<?echo js_url?>/accordion.js"></script>
<script type="text/javascript" src="<?echo js_url?>/script.js"></script>
<script type="text/javascript" src="<?echo js_url?>/fixedbar.js"></script>
<script type="text/javascript" src="<?echo js_url?>/jquery.jgrowl.js"></script>
<script type="text/javascript" src="<?echo js_url?>/jquery.easing.1.3.js"></script>
<script type="text/javascript" src="<?echo js_url?>/animation.compressed.js"></script>
<script type="text/javascript" src="<?echo js_url?>/jquery.elastic.js"></script>
<script type="text/javascript" src="<?echo js_url?>/jquery.limit-1.2.source.js"></script>
<script type="text/javascript" src="<?echo js_url?>/facebox.js"></script>
<script type="text/javascript" src="<?echo js_url?>/fcbkcomplete.js"></script>   
<script type="text/javascript" src="<?echo js_url?>/publisher.js"></script>
<script type="text/javascript" src="<?echo js_url?>/link.js"></script>
<!--script type="text/javascript" src="<?echo js_url?>/comment.js"></script-->

<?if ($google_api_key) {?>
<script src="http://maps.google.com/maps?file=api&v=2&key=<?echo $google_api_key;?>" type="text/javascript"></script>
<?}

if ($ses->error) {
	if (!$_GET["ref"]) {$ref=str_replace("&","__",$_SERVER["REQUEST_URI"]);}
	$login_url=urlencode("/login.php?cmd=login&ses_error=$ses_error&ref=$ref&apikey=$public");
}

if (($ses->error && $ses->error!="NotAuthorized" && $doc->doctype!='lostpassword' && $doc->doctype!='public'  && $doc->doctype!='login') || $ses_error) {
	$onload="onload=\"openlogin();\" onunload = \"GUnload()\"";
	?>
<script type="text/javascript">
function openlogin() {
	<?if ($google_api_key)	{?>
	initialize('53.551953','-113.674047');
	<?}?>
	<?if ($ses->error=="NotMember")	{$login_url=urlencode("/login.php?cmd=m_request&apikey=$public&session_id=".$ses->session_id);} 
	elseif ($ses->error && $ses->error!="NotAuthorized") {
		if (!$ses_error) {$ses_error=$ses->error;}
	}
	if ($login_url) {?>
	jQuery.facebox({ ajax: 'proxy.php?cmd=ajax&url=<?echo $login_url?>'})
	<?}?>
}
</script>
<?}?>

<script>
  jQuery(document).ready(function() {
    jQuery('a[rel*=facebox]').facebox() 
  })	
</script>