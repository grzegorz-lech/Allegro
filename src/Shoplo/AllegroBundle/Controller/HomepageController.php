<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HomepageController extends Controller
{
    public function indexAction()
    {
        return $this->redirect($this->generateUrl('shoplo_allegro_settings'));
    }

    public function loginAction()
    {
        $url = $this->container->get('hwi_oauth.security.oauth_utils')->getAuthorizationUrl('shoplo');

        return $this->redirect($url);
    }
}
