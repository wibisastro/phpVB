<?php
$doc->model("nav","menubar","xml/menu.xml");
$page->menubar("xml/menu2.xml");
//$doc->body("contents",$doc->content);
$doc->body("pageTitle",'Navigation Admin Page');
$doc->body("subTitle",'phpVB');

echo $doc->render();