<?php

namespace Droid\Plugin\Fw\Model;

class Rule
{
    protected $source;
    protected $port;
    
    public function getSource()
    {
        return $this->source;
    }
    
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }
    
    public function getPort()
    {
        return $this->port;
    }
    
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    protected $direction = 'in';
    
    public function getDirection()
    {
        return $this->direction;
    }
    
    public function setDirection($direction)
    {
        $this->direction = $direction;
        return $this;
    }

    protected $action;
    
    public function getAction()
    {
        return $this->action;
    }
    
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }
}
