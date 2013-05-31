<?php
namespace Erpk\Harvester\Client\Proxy;

use Erpk\Harvester\Client;

interface ProxyInterface
{
    public function getId();
    public function apply(Client $client);
}
