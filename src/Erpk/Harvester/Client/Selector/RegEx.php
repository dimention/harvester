<?php
namespace Erpk\Harvester\Client\Selector;

use Erpk\Harvester\Exception\ScrapeException;

class RegEx
{
    public static function find($subject, $pattern)
    {
        preg_match($pattern, $subject, $groups);
        return new Match($groups);
    }
}
