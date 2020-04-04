<?php

namespace Phpactor\Completion\Extension;

use Phpactor\Completion\Core\ChainSignatureHelper;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Core\TypedCompletor;
use Phpactor\Completion\Core\TypedCompletorRegistry;
use Phpactor\Container\Extension;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\MapResolver\Resolver;
use Phpactor\Container\Container;

class CompletionExtension implements Extension
{
    public const TAG_COMPLETOR = 'completion.completor';
    public const TAG_FORMATTER = 'completion.formatter';
    public const SERVICE_FORMATTER = 'completion.formatter';
    public const SERVICE_REGISTRY = 'completion.registry';
    public const KEY_COMPLETOR_TYPES = 'types';
    public const SERVICE_SIGNATURE_HELPER = 'completion.handler.signature_helper';
    public const TAG_SIGNATURE_HELPER = 'language_server_completion.handler.signature_help';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $this->registerCompletion($container);
    }

    private function registerCompletion(ContainerBuilder $container)
    {
        $container->register(self::SERVICE_REGISTRY, function (Container $container) {
            $completors = [];
            foreach ($container->getServiceIdsForTag(self::TAG_COMPLETOR) as $serviceId => $attrs) {
                $completors[] = new TypedCompletor($container->get($serviceId), $attrs[self::KEY_COMPLETOR_TYPES] ?? ['php']);
            }

            return new TypedCompletorRegistry($completors);
        });

        $container->register(self::SERVICE_FORMATTER, function (Container $container) {
            $formatters = [];
            foreach (array_keys($container->getServiceIdsForTag(self::TAG_FORMATTER)) as $serviceId) {
                $taggedFormatters = $container->get($serviceId);
                $taggedFormatters = is_array($taggedFormatters) ? $taggedFormatters : [ $taggedFormatters ];

                foreach ($taggedFormatters as $taggedFormatter) {
                    $formatters[] = $taggedFormatter;
                }
            }

            return new ObjectFormatter($formatters);
        });

        $container->register(self::SERVICE_SIGNATURE_HELPER, function (Container $container) {
            $helpers = [];

            $helper = null;
            foreach (array_keys($container->getServiceIdsForTag(self::TAG_SIGNATURE_HELPER)) as $serviceId) {
                $helpers[] = $container->get($serviceId);
            }

            return new ChainSignatureHelper(
                $container->get(LoggingExtension::SERVICE_LOGGER),
                $helpers
            );
        });
    }
}
