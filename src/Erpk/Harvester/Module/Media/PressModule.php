<?php
namespace Erpk\Harvester\Module\Media;

use Erpk\Harvester\Exception\ScrapeException;
use Erpk\Harvester\Module\Module;

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
        $request = $this->getClient()->post('write-article');

        $request->getHeaders()
            ->set('Referer', $this->getClient()->getBaseUrl().'/write-article');
        $request->addPostFields(
            array(
                'article_name' => $articleName,
                'article_body' => $articleBody,
                'article_category' => $articleCategory,
                '_token'  => $this->getSession()->getToken()
            )
        );
        $response = $request->send();

        if ($response->isRedirect()) {
            return Article::createFromUrl(
                $response->getLocation()
            );
        } else {
            throw new ScrapeException;
        }
    }
    
    public function editArticle(Article $article, $articleName, $articleBody, $articleCategory)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('edit-article/'.$article->getId());
        $request
            ->getHeaders()
            ->set('Referer', $this->getClient()->getBaseUrl().'/edit-article/'.$article->getId());

        $request->addPostFields(
            array(
                'commit' => 'Edit',
                'article_name' => $articleName,
                'article_body' => $articleBody,
                'article_category' => $articleCategory,
                '_token' => $this->getSession()->getToken()
            )
        );
        $response = $request->send();
        return $response->getBody(true);
    }

    public function deleteArticle(Article $article)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->get('delete-article/'.$article->getId().'/1');
        $request->send();
    }
}
