<?php
namespace API\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use API\ViewModel;
use Erpk\Harvester\Module\JobMarket\JobMarketModule;
use Erpk\Common\EntityManager;

class JobMarketController extends Controller
{
    public function get()
    {
        $module = new JobMarketModule($this->client);
        $em = EntityManager::getInstance();
        $countries = $em->getRepository('\Erpk\Common\Entity\Country');
        
        $country = $countries->findOneByCode($this->param('code'));
        $data = $module->scan($country, $this->param('page'));
        $data['@nodeName'] = 'offer';
        $vm = new ViewModel($data);
        $vm->setRootNodeName('offers');
        return $vm;
    }
}
