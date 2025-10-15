<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(MessageRepository $messages): Response
    {
        $all = $messages->findBy([], ['id' => 'DESC']); // tu peux changer 'id' par 'createdAt' si tu préfères
        return $this->render('default/home.html.twig', [
            'messages' => $all,
        ]);
    }

    #[Route('/message/{id}/delete', name: 'app_message_delete', methods: ['POST'])]
    public function delete(Message $message, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();

        // Vérifie si l'utilisateur est connecté et est l'auteur ou admin
        if ($user && ($this->isGranted('ROLE_ADMIN') || $message->getAuthor() === $user)) {
            $em->remove($message);
            $em->flush();
            $this->addFlash('success', 'Message supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce message.');
        }

        return $this->redirectToRoute('app_home');
    }
}
