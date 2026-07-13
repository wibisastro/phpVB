<?php namespace App\gov2option;

use Gov2lib\csrf;

/**
 * Controller registry koneksi gov3 (menu "Gurita") — #6134 slice C.
 *
 * Endpoint JSON untuk panel sidepanel (UI menyusul slice E). Keamanan
 * keputusan 9: gate webmaster di constructor DAN diulang per-method di
 * setiap mutation + token CSRF (header X-Gov2-Csrf / field _csrf; klien
 * mengambil token dari respons getList).
 */
class connection
{
    function __construct()
    {
        global $self;
        $self->ses->authenticate('webmaster');
    }

    function index($vars)
    {
        return $this->getList($vars);
    }

    /** GET daftar koneksi + token CSRF untuk mutation berikutnya */
    function getList($vars)
    {
        global $self, $doc;

        return $doc->responseGet([
            'connections' => $self->listConnections(),
            'jenis' => \App\gov2option\model\connection::JENIS,
            'csrf' => csrf::token(),
        ]);
    }

    /** POST simpan (insert/update) satu koneksi */
    function save($payload)
    {
        global $self, $doc;
        $self->ses->authenticate('webmaster');
        csrf::guard();

        $result = $self->saveConnection($payload['data'] ?? [], (int) ($self->ses->val['account_id'] ?? 0) ?: null);

        if (isset($result['errors'])) {
            header('HTTP/1.1 422 Unprocessable Entity');

            return ['class' => 'is-warning', 'notification' => join('; ', $result['errors']), 'errors' => $result['errors']];
        }

        return $doc->response('is-primary', '', $result['id']);
    }

    /** POST hapus koneksi by id */
    function del($payload)
    {
        global $self, $doc;
        $self->ses->authenticate('webmaster');
        csrf::guard();

        $affected = $self->deleteConnection((int) ($payload['data']['id'] ?? 0));

        if (!$affected) {
            header('HTTP/1.1 404 Not Found');

            return ['class' => 'is-warning', 'notification' => 'Koneksi tidak ditemukan'];
        }

        return $doc->response('is-primary');
    }

    /** POST save-to-lower-tier: materialisasi options satu app ke pinned */
    function pin($payload)
    {
        global $self, $doc;
        $self->ses->authenticate('webmaster');
        csrf::guard();

        $result = $self->pinApp(
            (string) ($payload['data']['app'] ?? ''),
            (int) ($self->ses->val['account_id'] ?? 0) ?: null
        );

        if (isset($result['errors'])) {
            header('HTTP/1.1 422 Unprocessable Entity');

            return ['class' => 'is-warning', 'notification' => join('; ', $result['errors'])];
        }

        return [
            'class' => 'is-primary',
            'notification' => "Pinned {$result['rows']} rows (kambing: {$result['remote']}, cache: {$result['cache']})",
        ] + $result;
    }
}
