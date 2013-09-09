<?php
namespace Erpk\Harvester\Module\Management;

class Company
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getId()
    {
        return $this->data['id'];
    }

    public function isRaw()
    {
        return $this->data['is_raw'];
    }

    public function getQuality()
    {
        return $this->data['quality'];
    }

    public function hasAlreadyWorked()
    {
        return $this->data['already_worked'];
    }
}
