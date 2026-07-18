<?php namespace App\gov2login;

class profile {
    function __construct () {
		global $self;
        $self->takeAll("components");
        $self->ses->authenticate('guest');
    }

    function index () {
        // Halaman profil portal pensiun — langsung ke profil beo ber-tab
        // (Profil + Ganti Password) TOP-LEVEL, sesi diadopsi via stoken.
        // Helper ssoProfileUrl tetap di sini (dipakai juga Gov2lib\login::profile).
        header("Location: " . self::ssoProfileUrl());
        exit;
    }

    /**
     * URL "Ganti Password" ke SSO node dengan stoken TERENKRIPSI — token sesi SSO
     * (ssokey = session_id node) tidak tampil plain di URL. AES-256-GCM,
     * key = sha256(apikey.public|secret): pasangan kontrak yang sama di config
     * portal & SSO node. Payload {t, c, x} — exp 5 menit. Tanpa ssokey/apikey →
     * fallback URL tanpa stoken (SSO node akan minta login).
     */
    static function ssoProfileUrl () {
        global $config, $self;
        $node = rtrim((string)$config->platform->ssonode, '/');
        $base = $node . '/gov2sso/sprofile?client=' . urlencode($_SERVER['SERVER_NAME']);
        // ssokey ada di Gov2Session ($self->ses->val), BUKAN $_SESSION — gov2session
        // pakai cookie JWT → ->val, tak menulis $_SESSION. (Fallback $_SESSION jaga2.)
        // Tanpa ini stoken kosong → "Ganti Password" mendarat di login, bukan profil.
        $ssokey = (string)(($self->ses->val['ssokey'] ?? null) ?: ($_SESSION['ssokey'] ?? ''));
        $pub = trim((string)($config->apikey->public ?? ''));
        $sec = trim((string)($config->apikey->secret ?? ''));
        if ($ssokey === '' || $pub === '' || $sec === '') { return $base; }
        $key = hash('sha256', $pub . '|' . $sec, true);
        $iv  = random_bytes(12);
        $tag = '';
        $ct  = openssl_encrypt(
            json_encode(array('t' => $ssokey, 'c' => $_SERVER['SERVER_NAME'], 'x' => time() + 300)),
            'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag
        );
        if ($ct === false) { return $base; }
        return $base . '&stoken=' . rtrim(strtr(base64_encode($iv . $tag . $ct), '+/', '-_'), '=');
    }
}