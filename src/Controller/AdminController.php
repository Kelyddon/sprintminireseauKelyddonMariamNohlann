<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/comments', name: 'admin_comments_index', methods: ['GET'])]
    public function comments(CommentRepository $commentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $comments = $commentRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/comment/index.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/admin/users', name: 'admin_users_index', methods: ['GET'])]
    public function users(UserRepository $users): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/user/index.html.twig', [
            'users' => $users->findAll(),
        ]);
    }

     #[Route('/admin/users/{id}/roles', name: 'admin_users_update_roles', methods: ['POST'])]
    public function updateRoles(User $user, Request $request, UserRepository $users, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('update_user_roles_' . $user->getId(), (string)$request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_users_index');
        }

        // Récupérer un tableau correctement
        $allowed = ['ROLE_AUTHOR', 'ROLE_ADMIN'];
        $submitted = (array) $request->request->all('roles');
        $newRoles = array_values(array_unique(array_intersect($submitted, $allowed)));

        // Toujours garder ROLE_AUTHOR
        if (!in_array('ROLE_AUTHOR', $newRoles, true)) {
            $newRoles[] = 'ROLE_AUTHOR';
        }

        // ...existing code...
        $adminCount = 0;
        foreach ($users->findAll() as $u) {
            if (in_array('ROLE_ADMIN', $u->getRoles(), true)) {
                $adminCount++;
            }
        }
        $isRemovingAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true) && !in_array('ROLE_ADMIN', $newRoles, true);
        if ($isRemovingAdmin && $adminCount <= 1) {
            $this->addFlash('danger', "Impossible de retirer le dernier administrateur.");
            return $this->redirectToRoute('admin_users_index');
        }

        $user->setRoles($newRoles);
        $em->flush();

        $this->addFlash('success', "Rôles mis à jour.");
        return $this->redirectToRoute('admin_users_index');
    }
    #[Route('/admin/users/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function deleteUser(User $user, Request $request, UserRepository $users, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_user_' . $user->getId(), (string)$request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_users_index');
        }

        $adminCount = 0;
        foreach ($users->findAll() as $u) {
            if (in_array('ROLE_ADMIN', $u->getRoles(), true)) {
                $adminCount++;
            }
        }
        if (in_array('ROLE_ADMIN', $user->getRoles(), true) && $adminCount <= 1) {
            $this->addFlash('danger', "Impossible de supprimer le dernier administrateur.");
            return $this->redirectToRoute('admin_users_index');
        }

        if ($this->getUser() && $user->getId() === $this->getUser()->getId()) {
            $this->addFlash('danger', "Vous ne pouvez pas supprimer votre propre compte.");
            return $this->redirectToRoute('admin_users_index');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', "Utilisateur supprimé.");
        return $this->redirectToRoute('admin_users_index');
    }
}