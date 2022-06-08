<?php

namespace vezdehod\toaster\pack\resource;

use Exception;

interface IResource {
    /**
     * @throws Exception
     */
    public function resolveLocalResource(): ?LocalResource;
}