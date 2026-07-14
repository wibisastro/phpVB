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

        $data = $payload['data'] ?? [];
        $result = $self->saveConnection($data, (int) ($self->ses->val['account_id'] ?? 0) ?: null);

        if (isset($result['errors'])) {
            header('HTTP/1.1 422 Unprocessable Entity');

            return ['class' => 'is-warning', 'notification' => join('; ', $result['errors']), 'errors' => $result['errors']];
        }

        $response = $doc->response('is-primary', '', $result['id']);

        // Registrasi gurita: langsung simpan inventori tools/list (#6134
        // slice D). Gagal discover ≠ gagal save — koneksi tersimpan, admin
        // bisa ulang via endpoint tools
        if (($data['jenis'] ?? '') === 'gurita' && ($data['status'] ?? 'on') !== 'off') {
            $discovered = $self->discoverTools($result['id']);
            $response['tools'] = $discovered['tools'] ?? 0;

            if (isset($discovered['errors'])) {
                $response['notification'] .= ' — discover tools gagal: ' . join('; ', $discovered['errors']);
            }
        }

        return $response;
    }

    /** POST refresh inventori tools/list satu koneksi gurita */
    function tools($payload)
    {
        global $self, $doc;
        $self->ses->authenticate('webmaster');
        csrf::guard();

        $result = $self->discoverTools((int) ($payload['data']['id'] ?? 0));

        if (isset($result['errors'])) {
            header('HTTP/1.1 422 Unprocessable Entity');

            return ['class' => 'is-warning', 'notification' => join('; ', $result['errors'])];
        }

        return ['class' => 'is-primary', 'notification' => "{$result['tools']} tools tersimpan"] + $result;
    }

    /** POST import hasil tools/call gurita → rows options app tujuan */
    function import($payload)
    {
        global $self, $doc;
        $self->ses->authenticate('webmaster');
        csrf::guard();

        $data = $payload['data'] ?? [];
        $arguments = $data['arguments'] ?? [];

        $result = $self->importFromTool(
            (int) ($data['id'] ?? 0),
            trim((string) ($data['tool'] ?? '')),
            is_array($arguments) ? $arguments : [],
            trim((string) ($data['app'] ?? '')),
            (int) ($self->ses->val['account_id'] ?? 0) ?: null
        );

        if (isset($result['errors'])) {
            header('HTTP/1.1 422 Unprocessable Entity');

            return ['class' => 'is-warning', 'notification' => join('; ', $result['errors'])];
        }

        return [
            'class' => 'is-primary',
            'notification' => "Import {$result['rows']} rows ke app '{$result['app']}'",
        ] + $result;
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
