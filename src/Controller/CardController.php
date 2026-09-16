<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Card;
use App\Repository\CardRepository;
use App\Service\RulesetService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CardController extends AbstractController
{
    #[Route('/card/{id}', name: 'app_card')]
    public function index(
        #[MapEntity(expr: 'repository.getCard(id)')] Card $card,
        CardRepository $cardRepository,
        RulesetService $rulesetService,
    ): Response {
        return $this->render('card/index.html.twig', [
            'card' => $card,
            'previousCard' => $cardRepository->findPreviousCard($card),
            'nextCard' => $cardRepository->findNextCard($card),
            'rulesetHistory' => $rulesetService->findRulesetHistory($card),
        ]);
    }

    #[Route('/card/{id}/modal', name: 'app_card_modal')]
    public function modal(
        #[MapEntity(expr: 'repository.getCard(id)')] Card $card,
    ): Response {
        return $this->render('card/_modal.html.twig', [
            'card' => $card,
        ]);
    }
}
