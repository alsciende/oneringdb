<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Dto\DtoCard;
use App\Entity\Card;
use App\Entity\PublishedSet;
use App\Enum\Culture;
use App\Enum\Rarity;
use App\Enum\Type;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\SerializerInterface;

class CardFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly string $projectDir,
    ) {
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $publishedSetRepository = $manager->getRepository(PublishedSet::class);

        $finder = new Finder();
        $finder->files()->in($this->projectDir . '/fixtures/cards/')->name('*.json');
        foreach ($finder as $file) {
            try {
                /** @var DtoCard $dto */
                $dto = $this->serializer->deserialize($file->getContents(), DtoCard::class, 'json');
            } catch (\Exception $exception) {
                throw new \RuntimeException("Cannot deserialize {$file}", previous: $exception);
            }

            $setId = sprintf('%02d', (int) $dto->setNumber);
            $publishedSet = $publishedSetRepository->find($setId);
            if ($publishedSet === null) {
                throw new \RuntimeException("Cannot find published set {$setId} for {$file}");
            }

            $type = Type::from(strtolower($dto->type));
            $entityClass = Card::TYPE_ENTITY_CLASSES[$type->value];
            $card = new $entityClass();
            $card->setId(sprintf('%02d%03d', (int) $dto->setNumber, (int) $dto->cardNumber));
            $card->setPublishedSet($publishedSet);
            $card->setTitle($dto->name);
            $card->setSubtitle($dto->subtitle);
            $card->setCulture($dto->culture !== null ? Culture::from(strtolower($dto->culture)) : null);
            $card->setTwilightCost($dto->twilightCost !== null ? (int) $dto->twilightCost : null);
            $card->setText($dto->gameText);
            $card->setLore($dto->flavorText);
            $card->setPosition((int) $dto->cardNumber);
            $card->setImageUrl(sprintf('https://lotrtcgwiki.com/wiki/_media/cards:lotr%s.jpg', $card->getId()));
            $card->setRarity(Rarity::CODES[$dto->rarity] ?? throw new \RuntimeException("Unknown rarity code \"{$dto->rarity}\" in {$file}"));
            $card->setUnique($dto->unique);
            $card->setSubtype($dto->subtype);
            $card->setStrength($dto->strength !== null ? (int) $dto->strength : null);
            $card->setVitality($dto->vitality !== null ? (int) $dto->vitality : null);
            $card->setStrengthModifier($dto->strengthModifier);
            $card->setVitalityModifier($dto->vitalityModifier);
            $card->setSiteNumber($dto->siteNumber !== null ? (int) $dto->siteNumber : null);
            $card->setShadowNumber($dto->shadowNumber !== null ? (int) $dto->shadowNumber : null);
            $card->setSignet($dto->signet);
            $card->setHomeSite($dto->homeSite);
            $card->setSiteNumberModifier($dto->siteNumberModifier);

            $manager->persist($card);
        }

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            PublishedSetFixtures::class,
        ];
    }
}
