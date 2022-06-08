<?php

namespace vezdehod\toaster;

use Exception;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\scheduler\ClosureTask;
use ReflectionClass;
use Throwable;
use vezdehod\toaster\pack\RuntimePackBuilder;
use vezdehod\toaster\queue\GlobalToastQueue;
use function array_shift;
use function implode;
use function is_array;
use function strtolower;

class VToasterMain extends PluginBase {

    private GlobalToastQueue $queue;

    private Toast $default;
    private Toast $error;
    private Toast $warning;

    /**
     * @throws Exception
     */
    protected function onLoad(): void {
        $this->saveResource("toast-background-slice.json");
        $this->saveResource("toast-background-slice.png");
        $this->saveResource("error.ogg");
        $this->saveResource("error.png");

        $this->queue = new GlobalToastQueue();
        ToastFactory::setFactory(fn(ToastOptions $options) => new Toast($options->getSound()->getName(), $options->getFlag(), $this->queue));


        $this->default = ToastFactory::create(ToastOptions::create($this, 'default')
            ->defaultSound("random.toast")
            ->defaultIcon("textures/ui/infobulb.png"));

        $this->error = ToastFactory::create(ToastOptions::create($this, 'error')
            ->fileIcon($this->getDataFolder() . "error.png")
            ->fileSound($this->getDataFolder() . "error.ogg"));

        $this->warning = ToastFactory::create(ToastOptions::create($this, 'warning')
            ->downloadIcon("https://files.softicons.com/download/internet-icons/3d-ii-icons-by-la-glanz-studio/png/256/warning.png")
            ->downloadSound("https://bigsoundbank.com/UPLOAD/ogg/2380.ogg"));
    }

    protected function onEnable(): void {
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

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn() => $this->queue->process()), 20);

        $this->getServer()->getPluginManager()->registerEvent(PlayerQuitEvent::class, function (PlayerQuitEvent $event): void {
            $this->queue->clear($event->getPlayer());
        }, EventPriority::MONITOR, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!($sender instanceof Player)) {
            return true;
        }
        if (count($args) < 3) {
            return false;
        }

        $toast = match (array_shift($args)) {
            "error" => $this->error,
            "warning" => $this->warning,
            default => $this->default
        };


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

        $message = implode(" ", $args);
        $header = $header === null || count($header) === 0 ? null : implode(" ", $header);
        match (true) {
            $silent && $immediately => $toast->sendSilent($sender, $message, $header),
            $silent && !$immediately => $toast->enqueueSilent($sender, $message, $header),
            !$silent && $immediately => $toast->send($sender, $message, $header),
            !$silent && !$immediately => $toast->enqueue($sender, $message, $header)
        };
        return true;
    }

    private function injectPack(ResourcePack $pack): void {
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