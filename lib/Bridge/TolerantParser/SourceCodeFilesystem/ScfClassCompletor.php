<?php

namespace Phpactor\Completion\Bridge\TolerantParser\SourceCodeFilesystem;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\ClassBaseClause;
use Microsoft\PhpParser\Node\QualifiedName;
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

    public function __construct(Filesystem $filesystem, FileToClass $fileToClass)
    {
        $this->filesystem = $filesystem;
        $this->fileToClass = $fileToClass;
    }
    public function complete(Node $node, string $source, int $offset): Response
    {
        if (false === $this->couldComplete($node)) {
            return Response::new();
        }


        $files = $this->filesystem->fileList()->phpFiles();

        $partial = '';
        if ($node instanceof QualifiedName) {
            $partial = mb_substr($source, $node->getStart(), $offset);
            $files = $files->filter(function (SplFileInfo $file) use ($partial) {
                return 0 === strpos($file->getFilename(), $partial);
            });
        }

        $suggestions = [];
        /** @var ScfFilePath $file */
        foreach ($files as $file) {
            $candidates = $this->fileToClass->fileToClassCandidates(FilePath::fromString($file->path()));

            if ($candidates->noneFound()) {
                continue;
            }

            $best = $candidates->best();
            $suggestions[] = Suggestion::create(
                't',
                $best->name(),
                $best->__toString()
            );
        }

        return Response::fromSuggestions(Suggestions::fromSuggestions($suggestions));
    }

    private function couldComplete(Node $node): bool
    {
        if ($node instanceof QualifiedName) {
            $node = $node->parent;
        }

        if ($node instanceof ClassBaseClause) {
            return true;
        }

        return false;
    }
}
