<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\GetCardsByCultureCommand;
use App\Entity\CardTypes\CompanionCard;
use App\Enum\Culture;
use App\Repository\CardRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(GetCardsByCultureCommand::class)]
class GetCardsByCultureCommandTest extends TestCase
{
    public function testExecuteWithACaseInsensitiveCultureNamePrintsMatchingCards(): void
    {
        $card = new CompanionCard()->setId('01001')->setTitle('Rosie Cotton')->setCulture(Culture::Shire);

        $repository = $this->createMock(CardRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with([
                'culture' => Culture::Shire,
            ])
            ->willReturn([$card]);

        $command = new GetCardsByCultureCommand($repository);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'culture' => 'Shire',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Rosie Cotton', $tester->getDisplay());
        $this->assertStringContainsString('[OK] Done!', $tester->getDisplay());
    }

    public function testExecuteWarnsWhenNoCardsMatch(): void
    {
        $repository = $this->createMock(CardRepository::class);
        $repository->method('findBy')->willReturn([]);

        $command = new GetCardsByCultureCommand($repository);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'culture' => 'gondor',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString("No cards found with the provided culture 'gondor'", $tester->getDisplay());
    }
}
