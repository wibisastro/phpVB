<?php namespace App\phpvb\model;

class index {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
		$this->controller=__DIR__."/../index.php";
		$this->baseName="phpvb";
        $this->baseBody=$this->baseName.'Body.html';
	}
}
?>