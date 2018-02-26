<?php
$doc->model("nav","menubar");
$doc->component("nav");
$doc->content("index.html");

$doc->body("contents",$doc->content);
$doc->body("pageTitle",'Government 2.0 StarterKit');
$doc->body("subTitle",'Bappenas');

echo $doc->render();