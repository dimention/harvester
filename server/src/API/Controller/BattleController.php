<?php
namespace API\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use API\ViewModel;
use Erpk\Harvester\Module\Military\MilitaryModule;

class BattleController extends Controller
{
    public function battle()
    {
        $module = new MilitaryModule($this->client);
        $data = $module->getCampaign($this->param('id'));
        
        foreach (array('attacker','defender') as $side) {
            for ($i=1; $i <= 4; $i++) {
                $data[$side]['divisions'][$i]['top_fighters']['@nodeName']='citizen';
            }
            $data[$side]['divisions']['@nodeName'] = 'division';
        }

        $vm = new ViewModel($data);
        $vm->setRootNodeName('battle');
        return $vm;
    }
    
    public function active()
    {
        $module = new MilitaryModule($this->client);

        $data = $module->getActiveCampaigns();
        $data = $data['all'];
        $data['@nodeName'] = 'battle';
        
        $vm = new ViewModel($data);
        $vm->setRootNodeName('battles');
        return $vm;
    }
}
