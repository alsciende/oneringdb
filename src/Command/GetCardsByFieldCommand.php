<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\Culture;
use App\Enum\Type;
use App\Repository\CardRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cards:get',
    description: 'Get a list of cards with some criterias.',
)]
class GetCardsByFieldCommand extends Command implements LoggerAwareInterface
{
    use CardTableTrait;
    use LoggerAwareTrait;

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
            ->addArgument('field', InputArgument::REQUIRED, 'What field to look for.')
            ->addOption(
                'value',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Value for the field'
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $field = strval($input->getArgument('field'));
        $values = $input->getOption('value');

        $this->logger->debug(
            sprintf(
                "field = %s\nvalues = [%s]",
                $field, implode(', ', $values)
            )
        );

        $allowedFields = $this->getCardHeaders();

        if (! in_array($field, $allowedFields, true)) {
            $this->io->error('Invalid field provided.');

            return Command::INVALID;
        }

        if (empty($values)) {
            $this->logger->debug('values is empty');
            if ($field === 'culture') {
                $values = array_map(
                    fn (Culture $culture) => $culture->value,
                    Culture::cases()
                );
            } elseif ($field === 'type') {
                $values = array_map(
                    fn (Type $type) => $type->value,
                    Type::cases()
                );
            } else {
                $values_with_duplicates = [];

                foreach ($this->cardRepository->findAll() as $value) {
                    $values_with_duplicates[] = $value->toArray()[$field];
                }
                $values = array_unique($values_with_duplicates);
            }
        }

        $this->logger->debug('2');
        $cards = [];

        foreach ($values as $value) {
            $this->logger->debug("{$field}, {$value}");
            array_push($cards, ...$this->cardRepository->findBy([
                $field => $value,
            ]));
        }

        $this->logger->debug('3');

        if (empty($cards)) {
            $this->io->warning('No cards found matching the criteria.');
        } else {
            $this->printCards($cards);
            $this->io->success('Done!');
        }

        return Command::SUCCESS;
    }
}
