<?php
namespace Erpk\Harvester\Module\Management;

use Erpk\Harvester\Exception\ScrapeException;
use Erpk\Harvester\Exception\NotFoundException;
use Erpk\Harvester\Client\Selector;
use Erpk\Harvester\Filter;
use Erpk\Harvester\Module\Module;

class ManagementModule extends Module
{
    public function eat()
    {
        $this->getClient()->checkLogin();
        
        $request = $this->getClient()->get('main/eat');
        $request->getHeaders()
            ->set('Referer', $this->getClient()->getBaseUrl())
            ->set('X-Requested-With', 'XMLHttpRequest');
        $query = $request->getQuery();
        $query
            ->set('format', 'json')
            ->set('_token', $this->getSession()->getToken())
            ->set('_', time());
            
        $response = $request->send()->json();
        return $response;
    }
    
    public function getInventory()
    {
        $this->getClient()->checkLogin();
        
        $request = $this->getClient()->get('economy/inventory');
        $request->getHeaders()->set('Referer', 'http://www.erepublik.com/en/economy/myCompanies');
        
        $response = $request->send();
        $hxs = Selector\XPath::loadHTML($response->getBody(true));
        
        $result = array();
        
        $parseItem = function ($item) use (&$result) {
            $ex = explode('_', str_replace('stock_', '', $item->select('strong/@id')->extract()));
            $result['items'][(int)$ex[0]][(int)$ex[1]] = (int)strtr($item->select('strong')->extract(), array(','=>''));
        };
        
        $items = $hxs->select('//*[@class="item_mask"][1]/ul[1]/li');
        foreach ($items as $item) {
            $parseItem($item);
        }
        
        $items = $hxs->select('//*[@class="item_mask"][2]/ul[1]/li');
        foreach ($items as $item) {
            $parseItem($item);
        }
        
        $storage = trim($hxs->select('//*[@class="area storage"][1]/h4[1]/strong[1]')->extract());
        $storage = strtr(
            $storage,
            array(
                ',' => '',
                ')' => '',
                '(' => ''
            )
        );
        $storage = explode('/', $storage);
        
        $result['storage'] = array(
            'current' => (int)$storage[0],
            'maximum' => (int)$storage[1]
        );
        
        return $result;
    }
    
    public function getCompanies()
    {
        $this->getClient()->checkLogin();
        
        $request = $this->getClient()->get('economy/myCompanies');
        $response = $request->send();
        $html = $response->getBody(true);
        preg_match('#var companies\s+=\s+(.+);#', $html, $matches);
        $result = json_decode($matches[1], true);
        return $result;
    }
    
    public function getTrainingGrounds()
    {
        $this->getClient()->checkLogin();
        
        $request = $this->getClient()->get('economy/training-grounds');
        $response = $request->send();
        $html = $response->getBody(true);
        preg_match('#var grounds\s+=\s+(.+);#', $html, $matches);
        $result = json_decode($matches[1], true);
        return $result;
    }
    
    public function getAccounts()
    {
        $this->getClient()->checkLogin();
        
        $request = $this->getClient()->get('economy/exchange-market/');
        $response = $request->send();
        $hxs = Selector\XPath::loadHTML($response->getBody(true));
        
        return array(
            'cc'   => (float)$hxs->select('//input[@id="eCash"][1]/@value')->extract(),
            'gold' => (float)$hxs->select('//input[@id="golden"][1]/@value')->extract(),
        );
    }

    public function train($q1 = true, $q2 = false, $q3 = false, $q4 = false)
    {
        $this->getClient()->checkLogin();
        $grounds = $this->getTrainingGrounds();

        $toTrain = array();
        for ($i = 0; $i <= 3; $i++) {
            if (${'q'.($i+1)} === true && $grounds[$i]['trained'] === false) {
                $toTrain[] = array(
                    'id' => (int)$grounds[$i]['id'],
                    'train' => 1
                );
            }
        }

        $request = $this->getClient()->post('economy/train');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl().'/economy/training-grounds');
        $request->addPostFields(
            array(
                '_token'  => $this->getSession()->getToken(),
                'grounds' => $toTrain
            )
        );

        $response = $request->send()->json();
        return $response;
    }

    public function workAsEmployee()
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('economy/work');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl().'/economy/myCompanies');
        $request->addPostFields(
            array(
                '_token'      => $this->getSession()->getToken(),
                'action_type' => 'work'
            )
        );

        $response = $request->send()->json();
        return $response;
    }
    
    public function getDailyTasksReward()
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->get('main/daily-tasks-reward');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl());

        $response = $request->send()->json();
        return $response;
    }
}
