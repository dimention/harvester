<?php
namespace Erpk\Harvester\Module\Exchange;

use Erpk\Harvester\Module\Module;
use Erpk\Harvester\Exception\ScrapeException;
use Erpk\Harvester\Exception\InvalidArgumentException;
use Erpk\Harvester\Client\Selector;
use Erpk\Harvester\Filter;
use GuzzleHttp\Event\CompleteEvent;

class ExchangeModule extends Module
{
    const CURRENCY = 0;
    const GOLD = 1;
    
    public function scan($mode, $page = 1)
    {
        if ($mode === self::CURRENCY) {
            $currencyId = 1;
        } else if ($mode === self::GOLD) {
            $currencyId = 62;
        } else {
            throw new InvalidArgumentException('Invalid currency');
        }

        $page = Filter::page($page);
        $this->getClient()->checkLogin();
        $options = [
            'query' => [
                '_token'         => $this->getSession()->getToken(),
                'currencyId'     => $currencyId,
                'page'           => $page-1,
                'personalOffers' => 0,
            ]
        ];
        
        $response = $this->getClient()->post('en/economy/exchange/retrieve/', $options);
        return $this->parseOffers($response->json());
    }

    public function scanAll($mode, $parallel = 1)
    {
        if ($mode === self::CURRENCY) {
            $currencyId = 1;
        } else if ($mode === self::GOLD) {
            $currencyId = 62;
        } else {
            throw new InvalidArgumentException('Invalid currency');
        }

        $first = $this->scan($mode, 1);
        $last = $first->getPaginator()->getLastPage();

        $query = [
                '_token'         => $this->getSession()->getToken(),
                'currencyId'     => $currencyId,
                'personalOffers' => 0,
        ];

        $requests = [];
        $results  = [];
        for($i=2; $i<=$last; $i++){
            $q = $query;
            $q['page'] = $i - 1;
            $options = ['query' => $q];
            $requests[] = $this->getClient()->createRequest('POST', 'en/economy/exchange/retrieve/', $options);
        }

        $parse = function (CompleteEvent $event) use (&$results) {
            $response = $event->getResponse();
            $offers   = ExchangeModule::parseOffers($response->json());
            $page = $offers->getPaginator()->getCurrentPage();
            foreach($offers as $offer){
                $offer->page = $page;
                $results[] = $offer;
            }
        };

        $this->getClient()->sendAll($requests, [
            'complete' => $parse,
            'parallel' => $parallel
        ]);

        return $results;
    }
    
    
    public static function parseOffers($data)
    {
        $hxs = Selector\XPath::loadHTML($data['buy_mode']);
        $paginator = new Selector\Paginator($hxs);

        $result = new OfferCollection;
        $result->setPaginator($paginator);
        $result->setGoldAmount((float)$data['gold']['value']);
        $result->setCurrencyAmount((float)$data['ecash']['value']);
        
        $rows = $hxs->select('//*[@class="exchange_offers"]/tr');
        if ($rows->hasResults()) {
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
        $options = [
            'query' => [
                'offerId' => $id,
                'amount'  => $amount,
                '_token'  => $this->getSession()->getToken(),
                'page'    => 0
            ],
            'headers' => [
                'Referer' => $this->getClient()->getBaseUrl().'en/economy/exchange-market/'
            ]
        ];

        $response = $this->getClient()->post('economy/exchange/purchase/', $options);
        return json_decode($response->getBody(true), true);
    }
}
