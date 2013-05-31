<?php
namespace API\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use API\ViewModel;
use Erpk\Harvester;
use Erpk\Harvester\Module\Military\MilitaryModule;

class MUController extends Controller
{
    public function get()
    {
        $module = new MilitaryModule($this->client);
        $data = $module->getUnit($this->param('unit'));

        $data['regiments']['@nodeName']='regiment';
        $vm = new ViewModel($data);
        $vm->setRootNodeName('military-unit');
        return $vm;
    }
    
    public function getRegiment()
    {
        $module = new MilitaryModule($this->client);
        $data = $module->getRegiment($this->param('unit'), $this->param('regiment'));
        foreach ($data as &$s) {
            $s['location'] = $s['location']->toArray();
        }
        $data['@nodeName'] = 'member';

        $vm = new ViewModel($data);
        $vm->setRootNodeName('members');
        return $vm;
    }
}
