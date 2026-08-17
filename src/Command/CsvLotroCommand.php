<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Card;
use App\Entity\Pack;
use App\Entity\PackCard;
use App\Enum\Culture;
use App\Enum\Type;
use App\Repository\CardRepository;
use App\Repository\PackCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @phpstan-type CardRow array{
 *     Id: string,
 *     "Hex Id": string,
 *     Set: string,
 *     Rarity: string,
 *     Number: string,
 *     "Collectors Info": string,
 *     Unique: string,
 *     Title: string,
 *     Subtitle: string,
 *     Culture: string,
 *     Type: string,
 *     Race: string,
 *     Class: string,
 *     "Twilight Cost": string,
 *     Strength: string,
 *     Vitality: string,
 *     Resistance: string,
 *     "Minion Site Number": string,
 *     "Ally Home Sites": string,
 *     "Site Number": string,
 *     "Site Arrow": string,
 *     Block: string,
 *     Background: string,
 *     Image: string,
 *     "Top Icon": string,
 *     "Top Text": string,
 *     "Middle Icon": string,
 *     "Middle Text": string,
 *     "Bottom Icon": string,
 *     "Bottom Text": string,
 *     Text: string,
 *     Lore: string,
 *     "German Title": string,
 *     "German Subtitle": string,
 *     "German Text": string,
 *     "German Lore": string,
 *     "French Title": string,
 *     "French Subtitle": string,
 *     "French Text": string,
 *     "French Lore": string,
 *     "Italian Title": string,
 *     "Italian Subtitle": string,
 *     "Italian Text": string,
 *     "Italian Lore": string,
 *     "Spanish Title": string,
 *     "Spanish Subtitle": string,
 *     "Spanish Text": string,
 *     "Spanish Lore": string,
 * }
 */
#[AsCommand(
    name: 'app:csv:lotro',
    description: 'Import card data from the LOTRO csv file',
)]
class CsvLotroCommand extends Command
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly CardRepository $cardRepository,
        private readonly PackCardRepository $packCardRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::OPTIONAL, 'CSV filename', 'data/lotro_card_data.csv');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filename = $input->getArgument('filename');

        $fs = new Filesystem();
        if ($fs->isAbsolutePath($filename)) {
            $filename = $fs->makePathRelative($filename, $this->projectDir);
        }

        $stream = fopen($this->projectDir . '/' . $filename, 'r');
        if ($stream === false) {
            throw new \RuntimeException('Could not open ' . $filename);
        }

        // transcoding
        stream_filter_append(
            $stream,
            'convert.iconv.WINDOWS-1252/UTF-8',
            STREAM_FILTER_READ,
        );

        $headers = fgetcsv($stream);
        if ($headers === false) {
            throw new \RuntimeException('Cannot read headers in CSV file');
        }
        $number = 1;

        $progress = $io->createProgressBar();
        $progress->start();
        try {
            while ($line = fgetcsv($stream)) {
                ++$number;
                /** @var CardRow $combine */
                $combine = array_combine($headers, $line);
                try {
                    $this->importLine($combine);
                } catch (\Throwable $exception) {
                    dump($combine);
                    throw $exception;
                }
                $progress->advance();
            }
        } finally {
            $progress->finish();
            fclose($stream);
        }

        $progress->clear();

        return Command::SUCCESS;
    }

    /**
     * @param CardRow $line
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function importLine(array $line): void
    {
        if ($line['Set'] === '0') {
            return;
        }
        if (in_array($line['Rarity'], ['W', 'D'], true)) {
            return;
        }

        $pack = $this->entityManager->find(Pack::class, sprintf('%02d', intval($line['Set'])));

        if (! $pack instanceof Pack) {
            throw new \RuntimeException('Missing pack');
        }

        $card = $this->cardRepository->findOneBy([
            'title' => $line['Title'],
            'subtitle' => $line['Subtitle'] ?: null,
        ]);

        if (! $card instanceof Card) {
            if ($line['Culture'] !== '') {
                $culture = Culture::from(strtolower($line['Culture']));
            } else {
                $culture = null;
            }
            $card = (new Card())
                ->setId($line['Id'])
                ->setCulture($culture)
                ->setTitle($line['Title'])
                ->setSubtitle($line['Subtitle'] ?: null)
                ->setText($line['Text'])
                ->setTwilightCost(intval($line['Twilight Cost']))
                ->setType(Type::from(strtolower($line['Type'])));

            $this->entityManager->persist($card);
        }

        $packCard = $this->packCardRepository->findOneBy([
            'pack' => $pack,
            'card' => $card,
        ]);

        if (! $packCard instanceof PackCard) {
            $packCard = (new PackCard())
                ->setCard($card)
                ->setLore($line['Lore'])
                ->setPack($pack)
                ->setPosition(intval($line['Number']))
                ->setQuantity(4);

            $packCard->setImageUrl(
                sprintf(
                    'https://lotrtcgwiki.com/wiki/_media/cards:lotr%02d%03d.jpg',
                    $pack->getId(),
                    $packCard->getPosition()
                )
            );

            $this->entityManager->persist($packCard);
        }

        $this->entityManager->flush();
    }
}
