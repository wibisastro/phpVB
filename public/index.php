<?php
require_once __DIR__."/../config/init.php";

try {
	if (file_exists($page->controller)) {
		include ($page->controller);
	} else {throw new Exception("No Controller Found");}
} catch (Exception $e) {
	echo $e->getMessage();
}