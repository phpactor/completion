<?php

namespace Phpactor\Completion\Extension\LanguageServer;

use Generator;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\SignatureHelp;
use LanguageServerProtocol\SignatureHelpOptions;
use LanguageServerProtocol\TextDocumentIdentifier;
use Phpactor\Completion\Core\Exception\CouldNotHelpWithSignature;
use Phpactor\Completion\Core\SignatureHelper;
use Phpactor\Completion\Extension\Util\PhpactorToLspSignature;
use Phpactor\LanguageServer\Core\Handler\CanRegisterCapabilities;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class SignatureHelpHandler implements Handler, CanRegisterCapabilities
{
    /**
     * @var Workspace
     */
    private $workspace;

    /**
     * @var SignatureHelper
     */
    private $helper;

    public function __construct(Workspace $workspace, SignatureHelper $helper)
    {
        $this->workspace = $workspace;
        $this->helper = $helper;
    }

    /**
     * {@inheritDoc}
     */
    public function methods(): array
    {
        return [
            'textDocument/signatureHelp' => 'signatureHelp'
        ];
    }

    public function signatureHelp(
        TextDocumentIdentifier $textDocument,
        Position $position
    ): Generator {
        $textDocument = $this->workspace->get($textDocument->uri);

        $languageId = $textDocument->languageId ?: 'php';

        try {
            yield PhpactorToLspSignature::toLspSignatureHelp($this->helper->signatureHelp(
                TextDocumentBuilder::create($textDocument->text)->language($languageId)->uri($textDocument->uri)->build(),
                ByteOffset::fromInt($position->toOffset($textDocument->text))
            ));
        } catch (CouldNotHelpWithSignature $couldNotHelp) {
            yield new SignatureHelp();
        }
    }

    public function registerCapabiltiies(ServerCapabilities $capabilities): void
    {
        $options = new SignatureHelpOptions();
        $options->triggerCharacters = [ '(', ',' ];
        $capabilities->signatureHelpProvider = $options;
    }
}
