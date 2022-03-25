<?php

namespace MessageBus\Factory;

use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Class TransportHelper
 */
class TransportHelper
{
    /**
     * Creates a general modifier for transports
     *
     * @param string $baseName
     *
     * @return string
     */
    public static function createTransportName(string $baseName): string
    {
        return 'Transport::'.mb_strtolower($baseName, 'UTF-8');
    }

    /**
     * Gets the Failure Transport from configuration or null
     *
     * @param ContainerInterface $container
     *
     * @return string|null
     */
    public static function getFailureTransport(ContainerInterface $container): ?string
    {
        try {
            $config = $container->get('config') ?? [];
            $messageBusConfig = $config['messageBus'] ?? [];

            return $messageBusConfig['failureTransport'] ?? null;
        } catch (Throwable $exception) {
            return null;
        }
    }

    /**
     * Gets the transport DSNs from configuration or empty array
     *
     * @param ContainerInterface $container
     *
     * @return array
     */
    public static function getTransportDsns(ContainerInterface $container): array
    {
        try {
            $config = $container->get('config') ?? [];
            $messageBusConfig = $config['messageBus'] ?? [];

            return $messageBusConfig['transportDSNs'] ?? [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    /**
     * Gets the handlers locators map from configuration or empty array
     *
     * @param ContainerInterface $container
     *
     * @return array
     */
    public static function getHandlersLocators(ContainerInterface $container): array
    {
        try {
            $config = $container->get('config') ?? [];
            $messageBusConfig = $config['messageBus'] ?? [];

            return array_map(
                function ($stringOrArray) {
                    if (is_string($stringOrArray)) {
                        $stringOrArray = [$stringOrArray];
                    }

                    return $stringOrArray;
                },
                $messageBusConfig['handlersLocatorMap'] ?? []
            );
        } catch (Throwable $exception) {
            return [];
        }
    }

    /**
     * Gets the senders locators map from configuration or empty array
     *
     * @param ContainerInterface $container
     *
     * @return array
     */
    public static function getSendersLocators(ContainerInterface $container): array
    {
        try {
            $config = $container->get('config') ?? [];
            $messageBusConfig = $config['messageBus'] ?? [];

            return array_map(
                function ($stringOrArray) {
                    if (is_string($stringOrArray)) {
                        $stringOrArray = [$stringOrArray];
                    }

                    return $stringOrArray;
                },
                $messageBusConfig['sendersLocatorMap'] ?? []
            );
        } catch (Throwable $exception) {
            return [];
        }
    }
}
