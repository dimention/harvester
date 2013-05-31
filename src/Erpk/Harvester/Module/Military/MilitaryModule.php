<?php
namespace Erpk\Harvester\Module\Military;

use Erpk\Harvester\Module\Module;
use Erpk\Harvester\Exception\ScrapeException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Common\Exception\ExceptionCollection;
use Erpk\Harvester\Exception\NotFoundException;
use Erpk\Harvester\Client\Selector;
use Erpk\Common\Citizen\Rank;
use Erpk\Harvester\Filter;

class MilitaryModule extends Module
{
    public function getActiveCampaigns()
    {
        $this->getClient()->checkLogin();
        
        $response = $this->getClient()->get('military/campaigns')->send();
        $hxs = Selector\XPath::loadHTML($response->getBody(true));
        
        $listing = $hxs->select('//div[@id="battle_listing"]');
        $ul = array(
            'all'      => '//ul[@class="all_battles"]',
            'bod'      => '//ul[@class="bod_listing"]',
            'country'  => '//ul[@class="country_battles"]',
            'allies'   => '//ul[@class="allies_battles"]'
        );
        $result = array();
        
        foreach ($ul as $type => $xpath) {
            $battles = $listing->select($xpath.'/li');
            $result[$type] = array();
            if (!$battles->hasResults()) {
                continue;
            }
            
            foreach ($battles as $li) {
                $id = $li->select('@id')->extract();
                $id = (int)substr($id, strpos($id, '-')+1);
                $result[$type][] = $id;
                $result['all'][] = $id;
            }
            sort($result[$type]);
        }
        $result['all'] = array_unique($result['all']);
        sort($result['all']);
        return $result;
    }
    
    protected function parseBattleField($html)
    {
        preg_match(
            '/var SERVER_DATA\s*=\s*({[^;]*)/i',
            $html,
            $serverDataRaw
        );
        
        if (!preg_match('/battleId\s*:\s*([0-9]+)/i', $serverDataRaw[1], $id)) {
            throw new ScrapeException;
        } else {
            $id = (int)$id[1];
        }
        
        if (!preg_match('/mustInvert\s*:\s*([a-z]+)/i', $serverDataRaw[1], $mustInvert)) {
            throw new ScrapeException;
        } else {
            $mustInvert = $mustInvert[1] == 'true';
        }
        
        if (!preg_match('/invaderId\s*:\s*([0-9]+)/i', $serverDataRaw[1], $invaderId)) {
            throw new ScrapeException;
        } else {
            $invaderId = (int)$invaderId[1];
        }
        
        if (!preg_match('/defenderId\s*:\s*([0-9]+)/i', $serverDataRaw[1], $defenderId)) {
            throw new ScrapeException;
        } else {
            $defenderId = (int)$defenderId[1];
        }
        
        if (!preg_match('/isResistance\s*:\s*([0-9]+)/i', $serverDataRaw[1], $isResistance)) {
            throw new ScrapeException;
        } else {
            $isResistance = $isResistance[1] == 1;
        }
        
        $regions = $this->getEntityManager()->getRepository('Erpk\Common\Entity\Region');
        $countries = $this->getEntityManager()->getRepository('Erpk\Common\Entity\Country');
        
        $hxs = Selector\XPath::loadHTML($html);
        $regionName = $hxs->select('//div[@id="pvp_header"][1]/h2[1]')->extract();
        $region = $regions->findOneByName($regionName);
        
        $battle = new \Erpk\Common\Entity\Battle;
        $battle->setId($id);
        $battle->setAttacker($countries->find($mustInvert ? $defenderId : $invaderId));
        $battle->setDefender($countries->find($mustInvert ? $invaderId : $defenderId));
        $battle->setRegion($region);
        $battle->setResistance($isResistance);
        
        return $battle;
    }
    
