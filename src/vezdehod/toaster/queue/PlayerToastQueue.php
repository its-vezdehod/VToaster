<?php

namespace vezdehod\toaster\queue;

use pocketmine\/*player\*/Player;
use SplQueue;
use function time;

class PlayerToastQueue {

    /** @var SplQueue<QueuedToast> */
    private /*SplQueue */$queue;
    private /*?int*/ $nextQueue = null;
    private $player;

    public function __construct(/*private*/ Player $player) {
        $this->player = $player;
        $this->queue = new SplQueue();
    }

    public function enqueue(QueuedToast $toast)/*: void*/ {
        $this->queue->enqueue($toast);
        if ($this->nextQueue === null) {
            $this->nextQueue = time();
        }
    }

    public function process(): bool {
        if ($this->queue->count() === 0) {
            return false;
        }

        $now = time();
        if ($this->nextQueue <= $now) {
            /** @var QueuedToast $toast */
            $toast = $this->queue->dequeue();
            $this->nextQueue = $now + $toast->getToast()->getDuration();
            $toast->send($this->player);
        }
        return true;
    }

}