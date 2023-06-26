<?php

/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

declare(strict_types=1);

namespace Oeuvres\Kit;

use \DateTime;
use Psr\Log\{AbstractLogger, InvalidArgumentException, LogLevel, LoggerInterface, NullLogger};

/**
 * A static class to access loggers accross application.
 *
 * @see https://www.php-fig.org/psr/psr-3/
 */
class Log
{
    /** name of default logger */
    const MAIN = "main";
    /** different channels to log in */
    static private $loggers = [];
    /** last message */
    static private $last = [];

    /**
     * System is unusable.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    static public function emergency($message, array $context = [], ?string $channel = self::MAIN): void
    {
        self::log(LogLevel::EMERGENCY, $message, $context, $channel);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    static public function alert($message, array $context = [], ?string $channel = self::MAIN): void
    {
        self::log(LogLevel::ALERT, $message, $context, $channel);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    static public function critical($message, array $context = [], ?string $channel = self::MAIN): void
    {
        self::log(LogLevel::CRITICAL, $message, $context, $channel);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    static public function error($message, array $context = [], ?string $channel = self::MAIN): void
    {
        self::log(LogLevel::ERROR, $message, $context, $channel);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    static public function warning($message, array $context = [], ?string $channel = self::MAIN): void
    {
        self::log(LogLevel::WARNING, $message, $context, $channel);
    }

    /**
     * Normal but significant events.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    static public function notice($message, array $context = [], ?string $channel = self::MAIN): void
    {
        self::log(LogLevel::NOTICE, $message, $context, $channel);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    static public function info($message, array $context = [], ?string $channel = self::MAIN): void
    {
        self::log(LogLevel::INFO, $message, $context, $channel);
    }

    /**
     * Detailed debug information.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    static public function debug($message, array $context = [], ?string $channel = self::MAIN): void
    {
        self::log(LogLevel::DEBUG, $message, $context, $channel);
    }

    /**
     * Generic logging to default logger
     * 
     * @param string  $level
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    static public function log(string $level, $message, array $context = [], ?string $channel = self::MAIN): void
    {
        // log error of logging ?
        if (!isset(self::$loggers[$channel])) $channel = self::MAIN;
        // always store last messsage, even if log is not outputed
        // it allows to communicate error messages from libs
        // mimics php error_get_last()
        self::$last[$channel] = $message;
        self::$loggers[$channel]->log($level, $message, $context);
    }

    /**
     * Return a Psr/3 logger
     */
    static public function channel(string $channel): LoggerInterface
    {
        if (!isset(self::$loggers[$channel])) return false;
        return self::$loggers[$channel];
    }

    /**
     * return last error message
     */
    static public function last(): string
    {
        return self::$last[self::MAIN];
    }

    /**
     * Initialize static fields
     */
    public static function init()
    {
        // ensure timezone ()
        if (ini_get('date.timezone')) {
            date_default_timezone_set(ini_get('date.timezone'));
        } else {
            date_default_timezone_set("Europe/Paris");
        }
        // ensure static logging
        self::$loggers[self::MAIN] = new NullLogger();
    }


    public static function setLogger(LoggerInterface $logger, ?string $channel = self::MAIN): void
    {
        self::$loggers[$channel] = $logger;
    }

    /**
     * A function ready to handle PHP error stream
     */
    public static function error_handler($errno, $errstr, $errfile, $errline)
    {
        // error was suppressed with the @-operator
        if (0 === error_reporting()) {
            return false;
        }
        switch ($errno) {
            case E_USER_ERROR:
            case E_ERROR:
                return Log::error($errstr);
                break;
            case E_USER_WARNING:
            case E_WARNING:
                return Log::warning($errstr);
                break;
            case E_USER_NOTICE:
            case E_NOTICE:
                return Log::notice($errstr);
                break;
        };
    }
}
Log::init();
