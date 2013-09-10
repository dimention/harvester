<?php
namespace Erpk\Harvester\Module\Exchange;

use Countable;
use IteratorAggregate;
use ArrayIterator;

class OfferCollection implements Countable, IteratorAggregate
{
    protected $paginator;
    protected $goldAmount;
    protected $currencyAmount;
    protected $data = array();

    public function count()
    {
        return count($this->data);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function setOffers($offers)
    {
        $this->data = $offers;
    }

    public function setPaginator($paginator)
    {
        $this->paginator = $paginator;
    }

    public function getPaginator()
    {
        return $this->paginator;
    }

    public function setGoldAmount($n)
    {
        $this->goldAmount = $n;
    }

    public function getGoldAmount()
    {
        return $this->goldAmount;
    }

    public function setCurrencyAmount($n)
    {
        $this->currencyAmount = $n;
    }

    public function getCurrencyAmount()
    {
        return $this->currencyAmount;
    }
}
