<?php
// src/Controller/PrimePerformanceController.php
namespace App\Controller;

use App\Entity\PrimePerformance;
use App\Form\PrimePerformanceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrimePerformanceController extends AbstractController
{
    #[Route('/prime-performance/new', name: 'prime_performance_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $prime = new PrimePerformance();
        $form  = $this->createForm(PrimePerformanceType::class, $prime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // calcul automatique du montant
            $prime->calculerMontant();

            $em->persist($prime);
            $em->flush();

            $this->addFlash('success', 'Prime de performance enregistrée avec montant : ' . number_format($prime->getMontantPerf(), 2, ',', ' ') . ' MAD');
            return $this->redirectToRoute('prime_performance_list');
        }

        return $this->render('prime_performance/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/prime-performance', name: 'prime_performance_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $primes = $em->getRepository(PrimePerformance::class)->findAll();
        return $this->render('prime_performance/list.html.twig', [
            'primes' => $primes,
        ]);
    }
}
