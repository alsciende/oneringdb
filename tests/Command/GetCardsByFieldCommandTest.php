<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\GetCardsByFieldCommand;
use App\Entity\CardTypes\CompanionCard;
use App\Entity\PublishedSet;
use App\Enum\Culture;
use App\Enum\Rarity;
use App\Enum\Type;
use App\Repository\CardRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(GetCardsByFieldCommand::class)]
class GetCardsByFieldCommandTest extends TestCase
{
    private function createCommand(CardRepository $repository): GetCardsByFieldCommand
    {
        $command = new GetCardsByFieldCommand($repository);
        $command->setLogger(new NullLogger());

        return $command;
    }

    public function testExecuteRejectsAnUnknownField(): void
    {
        $command = $this->createCommand($this->createMock(CardRepository::class));
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'field' => 'unknown',
        ]);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString('Invalid field provided.', $tester->getDisplay());
    }

    public function testExecuteWithExplicitValuesSkipsTheDefaultPopulation(): void
    {
        $card = new CompanionCard()->setId('01001')->setTitle('Aragorn');

        $repository = $this->createMock(CardRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with([
                'title' => 'Aragorn',
            ])
            ->willReturn([$card]);
        $repository->expects($this->never())->method('findAll');

        $tester = new CommandTester($this->createCommand($repository));
        $exitCode = $tester->execute([
            'field' => 'title',
            '--value' => ['Aragorn'],
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Aragorn', $tester->getDisplay());
        $this->assertStringContainsString('[OK] Done!', $tester->getDisplay());
    }

    public function testExecuteWithoutValuesForARegularFieldDerivesThemFromExistingCards(): void
    {
        $publishedSet = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);
        $aragorn = new CompanionCard()->setId('01001')->setTitle('Aragorn')->setRarity(Rarity::Rare)->setUnique(true);
        $rosie = new CompanionCard()->setId('01002')->setTitle('Rosie Cotton')->setRarity(Rarity::Common)->setUnique(false);
        $publishedSet->addCard($aragorn)->addCard($rosie);

        $repository = $this->createMock(CardRepository::class);
        $repository->expects($this->once())->method('findAll')->willReturn([$aragorn, $rosie]);
        $repository->method('findBy')->willReturnMap([
            [[
                'title' => 'Aragorn',
            ], [$aragorn]],
            [[
                'title' => 'Rosie Cotton',
            ], [$rosie]],
        ]);

        $tester = new CommandTester($this->createCommand($repository));
        $exitCode = $tester->execute([
            'field' => 'title',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Aragorn', $display);
        $this->assertStringContainsString('Rosie Cotton', $display);
    }

    public function testExecuteWithoutValuesForCultureUsesAllCultureCases(): void
    {
        $card = new CompanionCard()->setId('01002')->setTitle('Rosie Cotton')->setCulture(Culture::Shire);

        $repository = $this->createMock(CardRepository::class);
        $repository->expects($this->never())->method('findAll');
        $repository->method('findBy')->willReturnCallback(
            static fn (array $criteria): array => $criteria === [
                'culture' => Culture::Shire->value,
            ] ? [$card] : []
        );

        $tester = new CommandTester($this->createCommand($repository));
        $exitCode = $tester->execute([
            'field' => 'culture',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Rosie Cotton', $tester->getDisplay());
    }

    public function testExecuteWithoutValuesForTypeUsesAllTypeCases(): void
    {
        $card = new CompanionCard()->setId('01001')->setTitle('Aragorn');

        $repository = $this->createMock(CardRepository::class);
        $repository->expects($this->never())->method('findAll');
        $repository->method('findBy')->willReturnCallback(
            static fn (array $criteria): array => $criteria === [
                'type' => Type::Companion->value,
            ] ? [$card] : []
        );

        $tester = new CommandTester($this->createCommand($repository));
        $exitCode = $tester->execute([
            'field' => 'type',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Aragorn', $tester->getDisplay());
    }

    public function testExecuteWarnsWhenNoCardsMatchAnything(): void
    {
        $repository = $this->createMock(CardRepository::class);
        $repository->method('findAll')->willReturn([]);
        $repository->method('findBy')->willReturn([]);

        $tester = new CommandTester($this->createCommand($repository));
        $exitCode = $tester->execute([
            'field' => 'title',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No cards found matching the criteria.', $tester->getDisplay());
    }
}
