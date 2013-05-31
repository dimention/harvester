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
Get citizen profile:
```php
// assumes you have your Client object already set up
use Erpk\Harvester\Module\Citizen\CitizenModule;
$module = new CitizenModule($client);

$citizen = $module->get(2020512);
echo $citizen['name']; // Romper
```

Search for citizens by name:
```php
use Erpk\Harvester\Module\Citizen\CitizenModule;
$module = new CitizenModule($client);

$results = $module->search('Romp', 1);
print_r($results);
```
