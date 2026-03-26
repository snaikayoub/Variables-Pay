<?php

namespace App\Tests\Controller\Api;

use App\Entity\Category;
use App\Entity\Division;
use App\Entity\Employee;
use App\Entity\EmployeeSituation;
use App\Entity\PeriodePaie;
use App\Entity\PrimePerformance;
use App\Entity\Service;
use App\Entity\User;
use App\Tests\Support\ApiTestCase;

final class ResponsableServicePrimePerformanceControllerTest extends ApiTestCase
{
    public function testListReturnsOnlyPrimePerformanceInValidatorScope(): void
    {
        $validatorEmail = 'pp.service.validator@example.com';
        $validatorPass = 'pw';

        $serviceValidator = $this->createUser($validatorEmail, $validatorPass, ['ROLE_RESPONSABLE_SERVICE']);
        $otherServiceValidator = $this->createUser('pp.service.other@example.com', 'pw', ['ROLE_RESPONSABLE_SERVICE']);

        $category = $this->createCategory('C1');

        $division1 = $this->createDivision('D1', $this->createUser('pp.div1@example.com', 'pw', ['ROLE_RESPONSABLE_DIVISION']));
        $division2 = $this->createDivision('D2', $this->createUser('pp.div2@example.com', 'pw', ['ROLE_RESPONSABLE_DIVISION']));

        $service1 = $this->createService('S1', $division1, $serviceValidator);
        $service2 = $this->createService('S2', $division2, $otherServiceValidator);

        $employee1 = $this->createEmployeeWithSituation($service1, $category, 'mensuelle');
        $employee2 = $this->createEmployeeWithSituation($service2, $category, 'mensuelle');

        $periode = $this->createPeriodePaie('mensuelle');

        $pp1 = $this->createPrimePerformance($employee1, $periode, PrimePerformance::STATUS_SUBMITTED);
        $pp2 = $this->createPrimePerformance($employee2, $periode, PrimePerformance::STATUS_SUBMITTED);

        $token = $this->loginToken($validatorEmail, $validatorPass);

        $this->client()->request('GET', '/api/responsable/service/prime-performance?typePaie=mensuelle&status=submitted', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $payload = json_decode((string) $this->client()->getResponse()->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['items'] ?? null);

        $ids = array_map(static fn (array $row) => (int) ($row['id'] ?? 0), $payload['items']);

        $this->assertContains($pp1->getId(), $ids);
        $this->assertNotContains($pp2->getId(), $ids);
    }

    public function testBatchValidateSkipsOutOfScopeIds(): void
    {
        $validatorEmail = 'pp.service.validator2@example.com';
        $validatorPass = 'pw';

        $serviceValidator = $this->createUser($validatorEmail, $validatorPass, ['ROLE_RESPONSABLE_SERVICE']);
        $otherServiceValidator = $this->createUser('pp.service.other2@example.com', 'pw', ['ROLE_RESPONSABLE_SERVICE']);

        $category = $this->createCategory('C1');

        $division1 = $this->createDivision('D1', $this->createUser('pp.div3@example.com', 'pw', ['ROLE_RESPONSABLE_DIVISION']));
        $division2 = $this->createDivision('D2', $this->createUser('pp.div4@example.com', 'pw', ['ROLE_RESPONSABLE_DIVISION']));

        $service1 = $this->createService('S1', $division1, $serviceValidator);
        $service2 = $this->createService('S2', $division2, $otherServiceValidator);

        $employee1 = $this->createEmployeeWithSituation($service1, $category, 'mensuelle');
        $employee2 = $this->createEmployeeWithSituation($service2, $category, 'mensuelle');

        $periode = $this->createPeriodePaie('mensuelle');

        $ppIn = $this->createPrimePerformance($employee1, $periode, PrimePerformance::STATUS_SUBMITTED);
        $ppOut = $this->createPrimePerformance($employee2, $periode, PrimePerformance::STATUS_SUBMITTED);

        $token = $this->loginToken($validatorEmail, $validatorPass);

        $this->client()->request(
            'POST',
            '/api/responsable/service/prime-performance/batch/validate',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['ids' => [$ppIn->getId(), $ppOut->getId()]], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();

        $payload = json_decode((string) $this->client()->getResponse()->getContent(), true);
        $this->assertSame([$ppIn->getId()], $payload['validated'] ?? null);

        $this->em()->clear();
        $reloadedIn = $this->em()->getRepository(PrimePerformance::class)->find($ppIn->getId());
        $reloadedOut = $this->em()->getRepository(PrimePerformance::class)->find($ppOut->getId());

        $this->assertSame(PrimePerformance::STATUS_SERVICE_VALIDATED, $reloadedIn?->getStatus());
        $this->assertSame(PrimePerformance::STATUS_SUBMITTED, $reloadedOut?->getStatus());
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
        $e->setPrenom('Alex');
        $e->setDateNaissance(new \DateTimeImmutable('1992-01-01'));
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

    private function createPrimePerformance(Employee $employee, PeriodePaie $periode, string $status): PrimePerformance
    {
        $pp = new PrimePerformance();
        $pp->setEmployee($employee);
        $pp->setPeriodePaie($periode);
        $pp->setTauxMonetaire(150.0);
        $pp->setJoursPerf(2.0);
        $pp->setNoteHierarchique(50.0);
        $pp->setScoreEquipe(100.0);
        $pp->setScoreCollectif(100.0);
        $pp->setStatus($status);

        $this->em()->persist($pp);
        $this->em()->flush();

        return $pp;
    }
}
