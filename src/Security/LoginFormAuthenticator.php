<?php
// src/Security/LoginFormAuthenticator.php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function authenticate(Request $request): Passport
    {
        // Récupérer les champs du formulaire
        $username = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token');

        // Garder le nom d'utilisateur en session en cas d'erreur
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        // Déterminer la redirection selon le rôle
        $roles = $token->getRoleNames();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $route = 'admin'; // route EasyAdmin
        } elseif (in_array('ROLE_RH', $roles, true)) {
            $route = 'rh_dashboard';
        } elseif (in_array('ROLE_RESPONSABLE_DIVISION', $roles, true)) {
            $route = 'responsable_division_dashboard';
        } elseif (in_array('ROLE_RESPONSABLE_SERVICE', $roles, true)) {
            $route = 'responsable_service_dashboard';
        } elseif (in_array('ROLE_GESTIONNAIRE_SERVICE', $roles, true)) {
            $route = 'gestionnaire_dashboard';
        } else {
            $route = 'dashboard_collaborateur';
        }

        return new RedirectResponse($this->urlGenerator->generate($route));
    }

    protected function getLoginUrl(Request $request): string
    {
        // Route du formulaire de login
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
