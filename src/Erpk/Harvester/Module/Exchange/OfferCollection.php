<?php
namespace Erpk\Harvester\Module\Exchange;

use ArrayObject;

class OfferCollection extends ArrayObject
{
    protected $paginator;
    protected $goldAmount;
    protected $currencyAmount;

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
