<?php

namespace vezdehod\toaster;

use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use vezdehod\toaster\queue\GlobalToastQueue;
use vezdehod\toaster\queue\QueuedToast;

class Toast {


    public function __construct(
        private string           $sound,
        private string           $flag,
        private GlobalToastQueue $queue,
    ) {
    }

    public function getDuration(): int {
        return 6;//TODO
    }

    public function sendSilent(Player $player, string $header, string $message): void {
        $player->sendActionBarMessage($this->flag . TextFormat::RESET . $header . TextFormat::EOL  . TextFormat::RESET . $message);
    }

    public function send(Player $player, string $header, string $message): void {
        $this->sendSilent($player, $message, $header);
        $pos = $player->getPosition();
        $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create($this->sound, $pos->x, $pos->y, $pos->z, 1, 1));
    }

    public function enqueueSilent(Player $player, string $header, string $message): void {
        $this->queue->enqueue($player, new QueuedToast($this, $header, $message, true));
    }

    public function enqueue(Player $player, string $header, string $message): void {
        $this->queue->enqueue($player, new QueuedToast($this, $header, $message, false));
    }


}