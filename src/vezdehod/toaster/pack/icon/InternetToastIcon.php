<?php

namespace vezdehod\toaster\pack\icon;

use vezdehod\toaster\pack\resource\LocalResource;
use vezdehod\toaster\pack\resource\resolver\InternetResourceResolver;
use function pathinfo;
use function sprintf;
use const PATHINFO_BASENAME;

class InternetToastIcon extends ToastIcon {

    private /*string*/ $pack;
    private $url;

    public function __construct(/*private*/ string $url) { $this->url = $url; }

    public function resolveLocalResource()/*: ?LocalResource */{
        $file = (new InternetResourceResolver($this->url, self::SUPPORTED_TYPES))->resolve();
        $parts = pathinfo($file);
        return new LocalResource($file, ($this->pack = sprintf(self::TEXTURE_RESOURCE_PATH, $parts['filename'])) . "." . $parts['extension']);
    }

    public function getPackPath(): string { return $this->pack; }
}