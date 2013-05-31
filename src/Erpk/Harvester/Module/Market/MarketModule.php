<?php
namespace Erpk\Harvester\Module\Market;

use Erpk\Common\Entity;
use Erpk\Harvester\Filter;
use Erpk\Harvester\Exception\ScrapeException;
use Erpk\Harvester\Exception\InvalidArgumentException;
use Erpk\Harvester\Exception\RuntimeException;
use Erpk\Harvester\Client\Selector;
use Erpk\Harvester\Module\Module;

class MarketModule extends Module
{
    public function scan(Entity\Country $country, Entity\Industry $industry, $quality, $page = 1)
    {
        $page = Filter::page($page);
        $quality = Filter::positiveInteger($quality);
        
        $code = $industry->getCode();
        switch($code) {
            case 'food':
            case 'weapons':
                if ($quality < 1 || $quality > 7) {
                    throw new InvalidArgumentException('Quality for food and weapons should be between 1 and 7.');
                }
                break;
            case 'wrm':
            case 'frm':
                if ($quality != 1) {
                    throw new InvalidArgumentException('Quality for raw material must be 1.');
                }
                break;
            case 'defense':
            case 'ticket':
            case 'hospital':
                if ($quality < 1 || $quality > 5) {
                    throw new InvalidArgumentException('Quality for that industry should be between 1 and 5.');
                }
                break;
        }
        
        $this->getClient()->checkLogin();
        $request = $this->getClient()->get(
            'economy/market/'.$country->getId().'/'.
            $industry->getId().'/'.$quality.'/citizen/0/price_asc/'.$page
        );
        $response = $request->send();
        
        $offers = array();
        
        $this->parseOffers(
            function ($offer) use (&$offers, $country, $industry, $quality) {
                $offer->country  = $country;
                $offer->industry = $industry;
                $offer->quality  = $quality;
                $offers[] = $offer;
            },
            $response->getBody(true),
            $page
        );
        
        return $offers;
    }
    
    protected function parseOffers($callback, $html, $page)
    {
        if (stripos($html, 'There are no market offers matching your search.') !== false) {
            return array();
        }
        
        $hxs = Selector\XPath::loadHTML($html);
        $paginator = new Selector\Paginator($hxs);
        if ($paginator->isOutOfRange($page)) {
            return array();
        }
        
        $rows = $hxs->select('//*[@class="price_sorted"]/tr');
        foreach ($rows as $row) {
            $id         = $row->select('td/@id')->extract();
            $id         = substr($id, strripos($id, '_') + 1);
            $sellerUrl  = $row->select('td[@class="m_provider"][1]/a/@href')->extract();
            $price      = (float)$row->select('td[@class="m_price stprice"][1]/strong')->extract()+
                          (float)substr($row->select('td[@class="m_price stprice"][1]/sup')->extract(), 1)/100;
            
            $offer = new Offer;
            $offer->id = (int)$id;
            $offer->amount = (int)trim(str_replace(',', '', $row->select('td[@class="m_stock"][1]')->extract()));
            $offer->price = $price;
            $offer->sellerId = (int)substr($sellerUrl, strripos($sellerUrl, '/')+1);
            $offer->sellerName = trim($row->select('td[@class="m_provider"][1]/a')->extract());
            
            $callback($offer);
        }
    }
    
    public function buy(Offer $offer, $amount)
    {
        $amount = Filter::positiveInteger($amount);
        if ($amount >= 10000) {
            $amount = 9999;
        }
        
        $this->getClient()->checkLogin();
        
        $request = $this->getClient()->post(
            sprintf(
                'economy/market/%d/%d/%d',
                $offer->country->getId(),
                $offer->industry->getId(),
                $offer->quality
            )
        );
        
        $request->addPostFields(
            array(
                'amount'  => $amount,
                'offerId' => $offer->id,
                '_token'  => $this->getSession()->getToken()
            )
        );
        
        $response = $request->send();
        $hxs = Selector\XPath::loadHTML($response->getBody(true));
        
        $result = $hxs->select('//div[@id="marketplace"]/table');
        if ($result->count() < 2) {
            throw new ScrapeException;
        } else {
            return trim($result->extract());
        }
    }
}
