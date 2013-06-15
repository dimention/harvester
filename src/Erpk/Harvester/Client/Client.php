<?php
namespace Erpk\Harvester\Client;

use Erpk\Harvester\Exception;
use Erpk\Harvester\Filter;
use Erpk\Harvester\Client\Plugin\Maintenance\MaintenancePlugin;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Http\Client as GuzzleClient;

class Client extends GuzzleClient
{
    protected $email;
    protected $password;
    protected $session;
    protected $proxy = null;
    
    public function __construct($locale = 'en')
    {
        parent::__construct(
            'http://www.erepublik.com/'.$locale,
            array('redirect.disable' => true)
        );
        
        $this->getConfig()->set(
            'curl.options',
            array(
                CURLOPT_ENCODING          => '',
                CURLOPT_FOLLOWLOCATION    => false,
                CURLOPT_CONNECTTIMEOUT_MS => 3000,
                CURLOPT_TIMEOUT_MS        => 5000
            )
        );
        
        $this->getDefaultHeaders()
            ->set('Expect', '')
            ->set('Accept', 'text/javascript, application/javascript, application/ecmascript, application/x-ecmascript, */*; q=0.01')
            ->set('Accept-Charset', 'ISO-8859-2,utf-8;q=0.7,*;q=0.3')
            ->set('Accept-Language', 'pl-PL,pl;q=0.8,en-US;q=0.6,en;q=0.4');
        $this->getEventDispatcher()->addSubscriber(new MaintenancePlugin);

        $this->setUserAgent('Mozilla/5.0 (Windows NT 6.1; WOW64; rv:21.0) Gecko/20100101 Firefox/21.0');
    }
    
    public function setEmail($email)
    {
        $this->email = Filter::email($email);
        return $this;
    }
    
    public function getEmail()
    {
        if (isset($this->email)) {
            return $this->email;
        } else {
            throw new Exception\ConfigurationException('Account e-mail address not specified.');
        }
    }
    
    public function setPassword($pwd)
    {
        $this->password = $pwd;
        return $this;
    }
    
    public function getPassword()
    {
        if (isset($this->password)) {
            return $this->password;
        } else {
            throw new Exception\ConfigurationException('Account password not specified.');
        }
    }
    
    public function getSession()
    {
        if (!isset($this->session)) {
            $this->session = new Session(sys_get_temp_dir().'/'.'erpk['.$this->getEmail().'].session');
            $cookiePlugin = new CookiePlugin($this->session->getCookieJar());
            $this->getEventDispatcher()->addSubscriber($cookiePlugin);
        }
        return $this->session;
    }
    
    public function hasProxy()
    {
        return $this->proxy instanceof Proxy\AbstractProxy;
    }
    
    public function getProxy()
    {
        return $this->proxy;
    }
    
    public function setProxy(Proxy\AbstractProxy $proxy)
    {
        if ($this->hasProxy()) {
            $this->proxy->remove($this);
        }
        
        $this->proxy = $proxy;
        $this->proxy->apply($this);
        return $this;
    }
    
    public function removeProxy()
    {
        $this->proxy->remove($this);
        $this->proxy = null;
    }
    
    public function login()
    {
        $login = $this->post('login');
        $login->addPostFields(
            array(
                '_token'            =>  md5(time()),
                'citizen_email'     =>  $this->getEmail(),
                'citizen_password'  =>  $this->getPassword(),
                'remember'          =>  1
            )
        );
        
        $login->setHeader('Referer', 'http://www.erepublik.com/en');
        $login = $login->send();
        
        if ($login->isRedirect()) {
            $homepage = $this->get()->send();
            $hxs = Selector\XPath::loadHTML($homepage->getBody(true));
            $this->parseSessionData($hxs);
        } else {
            throw new Exception\ScrapeException('Login failed.');
        }
    }
    
    public function logout()
    {
        $this->post('logout')->send();
    }
    
    public function checkLogin()
    {
        if (!$this->getSession()->isValid()) {
            $this->login();
        }
    }
    
    protected function parseSessionData($hxs)
    {
        $userAvatar = $hxs->select('//a[@class="user_avatar"][1]');
        $token = $hxs->select('//*[@id="_token"][1]/@value')->extract();
        $id    = (int)strtr($userAvatar->select('@href')->extract(), array('/en/citizen/profile/' => ''));
        $name  = $userAvatar->select('@title')->extract();
        
        $this
            ->getSession()
            ->setToken($token)
            ->setCitizenId($id)
            ->setCitizenName($name)
            ->save();
    }
}
