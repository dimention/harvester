<?php
namespace Erpk\Harvester\Client\Plugin\Maintenance;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MaintenancePlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'request.receive.status_line' => 'onRequestReceiveStatusLine',
            //'request.sent'              => array('onRequestSent', 100),
        );
    }
    
    public function onRequestReceiveStatusLine(Event $event)
    {
        if ($event['status_code'] == 200 &&
           $event['reason_phrase'] == 'Service Unavailable'
        ) {
            throw new MaintenanceException;
        }
    }
}
