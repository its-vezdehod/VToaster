<?php

namespace vezdehod\toaster\pack\resource\resolver;

use vezdehod\toaster\pack\resource\resolver\exception\FileNotFoundException;
use vezdehod\toaster\pack\resource\resolver\exception\InvalidMimeTypeException;
use function file_exists;
use function mime_content_type;

class FileSystemResourceResolver implements IResourceResolver {

    /**
     * @param array<string, string> $mimes mime=>extension
     */
    public function __construct(private string $file, private array $mimes) { }

    public function resolve(): string {
        if (!file_exists($this->file)) {
            throw new FileNotFoundException();
        }
        if (!isset($this->mimes[mime_content_type($this->file)])) {
            throw new InvalidMimeTypeException();
        }
        return $this->file;
    }
}