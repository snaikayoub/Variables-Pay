<?php
// src/Command/LoadTestDataCommand.php
namespace App\Command;

use App\Entity\Division;
use App\Entity\Employee;
use App\Entity\EmployeeSituation;
use App\Entity\Service;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\GrpPerf;
use App\Entity\CategoryTM;
use App\Entity\CategorieFonction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:load-test-data',
    description: 'Crée 2 admins, 5 RH, gestionnaires & validateurs uniques par service, validateurs division, catégories, groupes performance et charge 10 divisions avec services, employés et situations.'
)]
class LoadTestDataCommand extends Command
{
    protected static $defaultName        = 'app:load-test-data';
    protected static $defaultDescription = 'Populate DB with test users (admins, RH, gestionnaires, validateurs), categories, group performances, divisions/services, employees and situations.';

    private EntityManagerInterface      $em;
    private UserPasswordHasherInterface $hasher;

    public function __construct(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
        $this->em     = $em;
        $this->hasher = $hasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plainPassword = 'ocp';

        // 1) Créer les categories professionnelles (extensible)
        // Demande: du bas vers le haut
        $categoryNames = ['OE/PC', 'OE/GC', 'TAMCA/PM', 'TAMCA/GM', 'AP'];
        $categories = [];
        foreach ($categoryNames as $catName) {
            $category = new Category();
            $category->setCategoryName($catName);
            $this->em->persist($category);
            $categories[] = $category;
        }
        $output->writeln('→ ' . count($categories) . ' catégories créées.');

        // 1bis) Créer les categories de fonction (CF1/CF2/CF3)
        $fonctionCatsSeed = [
            ['code' => 'CF1', 'tm' => 10],
            ['code' => 'CF2', 'tm' => 20],
            ['code' => 'CF3', 'tm' => 30],
        ];
        $fonctionCats = [];
        foreach ($fonctionCatsSeed as $row) {
            $cf = new CategorieFonction();
            $cf->setCode($row['code'])
               ->setTauxMonetaire((float) $row['tm']);
            $this->em->persist($cf);
            $fonctionCats[] = $cf;
        }
        $output->writeln('→ ' . count($fonctionCats) . ' categories de fonction créées.');

        // 2) Créer les groupes de performance
        $grpPerfNames = ['Groupe1', 'Groupe2', 'Groupe3'];
        $grpPerfs = [];
        foreach ($grpPerfNames as $grpName) {
            $grpPerf = new GrpPerf();
            $grpPerf->setNameGrp($grpName);
            $this->em->persist($grpPerf);
            $grpPerfs[] = $grpPerf;
        }
        $output->writeln('→ ' . count($grpPerfs) . ' groupes de performance créés.');

        // 3) Créer les taux monétaires par catégorie et groupe (CategoryTM)
        // Plage demandee: entre 5 et 30
        $categoryTMs = [];
        foreach ($categories as $category) {
            foreach ($grpPerfs as $grpPerf) {
                $TM = new CategoryTM();
                $TM->setCategory($category)
                    ->setGrpPerf($grpPerf)
                    ->setTM(rand(5, 30)); // Taux entre 5 et 30
                $this->em->persist($TM);
                $categoryTMs[] = $TM;
            }
        }
        $output->writeln('→ ' . count($categoryTMs) . ' taux de catégories créés.');

        // Flush des catégories et groupes
        $this->em->flush();

        // 4) Admins
        $admins = [];
        for ($i = 1; $i <= 2; $i++) {
            $u = new User();
            $u->setEmail("admin{$i}@ocp.com")
                ->setFullName("Admin {$i}")
                ->setRoles(['ROLE_ADMIN']);
            $u->setPassword($this->hasher->hashPassword($u, $plainPassword));
            $this->em->persist($u);
            $admins[] = $u;
            $output->writeln("→ Admin créé : admin{$i}@ocp.com");
        }

        // 5) RH
        $rhs = [];
        for ($i = 1; $i <= 5; $i++) {
            $u = new User();
            $u->setEmail("rh{$i}@ocp.com")
                ->setFullName("RH {$i}")
                ->setRoles(['ROLE_RH']);
            $u->setPassword($this->hasher->hashPassword($u, $plainPassword));
            $this->em->persist($u);
            $rhs[] = $u;
            $output->writeln("→ RH créé : rh{$i}@ocp.com");
        }

        // 6) Gestionnaires de service (un par service)
        $serviceNames = [
            'Comptabilité',
            'Trésorerie',
            'Recrutement',
            'Formation',
            'Paie',
            'Ligne A',
            'Ligne B',
            'Contrôle',
            'Transport',
            'Entrepôt',
            'Contrôle Qualité',
            'Mécanique',
            'Électrique',
            'Support',
            'Développement',
            'Réseau',
            'Digital',
            'Communication',
            'Régional',
            'Export',
            'Innovation',
            'Projets'
        ];
        $gestionnaires = [];
        foreach ($serviceNames as $idx => $name) {
            $num = $idx + 1;
            $u = new User();
            $u->setEmail("gest{$num}@ocp.com")
                ->setFullName("Gestionnaire {$num}")
                ->setRoles(['ROLE_GESTIONNAIRE_SERVICE']);
            $u->setPassword($this->hasher->hashPassword($u, $plainPassword));
            $this->em->persist($u);
            $gestionnaires[] = $u;
        }
        $output->writeln('→ ' . count($gestionnaires) . ' gestionnaires créés.');

        // 7) Validateurs service (un par service)
        $validatorsService = [];
        foreach ($serviceNames as $idx => $name) {
            $num = $idx + 1;
            $u = new User();
            $u->setEmail("valserv{$num}@ocp.com")
                ->setFullName("ValService {$num}")
                ->setRoles(['ROLE_RESPONSABLE_SERVICE']);
            $u->setPassword($this->hasher->hashPassword($u, $plainPassword));
            $this->em->persist($u);
            $validatorsService[] = $u;
        }
        $output->writeln('→ ' . count($validatorsService) . ' validateurs de service créés.');

        // 8) Validateurs division (10 pour 10 divisions)
        $divisionNames = [
            'Finance',
            'Ressources Humaines',
            'Production',
            'Logistique',
            'Qualité',
            'Maintenance',
            'Informatique',
            'Marketing',
            'Ventes',
            'Recherche & Développement'
        ];
        $validatorsDiv = [];
        foreach ($divisionNames as $idx => $name) {
            $num = $idx + 1;
            $u = new User();
            $u->setEmail("valdiv{$num}@ocp.com")
                ->setFullName("ValDivision {$num}")
                ->setRoles(['ROLE_RESPONSABLE_DIVISION']);
            $u->setPassword($this->hasher->hashPassword($u, $plainPassword));
            $this->em->persist($u);
            $validatorsDiv[] = $u;
        }
        $output->writeln('→ ' . count($validatorsDiv) . ' validateurs de division créés.');

        // Flush initial users
        $this->em->flush();

        // 9) Divisions & Services
        $data = [
            'Finance'                   => ['Comptabilité', 'Trésorerie'],
            'Ressources Humaines'       => ['Recrutement', 'Formation', 'Paie'],
            'Production'                => ['Ligne A', 'Ligne B', 'Contrôle'],
            'Logistique'                => ['Transport', 'Entrepôt'],
            'Qualité'                   => ['Contrôle Qualité'],
            'Maintenance'               => ['Mécanique', 'Électrique'],
            'Informatique'              => ['Support', 'Développement', 'Réseau'],
            'Marketing'                 => ['Digital', 'Communication'],
            'Ventes'                    => ['Régional', 'Export'],
            'Recherche & Développement' => ['Innovation', 'Projets'],
        ];

        // Données pour les employés
        $prenoms = [
            'Mohamed',
            'Ahmed',
            'Youssef',
            'Ali',
            'Amine',
            'Rachid',
            'Omar',
            'Ibrahim',
            'Khalid',
            'Said',
            'Fatima',
            'Aisha',
            'Meryem',
            'Khadija',
            'Samira',
            'Naima',
            'Leila',
            'Amina',
            'Nadia',
            'Souad'
        ];
        $noms = [
            'Alaoui',
            'Bennani',
            'Cherkaoui',
            'Fassi',
            'El Amrani',
            'Idrissi',
            'Berrada',
            'Tazi',
            'Lahlou',
            'Benjelloun',
            'El Mansouri',
            'Ouazzani',
            'Ziani',
            'Belhaj',
            'Chraibi',
            'Tahiri',
            'El Moussa',
            'Benmoussa',
            'Bennis',
            'Amrani'
        ];

        $natureChangements = ['Embauche', 'Promotion', 'Mutation', 'Changement de catégorie', 'Augmentation', 'Titularisation'];
        $grades = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $situationsFamiliales = ['Célibataire', 'Marié', 'Divorcé', 'Veuf'];
        $typesPaie = ['mensuelle', 'quinzaine'];

        $services = [];
        $totalEmployees = 0;
        $totalSituations = 0;
        $srvIdx = 0;

        foreach ($data as $divName => $serviceNames) {
            // Division
            $div = new Division();
            $div->setNom($divName)
                ->setValidateurDivision($validatorsDiv[floor($srvIdx / 3)]);
            $this->em->persist($div);

            foreach ($serviceNames as $srvName) {
                // Service
                $srv = new Service();
                $srv->setNom($srvName)
                    ->setDivision($div)
                    ->addGestionnaire($gestionnaires[$srvIdx])
                    ->setValidateurService($validatorsService[$srvIdx]);
                $this->em->persist($srv);
                $services[] = $srv;

                // Création d'employés pour ce service (entre 3 et 20)
                $nbEmployees = rand(3, 20);
                for ($e = 0; $e < $nbEmployees; $e++) {
                    $employee = new Employee();
                    $matricule = 'E' . str_pad($totalEmployees + 1, 5, '0', STR_PAD_LEFT);
                    $prenom = $prenoms[array_rand($prenoms)];
                    $nom = $noms[array_rand($noms)];

                    // Date naissance (entre 20 et 60 ans)
                    $anneeNaissance = rand(1965, 2005);
                    $dateNaissance = new \DateTime("$anneeNaissance-" . rand(1, 12) . "-" . rand(1, 28));

                    // Date embauche (1 à 20 ans d'ancienneté)
                    $anneeEmbauche = rand(2005, 2024);
                    $dateEmbauche = new \DateTime("$anneeEmbauche-" . rand(1, 12) . "-" . rand(1, 28));

                    // CIN: deux lettres + 6 chiffres
                    $cin = chr(rand(65, 90)) . chr(rand(65, 90)) . rand(100000, 999999);

                    // Attribution d'un groupe de performance aléatoire
                    $randomGrpPerf = $grpPerfs[array_rand($grpPerfs)];

                    // Attribution d'une categorie de fonction (CF1/CF2/CF3)
                    $randomCategorieFonction = $fonctionCats[array_rand($fonctionCats)];

                    $employee->setMatricule($matricule)
                        ->setNom($nom)
                        ->setPrenom($prenom)
                        ->setDateNaissance($dateNaissance)
                        ->setLieuNaissance('Casablanca') // Simplifié
                        ->setCodeSexe(in_array($prenom, ['Fatima', 'Aisha', 'Meryem', 'Khadija', 'Samira', 'Naima', 'Leila', 'Amina', 'Nadia', 'Souad']) ? 'F' : 'M')
                        ->setCin($cin)
                        ->setDateEmbauche($dateEmbauche)
                        ->setAdresse(rand(1, 1000) . ' Rue ' . rand(1, 100) . ', Casablanca')
                        ->setGrpPerf($randomGrpPerf)
                        ->setCategorieFonction($randomCategorieFonction);

                    $this->em->persist($employee);
                    $totalEmployees++;

                    // Création de 1 à 3 situations pour cet employé
                    $nbSituations = rand(1, 3);
                    $startDate = clone $dateEmbauche;

                    for ($s = 0; $s < $nbSituations; $s++) {
                        $situation = new EmployeeSituation();

                        // Configuration de la période
                        $endDate = null;
                        if ($s < $nbSituations - 1) {
                            // Si ce n'est pas la dernière situation, définir une date de fin
                            $intervalleJours = rand(180, 730); // entre 6 mois et 2 ans
                            $endDate = clone $startDate;
                            $endDate->modify("+$intervalleJours days");

                            // La prochaine situation commence après celle-ci
                            $nextStartDate = clone $endDate;
                            $nextStartDate->modify('+1 day');
                            $startDate = $nextStartDate;
                        }

                        // Attribution d'une catégorie aléatoire
                        $randomCategory = $categories[array_rand($categories)];

                        $situation->setEmployee($employee)
                            ->setStartDate($startDate)
                            ->setEndDate($endDate)
                            ->setNatureChangement($natureChangements[array_rand($natureChangements)])
                            ->setGrade($grades[array_rand($grades)])
                            ->setService($srv)
                            ->setSitFamiliale($situationsFamiliales[array_rand($situationsFamiliales)])
                            ->setEnf(rand(0, 5))
                            ->setEnfCharge(rand(0, 3))
                            ->setTauxHoraire(rand(30, 120) + (rand(0, 100) / 100))
                            ->setTypePaie($typesPaie[array_rand($typesPaie)])
                            ->setCategory($randomCategory); // Attribution de la catégorie

                        $this->em->persist($situation);
                        $totalSituations++;
                    }

                    // Flush périodique pour éviter une trop grande consommation mémoire
                    if ($totalEmployees % 50 == 0) {
                        $this->em->flush();
                        $output->writeln("  - Progress: $totalEmployees employés créés, $totalSituations situations...");
                    }
                }

                $output->writeln("→ Service « $srvName » avec $nbEmployees employés créé.");
                $srvIdx++;
            }
            $output->writeln("→ Division « $divName » et ses services créés.");
        }

        // Final flush
        $this->em->flush();

        $output->writeln('<info>Données de test chargées avec succès :</info>');
        $output->writeln("  - " . count($categories) . " Catégories, " . count($grpPerfs) . " Groupes de performance, " . count($categoryTMs) . " Taux de catégories");
        $output->writeln("  - 2 Admins, 5 RH, " . count($gestionnaires) . " Gestionnaires, " . count($validatorsService) . " ValidService, " . count($validatorsDiv) . " ValidDivision");
        $output->writeln("  - " . count($data) . " Divisions, $srvIdx Services");
        $output->writeln("  - $totalEmployees Employés, $totalSituations Situations");

        return Command::SUCCESS;
    }
}
