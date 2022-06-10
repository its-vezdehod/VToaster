<?php

namespace vezdehod\toaster\pack\resource;

class LocalResource {

    private $localPath;
    private $packPath;

    public function __construct(
        /*private*/ string $localPath,
        /*private*/ string $packPath
    ) {
        $this->packPath = $packPath;
        $this->localPath = $localPath;
    }

    public function getLocalPath(): string {
        return $this->localPath;
    }

    public function getPackPath(): string {
        return $this->packPath;
    }
}