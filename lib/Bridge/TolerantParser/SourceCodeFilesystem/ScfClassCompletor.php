<?php

namespace Phpactor\Completion\Bridge\TolerantParser\SourceCodeFilesystem;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\ClassBaseClause;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;
use Microsoft\PhpParser\Node\NamespaceUseClause;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\Statement\NamespaceUseDeclaration;
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
        /** @var ScfFilePath $file */
        foreach ($files as $file) {
            $candidates = $this->fileToClass->fileToClassCandidates(FilePath::fromString($file->path()));

            if ($candidates->noneFound()) {
                continue;
            }

            $best = $candidates->best();
            $options = [
                'type' => Suggestion::TYPE_CLASS,
                'short_description' => $best->__toString(),
            ];

            if (!$this->isAlreadyImported($best->__toString(), $node)) {
                $options['class_import'] = $best->__toString();
            }

            $suggestions[] = Suggestion::createWithOptions($best->name(), $options);

            if (++$count >= $this->limit) {
                break;
            }
        }

        $suggestions = Suggestions::fromSuggestions($suggestions);

        return Response::fromSuggestions($suggestions->sorted());
    }

    private function isAlreadyImported(string $candidate, Node $node): bool
    {
        foreach ($node->getImportTablesForCurrentScope()[0] as $resolvedName) {
            if ($resolvedName->getFullyQualifiedNameText() === $candidate) {
                return true;
            }
        }

        return false;
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
