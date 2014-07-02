<?php
namespace Erpk\Harvester\Client;

use Erpk\Harvester\Exception;
use Erpk\Harvester\Filter;
use Erpk\Harvester\Client\Plugin\Maintenance\MaintenancePlugin;
use Erpk\Harvester\Client\Proxy\ProxyInterface;
use GuzzleHttp\Subscriber\Cookie;
use GuzzleHttp\Client as GuzzleClient;

class Client extends GuzzleClient
{
    protected $email;
    protected $password;
    protected $session;
    protected $proxy = null;
    
    public function __construct()
    {
        $defaults = [
            'base_url' => 'http://www.erepublik.com',
            'defaults' => [
                'allow_redirects' => false,
                'timeout' => 5000,
                'headers' => [
                    'Expect' => '',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.8',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.66 Safari/537.36'
                ]]
        ];

        parent::__construct($defaults);
        $this->getEmitter()->attach(new MaintenancePlugin);
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
            $cookiePlugin = new Cookie($this->session->getCookieJar());
            $this->getEmitter()->attach($cookiePlugin);
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
        $options = [
            'query' => [
                '_token'            =>  md5(time()),
                'citizen_email'     =>  $this->getEmail(),
                'citizen_password'  =>  $this->getPassword(),
                'remember'          =>  1
            ],
            'headers' => [
                'Referer' => $this->getBaseUrl()
            ]
        ];

        $login = $this->post('en/login', $options);

        $status = $login->getStatusCode();
        if ($status >= 300 && $status < 400) {
            $homepage = $this->get('en');
            $hxs = Selector\XPath::loadHTML($homepage->getBody(true));
            $this->parseSessionData($hxs);
        } else {
            throw new Exception\ScrapeException('Login failed.');
        }
    }
    
    public function logout()
    {
        $this->post('en/logout');
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
        $id   = (int)strtr($userAvatar->select('@href')->extract(), ['/en/citizen/profile/' => '']);
        $name = $userAvatar->select('@title')->extract();
        
        $this
            ->getSession()
            ->setToken($token)
            ->setCitizenId($id)
            ->setCitizenName($name)
            ->save();
    }
}
