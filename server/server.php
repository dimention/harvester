<?php
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/../vendor/autoload.php';

use API\Command\RunCommand;
use Symfony\Component\Console\Application;

$app = new Application();
$app->add(new RunCommand);
$app->run();
