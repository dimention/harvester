<?php
namespace API\Renderer;

class JSON extends AbstractRenderer implements RendererInterface
{
    public function render($response, $viewModel)
    {
        $response->headers->set('Content-Type', 'application/json');
        $array = $this->filter($viewModel->toArray());
        $response->setContent(json_encode($array));
    }

    protected function filter($array)
    {
        if (isset($array['@nodeName'])) {
            unset($array['@nodeName']);
        }

        foreach ($array as &$val) {
            if (is_array($val)) {
                $val = $this->filter($val);
            }
        }
        return $array;
    }
}
