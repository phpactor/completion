<?php

namespace Phpactor\Completion\Tests\Integration\Extension;

use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Formatter\Formatter;
use Phpactor\Completion\Core\SignatureHelp;
use Phpactor\Completion\Core\SignatureHelper;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Extension\CompletionExtension;
use Phpactor\Completion\Tests\TestCase;
use Phpactor\Container\Container;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;
use Prophecy\Prophecy\ObjectProphecy;
use stdClass;

class CompletionExtensionTest extends TestCase
{
    const EXAMPLE_SUGGESTION = 'example_suggestion';
    const EXAMPLE_SOURCE = 'asd';
    const EXAMPLE_OFFSET = 1234;


    /**
     * @var ObjectProphecy
     */
    private $completor1;

    /**
     * @var ObjectProphecy
     */
    private $formatter1;

    /**
     * @var ObjectProphecy
     */
    private $signatureHelper1;

    protected function setUp(): void
    {
        $this->completor1 = $this->prophesize(Completor::class);
        $this->signatureHelper1 = $this->prophesize(SignatureHelper::class);
        $this->formatter1 = $this->prophesize(Formatter::class);
    }

    public function testCreatesChainedCompletor()
    {
        $document = TextDocumentBuilder::create(self::EXAMPLE_SOURCE)->build();
        $this->completor1->complete(
            $document,
            ByteOffset::fromInt(self::EXAMPLE_OFFSET)
        )->will(function () {
            return (function () {
                yield Suggestion::create(self::EXAMPLE_SUGGESTION);
            })();
        });

        $completor = $this->createContainer()->get(CompletionExtension::SERVICE_REGISTRY)->completorForType('php');
        $results = iterator_to_array($completor->complete(
            $document,
            ByteOffset::fromInt(self::EXAMPLE_OFFSET)
        ));

        $this->assertEquals(self::EXAMPLE_SUGGESTION, $results[0]->name());
    }

    public function testCreatesFormatterFromEitherSingleFormatterOrArray()
    {
        $object = new stdClass();
        $this->formatter1->canFormat($object)->shouldBeCalledTimes(3)->willReturn(false);

        $formatter = $this->createContainer()->get(CompletionExtension::SERVICE_FORMATTER);
        $canFormat = $formatter->canFormat($object);
        $this->assertEquals(false, $canFormat);
    }

    public function testCreatesSignatureHelper()
    {
        $document = TextDocumentBuilder::create(self::EXAMPLE_SOURCE)->build();
        $this->signatureHelper1->signatureHelp(
            $document,
            ByteOffset::fromInt(self::EXAMPLE_OFFSET)
        )->will(function () {
            return (function () {
                return new SignatureHelp([], 0);
            })();
        });

        $signatureHelper = $this->createContainer()->get(CompletionExtension::SERVICE_SIGNATURE_HELPER);
        $help = $signatureHelper->signatureHelp(
            $document,
            ByteOffset::fromInt(self::EXAMPLE_OFFSET)
        );

        $this->assertInstanceOf(SignatureHelp::class, $help);
    }

    private function createContainer(): Container
    {
        $builder = new PhpactorContainer();
        $extension = new CompletionExtension();

        $builder->register('completor1', function () {
            return $this->completor1->reveal();
        }, [ CompletionExtension::TAG_COMPLETOR => []]);

        $builder->register('formatter', function () {
            return $this->formatter1->reveal();
        }, [ CompletionExtension::TAG_FORMATTER => []]);

        $builder->register('signarure_helper', function () {
            return $this->signatureHelper1->reveal();
        }, [ CompletionExtension::TAG_SIGNATURE_HELPER => []]);

        $builder->register('formatter_array', function () {
            return [
                $this->formatter1->reveal(),
                $this->formatter1->reveal(),
            ];
        }, [ CompletionExtension::TAG_FORMATTER => []]);
        
        $extension->load($builder);

        $extension = new LoggingExtension();
        $extension->load($builder);
        return $builder->build([
            'logging.enabled' => false,
        ]);
    }
}
