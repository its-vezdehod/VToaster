<?php

namespace vezdehod\toaster\pack\resource;

use vezdehod\toaster\pack\resource\exception\FileNotFoundException;
use vezdehod\toaster\pack\resource\exception\InvalidMimeTypeException;
use function file_exists;
use function mime_content_type;

class FileSystemResource implements IResource {

    public function __construct(
        private string $file,
        private string $inPackName,
        private array  $mimes,
    ) {
    }

    public function fetch(): void {
        if (!file_exists($this->file)) {
            throw new FileNotFoundException();
        }
        if (!isset($this->mimes[mime_content_type($this->file)])) {
            throw new InvalidMimeTypeException();
        }
    }

    public function getLocalFile(): ?string { return $this->file; }

    public function getInPackUsageName(): string { return $this->inPackName; }
}