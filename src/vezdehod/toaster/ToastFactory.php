<?php

namespace vezdehod\toaster;

use Closure;
use function assert;

class ToastFactory {

    /** @var null|Closure(ToastOptions): Toast */
    private static /*?Closure*/ $factory;
    /** @var ToastOptions[] */
    private static /*?array*/ $toastCreatingRequests = [];


    /**
     * @param Closure(ToastOptions): Toast $factory
     */
    public static function setFactory(Closure $factory)/*: void*/ { self::$factory = $factory; }

    public static function create(ToastOptions $toast): Toast {
        assert(self::$toastCreatingRequests !== null, "You can not register toasts after resource pack being generated!");
        assert(self::$factory !== null, "You can not register toasts before VToaster loaded!");
        self::$toastCreatingRequests[] = $toast;

        return (self::$factory)($toast);
    }

    /**
     * @return ToastOptions[]
     */
    public static function getAndLock(): array {
        try {
            return self::$toastCreatingRequests;
        } finally {
            self::$toastCreatingRequests = null;
            self::$factory = null;
        }
    }
}