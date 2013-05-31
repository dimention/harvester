<?php
namespace Erpk\Harvester\Client\Selector;

use Erpk\Harvester\Exception\ScrapeException;

class Match
{
    protected $subject;
    protected $groups = array();
    
    public function __construct($groups)
    {
        $this->subject = $groups[0];
        $this->groups = array_slice($groups, 1);
    }
    
    public function group($n)
    {
        if (isset($this->groups[$n])) {
            return $this->groups[$n];
        } else {
            throw new ScrapeException;
        }
    }
    
    public function isEmpty()
    {
        return empty($this->groups);
    }
}
