<?php

declare(strict_types=1);

namespace App\Tests\Flash;

use App\Flash\FlashService;
use App\Flash\FlashType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[CoversClass(FlashService::class)]
class FlashServiceTest extends TestCase
{
    public function testAddFlashAddsTheMessageToTheSessionFlashBag(): void
    {
        $flashBag = new FlashBag();
        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $service = new FlashService($requestStack);
        $service->addFlash(FlashType::Success, 'It worked.');

        $this->assertSame(['It worked.'], $flashBag->get('success'));
    }

    public function testAddFlashThrowsWhenTheSessionIsNotFlashBagAware(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $service = new FlashService($requestStack);

        $this->expectException(\LogicException::class);
        $service->addFlash(FlashType::Danger, 'Oops.');
    }
}
