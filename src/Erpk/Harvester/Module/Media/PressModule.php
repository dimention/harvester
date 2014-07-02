<?php
namespace Erpk\Harvester\Module\Media;

use Erpk\Harvester\Exception;
use Erpk\Harvester\Module\Module;
use Erpk\Harvester\Client\Selector;

class PressModule extends Module
{
    const CATEGORY_FIRST_STEPS = 1;
    const CATEGORY_BATTLE_ORDERS = 2;
    const CATEGORY_WARFARE_ANALYSIS = 3;
    const CATEGORY_POLITICAL_DEBATES_AND_ANALYSIS = 4;
    const CATEGORY_FINANCIAL_BUSINESS = 5;
    const CATEGORY_SOCIAL_INTERACTIONS_AND_ENTERTAINMENT = 6;
    
    public function publishArticle($articleName, $articleBody, $articleCategory)
    {
        $this->getClient()->checkLogin();
        $options = [
            'query'   => [
                'article_name'     => $articleName,
                'article_body'     => $articleBody,
                'article_category' => $articleCategory,
                '_token'           => $this->getSession()->getToken()
            ],
            'headers' => [
                'Referer' => $this->getClient()->getBaseUrl() . '/en/write-article'
            ]
        ];

        $response = $this->getClient()->post('en/write-article', $options);
        $status = $response->getStatusCode();
        if ($status >= 300 && $status < 400) {
            return Article::createFromUrl(
                $response->getHeader('Location', true)
            );
        } else {
            throw new Exception\ScrapeException;
        }
    }
    
    public function editArticle(Article $article, $articleName, $articleBody, $articleCategory)
    {
        $this->getClient()->checkLogin();

        $options = [
            'query'   => [
                'commit'           => 'Edit',
                'article_name'     => $articleName,
                'article_body'     => $articleBody,
                'article_category' => $articleCategory,
                '_token'           => $this->getSession()->getToken()
            ],
            'headers' => [
                'Referer' => $this->getClient()->getBaseUrl() . '/en/edit-article/' . $article->getId()
            ]
        ];

        $response = $this->getClient()->post('en/edit-article/' . $article->getId(), $options);
        return $response->getBody(true);
    }

    public function deleteArticle(Article $article)
    {
        $this->getClient()->checkLogin();
        $this->getClient()->get('en/delete-article/'.$article->getId().'/1');
    }

    public function getNewspaper($id)
    {
        $id = Filter::id($id);
        $this->getClient()->checkLogin();

        $response = $this->getClient()->get('en/newspaper/'.$id);

        $status = $response->getStatusCode();
        if ($status >= 300 && $status < 400) {
            $location = 'http://www.erepublik.com'. $response->getHeader('Location', true);
            if (strpos($location, 'http://www.erepublik.com/en/newspaper/') !== false) {
                $response = $this->getClient()->get($location);
            } else {
                throw new Exception\NotFoundException('Newspaper does not exist.');
            }
        } else {
            throw new Exception\ScrapeException;
        }
       
        $xs = Selector\XPath::loadHTML($response->getBody(true));
        $result = [];

        $info      = $xs->select('//div[@class="newspaper_head"]');
        $avatar    = $info->select('//img[@class="avatar"]/@src')->extract();
        $url       = $info->select('div[@class="info"]/ul[1]/li[1]/a[1]/@href')->extract();
        $meta      = $xs->select('/*/head/meta[@name="description"]/@content')->extract();
        $meta1     = strpos($meta,'has ');
        $meta2     = strpos($meta,' articles');
        /**
         * BASIC DATA
         */
        $result['director']['name'] = $info->select('//li/a/@title')->extract();
        $result['director']['id']   = (int)substr($url, strrpos($url, '/')+1);
        $result['name']             = $info->select('//h1/a/@title')->extract();
        $result['avatar']           = str_replace('55x55','100x100',$avatar);
        $result['country']          = $info->select('div[1]/a[1]/img[2]/@title')->extract();
        $result['subscribers']      = (int)$info->select('div[@class="actions"]')->extract();
        $result['articles']         = (int)substr($meta,($meta1+3),($meta2 - $meta1 -3));
        if($result['avatar'] == '/images/default_avatars/Newspapers/default_100x100.gif'){
            $result['avatar'] = NULL;
        }
        return $result;
    }
}
