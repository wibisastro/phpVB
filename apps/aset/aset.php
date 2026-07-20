<?php namespace App\aset;

/**
 * Controller CRUD "aset" — uji Resep A skill halaman-cube.
 * Pola kanonik apps/home/crud.php (dispatcher two-class): controller orkestrasi
 * komponen + delegasi CRUD ke model ($self = App\aset\model\aset extends crudHandler).
 * Dressing cube di view (stat card invoice-list#1 + card pembungkus gov2table).
 */
class aset extends \Gov2lib\api {

    function __construct() {
       global $self;
       $self->takeAll("components");
       parent::__construct();
       $self->scrollInterval=300;
       $self->fields = $self->gov2formfield->getFields(__DIR__ . "/json/aset.json");
    }

    function index() {
        global $self,$doc;
        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Daftar Aset');
        $doc->body("subTitle",'CRUD contoh via skill halaman-cube (driver Supabase)');
        $self->loadTable();
        $self->gov2notification->content();
        $self->gov2search->content();
        $self->gov2button->content();
        $self->content();
        $self->gov2pagination->content();
    }

    function fields () {
        global $self;
        return $self->fields;
    }

    function edit ($vars) {
        global $self;
        return $self->getRecord($vars['id']);
    }

    function count ($vars) {
        global $self;
        return $self->getCount($vars['id']);
    }

    function table ($vars) {
        global $self, $doc;
        $data = $self->getRecords($vars['scroll']);
        if (sizeof($data) == 0) {
            $data = array("data" => "empty", "level" => "1");
        }
        return $doc->responseGet($data);
    }

    function add () {
        global $self;
        unset($_POST['id']);
        return $self->postAdd($_POST);
    }

    function update () {
        global $self;
        return $self->postUpdate($_POST);
    }

    function del () {
        global $self;
        return $self->postDel($_POST);
    }
}
