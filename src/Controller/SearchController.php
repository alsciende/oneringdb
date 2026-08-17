<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\AdvancedCardSearchType;
use App\Form\SimpleCardSearchType;
use App\Repository\CardRepository;
use App\Search\AdvancedCardSearch;
use App\Search\SimpleCardSearch;
use App\Service\SyntaxEncoder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly CardRepository $cardRepository,
        public readonly SyntaxEncoder $encoder,
    ) {
    }

    public function form(): Response
    {
        $search = new SimpleCardSearch();

        $form = $this->createForm(SimpleCardSearchType::class, $search, [
            'action' => $this->generateUrl('app_search_cards'),
            'method' => 'GET',
        ]);

        return $this->render('component/_search_form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/search', name: 'app_search_form')]
    public function advancedSearch(Request $request): Response
    {
        $search = new AdvancedCardSearch();

        $form = $this->createForm(AdvancedCardSearchType::class, $search);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('app_search_cards', [
                'q' => $this->encoder->encode($form->getData()),
            ]);
        }

        return $this->render('search/form.html.twig', [
            'form' => $form,
            'hide_search' => true,
        ]);
    }

    #[Route('/cards', name: 'app_search_cards', methods: ['GET'])]
    public function cards(Request $request): Response
    {
        $search = new SimpleCardSearch();

        $form = $this->createForm(SimpleCardSearchType::class, $search, [
            'action' => $this->generateUrl('app_search_cards'),
            'method' => 'GET',
        ]);

        $form->handleRequest($request);

        return $this->forward('\App\Controller\SearchController::search', [
            'form' => $form,
        ]);
    }

    public function search(FormInterface $form, ?string $title = null): Response
    {
        /** @var SimpleCardSearch $search */
        $search = $form->getData();

        $queryBuilder = $this->cardRepository->search($search->q, $search->sort);
        $adapter = new QueryAdapter($queryBuilder, fetchJoinCollection: false);
        $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            $search->page,
            CardRepository::PAGE_SIZE,
        );

        return $this->render('search/index.html.twig', [
            'form' => $form,
            'pagerfanta' => $pagerfanta,
            'title' => $title ?? 'Search Results',
            'hide_search' => true,
            'single_card' => $pagerfanta->count() === 1,
        ]);
    }
}