    public function getCampaign($id)
    {
        $startTime = microtime(true);
        
        $id = Filter::id($id);
        
        $battles = $this->getEntityManager()->getRepository('Erpk\Common\Entity\Battle');
        $countries = $this->getEntityManager()->getRepository('Erpk\Common\Entity\Country');
        
        $battleData = $battles->findOneById($id);
        
        $this->getClient()->checkLogin();
        try {
            $requests[] = $this->getClient()->get('military/battle-stats/'.$id.'/1');
            if ($battleData === null) {
                $requests[] = $this->getClient()->get('military/battlefield/'.$id);
            }
            
            $responses = $this->getClient()->send($requests);
            
            $battleStats = json_decode($responses[0]->getBody(true), true);
            if ($battleData === null) {
                /**
                 * Resistance wars FIX
                 */
                if (
                   $responses[1]->isRedirect() and
                   preg_match('#^http://www.erepublik.com/en/wars/show/([0-9]+)$#', $responses[1]->getLocation())
                ) {
                    
                    $war = $this->getClient()->get($responses[1]->getLocation())->send();
                    preg_match(
                        '#http://www.erepublik.com/en/military/battlefield-choose-side/[0-9]+/[0-9]+#',
                        $war->getBody(true),
                        $links
                    );
                    
                    $responses[1] = $this->getClient()->get($links[0])->send();
                    if ($responses[1]->isRedirect()) {
                        $responses[1] = $this->getClient()->get($responses[1]->getLocation())->send();
                    }
                }
                
                $battleData = $this->parseBattleField($responses[1]->getBody(true));
                $this->getEntityManager()->persist($battleData);
                $this->getEntityManager()->flush();
            }
        } catch (ExceptionCollection $e) {
            foreach ($e as $ee) {
                if ($ee instanceof ClientErrorResponseException and
                    $ee->getResponse()->getStatusCode() == 404) {
                    throw new NotFoundException;
                } else {
                    throw $ee;
                }
            }
        }
        
        $finished = $battleStats['division'][$battleData->getAttacker()->getId()]['total'] >= 83
                    || $battleStats['division'][$battleData->getDefender()->getId()]['total'] >= 83;
        
        if (!$finished) {
            $fightersData=$battleStats['fightersData'];
            $current=array_shift($battleStats['stats']['current']);
        }
        
        $battle = $battleData->toArray();
        $battle['is_finished'] = $finished;
        
        $sides = array('attacker' => $battleData->getAttacker(), 'defender' => $battleData->getDefender());
        foreach ($sides as $side => $info) {
            $sideId = $info->getId();
            
            $battle[$side] = $info->toArray();
            $battle[$side]['points'] =    (int)$battleStats['division'][$info->getId()]['total'];
            $battle[$side]['divisions']    = array();
            
            for ($n=1; $n<=4; $n++) {
                $tf = array();
                if (isset($current[$n][$info->getId()])) {
                    foreach ($current[$n][$info->getId()] as $fighter) {
                        $id = (int)$fighter['citizen_id'];
                        $data = $fightersData[$id];
                        $country = $countries->find($data['residence_country_id']);
                        if (!$country) {
                            throw new ScrapeException;
                        }
                        
                        $tf[] = array(
                            'id'        => $id,
                            'name'      => $data['name'],
                            'avatar'    => Selector\Filter::normalizeAvatar($data['avatar']),
                            'birth'     => substr($data['created_at'], 0, 10),
                            'country'   => $country,
                            'damage'    => (int)$fighter['damage'],
                            'kills'     => (int)$fighter['kills']
                        );
                    }
                }
                
                $bar = $battleStats['division']['domination'][$n];
                if ($side == 'attacker') {
                    $bar = 100-$bar;
                }
                
                $battle[$side]['divisions'][(int)$n] = array(
                    'points'       => $battleStats['division'][$info->getId()][$n]['points'],
                    'bar'          => $bar,
                    'domination'   => (int)$battleStats['division'][$info->getId()][$n]['domination'],
                    'won'          => $battleStats['division'][$info->getId()][$n]['won']==1,
                    'top_fighters' => $tf
                );
            }
        }
        
        return $battle;
    }
    
    public function getUnit($id)
    {
        $id = Filter::id($id);
        $request = $this->getClient()->get('main/group-list/members/'.$id);
        
        try {
            $response = $request->send();
        } catch (ClientErrorResponseException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                throw new NotFoundException('Military Unit '.$id.' not found.');
            } else {
                throw $e;
            }
        }
        
