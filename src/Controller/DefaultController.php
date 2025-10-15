<?php

namespace App\Controller;

use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(MessageRepository $messages): Response
    {
        $all = $messages->findBy([], ['id' => 'DESC']); // adapte si tu as createdAt
        return $this->render('default/home.html.twig', [
            'messages' => $all,
        ]);
    }
}