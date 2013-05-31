<?php
namespace API\Renderer;

interface RendererInterface
{
    public function render($response, $viewModel);
}
