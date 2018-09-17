<?php namespace App\components\model;

class gov2formfield extends \Gov2lib\document {
	function __construct () {
        global $pageID,$self,$doc;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        //-default utk single controller    
        $GLOBALS['vueData']['action']=$self->className; 
        $GLOBALS['vueData']['fieldurl']=$self->className.'/fields'; 
        $GLOBALS['vueData']['defaultLevel']=1; 
        
	}

    function getLevel ($_fields,$_data) {
        foreach($_fields as $key=>$val) {
            if ($val['name'] == "level") {
                foreach($val['options'] as $key2=>$val2) {
                    //harus diubah ketika implmen tabel yang bener
                    //if ($data->level==$val2) {return $key2;break;} <- yg lama
                    if ($_data==$key2) {return $val2;break;}
                }
            }                    
        }        
    } 
    
	function demo () {
        $GLOBALS['vueData']['action']='gov2formfield';
        $GLOBALS['vueData']['fieldurl']='gov2formfield/fields';
	}
    
    function checkRequired ($data,$fields) {
        while(list($key,$val)=each($fields)) {
            if ($val['required']==true) {
                if (!$data[$val['name']]) {
                    $result[$val['name']]=$val['error_message'];
                } 
            } 
        }
        return $result;
    }
    
    function getFields ($_file) {
        try {
            if (!file_exists($_file)) {
                throw new \Exception('JsonFileNotExist:'.$_file);
            } else {
                $_json=json_decode(file_get_contents($_file), true);
                if (is_array($_json)) {
                    $result=$_json;
                } else {
                    throw new \Exception('JsonFileNotValid:'.$_file);
                }
            }
        } catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
		}
        return $result;
    }
    
    function getTokenForm($_file) {
        $result=$this->getFields(__DIR__."/../json/$_file.json");
        return $result;
    }
}
?>