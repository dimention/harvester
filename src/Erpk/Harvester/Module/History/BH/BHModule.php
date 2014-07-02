<?php
namespace Erpk\Harvester\Module\History\BH;

use Erpk\Harvester\Module\Module;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Event\CompleteEvent;
use Erpk\Harvester\Exception\ScrapeException;
use Erpk\Harvester\Client\Selector;
use Erpk\Common\EntityManager;
use Erpk\Harvester\Filter;

/**
 * Class OrganizationModule
 * @package Erpk\Harvester\Module\Organization
 */
class BHModule extends Module
{

    /**
     * Gets useful information about an organization
     * @param integer $id Organization ID
     * @return array Array of organization's attributes
     * @throws Exception\OrganizationNotFoundException
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getProfile($id)
    {
        $id = Filter::id($id);

        $this->getClient()->checkLogin();

        $response = $this->getClient()->get('en/economy/citizen-accounts/'.$id);
        $result = self::parseProfile($response->getBody(true));
        $result['id'] = $id;
        return $result;

    }

    public function getBHs($ids, $parallel=1)
    {
        $requests = [];
        $results = [];
        foreach ($ids as $id){
            $requests[] = $this->getClient()->createRequest('GET', 'medals/citizen/'.$id);
        }

        $parse = function (CompleteEvent $event) use (&$results) {
            $id = Selector\Filter::extractOrganizationIdFromUrl($event->getRequest()->getUrl());

            $response = $event->getResponse();
            if ($response->getStatusCode() == 301){
                $result['exists'] = false;
            }
            else{
                $result = BHModule::parsePage($response->getBody(true));
                $result['exists'] = true;
            };
            $result['id'] = $id;
            $results[] = $result;
        };

        $this->getClient()->sendAll($requests, [
            'complete' => $parse,
            'parallel' => $parallel
        ]);

        return $results;
    }


    /**
     * Parses organization's profile HTML page and returns useful information
     * @param string $html HTML content of organization's profile page
     * @return array Array of organization's attributes
     * @throws \Erpk\Harvester\Exception\ScrapeException
     */
    public static function parsePage($html)
    {
        $mtime = microtime(true);
        $em = EntityManager::getInstance();
        $countries = $em->getRepository('Erpk\Common\Entity\Country');
        $regions = $em->getRepository('Erpk\Common\Entity\Region');
        
        $xs = Selector\XPath::loadHTML($html);
        $result = [];
        
        $content  = $xs->select('//div[@id="container"][1]/div[@id="content"][1]/div[@class="medals_list"][1]');

        $pages = $content->select('//div[@class="pages_box"][1]/a[last()]/@href')->extract();
        $pages = Selector\RegEx::find($pages, '/\/(\d+)$/i')->group(0);

        $list = $content->select('div[@class="medal_box"]');
        foreach($list as $item){
            $forCountry = $item->select('div/div[1]/div/a[1]/img/@title')->extract();

        }

//==================================================================================================
        $state    = $content->select('//div[@class="citizen_state"][1]');
        $holder = $content->select('//div[@class="citizen_content up"][1]/table[@class="holder racc"][1]');
        
        $result['name'] = $content->select('//img[@class="citizen_avatar"]/@alt')->extract();
        
        $avatar = $content->select('//img[@class="citizen_avatar"][1]/@style')->extract();
        $avatar = Selector\RegEx::find($avatar, '/background-image\: url\(([^)]+)\);/i');
        $result['avatar'] = Selector\Filter::normalizeAvatar($avatar->group(0));
        
        $result['online'] = $content->select('//span[@class="citizen_presence"][1]/img[1]/@alt')->extract() == 'online';

        $ban = $state->select(
            'div/span/img[contains(@src, "perm_banned")]/../..'
        );
        if ($ban->hasResults()) {
            $result['ban'] = [
                'type' => trim($ban->select('span')->extract()),
                'reason' => $ban->select('@title')->extract()
            ];
        } else {
            $result['ban'] = null;
        }
        
        $info = $sidebar->select('div[1]');
        $result['location'] = [
            'country' => $countries->findOneByName($info->select('a[1]/@title')->extract()),
            'region'  => $regions->findOneByName($info->select('a[2]/@title')->extract()),
        ];
        $country = $holder->select('tr[3]/td[1]/img[@class="icon"]/@src')->extract();
        $country = Selector\RegEx::find($country, '/([A-Za-z\-]+)\.png/i');
        $country = str_replace('-', ' ', $country->group(0));
        $country = str_replace('Taiwan', '(Taiwan)', $country);
        $country = str_replace('FYROM', '(FYROM)', $country);

        $result['country'] = $countries->findOneByName($country);

        $result['gold'] = (float) trim($holder->select('tr[2]/td[4]')->extract());
        $result['currency'] = (float) trim($holder->select('tr[3]/td[4]')->extract());
        $mtime = microtime(true)-$mtime;
        $result['parse time'] = round($mtime,3)." s";
        return $result;
    }
}
