<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Card;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\SerializerInterface;

class CardFixtures extends Fixture
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly string $projectDir,
    ) {
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $finder = new Finder();
        $finder->files()->in($this->projectDir . '/fixtures/cards/')->name('*.json');
        foreach ($finder as $file) {
            try {
                $card = $this->serializer->deserialize($file->getContents(), Card::class, 'json');
            } catch (\Exception $exception) {
                throw new \RuntimeException("Cannot deserializer {$file}", previous: $exception);
            }
            $manager->persist($card);
        }

        $manager->flush();
    }
}
