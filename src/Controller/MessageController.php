<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MessageController extends AbstractController
{
    #[Route('/message/add', name: 'app_message_add')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UserRepository $userRepository
    ): Response {
        $message = new Message();
        $form = $this->createForm(MessageFormType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setAuthor($this->getUser());
            $message->setCreatedAt(new \DateTimeImmutable());

            // Gestion de l'image uploadée
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid('msg_') . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('uploads_dir'), $newFilename);
                $message->setImage($newFilename);
            }

            $em->persist($message);
            $em->flush();

            // ✅ Envoi d'un mail à tous les utilisateurs
            $users = $userRepository->findAll();
            foreach ($users as $user) {
                if ($user->getEmail()) {
                    $email = (new Email())
                        ->from('noreply@monsite.test')
                        ->to($user->getEmail())
                        ->subject('Nouveau message publié')
                        ->text("Un nouveau message vient d'être publié par {$message->getAuthor()->getUsername()} :\n\n{$message->getContent()}");

                    $mailer->send($email);
                }
            }

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

        // Vérifie que seul l'auteur peut modifier
        if ($message->getAuthor() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres messages.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(MessageFormType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Supprime l'ancienne image si elle existe
                if ($message->getImage()) {
                    $oldPath = $this->getParameter('uploads_dir') . '/' . $message->getImage();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $newFilename = uniqid('msg_') . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('uploads_dir'), $newFilename);
                $message->setImage($newFilename);
            }

            $em->flush();
            $this->addFlash('success', 'Message modifié avec succès.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('message/edit.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
        ]);
    }
}
