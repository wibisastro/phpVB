<?php

namespace Gov2lib;

/**
 * Request handler for CRUD operations.
 * Processes form data, validation, and generates responses.
 *
 * @version 2.0 - PHP 8.4 refactor
 */
class crudHandler extends crudModel
{
    public function __construct(string $dsn = '')
    {
        parent::__construct($dsn);
    }

    /**
     * Handle tag deletion request.
     */
    public function postDelTag(array $data): array
    {
        global $doc;

        if (empty($data['id'])) {
            header('HTTP/1.1 422 Incomplete fields');
            return ['id' => 'No ID number'];
        }

        $this->doDel((int) $data['id']);
        return $doc->response('is-primary');
    }

    /**
     * Handle tag browsing request.
     */
    public function getBrowseTags(
        int $id,
        string $source,
        string $target,
        string $target2 = '',
        string $caption = ''
    ): mixed {
        global $doc, $config, $self;

        if (
            in_array($source, ['wilayah', 'kementerian', 'renstra'])
            && $id === -2
            && empty($self->ses->val[$this->className . '_id'])
        ) {
            $id = (int) trim((string) ($config->domain->attr['id'] ?? ''));
        }

        $id = $this->setRememberId($id, $source);
        $data = $this->doBrowseTags($id, $source, $target, $target2, $caption);

        if (empty($data)) {
            $data = ['data' => 'empty'];
        }

        return $doc->responseGet($data);
    }

    /**
     * Handle tag creation request.
     */
    public function postTagging(
        array $data,
        string $source,
        string $target,
        string $target2 = '',
        string $caption = ''
    ): array {
        global $doc, $config;

        if (empty($data['source_id']) || empty($data['target_id'])) {
            header('HTTP/1.1 422 Incomplete fields');
            return [
                'class' => (string) ($config->css->attr['is-warning'] ?? 'is-warning'),
                'notification' => 'Pasangan ID tidak lengkap',
            ];
        }

        $id = $this->doTagging($data, $source, $target, $target2, $caption);

        if (!is_array($doc->error)) {
            $record = $this->doRead($id);
            return $doc->response('is-primary', '', (int) ($record['id'] ?? 0));
        }

        header('HTTP/1.1 422 Insert Fails');
        return [
            'class' => 'is-danger',
            'notification' => $doc->error,
            'callback' => 'infoSnackbar',
        ];
    }

    /**
     * Handle record creation request with validation.
     */
    public function postAdd(array $data, mixed $fields = null): array
    {
        global $doc, $config, $requester;

        $fieldSet = $fields ?: ($this->fields ?? null);
        $errors = $this->gov2formfield->checkRequired($data, $fieldSet);

        if (is_array($errors)) {
            header('HTTP/1.1 422 Incomplete fields');
            $errors['class'] = (string) ($config->css->warning ?? 'is-warning');
            $errors['notification'] = 'Harap isi form dengan lengkap';
            return $errors;
        }

        $id = $this->doAdd($data);

        if (!is_array($doc->error)) {
            $record = $this->doRead($id);
            return $doc->response('is-primary', 'resetButton', (int) ($record['id'] ?? 0));
        }

        if (($requester ?? '') === 'browser') {
            header('HTTP/1.1 422 Query Fails');
        }

        return $doc->response('is-danger', 'resetButton');
    }

    /**
     * Handle record deletion request.
     */
    public function postDel(array $data): array
    {
        global $doc, $requester;

        if (empty($data['id'])) {
            if (($requester ?? '') === 'browser') {
                header('HTTP/1.1 422 Incomplete fields');
            }
            return ['id' => 'No ID number'];
        }

        $this->doDel((int) $data['id']);
        return $doc->response('is-primary', 'confirmClose', (int) $data['id']);
    }

    /**
     * Handle record update request with validation.
     */
    public function postUpdate(array $data): array
    {
        global $fields, $doc, $requester;

        $errors = $this->gov2formfield->checkRequired($data, $fields);

        if (is_array($errors)) {
            header('HTTP/1.1 422 Incomplete fields');
            return $errors;
        }

        $this->doUpdate($data);

        if (!is_array($doc->error)) {
            $updated = $this->doRead((int) ($data['id'] ?? 0));
            return $doc->response('is-info', 'toggleForm', (int) ($updated['id'] ?? $data['id'] ?? 0));
        }

        if (($requester ?? '') === 'browser') {
            header('HTTP/1.1 422 Incomplete fields');
        }

        return $doc->response('is-danger', 'toggleForm', (int) ($data['id'] ?? 0));
    }

    /**
     * Get breadcrumb data for a hierarchical record.
     */
    public function getBreadcrumb(
        string $root,
        int $id = 0,
        string $caption = '',
        string $code = ''
    ): mixed {
        global $vars, $doc, $config;

        $this->breadcrumb = [];

        if (!$id) {
            $id = (int) ($vars['id'] ?? 0);
        }

        $id = $this->setRememberId($id, $caption);
        $this->setBreadcrumb($id, $caption, $code);

        if (!$doc->error) {
            krsort($this->breadcrumb);

            $data = [];
            $counter = 1;

            if ($root) {
                $data[0] = [
                    'caption' => $root,
                    'id' => '0',
                    'level' => '0',
                    'code' => '0',
                    'level_label' => $root,
                ];
            }

            foreach ($this->breadcrumb as $val) {
                $data[$counter] = $val;
                $counter++;
            }

            return $data;
        }

        header('HTTP/1.1 422 Query Table Fails');
        return $doc->response('is-danger');
    }

    /**
     * Get children info for a hierarchical record.
     */
    public function getChildren(int $id): \stdClass
    {
        $data = new \stdClass();

        if (!$id) {
            $data->level = '1';
        } else {
            $result = $this->doRead($id);
            $data->level = (string) (($result['level'] ?? 0) + 1);
        }

        return $data;
    }

    /**
     * Get a single record by ID.
     */
    public function getRecord(int $id): array
    {
        if (!$id) {
            header('HTTP/1.1 422 Tidak ada nomor ID');
            return ['id' => 'Tidak ada nomor ID'];
        }

        $id = $this->setRememberId($id);
        $response = $this->doRead($id);

        if (empty($response)) {
            return ['data' => 'empty', 'level' => '1'];
        }

        return $response;
    }

    /**
     * Get paginated records.
     */
    public function getRecords(array $vars, string $parentName = ''): mixed
    {
        global $doc;

        $id = $this->setRememberId((int) ($vars['id'] ?? 0));
        $data = $this->doBrowse($vars['scroll'] ?? 0, $id, $parentName);

        if (empty($data)) {
            $data = ['data' => 'empty', 'level' => '1'];
        }

        return $doc->responseGet($data);
    }

    /**
     * Get count of children for a record.
     */
    public function getCount(int $id): mixed
    {
        global $doc;

        $id = $this->setRememberId($id);
        $data = $this->doCountChildren($id);

        return $doc->responseGet($data);
    }

    /**
     * Remember/restore an ID in the session for stateful browsing.
     */
    public function setRememberId(int $id, string $source = ''): int
    {
        global $self;

        $classname = $source ?: ($self->className ?? '');

        if ($id === -1) {
            $id = (int) ($self->ses->val[$classname . '_id'] ?? 0);
        } elseif ($id === -2) {
            $self->ses->val[($self->className ?? '') . '_id'] = '';
            $id = 0;
        } elseif ($id !== 0) {
            $self->ses->val[$classname . '_id'] = $id;
        }

        $self->ses->sesSave($self->ses->val);

        return $id;
    }
}
