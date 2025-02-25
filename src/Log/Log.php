<?php

declare(strict_types=1);

namespace dutchie027\GoveeApiV2\Log;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

final class Log
{
    /**
     * 'DEBUG'|'INFO'|'NOTICE'|'WARNING'|'ERROR'|'CRITICAL'|'ALERT'|'EMERGENCY'
     */
    private const ALLOWED_LEVELS = [100, 200, 250, 300, 400, 500, 550, 600];

    /**
     * @var Logger
     */
    protected static $instance;

    /**
     * @var bool
     */
    protected static $is_set = false;

    /**
     * Method to return the Monolog instance
     */
    public static function getLogger(): Logger
    {
        if (!self::$is_set) {
            self::configureInstance();
        }

        return self::$instance;
    }

    /**
     * Configure Monolog to use rotating files
     */
    protected static function configureInstance(): void
    {
        $logDir = $_ENV['LOG_DIR'] ?? './';
        $logPrefix = $_ENV['LOG_PREFIX'] ?? 'govee-api-v2';
        $logLevel = $_ENV['LOG_LEVEL'] ?? 200; // Default log level to 200 (INFO)

        if (!file_exists($logDir)) {
            mkdir($logDir, 0700, true);
        }

        $logger = new Logger($logPrefix);
        $logLevel = in_array($logLevel, self::ALLOWED_LEVELS, true) ? $logLevel : 200;
        $logger->pushHandler(new StreamHandler($logDir . DIRECTORY_SEPARATOR . $logPrefix . '.log', $logLevel));

        self::$instance = $logger;
        self::$is_set = true;
    }

    /**
     * Add Debug Message
     *
     * @param string       $message
     * @param array<mixed> $context
     *
     * @example Log::debug("something really interesting happened");
     */
    public static function debug($message, array $context = []): void
    {
        self::getLogger()->debug($message, $context);
    }

    /**
     * Add Info Message
     *
     * @param string       $message
     * @param array<mixed> $context
     *
     * @example Log::info("something really interesting happened");
     */
    public static function info($message, array $context = []): void
    {
        self::getLogger()->info($message, $context);
    }

    /**
     * Add Notice Message
     *
     * @param string       $message
     * @param array<mixed> $context
     *
     * @example Log::notice("something really interesting happened");
     */
    public static function notice($message, array $context = []): void
    {
        self::getLogger()->notice($message, $context);
    }

    /**
     * Add Warning Message
     *
     * @param string       $message
     * @param array<mixed> $context
     *
     * @example Log::warning("something really interesting happened");
     */
    public static function warning($message, array $context = []): void
    {
        self::getLogger()->warning($message, $context);
    }

    /**
     * Add Error Message
     *
     * @param string       $message
     * @param array<mixed> $context
     *
     * @example Log::error("something really interesting happened");
     */
    public static function error($message, array $context = []): void
    {
        self::getLogger()->error($message, $context);
    }

    /**
     * Add Critical Message
     *
     * @param string       $message
     * @param array<mixed> $context
     *
     * @example Log::critical("something really interesting happened");
     */
    public static function critical($message, array $context = []): void
    {
        self::getLogger()->critical($message, $context);
    }

    /**
     * Add Alert Message
     *
     * @param string       $message
     * @param array<mixed> $context
     *
     * @example Log::alert("something really interesting happened");
     */
    public static function alert($message, array $context = []): void
    {
        self::getLogger()->alert($message, $context);
    }

    /**
     * Add Emergency Message
     *
     * @param string       $message
     * @param array<mixed> $context
     *
     * @example Log::emergency("something really interesting happened");
     */
    public static function emergency($message, array $context = []): void
    {
        self::getLogger()->emergency($message, $context);
    }
}
