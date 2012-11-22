<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;

class HomepageController extends Controller
{
    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function indexAction()
    {
        return $this->render('ShoploAllegroBundle::homepage.html.twig');
    }

    /**
     * @Secure(roles="ROLE_USER")
     */
    public function footerAction()
    {
        $shoplo = $this->container->get('shoplo');
        $shop   = $shoplo->get('shop');

        return $this->render('ShoploAllegroBundle::footer.html.twig', array('shop' => $shop));
    }

    public function loginAction()
    {
        $url = $this->container->get('hwi_oauth.security.oauth_utils')->getAuthorizationUrl('shoplo');

        return $this->redirect($url);
    }
}
