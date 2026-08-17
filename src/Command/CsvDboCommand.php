<?php

declare(strict_types=1);

namespace App\Command;

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
    name: 'app:csv:dbo',
    description: 'Read and import csv dbo files',
)]
class CsvDboCommand extends Command
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('language', 'l', InputArgument::OPTIONAL, 'Language', 'English');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();
        $slugger = new AsciiSlugger();

        $language = $input->getOption('language');

        $filename = 'Languages';
        $io->section($filename);
        $languages = $this->readFile($this->projectDir . "/data/dbo.{$filename}.csv");
        $io->note('Lines: ' . count($languages));
        $filter = array_filter($languages, fn (array $row): bool => $row['Name'] === $language);
        if (count($filter) === 0) {
            $io->error('Cannot find language ' . $language);

            return Command::FAILURE;
        }
        $languageID = array_first($filter)['ID'];
        $io->note('LanguageID = ' . $languageID);

        $filename = 'TextTypes';
        $io->section($filename);
        $textTypes = $this->readFile($this->projectDir . "/data/dbo.{$filename}.csv");
        $io->note('Lines: ' . count($textTypes));

        $filename = 'Cultures';
        $io->section($filename);
        $cultures = $this->readFile($this->projectDir . "/data/dbo.{$filename}.csv");
        $io->note('Lines: ' . count($cultures));
        $cultures = array_combine(
            array_column($cultures, 'ID'),
            array_column($cultures, 'PostShadowsName')
        );

        $filename = 'Sides';
        $io->section($filename);
        $sides = $this->readFile($this->projectDir . "/data/dbo.{$filename}.csv");
        $io->note('Lines: ' . count($sides));
        $sides = array_combine(
            array_column($sides, 'ID'),
            array_column($sides, 'Name')
        );

        $filename = 'CardTypes';
        $io->section($filename);
        $cardTypes = $this->readFile($this->projectDir . "/data/dbo.{$filename}.csv");
        $io->note('Lines: ' . count($cardTypes));
        $cardTypes = array_combine(
            array_column($cardTypes, 'ID'),
            array_column($cardTypes, 'Name')
        );

        $filename = 'Signets';
        $io->section($filename);
        $signets = $this->readFile($this->projectDir . "/data/dbo.{$filename}.csv");
        $io->note('Lines: ' . count($signets));
        $signets = array_combine(
            array_column($signets, 'ID'),
            array_column($signets, 'Name')
        );

        $filename = 'Sets';
        $io->section($filename);
        $sets = $this->readFile($this->projectDir . "/data/dbo.{$filename}.csv");
        $io->note('Lines: ' . count($sets));
        foreach ($sets as $set) {
            $packID = sprintf('%02d', intval($set['SetNumber']));
            $fs->dumpFile(
                sprintf('%s/fixtures_dbo/packs/%s.json', $this->projectDir, $packID),
                json_encode([
                    'id' => $packID,
                    'name' => $set['Name'],
                    'release_date' => $set['ReleaseDate'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            );
        }
        $sets = array_combine(
            array_column($sets, 'ID'),
            array_map(fn (string $setNumber): string => sprintf('%02d', intval($setNumber)), array_column($sets, 'SetNumber'))
        );

        $filename = 'SubTypes';
        $io->section($filename);
        $subTypes = $this->readFile($this->projectDir . "/data/dbo.{$filename}.csv");
        $io->note('Lines: ' . count($subTypes));
        $subTypes = array_combine(
            array_column($subTypes, 'ID'),
            array_column($subTypes, 'Name')
        );

        $filename = 'CardSubTypes';
        $io->section($filename);
        $cardSubTypes = $this->readFile($this->projectDir . "/data/dbo.{$filename}.csv");
        $io->note('Lines: ' . count($cardSubTypes));

        $filename = 'Cards';
        $io->section($filename);
        $cards = $this->readFile($this->projectDir . "/data/dbo.{$filename}.csv");
        $io->note('Lines: ' . count($cards));

        $io->section('Texts');
        $texts = $this->readFile($this->projectDir . '/data/dbo.Texts.csv');
        $io->note('Lines before language filter: ' . count($texts));
        $texts = array_filter($texts, fn (array $row): bool => $row['LanguageID'] === $languageID);
        $io->note('Lines after language filter: ' . count($texts));

        foreach ($cards as $card) {
            $localTexts = array_filter($texts, fn (array $row): bool => $row['CardID'] === $card['ID']);
            foreach ($textTypes as $textType) {
                $this->addProperty($card, $localTexts, $textType['ID'], $textType['Name']);
            }
            $localCardSubTypes = array_filter($cardSubTypes, fn (array $row): bool => $row['CardID'] === $card['ID']);
            if (count($localCardSubTypes) > 0) {
                $card['SubType'] = $subTypes[array_first($localCardSubTypes)['SubTypeID']];
            }
            if (isset($cultures[$card['CultureID']])) {
                $card['Culture'] = $cultures[$card['CultureID']];
            } else {
                $card['Culture'] = '';
            }
            if (isset($sides[$card['SideID']])) {
                $card['Side'] = $cultures[$card['SideID']];
            } else {
                $card['Side'] = '';
            }
            if (isset($cardTypes[$card['CardTypeID']])) {
                $card['CardType'] = $cardTypes[$card['CardTypeID']];
            } else {
                $card['CardType'] = '';
            }
            if (isset($signets[$card['SignetID']])) {
                $card['Signet'] = $signets[$card['SignetID']];
            } else {
                $card['Signet'] = '';
            }

            $packID = $sets[$card['SetID']];
            $cardID = strtolower($slugger->slug($card['Title'] . ' ' . $card['Subtitle'])->toString());

            $fs->dumpFile(
                sprintf('%s/fixtures_dbo/cards/%s.json', $this->projectDir, $cardID),
                json_encode([
                    'id' => $cardID,
                    'title' => $card['Title'],
                    'subtitle' => $card['Subtitle'] ?: null,
                    'culture' => $card['Culture'] ?: null,
                    'twilight_cost' => intval($card['TwilightCost']),
                    'type' => $card['CardType'],
                    'subtype' => $card['SubType'] ?? '',
                    'text' => $card['Tagged Game Text'] ?? '',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            );

            $fs->dumpFile(
                sprintf('%s/fixtures_dbo/pack_cards/%s/%s.json', $this->projectDir, $packID, $cardID),
                json_encode([
                    'card_id' => $cardID,
                    'pack_id' => $packID,
                    'quantity' => 4,
                    'position' => $card['CardNumber'],
                    'lore' => $card['Lore'] ?? '',
                    'image_url' => sprintf(
                        "https://lotrtcgwiki.com\wiki\_media\cards:lotr%s%s.jpg",
                        $packID,
                        sprintf('%03d', intval($card['CardNumber']))
                    ),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            );

            if ($card['CollectorsInfo'] === '1U91') {
                dump($localTexts);
                dump($localCardSubTypes);
                dump($card);
                //  break;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, string>        $card
     * @param array<array<string, string>> $texts
     */
    private function addProperty(array &$card, array $texts, string $propertyID, string $propertyName): void
    {
        $filter = array_filter($texts, fn (array $row): bool => $row['TextTypeID'] === $propertyID);
        if (count($filter) > 0) {
            $card[$propertyName] = array_first($filter)['Text'];
        }
    }

    /**
     * @return array<array<string, string>>
     *
     * @throws \Throwable
     */
    private function readFile(string $filepath): array
    {
        $stream = fopen($filepath, 'r');
        if ($stream === false) {
            throw new \RuntimeException('Could not open ' . $filepath);
        }

        // transcoding
        stream_filter_append(
            $stream,
            'convert.iconv.WINDOWS-1252/UTF-8',
            STREAM_FILTER_READ,
        );

        $headers = fgetcsv($stream, separator: "\t", escape: '\\');
        if ($headers === false) {
            throw new \RuntimeException('Cannot read headers in file ' . $filepath);
        }

        $data = [];
        while ($line = fgetcsv($stream, separator: "\t", escape: '\\')) {
            try {
                $data[] = array_combine($headers, $line);
            } catch (\Throwable $exception) {
                dump($headers);
                dump($line);
                throw $exception;
            }
        }

        return $data;
    }
}
