<?php namespace Gov2lib;
/********************************************************************
*	Date		: Sep 30, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*	Version		: 1
*/

class customException extends \Exception {
    /*
    public function __construct () {
		@set_exception_handler(array($this, 'exceptionHandler'));
	}
    */
	public function exceptionHandler ($e) {
        global $doc;
        list($code,$message)=explode(":",$e);
        $doc->error($code,$message);
	}
	/*
	public function errorLog ($e) {
	    $errorMsg = 'Exception on line '.$this->getLine().' in '.$this->getFile()
	    .' with message: <b>'.$e.'</b> ';
	    return $errorMsg;
	}
    */
}
?>