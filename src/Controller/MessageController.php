<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
            $message->setAuthor($this->getUser());
            $message->setCreatedAt(new \DateTimeImmutable());

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

    #[Route('/message/{id}/edit', name: 'app_message_edit')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Message $message, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // ✅ Vérifie que l'utilisateur connecté est l'auteur
        if ($message->getAuthor() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres messages.');
            return $this->redirectToRoute('app_home');
        }

        // On utilise le même formulaire que pour la création
        $form = $this->createForm(\App\Form\MessageFormType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush(); // pas besoin de persist, l'objet existe déjà
            $this->addFlash('success', 'Message modifié avec succès.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('message/edit.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
        ]);
    }
}
