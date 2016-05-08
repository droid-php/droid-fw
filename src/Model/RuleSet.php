<?php

namespace Droid\Plugin\Fw\Model;

class RuleSet
{
    public function __construct($name)
    {
        $this->setName($name);
    }
    
    protected $name;
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    protected $rules = [];
    
    public function addRule(Rule $rule)
    {
        $this->rules[] = $rule;
    }
    
    public function getRules()
    {
        return $this->rules;
    }
    
    public function clearRules()
    {
        $this->rules = [];
    }
}
