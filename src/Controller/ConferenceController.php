<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Message\CommentMessage;
use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\Services\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ConferenceController extends AbstractController
{
    public function __construct(
        protected readonly ConferenceRepository $conferenceRepository,
        protected readonly CommentRepository $commentRepository,
        protected readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $bus,
    ) {}

    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function index(): Response
    {

        $conferences = $this->conferenceRepository->findAll();

        return $this->render(
            'conference/index.html.twig',
            [
                'conferences' => $conferences,
            ]
        )->setSharedMaxAge(3600);
    }

    #[Route('/conference_header', name: 'conference_header')]
    public function conferenceHeader(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/header.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ])->setSharedMaxAge(3600);
    }

    #[Route('/conference/{slug}', name: 'conference')]
    public function show(
        Request $request,
        Conference $conference,
        #[Autowire('%photo_dir%')] string $photoDir,
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);

            if ($photo = $form['photo']->getData()) {
                $filename = sprintf(
                    '%s.%s',
                    bin2hex(random_bytes(6)),
                    $photo->guessExtension()
                );
                $photo->move($photoDir, $filename);
                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            // check on spam
            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            $res = $this->bus->dispatch(new CommentMessage($comment->getId(), $context));

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $this->commentRepository->getCommentPaginator($conference, $offset);

        $previous = $offset - CommentRepository::COMMENTS_PER_PAGE;
        $next = min(count($paginator), $offset + CommentRepository::COMMENTS_PER_PAGE);

        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $previous,
            'next' => $next,
            'comment_form' => $form,
        ]);
    }
}
