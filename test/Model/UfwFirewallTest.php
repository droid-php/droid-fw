<?php

namespace Droid\Test\Model\Feature\Firewall;

use Droid\Model\Feature\Firewall\FirewallInterface;

use Droid\Plugin\Fw\Model\UfwFirewall;

class UfwFirewallTest extends \PHPUnit_Framework_TestCase
{
    protected $baseFirewall;
    protected $firewall;

    protected function setUp()
    {
        $this->baseFirewall = $this
            ->getMockBuilder(FirewallInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
        $this->firewall =  new UfwFirewall($this->baseFirewall);
    }

    public function testGetPolicyWithoutCustomPolicyReturnsDefaultPolicy()
    {
        $this
            ->baseFirewall
            ->expects($this->once())
            ->method('getPolicy')
            ->willReturn(array())
        ;

        $policy = $this->firewall->getPolicy();

        foreach (UfwFirewall::directions() as $direction) {
            $this->assertArrayHasKey(
                $direction,
                $policy,
                sprintf('There is a policy for direction "%s".', $direction)
            );
            $this->assertContains(
                $policy[$direction],
                UfwFirewall::policyActions(),
                sprintf('The policy for direction "%s" has a valid action value.', $direction)
            );
        }

        return $policy;
    }

    /**
     * @depends testGetPolicyWithoutCustomPolicyReturnsDefaultPolicy
     * @param unknown $defaultPolicy
     */
    public function testGetPolicyWithCustomPolicyReturnsDefaultPolicyMergedWithCustomPolicy($defaultPolicy)
    {
        $customPolicy = $this->constructCustomFirewallPolicy($defaultPolicy);

        $this
            ->baseFirewall
            ->method('getPolicy')
            ->willReturn($customPolicy)
        ;

        $this->assertSame(
            array_merge($defaultPolicy, $customPolicy),
            $this->firewall->getPolicy()
        );
    }

    /*
     * create a custom policy by selecting one direction from the default policy
     * and changing the action value of that direction.
     */
    private function constructCustomFirewallPolicy($defaultPolicy)
    {
        $customPolicy = array();

        $directions = UfwFirewall::directions();
        $someDirection = $directions[1];
        foreach (UfwFirewall::policyActions() as $action) {
            if ($defaultPolicy[$someDirection] !== $action) {
                $customPolicy[$someDirection] = $action;
                break;
            }
        }

        return $customPolicy;
    }
}
