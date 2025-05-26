<?php namespace App\gov2option\fields;


class field implements fieldInterface
{

    function __construct() {
        $filepath = __DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."json".DIRECTORY_SEPARATOR."option_field.json";
        $of = file_get_contents($filepath);
        $field = json_decode($of, 1);
        foreach ($field as $f => $value) {
            $this->$f = $value;
        }
    }

    public function serialize()
    {
        return (array)$this;
    }
}