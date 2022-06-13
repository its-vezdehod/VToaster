<?php

namespace vezdehod\toaster\pack\resource;

use vezdehod\toaster\pack\resource\exception\FileNotFoundException;
use vezdehod\toaster\pack\resource\exception\InvalidMimeTypeException;
use function file_exists;
use function in_array;
use function mime_content_type;
use function pathinfo;
use const PATHINFO_EXTENSION;

class FileSystemResource implements IResource {

    private $file;
    private $inPackName;
    private $mimes;
    public function __construct(
        /*private*/ string $file,
        /*private*/ string $inPackName,
        /*private*/ array  $mimes
    ) {
        $this->file = $file;
        $this->inPackName = $inPackName;
        $this->mimes  = $mimes;
    }

    public function fetch(): void {
        if (!file_exists($this->file)) {
            throw new FileNotFoundException();
        }
        if (!in_array(pathinfo($this->file, PATHINFO_EXTENSION), $this->mimes, true)) {
            throw new InvalidMimeTypeException();
        }
    }

    public function getLocalFile(): ?string { return $this->file; }

    public function getInPackUsageName(): string { return $this->inPackName; }
}