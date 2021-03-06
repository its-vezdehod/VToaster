<?php

namespace vezdehod\toaster\queue;

use pocketmine\player\Player;
use vezdehod\toaster\Toast;

class QueuedToast {

    public function __construct(
        private Toast  $toast,
        private string $header,
        private string $message,
        private bool   $silent
    ) {

    }

    public function getToast(): Toast {
        return $this->toast;
    }

    public function getHeader(): string {
        return $this->header;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function isSilent(): bool {
        return $this->silent;
    }

    public function send(Player $player): void {
        if ($this->silent) {
            $this->toast->sendSilent($player, $this->header, $this->message);
        } else {
            $this->toast->send($player, $this->header, $this->message);
        }


    }
}