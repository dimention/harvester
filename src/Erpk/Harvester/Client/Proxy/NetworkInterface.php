<?php
namespace Erpk\Harvester\Client\Proxy;

use Erpk\Harvester\Client;

class NetworkInterface extends Proxy implements ProxyInterface
{
    public $iface;
    
    public function __construct($iface)
    {
        $this->iface = $iface;
        
        $this->id = md5($iface);
    }
    
    public function apply(Client $client)
    {
        $config = $client->getConfig();
        $config->set('curl.CURLOPT_INTERFACE', $this->iface);
    }
    
    public function remove(Client $client)
    {
        $config = $client->getConfig();
        $config->remove('curl.CURLOPT_INTERFACE');
    }
}
