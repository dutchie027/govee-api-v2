<?php

namespace dutchie027\GoveeApiV2;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\RequestException;
use Dotenv\Dotenv;
use dutchie027\GoveeApiV2\Log\Log;

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
     * setRateVars
     * Takes a header array and sets the rate variables
     *
     * @param array $header
     *
     * @return void
     */
    public function setRateVars(array $header): void
    {
        // print_r($header);
        // $this->rate_remain = $header['X-RateLimit-Remaining'];
        // $this->rate_reset = $header['X-RateLimit-Reset'];
        // $this->rate_total = $header['X-RateLimit-Limit'];
        // Log::debug("Rate Remaining: " . $this->rate_remain);
        // Log::debug("Rate Reset: " . $this->rate_reset);
        // Log::debug("Rate Total: " . $this->rate_total);
    }

    /**
     * getDeviceList
     * Returns Full Device List
     */
    public function getDeviceList(int $array=0) : string|array
    {
        $url = self::API_URL . self::DEVICE_ENDPOINT;
        $response = $this->makeAPICall('GET', $url);
        return $array === 1 ? json_decode($response->getBody(), true) : $response->getBody();
    }

    /**
     * getDeviceCount
     * Returns total number of controllable devices
     *
     * @return int
     */
    public function getDeviceCount(): int
    {
        return count($this->getDeviceList(1)['data']);
    }

    /**
     * getDeviceMACArray
     * Returns array of controllable MAC addresses
     */
    public function getDeviceMACArray() : array
    {
        $array = $this->getDeviceList(1)['data'];

        foreach ($array as $devices) {
            $dev[] = $devices['device'];
        }

        return $dev;
    }

    /**
     * getDeviceNameArray
     * Returns Array of Device Names
     *
     * @return array
     */
    public function getDeviceNameArray()
    {
        $array = $this->getDeviceList()['data'];

        foreach ($array as $devices) {
            $dev[] = $devices['deviceName'];
        }

        return $dev;
    }

    /**
     * getAPIToken
     * Returns the stored API Token
     *
     * @return string
     */
    protected function getAPIToken(): string
    {
        return $this->p_token;
    }

    /**
     * setHeaders
     * Sets the headers using the API Token
     *
     * @return array
     */
    public function setHeaders()
    {
        return [
            'User-Agent' => 'testing/1.0',
            'Content-Type' => 'application/json',
            'Govee-API-Key' => $this->getAPIToken(),
        ];
    }

    public function makeAPICall($type, $url, $body = null)
    {
        $data['headers'] = $this->setHeaders();
        $data['body'] = $body;

        if ($this->checkPing()) {
            try {
                $request = $this->client->request($type, $url, $data);
            } catch (RequestException $e) {
                if ($e->hasResponse()) {
                    $response = $e->getResponse();
                    print $response->getBody();

                    exit;
                }
            }
        }
        $this->setRateVars($request->getHeaders());

        return $request;
    }

    public function checkPing()
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