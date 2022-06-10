<?php

namespace vezdehod\toaster;

use Exception;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\/*player\*/Player;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\scheduler\Task;
use pocketmine\level\sound\AnvilFallSound;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\ClickSound;
use ReflectionClass;
use Throwable;
use vezdehod\toaster\pack\RuntimePackBuilder;
use vezdehod\toaster\queue\GlobalToastQueue;
use function array_shift;
use function count;
use function implode;
use function is_array;
use function strtolower;

class VToasterMain extends PluginBase {

    private /*GlobalToastQueue */
        $queue;

    private /*Toast*/
        $default;
    private /*Toast*/
        $error;
    private /*Toast*/
        $warning;

    /**
     * @throws Exception
     */
    /*protected*/
    public function onLoad()/*: void*/ {
        $this->saveResource("toast-background-slice.json");
        $this->saveResource("toast-background-slice.png");
        $this->saveResource("error.ogg");
        $this->saveResource("error.png");

        $this->queue = new GlobalToastQueue();
        ToastFactory::setFactory(function (ToastOptions $options) { return new Toast($options->getSoundFactory(), $options->getFlag(), $this->queue); });


        $this->default = ToastFactory::create(ToastOptions::create($this, 'default')
            ->defaultIcon("textures/items/diamond_sword")
            ->soundOf(ClickSound::class));

        $this->error = ToastFactory::create(ToastOptions::create($this, 'error')
            ->fileIcon($this->getDataFolder() . "error.png")
            ->soundOf(AnvilFallSound::class));

        $this->warning = ToastFactory::create(ToastOptions::create($this, 'warning')
            ->downloadIcon("https://files.softicons.com/download/internet-icons/3d-ii-icons-by-la-glanz-studio/png/256/warning.png")
            ->soundOf(BlazeShootSound::class));
    }

    /*protected*/
    public function onEnable()/*: void*/ {
        $options = ToastFactory::getAndLock();

        $pack = new RuntimePackBuilder(
            $this->getDataFolder() . "tmp-rp.zip",
            $this->getDataFolder() . "toast-background-slice.png",
            $this->getDataFolder() . "toast-background-slice.json");
        foreach ($options as $option) {
            try {
                $pack->addToast($option);
            } catch (Throwable $exception) {
                $this->getLogger()->error("Failed to add {$option->getName()} for {$option->getPlugin()->getName()}: {$exception->getMessage()}");
                $this->getLogger()->logException($exception);
                throw $exception;
            }
        }

        $this->injectPack($pack->generate());

        $this->getServer()->getScheduler()->scheduleRepeatingTask(new class($this->queue) extends Task {
            private $queue;

            public function __construct(GlobalToastQueue $queue) { $this->queue = $queue; }

            public function onRun($currentTick) { $this->queue->process(); }
        }, 20);

        $this->getServer()->getPluginManager()->registerEvents(new class($this->queue) implements Listener {
            private $queue;

            public function __construct(GlobalToastQueue $queue) { $this->queue = $queue; }

            public function handle(PlayerQuitEvent $event)/*: void*/ {
                $this->queue->clear($event->getPlayer());
            }
        }, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args): bool {
        if (!($sender instanceof Player)) {
            return true;
        }
        if (count($args) < 3) {
            return false;
        }

        if (($toastStr = array_shift($args)) === "error") {
            $toast = $this->error;
        } else if ($toastStr === "warning") {
            $toast = $this->warning;
        } else {
            $toast = $this->default;
        }

        $header = null;
        $silent = false;
        $immediately = false;
        foreach ($args as $i => $arg) {
            if ($arg === "-s") {
                $silent = true;
                unset($args[$i]);
            } else if ($arg === "-n") {
                $immediately = true;
                unset($args[$i]);
            } else if ($arg === "-h") {
                $header = [];
                unset($args[$i]);
            } else if (is_array($header)) {
                $header[] = $arg;
                unset($args[$i]);
            }
        }

        $header = $header === null || count($header) === 0 ? null : implode(" ", $header);
        $message = implode(" ", $args);
        switch (true) {
            case $silent && $immediately:
                $toast->sendSilent($sender, $header, $message);
                break;
            case $silent && !$immediately:
                $toast->enqueueSilent($sender, $header, $message);
                break;
            case !$silent && $immediately:
                $toast->send($sender, $header, $message);
                break;
            case!$silent && !$immediately:
                $toast->enqueue($sender, $header, $message);
                break;
        }
        return true;
    }

    private function injectPack(ResourcePack $pack)/*: void*/ {
        $manager = $this->getServer()->getResourcePackManager();
        $reflection = new ReflectionClass($manager);

        $packsProperty = $reflection->getProperty("resourcePacks");
        $packsProperty->setAccessible(true);
        $currentResourcePacks = $packsProperty->getValue($manager);

        $uuidProperty = $reflection->getProperty("uuidList");
        $uuidProperty->setAccessible(true);
        $currentUUIDPacks = $uuidProperty->getValue($manager);

        $property = $reflection->getProperty("serverForceResources");
        $property->setAccessible(true);
        $property->setValue($manager, true);

        $currentUUIDPacks[strtolower($pack->getPackId())] = $currentResourcePacks[] = $pack;

        $packsProperty->setValue($manager, $currentResourcePacks);
        $uuidProperty->setValue($manager, $currentUUIDPacks);
    }
}