<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(UserRepository $userManager): Response
    {
        // On recuère tout les utilisateurs
        $users = $userManager->findAll();

        return $this->render('admin/index.html.twig', [
            'users' => $users,
        ]);
    }
}
