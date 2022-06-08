<?php

namespace vezdehod\toaster\pack\sound;


use vezdehod\toaster\pack\resource\LocalResource;

class PackToastSound extends ToastSound {

    public function resolveLocalResource(): ?LocalResource { return null; }
}