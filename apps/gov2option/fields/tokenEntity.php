<?php namespace App\gov2option\fields;


class tokenEntity
{
    function __construct()
    {
        $fields = [
            'iat' => 'text',
            'iss' => 'text',
            'aud' => 'text',
            'key' => 'text',
            'kllist' => 'text',
            'datalevel' => 'text',
            'exp' => 'text',
            'dataset' => 'textbox',
            'token' => 'textbox',
            'counter' => 'text',
            'suspend' => 'checkbox'
        ];
        foreach ($fields as $f => $type) {
            $this->$f = new field();
            $this->$f->nama = $f;
            $this->$f->type = $type;
            $this->$f->level = 2;
            $this->$f->level_label = 'option';
            if ($f !== 'suspend') {
                $this->$f->status = 'off';
            }
        }
    }

    public function serialize()
    {
        $serialized = [];
        $props = (array)$this;
        foreach ($props as $prop => $val) {
            if (method_exists($val, 'serialize')) {
                array_push($serialized, $val->serialize());
            }
        }
        return $serialized;
    }
}