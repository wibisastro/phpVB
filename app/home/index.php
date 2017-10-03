<?php
$doc->content("index.html");

$doc->body("contents",$doc->content);
$doc->body("pageTitle",'Government 2.0 StarterKit');
$doc->body("title",'Bappenas');

echo $template->render($doc->body);