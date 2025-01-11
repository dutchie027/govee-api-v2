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
                if ($instance === 'sensorHumidity') {
                    $stateValue = $capability['state']['value']['currentHumidity'];
                } else {
                    $stateValue = $capability['state']['value'];
                }

                break;
            }
        }

        return (int) $stateValue;
    }

    /**
     * Convert integer to RGB array
     *
     * @return array<int> $colors
     */
    public function intToRGB(int $rgb): array
    {
        $colors['r'] = ($rgb >> 16) & 0xFF;
        $colors['g'] = ($rgb >> 8) & 0xFF;
        $colors['b'] = $rgb & 0xFF;

        return $colors;
    }
}
