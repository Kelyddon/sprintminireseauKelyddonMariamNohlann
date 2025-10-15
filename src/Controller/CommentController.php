<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Message;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CommentController extends AbstractController
{
    #[Route('/message/{id}/comment', name: 'app_comment_add', methods: ['POST'])]
    public function add(Message $message, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('comment_add_' . $message->getId(), $token)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
        }

        $content = trim((string) $request->request->get('content'));
        if ($content === '') {
            $this->addFlash('danger', 'Le commentaire ne peut pas être vide.');
            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
        }

        $comment = new Comment();
        $comment
            ->setContent($content)
            ->setCreatedAt(new DateTimeImmutable())
            ->setAuthor($this->getUser())
            ->setMessage($message);

        $em->persist($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire ajouté avec succès.');

        // Retour à la page précédente (show du message)
        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
    }

    // ...existing code...
    #[Route('/comment/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Autorisé si auteur du commentaire ou admin
        if (!$this->isGranted('ROLE_ADMIN') && $comment->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce commentaire.');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_comment_' . $comment->getId(), $token)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
        }

        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé.');
        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
    }
}