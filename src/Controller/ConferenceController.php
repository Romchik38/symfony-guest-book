<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConferenceController extends AbstractController
{
    public function __construct(
        protected readonly ConferenceRepository $conferenceRepository,
        protected readonly CommentRepository $commentRepository
    ) {}

    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function index(): Response
    {

        return $this->render(
            'conference/index.html.twig',
            [
                'conferences' => $this->conferenceRepository->findAll(),
            ]
        );
    }

    #[Route('/conference/{id}', name: 'conference')]
    public function show(Request $request, Conference $conference): Response
    {
        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $this->commentRepository->getCommentPaginator($conference, $offset);

        $previous = $offset - CommentRepository::COMMENTS_PER_PAGE;
        $next = min(count($paginator), $offset + CommentRepository::COMMENTS_PER_PAGE);

        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $previous,
            'next' => $next,
        ]);
    }
}
