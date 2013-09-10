<?php
namespace Erpk\Harvester\Module\Management;

class Company
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        } else {
            return null;
        }
    }

    public function getId()
    {
        return $this->get('id');
    }

    public function isRaw()
    {
        return $this->get('is_raw');
    }

    public function getQuality()
    {
        return $this->get('quality');
    }

    public function hasAlreadyWorked()
    {
        return $this->get('already_worked');
    }
}
