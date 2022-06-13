<?php

namespace vezdehod\toaster;

use Closure;
use pocketmine\/*player\*/Player;
use pocketmine\utils\TextFormat;
use vezdehod\toaster\queue\GlobalToastQueue;
use vezdehod\toaster\queue\QueuedToast;

class Toast {


    private $sound;
    private $flag;
    private $queue;

    public function __construct(
        /*private */Closure           $sound,
        /*private */string           $flag,
        /*private */GlobalToastQueue $queue
    ) {
        $this->queue = $queue;
        $this->flag = $flag;
        $this->sound = $sound;
    }

    public function getDuration(): int {
        return 6;//TODO
    }

    public function sendSilent(Player $player, string $header, string $message)/*: void*/ {
        $player->addActionBarMessage($this->flag . TextFormat::RESET . $header . "\n"  . TextFormat::RESET . $message);
    }

    public function send(Player $player, string $header, string $message)/*: void*/ {
        $this->sendSilent($player, $header, $message);
        $player->getLevel()->addSound(($this->sound)($player->getPosition()), [$player]);
    }

    public function enqueueSilent(Player $player, string $header, string $message)/*: void*/ {
        $this->queue->enqueue($player, new QueuedToast($this, $header, $message, true));
    }

    public function enqueue(Player $player, string $header, string $message)/*: void*/ {
        $this->queue->enqueue($player, new QueuedToast($this, $header, $message, false));
    }


}