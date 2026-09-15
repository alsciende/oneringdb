<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\CardsSearchCommand;
use App\Entity\CardTypes\CompanionCard;
use App\Repository\CardRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CardsSearchCommand::class)]
class CardsSearchCommandTest extends TestCase
{
    public function testExecutePrintsTheMatchingCards(): void
    {
        $card = new CompanionCard()->setId('01001')->setTitle('Aragorn');

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$card]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getQuery')->willReturn($query);

        $repository = $this->createMock(CardRepository::class);
        $repository->expects($this->once())
            ->method('search')
            ->with('t:companion')
            ->willReturn($queryBuilder);

        $command = new CardsSearchCommand($repository);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'query' => 't:companion',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Query: t:companion', $tester->getDisplay());
        $this->assertStringContainsString('Aragorn', $tester->getDisplay());
        $this->assertStringContainsString('1 cards', $tester->getDisplay());
    }
}
