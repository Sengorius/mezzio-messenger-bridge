<?php

namespace MessageBus\Factory;

use Laminas\ServiceManager\AbstractPluginManager;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function is_callable;

/**
 * Class TransportPluginManager
 * @see https://docs.laminas.dev/laminas-servicemanager/plugin-managers/
 */
class TransportPluginManager extends AbstractPluginManager implements ServiceProviderInterface
{
    protected $instanceOf = TransportInterface::class;
    private ?array $providedTypes = null;


    /**
     * @return array|string[]
     *
     * @throws ReflectionException
     */
    public function getProvidedServices(): array
    {
        if (null === $this->providedTypes) {
            $this->providedTypes = [];

            foreach ($this->factories as $name => $factory) {
                if (!is_callable($factory)) {
                    $this->providedTypes[$name] = '?';
                } else {
                    $type = (new ReflectionFunction($factory))->getReturnType();
                    $this->providedTypes[$name] = $type ? ($type->allowsNull() ? '?' : '').($type instanceof ReflectionNamedType ? $type->getName() : $type) : '?';
                }
            }
        }

        return $this->providedTypes;
    }
}
