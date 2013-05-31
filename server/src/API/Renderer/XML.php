<?php
namespace API\Renderer;

use XMLWriter;

class XML extends AbstractRenderer implements RendererInterface
{
    public function render($response, $viewModel)
    {
        $xml = new XmlWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);
        
        $data = $viewModel->toArray();
        $rootNode = $viewModel->getRootNodeName();
        if (!$rootNode) {
            $rootNode = key($data);
            $data = $data[$rootNode];
        }
        
        if (is_array($data)) {
            $xml->startElement($rootNode);
            $this->write($xml, $data);
            $xml->endElement();
        } else {
            $xml->writeElement($rootNode, utf8_encode($data));
        }
        
        $response->headers->set('Content-Type', 'application/xml;charset=utf-8');
        $response->setContent($xml->outputMemory(true));
    }
    

    public function write(XMLWriter $xml, $data)
    {
        if (isset($data['@nodeName'])) {
            $tag = $data['@nodeName'];
            unset($data['@nodeName']);
        } else {
            $tag = null;
        }

        foreach ($data as $key => $value) {
            $key = $tag ? $tag : $key;
            if (is_array($value)) {
                $xml->startElement($key);
                $this->write($xml, $value);
                $xml->endElement();
                continue;
            } elseif (is_bool($value)) {
                $value=$value ? 'true' : 'false';
            }

            $xml->writeElement($key, utf8_encode($value));
        }
    }
}
