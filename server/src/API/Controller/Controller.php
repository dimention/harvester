<?php
namespace API\Controller;

abstract class Controller
{
    protected $request;
    protected $response;
    public $client;
    
    public function init()
    {
    }

    public function __construct($client, $params)
    {
        $this->client = $client;
        $this->params = $params;
    }

    public function param($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }
}
