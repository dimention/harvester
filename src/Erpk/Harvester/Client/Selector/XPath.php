<?php
namespace Erpk\Harvester\Client\Selector;

use Erpk\Harvester\Exception\ScrapeException;
use DOMXPath;
use DOMDocument;

class XPath
{
    protected $node;
    protected $xpath;
    
    public static function loadHTML($html)
    {
        $doc = @DOMDocument::loadHTML($html);
        if ($doc instanceof DOMDocument) {
            $xpath = new DOMXPath($doc);
        } else {
            throw new ScrapeException;
        }
        return new XPath($doc, $xpath);
    }
    
    public function __construct($node, DOMXPath $xpath)
    {
        $this->node = $node;
        $this->xpath = $xpath;
    }

    public function select($search)
    {
        return new XPathList(
            $search,
            $this->xpath,
            $this->xpath->query($search, $this->node)
        );
    }
    
    public function extract()
    {
        return $this->node->nodeValue;
    }
    
    public function __toString()
    {
        return $this->extract();
    }
}
