<?php
namespace Erpk\Harvester\Module;

use Erpk\Common\EntityManager;
use Erpk\Harvester\Filter;
use Erpk\Harvester\Client\Client;
use Erpk\Harvester\Client\bwClient;

abstract class Module
{
    protected $client;
    
    public function __construct($client)
    {
        $this->client = $client;
        if (is_callable([$this, 'init'])) {
            $this->init();
        }
    }

    /**
     * @return Client|bwClient
     */
    public function getClient()
    {
        return $this->client;
    }
    
    public function getSession()
    {
        return $this->client->getSession();
    }
    
    public function getEntityManager()
    {
        return EntityManager::getInstance();
    }

    protected function filter(&$variable, $filter)
    {
        $variable = Filter::$filter($variable);
    }
}
