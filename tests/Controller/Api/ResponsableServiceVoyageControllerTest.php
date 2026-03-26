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

final class ResponsableServiceVoyageControllerTest extends ApiTestCase
{
    public function testListReturnsOnlyVoyagesInValidatorScope(): void
    {
        $validatorEmail = 'service.validator@example.com';
        $validatorPass = 'pw';

        $otherEmail = 'other.validator@example.com';
        $otherPass = 'pw';

        $serviceValidator = $this->createUser($validatorEmail, $validatorPass, ['ROLE_RESPONSABLE_SERVICE']);
        $otherServiceValidator = $this->createUser($otherEmail, $otherPass, ['ROLE_RESPONSABLE_SERVICE']);

        $category = $this->createCategory('C1');

        $division1 = $this->createDivision('D1', $this->createUser('div1@example.com', 'pw', ['ROLE_RESPONSABLE_DIVISION']));
        $division2 = $this->createDivision('D2', $this->createUser('div2@example.com', 'pw', ['ROLE_RESPONSABLE_DIVISION']));

        $service1 = $this->createService('S1', $division1, $serviceValidator);
        $service2 = $this->createService('S2', $division2, $otherServiceValidator);

        $employee1 = $this->createEmployeeWithSituation($service1, $category, 'mensuelle');
        $employee2 = $this->createEmployeeWithSituation($service2, $category, 'mensuelle');

        $periode = $this->createPeriodePaie('mensuelle');

        $v1 = $this->createVoyage($employee1, $periode, VoyageDeplacement::STATUS_SUBMITTED);
        $v2 = $this->createVoyage($employee2, $periode, VoyageDeplacement::STATUS_SUBMITTED);

        $token = $this->loginToken($validatorEmail, $validatorPass);

        $this->client()->request('GET', '/api/responsable/service/voyages?typePaie=mensuelle&status=submitted', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $payload = json_decode((string) $this->client()->getResponse()->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['items'] ?? null);

        $ids = array_map(static fn (array $row) => (int) ($row['id'] ?? 0), $payload['items']);

        $this->assertContains($v1->getId(), $ids);
        $this->assertNotContains($v2->getId(), $ids);
    }

    public function testBatchValidateSkipsOutOfScopeIds(): void
    {
        $validatorEmail = 'service.validator2@example.com';
        $validatorPass = 'pw';

        $serviceValidator = $this->createUser($validatorEmail, $validatorPass, ['ROLE_RESPONSABLE_SERVICE']);
        $otherServiceValidator = $this->createUser('other.validator2@example.com', 'pw', ['ROLE_RESPONSABLE_SERVICE']);

        $category = $this->createCategory('C1');

        $division1 = $this->createDivision('D1', $this->createUser('div3@example.com', 'pw', ['ROLE_RESPONSABLE_DIVISION']));
        $division2 = $this->createDivision('D2', $this->createUser('div4@example.com', 'pw', ['ROLE_RESPONSABLE_DIVISION']));

        $service1 = $this->createService('S1', $division1, $serviceValidator);
        $service2 = $this->createService('S2', $division2, $otherServiceValidator);

        $employee1 = $this->createEmployeeWithSituation($service1, $category, 'mensuelle');
        $employee2 = $this->createEmployeeWithSituation($service2, $category, 'mensuelle');

        $periode = $this->createPeriodePaie('mensuelle');

        $vInScope = $this->createVoyage($employee1, $periode, VoyageDeplacement::STATUS_SUBMITTED);
        $vOutScope = $this->createVoyage($employee2, $periode, VoyageDeplacement::STATUS_SUBMITTED);

        $token = $this->loginToken($validatorEmail, $validatorPass);

        $this->client()->request(
            'POST',
            '/api/responsable/service/voyages/batch/validate',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['ids' => [$vInScope->getId(), $vOutScope->getId()]], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();

        $payload = json_decode((string) $this->client()->getResponse()->getContent(), true);
        $this->assertSame(2, $payload['processed'] ?? null);
        $this->assertSame([$vInScope->getId()], $payload['validated'] ?? null);
        $this->assertIsArray($payload['skipped'] ?? null);

        $this->em()->clear();
        $reloadedIn = $this->em()->getRepository(VoyageDeplacement::class)->find($vInScope->getId());
        $reloadedOut = $this->em()->getRepository(VoyageDeplacement::class)->find($vOutScope->getId());

        $this->assertSame(VoyageDeplacement::STATUS_SERVICE_VALIDATED, $reloadedIn?->getStatus());
        $this->assertSame(VoyageDeplacement::STATUS_SUBMITTED, $reloadedOut?->getStatus());
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
        $e->setPrenom('John');
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
