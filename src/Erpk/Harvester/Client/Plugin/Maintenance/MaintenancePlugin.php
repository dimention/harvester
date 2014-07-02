<?php
namespace Erpk\Harvester\Client\Plugin\Maintenance;

use GuzzleHttp\Event;

class MaintenancePlugin implements Event\SubscriberInterface
{
    public function getEvents()
    {
        return [
            'complete' => ['onRequestReceiveStatusLine']
        ];
    }
    
    public function onRequestReceiveStatusLine(Event\CompleteEvent $event)
    {
        if ($event->getResponse()->getStatusCode() == 200 &&
           $event->getResponse()->getReasonPhrase() == 'Service Unavailable'
        ) {
            throw new MaintenanceException;
        }
    }
}
