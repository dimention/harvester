<?php
namespace Erpk\Harvester\Client\Proxy;

abstract class Proxy
{
    public function getId()
    {
        return $this->id;
    }
}
