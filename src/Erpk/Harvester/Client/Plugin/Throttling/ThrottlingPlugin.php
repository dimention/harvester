<?php
namespace Erpk\Harvester\Client\Plugin\Throttling;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ThrottlingPlugin implements EventSubscriberInterface
{
    protected $client;
    protected $throttler;
    
    public function __construct($throttler)
    {
        $this->throttler = $throttler;
    }
    
    public function getThrottler()
    {
        return $this->throttler;
    }
    
    protected function getExternalAddressId($client)
    {
        if ($client->hasProxy()) {
            return $client->getProxy()->getId();
        } else {
            return 'localhost';
        }
    }

    public function onRequestBeforeSend(Event $event)
    {
        $client = $event['request']->getClient();
        $id = $this->getExternalAddressId($client);
        if ($this->throttler->isOverloaded($id)) {
            throw new ThrottlingException('Too many requests, throttling.');
        }
    }
    
    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send'         => array('onRequestBeforeSend', 200),
            'request.receive.status_line' => array('onRequestReceiveStatusLine',200)
        );
    }
    
    public function onRequestReceiveStatusLine(Event $event)
    {
        $client = $event['request']->getClient();
        $id = $this->getExternalAddressId($client);
        $this->throttler->push($id);
    }
}
