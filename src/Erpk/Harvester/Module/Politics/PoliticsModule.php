<?php
namespace Erpk\Harvester\Module\Politics;

use Erpk\Harvester\Module\Module;
use Erpk\Harvester\Exception\ScrapeException;
use Erpk\Harvester\Exception\NotFoundException;
use Erpk\Harvester\Client\Selector;
use Erpk\Harvester\Filter;

class PoliticsModule extends Module
{
    public function getParty($id)
    {
        $id = Filter::id($id);
        $this->getClient()->checkLogin();
        
        $response = $this->getClient()->get('party/'.$id)->send();
        
        if ($response->isRedirect()) {
            $location = $response->getLocation();
            if (strpos($location, 'http://www.erepublik.com/en/party/') !== false) {
                $response = $this->getClient()->get($location)->send();
            } else {
                throw new NotFoundException('Party does not exist.');
            }
        } else {
            throw new ScrapeException;
        }
        
        $hxs = Selector\XPath::loadHTML($response->getBody(true));
        
        $result = array('id' => $id);
        $profileholder = $hxs->select('//div[@id="profileholder"][1]');
        $url = $profileholder->select('a[2]/@href')->extract();
        $about = $hxs->select('//div[@class="about_message party_section"][1]/p[1]');
        $info = $hxs->select('//div[@class="infoholder"][1]');
        $congress = $hxs->select('//a[@name="congress"][1]/..');
        $countries = $this->getEntityManager()->getRepository('Erpk\Common\Entity\Country');
        
        $result['name']         = $profileholder->select('h1[1]')->extract();
        $result['about']        = $about->hasResults() ? $about->extract() : null;
        $result['members']      = (int)$info->select('p[1]/span[2]')->extract();
        $result['orientation']  = $info->select('p[2]/span[2]')->extract();
        $result['country']      = $countries->findOneByCode($profileholder->select('a[3]/img/@alt')->extract());
        
        if (!$result['country']) {
            throw new ScrapeException;
        }
        
        $result['president'] = array(
            'id'        =>  (int)substr($url, 1+strrpos($url, '/')),
            'name'      =>  $profileholder->select('a[2]')->extract()
        );
        
        $result['congress']  = array(
            'members'   =>  (int)trim($congress->select('div[1]/div[1]/div[1]/p[1]/span[1]')->extract()),
            'share'     =>  ((float)rtrim(trim($congress->select('div[1]/div[1]/div[1]/p[1]/span[1]')->extract()), '%'))/100
        );
        
        return $result;
    }
}
