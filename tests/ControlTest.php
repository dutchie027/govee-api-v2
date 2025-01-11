<?php

declare(strict_types=1);

use dutchie027\GoveeApiV2\Connect;
use dutchie027\GoveeApiV2\Control;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ControlTest extends TestCase
{
    private $control;
    private $connect;

    protected function setUp(): void
    {
        $guzzle = $this->createMock(Guzzle::class);
        $this->connect = $this->createMock(Connect::class);
        $this->control = $this->getMockBuilder(Control::class)
                              ->setConstructorArgs([$this->connect])
                              ->getMock();
    }

    public function testSetColorTemperatureWithinRange()
    {
        $data = [
            'payload' => [
                'capabilities' => [
                    [
                        'type' => 'devices.capabilities.color_setting',
                        'instance' => 'colorTemperatureK',
                        'parameters' => [
                            'range' => [
                                'min' => 2000,
                                'max' => 6500,
                            ],
                        ],
                    ],
                ],
                'sku' => 'test-sku',
                'device' => 'test-device',
            ],
        ];

        $this->control->expects(self::once())
                      ->method('setColorTemperature')
                      ->with($data, 3000)
                      ->willReturn('success');

        $result = $this->control->setColorTemperature($data, 3000);
        self::assertEquals('success', $result);
    }

    public function testSetColorTemperatureOutOfRange()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Color temperature must be between 2000 and 6500.');

        $data = [
            'payload' => [
                'capabilities' => [
                    [
                        'type' => 'devices.capabilities.color_setting',
                        'instance' => 'colorTemperatureK',
                        'parameters' => [
                            'range' => [
                                'min' => 2000,
                                'max' => 6500,
                            ],
                        ],
                    ],
                ],
                'sku' => 'test-sku',
                'device' => 'test-device',
            ],
        ];

        $this->control->setColorTemperature($data, 7000);
    }

    public function testSetColorTemperatureApiError()
    {
        $data = [
            'payload' => [
                'capabilities' => [
                    [
                        'type' => 'devices.capabilities.color_setting',
                        'instance' => 'colorTemperatureK',
                        'parameters' => [
                            'range' => [
                                'min' => 2000,
                                'max' => 6500,
                            ],
                        ],
                    ],
                ],
                'sku' => 'test-sku',
                'device' => 'test-device',
            ],
        ];

        $this->control->method('createPostPayload')
                      ->willReturn(json_encode(['payload' => 'test']));

        $this->control->method('makeAPICall')
                      ->willReturn(null);

        $result = $this->control->setColorTemperature($data, 3000);

        self::assertEquals('API Error', $result);
    }

    public function testSetColorTemperatureAtMinimumBoundary()
    {
        $data = [
            'payload' => [
                'capabilities' => [
                    [
                        'type' => 'devices.capabilities.color_setting',
                        'instance' => 'colorTemperatureK',
                        'parameters' => [
                            'range' => [
                                'min' => 2000,
                                'max' => 6500,
                            ],
                        ],
                    ],
                ],
                'sku' => 'test-sku',
                'device' => 'test-device',
            ],
        ];

        $this->control->method('createPostPayload')
                      ->willReturn(json_encode(['payload' => 'test']));

        $this->control->method('makeAPICall')
                      ->willReturn(new Response(200, [], 'success'));

        $result = $this->control->setColorTemperature($data, 2000);
        self::assertEquals('success', $result);
    }

    public function testSetColorTemperatureAtMaximumBoundary()
    {
        $data = [
            'payload' => [
                'capabilities' => [
                    [
                        'type' => 'devices.capabilities.color_setting',
                        'instance' => 'colorTemperatureK',
                        'parameters' => [
                            'range' => [
                                'min' => 2000,
                                'max' => 6500,
                            ],
                        ],
                    ],
                ],
                'sku' => 'test-sku',
                'device' => 'test-device',
            ],
        ];

        $this->control->method('createPostPayload')
                      ->willReturn(json_encode(['payload' => 'test']));

        $this->control->method('makeAPICall')
                      ->willReturn(new Response(200, [], 'success'));

        $result = $this->control->setColorTemperature($data, 6500);
        self::assertEquals('success', $result);
    }

    public function testSetColorTemperatureWithMalformedData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid device data format');

        $malformedData = [
            'payload' => [
                'capabilities' => [],
            ],
        ];

        $this->control->setColorTemperature($malformedData, 3000);
    }

    public function testSetColorTemperatureWithNegativeValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Color temperature must be between 2000 and 6500.');

        $data = [
            'payload' => [
                'capabilities' => [
                    [
                        'type' => 'devices.capabilities.color_setting',
                        'instance' => 'colorTemperatureK',
                        'parameters' => [
                            'range' => [
                                'min' => 2000,
                                'max' => 6500,
                            ],
                        ],
                    ],
                ],
                'sku' => 'test-sku',
                'device' => 'test-device',
            ],
        ];

        $this->control->setColorTemperature($data, -1000);
    }
}
