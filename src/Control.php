<?php

namespace dutchie027\GoveeApiV2;

class Control
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
    public function power(string $device): string
    {
        $body = '';
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

                if ($response === null) {
                    return 'API Error';
                }
                $body = $response->getBody();

                break;
            }
        }

        return $body;
    }

    public function colorRGB(string $device, int $r, int $g, int $b): string
    {
        $body = '';
        $jsonString = $this->client->getDeviceState($device);

        // Decode JSON string into PHP associative array
        $data = json_decode($jsonString, true);

        // Iterate through capabilities and check for type
        foreach ($data['payload']['capabilities'] as $capability) {
            $typeString = $capability['type'];
            $typeParts = explode('.', $typeString);
            $lastPart = end($typeParts);

            if ($lastPart == 'color_setting') {
                if ($capability['instance'] == 'colorRgb') {
                    $colorValue = $this->rgbToInteger($r, $g, $b);
                    $extra = [
                        'type' => 'devices.capabilities.color_setting',
                        'instance' => 'colorRgb',
                        'value' => $colorValue,
                    ];
                    $jsonPayload = $this->client->createPostPayload($data['payload']['sku'], $data['payload']['device'], $extra);

                    $response = $this->client->makeAPICall('POST', $this->client::DEVICE_CONTROL, $jsonPayload);

                    if ($response === null) {
                        return 'API Error';
                    }
                    $body = $response->getBody();

                    break;
                }
            }
        }

        return $body;
    }

    public function colorK(string $device, int $k): string
    {
        $body = '';
        $jsonString = $this->client->getDeviceState($device);

        // Decode JSON string into PHP associative array
        $data = json_decode($jsonString, true);

        // Iterate through capabilities and check for type
        foreach ($data['payload']['capabilities'] as $capability) {
            $typeString = $capability['type'];
            $typeParts = explode('.', $typeString);
            $lastPart = end($typeParts);

            if ($lastPart == 'color_setting') {
                if ($capability['instance'] == 'colorTemperatureK') {
                    $min = $capability['parameters']['range']['min'];
                    $max = $capability['parameters']['range']['max'];

                    if ($k < $min || $k > $max) {
                        throw new \InvalidArgumentException('Color temperature must be between ' . $min . ' and ' . $max . '.');
                    }
                    $colorValue = $k;
                    $extra = [
                        'type' => 'devices.capabilities.color_setting',
                        'instance' => 'colorTemperatureK',
                        'value' => $colorValue,
                    ];
                    $jsonPayload = $this->client->createPostPayload($data['payload']['sku'], $data['payload']['device'], $extra);

                    $response = $this->client->makeAPICall('POST', $this->client::DEVICE_CONTROL, $jsonPayload);

                    if ($response === null) {
                        return 'API Error';
                    }
                    $body = $response->getBody();

                    break;
                }
            }
        }

        return $body;
    }

    private function rgbToInteger(int $r, int $g, int $b): int
    {
        // Check if r, g, and b are between 0 and 255
        if ($r < 0 || $r > 255 || $g < 0 || $g > 255 || $b < 0 || $b > 255) {
            throw new \InvalidArgumentException('RGB values must be between 0 and 255.');
        }

        return (($r & 0xFF) << 16) | (($g & 0xFF) << 8) | ($b & 0xFF);
    }
}
