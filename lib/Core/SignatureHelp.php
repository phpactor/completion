<?php

namespace Phpactor\Completion\Core;

final class SignatureHelp
{
    /**
     * @var SignatureInformation[]
     */
    private $signatures;

    /**
     * @var int
     */
    private $activeSignature;

    /**
     * @var int
     */
    private $activeParameter;

    public function __construct(
        array $signatures = [],
        $activeSignature,
        int $activeParameter
    )
    {
        $this->signatures = $signatures;
        $this->activeSignature = $activeSignature;
        $this->activeParameter = $activeParameter;
    }

    public function activeParameter(): int
    {
        return $this->activeParameter;
    }

    public function activeSignature(): int
    {
        return $this->activeSignature;
    }

    public function signatures(): array
    {
        return $this->signatures;
    }
}
