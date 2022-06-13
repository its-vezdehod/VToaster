<?php

namespace vezdehod\toaster\pack\resource;

use Exception;

interface IResource {
    /**
     * @throws Exception
     */
    public function fetch()/*: void*/;

    public function getLocalFile()/*: ?string*/;

    public function getInPackUsageName()/*: string*/;
}