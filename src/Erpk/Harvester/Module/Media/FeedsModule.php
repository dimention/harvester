<?php
namespace Erpk\Harvester\Module\Media;

use Erpk\Harvester\Exception\ScrapeException;
use Erpk\Harvester\Module\Module;

class FeedsModule extends Module
{
    public function shoutFriend($message)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('main/wall-post/create/');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl());
        $request->addPostFields(
            array(
                'post_message' => $message,
                '_token'  => $this->getSession()->getToken()
            )
        );
        $response = $request->send()->json();
        return $response;
    }
    
    public function shoutParty($message)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('main/party-post/create/');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl());
        $request->addPostFields(
            array(
                'post_message' => $message,
                '_token'  => $this->getSession()->getToken()
            )
        );
        $response = $request->send()->json();
        return $response;
    }
    
    public function shoutMU($message, $groupId, $postAs = 1)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('main/group-wall/create/post');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl());
        $request->addPostFields(
            array(
                'post_message' => $message,
                'groupId' => $groupId,
                'post_as' => $postAs,
                '_token'  => $this->getSession()->getToken()
            )
        );
        $response = $request->send()->json();
        return $response;
    }
    
    public function delShoutFriend($postId)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('main/wall-post/delete/');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl());
        $request->addPostFields(
            array(
                'postId' => $postId,
                '_token'  => $this->getSession()->getToken()
            )
        );
        $response = $request->send()->json();
        return $response;
    }
    
    public function delShoutParty($postId)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('main/party-post/delete/');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl());
        $request->addPostFields(
            array(
                'postId' => $postId,
                '_token'  => $this->getSession()->getToken()
            )
        );
        $response = $request->send()->json();
        return $response;
    }
    
    public function delShoutMU($postId, $groupId)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('main/group-wall/create/post');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl());
        $request->addPostFields(
            array(
                'postId' => $postId,
                'groupId' => $groupId,
                '_token'  => $this->getSession()->getToken()
            )
        );
        $response = $request->send()->json();
        return $response;
    }
    
    public function getFriendFeed($page = 1)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('main/wall-post/older/');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl());
        $request->addPostFields(
            array(
                'page' => $page,
                '_token'  => $this->getSession()->getToken()
            )
        );
        $response = $request->send();
        return $response->getBody(true);
    }
    
    public function getPartyFeed($page = 1)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('main/party-post/older/');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl());
        $request->addPostFields(
            array(
                'page' => $page,
                '_token'  => $this->getSession()->getToken()
            )
        );
        $response = $request->send();
        return $response->getBody(true);
    }
    
    public function getMUFeed($page, $groupId)
    {
        $this->getClient()->checkLogin();
        $request = $this->getClient()->post('main/group-wall/older/retrieve');
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl());
        $request->addPostFields(
            array(
                'groupId' => $groupId,
                'page' => $page,
                'part' => 1,
                '_token'  => $this->getSession()->getToken()
            )
        );
        $response = $request->send();
        return $response->getBody(true);
    }
}
