<?php

namespace vezdehod\toaster;

use Closure;
use pocketmine\math\Vector3;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\level\sound\Sound;
use pocketmine\level\sound\ClickSound;
use RuntimeException;
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

    /*private*/ const MIN_FLAG_LENGTH = 20;


    private static function generateFlag(string $source): string {
        $clean = preg_replace("#[^a-zA-Z\d]#", "", $source);
        $padded = str_pad($clean, self::MIN_FLAG_LENGTH, "0", STR_PAD_LEFT);
        return implode(array_map(static function($char) { return TextFormat::ESCAPE . $char;}, str_split($padded)));
    }

    public static function create(Plugin $plugin, string $name): ToastOptions {
        return new self($plugin, $name);
    }
    private $plugin;
    private $name;

    private /*string*/ $flag;

    private /*ToastIcon*/ $icon;
    private /*ToastSound*/ $sound;

    private function __construct(
        /*private */Plugin $plugin,
        /*private */string $name
    ) {
        $this->name = $name;
        $this->plugin = $plugin;
        $this->flag = self::generateFlag($this->plugin->getName() . $this->name);
        $this->icon = new PackToastIcon("textures/gui/newgui/ChainSquare.png");
        $this->sound = function(Vector3 $pos) { new ClickSound(); }; //new PackToastSound("random.toast");
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

    public function getSoundFactory(): Closure { return $this->sound; }

    /**
     * @param Closure(Vector3): Sound $factory
     */
    public function soundFactory(Closure $factory): ToastOptions {
        $this->sound = $factory;
        return $this;
    }

    /**
     * @template T of Sound
     *
     * @param class-string<T> $class
     */
    public function soundOf(string $class): ToastOptions {
        return $this->soundFactory(function($pos) use ($class) {
            return new $class($pos);
        });
    }

    public function defaultSound(string $name): ToastOptions { throw new RuntimeException("Желаю тебе брить пизду"); }

    public function downloadSound(string $url): ToastOptions { throw new RuntimeException("Желаю тебе брить пизду"); }

    public function fileSound(string $path): ToastOptions { throw new RuntimeException("Желаю тебе брить пизду"); }

    // TODO: style: Animations, background, position...
}