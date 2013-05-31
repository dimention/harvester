<?php
namespace Erpk\Harvester\Module\Market;

class Offer
{
    public $id;
    public $amount;
    public $price;
    public $sellerId;
    public $sellerName;
    public $country;
    public $industry;
    public $quality;
    
    public function toArray()
    {
        return array(
            'id'       =>  $this->id,
            'amount'   =>  $this->amount,
            'price'    =>  $this->price,
            'seller'   =>  array(
                'id'     =>  $this->sellerId,
                'name'   =>  $this->sellerName
            )
        );
    }
}
