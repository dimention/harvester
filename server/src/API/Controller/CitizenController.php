<?php
namespace API\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use API\ViewModel;
use Erpk\Harvester\Module\Citizen\CitizenModule;

class CitizenController extends Controller
{

    public function profile()
    {
        $module = new CitizenModule($this->client);
        $data = $module->get($this->param('id'));

        $vm = new ViewModel($data);
        $vm->setRootNodeName('citizen');
        return $vm;
    }
    
    public function search()
    {
        $module = new CitizenModule($this->client);
        $data = $module->search($this->param('query'), $this->param('page'));
        $data['@nodeName'] = 'citizen';
        
        $vm = new ViewModel($data);
        $vm->setRootNodeName('results');
        return $vm;
    }
}
