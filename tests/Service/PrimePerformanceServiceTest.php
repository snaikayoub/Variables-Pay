<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\PrimePerformanceService;
use App\Entity\EmployeeSituation;
use App\Entity\PeriodePaie;
use App\Entity\Employee;
use App\Entity\PrimePerformance;
use App\Entity\CategoryTM;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Workflow\WorkflowInterface;

class PrimePerformanceServiceTest extends TestCase
{
    public function testSubmitPrimeCreatesNewPrime()
    {
        // Mocks for dependencies
        $emMock = $this->createMock(EntityManagerInterface::class);
        $workflowMock = $this->createMock(WorkflowInterface::class);
        $repoMock = $this->createMock(EntityRepository::class);
        $categoryRepoMock = $this->createMock(EntityRepository::class);

        // Mocks for entities
        $employeeMock = $this->createMock(Employee::class);
        
        $situationMock = $this->createMock(EmployeeSituation::class);
        $situationMock->method('getEmployee')->willReturn($employeeMock);
        
        $periodeMock = $this->createMock(PeriodePaie::class);
        $periodeMock->method('getScoreEquipe')->willReturn('1');
        $periodeMock->method('getScoreCollectif')->willReturn('1');

        // Configuration du repository map
        $emMock->method('getRepository')
               ->willReturnCallback(function($entityName) use ($repoMock, $categoryRepoMock) {
                   if ($entityName === PrimePerformance::class) {
                       return $repoMock;
                   }
                   if ($entityName === CategoryTM::class) {
                       return $categoryRepoMock;
                   }

                   throw new \LogicException('Unexpected repository request for: ' . (string) $entityName);
               });

        // Le repository ne trouve pas de prime existante => il renverra null
        $repoMock->method('findOneBy')->willReturn(null);

        // Optionnel : Mock resolveTauxMonetaire en simulant CategoryTM
        $categoryMock = $this->createMock(CategoryTM::class);
        $categoryMock->method('getTM')->willReturn(150.0);
        $categoryRepoMock->method('findOneBy')->willReturn($categoryMock);

        // Vérifier que persist() et flush() sont bien appelés
        $emMock->expects($this->once())->method('persist')->with($this->isInstanceOf(PrimePerformance::class));
        $emMock->expects($this->once())->method('flush');

        // Vérifier le workflow
        $workflowMock->method('can')->willReturn(true);
        $workflowMock->expects($this->once())->method('apply');

        $service = new PrimePerformanceService($emMock, $workflowMock);
        
        $prime = $service->submitPrime($situationMock, $periodeMock, 20.0, 15.0);

        // Assertions finales
        $this->assertInstanceOf(PrimePerformance::class, $prime);
        $this->assertEquals(PrimePerformance::STATUS_DRAFT, $prime->getStatus());
        $this->assertEquals(20.0, $prime->getJoursPerf());
        $this->assertEquals(15.0, $prime->getNoteHierarchique());
        $this->assertEquals(150.0, $prime->getTauxMonetaire());
    }
}
