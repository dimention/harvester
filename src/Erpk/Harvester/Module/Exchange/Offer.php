<?php
namespace Erpk\Harvester\Module\Exchange;

class Offer
{
    public $id;
    public $amount;
    public $rate;
    public $sellerId;
    public $sellerName;
    
    public function toArray()
    {
        return [
            'id'       =>  $this->id,
            'amount'   =>  $this->amount,
            'rate'     =>  $this->rate,
            'seller'   =>  [
                'id'     =>  $this->sellerId,
                'name'   =>  $this->sellerName
            ]
        ];
    }
}

