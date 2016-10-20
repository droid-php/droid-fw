<?php

namespace Droid\Plugin\Fw\Model;

use Droid\Model\Feature\Firewall\FirewallInterface;

/**
 * UfwFirewall.
 */
class UfwFirewall implements FirewallInterface
{
    protected $firewall;

    public function __construct(FirewallInterface $firewall)
    {
        $this->firewall = $firewall;
    }

    /**
     * @see \Droid\Model\Feature\Firewall\FirewallInterface::getRulesByHostname
     */
    public function getRulesByHostname($name)
    {
        return $this->firewall->getRulesByHostname($name);
    }

    /**
     * @see \Droid\Model\Feature\Firewall\FirewallInterface::constructAddresses
     */
    public function constructAddresses($address)
    {
        return $this->firewall->constructAddresses($address);
    }

    /**
     * @see \Droid\Model\Feature\Firewall\FirewallInterface::constructAddress
     */
    public function constructAddress($address)
    {
        return $this->firewall->constructAddress($address);
    }

    /**
     * Get the default firewall policy for the named Host, as a map of
     * directions to actions.
     *
     * The policy returned will be the "standard" policy (see standardPolicy)
     * merged with a policy obtained from Droid's Firewall.
     *
     * @param string $name Name of a Host
     *
     * @return array
     */
    public function getPolicyByHostname($name)
    {
        return array_merge(
            $this->standardPolicy(),
            $this->firewall->getPolicyByHostname($name)
        );
    }

    public function getPolicy()
    {
        return $this->standardPolicy();
    }

    /**
     * A list of UFW DIRECTIONS.
     */
    final public static function directions()
    {
        return array('incoming', 'outgoing', 'routed');
    }

    /**
     * A list of UFW verbs for the command named "default".
     */
    final public static function policyActions()
    {
        return array('accept', 'deny', 'reject');
    }

    /**
     * Get the standard default firewall policy, as a map of directions to
     * actions.
     *
     * @return array
     */
    protected function standardPolicy()
    {
        return array(
            'incoming' => 'deny',
            'outgoing' => 'reject',
            'routed' => 'reject',
        );
    }
}
