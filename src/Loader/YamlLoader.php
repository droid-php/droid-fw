<?php

namespace Droid\Plugin\Fw\Loader;

use Symfony\Component\Yaml\Parser as YamlParser;
use Droid\Plugin\Fw\Model\Firewall;
use Droid\Plugin\Fw\Model\RuleSet;
use Droid\Plugin\Fw\Model\Rule;
use Droid\Plugin\Fw\Model\Source;
use Droid\Plugin\Fw\Model\Port;
use RuntimeException;

class YamlLoader
{
    public function load(Firewall $firewall, $filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }
        
        $parser = new YamlParser();
        $data = $parser->parse(file_get_contents($filename));
        $this->loadFirewall($firewall, $data);
    }
    
    private function loadFirewall(Firewall $firewall, $data)
    {
        foreach ($data as $name => $ruleSetData) {
            $ruleSet = new RuleSet($name);
            $firewall->addRuleSet($ruleSet);
            foreach ($ruleSetData as $ruleData) {
                $rule = new Rule();
                $part = explode('/', $ruleData['source']);
                if (count($part)!=2) {
                    throw new RuntimeException("Invalid source. Use `type/value`: " . $ruleData['source']);
                }
                $source = new Source($part[0], $part[1]);
                $rule->setSource($source);

                $part = explode('/', $ruleData['port']);
                if (count($part)!=2) {
                    throw new RuntimeException("Invalid source. Use `value/protocol`: " . $ruleData['port']);
                }

                $port = new Port($part[0], $part[1]);
                $rule->setPort($port);
                if (isset($ruleData['action'])) {
                    $rule->setAction($ruleData['action']);
                    $ruleSet->addRule($rule);
                }
                if (isset($ruleData['direction'])) {
                    $rule->setAction($ruleData['direction']);
                    $ruleSet->addRule($rule);
                }
            }
        }
    }
}
