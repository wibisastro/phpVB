<?php namespace App\gov2login;
    
class member {

    public $page_role = 'webmaster';

    function __construct () {
		global $self,$vars, $doc;

        $self->takeAll("components");
        $self->fields = $self->gov2formfield->getFields(__DIR__."/json/member.json");
        // {role} di URL menentukan privilege gate — 'public' di-clamp ke
        // 'guest' (R1 role-framework): 'public' mem-bypass login sepenuhnya,
        // padahal controller ini memuat cmd mutasi (del/update/setTag);
        // tanpa clamp, POST /gov2login/user/public cmd=del bisa dieksekusi
        // ANON. Halaman daftar "role public" memang tak pernah ada (bukan
        // role DB). Gate per-cmd berbasis target = agenda R2.
        $_gate = ($vars['role'] ?? '') === 'public' ? 'guest' : $vars['role'];
        $self->ses->authenticate($_gate);
        $self->scrollInterval = 100;
    }
    
    function index ($vars) {
        global $self,$doc;

        // if ($role === 'default') {
        //     $user = $self->get_user(['account_id' => $self->ses->val['account_id']]);
        //     $self->ses->val['userRole'] = $user['role'];
        //     $self->ses->authenticate('role');
        //     $self->content('role-default.html');
        // } else {
        //     $doc->body("pageTitle",'Gov 2.0 Member Admin'); 
        //     $doc->body("roleName",$vars['role']); 
        //     // $self->content('table.html');
        //     $self->content();
        //     $self->content('role-tagging.html');
        // }

        $user = $self->get_user(['account_id' => $self->ses->val['account_id']]);
        if($user['role'] == 'guest'){
            throw new \Exception("Access Denied: UserRole akun Anda tidak memiliki wewenang mengakses halaman ini. Silakan hubungi Admin");
        }

        $role = $vars['role'];
        // $self->ses->authenticate($role);

        $self->loadTable($self->scrollInterval);
        $doc->body("pageTitle",'Gov 2.0 Member Admin');
        $doc->body("roleName","/gov2login/user/" . $role);
        // Override relative URLs to absolute paths (agar bekerja di cube theme)
        $GLOBALS['vueData']['action'] = '/gov2login/user/' . $role;
        $GLOBALS['vueData']['fieldurl'] = '/gov2login/user/' . $role . '/fields';
        $self->ses->val['userRole'] = $user['role'];
        $self->content('role-default.html');
    }

    function count ($vars) {
        global $self,$doc;
        $data=$self->memberCount($vars['role']);
        return $data;   
    }
    
    function fields () {
        global $self;
        return $self->fields;
    }

    function table ($vars) {
        global $doc, $self;

        $data=$self->memberBrowse($vars['role'],$vars['scroll']);
        // $data = $self->browse($vars['scroll']);
        return $doc->responseGet($data);   
    }
    
    function roleBrowse () {
        global $doc,$self;

        $superUser = $this->getSuperUser();

        $data=$self->roleBrowse($superUser);
        if (sizeof($data)==0) {$data=array("data"=>"empty","level"=>"1");}
        return $doc->responseGet($data);   
    }
    /*
    function add () {
        global $self;
        $response=$self->postAdd($_POST);
        return $response;
    }
    */
    function edit ($vars) {
        global $self, $doc;
        // $response=$self->getRecord($vars['id']);
        $data = $self->get_user(['id' => $vars['id']]);
        return $doc->responseGet($data);
    }
    
    function del () {
        global $self, $doc;
        // $response=$self->postDel($_POST);
        $self->delete_user($_POST['id']);
        $response = $doc->response('success', 'confirmClose', $_POST['id']);
        return $doc->repsonseGet($response);
    }
    
    function update () {
        global $self, $doc;
        // $response = $self->postUpdate($_POST);
        $payload = [
            'account_id' => $_POST['account_id'],
            'fullname' => $_POST['fullname'],
            'email' => $_POST['email'],
            'status' => $_POST['status'],
            'role' => $_POST['role']
        ];
        $affected = $self->update_user($payload);
        $response = $doc->response('success', 'infoSnackbar', $_POST['id']);
        return $doc->responseGet($response);
    }

    function getCurrentUser () {
        global $self;
        // $response=$self->getCurrentUser($self->ses->val['account_id']);
        $response = $self->ses->val;
        return $response;
    }

