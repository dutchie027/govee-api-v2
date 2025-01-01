<?php

namespace dutchie027\GoveeApiV2;

class Control
{
    private $name_array = [];
    private $mac_array = [];
    protected $client;

    /**
     * Default constructor
     */
    public function __construct(Connect $client)
    {
        $this->client = $client;
    }

    /**
     * turnOn
     * Turns A Plug On based on MAC or Name ($device)
     *
     * @param string $device
     *
     * @return string
     */
    public function power($device)
    {
        $jsonString = $this->client->getDeviceState($device);

        // Decode JSON string into PHP associative array
        $data = json_decode($jsonString, true);

        // Iterate through capabilities and check for type
        foreach ($data['payload']['capabilities'] as $capability) {
            $typeString = $capability['type'];
            $typeParts = explode('.', $typeString);
            $lastPart = end($typeParts);

            if ($lastPart == 'on_off') {
                $currval = $data['payload']['capabilities'][0]['state']['value'];
                $currval = $currval ^ 1;

                $extra = [
                    'type' => 'devices.capabilities.on_off',
                    'instance' => 'powerSwitch',
                    'value' => $currval,
                ];
                $jsonPayload = $this->client->createPostPayload($data['payload']['sku'], $data['payload']['device'], $extra);

                $response = $this->client->makeAPICall('POST', $this->client::DEVICE_CONTROL, $jsonPayload);

                return $response->getBody();
            }
        }
    }

    public function colorRGB($device, $r, $g, $b)
    {
        $jsonString = $this->client->getDeviceState($device);

        // Decode JSON string into PHP associative array
        $data = json_decode($jsonString, true);

        // Iterate through capabilities and check for type
        foreach ($data['payload']['capabilities'] as $capability) {
            $typeString = $capability['type'];
            $typeParts = explode('.', $typeString);
            $lastPart = end($typeParts);

            if ($lastPart == 'color_setting') {
                if ($capability['instance'] == 'colorRgb' && isset($r) && isset($g) && isset($b)) {
                    $colorValue = $this->rgbToInteger($r, $g, $b);
                    $instance = 'colorRgb';
                }

                $extra = [
                    'type' => 'devices.capabilities.color_setting',
                    'instance' => $instance,
                    'value' => $colorValue,
                ];
                $jsonPayload = $this->client->createPostPayload($data['payload']['sku'], $data['payload']['device'], $extra);

                $response = $this->client->makeAPICall('POST', $this->client::DEVICE_CONTROL, $jsonPayload);

                return $response->getBody();
            }
        }
    }

    public function colorK($device, $k)
    {
        $jsonString = $this->client->getDeviceState($device);

        // Decode JSON string into PHP associative array
        $data = json_decode($jsonString, true);

        // Iterate through capabilities and check for type
        foreach ($data['payload']['capabilities'] as $capability) {
            $typeString = $capability['type'];
            $typeParts = explode('.', $typeString);
            $lastPart = end($typeParts);

            if ($lastPart == 'color_setting') {
                if ($capability['instance'] == 'colorTemperatureK' && isset($k)) {
                    $min = $capability['parameters']['range']['min'];
                    $max = $capability['parameters']['range']['max'];

                    if ($k < $min || $k > $max) {
                        throw new \InvalidArgumentException('Color temperature must be between ' . $min . ' and ' . $max . '.');
                    }
                    $colorValue = $k;
                    $instance = 'colorTemperatureK';
                }

                $extra = [
                    'type' => 'devices.capabilities.color_setting',
                    'instance' => $instance,
                    'value' => $colorValue,
                ];
                $jsonPayload = $this->client->createPostPayload($data['payload']['sku'], $data['payload']['device'], $extra);

                $response = $this->client->makeAPICall('POST', $this->client::DEVICE_CONTROL, $jsonPayload);

                return $response->getBody();
            }
        }
    }

    private function rgbToInteger($r, $g, $b)
    {
        // Check if r, g, and b are between 0 and 255
        if ($r < 0 || $r > 255 || $g < 0 || $g > 255 || $b < 0 || $b > 255) {
            throw new \InvalidArgumentException('RGB values must be between 0 and 255.');
        }

        return (($r & 0xFF) << 16) | (($g & 0xFF) << 8) | ($b & 0xFF);
    }
}
