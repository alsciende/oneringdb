<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\Culture;
use App\Repository\CardRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cards:get:culture',
    description: 'Returns a list of cards whose culture matches the argument',
)]
class GetCardsByCultureCommand extends Command
{
    use CardTableTrait;

    private SymfonyStyle $io;

    public function __construct(
        public CardRepository $cardRepository
    ) {
        parent::__construct();
    }

    public function getStyle(): SymfonyStyle
    {
        return $this->io;
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('culture', InputArgument::REQUIRED, 'What field to look for.')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $cultureName = $input->getArgument('culture');

        $culture = Culture::from(strtolower((string) $cultureName));
        $cards = $this->cardRepository->findBy([
            'culture' => $culture,
        ]);

        if (empty($cards)) {
            $this->io->warning("No cards found with the provided culture '{$cultureName}'");
        } else {
            $this->printCards($cards);
            $this->io->success('Done!');
        }

        return Command::SUCCESS;
    }
}
