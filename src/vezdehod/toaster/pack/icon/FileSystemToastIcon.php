<?php

namespace vezdehod\toaster\pack\icon;


use vezdehod\toaster\pack\resource\LocalResource;
use vezdehod\toaster\pack\resource\resolver\FileSystemResourceResolver;
use function pathinfo;
use function sprintf;
use const PATHINFO_BASENAME;

class FileSystemToastIcon extends ToastIcon {

    private string $pack;

    public function __construct(private string $file) { }

    public function resolveLocalResource(): ?LocalResource {
        $this->file = (new FileSystemResourceResolver($this->file, self::SUPPORTED_TYPES))->resolve();
        $name = pathinfo($this->file, PATHINFO_BASENAME);
        return new LocalResource($this->file, $this->pack = sprintf(self::TEXTURE_RESOURCE_PATH, $name));
    }

    public function getPackPath(): string { return $this->pack; }
}