<?php

namespace vezdehod\toaster\pack\sound;

use vezdehod\toaster\pack\resource\IResource;

abstract class ToastSound implements IResource {

    /*public */const SOUND_DEFINITION_NAME = "vtoaster.%s";
    /*public */const SOUND_RESOURCE_PATH = 'sounds/vezdehodui/toast/%s';

    /*public */const SUPPORTED_TYPES = [
        'audio/ogg' => 'ogg'
    ];
    private $name;

    public function __construct(/*private*/ string $name) { $this->name = $name; }

    public function getName(): string { return $this->name; }
}