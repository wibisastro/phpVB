<?php
$main=new App\opd\model\opd;
try {
    if ($_POST) {
        switch($_POST["cmd"]) {
            case "add":
                if (!$_POST['nama']) {throw new Exception('Uncomplete');}
                else {
//                    $vars['account_id']=$gov2->authorized['account_id'];
                    $id=$main->{$pageID."Add"}($_POST);
                    $response=$main->{$pageID."Read"}($id);
					$response->_id=$response->opd_id;
					$records=$main->{$pageID."Browse"}();
					$doc->body("no",count($records));
					$doc->body("response",$response);
                }
            break;
            case "remove":
                if (!$_POST[$pageID.'_id']) {throw new Exception('NoID');}
                else {
					$response->_id=$main->{$pageID."Remove"}($_POST);
					$doc->body("response",$response);
				}
            break;
            case "update":
                if (!$_POST['nama']) {$doc->error="Uncomplete";}
                else {
                    $main->{$pageID."Update"}($_POST);
                    $response=$main->{$pageID."Read"}($_POST[$pageID.'_id']);
					$response->_id=$response->opd_id;
					$doc->body("no",$_POST["no"]);
					$doc->body("response",$response);
                }
            break;
            default:
    	}
		$doc->content("scaffoldResponse.html");
		$doc->body("contents",$doc->content);
		$template = $twig->load($scf->baseName.'Body.html');
    } else {
        if (!isset($vars["cmd"])) {$vars["cmd"]="";}
        switch($vars["cmd"]) {
            case "add":
				$doc->content("scaffoldAdd.html");
				$doc->body("contents",$doc->content);
				$template = $twig->load($scf->baseName.'Body.html');
            break;
            case "edit":
                if (!$vars[$pageID.'_id']) {throw new Exception('NoID');}
                else {
                    $edit=$main->{$pageID."Read"}($vars[$pageID."_id"]);
                    $edit->no = $vars["no"];
					$doc->body('edit',addslashes(json_encode($edit)));
                }
				$doc->content("scaffoldEdit.html");
				$doc->body("contents",$doc->content);
				$template = $twig->load($scf->baseName.'Body.html');
            break;
            default:
                $scaffold['addbutton']=true;
                $scaffold['remove']=true;
                $scaffold['form']='default';
                // $main->opd_history($_GET["parent"]);
                // $breadcrumb=$main->breadcrumb_path($main->history);
//                $data=$main->{$pageID."_browse"}($_GET["parent"]);
//                $parent=$main->{$pageID."_read"}($_GET["parent"]);
//                $doc->content("scaffold/browse.php");
				$data=$main->{$pageID."Browse"}();
				$doc->content("scaffoldBrowse.html");
				$doc->body("contents",$doc->content);
				$doc->body("data",$data);
				$doc->body("dataSize",count($data));
				$doc->body("pageTitle",'Organisasi Perangkat Daerah');
				$doc->body("title",'OPD');
        }
    }
} catch (Exception $e) {
	echo $e->getMessage();
}

$doc->render();