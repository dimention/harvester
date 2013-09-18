<?php
namespace Erpk\Harvester\Client\Proxy;

use Erpk\Harvester\Client\Client;

class HttpProxy implements ProxyInterface
{
    public $hostname;
    public $port;
    public $username;
    public $password;
    
    public function __construct($host, $port, $user = null, $pass = null)
    {
        $this->hostname = $host;
        $this->port = $port;
        $this->username = $user;
        $this->password = $pass;
    }
    
    public function apply(Client $client)
    {
        $config = $client->getConfig();
        $curl = $config->get('curl.options');
        
        $curl[CURLOPT_PROXY] = $this->hostname.':'.$this->port;
        if (isset($this->password)) {
            $curl[CURLOPT_PROXYUSERPWD] = $this->username.':'.$this->password;
        }
        
        $config->set('curl.options', $curl);
    }
    
    public function remove(Client $client)
    {
        $config = $client->getConfig();
        $curl = $config->get('curl.options');
        
        unset(
            $curl[CURLOPT_PROXY],
            $curl[CURLOPT_PROXYUSERPWD]
        );
        
        $config->set('curl.options', $curl);
    }
}
