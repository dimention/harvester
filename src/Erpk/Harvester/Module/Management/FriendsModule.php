<?php
namespace Erpk\Harvester\Module\Management;

use Erpk\Harvester\Client\Selector;
use Erpk\Harvester\Filter;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Erpk\Harvester\Module\Module;
use Erpk\Common\Entity;

class FriendsModule extends Module
{
    const COUNTRY_CURRENCY = 1;
    const GOLD = 62;

    public function startMessageThread($citizenIds, $subject, $content)
    {
        $this->getClient()->checkLogin();
        if (is_array($citizenIds)) {
            $citizens = implode(',', $citizenIds);
            $citizenId = 0;
        } else {
            $citizens = $citizenIds;
            $citizenId = $citizenIds;
        }

        $request = $this->getClient()->post('main/messages-compose/'.$citizenId);
        $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl().'/main/messages-compose/'.$citizenId);
        $request->addPostFields(
            array(
                    '_token'          => $this->getSession()->getToken(),
                    'citizen_name'    => $citizens,
                    'citizen_subject' => $subject,
                    'citizen_message' => $content
            )
        );

        $response = $request->send();

        return $this->parseMessage($response->getBody(true));
    }

    public function respondMessage($threadId, $messagebody)
    {
        $threadId = Filter::id($threadId);
        $this->getClient()->checkLogin();

        $request = $this->getClient()->post('main/messages-compose/0');
        $request->getHeaders()
        ->set('X-Requested-With', 'XMLHttpRequest')
        ->set('Referer', $this->getClient()->getBaseUrl().'/main/messages-compose/0');
        $request->addPostFields(
                array(
                        '_token'          => $this->getSession()->getToken(),
                        'thread_id'    => $threadId,
                        'citizen_message' => $messagebody
                )
        );

        $response = $request->send();

        return $this->parseMessage($response->getBody(true));
    }

    public function deleteMessage($threadId)
    {
        $citizenId = Filter::id($citizenId);
        $this->getClient()->checkLogin();

        $request = $this->getClient()->post('main/messages-delete');
        $request->getHeaders()
        ->set('X-Requested-With', 'XMLHttpRequest')
        ->set('Referer', $this->getClient()->getBaseUrl().'/main/messages-inbox');
        $request->addPostFields(
                array(
                        '_token'          => $this->getSession()->getToken(),
                        'delete_message[]'    => $threadId,
                )
        );

        $response = $request->send();

        return $response->getBody(true);
    }

    protected function retrieveMessageHtml($threadId)
    {
        $threadId = Filter::id($threadId);
        $this->getClient()->checkLogin();

        $request = $this->getClient()->get('main/messages-read/'.$threadId);
        $request->getHeaders()
        ->set('X-Requested-With', 'XMLHttpRequest')
        ->set('Referer', $this->getClient()->getBaseUrl().'/main/messages-inbox');

        $response = $request->send();

        return $response->getBody(true);
    }

    private function extractCitizenId(&$profileAddress)
    {
        $profileAddress = substr($profileAddress, strpos($profileAddress, '/profile/') + 10);

        return $profileAddress;
    }

    protected function parseMessage($html)
    {
        $nameholderPath = 'div[2]/div[1]/div[1]';
        $spanPath = $nameholderPath.'/span[@class="hide people_list_container"]';
        $hxs = Selector\XPath::loadHTML($html);
        $threadId = $hxs->select('//input[@name="delete_message[]"]/@value')->extract();
        $subject = $hxs->select('//div[@class="msg_title_container"]/h3')->extract();
        $messageItems = $hxs->select('//div[@class="message_item_container" or @class="message_item_container unread"]');

        if (!$messageItems->hasResults()) {
            return array();
        }

        foreach ($messageItems as $messageItem) {
            $senderId = $messageItem->select($nameholderPath.'/a[1]/@href')->extract();
            $this->extractCitizenId($senderId);
            $senderName = $messageItem->select($nameholderPath.'/a[1]/@title')->extract();
            $firstRecieverId = $messageItem->select($nameholderPath.'/a[2]/@href')->extract();
            $this->extractCitizenId($firstRecieverId);
            $firstRecieverName = $messageItem->select($nameholderPath.'/a[2]/@title')->extract();
            $recievers = $messageItem->select($spanPath);
            $recieverIds = array();
            $recieverNames = array();
            if ($messageItem->select($spanPath.'/a')->hasResults()) {
                $recieverIdsDOM = $recievers->select('a/@href');
                foreach ($recieverIdsDOM as $recieverIdItem) {
                    $r = $recieverIdItem->extract();
                    $this->extractCitizenId($r);
                    $recieverIds[] = $r;
                }
                $recieverNamesDOM = $recievers->select('a/@title');
                foreach ($recieverNamesDOM as $recieverNameItem) {
                    $recieverNames[] = $recieverNameItem->extract();
                }
            }
            $time = $messageItem->select($nameholderPath)->extract();
            $time = trim(substr($time, strpos($time, '|') + 1));
            $unread = strpos('unread', $messageItem->select('@class')->extract()) !== false;
            $body = $messageItem->select('div[2]/div[@class="msg_body"]')->extract();
            $body = str_replace(PHP_EOL, '<br />', $body);

            $message = new Message;
            $message->threadId = $threadId;
            $message->subject = $subject;
            $message->senderId = (int) $senderId;
            $message->senderName = $senderName;
            $message->recieverIds = array_merge(array($firstRecieverId), $recieverIds);
            $message->recieverNames = array_merge(array($firstRecieverName), $recieverNames);
            $message->time = trim($time);
            $message->unread = $unread;
            $message->body = trim($body);

            $messages[] = $message;
        }

        return $messages;
    }

    public function retrieveMessage($threadId)
    {
        $response = $this->retrieveMessageHtml($threadId);

        return $this->parseMessage($response);
    }

    protected function retrieveMessageThreadsHtml()
    {
        //fluid_blue_light_medium message_get
        $this->getClient()->checkLogin();

        $pages = array();

        $i = 1;
           do {
            $request = $this->getClient()->get('main/messages-paginated/'.$i);
            $request->getHeaders()
            ->set('X-Requested-With', 'XMLHttpRequest')
            ->set('Referer', $this->getClient()->getBaseUrl().'/main/messages-inbox');

            $response = $request->send();
            $page = $response->getBody(true);

            $hxs = Selector\XPath::loadHTML($page);
            $notendpage = $hxs->select('//span[@class="older"]')->hasResults();
            if ($notendpage) {
                $pages[] = $page;
            }
            $i++;
        } while ($notendpage);

        return $pages;
    }

    protected function parseMessageThreads($html)
    {
        $hxs = Selector\XPath::loadHTML($html);
        $messageThreadsItems = $hxs->select('//tr[@class=" " or @class="special_msg " or @class=" unread"]');

        if (!$messageThreadsItems->hasResults()) {
            return array();
        }

        foreach ($messageThreadsItems as $messageThreadsItem) {
            $threadId = $messageThreadsItem->select('th/input/@value')->extract();
            $lastResponderId = $messageThreadsItem->select('td[1]/div[@class="nameholder"]/div[1]/a/@href')->extract();
            $lastResponderId = substr($lastResponderId, strripos($lastResponderId, '/profile/') + 10);
            $lastResponderName = $messageThreadsItem->select('td[1]/div[@class="nameholder"]/div[1]/a/@title')->extract();
            $r = $messageThreadsItem->select('td[2]/div[@class="break-word"]/a')->extract();
            $subject = substr($r, 0, strripos($r, '('));
            $unreadMessages = trim(substr($r, strripos($r, '(') + 1, strripos($r, '/') - strripos($r, '(')));
            $totalMessages = trim(substr($r, strripos($r, '/') + 1, strripos($r, ')') - strripos($r, '/')));
            $lastResponseBrief = trim($messageThreadsItem->select('td[2]/div[@class="break-word"]')->extract());
            $lastResponseBrief = substr($lastResponseBrief, strpos($lastResponseBrief, PHP_EOL));
            $lastResponseTime = $messageThreadsItem->select('td[1]/div[@class="nameholder"]/div[1]/span[1]')->extract();
            $unread = trim($messageThreadsItem->select('@class')->extract()) == 'unread';
            $specialMsg = trim($messageThreadsItem->select('@class')->extract()) == 'special_msg';
            $replied = $messageThreadsItem->select('td[2]/div[@class="replied"]')->hasResults();

            $messageThread = new MessagesThread;
            $messageThread->threadId = (int) $threadId;
            $messageThread->lastResponderId = (int) $lastResponderId;
            $messageThread->lastResponderName = $lastResponderName;
            $messageThread->subject = trim($subject);
            $messageThread->lastResponseBrief = trim($lastResponseBrief);
            $messageThread->totalMessages = (int) $totalMessages;
            $messageThread->unreadMessages = (int) $unreadMessages;
            $messageThread->lastResponseTime = trim($lastResponseTime);
            $messageThread->unread = (boolean) $unread;
            $messageThread->specialMsg = (boolean) $specialMsg;
            $messageThread->replied = (boolean) $replied;
            $messageThreads[] = $messageThread;
        }

        return $messageThreads;
    }

    public function retrieveMessageThreads()
    {
        $response = $this->retrieveMessageThreadsHtml();
        $messages = array();
        if (is_array($response)) {
            foreach ($response as $page) {
                $r = $this->parseMessageThreads($page);
                $messages = array_merge($messages, $r);
            }
        }

        return $messages;
    }

    protected function updateFriend($citizenId, $status = 'add')
    {
        $citizenId = Filter::id($citizenId);
        $this->getClient()->checkLogin();
        $request = $this->getClient()->get('citizen/profile/'.$citizenId);
        $response = $request->send();
        $html = $response->getBody(true);

        $hxs = Selector\XPath::loadHTML($html);
        if ($hxs->select('//a[@class="action_friend tip"]/@href')->hasResults()) {
            $addurl = $hxs->select('//a[@class="action_friend tip"][1]/@href')->extract();
        }
        if ($hxs->select('//a[@class="action_friend_remove tip"]/@href')->hasResults()) {
            $removeurl = $hxs->select('//a[@class="action_friend_remove tip"][1]/@href')->extract();
        }

        if (isset($addurl) && $status = 'add') {
            $url = $addurl;
        }
        if (isset($removeurl) && $status = 'remove') {
            $url = $removeurl;
        }

        if (isset($url)) {
           $crequest = $this->getClient()->post($url);
           $cresponse = $crequest->send();
           $newRequest = $this->getClient()->get($cresponse->getLocation());
           $newResponse = $newRequest->send();
           $html = $newResponse->getBody(true);

           $hxs = Selector\XPath::loadHTML($html);
           $node = $hxs->select('//table[@class="success_message"]');
           if ($node->hasResults()) {
                  return $node->select('tr/td')->extract();
           }
        }

        return false;
    }

    public function isFriend($citizenId)
    {
        $citizenId = Filter::id($citizenId);
        $this->getClient()->checkLogin();
        $request = $this->getClient()->get('citizen/profile/'.$citizenId);
        $response = $request->send();
        $html = $response->getBody(true);

        $hxs = Selector\XPath::loadHTML($html);
        if ($hxs->select('//a[@class="action_friend tip"]')->hasResults()) {
            return false;
        }
        if ($hxs->select('//a[@class="action_friend_remove tip"]')->hasResults()) {
            return true;
        }

    }

    public function listFriendsbyPage($citizenId, $page)
    {
        $citizenId = Filter::id($citizenId);
        $this->getClient()->checkLogin();
        $request = $this->getClient()->get('main/citizen-friends/'.$citizenId.'/'.$page.'/list');

        $errtime = 0;
        $notloaded = true;
        while ($notloaded && $errtime < 5) {
            try {
                $response = $request->send();
                $html = $response->json()['content'];
                $notloaded = false;
            } catch (CurlException $e) {
                $errtime++;
            } catch (ClientErrorResponseException $e) {
                return array();
            }
        }

        $hxs = Selector\XPath::loadHTML($html);
        $friendsListItems = $hxs->select('//tr');

        if (!$friendsListItems->hasResults()) {
            return array();
        }

        foreach ($friendsListItems as $friendItem) {
            $link = $friendItem->select('td[@class="friend_info"]/a/@href')->extract();
            $citizenId = substr($link, strripos($link, '/') + 1);
            $citizenName = $friendItem->select('td[@class="friend_info"]/a/@title')->extract();
            $isDead = false;
            if ($friendItem->select('@class')->hasResults()) {
                $isDead = trim($friendItem->select('@class')->extract()) == 'dead';
            }
            $avatarUrl = $friendItem->select('td[@class="friend_info"]/a/img/@src')->extract();
            if ($friendItem->select('td[@class="actions"]')->hasResults()) {
                $removeUrl = $friendItem->select('td[@class="actions"]/div/a[@class="act remove"]/@href')->extract();
            }

            $friend = array();
            $friend['citizenId'] = (int) $citizenId;
            $friend['citizenName'] = $citizenName;
            $friend['isDead'] = (boolean) $isDead;
//    		$friend['link'] = $link;
            $friend['avatarUrl'] = $avatarUrl;
            if (isset($removeUrl)) {
                $friend['removeUrl'] = $removeUrl;
            }

            $friends[] = $friend;
        }

        return $friends;
    }

    public function iterateFriends($citizenId, $callback)
    {
        $friends = array();

        $i = 1;
        do {
            $page = $this->listFriendsbyPage($citizenId, $i);

            $notendpage = !empty($page);
 //           $friends = array_merge($friends, $page);
            foreach ($page as $friend) {
            	$callback($friend);
            }
            $i++;
        } while ($notendpage);

        return $friends;
    }

    public function addFriend($citizenId)
    {
        return $this->updateFriend($citizenId);
    }

    public function removeFriend($citizenId)
    {
        return $this->updateFriend($citizenId, 'remove');
    }

    protected function parseDonation($html)
    {
        $hxs = Selector\XPath::loadHTML($html);
        $node = $hxs->select('//table[@class="info_message"]');
        if ($node->hasResults()) {
            return $node->select('tr/td')->extract();
        }

        return false;
    }

    protected function donate($citizenId, $amount, $currencyId)
    {
        $citizenId = Filter::id($citizenId);
        $this->getClient()->checkLogin();

        $request = $this->getClient()->post('economy/donate-money-action');
        $request->setHeader('Referer', $this->getClient()->getBaseUrl().'/economy/donate-money/'.$citizenId);
        $request->addPostFields(
                array(
                        'citizen_id'    => $citizenId,
                        'amount' => $amount,
                        'currency_id' => $currencyId,
                        '_token' => $this->getSession()->getToken()
        )
        );

        $response = $request->send();

        $newRequest = $this->getClient()->get($response->getLocation());
        $newResponse = $newRequest->send();

        return $this->parseDonation($newResponse->getBody(true));
    }

    public function donateMoney($citizenId, $amount)
    {
        return $this->donate($citizenId, $amount, self::COUNTRY_CURRENCY);
    }

    public function donateGold($citizenId, $amount)
    {
        return $this->donate($citizenId, $amount, self::GOLD);
    }

    public function donateItems($citizenId, $amount, Entity\Industry $industry, $quality)
    {
        $citizenId = Filter::id($citizenId);
        $this->getClient()->checkLogin();

        $request = $this->getClient()->post('economy/donate-items-action');
        $request->setHeader('Referer', $this->getClient()->getBaseUrl().'/economy/donate-items/'.$citizenId);
        $request->addPostFields(
                array(
                        '_token'          => $this->getSession()->getToken(),
                        'citizen_id'    => $citizenId,
                        'amount' => $amount,
                        'industry_id' => $industry->getId(),
                        'quality' => $quality
        )
        );

        $response = $request->send();

        $newRequest = $this->getClient()->get($response->getLocation());
        $newResponse = $newRequest->send();

        return $this->parseDonation($newResponse->getBody(true));
    }
}
