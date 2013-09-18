<?php
namespace Erpk\Harvester\Client\Proxy;

use Erpk\Harvester\Client\Client;

interface ProxyInterface
{
    public function apply(Client $client);
    public function remove(Client $client);
}
