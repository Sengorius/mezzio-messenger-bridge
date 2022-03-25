<?php

namespace MessageBus\Factory;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Class MessageBusLoggerFactory
 */
class MessageBusLoggerFactory
{
    /**
     * @param ContainerInterface $container
     *
     * @return LoggerInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        $config = $container->get('config') ?? [];
        $logPath = $config['messageBus']['logPath'] ?? null;

        if (empty($logPath)) {
            throw new MessageBusException('The "logPath" variable is not defined. Please specify where to store the logs!');
        }

        $logFilePath = rtrim($logPath, '/ ').'/mb.log';
        $rotatingHandler = new RotatingFileHandler($logFilePath, 30, Logger::DEBUG);
        $logMsgProcessor = new PsrLogMessageProcessor();

        return new Logger('MessageBus', [$rotatingHandler], [$logMsgProcessor]);
    }
}
