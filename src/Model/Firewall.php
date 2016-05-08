<?php

namespace Droid\Plugin\Fw\Model;

use Droid\Model\Inventory;

class Firewall
{
    protected $inventory;
    public function __construct(Inventory $inventory)
    {
        $this->inventory = $inventory;
    }
    
    protected $ruleSets = [];
    
    public function addRuleSet(RuleSet $ruleSet)
    {
        $this->ruleSets[$ruleSet->getName()] = $ruleSet;
    }
    
    public function getRuleSet($name)
    {
        return $this->ruleSets[$name];
    }
    
    public function getRuleSets()
    {
        return $this->ruleSets;
    }
    
    public function getRulesByHostname($name)
    {
        $host = $this->inventory->getHost($name);
        $groups = $this->inventory->getHostGroups();
        $rules = [];
        foreach ($groups as $group) {
            if (in_array($host, $group->getHosts())) {
                $set = $this->getRuleSet($group->getName());
                if ($set) {
                    foreach ($set->getRules() as $rule) {
                        $rules[] = $rule;
                    }
                }
            }
        }
        $set = $this->getRuleSet($host->getName());
        if ($set) {
            foreach ($set->getRules() as $rule) {
                $rules[] = $rule;
            }
        }
        return $rules;
    }
}
