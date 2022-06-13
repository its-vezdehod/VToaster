<?php

namespace vezdehod\toaster\pack\resource;

class BuiltInResource implements IResource {
    private $inPackName;
    public function __construct(string $inPackName) { $this->inPackName = $inPackName; }

    public function fetch(): void { }

    public function getLocalFile(): ?string { return null; }

    public function getInPackUsageName(): string { return $this->inPackName; }
}