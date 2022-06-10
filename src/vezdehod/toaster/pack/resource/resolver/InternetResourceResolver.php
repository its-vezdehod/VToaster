<?php

namespace vezdehod\toaster\pack\resource\resolver;

use Exception;

//use pocketmine\utils\Internet;
//use pocketmine\utils\InternetException;
use vezdehod\toaster\pack\resource\resolver\exception\InvalidMimeTypeException;
use function array_keys;
use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function explode;
use function file_put_contents;
use function implode;
use function md5;
use function stripos;
use function strtolower;
use function substr;
use function sys_get_temp_dir;
use function trim;
use const CURLINFO_HEADER_SIZE;
use const CURLOPT_AUTOREFERER;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_FORBID_REUSE;
use const CURLOPT_FRESH_CONNECT;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;

class InternetResourceResolver implements IResourceResolver
{
    private $url;
    private $mimes;

    /**
     * @param array<string, string> $mimes mime=>extension
     */
    public function __construct(
        /*private*/ string $url,
        /*private*/ array  $mimes
        //TODO: Custom HTTP client?
    )
    {
        $this->mimes = $mimes;
        $this->url = $url;

    }

    public function resolve(): string
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 PocketMine-MP"]);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT,  10);
        curl_setopt($ch, CURLOPT_HEADER, true);
        try{
            $raw = curl_exec($ch);
            if($raw === false){
                throw new /*Internet*/ Exception("Failed to fetch resource " . $this->url . ":" . curl_error($ch));
            }
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $rawHeaders = substr($raw, 0, $headerSize);
            $body = substr($raw, $headerSize);
            $headers = [];
            foreach(explode("\r\n\r\n", $rawHeaders) as $rawHeaderGroup){
                $headerGroup = [];
                foreach(explode("\r\n", $rawHeaderGroup) as $line){
                    $nameValue = explode(":", $line, 2);
                    if(isset($nameValue[1])){
                        $headerGroup[trim(strtolower($nameValue[0]))] = trim($nameValue[1]);
                    }
                }
                $headers[] = $headerGroup;
            }
            $mime = 'unknown';
            foreach ($headers as $headerGroup) {
                foreach ($headerGroup as $header => $value) {
                    if (stripos($header, "content-type") === 0) {
                        list($mime) = explode(";", $value);
                        break;
                    }
                }
            }

            if (!isset($this->mimes[$mime = strtolower($mime)])) {
                throw new InvalidMimeTypeException("Excepted " . implode("|", array_keys($this->mimes)) . ", got $mime");
            }
            $fileName = sys_get_temp_dir() . "/" . md5($this->url) . "." . $this->mimes[$mime];
            file_put_contents($fileName, $body);
            return $fileName;
        }finally{
            curl_close($ch);
        }
    }
}