<?php

namespace App\Tests\Controller\Api;

use App\Entity\Category;
use App\Entity\Division;
use App\Entity\Employee;
use App\Entity\EmployeeSituation;
use App\Entity\PeriodePaie;
use App\Entity\Service;
use App\Entity\User;
use App\Entity\VoyageDeplacement;
use App\Tests\Support\ApiTestCase;

final class VoyageTransitionsTest extends ApiTestCase
{
    public function testServiceRejectAndRetourGestionnaireTransitions(): void
    {
        $serviceEmail = 'voy.service.validator@example.com';
        $servicePass = 'pw';
        $serviceValidator = $this->createUser($serviceEmail, $servicePass, ['ROLE_RESPONSABLE_SERVICE']);

        $divisionValidator = $this->createUser('voy.div.validator@example.com', 'pw', ['ROLE_RESPONSABLE_DIVISION']);
        $category = $this->createCategory('C1');

        $division = $this->createDivision('D1', $divisionValidator);
        $service = $this->createService('S1', $division, $serviceValidator);
        $employee = $this->createEmployeeWithSituation($service, $category, 'mensuelle');
        $periode = $this->createPeriodePaie('mensuelle');

        $voyage = $this->createVoyage($employee, $periode, VoyageDeplacement::STATUS_SUBMITTED);

        $token = $this->loginToken($serviceEmail, $servicePass);

        // reject: submitted -> rejected
        $this->client()->request(
            'POST',
            '/api/responsable/service/voyages/batch/reject',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['ids' => [$voyage->getId()]], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();

        $this->em()->clear();
        $rejected = $this->em()->getRepository(VoyageDeplacement::class)->find($voyage->getId());
        $this->assertSame(VoyageDeplacement::STATUS_REJECTED, $rejected?->getStatus());

        // retour_gestionnaire: rejected -> draft
        $this->client()->request(
            'POST',
            '/api/responsable/service/voyages/batch/retour',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['ids' => [$voyage->getId()]], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();

        $this->em()->clear();
        $draft = $this->em()->getRepository(VoyageDeplacement::class)->find($voyage->getId());
        $this->assertSame(VoyageDeplacement::STATUS_DRAFT, $draft?->getStatus());
    }

    public function testDivisionRetourServiceTransition(): void
    {
        $divisionEmail = 'voy.division.validator@example.com';
        $divisionPass = 'pw';
        $divisionValidator = $this->createUser($divisionEmail, $divisionPass, ['ROLE_RESPONSABLE_DIVISION']);

        $serviceValidator = $this->createUser('voy.service.validator2@example.com', 'pw', ['ROLE_RESPONSABLE_SERVICE']);
        $category = $this->createCategory('C1');

        $division = $this->createDivision('D1', $divisionValidator);
        $service = $this->createService('S1', $division, $serviceValidator);
        $employee = $this->createEmployeeWithSituation($service, $category, 'mensuelle');
        $periode = $this->createPeriodePaie('mensuelle');

        $voyage = $this->createVoyage($employee, $periode, VoyageDeplacement::STATUS_SERVICE_VALIDATED);

        $token = $this->loginToken($divisionEmail, $divisionPass);

        // retour_service: service_validated -> submitted
        $this->client()->request(
            'POST',
            '/api/responsable/division/voyages/batch/retour',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['ids' => [$voyage->getId()]], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();

        $this->em()->clear();
        $submitted = $this->em()->getRepository(VoyageDeplacement::class)->find($voyage->getId());
        $this->assertSame(VoyageDeplacement::STATUS_SUBMITTED, $submitted?->getStatus());
    }

    private function createCategory(string $name): Category
    {
        $c = new Category();
        $c->setCategoryName($name);
        $this->em()->persist($c);
        $this->em()->flush();

        return $c;
    }

    private function createDivision(string $name, User $divisionValidator): Division
    {
        $d = new Division();
        $d->setNom($name);
        $d->setValidateurDivision($divisionValidator);
        $this->em()->persist($d);
        $this->em()->flush();

        return $d;
    }

    private function createService(string $name, Division $division, User $serviceValidator): Service
    {
        $s = new Service();
        $s->setNom($name);
        $s->setDivision($division);
        $s->setValidateurService($serviceValidator);
        $this->em()->persist($s);
        $this->em()->flush();

        return $s;
    }

    private function createEmployeeWithSituation(Service $service, Category $category, string $typePaie): Employee
    {
        $e = new Employee();
        $e->setMatricule('M' . bin2hex(random_bytes(3)));
        $e->setNom('Doe');
        $e->setPrenom('Kim');
        $e->setDateNaissance(new \DateTimeImmutable('1990-01-01'));
        $e->setCodeSexe('M');
        $e->setCin('CIN' . bin2hex(random_bytes(3)));
        $e->setDateEmbauche(new \DateTimeImmutable('2020-01-01'));

        $this->em()->persist($e);

        $s = new EmployeeSituation();
        $s->setEmployee($e);
        $s->setService($service);
        $s->setCategory($category);
        $s->setStartDate(new \DateTimeImmutable('-1 day'));
        $s->setEndDate(null);
        $s->setNatureChangement('Affectation');
        $s->setGrade('G1');
        $s->setSitFamiliale('CELIB');
        $s->setEnf(0);
        $s->setEnfCharge(0);
        $s->setTypePaie($typePaie);

        $this->em()->persist($s);
        $this->em()->flush();

        return $e;
    }

    private function createPeriodePaie(string $typePaie): PeriodePaie
    {
        $p = new PeriodePaie();
        $p->setTypePaie($typePaie);
        $p->setMois(1);
        $p->setAnnee(2026);
        $p->setStatut(PeriodePaie::STATUT_OUVERT);
        $p->setScoreEquipe('100.00');
        $p->setScoreCollectif('100.00');

        $this->em()->persist($p);
        $this->em()->flush();

        return $p;
    }

    private function createVoyage(Employee $employee, PeriodePaie $periode, string $status): VoyageDeplacement
    {
        $v = new VoyageDeplacement();
        $v->setEmployee($employee);
        $v->setPeriodePaie($periode);
        $v->setModeTransport('car');
        $v->setDateHeureDepart(new \DateTimeImmutable('-2 hours'));
        $v->setDateHeureRetour(new \DateTimeImmutable('-1 hour'));
        $v->setDistanceKm(10);
        $v->setStatus($status);
        $v->setCreatedAt(new \DateTimeImmutable('-3 hours'));

        $this->em()->persist($v);
        $this->em()->flush();

        return $v;
    }
}
