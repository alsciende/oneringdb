<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\CardRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cards:search',
    description: 'Search cards based on a query',
)]
class CardsSearchCommand extends Command implements LoggerAwareInterface
{
    use CardTableTrait;
    use LoggerAwareTrait;

    private SymfonyStyle $io;

    public function __construct(
        private readonly CardRepository $cardRepository,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function getStyle(): SymfonyStyle
    {
        return $this->io;
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('query', InputArgument::REQUIRED, 'Search query')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $query = $input->getArgument('query');

        $this->io->title('Query: ' . $query);

        $cards = $this->cardRepository->search($query)->getQuery()->getResult();

        $this->printCards($cards);

        return Command::SUCCESS;
    }
}
