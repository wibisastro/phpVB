<?php

namespace App\gov2wilayah;

class sidepanel extends \Gov2lib\api
{
    public function __construct()
    {
        global $self;
        parent::__construct();
        $self->scrollInterval = 100;
    }

    public function breadcrumb(array $vars): mixed
    {
        global $self;
        return $self->getBreadcrumb('INDONESIA');
    }

    public function listWilayah(array $vars): mixed
    {
        global $self;
        return $self->getRecords($vars);
    }

    public function children(array $vars): array
    {
        global $self;
        return (array) $self->getChildren((int) ($vars['id'] ?? 0));
    }

    public function getWilayahConfig(): mixed
    {
        global $self, $doc;
        $result = [
            'userRole' => $self->ses->val['userRole'] ?? '',
            'locked' => false,
            'wilayah_nama' => $self->ses->val['wilayah_nama'] ?? '',
            'wilayah_id' => $self->ses->val['wilayah_id'] ?? null,
            'wilayah_level' => $self->ses->val['wilayah_level'] ?? '',
            'wilayah_parent_id' => $self->ses->val['wilayah_parent_id'] ?? null,
        ];

        $role = $result['userRole'];
        if ($role === '' || $role === 'member') {
            $result['locked'] = true;
        }

        return $doc->responseGet($result);
    }

    public function changeWilayah(array $vars): mixed
    {
        global $self, $doc;
        $wilayahId = (int) ($vars['id'] ?? 0);
        $nama = $_GET['nama'] ?? '';
        $level = $_GET['level'] ?? '';
        $parentId = (int) ($_GET['parent_id'] ?? 0);

        if ($wilayahId && $nama) {
            $self->ses->val['wilayah_id'] = $wilayahId;
            $self->ses->val['wilayah_nama'] = $nama;
            $self->ses->val['wilayah_level'] = $level;
            $self->ses->val['wilayah_parent_id'] = $parentId;
            $self->ses->sesSave($self->ses->val);
        }

        return $doc->responseGet(['status' => 'ok', 'wilayah_id' => $wilayahId, 'wilayah_nama' => $nama]);
    }

    public function resetWilayah(): mixed
    {
        global $self, $doc;
        $self->ses->val['wilayah_id'] = null;
        $self->ses->val['wilayah_nama'] = null;
        $self->ses->val['wilayah_level'] = null;
        $self->ses->val['wilayah_parent_id'] = null;
        $self->ses->sesSave($self->ses->val);

        return $doc->responseGet(['status' => 'ok']);
    }

    public function searchWilayah(): mixed
    {
        global $self, $doc;
        $keyword = $_GET['q'] ?? '';
        $fields = "id, parent_id, `level`, level_label, nama";
        $result = [];

        try {
            $q = "SELECT {$fields} FROM {$self->tbl->wilayah}
                  WHERE nama LIKE %ss
                  ORDER BY level ASC, nama ASC LIMIT 50";
            $result = \DB::query($q, $keyword);
        } catch (\MeekroDBException $e) {
        }

        return $doc->responseGet($result);
    }
}
