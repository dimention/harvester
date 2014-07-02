<?php
namespace Erpk\Harvester\Module\Country;

use Erpk\Harvester\Module\Module;
use Erpk\Harvester\Exception\ScrapeException;
use Erpk\Harvester\Exception\NotFoundException;
use Erpk\Harvester\Client\Selector;
use Erpk\Common\Entity;
use Erpk\Harvester\Client\Selector\Filter;

class CountryModule extends Module
{
    protected function get(Entity\Country $country, $type)
    {
        $name = $country->getEncodedName();
        $request = $this->getClient()->get('country/'.$type.'/'.$name);
        $request->getParams()->set('cookies.disable', true);
        $response = $request->send();
        if ($response->getStatusCode() == 301) {
            throw new ScrapeException;
        }
        
        $html = $response->getBody(true);
        return $html;
    }
    
    public function getSociety(Entity\Country $country)
    {
        $html = $this->get($country, 'society');
        $result = $country->toArray();
        $hxs = Selector\XPath::loadHTML($html);
        
        $table = $hxs->select('//table[@class="citizens largepadded"]/tr[position()>1]');
        foreach ($table as $tr) {
            $key = $tr->select('td[2]/span')->extract();
            $key = strtr(strtolower($key), ' ', '_');
            if ($key == 'citizenship_requests') {
                continue;
            }
            $value = $tr->select('td[3]/span')->extract();
            $result[$key] = (int)str_replace(',', '', $value);
        }

        if (preg_match('#Regions \(([0-9]+)\)#', $html, $regions)) {
            $result['region_count'] = (int)$regions[1];
        }
        
        $regions = $this->getEntityManager()->getRepository('Erpk\Common\Entity\Region');
        $result['regions'] = array();
        $table = $hxs->select('//table[@class="regions"]/tr[position()>1]');
        if ($table->hasResults()) {
            foreach ($table as $tr) {
                $region = $regions->findOneByName(trim($tr->select('td[1]//a[1]')->extract()));
                if (!$region) {
                    throw new ScrapeException;
                }
                $result['regions'][] = $region;
            }
        }
        
        return $result;
    }
    
    public function getEconomy(Entity\Country $country)
    {
        $html = $this->get($country, 'economy');
        $result = $country->toArray();
        
        $hxs = Selector\XPath::loadHTML($html);
        $economy = $hxs->select('//div[@id="economy"]');
        
        
        /* RESOURCES */
        $resources = $economy->select('//table[@class="resource_list"]/tr');
        $regions = array();
        if ($resources->hasResults()) {
            foreach ($resources as $tr) {
                $resource = $tr->select('td[1]/span')->extract();
                $r = $tr->select('td[2]/a');
                if ($r->hasResults()) {
                    foreach ($r as $region) {
                        $regions[$region->extract()] = $resource;
                    }
                }
            }
        }
        
        $u = array_count_values($regions);
        foreach ($u as $k => $raw) {
            if ($raw>=1) {
                $u[$k] = 1;
            } else {
                $u[$k] = 0;
            }
        }
        
        /* TREASURY */
        $treasury = $economy->select('//div[@class="accountdisplay largepadded"]');
        $result['treasury'] = array(
            'gold'=>(float)(
                Filter::parseInt($treasury->select('span[@class="special"][1]')->extract()).
                $treasury->select('sup[1]')->extract()
            ),
            'cc'=>(float)(
                Filter::parseInt($treasury->select('span[@class="special"][2]')->extract()).
                $treasury->select('sup[2]')->extract()
            )
        );
        
        
        /* BONUSES */
        $result['bonuses'] = array_fill_keys(array('food', 'frm', 'weapons', 'wrm'), 0);
        foreach (array('Grain', 'Fish', 'Cattle', 'Deer', 'Fruits') as $raw) {
            if (!isset($u[$raw])) {
                $u[$raw] = 0;
            } else {
                $u[$raw] = $u[$raw]*0.2;
            }
            $result['bonuses']['frm']+=$u[$raw];
            $result['bonuses']['food']+=$u[$raw];
        }
        foreach (array('Iron', 'Saltpeter', 'Rubber', 'Aluminum', 'Oil') as $raw) {
            if (!isset($u[$raw])) {
                $u[$raw] = 0;
            } else {
                $u[$raw] = $u[$raw]*0.2;
            }
            $result['bonuses']['wrm']+=$u[$raw];
            $result['bonuses']['weapons']+=$u[$raw];
        }
        
        /* TAXES */
        $industries = $this->getEntityManager()->getRepository('Erpk\Common\Entity\Industry');
        $taxes = $economy->select('h2[text()="Taxes" and @class="section"]/following-sibling::div[1]/table/tr');
        foreach ($taxes as $k => $tr) {
            if ($tr->select('th')->hasResults()) {
                continue;
            }
            $i = $tr->select('td[position()>=2 and position()<=5]/span');
            if (count($i)!=4) {
                throw new ScrapeException;
            }
            $vat = (float)rtrim($i->item(3)->extract(), '%')/100;
            $industry = $industries->findOneByName($i->item(0)->extract())->getCode();
            $result['taxes'][$industry] = array(
                'income' => (float)rtrim($i->item(1)->extract(), '%')/100,
                'import' => (float)rtrim($i->item(2)->extract(), '%')/100,
                'vat'    => empty($vat) ? null : $vat,
            );
        }
        
        /* SALARY */
        $salary = $economy->select('h2[text()="Salary" and @class="section"]/following-sibling::div[1]/table/tr');
        foreach ($salary as $k => $tr) {
            if ($tr->select('th')->hasResults()) {
                continue;
            }
            $i = $tr->select('td[position()>=1 and position()<=2]/span');
            if (count($i)!=2) {
                throw new ScrapeException;
            }
            $type = $i->item(0)->extract();
            $result['salary'][$type] = (float)$i->item(1)->extract();
        }
        
        /* EMBARGOES */
        $countries = $this->getEntityManager()->getRepository('Erpk\Common\Entity\Country');
        $result['embargoes'] = array();
        $embargoes = $economy->select(
            'h2[text()="Trade embargoes" and @class="section"]'.
            '/following-sibling::div[1]/table/tr[position()>1]'
        );
        if ($embargoes->hasResults()) {
            foreach ($embargoes as $tr) {
                if ($tr->select('td[1]/@colspan')->hasResults()) {
                    break;
                }
                $result['embargoes'][] = array(
                    'country' => $countries->findOneByName($tr->select('td[1]/span/a/@title')->extract()),
                    'expires' => str_replace('Expires in ', '', trim($tr->select('td[2]')->extract()))
                );
            }
        }
        return $result;
    }

    public function getOnlineCitizens(Entity\Country $country, $page = 1)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->get(
            'main/online-users/'.$country->getEncodedName().'/all/'.$page
        );
        $response = $request->send();
        $html = $response->getBody(true);
        $hxs = Selector\XPath::loadHTML($html);

        $result = array();
        $citizens = $hxs->select('//div[@class="citizen"]');
        if ($citizens->hasResults()) {
            foreach ($citizens as $citizen) {
                $url = $citizen->select('div[@class="nameholder"]/a[1]/@href')->extract();
                $result[] = array(
                    'id'   => (int)substr($url, strrpos($url, '/')+1),
                    'name' => trim($citizen->select('div[@class="nameholder"]/a[1]')->extract()),
                    'avatar' => $citizen->select('div[@class="avatarholder"]/a[1]/img[1]/@src')->extract()
                );
            }
            
        }
        return $result;
    }
}
