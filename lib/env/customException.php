<?php namespace Gov2lib\env;
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
        switch (STAGE) {
            case "prod":
                echo "Uncaught exception: liat Log"; //---prod
            break;
            case "build":
                $doc->error($e);
            //    echo $this->errorMessage($e), "\n"; //---dev
            break;
            default:
           //     echo $exception->getMessage();
        }
	}
	
	public function errorMessage ($e) {
	    $errorMsg = 'Exception on line '.$this->getLine().' in '.$this->getFile()
	    .' with message: <b>'.$e.'</b> ';
	    return $errorMsg;
	}
}
?>