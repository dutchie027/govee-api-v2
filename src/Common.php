<?php

namespace dutchie027\GoveeApiV2;

class Common
{
    /**
     * Undocumented variable
     *
     * @var Connect
     */
    protected $client;

    /**
     * Default constructor
     */
    public function __construct(Connect $client)
    {
        $this->client = $client;
    }

    /**
     * power
     */
    public function getStateByInstance(string $json, string $instance): int
    {
        // Decode JSON string into PHP associative array
        $data = json_decode($json, true);

        // Initialize variable to store the desired value
        $stateValue = 0;

        // Iterate through the capabilities
        foreach ($data['payload']['capabilities'] as $capability) {
            if ($capability['instance'] === $instance) {
                $stateValue = $capability['state']['value'];
                break;
            }
        }

        return (int) $stateValue;
    }

    public function intToRGB(int $rgb): array
    {
        $colors['r'] = ($rgb >> 16) & 0xFF;
        $colors['g'] = ($rgb >> 8) & 0xFF;
        $colors['b'] = $rgb & 0xFF;
        return $colors;
            }

}
