<?php

namespace vezdehod\toaster\pack\icon;

use vezdehod\toaster\pack\resource\LocalResource;

class PackToastIcon extends ToastIcon {

    public function __construct(private string $path) { }

    public function resolveLocalResource(): ?LocalResource { return null; }

    public function getPackPath(): string { return $this->path; }
}