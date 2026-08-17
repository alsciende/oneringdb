<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Pack;
use App\Repository\PackCardRepository;
use App\Repository\PackRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'app:json:export',
    description: 'Export card date to json files',
)]
class JsonExportCommand extends Command
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly PackRepository $packRepository,
        private readonly PackCardRepository $packCardRepository,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('set', InputArgument::REQUIRED, 'Set ID');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();
        $slugger = new AsciiSlugger();

        $setId = $input->getArgument('set');
        $pack = $this->packRepository->find($setId);
        if (! $pack instanceof Pack) {
            throw new \RuntimeException('Cannot find pack ' . $setId);
        }

        $dir = $this->projectDir . '/fixtures/cards/';

        $packCards = $this->packCardRepository->findBy([
            'pack' => $pack,
        ]);

        $progress = $io->createProgressBar();
        $progress->start();
        foreach ($packCards as $packCard) {
            $card = $packCard->getCard();
            $card->setId(strtolower($slugger->slug($card->getFullTitle())->toString()));
            $filename = sprintf('%s/fixtures/cards/%s.json', $this->projectDir, $card->getId());
            if (! $fs->exists($filename)) {
                $fs->dumpFile(
                    $filename,
                    json_encode($card->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                );
            }

            $filename = sprintf(
                '%s/fixtures/pack_cards/%s/%s.json',
                $this->projectDir,
                $packCard->getPack()->getId(),
                $card->getId()
            );
            if (! $fs->exists($filename)) {
                $fs->dumpFile(
                    $filename,
                    json_encode($packCard->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                );
            }

            $progress->advance();
        }
        $progress->finish();
        $progress->clear();

        $io->success('All done');

        return Command::SUCCESS;
    }
}
