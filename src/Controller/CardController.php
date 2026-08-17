<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Card;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CardController extends AbstractController
{
    #[Route('/card/{id}', name: 'app_card')]
    public function index(
        #[MapEntity(expr: 'repository.getCard(id)')] Card $card,
    ): Response {
        return $this->render('card/index.html.twig', [
            'card' => $card,
        ]);
    }
}
