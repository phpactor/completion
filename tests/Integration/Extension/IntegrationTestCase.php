<?php

namespace Phpactor\Completion\Tests\Integration\Extension;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Extension\CompletionExtension;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Completion\Extension\LanguageServerCompletionExtension;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\LanguageServer\Test\ServerTester;

class IntegrationTestCase extends TestCase
{
    protected function createTester(): ServerTester
    {
        $container = PhpactorContainer::fromExtensions([
            LoggingExtension::class,
            CompletionExtension::class,
            LanguageServerExtension::class,
            LanguageServerCompletionExtension::class,
            FilePathResolverExtension::class,
        ]);
        
        $builder = $container->get(LanguageServerExtension::SERVICE_LANGUAGE_SERVER_BUILDER);
        $this->assertInstanceOf(LanguageServerBuilder::class, $builder);

        return $builder->buildServerTester();
    }
}