        $hxs = Selector\XPath::loadHTML($response->getBody(true));
        $content = $hxs->select('//div[@id="content"]');
        if (!$content->hasResults()) {
            throw new ScrapeException;
        }
        
        $header = $content->select('div[@id="military_group_header"]');
        if (!$header->hasResults()) {
            throw new ScrapeException;
        }
        
        $members = $header->select('div[@class="header_content"]/h2/big')->extract();
        $members = explode(' ', $members);
        $members = (int)$members[0];
        
        $countries = $this->getEntityManager()->getRepository('Erpk\Common\Entity\Country');
        $country = $header->select('div[@class="header_content"]/div[@class="details"]/a[1]/img/@alt')->extract();
        $country = $countries->findOneByName($country);
        if (!$country) {
            throw new ScrapeException;
        }
        
        $avatar = $header->select('//img[@id="avatar"]/@src')->extract();
        preg_match('#[0-9]{4}/[0-9]{2}/[0-9]{2}#', $avatar, $created);
        
        $details=$header->select('div[@class="header_content"]/div[@class="details"]');
        $url=$details->select('a[2]/@href')->extract();
        
        $regs = array();
        $regiments = $content->select('//select[@id="regiments_lists"]/option/@value');
        foreach ($regiments as $regiment) {
            $regs[] = (int)$regiment->extract();
        }
        $regs = array_unique($regs);
        
        $result = array(
            'id'         => $id,
            'name'       => $header->select('div[@class="header_content"]/h2/span')->extract(),
            'avatar'     => $avatar,
            'created_at' => strtr($created[0], '/', '-'),
            'location'   => $country,
            'members'    => $members,
            'about'      => $header->select('//span[@id="editable_about"]')->extract(),
            'commander'  =>  array(
                'id'         =>  (int)substr($url, strrpos($url, '/')+1),
                'name'       =>  $header->select(
                    'div[@class="header_content"]/div[@class="details"]/a[2]/@title'
                )->extract()
            ),
            'regiments'  => $regs
        );
        
        return $result;
    }
    
    public static function getUnitAvatar($id, DateTime $createdAt, $size = 'medium')
    {
        return
            'http://static.erepublik.com/uploads/avatars/Groups/'.
            $createdAt->format('Y/m/d').'/'.
            md5($id).'_'.$size.'.jpg';
    }
    
    public function getRegiment($unit, $regiment)
    {
        $unit = Filter::id($unit);
        $regiment = Filter::id($regiment);
        
        $this->getClient()->checkLogin();
        $request = $this->getClient()->get('main/group-list/members/'.$unit.'/'.$regiment);
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        try {
            $response = $request->send();
        } catch (ClientErrorResponseException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                throw new NotFoundException('Regiment '.$regiment.' not found.');
            } else {
                throw $e;
            }
        }
        
        $result = array();
        
        $countries = $this->getEntityManager()->getRepository('Erpk\Common\Entity\Country');
        
        $hxs = Selector\XPath::loadHTML($response->getBody(true));
        
        if ($hxs->select('//table[@class="info_message"][1]/tr[1]/td[1]')->hasResults()) {
            return array();
        }
        
        $members = $hxs->select('//table[@regimentid="'.$regiment.'"][1]/tbody[1]/tr');
        
        if (!$members->hasResults()) {
            return array();
        } else {
            foreach ($members as $member) {
                $avatar = $member->select('td[@class="avatar"]');
                $mrank  = $member->select('td[@class="mrank"]');
                try {
                    $location = $avatar->select('div[@class="current_location"][1]/span[1]/span[1]/@title')->extract();
                } catch (NotFoundException $e) {
                    throw new ScrapeException;
                }
                $rankPoints = (int)$mrank->select('@sort')->extract();
                $result[] = array(
                    'id'        =>  (int)$member->select('@memberid')->extract(),
                    'name'      =>  $avatar->select('@sort')->extract(),
                    'status'    =>  $member->select('td[@class="status"][1]/div[1]/strong[1]')->extract(),
                    'avatar'    =>  str_replace('_55x55', '', $avatar->select('img[1]/@src')->extract()),
                    'location'  =>  $countries->findOneByName($location),
                    'rank'      =>  new Rank($rankPoints)
                );
            }
        }
        return $result;
    }
}
