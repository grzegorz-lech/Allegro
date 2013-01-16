<?php

namespace Shoplo\AllegroBundle\Authentication\Handler;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Shoplo\AllegroBundle\Entity\User;

class Login implements AuthenticationSuccessHandlerInterface
{
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        /** @var $user User */
        $user     = $token->getUser();
        $response = new RedirectResponse($this->router->generate('shoplo_allegro_homepage'));

        if (!$user->getUsername()) {
            $response->setTargetUrl($this->router->generate('shoplo_allegro_settings'));
        }

        return $response;
    }
}
