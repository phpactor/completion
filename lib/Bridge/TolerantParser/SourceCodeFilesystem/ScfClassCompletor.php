<?php

namespace Phpactor\Completion\Bridge\TolerantParser\SourceCodeFilesystem;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\ClassBaseClause;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;
use Microsoft\PhpParser\Node\NamespaceUseClause;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\Statement\NamespaceUseDeclaration;
use Microsoft\PhpParser\ResolvedName;
use Phpactor\ClassFileConverter\Domain\FilePath;
use Phpactor\ClassFileConverter\Domain\FileToClass;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\Filesystem\Domain\FilePath as ScfFilePath;
use Phpactor\Filesystem\Domain\Filesystem;
use SplFileInfo;

class ScfClassCompletor implements TolerantCompletor
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var FileToClass
     */
    private $fileToClass;

    /**
     * @var int
     */
    private $limit;

    public function __construct(Filesystem $filesystem, FileToClass $fileToClass, int $limit = 100)
    {
        $this->filesystem = $filesystem;
        $this->fileToClass = $fileToClass;
        $this->limit = $limit;
    }

    public function complete(Node $node, string $source, int $offset): Response
    {
        if (false === $this->couldComplete($node)) {
            return Response::new();
        }


        $files = $this->filesystem->fileList()->phpFiles();

        if ($node instanceof QualifiedName) {
            $files = $files->filter(function (SplFileInfo $file) use ($node) {
                return 0 === strpos($file->getFilename(), $node->getText());
            });
        }

        $suggestions = [];
        $count = 0;
        $currentNamespace = $this->getCurrentNamespace($node);
        $imports = $node->getImportTablesForCurrentScope();
        /** @var ScfFilePath $file */
        foreach ($files as $file) {
            $candidates = $this->fileToClass->fileToClassCandidates(FilePath::fromString($file->path()));

            if ($candidates->noneFound()) {
                continue;
            }

            $best = $candidates->best();
            $suggestions[] = Suggestion::createWithOptions(
                $best->name(),
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'short_description' => $best->__toString(),
                    'class_import' => $this->getClassNameForImport($best, $imports, $currentNamespace),
                ]
            );

            if (++$count >= $this->limit) {
                break;
            }
        }

        $suggestions = Suggestions::fromSuggestions($suggestions);

        return Response::fromSuggestions($suggestions->sorted());
    }

    private function getClassNameForImport($candidate, array $imports, ?string $currentNamespace): ?string
    {
        $candidateNamespace = $candidate->namespace();

        if ($currentNamespace === $candidateNamespace || $candidateNamespace === '' ) {
            return null;
        }

        /** @var ResolvedName $resolvedName */
        foreach ($imports[0] as $resolvedName) {
            if ($candidate->__toString() === $resolvedName->getFullyQualifiedNameText()) {
                return null;
            }
        }

        return $candidate->__toString();
    }

    private function getCurrentNamespace(Node $node): ?string
    {
        $currentNamespaceDefinition = $node->getNamespaceDefinition();

        return null !== $currentNamespaceDefinition && null !== $currentNamespaceDefinition->name
            ? $currentNamespaceDefinition->name->getText()
            : null;
    }

    private function couldComplete(Node $node): bool
    {
        if ($node instanceof QualifiedName) {
            return true;
        }

        if ($node instanceof ObjectCreationExpression) {
            return true;
        }

        if ($node instanceof NamespaceUseClause) {
            return true;
        }

        if ($node instanceof NamespaceUseDeclaration) {
            return true;
        }

        if ($node instanceof ClassBaseClause) {
            return true;
        }

        return false;
    }
}
