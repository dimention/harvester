<?php
namespace Erpk\Harvester\Client;

use Erpk\Harvester\Exception;
use Erpk\Harvester\Client\Plugin\Maintenance\MaintenancePlugin;
use GuzzleHttp\Client as GuzzleClient;

class bwClient extends GuzzleClient
{
    public function __construct()
    {
        $defaults = [
            'base_url' => 'http://battle-watcher.com',
            'defaults' => [
                'allow_redirects' => false,
                'timeout' => 5000,
                'headers' => [
                    'Expect' => '',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.8',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.66 Safari/537.36'
                ]]
        ];

        parent::__construct($defaults);
        $this->getEmitter()->attach(new MaintenancePlugin);
    }
}
