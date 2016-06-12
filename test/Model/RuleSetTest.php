<?php

namespace Droid\Test\Plugin\Fw\Model;

use Droid\Plugin\Fw\Model\RuleSet;

class RuleSetTest extends \PHPUnit_Framework_TestCase
{
    public function testICanLoadRuleSet()
    {
        new RuleSet('some-ruleset-name');
    }
}
