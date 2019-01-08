<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Phpactor\Completion\Core\Exception\CouldNotHelpWithSignature;
use Phpactor\Completion\Core\SignatureHelp;
use Phpactor\Completion\Core\SignatureHelper;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;

class WorseSignatureHelper implements SignatureHelper
{
    /**
     * @var SourceCodeReflector
     */
    private $reflector;

    public function __construct(SourceCodeReflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function signatureHelp(
        TextDocument $textDocument,
        ByteOffset $offset
    ): SignatureHelp
    {
        throw new CouldNotHelpWithSignature();
    }
}
