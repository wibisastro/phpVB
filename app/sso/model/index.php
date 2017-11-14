<?php namespace OPD\model;

class index {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
		$this->controller=__DIR__."/../index.php";
		$this->baseName="opd";
        $this->baseBody=$this->baseName.'Body.html';
	}
}
?>