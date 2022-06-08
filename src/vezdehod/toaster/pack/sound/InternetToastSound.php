<?php

namespace vezdehod\toaster\pack\sound;

use vezdehod\toaster\pack\resource\LocalResource;
use vezdehod\toaster\pack\resource\resolver\InternetResourceResolver;
use function md5;
use function pathinfo;
use function sprintf;
use const PATHINFO_BASENAME;

class InternetToastSound extends ToastSound {

    public function __construct(private string $url) {
        parent::__construct(sprintf(self::SOUND_DEFINITION_NAME, md5($this->url)));
    }

    public function resolveLocalResource(): ?LocalResource {
        $file = (new InternetResourceResolver($this->url, self::SUPPORTED_TYPES))->resolve();
        $name = pathinfo($file, PATHINFO_BASENAME);
        return new LocalResource($file, sprintf(self::SOUND_RESOURCE_PATH, $name));
    }
}