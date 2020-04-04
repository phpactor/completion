<?php

namespace Phpactor\Completion\Tests\Integration\Extension;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Extension\CompletionExtension;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\ClassToFile\ClassToFileExtension;
use Phpactor\Completion\Extension\CompletionWorseExtension;
use Phpactor\Extension\ComposerAutoloader\ComposerAutoloaderExtension;
use Phpactor\Extension\SourceCodeFilesystem\SourceCodeFilesystemExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class CompletionWorseExtensionTest extends TestCase
{
    public function testBuild()
    {
        $container = PhpactorContainer::fromExtensions([
            CompletionExtension::class,
            FilePathResolverExtension::class,
            ClassToFileExtension::class,
            ComposerAutoloaderExtension::class,
            LoggingExtension::class,
            WorseReflectionExtension::class,
            CompletionWorseExtension::class,
            SourceCodeFilesystemExtension::class
        ], [
            FilePathResolverExtension::PARAM_APPLICATION_ROOT => __DIR__,
        ]);

        $completor = $container->get(CompletionExtension::SERVICE_REGISTRY)->completorForType('php');
        $this->assertInstanceOf(Completor::class, $completor);
        assert($completor instanceof Completor);
        $completor->complete(
            TextDocumentBuilder::create('<?php array')->build(),
            ByteOffset::fromInt(8)
        );
    }
}
