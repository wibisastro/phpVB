<?php namespace App\gov2option\model;

class index extends \Gov2lib\crudHandler {
	function __construct ($dsn="") {
	    global $config;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
		$this->controller=__DIR__."/../".$this->className.".php";
        if (!$dsn) {$dsn=$config->domain->attr['dsn'];}
        parent::__construct($dsn);
        // $this->tbl->table=$this->tbl->dp_draft;
	}

    function getList() {
        global $self;
        $q = "SELECT DISTINCT(app) FROM {$this->tbl->options} WHERE level=1 AND type='option' AND UPPER(status)='ON'";
        $qs = "SELECT DISTINCT(app) FROM {$this->tbl->options} WHERE level=1 AND type='service' AND UPPER(status)='ON'";
        $res = [];

        try {
            $res['options'] = \DB::query($q);
            $res['services'] = \DB::query($qs);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }

        $res['userRole'] = $self->ses->val['userRole'] ?? '';
        return $res;
    }

    #---coded by claude
    function getYearOptions(string $pageID): array
    {
        global $self;
        $res = ['optionTahun' => [], 'activeYear' => null];

        if (empty($pageID)) {
            return $res;
        }

        $q = "SELECT child.nama, child.value as is_active
              FROM {$this->tbl->options} parent
              JOIN {$this->tbl->options} child ON child.parent_id = parent.id
              WHERE parent.app=%s AND parent.nama LIKE '%Tahun%' AND parent.level=1
              ORDER BY child.nama DESC";

        try {
            $rows = \DB::query($q, $pageID);
            foreach ($rows as $row) {
                $res['optionTahun'][] = ['nama' => $row['nama']];
                if ($row['is_active'] == '1' && $res['activeYear'] === null) {
                    $res['activeYear'] = $row['nama'];
                }
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }

        // Fallback: activeYear from member attr
        if ($res['activeYear'] === null && !empty($res['optionTahun'])) {
            $member = $self->opt->getMember();
            if (!empty($member['attr'])) {
                $xmlArray = json_decode(json_encode(simplexml_load_string($member['attr'])), true);
                if (!empty($xmlArray['tahun'])) {
                    $res['activeYear'] = (string)$xmlArray['tahun'];
                }
            }
        }

        return $res;
    }

    #---coded by claude
    function setYear(string $pageID, string $year): void
    {
        global $self;

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $member = $dom->createElement('member');
        $tahun = $dom->createElement('tahun', htmlspecialchars($year, ENT_XML1));
        $member->appendChild($tahun);
        $dom->appendChild($member);
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML();

        try {
            \DB::update('member', ['attr' => $xmlString], 'account_id=%i', $self->ses->val['account_id']);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }

    function dependencies () {
        global $self;
        $self->take("dpdraft2","draft");
    }

    function getRolePrivilege($id) {
        global $self, $uri;
        $query = "SELECT role FROM member WHERE id=%i LIMIT 1";
        try {
            $results['role'] = \DB::queryFirstField($query, $id); 
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
        }

        $query2 = "SELECT id,member_id,kecamatan_id FROM privilege WHERE member_id=%i";
        try {
            $results['privilege'] = \DB::query($query2, $id); 
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
        }
        
		return $results;
    }
}