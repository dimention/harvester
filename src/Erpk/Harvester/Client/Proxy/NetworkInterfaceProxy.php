<?php
namespace Erpk\Harvester\Client\Proxy;

use Erpk\Harvester\Client\Client;

class NetworkInterfaceProxy implements ProxyInterface
{
    public $iface;
    
    public function __construct($iface)
    {
        $this->iface = $iface;
    }
    
    public function apply(Client $client)
    {
        $config = $client->getConfig();
        $curl = $config->get('curl.options');
        $curl[CURLOPT_INTERFACE] = $this->iface;
        $config->set('curl.options', $curl);
    }
    
    public function remove(Client $client)
    {
        $config = $client->getConfig();
        $curl = $config->get('curl.options');
        unset($curl[CURLOPT_INTERFACE]);
        $config->set('curl.options', $curl);
    }
}
