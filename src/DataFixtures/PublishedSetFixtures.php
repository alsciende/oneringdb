<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\PublishedSet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\SerializerInterface;

class PublishedSetFixtures extends Fixture
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
        $finder->files()->in($this->projectDir . '/fixtures/published_sets/')->name('*.json');
        foreach ($finder as $file) {
            $publishedSet = $this->serializer->deserialize($file->getContents(), PublishedSet::class, 'json');
            $manager->persist($publishedSet);
        }

        $manager->flush();
    }
}
