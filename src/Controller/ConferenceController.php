<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConferenceController extends AbstractController
{
    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function index(): Response
    {
        $html = 
        <<<EOF
            <html>
                <body>
                    <h1>Home page</h1>
                    <img src="/img/young-woman-say-hello-isolated-white-wall.jpg" />
                </body>
            </html>
        EOF;

        return new Response($html);
    }
}
