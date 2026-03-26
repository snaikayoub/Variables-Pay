<?php
// src/Controller/PeriodePaieController.php
namespace App\Controller;

use App\Entity\PeriodePaie;
use App\Form\PeriodePaieType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PeriodePaieController extends AbstractController
{
    #[Route('/periode-paie/new', name: 'periode_paie_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $pp = new PeriodePaie();
        $form = $this->createForm(PeriodePaieType::class, $pp);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($pp);
            $em->flush();

            $this->addFlash('success', 'Période de paie créée');
            return $this->redirectToRoute('periode_paie_list');
        }

        return $this->render('periode_paie/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/periode-paie', name: 'periode_paie_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $list = $em->getRepository(PeriodePaie::class)->findAll();
        return $this->render('periode_paie/list.html.twig', [
            'periodes' => $list,
        ]);
    }
}
