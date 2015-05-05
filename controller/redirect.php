<?
$redirect=str_replace("__", "&", $_GET["redirect"]);
header("Location: $redirect");
?>