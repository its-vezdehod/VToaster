<?php

namespace vezdehod\toaster\pack\icon;

use vezdehod\toaster\pack\resource\IResource;

abstract class ToastIcon implements IResource {

    public const TEXTURE_RESOURCE_PATH = "textures/vezdehodui/toast/%s";

    public const SUPPORTED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    ];

    abstract public function getPackPath(): string;
}