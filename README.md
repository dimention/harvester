README
=========

What is that?
-------------

**Harvester** is eRepublik web scraping utility. It allows you easily get useful information directly from game.
It's written in PHP and based mainly on DOMXPath library.

Get started
-----------

Recommended way to install library is getting it through [Composer](http://getcomposer.org/).
Example of composer.json file:
```json
{
    "require": {
      "erpk/harvester": "*"
    }
}
```

Client
------

Client is object required in every Harvester module. How to create it?
```php
require __DIR__.'/vendor/autoload.php';

use Erpk\Harvester\Client\Client;

$client = new Client;
$client->setEmail('your_erepublik@email.com');
$client->setPassword('your_erepublik_password');
```

Modules
-------
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

// And economical data...
$economy = $module->getEconomy($poland);
```
