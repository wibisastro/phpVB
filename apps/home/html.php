<?php namespace App\home;
    
class html {
    function __construct () {
        global $doc;
        $doc->baseBody="index.html";
    }
    
    function index () {
        global $self,$htmlFile;
        switch ($_SERVER['SERVER_NAME']) {
            case "api.local.krisna.systems":
                $htmlFile="keyApiLocalKrisnaSystems.html";
            break;
            case "api.krisna.systems":
                $htmlFile="keyApiKrisnaSystems.html";
            break;
            case "api.kl2.web.id":
                $htmlFile="keyApiKl2Web.html";
            break;
            default:
        }
        $self->content($htmlFile);
    }
}