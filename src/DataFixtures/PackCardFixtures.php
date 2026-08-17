<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Dto\DtoPackCard;
use App\Entity\Card;
use App\Entity\Pack;
use App\Entity\PackCard;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\SerializerInterface;

class PackCardFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly string $projectDir,
    ) {
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $cardRepository = $manager->getRepository(Card::class);
        $packRepository = $manager->getRepository(Pack::class);

        $finder = new Finder();
        $finder->files()->in($this->projectDir . '/fixtures/pack_cards/')->name('*.json');
        foreach ($finder as $file) {
            /** @var DtoPackCard $dto */
            $dto = $this->serializer->deserialize($file->getContents(), DtoPackCard::class, 'json');
            $packCard = new PackCard();
            $packCard->setCard($cardRepository->find($dto->cardId));
            $packCard->setPack($packRepository->find($dto->packId));
            $packCard->setLore($dto->lore);
            $packCard->setPosition($dto->position);
            $packCard->setImageUrl($dto->imageUrl);
            $packCard->setQuantity($dto->quantity);
            $manager->persist($packCard);
        }

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            CardFixtures::class,
            PackFixtures::class,
        ];
    }
}
