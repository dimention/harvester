<?php
//require 'config.php';
require '../vendor/autoload.php';

use Erpk\Harvester\Client\Client;
use Erpk\Harvester\Module\Exchange\ExchangeModule;

$time = time();
$data = date('Y.m.d',$time);
$h = date('H',$time);
$mtime = microtime(true);

echo "Cron - ".date('Y.m.d H:i:s',$time)."\r\n------------------------------------\r\n";

$client = new Client;
$client->setEmail('statistikos_departamentas@yahoo.com');
$client->setPassword('dukartdu');
tim("prisijungimas 1");

$mod = new ExchangeModule($client);

$info = $mod->scanAll(ExchangeModule::GOLD, 10);
echo "<br>".count($info);
var_dump($info[15]);

tim('baigta');
function tim($str){
    global $mtime;
    $newtime = microtime(true);
    echo "\r\n==============".round($newtime-$mtime,3)."s ==================".$str."";
    $mtime=$newtime;
}