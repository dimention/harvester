<?php
namespace API\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use API\ViewModel;
use Erpk\Harvester\Module\Country\CountryModule;
use Erpk\Common\EntityManager;

class CountryController extends Controller
{
    protected function get($type)
    {
        $module = new CountryModule($this->client);
        $em = EntityManager::getInstance();
        $countries = $em->getRepository('\Erpk\Common\Entity\Country');
        $country = $countries->findOneByCode($this->param('code'));
        $data = $module->{'get'.$type}($country);

        if ($this->param('type') === 'Economy') {
            $data['embargoes']['@nodeName'] = 'embargo';
        }
        $vm = new ViewModel($data);
        $vm->setRootNodeName('country');
        
        return $vm;
    }
    
    public function society()
    {
        return $this->get('Society', $this->param('code'));
    }
    
    public function economy()
    {
        return $this->get('Economy', $this->param('code'));
    }
}
