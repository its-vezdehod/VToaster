<?php

namespace vezdehod\toaster\pack\resource;

class BuiltInResource implements IResource {

    public function __construct(private string $inPackName) { }

    public function fetch(): void { }

    public function getLocalFile(): ?string { return null; }

    public function getInPackUsageName(): string { return $this->inPackName; }
}