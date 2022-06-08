<?php

namespace vezdehod\toaster\pack\resource;

class LocalResource {

    public function __construct(
        private string $localPath,
        private string $packPath
    ) {
    }

    public function getLocalPath(): string {
        return $this->localPath;
    }

    public function getPackPath(): string {
        return $this->packPath;
    }
}