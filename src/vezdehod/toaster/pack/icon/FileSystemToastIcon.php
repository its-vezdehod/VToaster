<?php

namespace vezdehod\toaster\pack\icon;


use vezdehod\toaster\pack\resource\LocalResource;
use vezdehod\toaster\pack\resource\resolver\FileSystemResourceResolver;
use function pathinfo;
use function sprintf;
use const PATHINFO_BASENAME;

class FileSystemToastIcon extends ToastIcon {

    /** @var string */
    private /*string*/ $pack;
    private $file;

    public function __construct(/*private*/ string $file) { $this->file = $file; }

    public function resolveLocalResource()/*: ?LocalResource */{
        $this->file = (new FileSystemResourceResolver($this->file, self::SUPPORTED_TYPES))->resolve();
        $parts = pathinfo($this->file);
        return new LocalResource($this->file, ($this->pack = sprintf(self::TEXTURE_RESOURCE_PATH, $parts['filename'])) . "." . $parts['extension']);
    }

    public function getPackPath(): string { return $this->pack; }
}