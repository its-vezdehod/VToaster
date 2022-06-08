<?php

namespace vezdehod\toaster\pack\sound;

use vezdehod\toaster\pack\resource\LocalResource;
use vezdehod\toaster\pack\resource\resolver\FileSystemResourceResolver;
use function md5_file;
use function pathinfo;
use function sprintf;
use const PATHINFO_BASENAME;

class FileSystemToastSound extends ToastSound {

    public function __construct(private string $file) {
        parent::__construct(sprintf(self::SOUND_DEFINITION_NAME, md5_file($this->file)));
    }

    public function resolveLocalResource(): ?LocalResource {
        $this->file = (new FileSystemResourceResolver($this->file, self::SUPPORTED_TYPES))->resolve();
        $name = pathinfo($this->file, PATHINFO_BASENAME);
        return new LocalResource(
            $this->file,
            sprintf(self::SOUND_RESOURCE_PATH, $name)
        );
    }
}