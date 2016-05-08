<?php

namespace Droid\Plugin\Fw\Model;

class Source
{
    
    public function __construct($type, $value)
    {
        $this->setType($type);
        $this->setValue($value);
        
    }
    protected $type;
    
    public function getType()
    {
        return $this->type;
    }
    
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    
    protected $value;
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
