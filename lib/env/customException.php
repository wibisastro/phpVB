<?php namespace Gov2lib\env;
/********************************************************************
*	Date		: Sep 30, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*	Version		: 1
*/

class customException extends \Exception {
	public function __construct() {
		@set_exception_handler(array($this, 'uncaughtHandler'));
	}

	function uncaughtHandler($exception) {
		echo "Uncaught exception: liat Log"; //---prod
		echo $exception->getMessage(), "\n"; //---dev
	}
	
	public function errorMessage() {
	    //error message
	    $errorMsg = 'Error on line '.$this->getLine().' in '.$this->getFile()
	    .': <b>'.$this->getMessage().'</b> is not a valid E-Mail address';
	    return $errorMsg;
	}
}
?>