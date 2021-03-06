<?php

namespace vezdehod\toaster;

use Exception;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\scheduler\ClosureTask;
use ReflectionClass;
use Throwable;
use vezdehod\toaster\pack\RuntimePackBuilder;
use vezdehod\toaster\queue\GlobalToastQueue;
use function array_filter;
use function array_keys;
use function array_search;
use function array_shift;
use function array_slice;
use function count;
use function implode;
use function in_array;
use function mb_substr;
use function str_starts_with;
use function strtolower;

class VToasterMain extends PluginBase {

    private GlobalToastQueue $queue;

    /** @var array<string, Toast> */
    private array $toasts = [];

    /**
     * @throws Exception
     */
    protected function onLoad(): void {
        $this->saveResource('config.yml');
        $this->saveResource("toast-background-slice.json");
        $this->saveResource("toast-background-slice.png");
        $this->saveResource("error.ogg");
        $this->saveResource("error.png");

        $this->queue = new GlobalToastQueue();
        ToastFactory::setFactory(fn(ToastOptions $options) => new Toast($options->getSound()->getInPackUsageName(), $options->getFlag(), $this->queue));

        foreach ($this->getConfig()->get("toasts") as $toast => $rawOptions) {
            $options = ToastOptions::create($this, $toast);
            if (isset($rawOptions['icon'])) {
                $icon = $rawOptions['icon'];
                if (str_starts_with($icon, "./")) {
                    $options->fileIcon($this->getDataFolder() . mb_substr($icon, 2));
                } else if (str_starts_with($icon, "http")) {
                    $options->downloadIcon($icon);
                } else {
                    $options->defaultIcon($icon);
                }
            }
            if (isset($rawOptions['sound'])) {
                $sound = $rawOptions['sound'];
                if (str_starts_with($sound, "./")) {
                    $options->fileSound($this->getDataFolder() . mb_substr($sound, 2));
                } else if (str_starts_with($sound, "http")) {
                    $options->downloadSound($sound);
                } else {
                    $options->defaultSound($sound);
                }
            }
            //TODO: Animations, background
            $this->toasts[$toast] = ToastFactory::create($options);
        }
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
        if (count($args) < 4) {
            return false;
        }

        $target = $this->getServer()->getPlayerByPrefix(array_shift($args));
        if ($target === null) {
            $sender->sendMessage("Player not found!");
            return true;
        }

        $toast = $this->toasts[array_shift($args)] ?? null;
        if ($toast === null) {
            $sender->sendMessage("Toast not found! Available toasts: " . implode(", ", array_keys($this->toasts)));
            return true;
        }


        $silent = in_array("-s", $args, true);
        $immediately = in_array("-n", $args, true);
        $args = array_filter($args, static fn($arg) => $arg !== "-s" && $arg !== "-n");
        $messageIdx = array_search("-m", $args, true);
        if ($messageIdx === false) {
            return false;
        }
        $header = implode(" ", array_slice($args, 0, $messageIdx));
        $message = implode(" ", array_slice($args, $messageIdx + 1));

        match (true) {
            $silent && $immediately => $toast->sendSilent($target, $header, $message),
            $silent && !$immediately => $toast->enqueueSilent($target, $header, $message),
            !$silent && $immediately => $toast->send($target, $header, $message),
            !$silent && !$immediately => $toast->enqueue($target, $header, $message)
        };
        $sender->sendMessage("Toast successfully " . ($immediately ? "sent" : "enqueued") . " to {$target->getName()} " . ($silent ? "(without sound)" : ""));
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