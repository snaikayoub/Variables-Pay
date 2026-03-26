<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\CategoryTM;
use App\Entity\CategorieFonction;
use App\Entity\Conge;
use App\Entity\User;
use App\Entity\Service;
use App\Entity\Division;
use App\Entity\Employee;
use App\Entity\PeriodePaie;
use App\Entity\PrimeFonction;
use App\Entity\PrimePerformance;
use App\Entity\EmployeeSituation;
use App\Entity\GrpPerf;
use App\Entity\VoyageDeplacement;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
        // return $this->redirectToRoute('admin_user_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Variables-Pay Administration')
            ->setFaviconPath('favicon.ico')
            ->setTranslationDomain('admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('RH');
        yield MenuItem::linkToCrud('Collaborateurs', 'fa fa-users', Employee::class);
        yield MenuItem::linkToCrud('Situations', 'fa fa-file-alt', EmployeeSituation::class);
        yield MenuItem::linkToCrud('Conges', 'fa fa-plane-departure', Conge::class);
        yield MenuItem::linkToCrud('Voyages / Deplacements', 'fa fa-route', VoyageDeplacement::class);

        yield MenuItem::section('Administration');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user-cog', User::class);

        yield MenuItem::section('Paies');
        yield MenuItem::linkToCrud('Primes de performance', 'fa fa-trophy', PrimePerformance::class);
        yield MenuItem::linkToCrud('Primes de fonction', 'fa fa-id-badge', PrimeFonction::class);
        yield MenuItem::linkToCrud('Périodes de paie', 'fa fa-calendar-alt', PeriodePaie::class);
        yield MenuItem::linkToCrud('Categories professionnelles', 'fa fa-tags', Category::class);
        yield MenuItem::linkToCrud('Categories de fonction', 'fa fa-id-card', CategorieFonction::class);
        yield MenuItem::linkToCrud('Groupes Performances', 'fa fa-calendar-alt', GrpPerf::class);
        yield MenuItem::linkToCrud('CategoryTM', 'fa fa-calendar-alt', CategoryTM::class);

        yield MenuItem::section('Organisation');
        yield MenuItem::linkToCrud('Divisions', 'fa fa-building', Division::class);
        yield MenuItem::linkToCrud('Services', 'fa fa-concierge-bell', Service::class);


        yield MenuItem::section('Navigation');
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-globe', 'home');
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-sign-out');
    }
}
