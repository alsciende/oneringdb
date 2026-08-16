<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Pack;
use App\Form\SimpleCardSearchType;
use App\Search\SimpleCardSearch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PackController extends AbstractController
{
    #[Route('/set/{pack}', name: 'app_pack')]
    public function index(Pack $pack): Response
    {
        return $this->forward('\App\Controller\SearchController::search', [
            'form' => $this->createForm(SimpleCardSearchType::class, new SimpleCardSearch('p:' . $pack->getId()), [
                'action' => $this->generateUrl('app_search_cards'),
                'method' => 'GET',
            ]),
            'title' => $pack->getName(),
        ]);
    }
}
