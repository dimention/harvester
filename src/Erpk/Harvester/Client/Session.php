<?php
namespace Erpk\Harvester\Client;

use GuzzleHttp\Cookie\FileCookieJar;

class Session
{
    protected $savePath;
    protected $authTimeout = 480;
    protected $cookieJar;
    protected $data = array(
        'touch'         =>  null,
        'token'         =>  null,
        'citizen.id'    =>  null,
        'citizen.name'  =>  null
    );
    
    public function __construct($savePath)
    {
        $this->savePath=$savePath;
        $this->cookieJar = new FileCookieJar($this->savePath);
    }
    
    public function save()
    {
        $copy = $this->data;
        $this->cookieJar->Save($this->savePath);
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
        return $this->cookieJar;
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
