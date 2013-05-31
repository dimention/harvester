<?php
namespace API\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use React;
use API\Renderer;
use API\ViewModel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Erpk\Harvester\Client\Client;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Erpk\Harvester\Exception\Exception as ErpkException;

class RunCommand extends Command
{
    protected $client;
    protected $routes;

    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Start server')
            ->addOption(
                'email',
                null,
                InputOption::VALUE_REQUIRED,
                'Your eRepublik\'s account email address'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'Your eRepublik\'s account password'
            )
            ->addOption(
                'port',
                null,
                InputOption::VALUE_REQUIRED,
                'Server port'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new Client;
        $client->setEmail($input->getOption('email'));
        $client->setPassword($input->getOption('password'));
        $this->client = $client;

        $locator = new FileLocator(array(__DIR__.'/..'));
        $loader = new YamlFileLoader($locator);
        $collection = $loader->load('routing.yml');
        $this->routes = $collection;


        $loop = React\EventLoop\Factory::create();
        $socket = new React\Socket\Server($loop);
        $http = new React\Http\Server($socket);

        $http->on('request', array($this, 'handleRequest'));

        $port = $input->getOption('port');
        $socket->listen($port);
        echo 'Server running on http://localhost:'.$port.'/';
        $loop->run();
    }

    protected function controller($params)
    {
        $ex = explode('::', $params['_controller']);
        $className = 'API\Controller\\'.$ex[0];

        $obj = new $className($this->client, $params);
        try {
            return $obj->{$ex[1]}();
        } catch (ErpkException $e) {
            return ViewModel::error(get_class($e), 500);
        }
    }

    public function handleRequest($req, $res)
    {
        $uri = $req->getPath();
        $request = Request::create($uri.'?'.http_build_query($req->getQuery()));
        $context = new RequestContext();
        $context->fromRequest($request);
        $matcher = new UrlMatcher($this->routes, $context);

        try {
            $parameters = $matcher->match($uri);
            $vm = $this->controller($parameters);
        } catch (ResourceNotFoundException $e) {
            $vm = ViewModel::error('NotFoundException', 404);
        }

        if (isset($parameters['_format'])) {
            switch ($parameters['_format']) {
                case 'xml':
                    $renderer = new Renderer\XML;
                    break;
                case 'json':
                default:
                    $renderer = new Renderer\JSON;
            }
        } else {
            $renderer = new Renderer\JSON;
        }

        $response = new Response();
        $response->setStatusCode($vm->getStatusCode());
        $renderer->render($response, $vm);

        $headers = array();
        foreach ($response->headers as $k => $v) {
            $headers[$k] = $v[0];
        }

        $res->writeHead($response->getStatusCode(), $headers);
        $res->end($response->getContent());
    }
}
