<?php
$doc->model("nav","menubar","xml/menu.xml");
$nav->menubar("xml/menu2.xml");
$doc->component("nav");

$doc->content("index2.html");

$doc->body("contents",$doc->content);
$doc->body("pageTitle",'Government 2.0 with Vue');
$doc->body("title",'phpVB');

echo $doc->render();