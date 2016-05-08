<?php

namespace Droid\Plugin\Fw\Model;

class Port
{
    public function __construct($value, $type)
    {
        $this->setValue($value);
        $this->setType($type);        
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
