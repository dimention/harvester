<?php
namespace API\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use API\ViewModel;
use Erpk\Harvester\Module\Market\MarketModule;
use Erpk\Common\EntityManager;

class MarketController extends Controller
{
    public function get()
    {
        $module = new MarketModule($this->client);
        $em = EntityManager::getInstance();
        $countries = $em->getRepository('\Erpk\Common\Entity\Country');
        $industries = $em->getRepository('\Erpk\Common\Entity\Industry');
        
        $country  = $countries->findOneByCode($this->param('country'));
        $industry = $industries->findOneByCode($this->param('industry'));

        $data = $module->scan(
            $country,
            $industry,
            $this->param('quality'),
            $this->param('page')
        );
        $data['@nodeName']='offer';
        $vm = new ViewModel($data);
        $vm->setRootNodeName('offers');
        return $vm;
    }
}
