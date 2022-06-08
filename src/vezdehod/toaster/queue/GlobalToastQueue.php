<?php

namespace vezdehod\toaster\queue;

use pocketmine\player\Player;

class GlobalToastQueue {

    /** @var PlayerToastQueue[] */
    private array $queue = [];

    public function clear(Player $player): void { unset($this->queue[$player->getId()]); }

    public function enqueue(Player $player, QueuedToast $toast): void {
        if (!isset($this->queue[$player->getId()])) {
            $this->queue[$player->getId()] = new PlayerToastQueue($player);
        }
        $this->queue[$player->getId()]->enqueue($toast);
    }

    public function process(): void {
        foreach($this->queue as $id => $queue) {
            if (!$queue->process()) {
                unset($this->queue[$id]);
            }
        }
    }
}