<?php

namespace vezdehod\toaster\pack\resource\resolver;

use vezdehod\toaster\pack\resource\resolver\exception\FileNotFoundException;
use vezdehod\toaster\pack\resource\resolver\exception\InvalidMimeTypeException;
use function file_exists;
use function in_array;
use function pathinfo;
use const PATHINFO_EXTENSION;

//use function mime_content_type;

class FileSystemResourceResolver implements IResourceResolver {
    private $file;
    private $mimes;

    /**
     * @param array<string, string> $mimes mime=>extension
     */
    public function __construct(/*private*/ string $file, /*private*/ array $mimes) {
        $this->mimes = $mimes;
        $this->file = $file;
    }

    public function resolve(): string {
        if (!file_exists($this->file)) {
            throw new FileNotFoundException();
        }
        $extension = pathinfo($this->file, PATHINFO_EXTENSION);
        if (!in_array($extension, $this->mimes, true)) {
            throw new InvalidMimeTypeException();
        }
        return $this->file;
    }
}