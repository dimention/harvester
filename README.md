README
=========

What is that?
-------------

**Harvester** is eRepublik web scraping utility. Originally, Harvester was used by [api.erpk.org](http://api.erpk.org), but I decided to release it open source. It allows you easily get useful information directly from game.
It's written in PHP and based mainly on DOMXPath library. Requires PHP 5.3+. For **standalone API webserver** look in [erpk/harsever](https://github.com/erpk/harserver).

Get started
-----------

Recommended way to install library is getting it through [Composer](http://getcomposer.org/).
Create composer.json file in your application directory:
```json
{
    "require": {
      "erpk/harvester": "*"
    }
}
```

Then download latest [composer.phar](http://getcomposer.org/composer.phar) and run following command:
```
php composer.phar install
```
That command will install Harvester along with all its dependencies. Now, in order to use libraries, you have to include autoloader, which is located in `vendor/autoload.php`.
```php
<?php
require __DIR__.'/vendor/autoload.php';
```

Client
------

Client is object required in every Harvester module. How to create it?
```php
<?php
require __DIR__.'/vendor/autoload.php';

use Erpk\Harvester\Client\Client;

$client = new Client;
$client->setEmail('your_erepublik@email.com');
$client->setPassword('your_erepublik_password');
```

Modules
-------
Following examples assume you have already set up your Client and included autoloader.
###Citizen
```php
use Erpk\Harvester\Module\Citizen\CitizenModule;
// assumes you have your Client object already set up
$module = new CitizenModule($client);

// Get citizen profile
$citizen = $module->get(2020512);
echo $citizen['name']; // Romper

// Search for citizens by name
$results = $module->search('Romp', 1); // page 1
print_r($results);
```
###Military
```php
use Erpk\Harvester\Module\Military\MilitaryModule;
$module = new MilitaryModule($client);

$activeCampaigns = $module->getActiveCampaigns();

$campaign = $module->getCampaign(41661);

$unit = $module->getUnit(5);

$regiment = $module->getRegiment(5, 1);
```

###Exchange
```php
use Erpk\Harvester\Module\Exchange\ExchangeModule;
$module = new ExchangeModule($client);

// Offers for buy currency, page 1
$offers = $module->scan(ExchangeModule::CURRENCY, 1);

// Offers for buy gold, page 1
$offers = $module->scan(ExchangeModule::GOLD, 1);

// Buy offer
$response = $module->buy($offerId, $amountToBuy);
```

###JobMarket
```php
use Erpk\Harvester\Module\JobMarket\JobMarketModule;
$module = new JobMarketModule($client);

// Job offers in Poland, page 1
use Erpk\Common\EntityManager;
$em = EntityManager::getInstance();
$countries = $em->getRepository('\Erpk\Common\Entity\Country');

$poland = $countries->findOneByCode('PL');
$offers = $module->scan($poland, 1);
```

###Market
```php
use Erpk\Harvester\Module\Market\MarketModule;
$module = new MarketModule($client);

// Q7 weapons offers in Poland, page 1
use Erpk\Common\EntityManager;
$em = EntityManager::getInstance();
$countries = $em->getRepository('\Erpk\Common\Entity\Country');
$industries = $em->getRepository('\Erpk\Common\Entity\Industry');

$poland = $countries->findOneByCode('PL');
$weapons = $industries->findOneByCode('weapons');

$offers = $module->scan($poland, $weapons, 7, 1);

// And now buy some weapons
$response = $module->buy($offers[0], 15);
```

###Country
```php
use Erpk\Harvester\Module\Country\CountryModule;
$module = new CountryModule($client);

// Get Poland's society information
use Erpk\Common\EntityManager;
$em = EntityManager::getInstance();
$countries = $em->getRepository('\Erpk\Common\Entity\Country');
$poland = $countries->findOneByCode('PL');

$society = $module->getSociety($poland);
```

###Management
```php
use Erpk\Harvester\Module\Management\ManagementModule;
$module = new ManagementModule($client);

// Refill energy
$module->eat();
// Get items in inventory
$module->getInventory();
// Train in all (four) training grounds
$module->train(true, true, true, true);
// Work as employee
$module->workAsEmployee();
// Get rewards for daily tasks
$module->getDailyTasksReward();
```
