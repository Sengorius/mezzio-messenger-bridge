<?php

namespace MessageBus\Factory;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Class MessageBusCacheFactory
 */
class MessageBusCacheFactory
{
    /**
     * @param ContainerInterface $container
     *
     * @return CacheItemPoolInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): CacheItemPoolInterface
    {
        $config = $container->get('config') ?? [];
        $cachePath = $config['messageBus']['cachePath'] ?? null;

        if (empty($cachePath)) {
            throw new MessageBusException('The "cachePath" variable is not defined. Please specify where to store the cache!');
        }

        return new FilesystemAdapter('message-bus', 0, $cachePath);
    }
}
