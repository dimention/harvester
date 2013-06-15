<?php
namespace Erpk\Harvester\Module\Exchange;

use Erpk\Harvester\Module\Module;
use Erpk\Harvester\Exception\ScrapeException;
use Erpk\Harvester\Exception\InvalidArgumentException;
use Erpk\Harvester\Client\Selector;
use Erpk\Harvester\Filter;

class ExchangeModule extends Module
{
    const CURRENCY = 0;
    const GOLD = 1;
    
    public function scan($mode, $page = 1)
    {
        if ($mode !== self::CURRENCY && $mode !== self::GOLD) {
            throw new InvalidArgumentException('Invalid currency');
        }
        $page = Filter::page($page);
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('economy/exchange/retrieve/');
        $request->addPostFields(
            array(
                '_token'         => $this->getSession()->getToken(),
                'currencyId'     => $mode ? 62 : 1,
                'page'           => $page-1,
                'personalOffers' => 0,
            )
        );
        
        $response = $request->send();
        $result = json_decode($response->getBody(true), true);
        return $this->parseOffers($result['buy_mode'], $page);
    }
    
    
    public static function parseOffers($html, $requestedPage)
    {
        $result = array();
        if (strpos($html, 'No offers to display') !== false) {
            return $result;
        }
        
        $hxs = Selector\XPath::loadHTML($html);
        $paginator = new Selector\Paginator($hxs);
        if ($paginator->isOutOfRange($requestedPage)) {
            return array();
        }
        
        $rows = $hxs->select('//*[@class="exchange_offers"]/tr');
        
        foreach ($rows as $row) {
            $url = $row->select('td[1]/a/@href')->extract();
            $offer = new Offer;
            $offer->id = (int)substr($row->select('td[3]/strong[2]/@id')->extract(), 14);
            $offer->amount = (float)str_replace(',', '', $row->select('td[2]/strong/span')->extract());
            $offer->rate = (float)$row->select('td[3]/strong[2]/span')->extract();
            $offer->sellerId = (int)substr($url, strripos($url, '/') + 1);
            $offer->sellerName = (string)$row->select('td[1]/a/@title')->extract();
            $result[] = $offer;
        }
        return $result;
    }
    
    public function buy($id, $amount)
    {
        $id = Filter::id($id);
        $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);
        if (!$amount) {
            throw new InvalidArgumentException('Specified amount isn\'t a valid float.');
        }
        
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('economy/exchange/purchase/');
        $request->addPostFields(
            array(
                'offerId' => $id,
                'amount'  => $amount,
                '_token'  => $this->getSession()->getToken(),
                'page'    => 0
            )
        );
        $request->setHeader('Referer', $this->getClient()->getBaseUrl().'/economy/exchange-market/');
        $response = $request->send();
        return json_decode($response->getBody(true), true);
    }
}
