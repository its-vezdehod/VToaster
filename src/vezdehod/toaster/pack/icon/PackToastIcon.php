<?php

namespace vezdehod\toaster\pack\icon;

use vezdehod\toaster\pack\resource\LocalResource;

class PackToastIcon extends ToastIcon {

    private $path;

    public function __construct(/*private*/ string $path) { $this->path = $path; }

    public function resolveLocalResource()/*: ?LocalResource */{ return null; }

    public function getPackPath(): string { return $this->path; }
}