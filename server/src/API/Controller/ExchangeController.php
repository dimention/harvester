<?php
namespace API\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use API\ViewModel;
use Erpk\Harvester\Module\Exchange\ExchangeModule;

class ExchangeController extends Controller
{
    public function get()
    {
        switch ($this->param('mode')) {
            case 0:
            case 'cc':
                $buy = ExchangeModule::CURRENCY;
                break;
            case 'gold':
            case 1:
            default:
                $buy = ExchangeModule::GOLD;
                break;
        }

        $module = new ExchangeModule($this->client);
        $data = $module->scan($buy, $this->param('page'));
        $data['@nodeName'] = 'offer';

        $vm = new ViewModel($data);
        $vm->setRootNodeName('offers');
        return $vm;
    }
}
