<?php

namespace MessageBus\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\AddBusNameStampMiddleware;
use Symfony\Component\Messenger\Middleware\DispatchAfterCurrentBusMiddleware;
use Symfony\Component\Messenger\Middleware\FailedMessageProcessingMiddleware;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\RejectRedeliveredMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;

/**
 * Class DefaultMessageBusFactory
 */
final class DefaultMessageBusFactory
{
    public const BUSNAME = 'DefaultMessageBus';


    /**
     * @param ContainerInterface $container
     *
     * @return MessageBusInterface
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): MessageBusInterface
    {
        $logger = $container->get('MessageBusLogger');

        $middlewares = [
            new AddBusNameStampMiddleware(self::BUSNAME),
            new RejectRedeliveredMessageMiddleware(),
            new DispatchAfterCurrentBusMiddleware(),
            new FailedMessageProcessingMiddleware(),
        ];

        // add custom configured middlewares
        $customMiddlewares = TransportHelper::getCustomMiddlewares($container);
        $middlewares = array_merge($middlewares, $customMiddlewares);

        // add sender and handlers at the end
        $sendMessageMiddleware = new SendMessageMiddleware($this->generateSendersLocator($container));
        $sendMessageMiddleware->setLogger($logger);
        $middlewares[] = $sendMessageMiddleware;

        $handleMessageMiddleware = new HandleMessageMiddleware($this->generateHandlersLocator($container));
        $handleMessageMiddleware->setLogger($logger);
        $middlewares[] = $handleMessageMiddleware;

        return new MessageBus($middlewares);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return HandlersLocatorInterface
     */
    private function generateHandlersLocator(ContainerInterface $container): HandlersLocatorInterface
    {
        $handlersLocatorMap = array_map(
            function (array $locators) use ($container) {
                return array_map(
                    function (string $className) use ($container) {
                        return new HandlerDescriptor(new $className($container));
                    },
                    $locators
                );
            },
            TransportHelper::getHandlersLocators($container)
        );

        return new HandlersLocator($handlersLocatorMap);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return SendersLocatorInterface
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    private function generateSendersLocator(ContainerInterface $container): SendersLocatorInterface
    {
        $transportPluginManager = $container->get(TransportPluginManager::class);
        $sendersLocatorMap = TransportHelper::getSendersLocators($container);

        return new SendersLocator($sendersLocatorMap, $transportPluginManager);
    }
}
