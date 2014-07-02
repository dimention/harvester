<?php
namespace Erpk\Harvester\Client;

use Erpk\Harvester\Exception;
use Erpk\Harvester\Filter;
use Erpk\Harvester\Client\Plugin\Maintenance\MaintenancePlugin;
use Erpk\Harvester\Client\Proxy\ProxyInterface;
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
            ->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
            ->set('Accept-Language', 'en-US,en;q=0.8');
        $this->getEventDispatcher()->addSubscriber(new MaintenancePlugin);

        $this->setUserAgent('Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.66 Safari/537.36');
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
        return $this->proxy instanceof ProxyInterface;
    }
    
    public function getProxy()
    {
        return $this->proxy;
    }
    
    public function setProxy(ProxyInterface $proxy)
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
        
        $login->setHeader('Referer', $this->getBaseUrl());
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
        $token = null;

        $tokenInput = $hxs->select('//*[@id="_token"][1]/@value');
        if (!$tokenInput->hasResults()) {
            $scripts = $hxs->select('//script[@type="text/javascript"]');
            $tokenPattern = '@csrfToken\s*:\s*\'([a-z0-9]+)\'@';
            foreach ($scripts as $script) {
                if (preg_match($tokenPattern, $script->extract(), $matches)) {
                    $token = $matches[1];
                    break;
                }
            }
        } else {
            $token = $tokenInput->extract();
        }

        if ($token === null) {
            throw new Exception\ScrapeException('CSRF token not found');
        }

        $userAvatar = $hxs->select('//a[@class="user_avatar"][1]');
        $id   = (int)strtr($userAvatar->select('@href')->extract(), array('/en/citizen/profile/' => ''));
        $name = $userAvatar->select('@title')->extract();
        
        $this
            ->getSession()
            ->setToken($token)
            ->setCitizenId($id)
            ->setCitizenName($name)
            ->save();
    }
}
