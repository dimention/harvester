<?php
namespace Erpk\Harvester\Client;

use Guzzle\Plugin\Cookie\Cookie;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;

class Session
{
    protected $savePath;
    protected $authTimeout = 480;
    protected $data = array(
        'touch'         =>  null,
        'token'         =>  null,
        'cookieJar'     =>  null,
        'citizen.id'    =>  null,
        'citizen.name'  =>  null
    );
    
    public function __construct($savePath)
    {
        $this->savePath=$savePath;
        $cookieJar = new ArrayCookieJar();
        
        if (file_exists($savePath)) {
            $this->data = unserialize(file_get_contents($savePath));
            $cookieJar->unserialize($this->data['cookieJar']);
        }
        
        $this->data['cookieJar'] = $cookieJar;
    }
    
    protected function serializeCookieJar()
    {
        return json_encode(
            array_map(
                function (Cookie $cookie) {
                    return $cookie->toArray();
                },
                $this->getCookieJar()->all()
            )
        );
    }
    
    public function save()
    {
        $copy = $this->data;
        $copy['cookieJar'] = $this->serializeCookieJar();
        
        file_put_contents(
            $this->savePath,
            serialize($copy)
        );
    }
    
    public function __destruct()
    {
        $this->save();
    }
    
    public function isValid()
    {
        return (time()-$this->data['touch'])<$this->authTimeout;
    }
    
    public function getCookieJar()
    {
        return $this->data['cookieJar'];
    }
    
    public function getCitizenName()
    {
        return $this->data['name'];
    }

    public function setCitizenName($name)
    {
        $this->data['citizen.name'] = $name;
        return $this;
    }
    
    public function getCitizenId()
    {
        return $this->data['id'];
    }

    public function setCitizenId($id)
    {
        $this->data['citizen.id'] = $id;
        return $this;
    }
    
    public function getToken()
    {
        return $this->data['token'];
    }
    
    public function setToken($token)
    {
        $this->data['token'] = $token;
        $this->touch();
        return $this;
    }
    
    public function touch()
    {
        $this->data['touch'] = time();
    }
}
