<?php
namespace Erpk\Harvester\Client\Selector;

use Erpk\Harvester\Exception\InvalidArgumentException;

class Filter
{
    public static function validRequestedPage(XPath $hxs, $requestedPage)
    {
        $pager = $hxs->select('(//ul[@class="pager"]/li/a)[last()]');
        if ($pager->hasResults()) {
            $href = $pager->select('@href')->extract();
            $currentPage = (int)substr($href, strrpos($href, '/')+1);
            if ($requestedPage > $currentPage) {
                return false;
            } else {
                return true;
            }
        } elseif ($requestedPage > 1) {
            return false;
        } else {
            return true;
        }
    }
    
    public static function extractPartyIdFromUrl($url)
    {
        preg_match('@-([0-9]+)$@', $url, $partyId);
        return (int)$partyId[1];
    }
    
    public static function extractCitizenIdFromUrl($url)
    {
        preg_match('@citizen/profile/([0-9]+)$@', $url, $citizenId);
        return (int)$citizenId[1];
    }

    public static function extractOrganizationIdFromUrl($url)
    {
        preg_match('@economy/citizen\-accounts/([0-9]+)$@', $url, $citizenId);
        return (int)$citizenId[1];
    }

    public static function extractCountryFromUrl($url)
    {
        preg_match('@country/economy/([\S]+)$@', $url, $country);
        if ($country[1] == 'Bosnia-Herzegovina') $country = 'Bosnia and Herzegovina';
        else{
            $country = str_replace('-', ' ', $country[1]);
            $country = str_replace('Taiwan', '(Taiwan)', $country);
            $country = str_replace('FYROM', '(FYROM)', $country);
        }
        return $country;
    }
    
    public static function normalizeAvatar($url)
    {
        return preg_replace('#(.+?)([a-z0-9]{32})(.+)#', '\1\2_100x100.jpg', $url);
    }

    public static function parseInt($n)
    {
        return (int)trim(str_replace(',', '', $n));
    }
}
