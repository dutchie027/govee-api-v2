<?php

namespace dutchie027\GoveeApiV2;

use Dotenv\Dotenv;
use dutchie027\GoveeApiV2\Log\Log;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class Connect
{
    /**
     * Root of the API.
     *
     * @const string
     */
    public const API_URL = 'https://openapi.api.govee.com';

    /**
     * Ping Endpoint
     *
     * @const string
     */
    public const PING_ENDPOINT = self::API_URL . '/ping';

    /**
     * RAW Device Endpoint
     *
     * @const string
     */
    public const DEVICE_ENDPOINT = self::API_URL . '/router/api/v1/user/devices';

    /**
     * Control Endpoint
     *
     * @const string
     */
    public const DEVICE_CONTROL = self::API_URL . '/router/api/v1/device/control';

    /**
     * Device State Endpoint
     *
     * @const string
     */
    public const DEVICE_STATE = self::API_URL . '/router/api/v1/device/state';

    /**
     * API Token
     *
     * @var string
     */
    private $p_token;

    /**
     * @var array<string, string>
     */
    private $name_hash = [];

    /**
     * @var array<string>
     */
    private $name_array = [];

    /**
     * @var array<string, string>
     */
    private $mac_hash = [];

    /**
     * @var array<string>
     */
    private $mac_array = [];

    /**
     * @var array<string,string>
     */
    private $sku_hash = [];

    /**
     * Remaining Times To Call the API
     *
     * @var string
     */
    public $rate_remain;

    /**
     * EPOCH when rate resets
     *
     * @var string
     */
    public $rate_reset;

    /**
     * Total Rate Limit
     *
     * @var string
     */
    public $rate_total;

    /**
     * The Guzzle HTTP client instance.
     *
     * @var \GuzzleHttp\Client
     */
    public $client;

    /**
     * Default constructor
     */
    public function __construct(string $apitoken = null, Guzzle $client = null)
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
        
        // Check if $apitoken is a valid GUID
        if ($apitoken && preg_match('/^\{?[A-F0-9]{8}\-[A-F0-9]{4}\-[A-F0-9]{4}\-[A-F0-9]{4}\-[A-F0-9]{12}\}?$/i', $apitoken)) {
            $this->p_token = $apitoken;
        } else {
            $this->p_token = $_ENV['API_TOKEN'];
        }
        
        $this->client = $client ?: new Guzzle();
        $this->loadAllDevices();
    }
    
    /**
     * getDeviceList
     * Returns Full Device List
     *
     * @return array<mixed>|string
     */
    public function getDeviceList(int $array = 0): string|array
    {
        $response = $this->makeAPICall('GET', self::DEVICE_ENDPOINT);

        if ($response === null) {
            return 'API Error';
        }

        return $array === 1 ? json_decode($response->getBody(), true) : $response->getBody();
    }

    /**
     * getDeviceCount
     * Returns total number of controllable devices
     */
    public function getDeviceCount(): int
    {
        return count($this->name_array);
    }

    /**
     * getDeviceMACArray
     * Returns array of controllable MAC addresses
     *
     * @return array<string>
     */
    public function getDeviceMACArray(string $sort = null): array
    {
        $sort === 'asc' ? sort($this->mac_array) : ($sort === 'desc' ? rsort($this->mac_array) : null);

        return $this->mac_array;
    }

    /**
     * getDeviceNameArray
     * Returns Array of Device Names
     *
     * @return array<string>
     */
    public function getDeviceNameArray(string $sort = null): array
    {
        $sort === 'asc' ? sort($this->name_array) : ($sort === 'desc' ? rsort($this->name_array) : null);

        return $this->name_array;
    }

    /**
     * getAPIToken
     * Returns the stored API Token
     */
    protected function getAPIToken(): string
    {
        return $this->p_token;
    }

    /**
     * setHeaders
     * Sets the headers using the API Token
     *
     * @return array<string, string>
     */
    public function setHeaders(): array
    {
        return [
            'User-Agent' => 'testing/1.0',
            'Content-Type' => 'application/json',
            'Govee-API-Key' => $this->getAPIToken(),
        ];
    }

    /**
     * makeAPICall
     * Makes the API Call
     */
    public function makeAPICall(string $type, string $url, string $body = null): ?ResponseInterface
    {
        $data['headers'] = $this->setHeaders();
        $data['body'] = $body;
        $request = null; // Initialize $request to null

        if ($this->checkPing()) {
            try {
                $request = $this->client->request($type, $url, $data);
            } catch (RequestException $e) {
                if ($e->hasResponse()) {
                    $response = $e->getResponse();

                    if ($response !== null) {
                        Log::error('API Error: ' . $response->getBody());
                    }
                    Log::error($url);

                    if (isset($body)) {
                        Log::error($body);
                    }

                    exit;
                }
            }
        } else {
            return null; // Handle ping failure case
        }

        return $request;
    }

    public function checkPing(): bool
    {
        $response = $this->client->request('GET', self::PING_ENDPOINT);

        if ($response->getStatusCode() == 200) {
            // in future we might want to regex match the word
            // pong (case insensitive) which is what their endpoint
            // currently returns. However, 200 is much safer than
            // looking for a specific word
            //if (preg_match("/pong/i", $response->getBody())) {
            return true;
        }

        die('API Seems Offline or you have connectivity issues at present.');
    }

    public function getDeviceMAC(string $device): string
    {
        if (preg_match('/^([a-fA-F0-9]{2}\:){7}[a-fA-F0-9]{2}$/', $device)) {
            return $device;
        }

        if (in_array($device, $this->mac_hash, true)) {
            return $this->name_hash[$device];
        }

        die('Device Not Found');
    }

    public function getDeviceState(string $device): string
    {
        $mac = $this->getDeviceMAC($device);
        $model = $this->sku_hash[$mac];

        $jsonPayload = $this->createPostPayload($model, $mac);

        $response = $this->makeAPICall('POST', self::DEVICE_STATE, $jsonPayload);

        if ($response === null) {
            return 'API Error';
        }

        return $response->getBody();
    }

    /**
     * createPostPayload
     * Creates the JSON payload for the API
     *
     * @param array<mixed>|null $capability
     */
    public function createPostPayload(string $sku, string $device, array|null $capability = []): string
    {
        $payload = [
            'requestId' => 'uuid',
            'payload' => [
                'sku' => $sku,
                'device' => $device,
            ],
        ];

        if (isset($capability) && !empty($capability)) {
            $payload['payload']['capability'] = $capability;
        }

        if (($json = json_encode($payload)) === false) {
            // Handle the error
            error_log('Failed to encode JSON: ' . json_last_error_msg());

            // Provide a default response or take other actions
            return '';
        }

        // Continue with the JSON-encoded payload
        return $json;
    }

    /**
     * loadAllDevices
     * Called by the constructor. Pre-Loads arrays/hashes to reference
     * lights by either MAC address or name
     */
    private function loadAllDevices(): void
    {
        $all_devices = $this->getDeviceList(1);
        $devices = (is_array($all_devices) && array_key_exists('data', $all_devices)) ? $all_devices['data'] : [];

        foreach ($devices as $device) {
            $name = $device['deviceName'];
            $mac = $device['device'];
            $model = $device['sku'];
            $this->name_hash[$name] = $mac;
            $this->name_array[] = $name;
            $this->mac_hash[$mac] = $name;
            $this->mac_array[] = $mac;
            $this->sku_hash[$mac] = $model;
        }
    }

    public function control(): Control
    {
        return new Control($this);
    }
}
