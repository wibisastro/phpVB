<?php namespace App\components\model;

/**
 * Class gov2formfield
 * @package App\components\model
 *
 * v0.2 [April 21, 2021] [rijal@cybergl.co.id] Perubahan fn checkRequired() pada baris 46-51 menyesuaikan
 *      deprecated each() ke versi PHP7.2
 */

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

    function getLevel ($fields,$level,$level_label="") {
        foreach($fields as $_key=>$_val) {
            if ($_val['name'] == "level") {
                foreach($_val['options'] as $_key2=>$_val2) {
                    if ($level==$_key2 && !$level_label) {return $_val2;break;}
                    //--------baris di atas akan deprecated
                    if ($level==$_key2 && !is_array($_val2) && $level_label==$_val2) {
                        return $_val2;break;
                    } elseif ($level==$_key2 && is_array($_val2)) {
                        foreach($_val2 as $_key3=>$_val3) {
                            if ($level_label==$_val3) {return $_val3;break;}
                        }
                    }
                }
            }                    
        }        
    } 
    
	function demo () {
        $GLOBALS['vueData']['action']='gov2formfield';
        $GLOBALS['vueData']['fieldurl']='gov2formfield/fields';
	}
    
    function checkRequired ($data,$fields) {
        $result = null;
        foreach ($fields as $field) {
            if ((bool)$field['required'] == true) {
                if (!$data[$field['name']]) {
                    $result[$field['name']] = $field['error_message'] ? $field['error_message'] : "{$field['name']} required";
                }
            }
        }
        return $result;
    }
    
    function getFields ($_file) { //echo $_file;
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