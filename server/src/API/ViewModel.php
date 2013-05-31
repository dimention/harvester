<?php
namespace API;

class ViewModel
{
    protected $data;
    protected $code;
    
    public static function error($type, $code, $msg = null)
    {
        $vm = new self(array('type' => $type, 'message' => $msg), $code);
        $vm->setRootNodeName('error');
        return $vm;
    }

    public function toArray()
    {
        return $this->data;
    }
    
    public function __construct($data, $code = 200)
    {
        $this->data = $this->convert($data);
        $this->code = $code;
    }

    public function getStatusCode()
    {
        return $this->code;
    }

    protected function convert($item)
    {
        if (is_array($item)) {
            foreach ($item as &$v) {
                $v = $this->convert($v);
            }
        } elseif (is_object($item)) {
            $item = $item->toArray();
            $item = $this->convert($item);
        }
        return $item;
    }
    
    public function setRootNodeName($name)
    {
        $this->rootNode = $name;
        return $this;
    }
    
    public function getRootNodeName()
    {
        return isset($this->rootNode) ? $this->rootNode : null;
    }
}
