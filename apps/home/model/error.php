<?php namespace App\home\model;

class error extends \Gov2lib\document {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
		$this->controller=__DIR__."/../".$this->className.".php";
	}
}