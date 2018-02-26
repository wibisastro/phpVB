<?php
if (!isset($vars['render'])) {$vars['render']="";}
switch ($vars['render']) {
    case "render":
        $doc->content($page->componentName);
        $doc->body("contents",$doc->content);
        echo $template->render($doc->body);
    break;
    default:
        if (file_exists($page->templateDir."/".$page->componentName)) {
            readfile($page->templateDir."/".$page->componentName); 
        }
}