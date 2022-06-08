<?php

namespace vezdehod\toaster\pack\resource\resolver;

use Exception;

interface IResourceResolver {

    /**
     * Returns path to file for injection into resource pack or null if resource already in pack
     * @throws Exception
     */
    public function resolve(): string;
}