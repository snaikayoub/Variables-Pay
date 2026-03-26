<?php

// src/Controller/Dashboard/CollaborateurDashboardController.php
namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardCollaborateurController extends AbstractController
{
    #[Route('/mon-espace', name: 'dashboard_collaborateur')]
    public function index(): Response
    {
        return $this->render('dashboard/collaborateur.html.twig');
    }
}
