<?php
namespace Erpk\Harvester\Module\JobMarket;

use Erpk\Harvester\Module\Module;
use Erpk\Harvester\Exception\InvalidArgumentException;
use Erpk\Harvester\Exception\ScrapeException;
use Erpk\Harvester\Client\Selector;
use Erpk\Harvester\Filter;
use Erpk\Common\Entity;

class JobMarketModule extends Module
{
    public function scan(Entity\Country $country, $page = 1)
    {
        $page = Filter::page($page);
        $this->getClient()->checkLogin();
        
        $url      = 'economy/job-market/'.$country->getId().'/'.$page;
        $request  = $this->getClient()->get($url);
        $response = $request->send();
        
        return $this->parseOffers($response->getBody(true), $page);
    }
    
    public static function parseOffers($html, $requestedPage)
    {
        $hxs = Selector\XPath::loadHTML($html);

        $rows = $hxs->select('//*[@class="salary_sorted"]/tr');
        $offers = array();
        if (!$rows->hasResults()) {
            return $offers;
        }
        
        foreach ($rows as $row) {
            
            $url = $row->select('td/a/@href')->extract();
            $offers[] = array(
                'employer' => array(
                    'id' => (int)substr($url, strrpos($url, '/')+1),
                    'name' => $row->select('td/a/@title')->extract()
                ),
                'salary' => (int)$row->select('td[4]/strong')->extract()+
                (float)substr($row->select('td[4]/sup')->extract(), 1)/100
            );
        }
        return $offers;
    }
}
