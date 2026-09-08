<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PublishedSet;
use App\Form\SimpleCardSearchType;
use App\Search\SimpleCardSearch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublishedSetController extends AbstractController
{
    #[Route('/set/{set}', name: 'app_published_set')]
    public function index(PublishedSet $set): Response
    {
        return $this->forward('\App\Controller\SearchController::search', [
            'form' => $this->createForm(SimpleCardSearchType::class, new SimpleCardSearch('p:' . $set->getId()), [
                'action' => $this->generateUrl('app_search_cards'),
                'method' => 'GET',
            ]),
            'title' => $set->getName(),
        ]);
    }
}
