<?php

namespace vezdehod\toaster\pack\resource;

use pocketmine\utils\Internet;
use pocketmine\utils\InternetException;
use vezdehod\toaster\pack\resource\exception\InvalidMimeTypeException;
use function array_keys;
use function explode;
use function file_put_contents;
use function implode;
use function md5;
use function stripos;
use function strtolower;
use function sys_get_temp_dir;

class InternetResource implements IResource {
    //TODO: Refactor this

    private string $file;

    public function __construct(
        private string $url,
        private string $inPackName,
        private array  $mimes,
        //TODO: Custom HTTP client?
    ) {
    }

    public function fetch(): void {
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
        $this->file = sys_get_temp_dir() . "/" . md5($this->url) . "." . $this->mimes[$mime];
        file_put_contents($this->file, $result->getBody());
    }

    public function getLocalFile(): ?string {
        return $this->file;
    }

    public function getInPackUsageName(): string {
        return $this->inPackName;
    }
}