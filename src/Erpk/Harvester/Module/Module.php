<?php
namespace Erpk\Harvester\Module;

use Erpk\Common\EntityManager;
use Erpk\Harvester\Client\Client;
use Erpk\Harvester\Filter;

abstract class Module
{
    protected $client;
    
    public function __construct(Client $client)
    {
        $this->client = $client;
        if (is_callable(array($this, 'init'))) {
            $this->init();
        }
    }
    
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
