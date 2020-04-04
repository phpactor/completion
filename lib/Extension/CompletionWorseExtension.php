<?php

namespace Phpactor\Completion\Extension;

use Phpactor\Completion\Bridge\TolerantParser\LimitingCompletor;
use Phpactor\Completion\Bridge\TolerantParser\SourceCodeFilesystem\ScfClassCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseClassAliasCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseConstantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseConstructorCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseDeclaredClassCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseSignatureHelper;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\ClassFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\FunctionFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\MethodFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\ParametersFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\VariableFormatter;
use Phpactor\Completion\Bridge\TolerantParser\ChainTolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseClassMemberCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseFunctionCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseParameterCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseLocalVariableCompletor;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\ParameterFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\PropertyFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\TypeFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\TypesFormatter;
use Phpactor\Container\Extension;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Extension\SourceCodeFilesystem\SourceCodeFilesystemExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\MapResolver\Resolver;
use Phpactor\Container\Container;

class CompletionWorseExtension implements Extension
{
    const PARAM_CLASS_COMPLETOR_LIMIT = 'completion_worse.completor.class.limit';
    const TAG_TOLERANT_COMPLETOR = 'completion_worse.tolerant_completor';

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $this->registerCompletion($container);
        $this->registerSignatureHelper($container);
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_CLASS_COMPLETOR_LIMIT => 100,
        ]);
    }

    private function registerCompletion(ContainerBuilder $container)
    {
        $container->register('completion_worse.completor.tolerant.chain', function (Container $container) {
            $completors = [];
            foreach (array_keys($container->getServiceIdsForTag(self::TAG_TOLERANT_COMPLETOR)) as $serviceId) {
                $completors[] = $container->get($serviceId);
            }

            return new ChainTolerantCompletor(
                $completors,
                $container->get('worse_reflection.tolerant_parser')
            );
        }, [ CompletionExtension::TAG_COMPLETOR => []]);

        $container->register('completion_worse.completor.parameter', function (Container $container) {
            return new WorseParameterCompletor(
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
                $container->get(CompletionExtension::SERVICE_FORMATTER)
            );
        }, [ self::TAG_TOLERANT_COMPLETOR => []]);

        $container->register('completion_worse.completor.constructor', function (Container $container) {
            return new WorseConstructorCompletor(
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
                $container->get(CompletionExtension::SERVICE_FORMATTER)
            );
        }, [ self::TAG_TOLERANT_COMPLETOR => []]);
        
        $container->register('completion_worse.completor.tolerant.class_member', function (Container $container) {
            return new WorseClassMemberCompletor(
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
                $container->get(CompletionExtension::SERVICE_FORMATTER)
            );
        }, [ self::TAG_TOLERANT_COMPLETOR => []]);

        $container->register('completion_worse.completor.tolerant.class', function (Container $container) {
            return new LimitingCompletor(new ScfClassCompletor(
                $container->get(SourceCodeFilesystemExtension::SERVICE_REGISTRY)->get('composer'),
                $container->get('class_to_file.file_to_class')
            ), $container->getParameter(self::PARAM_CLASS_COMPLETOR_LIMIT));
        }, [ self::TAG_TOLERANT_COMPLETOR => []]);

        $container->register('completion_worse.completor.local_variable', function (Container $container) {
            return new WorseLocalVariableCompletor(
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
                $container->get(CompletionExtension::SERVICE_FORMATTER)
            );
        }, [ self::TAG_TOLERANT_COMPLETOR => []]);

        $container->register('completion_worse.completor.function', function (Container $container) {
            return new WorseFunctionCompletor(
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
                $container->get(CompletionExtension::SERVICE_FORMATTER)
            );
        }, [ self::TAG_TOLERANT_COMPLETOR => []]);

        $container->register('completion_worse.completor.constant', function (Container $container) {
            return new WorseConstantCompletor();
        }, [ self::TAG_TOLERANT_COMPLETOR => []]);

        $container->register('completion_worse.completor.class_alias', function (Container $container) {
            return new WorseClassAliasCompletor(
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR)
            );
        }, [ self::TAG_TOLERANT_COMPLETOR => []]);

        $container->register('completion_worse.completor.class_alias', function (Container $container) {
            return new WorseDeclaredClassCompletor(
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
                $container->get(CompletionExtension::SERVICE_FORMATTER)
            );
        }, [ self::TAG_TOLERANT_COMPLETOR => []]);

        $container->register('completion_worse.formatters', function (Container $container) {
            return [
                new TypeFormatter(),
                new TypesFormatter(),
                new MethodFormatter(),
                new ParameterFormatter(),
                new ParametersFormatter(),
                new ClassFormatter(),
                new PropertyFormatter(),
                new FunctionFormatter(),
                new VariableFormatter(),
            ];
        }, [ CompletionExtension::TAG_FORMATTER => []]);
    }

    private function registerSignatureHelper(ContainerBuilder $container)
    {
        $container->register('completion_worse.signature_helper', function (Container $container) {
            return new WorseSignatureHelper(
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
                $container->get(CompletionExtension::SERVICE_FORMATTER)
            );
        }, [ CompletionExtension::TAG_SIGNATURE_HELPER => []]);
    }
}
