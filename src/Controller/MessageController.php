<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MessageController extends AbstractController
{
    #[Route('/message/add', name: 'app_message_add')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $message = new Message();
        $form = $this->createForm(MessageFormType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer l’auteur si la méthode/propriété existe (author ou user)
            if (method_exists($message, 'setAuthor')) {
                $message->setAuthor($this->getUser());
            } elseif (method_exists($message, 'setUser')) {
                $message->setUser($this->getUser());
            }

            // Date de création si supportée par l’entité
            if (method_exists($message, 'getCreatedAt') && null === $message->getCreatedAt() && method_exists($message, 'setCreatedAt')) {
                $message->setCreatedAt(new \DateTimeImmutable());
            }

            $em->persist($message);
            $em->flush();

            $this->addFlash('success', 'Message publié avec succès.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('message/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/message/{id}', name: 'app_message_show', requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function show(Message $message): Response
    {
        return $this->render('message/show.html.twig', [
            'message' => $message,
        ]);
    }
}