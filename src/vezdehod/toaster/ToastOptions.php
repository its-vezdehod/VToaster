<?php

namespace vezdehod\toaster;

use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use vezdehod\toaster\pack\resource\BuiltInResource;
use vezdehod\toaster\pack\resource\FileSystemResource;
use vezdehod\toaster\pack\resource\InternetResource;
use vezdehod\toaster\pack\resource\IResource;
use function array_map;
use function implode;
use function preg_replace;
use function sprintf;
use function str_pad;
use function str_split;
use const STR_PAD_LEFT;

class ToastOptions {

    private const ICON_SUPPORTED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    ];

    private const SOUND_SUPPORTED_TYPES = [
        'audio/ogg' => 'ogg'
    ];

    private const SOUND_NAME = "vtoaster.%s.%s";
    private const ICON_NAME = "%s-%s";

    private const MIN_FLAG_LENGTH = 20;

    private static function generateFlag(string $source): string {
        $clean = preg_replace("#[^a-zA-Z\d]#", "", $source);
        $padded = str_pad($clean, self::MIN_FLAG_LENGTH, "0", STR_PAD_LEFT);
        return implode(array_map(static fn($char) => TextFormat::ESCAPE . $char, str_split($padded)));
    }

    public static function create(Plugin $plugin, string $name): ToastOptions {
        return new self($plugin, $name);
    }

    private string $flag;

    private IResource $icon;
    private IResource $sound;

    private function __construct(
        private Plugin $plugin,
        private string $name
    ) {
        $this->flag = self::generateFlag($this->plugin->getName() . $this->name);
        $this->icon = new BuiltInResource("textures/ui/infobulb.png");
        $this->sound = new BuiltInResource("random.toast");
    }

    public function getPlugin(): Plugin { return $this->plugin; }

    public function getName(): string { return $this->name; }

    public function getFlag(): string { return $this->flag; }

    public function getIcon(): IResource { return $this->icon; }

    public function defaultIcon(string $path): ToastOptions {
        $this->icon = new BuiltInResource($path);
        return $this;
    }

    public function downloadIcon(string $url): ToastOptions {
        $iconName = sprintf(self::ICON_NAME, $this->getPlugin()->getName(), $this->name);
        $this->icon = new InternetResource($url, $iconName, self::ICON_SUPPORTED_TYPES);
        return $this;
    }

    public function fileIcon(string $path): ToastOptions {
        $iconName = sprintf(self::ICON_NAME, $this->getPlugin()->getName(), $this->name);
        $this->icon = new FileSystemResource($path, $iconName, self::ICON_SUPPORTED_TYPES);
        return $this;
    }

    public function getSound(): IResource { return $this->sound; }

    public function defaultSound(string $name): ToastOptions {
        $this->sound = new BuiltInResource($name);
        return $this;
    }

    public function downloadSound(string $url): ToastOptions {
        $sound = sprintf(self::SOUND_NAME, $this->getPlugin()->getName(), $this->name);
        $this->sound = new InternetResource($url, $sound, self::SOUND_SUPPORTED_TYPES);
        return $this;
    }

    public function fileSound(string $path): ToastOptions {
        $sound = sprintf(self::SOUND_NAME, $this->getPlugin()->getName(), $this->name);
        $this->sound = new FileSystemResource($path, $sound, self::SOUND_SUPPORTED_TYPES);
        return $this;
    }

    // TODO: style: Animations, background, position...
}