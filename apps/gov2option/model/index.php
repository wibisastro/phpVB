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

    #---coded by claude
    function getUnitKerjaList($vars = [])
    {
        global $doc;
        $parentId = (int)($vars['id'] ?? 0);
        $fields = "id, parent_id, `level`, nama, kode, portal";
        $result = [];

        try {
            if ($parentId > 0) {
                $q = "SELECT {$fields} FROM {$this->tbl->kementerian} WHERE parent_id=%i ORDER BY kode ASC";
                $result = \DB::query($q, $parentId);
            } else {
                $q = "SELECT {$fields} FROM {$this->tbl->kementerian} WHERE parent_id=0 ORDER BY kode ASC";
                $result = \DB::query($q);
            }
            foreach ($result as $i => $row) {
                $qc = "SELECT COUNT(*) FROM {$this->tbl->kementerian} WHERE parent_id=%i";
                $cnt = \DB::queryFirstField($qc, $row['id']);
                $result[$i]['has_children'] = (int)$cnt > 0;
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }

        return $doc->responseGet($result);
    }

    #---coded by claude
    function searchUnitKerja($vars = [])
    {
        global $doc;
        $keyword = $_GET['q'] ?? '';
        $fields = "id, parent_id, `level`, nama, kode, portal";
        $result = [];

        try {
            $q = "SELECT {$fields} FROM {$this->tbl->kementerian}
                  WHERE (nama LIKE %ss OR kode LIKE %ss)
                  ORDER BY kode ASC LIMIT 50";
            $result = \DB::query($q, $keyword, $keyword);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }

        return $doc->responseGet($result);
    }

    #---coded by claude
    function getUnitKerjaConfig($vars = [])
    {
        global $self, $doc;
        $result = ['userRole' => '', 'locked' => false, 'unit_nama' => '', 'unit_id' => null, 'portal' => ''];

        $result['userRole'] = $self->ses->val['userRole'] ?? '';
        $result['unit_nama'] = $self->ses->val['unit_nama'] ?? '';
        $result['unit_id'] = $self->ses->val['opd_id'] ?? null;
        $result['portal'] = $self->ses->val['opd'] ?? '';

        $role = $result['userRole'];
        if ($role === '' || $role === 'member') {
            $result['locked'] = true;
        }

        return $doc->responseGet($result);
    }

    #---coded by claude
    function changePortal($vars = [])
    {
        global $self, $doc;
        $unitId = (int)($vars['id'] ?? 0);
        $portal = $_GET['portal'] ?? '';
        $unitNama = $_GET['nama'] ?? '';

        if ($unitId && $unitNama) {
            $self->ses->val['opd_id'] = $unitId;
            $self->ses->val['opd'] = $portal;
            $self->ses->val['portal_nama'] = $unitNama;
            $self->ses->val['unit_nama'] = $unitNama;
            $self->ses->val['unit_id'] = $unitId;
            $self->ses->val['change_portal'] = 1;
            $self->ses->sesSave($self->ses->val);
        }

        return $doc->responseGet(['status' => 'ok', 'unit_id' => $unitId, 'unit_nama' => $unitNama]);
    }

    #---coded by claude
    function resetPortal($vars = [])
    {
        global $self, $doc;
        $self->ses->val['opd_id'] = null;
        $self->ses->val['opd'] = null;
        $self->ses->val['portal_nama'] = null;
        $self->ses->val['unit_nama'] = null;
        $self->ses->val['unit_id'] = null;
        $self->ses->val['change_portal'] = null;
        $self->ses->sesSave($self->ses->val);

        return $doc->responseGet(['status' => 'ok']);
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