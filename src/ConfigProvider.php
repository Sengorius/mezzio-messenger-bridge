<?php

declare(strict_types=1);

namespace MessageBus;

use MessageBus\Factory\DefaultMessageBusFactory;
use MessageBus\Factory\MessageBusCacheFactory;
use MessageBus\Factory\MessageBusException;
use MessageBus\Factory\MessageBusLoggerFactory;
use MessageBus\Factory\TransportHelper;
use MessageBus\Factory\TransportPluginManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection as AmqpConnection;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransportFactory;
use Symfony\Component\Messenger\Bridge\Redis\Transport\Connection as RedisConnection;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransport;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Throwable;

/**
 * The configuration provider for the MessageBus module
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'aliases' => [
                MessageBusInterface::class => DefaultMessageBusFactory::class,
                MessageBus::class => DefaultMessageBusFactory::class,
            ],

            'factories' => [
                TransportPluginManager::class => function (ContainerInterface $container) {
                    $transportDsns = TransportHelper::getTransportDsns($container);
                    $factories = [];

                    if (empty($transportDsns)) {
                        throw new MessageBusException('Missing configuration "transportDSNs"!');
                    }

                    foreach ($transportDsns as $name => $dsn) {
                        $fullName = TransportHelper::createTransportName((string) $name);

                        if (isset($factories[$fullName])) {
                            throw new MessageBusException(sprintf('Transport "%s" was configured twice, wich cannot work as expected!', $name));
                        }

                        $factories[$fullName] = function (ContainerInterface $container) use ($dsn) {
                            $split = explode('://', $dsn);

                            switch (strtolower(trim($split[0]))) {
                                case 'amqp':
                                    if (!class_exists(AmqpTransport::class)) {
                                        throw new MessageBusException('Missing package "symfony/amqp-messenger". Use `composer req symfony/amqp-messenger` to fix this.');
                                    }

                                    return new AmqpTransport(AmqpConnection::fromDsn($dsn));

                                case 'redis':
                                    if (!class_exists(RedisTransport::class)) {
                                        throw new MessageBusException('Missing package "symfony/redis-messenger". Use `composer req symfony/redis-messenger` to fix this.');
                                    }

                                    return new RedisTransport(RedisConnection::fromDsn($dsn));

                                case 'doctrine':
                                    if (!class_exists(DoctrineTransport::class)) {
                                        throw new MessageBusException('Missing package "symfony/doctrine-messenger". Use `composer req symfony/doctrine-messenger` to fix this.');
                                    }

                                    // Doctrine has to be configured in ServiceManager and aliased to ManagerRegistry!
                                    try {
                                        $managerRegistry = $container->get(Doctrine\Persistence\ManagerRegistry::class);
                                        $transportFactory = new DoctrineTransportFactory($managerRegistry);

                                        return $transportFactory->createTransport($dsn, [], new PhpSerializer());
                                    } catch (Throwable $exception) {
                                        throw new MessageBusException('Missing dependency on the Doctrine ManagerRegistry!', 0, $exception);
                                    }

                                default:
                                    throw new MessageBusException(sprintf('Unknown transport DSN "%s"!', $dsn));
                            }
                        };
                    }

                    return new TransportPluginManager($container, [
                        'factories' => $factories,
                    ]);
                },
                'MessageBusLogger' => MessageBusLoggerFactory::class,
                'MessageBusCache' => MessageBusCacheFactory::class,
                DefaultMessageBusFactory::class => DefaultMessageBusFactory::class,
            ],
        ];
    }
}
