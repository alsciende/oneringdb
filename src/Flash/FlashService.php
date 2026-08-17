<?php

declare(strict_types=1);

namespace App\Flash;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

readonly class FlashService
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function addFlash(FlashType $type, string $message): void
    {
        $flashBag = $this->getFlashBag();
        $flashBag->add($type->value, $message);
    }

    private function getFlashBag(): FlashBagInterface
    {
        $session = $this->requestStack->getSession();
        if (! $session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException(sprintf('Cannot get the flashBag because class "%s" doesn\'t implement "%s".', get_debug_type($session), FlashBagAwareSessionInterface::class));
        }

        return $session->getFlashBag();
    }
}
