<?php

namespace Shoplo\AllegroBundle\Authentication\Handler;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Shoplo\AllegroBundle\Entity\User;

class Login implements AuthenticationSuccessHandlerInterface
{
    protected $router;
    protected $security;
    protected $doctrine;

    public function __construct(Router $router, SecurityContext $security, Doctrine $doctrine)
    {
        $this->router   = $router;
        $this->security = $security;
        $this->doctrine = $doctrine;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $response = new RedirectResponse($this->router->generate('shoplo_allegro_homepage'));

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            // Search for user in DB
            $em         = $this->doctrine->getManager();
            $repository = $em->getRepository('ShoploAllegroBundle:User');
            $user       = $repository->findOneBy(array('shopId' => $token->getUsername()));

            if ($user instanceof User) {
                $user->addRole('ROLE_ADMIN', $token, $request->getSession());

                // Remember last access token
                $token = $token->getAccessToken();
                $user->setOauthToken($token['oauth_token']);
                $user->setOauthTokenSecret($token['oauth_token_secret']);
                $em->persist($user);
                $em->flush();
            } else {
                $response->setTargetUrl($this->router->generate('shoplo_allegro_settings'));
            }
        }

        return $response;
    }
}
