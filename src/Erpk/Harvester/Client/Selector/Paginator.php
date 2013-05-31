<?php
namespace Erpk\Harvester\Client\Selector;

class Paginator
{
    protected $currentPage;
    protected $lastPage;
    
    protected function extractPage($str)
    {
        return (int)strtr($str->extract(), array('page_'=>''));
    }
    
    public function __construct(XPath $hxs)
    {
        $pager = $hxs->select('//ul[@class="pager"][1]');
        
        $last = $pager->select('//a[@class="last"][1]/@rel');
        $current = $pager->select('//a[@class="on"][1]/@rel');
        $lastSelectable = $pager->select('//li/a[position()=last()][1]');
        
        $this->currentPage = $current->hasResults() ? $this->extractPage($current) : null;
        $this->lastPage = $this->extractPage($last->hasResults() ? $last : $lastSelectable);
    }
    
    public function getFirstPage()
    {
        return $this->firstPage;
    }
    
    public function getCurrentPage()
    {
        return $this->currentPage;
    }
    
    public function getLastPage()
    {
        return $this->lastPage;
    }
    
    public function toArray()
    {
        return array(
            'current' => $this->currentPage,
            'last'    => $this->lastPage
        );
    }
    
    public function isOutOfRange($page)
    {
        return $page > $this->lastPage;
    }
}
