<?php

namespace vezdehod\toaster\pack\resource;

use Exception;

interface IResource {
    /**
     * @return LocalResource|null
     * @throws Exception
     */
    public function resolveLocalResource()/*: ?LocalResource*/;
}