<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\Culture;
use App\Form\SimpleCardSearchType;
use App\Search\SimpleCardSearch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CultureController extends AbstractController
{
    #[Route('/culture/{culture}', name: 'app_culture')]
    public function index(Culture $culture, TranslatorInterface $translator): Response
    {
        return $this->forward('\App\Controller\SearchController::search', [
            'form' => $this->createForm(SimpleCardSearchType::class, new SimpleCardSearch('c:' . $culture->value), [
                'action' => $this->generateUrl('app_search_cards'),
                'method' => 'GET',
            ]),
            'title' => $translator->trans($culture->value, [], 'cultures'),
        ]);
    }
}
