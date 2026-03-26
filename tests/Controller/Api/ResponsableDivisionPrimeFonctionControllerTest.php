<?php

namespace App\Tests\Controller\Api;

use App\Entity\Category;
use App\Entity\Division;
use App\Entity\Employee;
use App\Entity\EmployeeSituation;
use App\Entity\PeriodePaie;
use App\Entity\PrimeFonction;
use App\Entity\Service;
use App\Entity\User;
use App\Tests\Support\ApiTestCase;

final class ResponsableDivisionPrimeFonctionControllerTest extends ApiTestCase
{
    public function testListReturnsOnlyPrimeFonctionInDivisionScope(): void
    {
        $divisionEmail = 'pf.division.validator@example.com';
        $divisionPass = 'pw';
        $divisionValidator = $this->createUser($divisionEmail, $divisionPass, ['ROLE_RESPONSABLE_DIVISION']);

        $otherDivisionValidator = $this->createUser('pf.division.other@example.com', 'pw', ['ROLE_RESPONSABLE_DIVISION']);
        $serviceValidator = $this->createUser('pf.service.any@example.com', 'pw', ['ROLE_RESPONSABLE_SERVICE']);

        $category = $this->createCategory('C1');

        $division1 = $this->createDivision('D1', $divisionValidator);
        $division2 = $this->createDivision('D2', $otherDivisionValidator);

        $service1 = $this->createService('S1', $division1, $serviceValidator);
        $service2 = $this->createService('S2', $division2, $serviceValidator);

        $employee1 = $this->createEmployeeWithSituation($service1, $category, 'mensuelle');
        $employee2 = $this->createEmployeeWithSituation($service2, $category, 'mensuelle');

        $periode = $this->createPeriodePaie('mensuelle');

        $pf1 = $this->createPrimeFonction($employee1, $periode, PrimeFonction::STATUS_SERVICE_VALIDATED);
        $pf2 = $this->createPrimeFonction($employee2, $periode, PrimeFonction::STATUS_SERVICE_VALIDATED);

        $token = $this->loginToken($divisionEmail, $divisionPass);

        $this->client()->request('GET', '/api/responsable/division/prime-fonction?typePaie=mensuelle&status=service_validated', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $payload = json_decode((string) $this->client()->getResponse()->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['items'] ?? null);

        $ids = array_map(static fn (array $row) => (int) ($row['id'] ?? 0), $payload['items']);

        $this->assertContains($pf1->getId(), $ids);
        $this->assertNotContains($pf2->getId(), $ids);
    }

    public function testBatchValidateAndRetourWorkInScopeAndSkipOutOfScope(): void
    {
        $divisionEmail = 'pf.division.validator2@example.com';
        $divisionPass = 'pw';
        $divisionValidator = $this->createUser($divisionEmail, $divisionPass, ['ROLE_RESPONSABLE_DIVISION']);

        $otherDivisionValidator = $this->createUser('pf.division.other2@example.com', 'pw', ['ROLE_RESPONSABLE_DIVISION']);
        $serviceValidator = $this->createUser('pf.service.any2@example.com', 'pw', ['ROLE_RESPONSABLE_SERVICE']);

        $category = $this->createCategory('C1');

        $division1 = $this->createDivision('D1', $divisionValidator);
        $division2 = $this->createDivision('D2', $otherDivisionValidator);

        $service1 = $this->createService('S1', $division1, $serviceValidator);
        $service2 = $this->createService('S2', $division2, $serviceValidator);

        $employee1 = $this->createEmployeeWithSituation($service1, $category, 'mensuelle');
        $employee2 = $this->createEmployeeWithSituation($service2, $category, 'mensuelle');

        $periode = $this->createPeriodePaie('mensuelle');

        $pfIn = $this->createPrimeFonction($employee1, $periode, PrimeFonction::STATUS_SERVICE_VALIDATED);
        $pfOut = $this->createPrimeFonction($employee2, $periode, PrimeFonction::STATUS_SERVICE_VALIDATED);

        $token = $this->loginToken($divisionEmail, $divisionPass);

        // Validate (service_validated -> division_validated)
        $this->client()->request(
            'POST',
            '/api/responsable/division/prime-fonction/batch/validate',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['ids' => [$pfIn->getId(), $pfOut->getId()]], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client()->getResponse()->getContent(), true);
        $this->assertSame([$pfIn->getId()], $payload['validated'] ?? null);

        $this->em()->clear();
        $reloadedIn = $this->em()->getRepository(PrimeFonction::class)->find($pfIn->getId());
        $this->assertSame(PrimeFonction::STATUS_DIVISION_VALIDATED, $reloadedIn?->getStatus());

        // Retour service (service_validated -> submitted)
        $reloadedIn?->setStatus(PrimeFonction::STATUS_SERVICE_VALIDATED);
        $this->em()->flush();

        $this->client()->request(
            'POST',
            '/api/responsable/division/prime-fonction/batch/retour',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['ids' => [$pfIn->getId(), $pfOut->getId()]], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();

        $this->em()->clear();
        $afterRetourIn = $this->em()->getRepository(PrimeFonction::class)->find($pfIn->getId());
        $afterRetourOut = $this->em()->getRepository(PrimeFonction::class)->find($pfOut->getId());

        $this->assertSame(PrimeFonction::STATUS_SUBMITTED, $afterRetourIn?->getStatus());
        $this->assertSame(PrimeFonction::STATUS_SERVICE_VALIDATED, $afterRetourOut?->getStatus());
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
        $e->setPrenom('Sam');
        $e->setDateNaissance(new \DateTimeImmutable('1994-01-01'));
        $e->setCodeSexe('F');
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

    private function createPrimeFonction(Employee $employee, PeriodePaie $periode, string $status): PrimeFonction
    {
        $pf = new PrimeFonction();
        $pf->setEmployee($employee);
        $pf->setPeriodePaie($periode);
        $pf->setTauxMonetaireFonction(10.0);
        $pf->setNombreJours(2.0);
        $pf->setNoteHierarchique(1.0);
        $pf->setStatus($status);

        $this->em()->persist($pf);
        $this->em()->flush();

        return $pf;
    }
}
