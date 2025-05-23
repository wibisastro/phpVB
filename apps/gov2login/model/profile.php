<?php namespace App\gov2login\model;

class profile extends \Gov2lib\document {
    function __construct () {
        global $doc,$ses;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $doc->body("className",$this->className);
    }
}
?>