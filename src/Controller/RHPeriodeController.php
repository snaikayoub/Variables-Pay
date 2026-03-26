<?php

// src/Controller/RHPeriodeController.php

namespace App\Controller;

use App\Entity\PeriodePaie;
use App\Form\PeriodePaieType;
use App\Repository\PeriodePaieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rh/periodes')]
class RHPeriodeController extends AbstractController
{
    #[Route('/', name: 'rh_periodes_index')]
    public function index(PeriodePaieRepository $repo, Request $request): Response
    {
        $type = $request->query->get('type', 'mensuelle');
        $periodes = $repo->findBy(['typePaie' => $type], ['annee' => 'DESC', 'mois' => 'DESC', 'quinzaine' => 'ASC']);

        return $this->render('rh/periodes/index.html.twig', [
            'periodes' => $periodes,
            'type' => $type,
        ]);
    }

    #[Route('/edit/{id}', name: 'rh_periodes_edit')]
    public function edit(
        PeriodePaie $periode,
        Request $request,
        EntityManagerInterface $em,
        PeriodePaieRepository $repo
    ): Response {
        $form = $this->createForm(PeriodePaieType::class, $periode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Si statut changé à 'Ouverte', on ferme les autres du même type
            if ($periode->getStatut() === PeriodePaie::STATUT_OUVERT) {
                $repo->fermerAutresPeriodesOuvertes($periode);
            }

            $em->flush();
            $this->addFlash('success', 'Période modifiée avec succès.');
            return $this->redirectToRoute('rh_periodes_index', ['type' => $periode->getTypePaie()]);
        }

        return $this->render('rh/periodes/edit.html.twig', [
            'form' => $form,
            'periode' => $periode,
        ]);
    }
}
