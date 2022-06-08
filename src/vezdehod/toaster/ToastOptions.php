<?php

namespace vezdehod\toaster;

use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use vezdehod\toaster\pack\icon\FileSystemToastIcon;
use vezdehod\toaster\pack\icon\InternetToastIcon;
use vezdehod\toaster\pack\icon\PackToastIcon;
use vezdehod\toaster\pack\icon\ToastIcon;
use vezdehod\toaster\pack\sound\FileSystemToastSound;
use vezdehod\toaster\pack\sound\InternetToastSound;
use vezdehod\toaster\pack\sound\PackToastSound;
use vezdehod\toaster\pack\sound\ToastSound;
use function array_map;
use function implode;
use function preg_replace;
use function str_pad;
use function str_split;
use const STR_PAD_LEFT;

class ToastOptions {

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

    private ToastIcon $icon;
    private ToastSound $sound;

    private function __construct(
        private Plugin $plugin,
        private string $name
    ) {
        $this->flag = self::generateFlag($this->plugin->getName() . $this->name);
        $this->icon = new PackToastIcon("textures/ui/infobulb.png");
        $this->sound = new PackToastSound("random.toast");
    }

    public function getPlugin(): Plugin { return $this->plugin; }

    public function getName(): string { return $this->name; }

    public function getFlag(): string { return $this->flag; }

    public function getIcon(): ToastIcon { return $this->icon; }

    public function icon(ToastIcon $icon): ToastOptions {
        $this->icon = $icon;
        return $this;
    }

    public function defaultIcon(string $path): ToastOptions { return $this->icon(new PackToastIcon($path)); }

    public function downloadIcon(string $url): ToastOptions { return $this->icon(new InternetToastIcon($url)); }

    public function fileIcon(string $path): ToastOptions { return $this->icon(new FileSystemToastIcon($path)); }

    public function getSound(): ToastSound { return $this->sound; }

    public function sound(ToastSound $sound): ToastOptions {
        $this->sound = $sound;
        return $this;
    }

    public function defaultSound(string $name): ToastOptions { return $this->sound(new PackToastSound($name)); }

    public function downloadSound(string $url): ToastOptions { return $this->sound(new InternetToastSound($url)); }

    public function fileSound(string $path): ToastOptions { return $this->sound(new FileSystemToastSound($path)); }

    // TODO: style: Animations, background, position...
}