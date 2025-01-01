<?php

namespace Tests\dutchie027\GoveeApiV2;

use dutchie027\GoveeApiV2\Connect;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ConnectTest extends TestCase
{
    private Connect $connect;
    private MockHandler $mockHandler;
    private const TEST_API_KEY = 'mock-api-key-12345';

    protected function setUp(): void
    {
        // Mock environment variables
        putenv('GOVEE_API_KEY=' . self::TEST_API_KEY);
        putenv('LOG_DIR=/tmp/govee-tests');
        putenv('LOG_PREFIX=govee_test');
        putenv('LOG_LEVEL=200');

        // Set up mock HTTP client
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        // Create Connect instance and set properties
        $this->connect = new Connect();

        // Mock the client before any API calls
        $reflection = new \ReflectionClass($this->connect);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->connect, $client);

        // Set the token property
        $tokenProperty = $reflection->getProperty('p_token');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($this->connect, self::TEST_API_KEY);
    }

    protected function tearDown(): void
    {
        putenv('GOVEE_API_KEY');
        putenv('LOG_DIR');
        putenv('LOG_PREFIX');
        putenv('LOG_LEVEL');
    }

    public function testSuccessfulGetRequest(): void
    {
        // Mock ping check response
        $this->mockHandler->append(
            new Response(200, [], json_encode(['data' => 'pong']))
        );

        // Mock actual API response
        $expectedResponse = ['status' => 'success'];
        $this->mockHandler->append(
            new Response(200, [], json_encode($expectedResponse))
        );

        $response = $this->connect->makeAPICall('GET', '/test-endpoint');

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(json_encode($expectedResponse), $response->getBody()->getContents());
    }

    public function testFailedRequest(): void
    {
        // Mock ping check response
        $this->mockHandler->append(
            new Response(200, [], json_encode(['data' => 'pong']))
        );

        // Mock failed request with proper exception
        $this->mockHandler->append(
            new RequestException(
                'Server Error',
                new Request('GET', '/test-endpoint')
            )
        );

        $response = $this->connect->makeAPICall('GET', '/test-endpoint');
        self::assertNull($response);
    }

    public function testPostRequestWithPayload(): void
    {
        // Mock ping check response
        $this->mockHandler->append(
            new Response(200, [], json_encode(['data' => 'pong']))
        );

        // Mock post response
        $expectedResponse = ['status' => 'created'];
        $this->mockHandler->append(
            new Response(201, [], json_encode($expectedResponse))
        );

        $payload = json_encode(['data' => 'test']);
        $response = $this->connect->makeAPICall('POST', '/test-endpoint', $payload);

        self::assertEquals(201, $response->getStatusCode());
        self::assertEquals(json_encode($expectedResponse), $response->getBody()->getContents());
    }

    public function testRateLimitHeaders(): void
    {
        // Mock ping check response
        $this->mockHandler->append(
            new Response(200, [], json_encode(['data' => 'pong']))
        );

        // Mock response with rate limit headers
        $this->mockHandler->append(
            new Response(200, [
                'X-RateLimit-Remaining' => '98',
                'X-RateLimit-Total' => '100',
                'X-RateLimit-Reset' => '60',
            ], '{"status":"success"}')
        );

        $response = $this->connect->makeAPICall('GET', '/test-endpoint');

        self::assertEquals('98', $response->getHeader('X-RateLimit-Remaining')[0]);
        self::assertEquals('100', $response->getHeader('X-RateLimit-Total')[0]);
        self::assertEquals('60', $response->getHeader('X-RateLimit-Reset')[0]);
    }

    public function testCreatePostPayload(): void
    {
        $method = new \ReflectionMethod(Connect::class, 'createPostPayload');
        $method->setAccessible(true);

        $result = $method->invoke($this->connect, 'TEST-SKU', 'TEST-DEVICE');
        $decoded = json_decode($result, true);

        self::assertIsArray($decoded);
        self::assertArrayHasKey('requestId', $decoded);
        self::assertArrayHasKey('payload', $decoded);
        self::assertEquals('TEST-SKU', $decoded['payload']['sku']);
        self::assertEquals('TEST-DEVICE', $decoded['payload']['device']);
    }
}
