<?php namespace App\gov2pipe\model;

class index extends \Gov2lib\crudHandler {
	function __construct () {
		global $config, $doc;
		$this->templateDir= __DIR__ . "/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        try {
            $cookies = $doc->envRead($_COOKIE['Gov2Session'] ?? null);
            $this->dsn = $cookies['portal'];
            $this->dsn_id = $cookies['portal_id'];
            if (!$this->dsn) {
                $this->dsn      = $config->domain->attr['dsn'];
                $this->dsn_id   = $config->domain->attr['id'];
            }
        } catch (\Exception $e) {
            $this->dsn      = $config->domain->attr['dsn'];
            $this->dsn_id   = $config->domain->attr['id'];
        }
        parent::__construct($this->dsn);
	}

	function loadTable() {
	    global $self;
    }

    function dependencies () {

    }
}