    function permission() {
        global $self, $cmdID;

        $user = $self->get_user(['account_id' => $self->ses->val['account_id']]);

        $permission = [
            'canAdd' => false,
            'canEdit' => true,
            'canDelete' => true
        ];

        if($user['role'] == 'guest' || $user['role'] == 'member' || ($user['role'] == 'admin' && $cmdID == 'admin')){
            $permission = [
                'canAdd' => false,
                'canEdit' => false,
                'canDelete' => false
            ];
        }else if($user['role'] == 'webmaster' && $cmdID == 'webmaster'){
            $permission = [
                'canAdd' => false,
                'canEdit' => false,
                'canDelete' => false
            ];

            $superUser = $this->getSuperUser();
            if($superUser){
                $permission = [
                    'canAdd' => false,
                    'canEdit' => true,
                    'canDelete' => true
                ];
            }
        }

        return $permission;
    }

    function getSuperUser(){
        global $self, $cmdID;

        $superUser = false;

        $user = $self->get_user(['account_id' => $self->ses->val['account_id']]);

        $_filePath = __DIR__."/../../apps/sdi/xml/superuser.xml";
        if (file_exists($_filePath)) {
            $_data = simplexml_load_file($_filePath, "SimpleXMLElement", LIBXML_NOCDATA);
            if(is_object($_data)) {
                foreach ($_data->role as $row) {
                    $name = $row->attributes()['name'];
                    if($name[0] == 'webmaster'){
                        $accountId = (array) $row->account_id;
                        if(in_array($user['account_id'], $accountId)){
                            $superUser = true;
                            break;
                        }
                    }
                }
            }
        }

        return $superUser;
    }

    function setTag() {
        global $self, $doc;
        $_POST['target_id'] = intval($_POST['target_id']);
        $_POST['source_id'] = intval($_POST['source_id']);

        if (!$this->authorized($_POST['source_id'])) {
            $response = $doc->response('danger', 'infoSnackbar');
            $response['notification'] = 'Anda tidak diizinkan untuk menambah role di unit lain';
            return $response;
        }

        $response = $self->postTagging($_POST, 'unit', 'member', '', 'fullname');
        if ($response['class'] === 'is-danger') {
            if (is_array($response['notification'])) {
                $_text = "";
                if($response['notification']['doTagging']) {
                    $_text .= 'doTagging: '.$response['notification']['doTagging'];
                } else if($response['notification']['AlreadyTagged']) {
                    $_text .= $response['notification'] = $response['notification']['AlreadyTagged'];
                }
                $response['notification'] = $_text;
            } else {
                $response['notification'] = 'Already Tagged';
            }
        }
        $response['class'] == 'is-primary' ? $response['class'] = 'success' : $response['class'] = 'danger';
        $response['callback'] = 'infoSnackbar';
        return $response;
    }

    function unsetTag() {
        global $self, $doc;

        $tag = $self->get_tag($_POST['id']);

        if (!$this->authorized($tag['unit_id'])) {
            $response = $doc->response('danger', 'infoSnackbar');
            $response['notification'] = 'Anda tidak diizinkan untuk menghapus role di unit lain';
            return $response;
        }

        $response = $self->postDelTag($_POST);
        $response['class'] == 'is-primary' ? $response['class'] = 'success' : $response['class'] = 'danger';
        $response['callback'] = 'infoSnackbar';
        return $response;
    }

    function getTags($vars) {
        global $self;
        $id = $self->setRememberId($vars['id']);
        return $self->getBrowseTags($id, 'unit', 'member', '', 'fullname');
    }

    /**
     * Gate tulis tagging unit (setTag/unsetTag): wewenang role LOKAL portal
     * aktif, dibaca via enum UserRole (R1 role-framework) — bukan lagi angka
     * pageroles.xml. Jalur lintas-DSN lama (get_user_unit ke portal unit
     * target, sistem role per-unit) dihapus: mati sejak UI role-tagging
     * dinonaktifkan; kebutuhan multi-instansi dilayani memberships (R4).
     * Delta sadar: owner/developer kini lolos (dulu terblokir karena tak ada
     * di peta XML gov2login → level 0); webmaster portal LAIN tak lagi diakui.
     */
    function authorized ($unit_id)
    {
        global $self;

        $user = $self->get_user(['account_id' => $self->ses->val['account_id']]);

        return \Gov2lib\Enums\UserRole::fromName((string)($user['role'] ?? ''))->level()
            >= \Gov2lib\Enums\UserRole::fromName($this->page_role)->level();
    }
}