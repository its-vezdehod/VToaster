<?php

namespace vezdehod\toaster\pack\icon;

use vezdehod\toaster\pack\resource\LocalResource;
use vezdehod\toaster\pack\resource\resolver\InternetResourceResolver;
use function pathinfo;
use function sprintf;
use const PATHINFO_BASENAME;

class InternetToastIcon extends ToastIcon {

    private string $pack;

    public function __construct(private string $url) { }

    public function resolveLocalResource(): ?LocalResource {
        $file = (new InternetResourceResolver($this->url, self::SUPPORTED_TYPES))->resolve();
        $name = pathinfo($file, PATHINFO_BASENAME);
        return new LocalResource($file, $this->pack = sprintf(self::TEXTURE_RESOURCE_PATH, $name));
    }

    public function getPackPath(): string { return $this->pack; }
}