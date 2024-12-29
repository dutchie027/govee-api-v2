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
    public const PING_ENDPOINT = '/ping';

    /**
     * RAW Device Endpoint
     *
     * @const string
     */
    public const DEVICE_ENDPOINT = '/router/api/v1/user/devices';

    /**
     * Control Endpoint
     *
     * @const string
     */
    public const DEVICE_CONTROL = self::API_URL . self::DEVICE_ENDPOINT . '/control';

    /**
     * Device State Endpoint
     *
     * @const string
     */
    public const DEVICE_STATE = self::API_URL . self::DEVICE_ENDPOINT . '/state';

    /**
     * API Token
     *
     * @var string
     */
    private $p_token;

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
    public function __construct(Guzzle $client = null)
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
        $this->p_token = $_ENV['API_TOKEN'];
        $this->client = $client ?: new Guzzle();
    }

    /**
     * getDeviceList
     * Returns Full Device List
     *
     * @return array<mixed>|string
     */
    public function getDeviceList(int $array = 0): string|array
    {
        $url = self::API_URL . self::DEVICE_ENDPOINT;
        $response = $this->makeAPICall('GET', $url);
        if ($response === null) {
            return 'API Error';
        } else {
            return $array === 1 ? json_decode($response->getBody(), true) : $response->getBody();    
        }
    }

    /**
     * getDeviceCount
     * Returns total number of controllable devices
     */
    public function getDeviceCount(): int
    {
        return $data = isset($this->getDeviceList(1)['data']) ?
        count($this->getDeviceList(1)['data']) :
        0;
    }

    /**
     * getDeviceMACArray
     * Returns array of controllable MAC addresses
     *
     * @return array<string>
     */
    public function getDeviceMACArray(): array
    {
        $list = $this->getDeviceList(1);
        $dev = [];
    
        // Check if 'data' key exists and is an array
        if (is_array($list) && array_key_exists('data', $list)) {
            $array = $list['data'];
            
            foreach ($array as $devices) {
                if (isset($devices['device'])) {
                    $dev[] = $devices['device'];
                }
            }
        }
    
        return $dev;
    }
    

    /**
     * getDeviceNameArray
     * Returns Array of Device Names
     *
     * @return array<string>
     */
    public function getDeviceNameArray(): array
    {
        $list = $this->getDeviceList(1);
        $dev = [];
    
        // Check if 'data' key exists and is an array
        if (is_array($list) && array_key_exists('data', $list)) {
            $array = $list['data'];
            
            foreach ($array as $devices) {
                if (isset($devices['deviceName'])) {
                    $dev[] = $devices['deviceName'];
                }
            }
        }
    
        return $dev;
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
        $url = self::API_URL . self::PING_ENDPOINT;
        $response = $this->client->request('GET', $url);

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
}
