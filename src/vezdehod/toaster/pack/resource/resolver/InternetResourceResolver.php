<?php

namespace vezdehod\toaster\pack\resource\resolver;

use pocketmine\utils\Internet;
use pocketmine\utils\InternetException;
use vezdehod\toaster\pack\resource\resolver\exception\InvalidMimeTypeException;
use function array_keys;
use function explode;
use function file_put_contents;
use function implode;
use function md5;
use function stripos;
use function strtolower;
use function sys_get_temp_dir;

class InternetResourceResolver implements IResourceResolver {

    /**
     * @param array<string, string> $mimes mime=>extension
     */
    public function __construct(
        private string $url,
        private array  $mimes,
        //TODO: Custom HTTP client?
    ) {

    }

    public function resolve(): string {
        $result = Internet::getURL($this->url);
        if ($result === null) {
            throw new InternetException("Failed to fetch resource " . $this->url);
        }
        $mime = 'unknown';
        foreach ($result->getHeaders() as $headers) {
            foreach ($headers as $header => $value) {
                if (stripos($header, "content-type") === 0) {
                    [$mime] = explode(";", $value);
                    break;
                }
            }
        }

        if (!isset($this->mimes[$mime = strtolower($mime)])) {
            throw new InvalidMimeTypeException("Excepted " . implode("|", array_keys($this->mimes)) . ", got $mime");
        }
        $fileName = sys_get_temp_dir() . "/" . md5($this->url) . "." . $this->mimes[$mime];
        file_put_contents($fileName, $result->getBody());
        return $fileName;
    }
}